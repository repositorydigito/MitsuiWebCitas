<?php

namespace App\Services\C4C;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class VehicleService
{
    protected ?string $baseUrl = null;
    protected ?string $username = null;
    protected ?string $password = null;
    protected ?int $timeout = null;
    private bool $initialized = false;

    /**
     * Inicializar configuración de manera lazy (solo cuando se necesita)
     */
    protected function initializeConfig(): void
    {
        if ($this->initialized) {
            return;
        }

        // Usar tanto config() como env() como fallback para máxima compatibilidad
        $this->baseUrl = config('services.c4c_vehicles.url') ?: env('C4C_VEHICLES_URL', '');
        $this->username = config('services.c4c_vehicles.username') ?: env('C4C_VEHICLES_USERNAME', '_ODATA');
        $this->password = config('services.c4c_vehicles.password') ?: env('C4C_VEHICLES_PASSWORD', '');
        $this->timeout = config('services.c4c_vehicles.timeout') ?: env('C4C_TIMEOUT', 120);

        // Si aún no tenemos la URL, intentar directamente desde $_ENV como último recurso
        if (empty($this->baseUrl)) {
            $this->baseUrl = $_ENV['C4C_VEHICLES_URL'] ?? '';
        }

        // Validación solo cuando se inicializa realmente
        if (empty($this->baseUrl)) {
            throw new \Exception('C4C_VEHICLES_URL no está configurada en el archivo .env. Verificar configuración del servicio.');
        }

        $this->initialized = true;

        // Log::info('🔧 VehicleService inicializado correctamente', [
        //     'base_url_configured' => !empty($this->baseUrl),
        //     'base_url_preview' => substr($this->baseUrl, 0, 50) . '...',
        //     'username' => $this->username,
        //     'timeout' => $this->timeout
        // ]);
    }

