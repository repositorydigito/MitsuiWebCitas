<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Services\C4C\AppointmentSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job para sincronizar citas con C4C de forma asíncrona
 * Integrado con el proyecto MitsuiWebCitas existente
 */
class SyncAppointmentToC4CJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $appointmentId;
    public int $tries = 3;
    public int $timeout = 120;
    public int $backoff = 60;

    public function __construct(Appointment $appointment)
    {
        $this->appointmentId = $appointment->id;
        $this->onQueue('c4c-sync');
    }

    public function handle(AppointmentSyncService $syncService): void
    {
        $appointment = Appointment::find($this->appointmentId);
        
        if (!$appointment) {
            Log::warning('⚠️ Appointment no encontrada para sincronización', [
                'appointment_id' => $this->appointmentId
            ]);
            return;
        }

        Log::info('🚀 Iniciando job de sincronización C4C', [
            'job_id' => $this->job->getJobId(),
            'appointment_id' => $appointment->id,
            'appointment_number' => $appointment->appointment_number,
            'attempt' => $this->attempts()
        ]);

        try {
            if ($appointment->trashed()) {
                Log::info('ℹ️ Cita eliminada, cancelando sincronización', [
                    'appointment_id' => $appointment->id
                ]);
                return;
            }

            if ($appointment->is_synced && $appointment->c4c_uuid) {
                Log::info('ℹ️ Cita ya sincronizada, omitiendo', [
                    'appointment_id' => $appointment->id,
                    'c4c_uuid' => $appointment->c4c_uuid
                ]);
                return;
            }

            $result = $syncService->syncAppointmentToC4C($appointment);

            if ($result['success']) {
                Log::info('✅ Sincronización C4C completada exitosamente', [
                    'job_id' => $this->job->getJobId(),
                    'appointment_id' => $appointment->id,
                    'c4c_uuid' => $result['c4c_uuid'] ?? 'N/A',
                    'attempt' => $this->attempts()
                ]);

                $this->markAsProcessed($result, $appointment);

            } else {
                Log::error('❌ Error en sincronización C4C', [
                    'job_id' => $this->job->getJobId(),
                    'appointment_id' => $appointment->id,
                    'error' => $result['error'] ?? 'Error desconocido',
                    'attempt' => $this->attempts()
                ]);

                if ($this->shouldRetry($result)) {
                    throw new \Exception($result['error'] ?? 'Error en sincronización C4C');
                } else {
                    $this->markAsFailed($result, $appointment);
                }
            }

        } catch (\Exception $e) {
            Log::error('💥 Excepción en job de sincronización C4C', [
                'job_id' => $this->job->getJobId(),
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $appointment = Appointment::find($this->appointmentId);
        
        Log::error('🔥 Job de sincronización C4C falló definitivamente', [
            'job_id' => $this->job?->getJobId(),
            'appointment_id' => $this->appointmentId,
            'appointment_number' => $appointment?->appointment_number,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        if ($appointment) {
            try {
                $appointment->update([
                    'is_synced' => false,
                    'c4c_status' => 'sync_failed',
                    'synced_at' => null
                ]);

            } catch (\Exception $e) {
                Log::error('💥 Error adicional al manejar fallo del job', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    protected function markAsProcessed(array $result, Appointment $appointment): void
    {
        try {
            $appointment->update([
                'synced_at' => now()
            ]);

            Log::info('📝 Cita marcada como procesada', [
                'appointment_id' => $appointment->id,
                'c4c_uuid' => $result['c4c_uuid'] ?? 'N/A'
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Error marcando cita como procesada', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function markAsFailed(array $result, Appointment $appointment): void
    {
        try {
            $appointment->update([
                'is_synced' => false,
                'c4c_status' => 'sync_failed',
                'synced_at' => null
            ]);

            Log::warning('⚠️ Cita marcada como fallida permanentemente', [
                'appointment_id' => $appointment->id,
                'error' => $result['error'] ?? 'Error desconocido'
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Error marcando cita como fallida', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function shouldRetry(array $result): bool
    {
        $error = $result['error'] ?? '';

        $nonRetryableErrors = [
            'No se encontró el local',
            'Datos inválidos',
            'UUID requerido',
            'Formato incorrecto',
            'Cita ya existe'
        ];

        foreach ($nonRetryableErrors as $nonRetryableError) {
            if (stripos($error, $nonRetryableError) !== false) {
                Log::info('🚫 Error no reintentable detectado', [
                    'appointment_id' => $this->appointment->id,
                    'error' => $error
                ]);
                return false;
            }
        }

        $retryableErrors = [
            'timeout',
            'connection',
            'network',
            'temporary',
            'service unavailable',
            'internal server error'
        ];

        foreach ($retryableErrors as $retryableError) {
            if (stripos($error, $retryableError) !== false) {
                Log::info('🔄 Error reintentable detectado', [
                    'appointment_id' => $this->appointment->id,
                    'error' => $error,
                    'attempt' => $this->attempts()
                ]);
                return true;
            }
        }

        return true;
    }

    public function tags(): array
    {
        return [
            'c4c-sync',
            'appointment:' . $this->appointment->id,
            'premise:' . ($this->appointment->premise->code ?? 'unknown')
        ];
    }
}

