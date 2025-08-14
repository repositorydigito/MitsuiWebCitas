<?php

namespace App\Filament\Pages;

use App\Models\Vehicle;
use App\Services\VehiculoSoapService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\WithPagination;

class Vehiculos extends Page
{
    public $puntosClubMitsui = null; // Puntos Club Mitsui para pasar a la vista

    use HasPageShield, WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Mis Vehículos';

    protected static ?string $navigationGroup = '🚗 Vehículos';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = '';

    protected static string $view = 'filament.pages.vehiculos-con-pestanas';

    public function getViewData(): array
    {
        return array_merge(parent::getViewData(), [
            'puntosClubMitsui' => $this->puntosClubMitsui,
        ]);
    }

    public Collection $todosLosVehiculos;

    protected array $vehiculosAgrupados = [];

    public array $marcasInfo = [];

    public array $marcaCounts = [];

    public string $activeTab = 'Z01';

    public string $search = '';

    // Estados de carga
    public bool $isLoading = false;

    public string $loadingMessage = '';

    public string $dataSource = ''; // 'webservice', 'database', 'mock'

    protected $queryString = ['activeTab', 'search'];

    protected $listeners = [
        'updatedSearch' => 'filtrarVehiculos',
        'actualizarEstadoVehiculos' => 'actualizarEstadoVehiculos',
        'refrescarEstadoCitas' => 'refrescarEstadoCitas'
    ];

    protected $mapaMarcas = [
        'Z01' => 'TOYOTA',
        'Z02' => 'LEXUS',
        'Z03' => 'HINO',
    ];

    public function mount(): void
    {
        Log::info("[VehiculosPage] === INICIANDO MOUNT ===");
        
        // Forzar carga inmediata de vehículos al entrar a la página
        $this->isLoading = true;
        $this->loadingMessage = 'Cargando vehículos...';
        
        try {
            $this->cargarVehiculos();
            $this->cargarPuntosClubMitsui();
            
            // Verificar si se viene de agendar cita para actualizar estado
            $this->verificarActualizacionDesdeAgendarCita();
            
            Log::info("[VehiculosPage] Mount completado exitosamente. Total vehículos: " . $this->todosLosVehiculos->count());
        } catch (\Exception $e) {
            Log::error("[VehiculosPage] Error en mount: " . $e->getMessage());
            $this->loadingMessage = 'Error al cargar vehículos';
        } finally {
            // Asegurar que los vehículos se muestren inmediatamente
            $this->isLoading = false;
        }
        
        Log::info("[VehiculosPage] === MOUNT FINALIZADO ===");
    }

    /**
     * Verificar si se viene de agendar cita o agregar vehículo y actualizar estado si es necesario
     */
    protected function verificarActualizacionDesdeAgendarCita(): void
    {
        try {
            $necesitaActualizacion = false;
            
            // Verificar si hay una flag en la sesión que indique que se agendó una cita
            if (session()->has('cita_agendada_recientemente')) {
                Log::info("[VehiculosPage] Detectada cita agendada recientemente, actualizando estado...");
                
                // Limpiar la flag de la sesión
                session()->forget('cita_agendada_recientemente');
                $necesitaActualizacion = true;
            }
            
            // Verificar si hay una flag en la sesión que indique que se agregó un vehículo
            if (session()->has('vehiculo_agregado_recientemente')) {
                Log::info("[VehiculosPage] Detectado vehículo agregado recientemente, recargando vehículos...");
                
                // Limpiar la flag de la sesión
                session()->forget('vehiculo_agregado_recientemente');
                
                // Para vehículos nuevos, necesitamos recargar completamente
                $this->cargarVehiculos();
                return; // Ya se recargó todo, no necesitamos hacer más
            }
            
            // Si solo se agendó una cita, solo actualizar el estado de citas
            if ($necesitaActualizacion) {
                $this->enriquecerVehiculosConEstadoCitas();
                $this->agruparYPaginarVehiculos();
                Log::info("[VehiculosPage] Estado actualizado después de agendar cita");
            }
        } catch (\Exception $e) {
            Log::error("[VehiculosPage] Error al verificar actualización desde otras páginas: " . $e->getMessage());
        }
    }

    /**
     * Método para actualizar el estado de los vehículos (llamado desde otras páginas)
     */
    public function actualizarEstadoVehiculos(): void
    {
        Log::info("[VehiculosPage] Actualizando estado de vehículos...");
        
        // Solo actualizar el enriquecimiento con citas, no recargar todo
        $this->enriquecerVehiculosConEstadoCitas();
        $this->agruparYPaginarVehiculos();
        
        Log::info("[VehiculosPage] Estado de vehículos actualizado");
    }

    /**
     * Método público para forzar actualización desde JavaScript/Livewire
     */
    public function refrescarEstadoCitas(): void
    {
        Log::info("[VehiculosPage] Refrescando estado de citas por solicitud externa...");
        
        // Limpiar cualquier caché de citas pendientes
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && $user->c4c_internal_id) {
            $cacheKey = "citas_pendientes_{$user->c4c_internal_id}";
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
        }
        
