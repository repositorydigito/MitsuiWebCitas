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
    use HasPageShield, WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Mis Vehículos';

    protected static ?string $navigationGroup = '🚗 Vehículos';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Cita de servicio';

    protected static string $view = 'filament.pages.vehiculos-con-pestanas';

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

    protected $listeners = ['updatedSearch' => 'filtrarVehiculos'];

    protected $mapaMarcas = [
        'Z01' => 'TOYOTA',
        'Z02' => 'LEXUS',
        'Z03' => 'HINO',
    ];

    public function mount(): void
    {
        $this->cargarVehiculos();
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
            // Verificar si el webservice está habilitado en la configuración
            $webserviceEnabled = config('vehiculos_webservice.enabled', true);

            if ($webserviceEnabled) {
                // Activar estado de carga para webservice
                $this->isLoading = true;
                $this->loadingMessage = 'Consultando vehículos en SAP (15s timeout)...';
                $this->dataSource = 'webservice';

                Log::info('[VehiculosPage] Webservice habilitado, cargando vehículos con timeout de 15 segundos');

                // Obtener el documento del usuario autenticado
                $user = \Illuminate\Support\Facades\Auth::user();
                $documentoCliente = $user?->document_number ?? '20605414410';
                Log::info("[VehiculosPage] Usando documento del usuario autenticado: {$documentoCliente}");

                $service = app(VehiculoSoapService::class);

                // Implementar timeout de 15 segundos usando set_time_limit
                $timeoutStart = time();
                $maxExecutionTime = 15;

                try {
                    // Establecer timeout específico para esta operación
                    set_time_limit($maxExecutionTime + 5); // +5 segundos de margen

                    $this->todosLosVehiculos = $service->getVehiculosCliente($documentoCliente, $codigosMarca);

                    $executionTime = time() - $timeoutStart;
                    Log::info("[VehiculosPage] Flujo completado en {$executionTime} segundos");

                    // Verificar la fuente real de los datos basándose en el campo fuente_datos
                    if ($this->todosLosVehiculos->isNotEmpty()) {
                        $primerVehiculo = $this->todosLosVehiculos->first();
                        $fuenteDatos = $primerVehiculo['fuente_datos'] ?? 'unknown';

                        switch ($fuenteDatos) {
                            case 'SAP_Z3PF':
                                $this->dataSource = 'webservice';
                                $this->loadingMessage = "Datos obtenidos desde SAP en {$executionTime}s";
                                break;
                            case 'C4C_WSCitas':
                                $this->dataSource = 'c4c';
                                $this->loadingMessage = "Datos obtenidos desde C4C en {$executionTime}s";
                                break;
                            case 'BaseDatos_Local':
                                $this->dataSource = 'database';
                                $this->loadingMessage = "Datos obtenidos desde BD local en {$executionTime}s";
                                break;
                            default:
                                $this->dataSource = 'mock';
                                $this->loadingMessage = "Datos simulados (servicios no disponibles) - {$executionTime}s";
                                break;
                        }
                    } else {
                        $this->dataSource = 'empty';
                        $this->loadingMessage = 'No se encontraron vehículos en ningún sistema';
                    }

                } catch (\Exception $e) {
                    $executionTime = time() - $timeoutStart;
                    if ($executionTime >= $maxExecutionTime) {
                        Log::warning("[VehiculosPage] Timeout alcanzado ({$executionTime}s), usando fallback");
                        $this->loadingMessage = 'Timeout en servicios, cargando desde BD local...';
                        throw new \Exception("Timeout de {$maxExecutionTime}s alcanzado");
                    } else {
                        throw $e;
                    }
                }

            } else {
                // Activar estado de carga para base de datos
                $this->isLoading = true;
                $this->loadingMessage = 'Cargando vehículos desde la base de datos...';
                $this->dataSource = 'database';

                Log::info('[VehiculosPage] Webservice deshabilitado, cargando vehículos desde la base de datos');

                $vehiculosDB = Vehicle::where('status', 'active')->get();

                if ($vehiculosDB->count() > 0) {
                    Log::info("[VehiculosPage] Encontrados {$vehiculosDB->count()} vehículos en la base de datos");

                    // Convertir los modelos a un formato compatible con el que usa la vista
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
                    Log::warning('[VehiculosPage] No hay vehículos en la base de datos y el webservice está deshabilitado');
                    $this->todosLosVehiculos = collect();
                    $this->loadingMessage = 'No se encontraron vehículos en la base de datos';
                }
            }

            $this->agruparYPaginarVehiculos();

            // Finalizar estado de carga
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

        if (empty($this->vehiculosAgrupados) && ! empty($this->marcasInfo)) {
            $this->activeTab = array_key_first($this->marcasInfo);
        } elseif (! isset($this->vehiculosAgrupados[$this->activeTab]) && ! empty($this->vehiculosAgrupados)) {
            $this->activeTab = array_key_first($this->vehiculosAgrupados);
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
}
