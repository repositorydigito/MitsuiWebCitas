<?php

namespace App\Jobs;

use App\Models\Appointment;
use App\Models\CenterOrganizationMapping;
use App\Services\C4C\OfferService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateOfferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Appointment $appointment;
    
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
    public array $backoff = [60, 300, 900]; // 1min, 5min, 15min

    /**
     * Tags para identificar el job en el dashboard
     */
    public function tags(): array
    {
        return [
            'offer-creation',
            'appointment:' . $this->appointment->id,
            'customer:' . $this->appointment->customer_id
        ];
    }

    /**
     * Create a new job instance.
     */
    public function __construct(Appointment $appointment)
    {
        $this->appointment = $appointment;
        $this->onQueue('offers'); // Cola específica para ofertas
        
        Log::info('🎯 CreateOfferJob creado', [
            'appointment_id' => $appointment->id,
            'queue' => 'offers'
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(OfferService $offerService): void
    {
        Log::info('🎯 Iniciando creación de oferta', [
            'appointment_id' => $this->appointment->id,
            'attempt' => $this->attempts(),
            'max_tries' => $this->tries
        ]);

        try {
            // Refrescar el modelo para obtener datos actualizados
            $this->appointment->refresh();

            // Validaciones previas
            if (!$this->validarPrerrequisitos()) {
                return;
            }

            // ✅ DETECTAR CLIENTE WILDCARD ANTES DE CREAR OFERTA
            $user = \App\Models\User::where('document_number', $this->appointment->customer_ruc)->first();
            $isWildcardClient = $user && $user->c4c_internal_id === '1200166011';
            
            if ($isWildcardClient) {
                Log::info('🎯 Cliente wildcard detectado - usando método especializado', [
                    'appointment_id' => $this->appointment->id,
                    'customer_ruc' => $this->appointment->customer_ruc,
                    'c4c_internal_id' => $user->c4c_internal_id
                ]);
                
                // Crear oferta con método wildcard
                $result = $offerService->crearOfertaWildcard($this->appointment);
            } else {
                // Crear la oferta (FLUJO ORIGINAL INTACTO)
                $result = $offerService->crearOfertaDesdeCita($this->appointment);
            }

            if ($result['success']) {
                Log::info('✅ Oferta creada exitosamente', [
                    'appointment_id' => $this->appointment->id,
                    'offer_id' => $result['data']['id'] ?? 'N/A',
                    'offer_uuid' => $result['data']['uuid'] ?? 'N/A',
                    'attempt' => $this->attempts()
                ]);

                // Marcar como procesado exitosamente
                $this->markAsProcessed($result['data']);

            } else {
                Log::error('❌ Error creando oferta', [
                    'appointment_id' => $this->appointment->id,
                    'error' => $result['error'],
                    'attempt' => $this->attempts()
                ]);

                // Determinar si el error es reintentable
                if ($this->isRetryableError($result['error'])) {
                    throw new \Exception($result['error']);
                } else {
                    // Error no reintentable, marcar como fallido permanentemente
                    $this->markAsPermanentlyFailed($result['error']);
                }
            }

        } catch (\Exception $e) {
            Log::error('💥 Excepción en CreateOfferJob', [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-lanzar la excepción para que Laravel maneje los reintentos
            throw $e;
        }
    }

    /**
     * Validar prerrequisitos para crear la oferta
     */
    protected function validarPrerrequisitos(): bool
    {
        // 1. Verificar si ya tiene oferta
        if ($this->appointment->c4c_offer_id) {
            Log::info('ℹ️ Cita ya tiene oferta, omitiendo', [
                'appointment_id' => $this->appointment->id,
                'existing_offer_id' => $this->appointment->c4c_offer_id
            ]);
            return false;
        }

        // 2. Verificar que esté sincronizada con C4C
        if (!$this->appointment->is_synced || !$this->appointment->c4c_uuid) {
            Log::warning('⚠️ Cita no sincronizada, posponiendo oferta', [
                'appointment_id' => $this->appointment->id,
                'is_synced' => $this->appointment->is_synced,
                'c4c_uuid' => $this->appointment->c4c_uuid ? 'presente' : 'ausente'
            ]);
            
            // Posponer el job 5 minutos para dar tiempo a la sincronización
            $this->release(300);
            return false;
        }

        // 3. Verificar que tenga paquete ID (SALTAR PARA CLIENTES WILDCARD)
        $user = \App\Models\User::where('document_number', $this->appointment->customer_ruc)->first();
        $isWildcardClient = $user && $user->c4c_internal_id === '1200166011';
        
        if (!$isWildcardClient && !$this->appointment->package_id) {
            Log::warning('⚠️ Cita sin paquete ID, no se puede crear oferta', [
                'appointment_id' => $this->appointment->id,
                'maintenance_type' => $this->appointment->maintenance_type
            ]);

            // Este es un error permanente, no reintentable
            $this->markAsPermanentlyFailed('Cita sin paquete ID');
            return false;
        }
        
        if ($isWildcardClient) {
            Log::info('✅ Cliente wildcard detectado - saltando validación de paquete ID', [
                'appointment_id' => $this->appointment->id,
                'c4c_internal_id' => $user->c4c_internal_id
            ]);
        }

        // ✅ NUEVA VALIDACIÓN: Debe tener vehicle_brand_code
        if (!$this->appointment->vehicle_brand_code) {
            Log::warning('⚠️ Sin vehicle_brand_code, omitiendo creación de oferta', [
                'appointment_id' => $this->appointment->id,
                'vehicle_brand_code' => $this->appointment->vehicle_brand_code
            ]);

            $this->markAsPermanentlyFailed('Cita sin código de marca del vehículo');
            return false;
        }

        // ✅ NUEVA VALIDACIÓN: Debe tener center_code
        if (!$this->appointment->center_code) {
            Log::warning('⚠️ Sin center_code, omitiendo creación de oferta', [
                'appointment_id' => $this->appointment->id,
                'center_code' => $this->appointment->center_code
            ]);

            $this->markAsPermanentlyFailed('Cita sin código de centro');
            return false;
        }

        // ✅ NUEVA VALIDACIÓN: Debe existir mapeo organizacional
        $mapping = CenterOrganizationMapping::forCenterAndBrand(
            $this->appointment->center_code,
            $this->appointment->vehicle_brand_code
        )->first();

        if (!$mapping) {
            Log::error('❌ No existe mapeo organizacional', [
                'appointment_id' => $this->appointment->id,
                'center_code' => $this->appointment->center_code,
                'brand_code' => $this->appointment->vehicle_brand_code
            ]);

            $this->markAsPermanentlyFailed('Configuración organizacional no encontrada para centro: ' .
                                        $this->appointment->center_code . ' y marca: ' .
                                        $this->appointment->vehicle_brand_code);
            return false;
        }

        Log::info('✅ Prerrequisitos validados exitosamente', [
            'appointment_id' => $this->appointment->id,
            'package_id' => $this->appointment->package_id,
            'customer_id' => $this->appointment->customer_id,
            'c4c_uuid' => $this->appointment->c4c_uuid
        ]);

        return true;
    }

    /**
     * Determinar si un error es reintentable
     */
    protected function isRetryableError(string $error): bool
    {
        $retryableErrors = [
            'timeout',
            'connection',
            'network',
            'temporary',
            'server error',
            'service unavailable',
            'gateway timeout'
        ];

        $errorLower = strtolower($error);
        
        foreach ($retryableErrors as $retryableError) {
            if (str_contains($errorLower, $retryableError)) {
                Log::info('🔄 Error identificado como reintentable', [
                    'error' => $error,
                    'pattern' => $retryableError
                ]);
                return true;
            }
        }

        Log::info('🚫 Error identificado como NO reintentable', [
            'error' => $error
        ]);

        return false;
    }

    /**
     * Marcar como procesado exitosamente
     */
    protected function markAsProcessed(array $offerData): void
    {
        try {
            // El OfferService ya actualiza el c4c_offer_id, pero podemos agregar más campos
            $this->appointment->update([
                'offer_created_at' => now(),
                'offer_creation_attempts' => $this->attempts()
            ]);

            Log::info('✅ Cita marcada como procesada', [
                'appointment_id' => $this->appointment->id,
                'offer_id' => $offerData['id'] ?? 'N/A'
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Error marcando cita como procesada', [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Marcar como fallido permanentemente
     */
    protected function markAsPermanentlyFailed(string $reason): void
    {
        try {
            $this->appointment->update([
                'offer_creation_failed' => true,
                'offer_creation_error' => $reason,
                'offer_creation_attempts' => $this->attempts()
            ]);

            Log::warning('⚠️ Cita marcada como fallida permanentemente', [
                'appointment_id' => $this->appointment->id,
                'reason' => $reason
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Error marcando cita como fallida', [
                'appointment_id' => $this->appointment->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('🔥 CreateOfferJob falló definitivamente', [
            'appointment_id' => $this->appointment->id,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'max_tries' => $this->tries
        ]);

        // Marcar como fallido en la base de datos
        $this->markAsPermanentlyFailed($exception->getMessage());

        // Opcional: Enviar notificación a administradores
        $this->notifyAdministrators($exception);
    }

    /**
     * Notificar a administradores sobre el fallo
     */
    protected function notifyAdministrators(\Throwable $exception): void
    {
        try {
            // Aquí puedes implementar notificaciones por email, Slack, etc.
            Log::critical('🚨 Notificación a administradores: CreateOfferJob falló', [
                'appointment_id' => $this->appointment->id,
                'appointment_number' => $this->appointment->appointment_number,
                'customer_id' => $this->appointment->customer_id,
                'error' => $exception->getMessage(),
                'attempts' => $this->attempts()
            ]);

            // Ejemplo de notificación por email (descomenta si tienes configurado)
            /*
            Mail::to(config('app.admin_email'))->send(
                new OfferCreationFailedMail($this->appointment, $exception)
            );
            */

        } catch (\Exception $e) {
            Log::error('💥 Error enviando notificación a administradores', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the middleware the job should pass through.
     */
    public function middleware(): array
    {
        return [
            // Aquí puedes agregar middleware personalizado
            // Por ejemplo, rate limiting, etc.
        ];
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        // El job expira después de 1 hora desde su creación
        return now()->addHour();
    }
}