    /**
     * Obtener vehículo por placa
     */
    public function obtenerVehiculoPorPlaca(string $placa): array
    {
        $this->initializeConfig(); // Inicializar solo cuando se usa

        try {
            $cacheKey = "c4c_vehicle_placa_{$placa}";

            return Cache::remember($cacheKey, 300, function () use ($placa) {
                $queryParams = [
                    '$format' => 'json',
                    '$filter' => "zPlaca eq '{$placa}'"
                ];

                // Log::info('🚗 Consultando vehículo por placa', [
                //     'placa' => $placa,
                //     'base_url' => $this->baseUrl,
                //     'query_params' => $queryParams
                // ]);

                $response = Http::withBasicAuth($this->username, $this->password)
                    ->timeout($this->timeout)
                    ->get($this->baseUrl, $queryParams);

                if ($response->successful()) {
                    $data = $response->json();
                    $vehiculos = $data['d']['results'] ?? [];

                    if (!empty($vehiculos)) {
                        $vehiculo = $vehiculos[0]; // Tomar el primer resultado

                        // Log::info('✅ Vehículo encontrado por placa', [
                        //     'placa' => $placa,
                        //     'ObjectID' => $vehiculo['ObjectID'] ?? 'N/A',
                        //     'zModelo' => $vehiculo['zModelo'] ?? 'N/A',
                        //     'zDescMarca' => $vehiculo['zDescMarca'] ?? 'N/A',
                        //     'zTipoValorTrabajo' => $vehiculo['zTipoValorTrabajo'] ?? 'N/A'
                        // ]);

                        return [
                            'success' => true,
                            'found' => true,
                            'data' => $vehiculo,
                            'total' => count($vehiculos)
                        ];
                    } else {
                        // Log::warning('⚠️ Vehículo no encontrado por placa', [
                        //     'placa' => $placa
                        // ]);

                        return [
                            'success' => true,
                            'found' => false,
                            'data' => null,
                            'total' => 0
                        ];
                    }
                } else {
                    // Log::error('💥 Error HTTP en consulta de vehículo', [
                    //     'placa' => $placa,
                    //     'status' => $response->status(),
                    //     'body' => $response->body()
                    // ]);

                    return [
                        'success' => false,
                        'found' => false,
                        'error' => "HTTP {$response->status()}: {$response->body()}",
                        'data' => null
                    ];
                }
            });

        } catch (\Exception $e) {
            // Log::error('💥 Error obteniendo vehículo por placa', [
            //     'error' => $e->getMessage(),
            //     'placa' => $placa
            // ]);

            return [
                'success' => false,
                'found' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }

    /**
     * Obtener tipo_valor_trabajo por placa específicamente
     */
    public function obtenerTipoValorTrabajoPorPlaca(string $placa): ?string
    {
        $this->initializeConfig(); // Inicializar solo cuando se usa

        try {
            // Log::info('🔧 Consultando tipo_valor_trabajo por placa', [
            //     'placa' => $placa
            // ]);

            $resultado = $this->obtenerVehiculoPorPlaca($placa);

            if ($resultado['success'] && $resultado['found'] && $resultado['data']) {
                $tipoValorTrabajo = $resultado['data']['zTipoValorTrabajo'] ?? null;

                // Log::info('✅ Tipo valor trabajo obtenido', [
                //     'placa' => $placa,
                //     'tipo_valor_trabajo' => $tipoValorTrabajo
                // ]);

                return $tipoValorTrabajo;
            }

            // Log::warning('⚠️ No se pudo obtener tipo_valor_trabajo', [
            //     'placa' => $placa,
            //     'resultado' => $resultado
            // ]);

            return null;

        } catch (\Exception $e) {
            // Log::error('💥 Error obteniendo tipo_valor_trabajo por placa', [
            //     'error' => $e->getMessage(),
            //     'placa' => $placa
            // ]);

            return null;
        }
    }

    /**
     * Obtener vehículos por cliente
     */
    public function obtenerVehiculosPorCliente(string $clienteId): array
    {
        $this->initializeConfig(); // Inicializar solo cuando se usa

        try {
            $cacheKey = "c4c_vehicles_cliente_{$clienteId}";
            
            return Cache::remember($cacheKey, 300, function () use ($clienteId) {
                $url = $this->baseUrl . "?\$filter=CustomerID eq '{$clienteId}'";

                // Log::info('🚗 Consultando vehículos por cliente', [
                //     'cliente_id' => $clienteId,
                //     'url' => $url
                // ]);

                $response = $this->makeRequest($url);
                
                if ($response['success']) {
                    $vehiculos = $response['data']->d->results ?? [];
                    
                    // Log::info('✅ Vehículos obtenidos por cliente', [
                    //     'cliente_id' => $clienteId,
                    //     'total_vehiculos' => count($vehiculos),
                    //     'primeros_3' => array_slice(array_map(function($v) {
                    //         return [
                    //             'placa' => $v->zPlaca ?? 'N/A',
                    //             'modelo' => $v->Model ?? 'N/A',
                    //             'marca' => $v->Brand ?? 'N/A'
                    //         ];
                    //     }, $vehiculos), 0, 3)
                    // ]);

                    return [
                        'success' => true,
                        'data' => $vehiculos,
                        'total' => count($vehiculos)
                    ];
                }

                return $response;
            });

        } catch (\Exception $e) {
            // Log::error('💥 Error obteniendo vehículos por cliente', [
            //     'error' => $e->getMessage(),
            //     'cliente_id' => $clienteId
            // ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Validar existencia de placa
     */
    public function validarPlacaExiste(string $placa): array
    {
        $this->initializeConfig(); // Inicializar solo cuando se usa

        try {
            $resultado = $this->obtenerVehiculoPorPlaca($placa);
            
            return [
                'exists' => $resultado['found'] ?? false,
                'data' => $resultado['data'] ?? null
            ];

        } catch (\Exception $e) {
            // Log::error('💥 Error validando placa', [
            //     'error' => $e->getMessage(),
            //     'placa' => $placa
            // ]);

            return [
                'exists' => false,
                'data' => null
            ];
        }
    }

    /**
     * Obtener todos los vehículos (con paginación)
     */
    public function obtenerTodosLosVehiculos(int $skip = 0, int $top = 50): array
    {
        $this->initializeConfig(); // Inicializar solo cuando se usa

        try {
            $cacheKey = "c4c_all_vehicles_{$skip}_{$top}";
            
            return Cache::remember($cacheKey, 600, function () use ($skip, $top) {
                $url = $this->baseUrl . "?\$skip={$skip}&\$top={$top}";

                // Log::info('🚗 Consultando todos los vehículos', [
                //     'skip' => $skip,
                //     'top' => $top,
                //     'url' => $url
                // ]);

                $response = $this->makeRequest($url);
                
                if ($response['success']) {
                    $vehiculos = $response['data']->d->results ?? [];
                    
                    // Log::info('✅ Vehículos obtenidos (paginados)', [
                    //     'skip' => $skip,
                    //     'top' => $top,
                    //     'total_obtenidos' => count($vehiculos)
                    // ]);

                    return [
                        'success' => true,
                        'data' => $vehiculos,
                        'total' => count($vehiculos),
                        'skip' => $skip,
                        'top' => $top
                    ];
                }

                return $response;
            });

        } catch (\Exception $e) {
            // Log::error('💥 Error obteniendo todos los vehículos', [
            //     'error' => $e->getMessage(),
            //     'skip' => $skip,
            //     'top' => $top
            // ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Buscar vehículos por modelo
     */
    public function buscarVehiculosPorModelo(string $modelo): array
    {
        $this->initializeConfig(); // Inicializar solo cuando se usa

        try {
            $url = $this->baseUrl . "?\$filter=substringof('{$modelo}',Model)";

            // Log::info('🚗 Buscando vehículos por modelo', [
            //     'modelo' => $modelo,
            //     'url' => $url
            // ]);

            $response = $this->makeRequest($url);
            
            if ($response['success']) {
                $vehiculos = $response['data']->d->results ?? [];
                
                // Log::info('✅ Vehículos encontrados por modelo', [
                //     'modelo' => $modelo,
                //     'total_encontrados' => count($vehiculos)
                // ]);

                return [
                    'success' => true,
                    'data' => $vehiculos,
                    'total' => count($vehiculos)
                ];
            }

            return $response;

        } catch (\Exception $e) {
            // Log::error('💥 Error buscando vehículos por modelo', [
            //     'error' => $e->getMessage(),
            //     'modelo' => $modelo
            // ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Health check del servicio de vehículos
     */
    public function healthCheck(): array
    {
        try {
            $this->initializeConfig(); // Inicializar para health check
            
            // Log::info('🏥 Verificando salud del servicio de vehículos');

            // Hacer una consulta simple para verificar conectividad
            $url = $this->baseUrl . '?\$top=1';
            $response = $this->makeRequest($url);

            if ($response['success']) {
                // Log::info('✅ Servicio de vehículos saludable');
            }

            return [
                'success' => true,
                'message' => 'Servicio de vehículos operativo'
            ];

        } catch (\Exception $e) {
            Log::error('💥 Error en health check de vehículos', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error interno en servicio de vehículos'
            ];
        }
    }

    /**
     * Realizar petición HTTP a la API
     */
    protected function makeRequest(string $url): array
    {
        $this->initializeConfig(); // Asegurar inicialización antes de hacer requests

        $startTime = microtime(true);

        Log::info('🌐 Iniciando petición HTTP', [
            'url' => $url,
            'username' => $this->username,
            'password_length' => strlen($this->password),
            'timeout' => $this->timeout
        ]);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_USERPWD => $this->username . ':' . $this->password,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_VERBOSE => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        if ($error) {
            Log::error('💥 Error cURL en VehicleService', [
                'error' => $error,
                'url' => $url
            ]);

            return [
                'success' => false,
                'error' => "cURL Error: {$error}",
                'data' => null,
                'response_time' => $responseTime
            ];
        }

        if ($httpCode !== 200) {
            Log::error('💥 Error HTTP en VehicleService', [
                'http_code' => $httpCode,
                'url' => $url,
                'response_preview' => substr($response, 0, 500)
            ]);

            return [
                'success' => false,
                'error' => "HTTP {$httpCode}",
                'data' => null,
                'response_time' => $responseTime
            ];
        }

        $data = json_decode($response);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('💥 Error JSON en VehicleService', [
                'json_error' => json_last_error_msg(),
                'response_preview' => substr($response, 0, 500)
            ]);

            return [
                'success' => false,
                'error' => 'Invalid JSON response',
                'data' => null,
                'response_time' => $responseTime
            ];
        }

        Log::info('✅ Petición exitosa a VehicleService', [
            'url' => $url,
            'http_code' => $httpCode,
            'response_time' => $responseTime . 'ms'
        ]);

        return [
            'success' => true,
            'data' => $data,
            'http_code' => $httpCode,
            'response_time' => $responseTime
        ];
    }
}
