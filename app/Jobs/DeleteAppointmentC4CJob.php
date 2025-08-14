<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\C4C\AppointmentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DeleteAppointmentC4CJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $c4cUuid;
    public int $appointmentId;
    public string $jobId;

    /**
     * Tiempo de vida del job en segundos
     */
    public int $timeout = 300; // 5 minutos

    /**
     * Número de intentos máximos
     */
    public int $tries = 3;

    /**
     * Backoff strategy for retries (seconds)
     */
    public array $backoff = [60, 300, 900]; // 1min, 5min, 15min

    public function __construct(string $c4cUuid, int $appointmentId, string $jobId)
    {
        $this->c4cUuid = $c4cUuid;
        $this->appointmentId = $appointmentId;
        $this->jobId = $jobId;
        
        // Usar cola específica para operaciones C4C
        $this->onQueue('c4c-delete');
    }

    public function handle(): void
    {
        try {
            Log::info('[DeleteAppointmentC4CJob] 🗑️ Iniciando eliminación de cita en C4C', [
                'job_id' => $this->jobId,
                'appointment_id' => $this->appointmentId,
                'c4c_uuid' => $this->c4cUuid,
                'attempt' => $this->attempts(),
            ]);

            // Verificar que la cita aún existe y está en estado válido para eliminación
            $appointment = Appointment::find($this->appointmentId);
            if (!$appointment) {
                throw new \Exception("Appointment not found: {$this->appointmentId}");
            }

            

            // Actualizar status a "processing"
            Cache::put("delete_job_{$this->jobId}", [
                'status' => 'processing',
                'progress' => 25,
                'message' => 'Eliminando cita en C4C...',
                'appointment_id' => $this->appointmentId,
                'c4c_uuid' => $this->c4cUuid,
                'updated_at' => now(),
            ], 600); // 10 minutos

            // Eliminar en C4C
            $appointmentService = app(AppointmentService::class);
            
            // 🔍 LOG DETALLADO antes de eliminar
            Log::info('🔍 [DeleteAppointmentC4CJob] PREPARANDO ELIMINACIÓN', [
                'job_id' => $this->jobId,
                'appointment_id' => $this->appointmentId,
                'c4c_uuid' => $this->c4cUuid,
                'appointment_service_class' => get_class($appointmentService),
                'about_to_call' => 'cancel() method',
                'appointment_local_data' => [
                    'status' => $appointment->status,
                    'c4c_status' => $appointment->c4c_status,
                    'start_date' => $appointment->start_date,
                    'end_date' => $appointment->end_date,
                    'vehicle_plate' => $appointment->vehicle_plate,
                ],
            ]);
            
            // Usar cancel() en lugar de delete() para mantener consistencia
            $cancelData = [
                'uuid' => $this->c4cUuid,
                'action_code' => '04',
                'lifecycle_status' => '2',
                'estado_cita' => '6',
                'viene_hcp' => 'X'
            ];
            
            Log::info('🔍 [DeleteAppointmentC4CJob] DATOS A ENVIAR EN CANCELACIÓN', [
                'job_id' => $this->jobId,
                'cancelData' => $cancelData,
                'c4c_uuid' => $this->c4cUuid,
                'metodo_a_llamar' => 'AppointmentService::cancel()',
            ]);
            
            $resultadoC4C = $appointmentService->cancel($cancelData);
            
            // 🔍 LOG DEL RESULTADO DETALLADO
            Log::info('🔍 [DeleteAppointmentC4CJob] RESULTADO COMPLETO DE ELIMINACIÓN', [
                'job_id' => $this->jobId,
                'resultado_completo' => $resultadoC4C,
                'success' => $resultadoC4C['success'] ?? false,
                'error' => $resultadoC4C['error'] ?? 'ninguno',
                'data_type' => isset($resultadoC4C['data']) ? gettype($resultadoC4C['data']) : 'no_data',
                'data_content' => $resultadoC4C['data'] ?? 'NO DATA AVAILABLE',
            ]);

            if (!$resultadoC4C['success']) {
                throw new \Exception('Error al eliminar cita en C4C: ' . ($resultadoC4C['error'] ?? 'Error desconocido'));
            }

            Log::info('[DeleteAppointmentC4CJob] ✅ Cita eliminada exitosamente en C4C', [
                'appointment_id' => $this->appointmentId,
                'c4c_uuid' => $this->c4cUuid,
                'result' => $resultadoC4C['data'] ?? [],
            ]);

            // Actualizar progress
            Cache::put("delete_job_{$this->jobId}", [
                'status' => 'processing',
                'progress' => 75,
                'message' => 'Actualizando base de datos local...',
                'appointment_id' => $this->appointmentId,
                'updated_at' => now(),
            ], 600);

            // Actualizar el registro en la base de datos local
            $appointment->update([
                'status' => 'cancelled',
                'c4c_status' => '6',
                'is_synced' => true,
                'synced_at' => now(),
            ]);

            Log::info('[DeleteAppointmentC4CJob] 📄 Appointment marcado como cancelado en BD local', [
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
                'c4c_uuid' => $this->c4cUuid,
            ]);

            // Marcar como completado
            Cache::put("delete_job_{$this->jobId}", [
                'status' => 'completed',
                'progress' => 100,
                'message' => '¡Cita cancelada exitosamente!',
                'appointment_id' => $this->appointmentId,
                'appointment_number' => $appointment->appointment_number,
                'updated_at' => now(),
            ], 600);

            Log::info('[DeleteAppointmentC4CJob] ✅ Job de eliminación completado exitosamente', [
                'job_id' => $this->jobId,
                'appointment_id' => $this->appointmentId,
                'c4c_uuid' => $this->c4cUuid,
            ]);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            Log::error('[DeleteAppointmentC4CJob] ❌ Error en job de eliminación', [
                'job_id' => $this->jobId,
                'appointment_id' => $this->appointmentId,
                'c4c_uuid' => $this->c4cUuid,
                'error' => $errorMessage,
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
                'trace' => $e->getTraceAsString(),
            ]);

            // Verificar si es un error fatal (NO reintentable)
            if ($this->isFatalError($errorMessage)) {
                Log::warning('[DeleteAppointmentC4CJob] ⚠️ Error fatal detectado - NO se reintentará', [
                    'job_id' => $this->jobId,
                    'error' => $errorMessage,
                ]);

                // Marcar como fallido permanentemente
                Cache::put("delete_job_{$this->jobId}", [
                    'status' => 'failed',
                    'progress' => 0,
                    'message' => 'Error al eliminar cita: ' . $errorMessage,
                    'appointment_id' => $this->appointmentId,
                    'error' => $errorMessage,
                    'fatal' => true,
                    'updated_at' => now(),
                ], 600);

                // Actualizar appointment como fallido
                if ($appointment = Appointment::find($this->appointmentId)) {
                    $appointment->update([
                        'status' => 'cancel_failed',
                        'c4c_error' => $errorMessage,
                    ]);
                }

                // NO hacer throw para evitar reintentos - usar fail() en su lugar
                $this->fail($e);
                return;
            }

            // Es un error temporal - SÍ se puede reintentar
            Log::info('[DeleteAppointmentC4CJob] 🔄 Error temporal - se reintentará', [
                'job_id' => $this->jobId,
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
            ]);

            // Actualizar estado como reintentando
            Cache::put("delete_job_{$this->jobId}", [
                'status' => 'retrying',
                'progress' => 0,
                'message' => "Reintentando eliminación... (intento {$this->attempts()}/{$this->tries}): " . $errorMessage,
                'appointment_id' => $this->appointmentId,
                'error' => $errorMessage,
                'attempt' => $this->attempts(),
                'updated_at' => now(),
            ], 600);

            // Re-lanzar la excepción para que Laravel maneje los reintentos
            throw $e;
        }
    }

    /**
     * Manejar cuando el job falla después de todos los intentos
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[DeleteAppointmentC4CJob] ❌ Job de eliminación falló después de todos los intentos', [
            'job_id' => $this->jobId,
            'appointment_id' => $this->appointmentId,
            'c4c_uuid' => $this->c4cUuid,
            'error' => $exception->getMessage(),
        ]);

        // Actualizar el appointment como fallido en eliminación
        $appointment = Appointment::find($this->appointmentId);
        if ($appointment) {
            $appointment->update([
                'status' => 'delete_failed',
                'c4c_error' => $exception->getMessage(),
            ]);
        }

        // Actualizar cache con estado final de error
        Cache::put("delete_job_{$this->jobId}", [
            'status' => 'failed',
            'progress' => 0,
            'message' => 'Error al eliminar la cita después de múltiples intentos: ' . $exception->getMessage(),
            'appointment_id' => $this->appointmentId,
            'error' => $exception->getMessage(),
            'updated_at' => now(),
        ], 600);
    }

    

    /**
     * Determinar si el error es fatal (no reintentable) o temporal (reintentable)
     */
    private function isFatalError(string $errorMessage): bool
    {
        // Lista de errores fatales específicos para eliminación
        $fatalErrorPatterns = [
            // Errores de negocio para eliminación
            'Appointment not found',           // Cita no encontrada en C4C
            'Appointment already deleted',     // Cita ya eliminada
            'Cannot delete appointment',       // No se puede eliminar la cita
            'Appointment in progress',         // Cita en progreso (en taller)
            'Appointment completed',           // Cita ya completada
            'Invalid appointment status',      // Estado de cita inválido para eliminación
            'Appointment has active services', // Cita tiene servicios activos

            // Errores de validación
            'Invalid UUID',                    // UUID inválido
            'UUID format error',               // Error de formato UUID
            'Missing required UUID',           // UUID requerido faltante

            // Errores de permisos
            'Unauthorized to delete',          // No autorizado para eliminar
            'Delete permission denied',        // Permiso de eliminación denegado
            'User cannot delete appointment',  // Usuario no puede eliminar cita

            // Errores de configuración
            'Delete service not configured',   // Servicio de eliminación no configurado
            'Invalid delete configuration',    // Configuración de eliminación inválida
        ];

        $errorLower = strtolower($errorMessage);

        Log::info('[DeleteAppointmentC4CJob] 🔍 Analizando error para determinar si es fatal', [
            'job_id' => $this->jobId,
            'error_message' => $errorMessage,
            'error_lower' => $errorLower,
        ]);

        foreach ($fatalErrorPatterns as $pattern) {
            $patternLower = strtolower($pattern);
            if (str_contains($errorLower, $patternLower)) {
                Log::warning('[DeleteAppointmentC4CJob] 🚨 ERROR FATAL DE ELIMINACIÓN DETECTADO!', [
                    'job_id' => $this->jobId,
                    'matched_pattern' => $pattern,
                    'error_message' => $errorMessage,
                ]);

                return true;
            }
        }

        Log::info('[DeleteAppointmentC4CJob] 🔄 Error temporal detectado - se puede reintentar', [
            'job_id' => $this->jobId,
            'error_message' => $errorMessage,
        ]);

        // Si no coincide con ningún patrón fatal, es un error temporal
        return false;
    }
}