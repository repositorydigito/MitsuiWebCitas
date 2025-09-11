<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\C4C\ProductService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class DownloadProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $packageId;
    protected ?int $appointmentId;
    protected int $cacheHours;

    /**
     * Create a new job instance.
     */
    public function __construct(string $packageId, ?int $appointmentId = null, int $cacheHours = 24)
    {
        $this->packageId = $packageId;
        $this->appointmentId = $appointmentId;
        $this->cacheHours = $cacheHours;
        
        // Configurar cola específica para productos
        $this->onQueue('products');
    }

    /**
     * Execute the job.
     */
    public function handle(ProductService $productService): void
    {
        $startTime = now();
        
        Log::info('🚀 Iniciando descarga de productos', [
            'package_id' => $this->packageId,
            'appointment_id' => $this->appointmentId,
            'cache_hours' => $this->cacheHours
        ]);

        try {
            // 1. VERIFICAR SI YA EXISTEN productos maestros en cache válido
            if (Product::existsMasterProductsForPackage($this->packageId, $this->cacheHours)) {
                Log::info('✅ Productos ya existen en cache', [
                    'package_id' => $this->packageId,
                    'appointment_id' => $this->appointmentId,
                    'accion' => 'SKIP_DOWNLOAD'
                ]);

                // Si hay appointment_id, vincular productos existentes
                if ($this->appointmentId) {
                    $this->linkExistingProductsToAppointment();
                    // TAMBIÉN disparar CreateOfferJob aunque venga de cache
                    $this->dispatchCreateOfferJob();
                }
                
                $this->updateJobStatus('completed_from_cache');
                return;
            }

            // 2. DESCARGAR productos de C4C si no existen
            Log::info('📥 Descargando productos de C4C', [
                'package_id' => $this->packageId,
                'endpoint' => 'BOListaProductosProductosVinculadosCollection'
            ]);

            $resultado = $this->downloadProductsFromC4C($productService);

            // ===== INICIO: FORMATO ALTERNATIVO PACKAGE_ID (ROLLBACK: Comentar desde aquí hasta FINAL) =====
            // 🔥 NUEVA LÓGICA: Intentar formato alternativo si es necesario
            if (!$resultado['success'] || count($resultado['productos'] ?? []) === 0) {
                // Intentar formato alternativo solo si cumple TODAS las condiciones
                if ($this->shouldTryAlternativeFormat()) {
                    Log::info('🔄 Intentando formato alternativo para package_id', [
                        'original_package_id' => $this->packageId,
                        'appointment_id' => $this->appointmentId,
                        'original_error' => $resultado['error'] ?? 'No products found'
                    ]);
                    
                    try {
                        $alternativeResult = $this->tryAlternativePackageFormat($productService);
                        
                        if ($alternativeResult['success'] && count($alternativeResult['productos'] ?? []) > 0) {
                            Log::info('✅ Formato alternativo exitoso', [
                                'alternative_package_id' => str_replace('-', '=', $this->packageId),
                                'products_found' => count($alternativeResult['productos'])
                            ]);
                            
                            // Actualizar package_id en la cita
                            $this->updateAppointmentPackageId(str_replace('-', '=', $this->packageId));
                            
                            // Usar el resultado alternativo
                            $resultado = $alternativeResult;
                        } else {
                            Log::info('❌ Formato alternativo también falló', [
                                'alternative_package_id' => str_replace('-', '=', $this->packageId)
                            ]);
                            throw new \Exception($resultado['error'] ?? 'No products found with either format');
                        }
                    } catch (\Exception $e) {
                        Log::error('💥 Error en formato alternativo', [
                            'error' => $e->getMessage(),
                            'original_package_id' => $this->packageId
                        ]);
                        throw new \Exception($resultado['error'] ?? 'Product download failed');
                    }
                } else {
                    // No cumple condiciones, comportamiento original
                    throw new \Exception($resultado['error'] ?? 'Product download failed');
                }
            }
            // ===== FINAL: FORMATO ALTERNATIVO PACKAGE_ID (ROLLBACK: Comentar hasta aquí) =====

            // 3. PROCESAR y guardar productos maestros
            $productosCreados = $this->saveProductsToDatabase($resultado['productos']);

            // 4. VINCULAR productos a cita específica si se proporciona appointment_id
            if ($this->appointmentId && isset($resultado['productos']) && count($resultado['productos']) > 0) {
                $this->linkExistingProductsToAppointment();
            }

            $executionTime = now()->diffInSeconds($startTime);
            
            Log::info('✅ Descarga de productos completada exitosamente', [
                'package_id' => $this->packageId,
                'appointment_id' => $this->appointmentId,
                'productos_creados' => $productosCreados,
                'productos_disponibles' => count($resultado['productos']),
                'tiempo_ejecucion' => $executionTime . 's'
            ]);

            // 5. DISPARAR CreateOfferJob automáticamente si es para una cita específica
            if ($this->appointmentId && isset($resultado['productos']) && count($resultado['productos']) > 0) {
                $this->dispatchCreateOfferJob();
            }

            $this->updateJobStatus('completed', [
                'productos_creados' => $productosCreados,
                'tiempo_ejecucion' => $executionTime
            ]);

        } catch (\Exception $e) {
            $this->handleJobFailure($e, $startTime);
        }
    }

    /**
     * Descargar productos de C4C usando ProductService
     */
    protected function downloadProductsFromC4C(ProductService $productService): array
    {
        // Filtros para obtener productos vinculados activos
        $filtros = [
            'zIDPadre' => $this->packageId,
            'zEstado' => '02' // Solo productos activos
        ];

        // Llamar al ProductService para obtener productos vinculados
        $resultado = $productService->obtenerProductosVinculados($filtros);

        if (!$resultado['success']) {
            Log::error('💥 Error descargando productos de C4C', [
                'package_id' => $this->packageId,
                'error' => $resultado['error'],
                'filtros' => $filtros
            ]);

            return [
                'success' => false,
                'error' => $resultado['error'],
                'productos' => []
            ];
        }

        $productos = $resultado['data'] ?? [];
        
        Log::info('📦 Productos obtenidos de C4C', [
            'package_id' => $this->packageId,
            'total_productos' => count($productos),
            'tipos_posicion' => array_count_values(array_map(function($p) {
                return $p->zTipoPosicion ?? 'N/A';
            }, $productos))
        ]);

        return [
            'success' => true,
            'productos' => $productos,
            'total' => count($productos)
        ];
    }

    /**
     * Guardar productos en base de datos como productos maestros
     */
    protected function saveProductsToDatabase(array $productos): int
    {
        $productosCreados = 0;

        foreach ($productos as $productoC4C) {
            try {
                // Mapear datos de C4C al formato del modelo
                $datosProducto = Product::mapFromC4CData($productoC4C, $this->packageId);

                // Verificar si el producto ya existe (por si acaso)
                $existe = Product::where('package_id', $this->packageId)
                    ->where('c4c_product_id', $datosProducto['c4c_product_id'])
                    ->whereNull('appointment_id')
                    ->exists();

                if (!$existe) {
                    Product::create($datosProducto);
                    $productosCreados++;

                    Log::debug('✅ Producto maestro creado', [
                        'package_id' => $this->packageId,
                        'c4c_product_id' => $datosProducto['c4c_product_id'],
                        'description' => $datosProducto['description'],
                        'position_type' => $datosProducto['position_type']
                    ]);
                } else {
                    Log::debug('⚠️ Producto maestro ya existe', [
                        'package_id' => $this->packageId,
                        'c4c_product_id' => $datosProducto['c4c_product_id']
                    ]);
                }

            } catch (\Exception $e) {
                Log::error('💥 Error creando producto maestro', [
                    'package_id' => $this->packageId,
                    'c4c_product_id' => $datosProducto['c4c_product_id'] ?? 'N/A',
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('📊 Resumen de productos maestros creados', [
            'package_id' => $this->packageId,
            'productos_procesados' => count($productos),
            'productos_creados' => $productosCreados
        ]);

        return $productosCreados;
    }

    /**
     * Vincular productos maestros existentes a una cita específica
     */
    protected function linkExistingProductsToAppointment(): void
    {
        if (!$this->appointmentId) {
            return;
        }

        try {
            // Verificar si ya están vinculados
            $yaVinculados = Product::forAppointment($this->appointmentId)
                ->forPackage($this->packageId)
                ->exists();

            if ($yaVinculados) {
                Log::info('⚠️ Productos ya vinculados a la cita', [
                    'appointment_id' => $this->appointmentId,
                    'package_id' => $this->packageId
                ]);
                return;
            }

            // Crear productos específicos para la cita
            $productosCreados = Product::createProductsForAppointment($this->appointmentId, $this->packageId);

            Log::info('🔗 Productos vinculados a cita', [
                'appointment_id' => $this->appointmentId,
                'package_id' => $this->packageId,
                'productos_vinculados' => $productosCreados
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Error vinculando productos a cita', [
                'appointment_id' => $this->appointmentId,
                'package_id' => $this->packageId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar estado del job en cache
     */
    protected function updateJobStatus(string $status, array $data = []): void
    {
        $cacheKey = "download_products_job_{$this->packageId}";
        
        $jobData = [
            'status' => $status,
            'package_id' => $this->packageId,
            'appointment_id' => $this->appointmentId,
            'updated_at' => now()->toISOString(),
            'data' => $data
        ];

        Cache::put($cacheKey, $jobData, 300); // 5 minutos de cache
    }

    /**
     * Manejar fallos del job
     */
    protected function handleJobFailure(\Exception $e, $startTime): void
    {
        $executionTime = now()->diffInSeconds($startTime);

        Log::error('💥 Error en descarga de productos', [
            'package_id' => $this->packageId,
            'appointment_id' => $this->appointmentId,
            'error' => $e->getMessage(),
            'tiempo_ejecucion' => $executionTime . 's',
            'trace' => $e->getTraceAsString()
        ]);

        $this->updateJobStatus('failed', [
            'error' => $e->getMessage(),
            'tiempo_ejecucion' => $executionTime
        ]);

        // Re-lanzar la excepción para que Laravel maneje el retry
        throw $e;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('💥 Job de descarga de productos falló definitivamente', [
            'package_id' => $this->packageId,
            'appointment_id' => $this->appointmentId,
            'error' => $exception->getMessage(),
            'intentos_realizados' => $this->attempts()
        ]);

        $this->updateJobStatus('failed_permanently', [
            'error' => $exception->getMessage(),
            'intentos_realizados' => $this->attempts()
        ]);
    }

    /**
     * Disparar CreateOfferJob después de descargar productos exitosamente
     */
    protected function dispatchCreateOfferJob(): void
    {
        try {
            $appointment = \App\Models\Appointment::find($this->appointmentId);
            
            if (!$appointment) {
                Log::warning('⚠️ No se puede disparar CreateOfferJob: appointment no encontrada', [
                    'appointment_id' => $this->appointmentId,
                    'package_id' => $this->packageId
                ]);
                return;
            }

            Log::info('🚀 Disparando CreateOfferJob después de descargar productos', [
                'appointment_id' => $this->appointmentId,
                'package_id' => $this->packageId,
                'trigger' => 'productos_descargados'
            ]);

            // Disparar CreateOfferJob en la cola 'offers'
            \App\Jobs\CreateOfferJob::dispatch($appointment)->onQueue('offers');
            
            Log::info('✅ CreateOfferJob encolado exitosamente', [
                'appointment_id' => $this->appointmentId,
                'package_id' => $this->packageId,
                'queue' => 'offers'
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Error disparando CreateOfferJob', [
                'appointment_id' => $this->appointmentId,
                'package_id' => $this->packageId,
                'error' => $e->getMessage()
            ]);
        }
    }

    // ===== INICIO: MÉTODOS AUXILIARES FORMATO ALTERNATIVO (ROLLBACK: Comentar estos métodos) =====
    /**
     * 🔒 Verificar si debe intentar formato alternativo
     * SOLO para PRIORIDAD 2 + CLIENTE NORMAL + CON APPOINTMENT_ID
     */
    protected function shouldTryAlternativeFormat(): bool
    {
        try {
            // Verificación 1: Debe tener appointment_id
            if (!$this->appointmentId) {
                return false;
            }

            // Verificación 2: Debe ser PRIORIDAD 2 (servicios adicionales)
            if (!$this->isPriority2Package()) {
                return false;
            }

            // Verificación 3: Debe ser cliente normal (no wildcard)
            if (!$this->isNormalClient()) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error en shouldTryAlternativeFormat', [
                'error' => $e->getMessage(),
                'package_id' => $this->packageId,
                'appointment_id' => $this->appointmentId
            ]);
            return false; // En caso de error, no intentar formato alternativo
        }
    }

    /**
     * 🔍 Detectar si es PRIORIDAD 2 (servicios adicionales)
     * Formato: M2275-PQLEX, M2275-PQWYNNS8, etc.
     */
    protected function isPriority2Package(): bool
    {
        // Regex más estricto: M + dígitos + guión + al menos una letra mayúscula
        return preg_match('/^M\d+-[A-Z]+[A-Z0-9]*$/i', $this->packageId) === 1;
    }

    /**
     * 👤 Verificar si es cliente normal (no wildcard)
     */
    protected function isNormalClient(): bool
    {
        try {
            if (!$this->appointmentId) {
                return false;
            }

            $appointment = \App\Models\Appointment::find($this->appointmentId);
            if (!$appointment || !$appointment->customer_ruc) {
                return false;
            }

            $user = \App\Models\User::where('document_number', $appointment->customer_ruc)
                                    ->whereNotNull('c4c_internal_id')
                                    ->first();

            if (!$user) {
                return false; // Sin usuario asociado, no procesar
            }

            // FALSE si es wildcard, TRUE si es normal
            return $user->c4c_internal_id !== '1200166011';
            
        } catch (\Exception $e) {
            Log::error('Error verificando cliente normal', [
                'error' => $e->getMessage(),
                'appointment_id' => $this->appointmentId
            ]);
            return false;
        }
    }

    /**
     * 🔄 Intentar descargar con formato alternativo M2275=PQLEX
     */
    protected function tryAlternativePackageFormat(ProductService $productService): array
    {
        $alternativePackageId = str_replace('-', '=', $this->packageId);
        
        Log::info('🔄 Intentando descarga con formato alternativo', [
            'original' => $this->packageId,
            'alternative' => $alternativePackageId
        ]);

        // Filtros para la API con formato alternativo
        $filtros = [
            'zIDPadre' => $alternativePackageId,
            'zEstado' => '02' // Solo productos activos
        ];

        // Llamar al ProductService
        $resultado = $productService->obtenerProductosVinculados($filtros);

        if (!$resultado['success']) {
            Log::warning('⚠️ Formato alternativo falló', [
                'alternative_package_id' => $alternativePackageId,
                'error' => $resultado['error']
            ]);
            
            return [
                'success' => false,
                'error' => $resultado['error'],
                'productos' => []
            ];
        }

        $productos = $resultado['data'] ?? [];
        
        Log::info('📦 Productos obtenidos con formato alternativo', [
            'alternative_package_id' => $alternativePackageId,
            'total_productos' => count($productos)
        ]);

        return [
            'success' => true,
            'productos' => $productos,
            'total' => count($productos)
        ];
    }

    /**
     * 💾 Actualizar package_id en la cita con el formato que funcionó
     */
    protected function updateAppointmentPackageId(string $newPackageId): void
    {
        try {
            if (!$this->appointmentId) {
                return;
            }

            $appointment = \App\Models\Appointment::find($this->appointmentId);
            if (!$appointment) {
                Log::warning('⚠️ No se pudo actualizar package_id: appointment no encontrada', [
                    'appointment_id' => $this->appointmentId
                ]);
                return;
            }

            $oldPackageId = $appointment->package_id;
            $appointment->package_id = $newPackageId;
            $appointment->save();

            Log::info('✅ Package ID actualizado en la cita', [
                'appointment_id' => $this->appointmentId,
                'old_package_id' => $oldPackageId,
                'new_package_id' => $newPackageId
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Error actualizando package_id en la cita', [
                'appointment_id' => $this->appointmentId,
                'new_package_id' => $newPackageId,
                'error' => $e->getMessage()
            ]);
            // No relanzar excepción, continuar con el procesamiento
        }
    }
    // ===== FINAL: MÉTODOS AUXILIARES FORMATO ALTERNATIVO (ROLLBACK: Comentar hasta aquí) =====
}