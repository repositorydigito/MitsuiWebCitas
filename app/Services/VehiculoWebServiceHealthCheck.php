<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

class VehiculoWebServiceHealthCheck
{
    /**
     * Clave utilizada para guardar el estado en caché
     */
    const CACHE_KEY = 'vehiculo_webservice_health';

    /**
     * Contador de intentos fallidos
     */
    const CACHE_KEY_ATTEMPTS = 'vehiculo_webservice_failed_attempts';

    /**
     * Estado actual del servicio (true = disponible, false = no disponible)
     */
    protected ?bool $isAvailable = null;

    /**
     * Tiempo máximo de espera en segundos
     */
    protected int $timeout;

    /**
     * Número máximo de intentos fallidos permitidos
     */
    protected int $retryAttempts;

    /**
     * Intervalo para verificación de salud en segundos
     */
    protected int $healthCheckInterval;

    /**
     * Constructor del servicio
     *
     * @param  int  $timeout  Tiempo máximo de espera en segundos
     * @param  int  $retryAttempts  Número máximo de intentos fallidos permitidos
     * @param  int  $healthCheckInterval  Intervalo para verificación de salud en segundos
     */
    public function __construct(
        int $timeout = 5,
        int $retryAttempts = 2,
        int $healthCheckInterval = 300
    ) {
        $this->timeout = $timeout;
        $this->retryAttempts = $retryAttempts;
        $this->healthCheckInterval = $healthCheckInterval;
        $this->isAvailable = $this->getStoredStatus();
    }

    /**
     * Verifica si el servicio SOAP está disponible
     *
     * @param  bool  $forceCheck  Forzar verificación sin usar caché
     */
    public function isAvailable(bool $forceCheck = false): bool
    {
        // Si está deshabilitado por configuración, retorna false inmediatamente
        if (! config('vehiculos_webservice.enabled', true)) {
            Log::debug('[VehiculoWebServiceHealthCheck] WebService deshabilitado por configuración');

            return false;
        }

        // Si ya verificamos y no se fuerza verificación, retorna el valor en memoria
        if ($this->isAvailable !== null && ! $forceCheck) {
            return $this->isAvailable;
        }

        // Si hay valor en caché y no se fuerza verificación, retorna ese valor
        if (! $forceCheck && Cache::has(self::CACHE_KEY)) {
            $this->isAvailable = Cache::get(self::CACHE_KEY);

            return $this->isAvailable;
        }

        // Verificar estado actual del servicio
        $this->isAvailable = $this->checkServiceHealth();

        // Almacenar resultado en caché
        Cache::put(self::CACHE_KEY, $this->isAvailable, $this->healthCheckInterval);

        return $this->isAvailable;
    }

    /**
     * Obtiene el estado almacenado en caché
     */
    protected function getStoredStatus(): ?bool
    {
        return Cache::has(self::CACHE_KEY) ? Cache::get(self::CACHE_KEY) : null;
    }

    /**
     * Verifica la salud real del servicio SOAP
     */
    protected function checkServiceHealth(): bool
    {
        Log::info('[VehiculoWebServiceHealthCheck] Verificando disponibilidad del WebService SOAP');

        $wsdlPath = storage_path('wsdl/vehiculos.wsdl');

        if (! file_exists($wsdlPath)) {
            Log::error('[VehiculoWebServiceHealthCheck] WSDL local no encontrado: '.$wsdlPath);
            $this->increaseFailedAttempts();

            return false;
        }

        try {
            // Configuración para la prueba de salud
            $options = [
                'trace' => true,
                'exceptions' => true,
                'connection_timeout' => $this->timeout,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]),
                'login' => config('services.vehiculos.usuario'),
                'password' => config('services.vehiculos.password'),
            ];

            // Intentar crear el cliente SOAP
            $client = new SoapClient($wsdlPath, $options);

            // Si llegamos aquí, el cliente se creó correctamente
            Log::info('[VehiculoWebServiceHealthCheck] Conexión al WebService establecida correctamente');

            // Resetear contador de intentos fallidos
            Cache::forget(self::CACHE_KEY_ATTEMPTS);

            return true;
        } catch (SoapFault $e) {
            Log::error('[VehiculoWebServiceHealthCheck] Error SOAP al verificar disponibilidad: '.$e->getMessage());
            $this->increaseFailedAttempts();

            return false;
        } catch (Exception $e) {
            Log::error('[VehiculoWebServiceHealthCheck] Excepción al verificar disponibilidad: '.$e->getMessage());
            $this->increaseFailedAttempts();

            return false;
        }
    }

    /**
     * Incrementa el contador de intentos fallidos
     *
     * @return int Número actual de intentos fallidos
     */
    protected function increaseFailedAttempts(): int
    {
        $attempts = Cache::get(self::CACHE_KEY_ATTEMPTS, 0) + 1;
        Cache::put(self::CACHE_KEY_ATTEMPTS, $attempts, 3600); // 1 hora

        $maxAttempts = $this->retryAttempts;

        Log::warning("[VehiculoWebServiceHealthCheck] Intento fallido {$attempts}/{$maxAttempts}");

        return $attempts;
    }

    /**
     * Verifica si el circuit breaker está abierto (demasiados fallos)
     */
    public function isCircuitBreakerOpen(): bool
    {
        $attempts = Cache::get(self::CACHE_KEY_ATTEMPTS, 0);
        $maxAttempts = $this->retryAttempts;

        return $attempts >= $maxAttempts;
    }

    /**
     * Resetea el estado del servicio
     */
    public function reset(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY_ATTEMPTS);
        $this->isAvailable = null;

        Log::info('[VehiculoWebServiceHealthCheck] Estado del servicio reseteado');
    }
}
