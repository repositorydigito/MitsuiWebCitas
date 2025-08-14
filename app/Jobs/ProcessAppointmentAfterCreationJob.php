<?php

namespace App\Jobs;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job coordinador que se ejecuta después de crear una cita
 * para procesar actualizaciones relacionadas en secuencia
 */
class ProcessAppointmentAfterCreationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $appointmentId;

    /**
     * Número de intentos permitidos
     */
    public int $tries = 3;

    /**
     * Timeout del job en segundos
     */
    public int $timeout = 300;

    /**
     * Backoff entre reintentos (en segundos)
     */
    public array $backoff = [60, 120, 300];

    /**
     * Create a new job instance.
     *
     * @param int $appointmentId ID de la cita recién creada
     */
    public function __construct(int $appointmentId)
    {
        $this->appointmentId = $appointmentId;
        $this->onQueue('appointment-processing');

        Log::info('🎯 ProcessAppointmentAfterCreationJob creado', [
            'appointment_id' => $appointmentId,
            'queue' => 'appointment-processing'
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('🚀 Iniciando procesamiento post-creación de cita', [
            'appointment_id' => $this->appointmentId,
            'attempt' => $this->attempts()
        ]);

        try {
            // Obtener la cita con su vehículo
            $appointment = Appointment::with('vehicle')->find($this->appointmentId);
            
            if (!$appointment) {
                Log::warning('⚠️ Cita no encontrada', [
                    'appointment_id' => $this->appointmentId
                ]);
                return;
            }

            if (!$appointment->vehicle) {
                Log::warning('⚠️ Cita sin vehículo asociado', [
                    'appointment_id' => $this->appointmentId
                ]);
                return;
            }

            Log::info('📋 Procesando cita', [
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
                'vehicle_id' => $appointment->vehicle->id,
                'license_plate' => $appointment->vehicle->license_plate,
                'maintenance_type' => $appointment->maintenance_type
            ]);

            // PASO 1: Actualizar tipo_valor_trabajo del vehículo
            $this->updateVehicleTipoValorTrabajo($appointment);

            // PASO 2: Actualizar package_id de la cita (con delay)
            $this->updateAppointmentPackageId($appointment);

            Log::info('✅ Procesamiento post-creación completado', [
                'appointment_id' => $this->appointmentId,
                'attempt' => $this->attempts()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en ProcessAppointmentAfterCreationJob', [
                'appointment_id' => $this->appointmentId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts()
            ]);

            throw $e;
        }
    }

    /**
     * Actualizar tipo_valor_trabajo del vehículo
     */
    protected function updateVehicleTipoValorTrabajo(Appointment $appointment): void
    {
        $vehicle = $appointment->vehicle;

        // Verificar si el vehículo necesita actualización
        $needsUpdate = empty($vehicle->tipo_valor_trabajo) || 
                      in_array($vehicle->brand_code, ['Z01', 'Z02', 'Z03']); // Solo Toyota, Lexus, Hino

        if (!$needsUpdate) {
            Log::info('ℹ️ Vehículo no requiere actualización de tipo_valor_trabajo', [
                'vehicle_id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'brand_code' => $vehicle->brand_code,
                'current_tipo_valor_trabajo' => $vehicle->tipo_valor_trabajo
            ]);
            return;
        }

        Log::info('🚗 Despachando job para actualizar tipo_valor_trabajo', [
            'vehicle_id' => $vehicle->id,
            'license_plate' => $vehicle->license_plate,
            'current_tipo_valor_trabajo' => $vehicle->tipo_valor_trabajo
        ]);

        // Despachar job inmediatamente
        UpdateVehicleTipoValorTrabajoJob::dispatch($vehicle->id, false);
    }

    /**
     * Actualizar package_id de la cita
     */
    protected function updateAppointmentPackageId(Appointment $appointment): void
    {
        // Verificar si la cita necesita package_id
        $needsPackageId = !empty($appointment->maintenance_type) && 
                         (empty($appointment->package_id) || $appointment->package_id === '') &&
                         in_array($appointment->vehicle->brand_code, ['Z01', 'Z02', 'Z03']);

        if (!$needsPackageId) {
            Log::info('ℹ️ Cita no requiere actualización de package_id', [
                'appointment_id' => $appointment->id,
                'maintenance_type' => $appointment->maintenance_type,
                'current_package_id' => $appointment->package_id,
                'vehicle_brand_code' => $appointment->vehicle->brand_code
            ]);
            return;
        }

        Log::info('📦 Despachando job para actualizar package_id', [
            'appointment_id' => $appointment->id,
            'maintenance_type' => $appointment->maintenance_type,
            'vehicle_license_plate' => $appointment->vehicle->license_plate
        ]);

        // Despachar job con delay para que el tipo_valor_trabajo se actualice primero
        UpdateAppointmentPackageIdJob::dispatch($appointment->id, false)
            ->delay(now()->addMinutes(2)); // 2 minutos de delay
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('💥 ProcessAppointmentAfterCreationJob falló definitivamente', [
            'appointment_id' => $this->appointmentId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // Opcional: Notificar a administradores sobre el fallo
        // Notification::route('mail', config('app.admin_email'))
        //     ->notify(new AppointmentProcessingFailedNotification($this->appointmentId, $exception));
    }

    /**
     * Tags para identificar el job en el dashboard
     */
    public function tags(): array
    {
        return [
            'appointment-processing',
            'post-creation',
            'appointment:' . $this->appointmentId
        ];
    }
}