        // Actualizar estado
        $this->enriquecerVehiculosConEstadoCitas();
        $this->agruparYPaginarVehiculos();
        
        Log::info("[VehiculosPage] Estado de citas refrescado");
    }



    /**
     * Consultar citas desde la base de datos local como fallback
     */
    protected function consultarCitasLocales($user): array
    {
        try {
            Log::info("[VehiculosPage] Consultando citas locales para usuario: {$user->id}");

            $citasLocales = \App\Models\Appointment::where('customer_ruc', $user->document_number)
                ->whereIn('status', ['pending', 'confirmed'])
                ->where('appointment_date', '>=', now()->startOfDay())
                ->get();

            Log::info("[VehiculosPage] Citas locales encontradas: " . $citasLocales->count());

            $citasFormateadas = [];
            foreach ($citasLocales as $cita) {
                $placa = $cita->vehicle_plate ?? '';
                
                if ($placa) {
                    $citasFormateadas[] = [
                        'vehicle' => [
                            'plate' => $placa
                        ],
                        'status' => [
                            'appointment_code' => $cita->status === 'pending' ? '1' : '2'
                        ],
                        'dates' => [
                            'scheduled_start_date' => $cita->appointment_date->format('Y-m-d')
                        ]
                    ];
                }
            }

            return $citasFormateadas;

        } catch (\Exception $e) {
            Log::error("[VehiculosPage] Error al consultar citas locales: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cargar los puntos Club Mitsui desde SAP y guardarlos en la propiedad
     */
    protected function cargarPuntosClubMitsui(): void
    {
        try {
            // Verificar si los webservices están habilitados
            $webserviceEnabled = config('vehiculos_webservice.enabled', true);
            if (!$webserviceEnabled) {
                Log::info("[VehiculosPage] Webservice SAP deshabilitado, no se cargan puntos Club Mitsui");
                $this->puntosClubMitsui = null;
                return;
            }

            $user = \Illuminate\Support\Facades\Auth::user();
            $documento = $user?->document_number;
            if (!$documento) {
                $this->puntosClubMitsui = null;
                return;
            }
            // Crear cliente SOAP igual que en SapTestCustomer
            // Usar el mismo WSDL local que VehiculoSoapService para evitar problemas de WS-Policy
            $wsdlUrl = storage_path('wsdl/vehiculos.wsdl');
            $usuario = config('services.sap_3p.usuario');
            $password = config('services.sap_3p.password');
            $options = [
                'login' => $usuario,
                'password' => $password,
                'connection_timeout' => 10,
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
            ];
            $soapClient = new \SoapClient($wsdlUrl, $options);
            $parametros = [
                'PI_NUMDOCCLI' => $documento,
            ];
            $respuesta = $soapClient->Z3PF_GETDATOSCLIENTE($parametros);
            $this->puntosClubMitsui = isset($respuesta->PE_PUNCLU) ? $respuesta->PE_PUNCLU : null;
        \Log::info('[VehiculosPage] Puntos Club Mitsui obtenidos', [
            'documento' => $documento,
            'puntos' => $this->puntosClubMitsui,
            'respuesta_raw' => $respuesta,
            'respuesta_campos' => is_object($respuesta) ? array_keys(get_object_vars($respuesta)) : null
        ]);
        } catch (\Exception $e) {
            \Log::error('[VehiculosPage] Error al obtener puntos Club Mitsui: ' . $e->getMessage());
            $this->puntosClubMitsui = null;
        }
    }

    public function updatedSearch(): void
    {
        Log::info("[VehiculosPage] Búsqueda actualizada: '{$this->search}'");
        $this->filtrarVehiculos();
        $this->resetPage();
    }

    public function filtrarVehiculos(): void
    {
        $this->agruparYPaginarVehiculos();
    }

    public function limpiarBusqueda(): void
    {
        $this->search = '';
        $this->filtrarVehiculos();
        $this->resetPage();
    }

    protected function cargarVehiculos(): void
    {
        $codigosMarca = array_keys($this->mapaMarcas);
        $this->marcasInfo = $this->mapaMarcas;

        try {
            $user = \Illuminate\Support\Facades\Auth::user();
            
            Log::info("[VehiculosPage] === INICIANDO CARGA DE VEHÍCULOS ===");
            Log::info("[VehiculosPage] Usuario ID: " . ($user?->id ?? 'null'));
            Log::info("[VehiculosPage] Document number: " . ($user?->document_number ?? 'null'));
            Log::info("[VehiculosPage] Email: " . ($user?->email ?? 'null'));

            // Consultar ambos orígenes SIEMPRE
            $vehiculosSAP = collect();
            $vehiculosDB = collect();
            $webserviceEnabled = config('vehiculos_webservice.enabled', true);

            if ($webserviceEnabled && !empty($user?->document_number)) {
                $vehiculosSAP = $this->consultarVehiculosSAP($user, $codigosMarca);
                
                // Si SAP falló pero tenemos pocos vehículos locales, intentar una vez más
                if ($vehiculosSAP->isEmpty() && $vehiculosDB->count() < 3) {
                    Log::info("[VehiculosPage] SAP falló y pocos vehículos locales, reintentando SAP...");
                    sleep(1); // Pequeña pausa antes del retry
                    $vehiculosSAP = $this->consultarVehiculosSAP($user, $codigosMarca);
                }
            } else {
                if (empty($user?->document_number)) {
                    Log::info("[VehiculosPage] Usuario sin document_number, saltando consulta SAP");
                } else {
                    Log::info("[VehiculosPage] Webservice SAP deshabilitado");
                }
            }

            // Siempre cargar los locales
            $vehiculosDB = Vehicle::where('user_id', $user->id)
                ->where('status', 'active')
                ->get();
                
            Log::info("[VehiculosPage] Vehículos BD encontrados para user_id {$user->id}: " . $vehiculosDB->count());
            
            $vehiculosDB = $vehiculosDB->map(function ($vehicle) {
                    return [
                        'vhclie' => $vehicle->vehicle_id,
                        'numpla' => $vehicle->license_plate,
                        'modver' => $vehicle->model,
                        'aniomod' => $vehicle->year,
                        'marca_codigo' => $vehicle->brand_code,
                        'marca_nombre' => $vehicle->brand_name,
                        'kilometraje' => $vehicle->mileage,
                        'color' => $vehicle->color,
                        'vin' => $vehicle->vin,
                        'motor' => $vehicle->engine_number,
                        'ultimo_servicio_fecha' => $vehicle->last_service_date,
                        'ultimo_servicio_km' => $vehicle->last_service_mileage,
                        'proximo_servicio_fecha' => $vehicle->next_service_date,
                        'proximo_servicio_km' => $vehicle->next_service_mileage,
                        'mantenimiento_prepagado' => $vehicle->has_prepaid_maintenance,
                        'mantenimiento_prepagado_vencimiento' => $vehicle->prepaid_maintenance_expiry,
                        'imagen_url' => $vehicle->image_url,
                        'fuente_datos' => 'BaseDatos_Local',
                    ];
                });

            // Log detallado para diagnóstico
            Log::info("[VehiculosPage] Vehículos obtenidos - SAP: " . $vehiculosSAP->count() . ", BD Local: " . $vehiculosDB->count());
            
            if ($vehiculosSAP->count() > 0) {
                Log::info("[VehiculosPage] Primeros vehículos SAP:", $vehiculosSAP->take(3)->toArray());
            }
            
            if ($vehiculosDB->count() > 0) {
                Log::info("[VehiculosPage] Primeros vehículos BD:", $vehiculosDB->take(3)->toArray());
            }

            // Unir ambos orígenes y eliminar duplicados por placa (numpla)
            $todos = $vehiculosSAP->concat($vehiculosDB)
                ->unique(function ($item) {
                    return $item['numpla'] ?? '';
                })
                ->values();

            Log::info("[VehiculosPage] Total vehículos después de unir y eliminar duplicados: " . $todos->count());

            $this->todosLosVehiculos = collect($todos);
            $this->determinarFuenteDatos($this->todosLosVehiculos);

            // Enriquecer y paginar
            $this->enriquecerVehiculosConEstadoCitas();
            $this->enriquecerVehiculosConImagenModelo();
            $this->agruparYPaginarVehiculos();
            
            // Asegurar que se muestre la pestaña correcta
            if (empty($this->vehiculosAgrupados) && !empty($this->marcasInfo)) {
                $this->activeTab = array_key_first($this->marcasInfo);
            } elseif (!isset($this->vehiculosAgrupados[$this->activeTab]) && !empty($this->vehiculosAgrupados)) {
                $this->activeTab = array_key_first($this->vehiculosAgrupados);
            }
            
            $this->isLoading = false;
        } catch (\Exception $e) {
            Log::error('[VehiculosPage] Error crítico al obtener vehículos: '.$e->getMessage());
            $this->todosLosVehiculos = collect();
            $this->vehiculosAgrupados = [];
            $this->isLoading = false;
            $this->loadingMessage = 'Error al cargar vehículos: '.$e->getMessage();
            $this->dataSource = 'error';

            \Filament\Notifications\Notification::make()
                ->title('Error al Cargar Vehículos')
                ->body('No se pudo obtener la lista de vehículos. Intente más tarde.')
                ->danger()
                ->send();
        }


    }

    /**
     * Consultar vehículos desde SAP
     */
    protected function consultarVehiculosSAP($user, array $codigosMarca): \Illuminate\Support\Collection
    {
        try {
            $this->isLoading = true;
            $this->loadingMessage = 'Sincronizando con SAP (15s timeout)...';

            // Obtener el documento del usuario autenticado
            $documentoCliente = $user?->document_number;
            
            // Si no hay documento válido, no consultar SAP
            if (empty($documentoCliente)) {
                Log::warning("[VehiculosPage] Usuario sin document_number válido, saltando consulta SAP");
                return collect();
            }
            
            Log::info("[VehiculosPage] Consultando SAP con documento: {$documentoCliente}");

            $service = app(VehiculoSoapService::class);

            // Implementar timeout de 15 segundos
            $timeoutStart = time();
            $maxExecutionTime = 15;

            // Establecer timeout específico para esta operación
            set_time_limit($maxExecutionTime + 5); // +5 segundos de margen

            $vehiculosSAP = $service->getVehiculosCliente($documentoCliente, $codigosMarca);

            $executionTime = time() - $timeoutStart;
            Log::info("[VehiculosPage] Consulta SAP completada en {$executionTime} segundos");

            return $vehiculosSAP;

        } catch (\Exception $e) {
            $executionTime = time() - ($timeoutStart ?? time());
            Log::error("[VehiculosPage] Error al consultar SAP: " . $e->getMessage());
            Log::error("[VehiculosPage] Traza del error: " . $e->getTraceAsString());

            if ($executionTime >= $maxExecutionTime) {
                Log::warning("[VehiculosPage] Timeout SAP alcanzado ({$executionTime}s)");
                $this->loadingMessage = 'Timeout en SAP, usando datos locales...';
            } else {
                $this->loadingMessage = 'Error en SAP, usando datos locales...';
            }

            return collect();
        }
    }

    /**
     * Sincronizar vehículos de SAP con la base de datos local
     */
    protected function sincronizarVehiculosConBD($user, \Illuminate\Support\Collection $vehiculosSAP): void
    {
        try {
            Log::info("[VehiculosPage] Iniciando sincronización BD para usuario {$user->id}");

            // Obtener vehículos existentes en BD
            $vehiculosExistentes = Vehicle::where('user_id', $user->id)->get()->keyBy('license_plate');

            $nuevosVehiculos = 0;
            $actualizados = 0;

            foreach ($vehiculosSAP as $vehiculoSAP) {
                $placa = $vehiculoSAP['numpla'] ?? null;

                if (!$placa) {
                    continue;
                }

                if ($vehiculosExistentes->has($placa)) {
                    // Vehículo existe, actualizar datos
                    $vehiculoExistente = $vehiculosExistentes->get($placa);
                    $this->actualizarVehiculoExistente($vehiculoExistente, $vehiculoSAP);
                    $actualizados++;
                } else {
                    // Vehículo nuevo, crear registro
                    $this->crearNuevoVehiculo($user, $vehiculoSAP);
                    $nuevosVehiculos++;
                }
            }

            Log::info("[VehiculosPage] Sincronización completada: {$nuevosVehiculos} nuevos, {$actualizados} actualizados");

            if ($nuevosVehiculos > 0) {
                $this->loadingMessage = "Sincronizado: {$nuevosVehiculos} vehículos nuevos agregados desde SAP";
            } else {
                $this->loadingMessage = "Sincronizado: datos actualizados desde SAP";
            }

        } catch (\Exception $e) {
            Log::error("[VehiculosPage] Error en sincronización BD: " . $e->getMessage());
        }
    }

    /**
     * Actualizar vehículo existente con datos de SAP
     */
    protected function actualizarVehiculoExistente(Vehicle $vehiculo, array $vehiculoSAP): void
    {
        try {
            $vehiculo->update([
                'model' => $vehiculoSAP['modver'] ?? $vehiculo->model,
                'year' => $vehiculoSAP['aniomod'] ?? $vehiculo->year,
                'brand_code' => $vehiculoSAP['marca_codigo'] ?? $vehiculo->brand_code,
                'brand_name' => $this->obtenerNombreMarca($vehiculoSAP['marca_codigo'] ?? ''),
                'updated_at' => now(),
            ]);

            Log::debug("[VehiculosPage] Vehículo actualizado: {$vehiculo->license_plate}");

        } catch (\Exception $e) {
            Log::error("[VehiculosPage] Error al actualizar vehículo {$vehiculo->license_plate}: " . $e->getMessage());
        }
    }

    /**
     * Crear nuevo vehículo desde datos de SAP
     */
    protected function crearNuevoVehiculo($user, array $vehiculoSAP): void
    {
        try {
            Vehicle::create([
                'user_id' => $user->id,
                'vehicle_id' => $vehiculoSAP['vhclie'] ?? 'VH' . uniqid(),
                'license_plate' => $vehiculoSAP['numpla'],
                'model' => $vehiculoSAP['modver'] ?? 'Modelo no especificado',
                'year' => $vehiculoSAP['aniomod'] ?? date('Y'),
                'brand_code' => $vehiculoSAP['marca_codigo'] ?? 'Z01',
                'brand_name' => $this->obtenerNombreMarca($vehiculoSAP['marca_codigo'] ?? 'Z01'),
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            Log::info("[VehiculosPage] Nuevo vehículo creado: {$vehiculoSAP['numpla']}");

        } catch (\Exception $e) {
            Log::error("[VehiculosPage] Error al crear vehículo {$vehiculoSAP['numpla']}: " . $e->getMessage());
        }
    }

    /**
     * Obtener nombre de marca desde código
     */
    protected function obtenerNombreMarca(string $codigoMarca): string
    {
        $marcas = [
            'Z01' => 'TOYOTA',
            'Z02' => 'LEXUS',
            'Z03' => 'HINO',
        ];

        return $marcas[$codigoMarca] ?? 'TOYOTA';
    }

    /**
     * Cargar vehículos desde base de datos local
     */
    protected function cargarVehiculosDesdeBaseDatos($user): void
    {
        $this->isLoading = true;
        $this->loadingMessage = 'Cargando vehículos desde la base de datos...';
        $this->dataSource = 'database';

        $vehiculosDB = Vehicle::where('user_id', $user->id)
                            ->where('status', 'active')
                            ->get();

        if ($vehiculosDB->count() > 0) {
            Log::info("[VehiculosPage] Cargados {$vehiculosDB->count()} vehículos desde BD local");

            $this->todosLosVehiculos = $vehiculosDB->map(function ($vehicle) {
                return [
                    'vhclie' => $vehicle->vehicle_id,
                    'numpla' => $vehicle->license_plate,
                    'modver' => $vehicle->model,
                    'aniomod' => $vehicle->year,
                    'marca_codigo' => $vehicle->brand_code,
                    'marca_nombre' => $vehicle->brand_name,
                    'kilometraje' => $vehicle->mileage,
                    'color' => $vehicle->color,
                    'vin' => $vehicle->vin,
                    'motor' => $vehicle->engine_number,
                    'ultimo_servicio_fecha' => $vehicle->last_service_date,
                    'ultimo_servicio_km' => $vehicle->last_service_mileage,
                    'proximo_servicio_fecha' => $vehicle->next_service_date,
                    'proximo_servicio_km' => $vehicle->next_service_mileage,
                    'mantenimiento_prepagado' => $vehicle->has_prepaid_maintenance,
                    'mantenimiento_prepagado_vencimiento' => $vehicle->prepaid_maintenance_expiry,
                    'imagen_url' => $vehicle->image_url,
                ];
            });

            $this->loadingMessage = "Cargados {$vehiculosDB->count()} vehículos desde la base de datos";
        } else {
            Log::warning('[VehiculosPage] No hay vehículos en la base de datos');
            $this->todosLosVehiculos = collect();
            $this->loadingMessage = 'No se encontraron vehículos en la base de datos';
        }
    }

    /**
     * Determinar la fuente de datos basándose en los vehículos obtenidos
     */
    protected function determinarFuenteDatos(\Illuminate\Support\Collection $vehiculos): void
    {
        if ($vehiculos->isNotEmpty()) {
            $primerVehiculo = $vehiculos->first();
            $fuenteDatos = $primerVehiculo['fuente_datos'] ?? 'unknown';

            switch ($fuenteDatos) {
                case 'SAP_Z3PF':
                    $this->dataSource = 'webservice';
                    break;
                case 'C4C_WSCitas':
                    $this->dataSource = 'c4c';
                    break;
                case 'BaseDatos_Local':
                    $this->dataSource = 'database';
                    break;
                default:
                    $this->dataSource = 'mock';
                    break;
            }
        } else {
            $this->dataSource = 'empty';
            $this->loadingMessage = 'No se encontraron vehículos en ningún sistema';
        }
    }

    protected function agruparYPaginarVehiculos(int $perPage = 5): void
    {
        $this->vehiculosAgrupados = [];
        $this->marcaCounts = [];

        if ($this->todosLosVehiculos->isEmpty()) {
            foreach ($this->marcasInfo as $codigo => $nombre) {
                $this->marcaCounts[$codigo] = 0;
            }

            return;
        }

        // Aplicar filtro de búsqueda por placa y modelo si existe
        $vehiculosFiltrados = $this->todosLosVehiculos;
        if (! empty($this->search)) {
            $searchTerm = strtoupper(trim($this->search));
            Log::info("[VehiculosPage] Aplicando filtro de búsqueda: '{$searchTerm}'");

            $vehiculosFiltrados = $this->todosLosVehiculos->filter(function ($vehiculo) use ($searchTerm) {
                $placa = strtoupper($vehiculo['numpla'] ?? '');
                $modelo = strtoupper($vehiculo['modver'] ?? '');

                // Buscar en placa y modelo usando str_contains para coincidencias parciales
                return str_contains($placa, $searchTerm) || str_contains($modelo, $searchTerm);
            });

            Log::info('[VehiculosPage] Vehículos encontrados después del filtro: '.$vehiculosFiltrados->count());
        }

        // Inicializar colecciones vacías para cada marca
        $gruposPorMarca = [];
        foreach ($this->marcasInfo as $codigo => $nombre) {
            $gruposPorMarca[$codigo] = collect();
        }

        // Filtrar vehículos por marca según el campo marca_codigo
        foreach ($vehiculosFiltrados as $vehiculo) {
            $marcaCodigo = $vehiculo['marca_codigo'] ?? null;

            // Solo asignar vehículos a marcas válidas y existentes
            if (isset($marcaCodigo) && isset($this->mapaMarcas[$marcaCodigo])) {
                $gruposPorMarca[$marcaCodigo]->push($vehiculo);
            } else {
                // Si no tiene marca o la marca no es válida, no lo asignamos a ninguna marca
                // Esto evita que se muestren vehículos en marcas a las que no pertenecen
                continue;
            }
        }

        // Contar vehículos por marca
        foreach ($this->marcasInfo as $codigo => $nombre) {
            $this->marcaCounts[$codigo] = $gruposPorMarca[$codigo]->count();
        }

        Log::debug('[VehiculosPage] Conteo de vehículos por marca:', $this->marcaCounts);

        // Crear paginadores solo para marcas que tienen vehículos
        foreach ($gruposPorMarca as $marcaCodigo => $vehiculosDeMarca) {
            if ($vehiculosDeMarca->isEmpty()) {
                // No crear paginador para marcas sin vehículos
                continue;
            }

            $pageName = "page_{$marcaCodigo}";
            $currentPage = Paginator::resolveCurrentPage($pageName);

            $paginator = new LengthAwarePaginator(
                $vehiculosDeMarca->forPage($currentPage, $perPage)->values(),
                $this->marcaCounts[$marcaCodigo],
                $perPage,
                $currentPage,
                [
                    'path' => Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                    'view' => 'pagination::default',
                    'fragment' => null,
                ]
            );

            // Limitar el número de páginas que se muestran
            $paginator->onEachSide(1);

            // Personalizar los textos de la paginación
            $paginator->withQueryString()->appends(['activeTab' => $marcaCodigo]);

            $this->vehiculosAgrupados[$marcaCodigo] = $paginator;
        }

        Log::debug('[VehiculosPage] Paginadores creados para marcas:', array_keys($this->vehiculosAgrupados));
    }

    public function selectTab(string $tab): void
    {
        Log::debug("[VehiculosPage] Cambiando a pestaña: {$tab}");
        $this->activeTab = $tab;
        $this->resetPage("page_{$tab}");
    }

    public function getVehiculosPaginadosProperty(): ?LengthAwarePaginator
    {
        if (empty($this->vehiculosAgrupados) && $this->todosLosVehiculos->isNotEmpty()) {
            $this->agruparYPaginarVehiculos();
        }

        // Si no hay vehículos para la marca seleccionada, devolver null
        if (! isset($this->vehiculosAgrupados[$this->activeTab])) {
            Log::info("[VehiculosPage] No hay vehículos para la marca: {$this->activeTab}");

            return null;
        }

        return $this->vehiculosAgrupados[$this->activeTab];
    }

    public function paginationView()
    {
        return 'vendor.pagination.default';
    }

    public function eliminarVehiculo($vehiculoId): void
    {
        try {
            // Buscar el vehículo en la base de datos
            $vehiculo = Vehicle::where('vehicle_id', $vehiculoId)->first();

            if ($vehiculo) {
                // Eliminar el vehículo (soft delete)
                $vehiculo->delete();

                \Filament\Notifications\Notification::make()
                    ->title('Vehículo retirado')
                    ->body('El vehículo ha sido retirado correctamente.')
                    ->success()
                    ->send();

                // Recargar los vehículos
                $this->cargarVehiculos();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Error')
                    ->body("No se encontró el vehículo con ID: {$vehiculoId}")
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al retirar el vehículo: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Consultar citas pendientes del usuario usando WSCitas
     */
    protected function consultarCitasPendientes($user): array
    {
        try {
            Log::info("[VehiculosPage] === INICIO DEBUG CITAS ===");
            Log::info("[VehiculosPage] Usuario ID: {$user->id}");
            Log::info("[VehiculosPage] Document number: {$user->document_number}");
            Log::info("[VehiculosPage] C4C Internal ID: {$user->c4c_internal_id}");
            Log::info("[VehiculosPage] Is comodin: " . ($user->is_comodin ? 'true' : 'false'));
            Log::info("[VehiculosPage] Has real C4C data: " . ($user->hasRealC4cData() ? 'true' : 'false'));

            // Solo consultar si tiene c4c_internal_id válido
            if (!$user->hasRealC4cData()) {
                Log::warning("[VehiculosPage] Usuario sin datos C4C válidos, saltando consulta WSCitas");
                return [];
            }

            Log::info("[VehiculosPage] Consultando citas pendientes para c4c_internal_id: {$user->c4c_internal_id}");

            // Deshabilitar caché temporalmente para debugging
            // $cacheKey = "citas_pendientes_{$user->c4c_internal_id}";
            // return \Illuminate\Support\Facades\Cache::remember($cacheKey, 300, function () use ($user) {

            $appointmentService = app(\App\Services\C4C\AppointmentQueryService::class);

            $result = $appointmentService->getPendingAppointments($user->c4c_internal_id, [
                'status_codes' => [1, 2], // Generada y Confirmada
                'limit' => 1000
            ]);

            Log::info("[VehiculosPage] Resultado WSCitas completo:", $result);

            if ($result['success'] ?? false) {
                $citas = $result['data'] ?? [];
                Log::info("[VehiculosPage] WSCitas encontradas: " . count($citas));

                // Log resumido de citas encontradas
                Log::info("[VehiculosPage] Citas encontradas: " . count($citas));
                foreach ($citas as $index => $cita) {
                    Log::info("[VehiculosPage] Cita {$index}: Placa={$cita['vehicle']['plate']}, Estado={$cita['status']['appointment_code']}-{$cita['status']['appointment_name']}, Fecha={$cita['dates']['scheduled_start_date']}");
                }

                return $citas;
            }

            Log::error("[VehiculosPage] Error en WSCitas: " . ($result['error'] ?? 'Unknown error'));
            return [];
            // });

        } catch (\Exception $e) {
            Log::error("[VehiculosPage] Error al consultar citas pendientes: " . $e->getMessage());
            Log::error("[VehiculosPage] Stack trace: " . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Indexar citas por placa para búsqueda optimizada
     */
    protected function indexarCitasPorPlaca(array $citas): array
    {
        $indice = [];

        Log::info("[VehiculosPage] === CREANDO ÍNDICE DE CITAS ===");

        foreach ($citas as $index => $cita) {
            $placa = $cita['vehicle']['plate'] ?? null;
            Log::info("[VehiculosPage] Procesando cita {$index} - Placa: " . ($placa ?? 'NULL'));

            if ($placa) {
                if (!isset($indice[$placa])) {
                    $indice[$placa] = [];
                }
                $indice[$placa][] = $cita;
                Log::info("[VehiculosPage] Cita agregada al índice para placa: {$placa}");
            } else {
                Log::warning("[VehiculosPage] Cita sin placa encontrada:", $cita);
            }
        }

        Log::info("[VehiculosPage] Índice de citas creado para " . count($indice) . " placas");
        Log::info("[VehiculosPage] Placas en índice: " . implode(', ', array_keys($indice)));

        return $indice;
    }

    /**
     * Evaluar si un vehículo tiene citas pendientes válidas
     */
    protected function evaluarEstadoCitas(array $citasDelVehiculo): bool
    {
        if (empty($citasDelVehiculo)) {
            return false;
        }

        foreach ($citasDelVehiculo as $cita) {
            $statusCode = $cita['status']['appointment_code'] ?? '';
            $fechaProgramada = $cita['dates']['scheduled_start_date'] ?? '';

            // Verificar si es una cita válida (Generada=1 o Confirmada=2)
            if (in_array($statusCode, ['1', '2'])) {
                // Si no hay fecha o es futura, considerar como pendiente
                if (empty($fechaProgramada) || strtotime($fechaProgramada) >= strtotime('today')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Enriquecer vehículos con estado de citas (UNA CONSULTA POR USUARIO)
     */
    protected function enriquecerVehiculosConEstadoCitas(): void
    {
        try {
            $user = \Illuminate\Support\Facades\Auth::user();

            Log::info("[VehiculosPage] Enriqueciendo " . $this->todosLosVehiculos->count() . " vehículos con estado de citas");

            // Solo proceder si hay vehículos
            if ($this->todosLosVehiculos->isEmpty()) {
                Log::info("[VehiculosPage] No hay vehículos para enriquecer con citas");
                return;
            }

            // UNA SOLA consulta WSCitas para el usuario
            $citasPendientes = $this->consultarCitasPendientes($user);

            // También consultar citas desde la base de datos local como fallback
            $citasLocales = $this->consultarCitasLocales($user);

            // Combinar ambas fuentes de citas
            $todasLasCitas = array_merge($citasPendientes, $citasLocales);

            // Crear índice para búsqueda O(1)
            $indiceCitas = $this->indexarCitasPorPlaca($todasLasCitas);

            // Enriquecer cada vehículo con información de citas
            $vehiculosConCitas = 0;
            $this->todosLosVehiculos = $this->todosLosVehiculos->map(function ($vehiculo) use ($indiceCitas, &$vehiculosConCitas) {
                $placa = $vehiculo['numpla'] ?? '';

                // Búsqueda O(1) en el índice
                $citasDelVehiculo = $indiceCitas[$placa] ?? [];

                // Evaluar si tiene citas pendientes
                $tieneCitaAgendada = $this->evaluarEstadoCitas($citasDelVehiculo);

                if ($tieneCitaAgendada) {
                    $vehiculosConCitas++;
                    Log::info("[VehiculosPage] Vehículo {$placa}: TIENE CITA AGENDADA");
                }

                // Agregar campo sin modificar vehículo existente
                $vehiculo['tiene_cita_agendada'] = $tieneCitaAgendada;
                $vehiculo['citas_pendientes_count'] = count($citasDelVehiculo);

                return $vehiculo;
            });

            Log::info("[VehiculosPage] Enriquecimiento completado: {$vehiculosConCitas} vehículos con citas agendadas");

        } catch (\Exception $e) {
            Log::error("[VehiculosPage] Error al enriquecer vehículos con citas: " . $e->getMessage());
            Log::error("[VehiculosPage] Stack trace: " . $e->getTraceAsString());

            // Fallback seguro: marcar todos como "sin cita"
            $this->todosLosVehiculos = $this->todosLosVehiculos->map(function ($vehiculo) {
                $vehiculo['tiene_cita_agendada'] = false;
                $vehiculo['citas_pendientes_count'] = 0;
                return $vehiculo;
            });
        }
    }

    /**
     * Método para refrescar vehículos manualmente (forzar sincronización con SAP)
     */
    public function refrescarVehiculos(): void
    {
        try {
            Log::info("[VehiculosPage] Refrescando vehículos manualmente");

            \Filament\Notifications\Notification::make()
                ->title('Sincronizando...')
                ->body('Consultando SAP para obtener vehículos actualizados')
                ->info()
                ->send();

            // Recargar vehículos forzando consulta a SAP
            $this->cargarVehiculos();

            \Filament\Notifications\Notification::make()
                ->title('Vehículos actualizados')
                ->body('La lista de vehículos ha sido sincronizada con SAP')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error("[VehiculosPage] Error al refrescar vehículos: " . $e->getMessage());

            \Filament\Notifications\Notification::make()
                ->title('Error al actualizar')
                ->body('No se pudo sincronizar con SAP. Mostrando datos locales.')
                ->warning()
                ->send();
        }
    }

    /**
     * Método para forzar recarga completa de vehículos (útil para debugging)
     */
    public function forzarRecarga(): void
    {
        Log::info('[VehiculosPage] === FORZANDO RECARGA COMPLETA ===');
        
        $this->isLoading = true;
        $this->loadingMessage = 'Forzando recarga completa...';
        
        try {
            // Limpiar datos actuales
            $this->todosLosVehiculos = collect();
            $this->vehiculosAgrupados = [];
            $this->marcaCounts = [];
            
            // Recargar todo
            $this->cargarVehiculos();
            
            Log::info('[VehiculosPage] Recarga forzada completada. Total vehículos: ' . $this->todosLosVehiculos->count());
            
            \Filament\Notifications\Notification::make()
                ->title('Recarga completa exitosa')
                ->body('Se han recargado ' . $this->todosLosVehiculos->count() . ' vehículos')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('[VehiculosPage] Error en recarga forzada: ' . $e->getMessage());
            
            \Filament\Notifications\Notification::make()
                ->title('Error en recarga')
                ->body('Error al forzar recarga: ' . $e->getMessage())
                ->danger()
                ->send();
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Enriquecer los vehículos con la imagen del modelo desde la tabla models (match flexible y seguro)
     */
    protected function enriquecerVehiculosConImagenModelo(): void
    {
        // Obtener todos los modelos activos con nombre e imagen
        $modelos = \App\Models\Modelo::where('is_active', true)->get(['name', 'image']);
        $mapaImagenes = [];
        foreach ($modelos as $modelo) {
            // Normalizar nombre: quitar espacios, mayúsculas, tildes
            $nombreNormalizado = $this->normalizarTexto($modelo->name);
            $mapaImagenes[$nombreNormalizado] = $modelo->image;
        }

        $this->todosLosVehiculos = $this->todosLosVehiculos->map(function ($vehiculo) use ($mapaImagenes) {
            $nombreModelo = $vehiculo['modver'] ?? '';
            $nombreNormalizado = $this->normalizarTexto($nombreModelo);
            $imagenModelo = $mapaImagenes[$nombreNormalizado] ?? null;
            if ($imagenModelo) {
                $vehiculo['modelo_image_url'] = asset('storage/' . $imagenModelo);
            } else {
                $vehiculo['modelo_image_url'] = null;
            }
            return $vehiculo;
        });
    }

    /**
     * Normaliza un texto para comparar modelos (mayúsculas, sin espacios, sin tildes)
     */
    protected function normalizarTexto($texto)
    {
        $texto = mb_strtoupper(trim($texto));
        $texto = str_replace([' ', '-', '_'], '', $texto);
        // Quitar tildes
        $texto = strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'À' => 'A', 'È' => 'E', 'Ì' => 'I', 'Ò' => 'O', 'Ù' => 'U',
            'Ä' => 'A', 'Ë' => 'E', 'Ï' => 'I', 'Ö' => 'O', 'Ü' => 'U',
            'Ñ' => 'N'
        ]);
        return $texto;
    }
}
