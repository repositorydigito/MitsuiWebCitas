<?php

namespace App\Services\C4C;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\PackageIdCalculator;

class ProductService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;

    public function __construct()
    {
        $this->baseUrl = env('C4C_PRODUCTS_URL', 'https://my317791.crm.ondemand.com/sap/c4c/odata/cust/v1/obtenerlistadoproductos/BOListaProductosProductosVinculadosCollection');
        $this->username = env('C4C_PRODUCTS_USERNAME', '_ODATA');
        $this->password = env('C4C_PRODUCTS_PASSWORD');
        $this->timeout = env('C4C_TIMEOUT', 120);
    }

    /**
     * Obtener lista completa de productos desde C4C
     */
    public function obtenerListaProductos(array $filtros = []): array
    {
        try {
            $cacheKey = 'c4c_products_' . md5(json_encode($filtros));

            return Cache::remember($cacheKey, 300, function () use ($filtros) {
                $url = $this->baseUrl;

                // Agregar filtros OData si se proporcionan
                if (!empty($filtros)) {
                    $filterQuery = [];
                    foreach ($filtros as $campo => $valor) {
                        $filterQuery[] = "{$campo} eq '{$valor}'";
                    }
                    $filterString = implode(' and ', $filterQuery);
                    $url .= '?$filter=' . urlencode($filterString);
                }

                Log::info('🔍 Consultando productos C4C', [
                    'url' => $url,
                    'filtros' => $filtros
                ]);

                $response = $this->makeRequest($url);

                if ($response['success']) {
                    $productos = $response['data']->d->results ?? [];

                    Log::info('✅ Productos obtenidos exitosamente', [
                        'total' => count($productos),
                        'primeros_3' => array_slice(array_map(function ($p) {
                            return [
                                'id' => $p->ProductID ?? 'N/A',
                                'descripcion' => $p->Description ?? 'N/A',
                                'estado' => $p->zEstado ?? 'N/A'
                            ];
                        }, $productos), 0, 3)
                    ]);

                    return [
                        'success' => true,
                        'data' => $productos,
                        'total' => count($productos)
                    ];
                }

                return $response;
            });
        } catch (\Exception $e) {
            Log::error('💥 Error obteniendo lista de productos', [
                'error' => $e->getMessage(),
                'filtros' => $filtros
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Obtener productos vinculados por package_id desde C4C
     * Método específico para DownloadProductsJob
     */
    public function obtenerProductosVinculados(array $filtros = []): array
    {
        try {
            // Cache específico para productos vinculados
            $cacheKey = 'c4c_productos_vinculados_' . md5(json_encode($filtros));

            return Cache::remember($cacheKey, 300, function () use ($filtros) {
                $url = $this->baseUrl;

                // Agregar filtros OData específicos para productos vinculados
                if (!empty($filtros)) {
                    $filterQuery = [];
                    foreach ($filtros as $campo => $valor) {
                        $filterQuery[] = "{$campo} eq '{$valor}'";
                    }
                    $filterString = implode(' and ', $filterQuery);
                    $url .= '?$filter=' . urlencode($filterString);
                }

                Log::info('🔍 Consultando productos vinculados C4C', [
                    'url' => $url,
                    'url_length' => strlen($url),
                    'base_url' => $this->baseUrl,
                    'filtros' => $filtros,
                    'endpoint' => 'BOListaProductosProductosVinculadosCollection'
                ]);

                $response = $this->makeRequest($url);

                if ($response['success']) {
                    $productos = $response['data']->d->results ?? [];

                    // ✅ LOGGING TEMPORAL: Ver JSON completo que llega desde C4C
                    if (!empty($productos)) {
                        Log::info('🔍 JSON COMPLETO DESDE C4C - PRIMER PRODUCTO', [
                            'package_id' => $filtros['zIDPadre'] ?? 'N/A',
                            'primer_producto_completo' => json_encode($productos[0], JSON_PRETTY_PRINT),
                            'campos_cantidad' => [
                                'zCantidad' => $productos[0]->zCantidad ?? 'NULL',
                                'zZMENG' => $productos[0]->zZMENG ?? 'NULL',
                                'zMENGE' => $productos[0]->zMENGE ?? 'NULL',
                                'unitCode' => $productos[0]->unitCode ?? 'NULL',
                                'unitCode1' => $productos[0]->unitCode1 ?? 'NULL',
                                'unitCode2' => $productos[0]->unitCode2 ?? 'NULL'
                            ]
                        ]);
                    }

                    Log::info('✅ Productos vinculados obtenidos', [
                        'total' => count($productos),
                        'package_id' => $filtros['zIDPadre'] ?? 'N/A',
                        'estado_filtro' => $filtros['zEstado'] ?? 'N/A',
                        'tipos_encontrados' => array_count_values(array_map(function ($p) {
                            return $p->zTipoPosicion ?? 'N/A';
                        }, $productos))
                    ]);

                    return [
                        'success' => true,
                        'data' => $productos,
                        'total' => count($productos),
                        'package_id' => $filtros['zIDPadre'] ?? null
                    ];
                }

                return $response;
            });
        } catch (\Exception $e) {
            Log::error('💥 Error obteniendo productos vinculados', [
                'error' => $e->getMessage(),
                'filtros' => $filtros,
                'endpoint' => 'BOListaProductosProductosVinculadosCollection'
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => [],
                'package_id' => $filtros['zIDPadre'] ?? null
            ];
        }
    }

    /**
     * Obtener paquete ID basado en el tipo de mantenimiento y vehículo
     * SOLO USA LÓGICA DINÁMICA basada en tipo_valor_trabajo del vehículo
     */
    public function obtenerPaquetePorTipo(string $tipoMantenimiento, ?\App\Models\Vehicle $vehicle = null): ?string
    {
        try {
            Log::info('📦 Calculando package_id dinámicamente', [
                'tipo_mantenimiento' => $tipoMantenimiento,
                'vehicle_id' => $vehicle?->id,
                'vehicle_tipo_valor_trabajo' => $vehicle?->tipo_valor_trabajo,
                'vehicle_brand_code' => $vehicle?->brand_code
            ]);

            // Requiere vehículo para calcular package_id
            if (!$vehicle) {
                Log::warning('⚠️ No se puede calcular package_id sin vehículo', [
                    'tipo_mantenimiento' => $tipoMantenimiento
                ]);
                return null;
            }

            $packageId = $this->calculateDynamicPackageId($vehicle, $tipoMantenimiento);

            if ($packageId) {
                Log::info('✅ Package ID calculado dinámicamente', [
                    'package_id' => $packageId,
                    'tipo_mantenimiento' => $tipoMantenimiento,
                    'vehicle_tipo_valor_trabajo' => $vehicle->tipo_valor_trabajo
                ]);
                return $packageId;
            }

            Log::warning('⚠️ No se pudo calcular package_id dinámicamente', [
                'vehicle_id' => $vehicle->id,
                'vehicle_tipo_valor_trabajo' => $vehicle->tipo_valor_trabajo,
                'vehicle_brand_code' => $vehicle->brand_code,
                'tipo_mantenimiento' => $tipoMantenimiento
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('💥 Error obteniendo paquete por tipo', [
                'error' => $e->getMessage(),
                'tipo_mantenimiento' => $tipoMantenimiento,
                'vehicle_id' => $vehicle?->id
            ]);
            return null;
        }
    }

    /**
     * Calcular package_id dinámicamente usando el servicio centralizado
     */
    protected function calculateDynamicPackageId(\App\Models\Vehicle $vehicle, string $maintenanceType): ?string
    {
        $calculator = app(PackageIdCalculator::class);
        return $calculator->calculate($vehicle, $maintenanceType);
    }

    /**
     * Calcular package_id con código de servicio o campaña
     */
    protected function calculatePackageIdWithCode(\App\Models\Vehicle $vehicle, string $code): ?string
    {
        $calculator = app(PackageIdCalculator::class);
        return $calculator->calculateWithCode($vehicle, $code);
    }

    /**
     * Calcular package_id con sistema de prioridades
     * PRIORIDAD 1: Mantenimiento periódico
     * PRIORIDAD 2: Servicios adicionales
     * PRIORIDAD 3: Campañas
     */
    public function calculatePackageIdWithPriority(
        \App\Models\Vehicle $vehicle,
        ?string $maintenanceType = null,
        ?array $additionalServices = null,
        ?array $campaigns = null
    ): ?string {
        try {
            Log::info('📦 Calculando package_id con prioridades', [
                'vehicle_id' => $vehicle->id,
                'vehicle_tipo_valor_trabajo' => $vehicle->tipo_valor_trabajo,
                'maintenance_type' => $maintenanceType,
                'additional_services_count' => count($additionalServices ?? []),
                'additional_services' => $additionalServices,
                'campaigns_count' => count($campaigns ?? []),
                'campaigns' => $campaigns
            ]);

            // PRIORIDAD 1: Mantenimiento periódico
            if (!empty($maintenanceType)) {
                $packageId = $this->calculateDynamicPackageId($vehicle, $maintenanceType);
                if ($packageId) {
                    Log::info('✅ Package ID calculado con prioridad 1 (mantenimiento)', [
                        'package_id' => $packageId,
                        'maintenance_type' => $maintenanceType
                    ]);
                    return $packageId;
                }
            }

            // PRIORIDAD 2: Servicios adicionales
            if (!empty($additionalServices)) {
                $firstService = reset($additionalServices);
                if (!empty($firstService['code'])) {
                    $packageId = $this->calculatePackageIdWithCode($vehicle, $firstService['code']);
                    if ($packageId) {
                        Log::info('✅ Package ID calculado con prioridad 2 (servicio)', [
                            'package_id' => $packageId,
                            'service_code' => $firstService['code']
                        ]);
                        return $packageId;
                    }
                }
            }

            // PRIORIDAD 3: Campañas
            if (!empty($campaigns)) {
                $firstCampaign = reset($campaigns);
                if (!empty($firstCampaign['code'])) {
                    $packageId = $this->calculatePackageIdWithCode($vehicle, $firstCampaign['code']);
                    if ($packageId) {
                        Log::info('✅ Package ID calculado con prioridad 3 (campaña)', [
                            'package_id' => $packageId,
                            'campaign_code' => $firstCampaign['code']
                        ]);
                        return $packageId;
                    }
                }
            }

            Log::warning('⚠️ No se pudo calcular package_id con ninguna prioridad', [
                'vehicle_id' => $vehicle->id,
                'maintenance_type' => $maintenanceType,
                'additional_services' => $additionalServices,
                'campaigns' => $campaigns
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('💥 Error calculando package_id con prioridades', [
                'error' => $e->getMessage(),
                'vehicle_id' => $vehicle->id
            ]);
            return null;
        }
    }





    /**
     * Validar que un paquete existe en C4C
     */
    public function validarPaqueteExiste(string $paqueteId): array
    {
        try {
            $cacheKey = "c4c_package_exists_{$paqueteId}";

            return Cache::remember($cacheKey, 600, function () use ($paqueteId) {
                $filtros = ['ProductID' => $paqueteId, 'zEstado' => '02'];
                $resultado = $this->obtenerListaProductos($filtros);

                $exists = $resultado['success'] && !empty($resultado['data']);

                Log::info('🔍 Validación de paquete', [
                    'paquete_id' => $paqueteId,
                    'exists' => $exists,
                    'total_encontrados' => $exists ? count($resultado['data']) : 0
                ]);

                return [
                    'exists' => $exists,
                    'data' => $exists ? $resultado['data'][0] : null
                ];
            });
        } catch (\Exception $e) {
            Log::error('💥 Error validando paquete', [
                'error' => $e->getMessage(),
                'paquete_id' => $paqueteId
            ]);

            return [
                'exists' => false,
                'data' => null
            ];
        }
    }

    /**
     * Obtener productos por estado (activos = '02')
     */
    public function obtenerProductosActivos(): array
    {
        return $this->obtenerListaProductos(['zEstado' => '02']);
    }

    /**
     * Buscar productos por descripción
     */
    public function buscarProductosPorDescripcion(string $descripcion): array
    {
        try {
            // OData no soporta LIKE directamente, pero podemos usar contains
            $url = $this->baseUrl . "?$filter=substringof('{$descripcion}',Description) and zEstado eq '02'";

            Log::info('🔍 Buscando productos por descripción', [
                'descripcion' => $descripcion,
                'url' => $url
            ]);

            $response = $this->makeRequest($url);

            if ($response['success']) {
                return [
                    'success' => true,
                    'data' => $response['data']->d->results ?? [],
                    'total' => count($response['data']->d->results ?? [])
                ];
            }

            return $response;
        } catch (\Exception $e) {
            Log::error('💥 Error buscando productos por descripción', [
                'error' => $e->getMessage(),
                'descripcion' => $descripcion
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Health check del servicio de productos
     */
    public function healthCheck(): array
    {
        try {
            Log::info('🏥 Verificando salud del servicio de productos');

            // Hacer una consulta simple para verificar conectividad
            $url = $this->baseUrl . '?$top=1';
            $response = $this->makeRequest($url);

            if ($response['success']) {
                Log::info('✅ Servicio de productos saludable');
                return [
                    'success' => true,
                    'message' => 'Servicio de productos operativo',
                    'response_time' => $response['response_time'] ?? null
                ];
            }

            Log::warning('⚠️ Servicio de productos con problemas', [
                'error' => $response['error']
            ]);

            return [
                'success' => false,
                'error' => $response['error'],
                'message' => 'Servicio de productos no disponible'
            ];
        } catch (\Exception $e) {
            Log::error('💥 Error en health check de productos', [
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'message' => 'Error interno en servicio de productos'
            ];
        }
    }

    /**
     * Realizar petición HTTP a la API
     */
    protected function makeRequest(string $url): array
    {
        $startTime = microtime(true);

        Log::debug('🔧 ProductService makeRequest', [
            'url' => $url,
            'username' => $this->username,
            'password_length' => strlen($this->password),
            'password_preview' => substr($this->password, 0, 5) . '...'
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
            CURLOPT_SSL_VERIFYHOST => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        $responseTime = round((microtime(true) - $startTime) * 1000, 2);

        if ($error) {
            Log::error('💥 Error cURL en ProductService', [
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
            Log::error('💥 Error HTTP en ProductService', [
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
            Log::error('💥 Error JSON en ProductService', [
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

        Log::info('✅ Petición exitosa a ProductService', [
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
