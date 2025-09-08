<?php

namespace App\Jobs;

use App\Models\Vehicle;
use App\Services\C4C\VehicleService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para actualizar tipo_valor_trabajo de un vehículo específico
 * consultando el webservice C4C
 */
class UpdateVehicleTipoValorTrabajoJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $vehicleId;
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
     * @param int $vehicleId ID del vehículo a actualizar
     * @param bool $forceUpdate Forzar actualización incluso si ya tiene valor
     */
    public function __construct(int $vehicleId, bool $forceUpdate = false)
    {
        $this->vehicleId = $vehicleId;
        $this->forceUpdate = $forceUpdate;
        $this->onQueue('vehicle-updates');

        Log::info('🚗 UpdateVehicleTipoValorTrabajoJob creado', [
            'vehicle_id' => $vehicleId,
            'force_update' => $forceUpdate,
            'queue' => 'vehicle-updates'
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(VehicleService $vehicleService): void
    {
        Log::info('🔄 Iniciando actualización de tipo_valor_trabajo', [
            'vehicle_id' => $this->vehicleId,
            'force_update' => $this->forceUpdate,
            'attempt' => $this->attempts()
        ]);

        try {
            // Obtener el vehículo
            $vehicle = Vehicle::find($this->vehicleId);
            
            if (!$vehicle) {
                Log::warning('⚠️ Vehículo no encontrado', [
                    'vehicle_id' => $this->vehicleId
                ]);
                return;
            }

            // Verificar si necesita actualización
            if (!$this->forceUpdate && !empty($vehicle->tipo_valor_trabajo)) {
                Log::info('ℹ️ Vehículo ya tiene tipo_valor_trabajo', [
                    'vehicle_id' => $this->vehicleId,
                    'license_plate' => $vehicle->license_plate,
                    'current_value' => $vehicle->tipo_valor_trabajo
                ]);
                return;
            }

            // Consultar C4C por la placa
            Log::info('🌐 Consultando C4C para obtener tipo_valor_trabajo', [
                'vehicle_id' => $this->vehicleId,
                'license_plate' => $vehicle->license_plate
            ]);

            $tipoValorTrabajo = $vehicleService->obtenerTipoValorTrabajoPorPlaca($vehicle->license_plate);

            if (!$tipoValorTrabajo) {
                Log::warning('⚠️ No se pudo obtener tipo_valor_trabajo desde C4C', [
                    'vehicle_id' => $this->vehicleId,
                    'license_plate' => $vehicle->license_plate
                ]);
                return;
            }

            // Verificar si realmente necesita actualización
            if (!$this->forceUpdate && $vehicle->tipo_valor_trabajo === $tipoValorTrabajo) {
                Log::info('ℹ️ Vehículo ya tiene el valor correcto', [
                    'vehicle_id' => $this->vehicleId,
                    'license_plate' => $vehicle->license_plate,
                    'tipo_valor_trabajo' => $tipoValorTrabajo
                ]);
                return;
            }

            // Actualizar el vehículo
            $oldValue = $vehicle->tipo_valor_trabajo;
            $vehicle->tipo_valor_trabajo = $tipoValorTrabajo;
            $vehicle->save();

            Log::info('✅ Tipo valor trabajo actualizado exitosamente', [
                'vehicle_id' => $this->vehicleId,
                'license_plate' => $vehicle->license_plate,
                'old_value' => $oldValue,
                'new_value' => $tipoValorTrabajo,
                'attempt' => $this->attempts()
            ]);

            // Despachar job para actualizar package_id de citas relacionadas
            $this->dispatchPackageIdUpdateJobs($vehicle);

        } catch (\Exception $e) {
            Log::error('❌ Error en UpdateVehicleTipoValorTrabajoJob', [
                'vehicle_id' => $this->vehicleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts()
            ]);

            throw $e;
        }
    }

    /**
     * Despachar jobs para actualizar package_id de citas relacionadas
     */
    protected function dispatchPackageIdUpdateJobs(Vehicle $vehicle): void
    {
        try {
            // Buscar citas activas del vehículo que puedan requerir actualización de package_id
            // (incluye las que ya tienen package_id para permitir corrección si el cálculo difiere)
            $appointments = $vehicle->appointments()
                ->whereNotNull('maintenance_type')
                ->whereIn('status', ['confirmed', 'pending'])
                ->get();

            if ($appointments->isEmpty()) {
                Log::info('ℹ️ No hay citas que requieran actualización de package_id', [
                    'vehicle_id' => $vehicle->id,
                    'license_plate' => $vehicle->license_plate
                ]);
                return;
            }

            Log::info('📦 Despachando jobs para actualizar package_id de citas', [
                'vehicle_id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'appointments_count' => $appointments->count()
            ]);

            // Despachar job para cada cita con un pequeño delay
            foreach ($appointments as $index => $appointment) {
                $delay = now()->addSeconds($index * 5); // 5 segundos entre cada job
                
                // No forzar: el job decidirá sobrescribir si el nuevo package_id difiere del actual
                UpdateAppointmentPackageIdJob::dispatch($appointment->id, false)
                    ->delay($delay);

                Log::info('📤 Job de package_id despachado', [
                    'appointment_id' => $appointment->id,
                    'appointment_number' => $appointment->appointment_number,
                    'delay_seconds' => $index * 5
                ]);
            }

        } catch (\Exception $e) {
            Log::error('❌ Error despachando jobs de package_id', [
                'vehicle_id' => $vehicle->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('💥 UpdateVehicleTipoValorTrabajoJob falló definitivamente', [
            'vehicle_id' => $this->vehicleId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Tags para identificar el job en el dashboard
     */
    public function tags(): array
    {
        return [
            'vehicle-update',
            'tipo-valor-trabajo',
            'vehicle:' . $this->vehicleId
        ];
    }
}
