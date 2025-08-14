<?php

namespace App\Console\Commands;

use App\Jobs\DeleteAppointmentC4CJob;
use App\Models\Appointment;
use App\Services\C4C\AppointmentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class C4CDeleteAppointment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:delete-appointment 
                            {uuid : UUID de la cita a eliminar en C4C}
                            {--sync : Eliminación síncrona sin usar jobs}
                            {--force : Forzar eliminación sin validaciones de negocio}
                            {--confirm : Confirmar eliminación sin preguntar}
                            {--real : Usar servicio real en lugar de mock}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Eliminar una cita existente en C4C con validaciones de negocio y procesamiento asíncrono';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $uuid = $this->argument('uuid');
        $isSync = $this->option('sync');
        $isForce = $this->option('force');
        $isConfirm = $this->option('confirm');

        $this->info("🗑️ Iniciando eliminación de cita: {$uuid}");

        // Validar formato UUID
        if (!$this->isValidUuid($uuid)) {
            $this->error('❌ Formato de UUID inválido');
            return 1;
        }

        // Buscar la cita en la base de datos local
        $appointment = Appointment::where('c4c_uuid', $uuid)->first();
        
        if (!$appointment) {
            $this->warn('⚠️ Cita no encontrada en base de datos local');
            
            if (!$isConfirm && !$this->confirm('¿Continuar con eliminación directa en C4C?')) {
                $this->info('Operación cancelada');
                return 0;
            }
        } else {
            $this->info("📋 Cita encontrada: {$appointment->appointment_number}");
            $this->info("📍 Estado actual: {$appointment->status}");
            $this->info("🔄 Estado C4C: {$appointment->c4c_status}");
            $this->info("🏢 Centro: {$appointment->center_code}");
            $this->info("🚗 Placa: {$appointment->vehicle_plate}");
        }

        // Validaciones de negocio (a menos que se use --force)
        if (!$isForce && $appointment) {
            $validationResult = $this->validateDeletion($appointment);
            if (!$validationResult['valid']) {
                $this->error("❌ {$validationResult['error']}");
                
                if (!$isConfirm && !$this->confirm('¿Forzar eliminación de todas formas? (usa --force para saltar)')) {
                    return 1;
                }
            }
        }

        // Confirmación de seguridad
        if (!$isConfirm && !$this->confirm('¿Está seguro de que desea eliminar esta cita? Esta acción no se puede deshacer.')) {
            $this->info('Operación cancelada por el usuario.');
            return 0;
        }

        if ($this->option('real')) {
            $this->info('Usando servicio real (forzado por opción --real)...');
        } else {
            $this->info('Usando servicio mock (usar --real para servicio real)...');
        }

        if ($isSync) {
            return $this->deleteSynchronously($uuid, $appointment);
        } else {
            return $this->deleteAsynchronously($uuid, $appointment);
        }
    }

    /**
     * Eliminación síncrona
     */
    private function deleteSynchronously(string $uuid, ?Appointment $appointment): int
    {
        $this->info('🔄 Eliminando cita síncronamente...');

        try {
            $appointmentService = app(AppointmentService::class);
            $result = $appointmentService->delete($uuid);

            if ($result['success']) {
                $this->info('✅ Cita eliminada exitosamente en C4C');

                // Actualizar estado local si existe
                if ($appointment) {
                    $appointment->update([
                        'status' => 'deleted',
                        'c4c_status' => 'deleted',
                        'is_synced' => true,
                        'synced_at' => now(),
                        'deleted_at' => now(),
                    ]);
                    $this->info('✅ Estado local actualizado');
                }

                $this->showSuccessDetails($result);
                return 0;

            } else {
                $this->error("❌ Error al eliminar cita: {$result['error']}");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("💥 Excepción: {$e->getMessage()}");
            Log::error('[C4CDeleteAppointment] Error en eliminación síncrona', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);
            return 1;
        }
    }

    /**
     * Eliminación asíncrona
     */
    private function deleteAsynchronously(string $uuid, ?Appointment $appointment): int
    {
        $this->info('⚡ Eliminando cita asíncronamente...');

        try {
            $jobId = Str::uuid()->toString();

            if ($appointment) {
                // Marcar como en proceso de eliminación
                $appointment->update([
                    'status' => 'deleting',
                    'c4c_status' => 'deleting',
                ]);
            }

            // Disparar job
            DeleteAppointmentC4CJob::dispatch(
                $uuid, 
                $appointment?->id ?? 0, 
                $jobId
            )->onQueue('c4c-delete');

            $this->info("✅ Job de eliminación disparado");
            $this->info("🔍 Job ID: {$jobId}");
            $this->info("⏱️ Monitorear con: php artisan c4c:delete-status {$jobId}");

            return 0;

        } catch (\Exception $e) {
            $this->error("💥 Error disparando job: {$e->getMessage()}");
            
            // Revertir estado si hubo error
            if ($appointment) {
                $appointment->update([
                    'status' => $appointment->getOriginal('status'),
                    'c4c_status' => $appointment->getOriginal('c4c_status'),
                ]);
            }

            return 1;
        }
    }

    /**
     * Mostrar detalles de éxito
     */
    private function showSuccessDetails(array $result): void
    {
        $this->info("\n<fg=green;options=bold>--- Confirmación de Eliminación ---</>");

        if (isset($result['data'])) {
            $appointment = $result['data'];
            $this->info('Estado: '.($appointment['status'] ?? 'Eliminada'));

            if (isset($appointment['uuid'])) {
                $this->info('UUID: '.$appointment['uuid']);
            }
            if (isset($appointment['id'])) {
                $this->info('ID: '.$appointment['id']);
            }
            if (isset($appointment['change_state_id'])) {
                $this->info('Change State ID: '.$appointment['change_state_id']);
            }
            if (isset($appointment['message'])) {
                $this->info('Mensaje: '.$appointment['message']);
            }
        }

        // Mostrar warnings si existen
        if (isset($result['warnings']) && !empty($result['warnings'])) {
            $this->info("\n<fg=yellow;options=bold>⚠️ Advertencias:</>");
            foreach ($result['warnings'] as $warning) {
                $this->warn('  - '.$warning);
            }
        }

        $this->info("\n<fg=red;options=bold>⚠️ IMPORTANTE:</> La cita ha sido marcada como eliminada en el sistema.");
        $this->info("🎉 Eliminación completada exitosamente");
    }

    /**
     * Validar si una cita puede ser eliminada
     */
    private function validateDeletion(Appointment $appointment): array
    {
        $allowedStatuses = ['pending', 'confirmed', 'generated'];
        $allowedC4CStatuses = ['1', '2'];

        if (!in_array($appointment->status, $allowedStatuses)) {
            return [
                'valid' => false,
                'error' => "No se puede eliminar cita con estado: {$appointment->status}. Solo se permiten: " . implode(', ', $allowedStatuses),
            ];
        }

        if ($appointment->c4c_status && !in_array($appointment->c4c_status, $allowedC4CStatuses)) {
            $statusNames = ['1' => 'Generada', '2' => 'Confirmada'];
            return [
                'valid' => false,
                'error' => "No se puede eliminar cita con estado C4C: {$appointment->c4c_status}. Solo se permiten: " . implode(', ', array_values($statusNames)),
            ];
        }

        if (in_array($appointment->status, ['deleting', 'delete_failed'])) {
            return [
                'valid' => false,
                'error' => "Cita ya está en proceso de eliminación o falló anteriormente",
            ];
        }

        if ($appointment->deleted_at) {
            return [
                'valid' => false,
                'error' => "Cita ya está eliminada",
            ];
        }

        return [
            'valid' => true,
            'error' => null,
        ];
    }

    /**
     * Validar formato UUID
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
}
