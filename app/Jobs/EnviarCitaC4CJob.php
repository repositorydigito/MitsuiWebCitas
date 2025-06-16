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

class EnviarCitaC4CJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $citaData;

    public array $appointmentData;

    public string $jobId;

    public int $appointmentId;

    /**
     * Tiempo de vida del job en segundos
     */
    public int $timeout = 300; // 5 minutos

    /**
     * Número de intentos máximos
     */
    public int $tries = 3;

    public function __construct(array $citaData, array $appointmentData, string $jobId, int $appointmentId)
    {
        $this->citaData = $citaData;
        $this->appointmentData = $appointmentData;
        $this->jobId = $jobId;
        $this->appointmentId = $appointmentId;
    }

    public function handle(): void
    {
        try {
            Log::info('[EnviarCitaC4CJob] Iniciando envío de cita a C4C', [
                'job_id' => $this->jobId,
                'appointment_id' => $this->appointmentId,
            ]);

            // Actualizar status a "processing"
            Cache::put("cita_job_{$this->jobId}", [
                'status' => 'processing',
                'progress' => 25,
                'message' => 'Enviando cita a C4C...',
                'updated_at' => now(),
            ], 600); // 10 minutos

            // Enviar a C4C
            $appointmentService = app(AppointmentService::class);
            $resultadoC4C = $appointmentService->create($this->citaData);

            if (! $resultadoC4C['success']) {
                throw new \Exception('Error al enviar cita a C4C: '.($resultadoC4C['error'] ?? 'Error desconocido'));
            }

            Log::info('[EnviarCitaC4CJob] ✅ Cita enviada exitosamente a C4C', $resultadoC4C['data'] ?? []);

            // Actualizar progress
            Cache::put("cita_job_{$this->jobId}", [
                'status' => 'processing',
                'progress' => 75,
                'message' => 'Actualizando base de datos...',
                'updated_at' => now(),
            ], 600);

            // Actualizar el registro en la base de datos
            $appointment = Appointment::find($this->appointmentId);
            if ($appointment) {
                $appointment->update([
                    'c4c_uuid' => $resultadoC4C['data']['uuid'] ?? null,
                    'is_synced' => true,
                    'synced_at' => now(),
                    'status' => 'confirmed',
                ]);

                Log::info('[EnviarCitaC4CJob] Appointment actualizado en BD', [
                    'appointment_id' => $appointment->id,
                    'c4c_uuid' => $appointment->c4c_uuid,
                ]);
            }

            // Marcar como completado
            Cache::put("cita_job_{$this->jobId}", [
                'status' => 'completed',
                'progress' => 100,
                'message' => '¡Cita confirmada exitosamente!',
                'appointment_number' => $appointment->appointment_number ?? null,
                'updated_at' => now(),
            ], 600);

            Log::info('[EnviarCitaC4CJob] ✅ Job completado exitosamente', [
                'job_id' => $this->jobId,
                'appointment_id' => $this->appointmentId,
            ]);

        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            Log::error('[EnviarCitaC4CJob] ❌ Error en job', [
                'job_id' => $this->jobId,
                'appointment_id' => $this->appointmentId,
                'error' => $errorMessage,
                'trace' => $e->getTraceAsString(),
            ]);

            // 🚨 VERIFICAR SI ES UN ERROR FATAL (NO REINTENTABLE)
            if ($this->isFatalError($errorMessage)) {
                Log::warning('[EnviarCitaC4CJob] ⚠️ Error fatal detectado - NO se reintentará', [
                    'job_id' => $this->jobId,
                    'error' => $errorMessage,
                ]);

                // Marcar como fallido permanentemente
                Cache::put("cita_job_{$this->jobId}", [
                    'status' => 'failed',
                    'progress' => 0,
                    'message' => 'Error de negocio: '.$errorMessage,
                    'appointment_id' => $this->appointmentId,
                    'error' => $errorMessage,
                    'fatal' => true,
                    'updated_at' => now(),
                ], 600);

                // Actualizar appointment como fallido
                if ($appointment = Appointment::find($this->appointmentId)) {
                    $appointment->update([
                        'status' => 'failed',
                        'c4c_error' => $errorMessage,
                        'is_synced' => false,
                    ]);
                }

                // NO hacer throw para evitar reintentos - usar fail() en su lugar
                $this->fail($e);

                return;
            }

            // 🔄 ES UN ERROR TEMPORAL - SÍ se puede reintentar
            Log::info('[EnviarCitaC4CJob] 🔄 Error temporal - se reintentará', [
                'job_id' => $this->jobId,
                'attempt' => $this->attempts(),
                'max_attempts' => $this->tries,
            ]);

            // Actualizar estado como reintentando
            Cache::put("cita_job_{$this->jobId}", [
                'status' => 'retrying',
                'progress' => 0,
                'message' => "Reintentando... (intento {$this->attempts()}/{$this->tries}): ".$errorMessage,
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
        Log::error('[EnviarCitaC4CJob] ❌ Job falló después de todos los intentos', [
            'job_id' => $this->jobId,
            'appointment_id' => $this->appointmentId,
            'error' => $exception->getMessage(),
        ]);

        // Actualizar el appointment como fallido
        $appointment = Appointment::find($this->appointmentId);
        if ($appointment) {
            $appointment->update([
                'status' => 'failed',
                'is_synced' => false,
            ]);
        }

        // Actualizar cache con estado final de error
        Cache::put("cita_job_{$this->jobId}", [
            'status' => 'failed',
            'progress' => 0,
            'message' => 'Error al procesar la cita: '.$exception->getMessage(),
            'error' => $exception->getMessage(),
            'updated_at' => now(),
        ], 600);
    }

    /**
     * Determinar si el error es fatal (no reintentable) o temporal (reintentable)
     */
    private function isFatalError(string $errorMessage): bool
    {
        // Lista de errores fatales que NO deben reintentarse
        $fatalErrorPatterns = [
            // Errores de negocio
            'ya tiene cita(s) abierta(s)', // El vehículo ya tiene cita abierta
            'Customer not found', // Cliente no encontrado
            'Vehicle not found', // Vehículo no encontrado
            'Invalid customer data', // Datos de cliente inválidos
            'Invalid vehicle data', // Datos de vehículo inválidos
            'Duplicate appointment', // Cita duplicada
            'Invalid appointment date', // Fecha de cita inválida
            'Appointment date in the past', // Fecha en el pasado
            'No available slots', // No hay horarios disponibles
            'Invalid center code', // Código de centro inválido
            'Appointment already exists', // Cita ya existe
            'Invalid maintenance type', // Tipo de mantenimiento inválido

            // Errores de validación
            'Validation failed', // Falla de validación
            'Invalid input', // Entrada inválida
            'Missing required field', // Campo requerido faltante
            'Invalid format', // Formato inválido

            // Errores de permisos
            'Unauthorized', // No autorizado
            'Access denied', // Acceso denegado
            'Permission denied', // Permiso denegado
            'Invalid credentials', // Credenciales inválidas

            // Errores de configuración
            'Service not configured', // Servicio no configurado
            'Invalid configuration', // Configuración inválida
        ];

        $errorLower = strtolower($errorMessage);

        Log::info('[EnviarCitaC4CJob] 🔍 Analizando error para determinar si es fatal', [
            'job_id' => $this->jobId,
            'error_message' => $errorMessage,
            'error_lower' => $errorLower,
        ]);

        foreach ($fatalErrorPatterns as $pattern) {
            $patternLower = strtolower($pattern);
            if (str_contains($errorLower, $patternLower)) {
                Log::warning('[EnviarCitaC4CJob] 🚨 ERROR FATAL DETECTADO!', [
                    'job_id' => $this->jobId,
                    'matched_pattern' => $pattern,
                    'error_message' => $errorMessage,
                ]);

                return true;
            }
        }

        Log::info('[EnviarCitaC4CJob] 🔄 Error temporal detectado - se puede reintentar', [
            'job_id' => $this->jobId,
            'error_message' => $errorMessage,
        ]);

        // Si no coincide con ningún patrón fatal, es un error temporal
        return false;
    }
}
