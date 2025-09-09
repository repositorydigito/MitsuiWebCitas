<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\Vehicle;
use App\Services\PackageIdCalculator;
use App\Jobs\DownloadProductsJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para actualizar package_id de citas basándose en:
 * - tipo_valor_trabajo del vehículo
 * - marca del vehículo (Toyota/Hino)
 * - tipo de mantenimiento de la cita
 */
class UpdateAppointmentPackageIdJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected ?int $appointmentId;
    protected bool $forceUpdate;

    /**
     * Número de intentos permitidos
     */
    public int $tries = 3;

    /**
     * Timeout del job en segundos
     */
    public int $timeout = 120;

    /**
     * Backoff entre reintentos (en segundos)
     */
    public array $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     *
     * @param int|null $appointmentId ID específico de cita (null para procesar todas)
     * @param bool $forceUpdate Forzar actualización incluso si ya tiene package_id
     */
    public function __construct(?int $appointmentId = null, bool $forceUpdate = false)
    {
        $this->appointmentId = $appointmentId;
        $this->forceUpdate = $forceUpdate;
        $this->onQueue('package-updates');

        Log::info('🎯 UpdateAppointmentPackageIdJob creado', [
            'appointment_id' => $appointmentId,
            'force_update' => $forceUpdate,
            'queue' => 'package-updates'
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🔄 Iniciando actualización de package_id', [
            'appointment_id' => $this->appointmentId,
            'force_update' => $this->forceUpdate,
            'attempt' => $this->attempts()
        ]);

        try {
            if ($this->appointmentId) {
                // Procesar una cita específica
                $appointment = Appointment::with('vehicle')->find($this->appointmentId);

                if (!$appointment) {
                    Log::warning('⚠️ Cita no encontrada', [
                        'appointment_id' => $this->appointmentId
                    ]);
                    return;
                }

                $this->processAppointment($appointment);
            } else {
                // Procesar todas las citas que necesiten actualización
                $this->processAllAppointments();
            }

            Log::info('✅ Actualización de package_id completada exitosamente', [
                'appointment_id' => $this->appointmentId,
                'attempt' => $this->attempts()
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error en UpdateAppointmentPackageIdJob', [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts()
            ]);

            throw $e;
        }
    }

    /**
     * Procesar todas las citas que necesiten actualización
     */
    protected function processAllAppointments(): void
    {
        $query = Appointment::with('vehicle')
            ->whereNotNull('maintenance_type')
            ->whereHas('vehicle', function ($q) {
                $q->whereNotNull('tipo_valor_trabajo')
                    ->whereIn('brand_code', ['Z01', 'Z02', 'Z03']); // Toyota, Lexus y Hino
            });

        if (!$this->forceUpdate) {
            $query->where(function ($q) {
                $q->whereNull('package_id')
                    ->orWhere('package_id', '');
            });
        }

        $appointments = $query->get();

        Log::info('📊 Citas a procesar', [
            'total' => $appointments->count(),
            'force_update' => $this->forceUpdate
        ]);

        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($appointments as $appointment) {
            try {
                $processed++;
                $result = $this->processAppointment($appointment);

                if ($result['updated']) {
                    $updated++;
                } else {
                    $skipped++;
                }
            } catch (\Exception $e) {
                $errors++;
                Log::error('❌ Error procesando cita individual', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('📈 Resumen de procesamiento', [
            'total_procesadas' => $processed,
            'actualizadas' => $updated,
            'omitidas' => $skipped,
            'errores' => $errors
        ]);
    }

    /**
     * Procesar una cita individual
     */
    protected function processAppointment(Appointment $appointment): array
    {
        Log::info('🔍 Procesando cita', [
            'appointment_id' => $appointment->id,
            'current_package_id' => $appointment->package_id,
            'maintenance_type' => $appointment->maintenance_type,
            'vehicle_id' => $appointment->vehicle_id
        ]);

        if (!$appointment->vehicle) {
            Log::warning('⚠️ Cita sin vehículo asociado', [
                'appointment_id' => $appointment->id
            ]);
            return ['updated' => false, 'reason' => 'no_vehicle'];
        }

        $vehicle = $appointment->vehicle;
        // Calcular nuevo package_id usando el servicio centralizado (antes de decidir sobrescritura)
        $calculator = app(PackageIdCalculator::class);
        $newPackageId = $calculator->calculate($vehicle, $appointment->maintenance_type);

        if (!$newPackageId) {
            Log::warning('⚠️ No se pudo calcular package_id', [
                'appointment_id' => $appointment->id,
                'vehicle_tipo_valor_trabajo' => $vehicle->tipo_valor_trabajo,
                'vehicle_brand_code' => $vehicle->brand_code,
                'maintenance_type' => $appointment->maintenance_type
            ]);
            return ['updated' => false, 'reason' => 'calculation_failed'];
        }

        // ✅ DETECTAR CLIENTE COMODÍN ANTES DE ASIGNAR PACKAGE_ID
        $user = \App\Models\User::where('document_number', $appointment->customer_ruc)->first();
        $isWildcardClient = $user && $user->c4c_internal_id === '1200166011';

        if ($isWildcardClient) {
            Log::info('⚠️ [UpdateAppointmentPackageIdJob] Cliente comodín detectado - NO se actualiza package_id', [
                'appointment_id' => $appointment->id,
                'customer_ruc' => $appointment->customer_ruc,
                'calculated_package_id' => $newPackageId,
                'is_wildcard_client' => true
            ]);
            return ['updated' => false, 'reason' => 'wildcard_client'];
        }

        // Decidir si se sobrescribe el package_id existente
        $current = $appointment->package_id;
        if (!$this->forceUpdate && !empty($current)) {
            if ($current === $newPackageId) {
                Log::info('ℹ️ Package ID ya es el correcto, no se actualiza', [
                    'appointment_id' => $appointment->id,
                    'package_id' => $current
                ]);
                return ['updated' => false, 'reason' => 'no_change'];
            }

            Log::warning('✳️ Package ID difiere, se sobrescribirá', [
                'appointment_id' => $appointment->id,
                'old_package_id' => $current,
                'new_package_id' => $newPackageId
            ]);
        }

        // Limpiar productos previamente vinculados a esta cita para evitar mezclar paquetes
        try {
            \App\Models\Product::forAppointment($appointment->id)->delete();
            Log::info('🧹 Productos de cita eliminados antes de vincular nuevo paquete', [
                'appointment_id' => $appointment->id
            ]);
        } catch (\Exception $e) {
            Log::error('💥 Error eliminando productos previos de la cita', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }

        // Actualizar la cita con el nuevo package_id (solo para clientes normales)
        $appointment->package_id = $newPackageId;
        $appointment->save();

        Log::info('✅ Package ID actualizado', [
            'appointment_id' => $appointment->id,
            'old_package_id' => $current,
            'new_package_id' => $newPackageId
        ]);

        // 🚀 Disparar descarga de productos vinculados del paquete correcto
        $this->dispatchProductDownload($appointment->id, $newPackageId);

        return ['updated' => true, 'package_id' => $newPackageId];
    }

    /**
     * Disparar job de descarga de productos vinculados
     */
    protected function dispatchProductDownload(int $appointmentId, string $packageId): void
    {
        try {
            Log::info('🚀 Disparando descarga de productos', [
                'appointment_id' => $appointmentId,
                'package_id' => $packageId,
                'trigger' => 'package_id_updated'
            ]);

            // Disparar job de descarga de productos con appointment_id
            DownloadProductsJob::dispatch($packageId, $appointmentId)
                ->delay(now()->addSeconds(5)); // Pequeño delay para asegurar que la cita esté guardada

            Log::info('✅ Job de descarga de productos disparado', [
                'appointment_id' => $appointmentId,
                'package_id' => $packageId
            ]);
        } catch (\Exception $e) {
            Log::error('💥 Error disparando job de descarga de productos', [
                'appointment_id' => $appointmentId,
                'package_id' => $packageId,
                'error' => $e->getMessage()
            ]);

            // No re-lanzar la excepción para no fallar el job principal
        }
    }

    /**
     * Tags para identificar el job en el dashboard
     */
    public function tags(): array
    {
        $tags = ['package-id-update'];

        if ($this->appointmentId) {
            $tags[] = 'appointment:' . $this->appointmentId;
        } else {
            $tags[] = 'bulk-update';
        }

        return $tags;
    }
}
