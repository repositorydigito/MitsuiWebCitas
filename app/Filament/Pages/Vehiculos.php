<?php

namespace App\Filament\Pages;

use App\Services\VehiculoSoapService;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\WithPagination;

class Vehiculos extends Page
{
    use WithPagination;
    
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    
    protected static ?string $navigationLabel = 'Mis Vehículos';
    
    protected static ?string $title = 'Cita de servicio';

    protected static string $view = 'filament.pages.vehiculos-con-pestanas';
    
    public Collection $todosLosVehiculos;
    protected array $vehiculosAgrupados = [];
    public array $marcasInfo = [];
    public array $marcaCounts = [];
    public string $activeTab = 'Z01';
    
    protected $queryString = ['activeTab'];

    protected $mapaMarcas = [
        'Z01' => 'TOYOTA',
        'Z02' => 'LEXUS',
        'Z03' => 'HINO',
    ];

    public function mount(): void
    {
        $this->cargarVehiculos();
    }

    protected function cargarVehiculos(): void
    {
        $documentoCliente = '20605414410';
        $codigosMarca = array_keys($this->mapaMarcas);
        $this->marcasInfo = $this->mapaMarcas;

        try {
            $service = app(VehiculoSoapService::class);
            //Log::info("[VehiculosPage] Iniciando consulta de vehículos para cliente {$documentoCliente}");
            $this->todosLosVehiculos = $service->getVehiculosCliente($documentoCliente, $codigosMarca);
            
            //Log::info("[VehiculosPage] Total vehículos recibidos: {$this->todosLosVehiculos->count()}");
            
            $this->agruparYPaginarVehiculos();

        } catch (\Exception $e) {
            Log::error("[VehiculosPage] Error crítico al obtener vehículos en mount(): " . $e->getMessage());
            $this->todosLosVehiculos = collect();
            $this->vehiculosAgrupados = [];
            \Filament\Notifications\Notification::make()
                ->title('Error al Cargar Vehículos')
                ->body('No se pudo obtener la lista de vehículos. Intente más tarde.')
                ->danger()
                ->send();
        }
        
        if (empty($this->vehiculosAgrupados) && !empty($this->marcasInfo)) {
            $this->activeTab = array_key_first($this->marcasInfo);
        } elseif (!isset($this->vehiculosAgrupados[$this->activeTab]) && !empty($this->vehiculosAgrupados)) {
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

        $grupos = $this->todosLosVehiculos->groupBy('marca_codigo');
        
        //Log::debug("[VehiculosPage] Vehículos agrupados por marca:", $grupos->map->count()->toArray());

        foreach ($this->marcasInfo as $codigo => $nombre) {
            $this->marcaCounts[$codigo] = $grupos->get($codigo, collect())->count();
        }
        
        foreach ($grupos as $marcaCodigo => $vehiculosDeMarca) {
            $pageName = "page_{$marcaCodigo}"; 
            $currentPage = Paginator::resolveCurrentPage($pageName);
            
            $paginator = new LengthAwarePaginator(
                $vehiculosDeMarca->forPage($currentPage, $perPage)->values(),
                $this->marcaCounts[$marcaCodigo],
                $perPage,
                $currentPage,
                ['path' => Paginator::resolveCurrentPath(), 'pageName' => $pageName] 
            );
            
            $this->vehiculosAgrupados[$marcaCodigo] = $paginator;
        }
        
         Log::debug("[VehiculosPage] Paginadores creados para marcas:", array_keys($this->vehiculosAgrupados));
         Log::debug("[VehiculosPage] Contadores por marca:", $this->marcaCounts);
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
        
        $paginator = $this->vehiculosAgrupados[$this->activeTab] ?? null;
        
        return $paginator;
    }
} 