<?php

namespace App\Services\C4C;

use App\Models\Appointment;
use App\Models\Local;
use App\Services\C4C\AvailabilityService;
use App\Services\C4C\AppointmentService;
use App\Services\C4C\ProductService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para sincronizar citas entre el sistema local y C4C
 * Integrado con el proyecto MitsuiWebCitas existente
 */
class AppointmentSyncService
{
    protected AvailabilityService $availabilityService;
    protected AppointmentService $appointmentService;
    protected ProductService $productService;

    protected array $centerMapping = [
        'M013' => 'L013', // Molina
        'M023' => 'L023', // Canada
        'M303' => 'L303', // Miraflores
        'M313' => 'L313', // Arequipa
        'M033' => 'L033', // Hino
        'L013' => 'L013', // Lexus (mismo código)
    ];

    public function __construct(
        AvailabilityService $availabilityService,
        AppointmentService $appointmentService,
        ProductService $productService
    ) {
        $this->availabilityService = $availabilityService;
        $this->appointmentService = $appointmentService;
        $this->productService = $productService;
    }

    /**
     * Sincronizar una cita local con C4C
     */
    public function syncAppointmentToC4C(Appointment $appointment): array
    {
        try {
            Log::info('🔄 Iniciando sincronización de cita con C4C', [
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
                'premise_code' => $appointment->premise->code ?? 'N/A'
            ]);

            if ($appointment->is_synced && $appointment->c4c_uuid) {
                Log::info('ℹ️ Cita ya sincronizada', [
                    'appointment_id' => $appointment->id,
                    'c4c_uuid' => $appointment->c4c_uuid
                ]);

                return [
                    'success' => true,
                    'already_synced' => true,
                    'c4c_uuid' => $appointment->c4c_uuid,
                    'message' => 'Cita ya sincronizada con C4C'
                ];
            }

            $premise = $appointment->premise;
            if (!$premise) {
                return [
                    'success' => false,
                    'error' => 'No se encontró el local asociado a la cita',
                    'message' => 'Error de datos: local no encontrado'
                ];
            }

            $c4cCenterId = $this->centerMapping[$premise->code] ?? $premise->code;

            $availabilityCheck = $this->availabilityService->checkSlotAvailability(
                $c4cCenterId,
                $appointment->appointment_date->format('Y-m-d'),
                $appointment->appointment_time->format('H:i:s')
            );

            if (!$availabilityCheck['success'] || !$availabilityCheck['available']) {
                Log::warning('⚠️ Slot no disponible en C4C', [
                    'appointment_id' => $appointment->id,
                    'center_id' => $c4cCenterId,
                    'date' => $appointment->appointment_date->format('Y-m-d'),
                    'time' => $appointment->appointment_time->format('H:i:s')
                ]);

                return [
                    'success' => false,
                    'error' => 'El horario seleccionado ya no está disponible en C4C',
                    'message' => 'Conflicto de horario detectado'
                ];
            }

            $c4cData = $this->prepareC4CData($appointment, $c4cCenterId);
            $c4cResponse = $this->appointmentService->createSimple($c4cData);

            if (!$c4cResponse['success']) {
                Log::error('❌ Error creando cita en C4C', [
                    'appointment_id' => $appointment->id,
                    'error' => $c4cResponse['error'] ?? 'Error desconocido'
                ]);

                return [
                    'success' => false,
                    'error' => $c4cResponse['error'] ?? 'Error al crear cita en C4C',
                    'message' => 'Fallo en la sincronización con C4C'
                ];
            }

            $c4cUuid = $this->extractUuidFromResponse($c4cResponse);

            if (!$c4cUuid) {
                Log::warning('⚠️ No se pudo extraer UUID de la respuesta C4C', [
                    'appointment_id' => $appointment->id,
                    'c4c_response' => $c4cResponse
                ]);

                return [
                    'success' => false,
                    'error' => 'No se pudo obtener el UUID de C4C',
                    'message' => 'Respuesta incompleta de C4C'
                ];
            }

            // ✅ OBTENER PACKAGE_ID desde ProductService con lógica dinámica
            $packageId = null;
            if ($appointment->maintenance_type) {
                // Cargar vehículo si no está cargado
                if (!$appointment->relationLoaded('vehicle')) {
                    $appointment->load('vehicle');
                }

                $packageId = $this->productService->obtenerPaquetePorTipo(
                    $appointment->maintenance_type,
                    $appointment->vehicle
                );

                Log::info('📦 Package ID obtenido dinámicamente durante sincronización', [
                    'appointment_id' => $appointment->id,
                    'maintenance_type' => $appointment->maintenance_type,
                    'package_id' => $packageId,
                    'vehicle_tipo_valor_trabajo' => $appointment->vehicle?->tipo_valor_trabajo,
                    'vehicle_brand_code' => $appointment->vehicle?->brand_code
                ]);
            } else {
                Log::warning('⚠️ No se puede calcular package_id sin maintenance_type', [
                    'appointment_id' => $appointment->id
                ]);
            }

            $appointment->update([
                'c4c_uuid' => $c4cUuid,
                'package_id' => $packageId, // ✅ AGREGAR PACKAGE_ID
                'is_synced' => true,
                'synced_at' => now(),
                'c4c_status' => 'created'
            ]);

            Log::info('✅ Cita sincronizada exitosamente', [
                'appointment_id' => $appointment->id,
                'c4c_uuid' => $c4cUuid,
                'package_id' => $packageId
            ]);

            return [
                'success' => true,
                'c4c_uuid' => $c4cUuid,
                'package_id' => $packageId,
                'synced_at' => $appointment->synced_at,
                'message' => 'Cita sincronizada exitosamente con C4C'
            ];

        } catch (\Exception $e) {
            Log::error('💥 Excepción en sincronización con C4C', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error interno en la sincronización'
            ];
        }
    }

    /**
     * Preparar datos para enviar a C4C
     */
    protected function prepareC4CData(Appointment $appointment, string $c4cCenterId): array
    {
        $startDateTime = Carbon::parse($appointment->appointment_date->format('Y-m-d') . ' ' . $appointment->appointment_time->format('H:i:s'));
        $endDateTime = $appointment->appointment_end_time ? 
            Carbon::parse($appointment->appointment_end_time) : 
            $startDateTime->copy()->addMinutes($this->calculateDuration($appointment));

        return [
            'customer_id' => $appointment->customer_ruc,
            'customer_name' => $appointment->customer_name . ' ' . $appointment->customer_last_name,
            'start_date' => $startDateTime->toISOString(),
            'end_date' => $endDateTime->toISOString(),
            'license_plate' => $appointment->vehicle->license_plate ?? 'N/A',
            'center_id' => $c4cCenterId,
            'notes' => $appointment->comments ?? 'Cita creada desde sistema local',
            'is_express' => $appointment->service_mode === 'express' ? 'true' : 'false'
        ];
    }

    /**
     * Extraer UUID de la respuesta de C4C
     */
    protected function extractUuidFromResponse(array $response): ?string
    {
        if (isset($response['data']['uuid'])) {
            return $response['data']['uuid'];
        }

        if (isset($response['data']['ObjectID'])) {
            return $response['data']['ObjectID'];
        }

        if (isset($response['data']['appointment_id'])) {
            return $response['data']['appointment_id'];
        }

        if (isset($response['data']) && is_array($response['data'])) {
            foreach ($response['data'] as $key => $value) {
                if (is_string($value) && strlen($value) === 32) {
                    return $value;
                }
            }
        }

        return null;
    }



    /**
     * Calcular duración de la cita en minutos
     */
    protected function calculateDuration(Appointment $appointment): int
    {
        $durations = [
            '5,000 Km' => 45,
            '10,000 Km' => 60,
            '20,000 Km' => 90,
            '40,000 Km' => 120,
            'express' => 30,
            'regular' => 60
        ];

        return $durations[$appointment->maintenance_type] ??
               $durations[$appointment->service_mode] ??
               $durations['regular'];
    }
}

