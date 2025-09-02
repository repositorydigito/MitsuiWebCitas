<?php

namespace App\Filament\Pages;

use App\Models\AdditionalService;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class GestionServiciosAdicionales extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationLabel = 'Otros Servicios';

    protected static ?string $title = 'Otros Servicios';

    protected static string $view = 'filament.pages.gestion-servicios-adicionales';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 4;

    // Propiedades para la tabla
    public Collection $serviciosAdicionales;

    public int $perPage = 10;

    public int $currentPage = 1;

    // Propiedad para búsqueda
    public string $busqueda = '';

    // Propiedad para filtro de marca
    public string $filtroMarca = '';

    // Estado de los servicios
    public array $estadoServicios = [];

    // Modal para agregar/editar servicio
    public bool $isFormModalOpen = false;

    public ?array $servicioEnEdicion = null;

    public string $accionFormulario = 'crear';

    public function mount(): void
    {
        $this->currentPage = request()->query('page', 1);
        $this->cargarServiciosAdicionales();
    }

    public function cargarServiciosAdicionales(): void
    {
        try {
            $servicios = AdditionalService::orderBy('name')->get();

            $this->serviciosAdicionales = $servicios->map(function ($servicio) {
                // Asegurar que brand siempre sea un array
                $brand = $servicio->brand;
                if (!is_array($brand)) {
                    // Si es string, convertir a array
                    $brand = $brand ? [$brand] : ['Toyota'];
                }
                
                return [
                    'id' => $servicio->id,
                    'name' => $servicio->name,
                    'code' => $servicio->code,
                    'brand' => $brand,
                    'description' => $servicio->description,
                    'is_active' => $servicio->is_active,
                ];
            });

            // Inicializar el estado de los servicios
            foreach ($this->serviciosAdicionales as $servicio) {
                $this->estadoServicios[$servicio['id']] = $servicio['is_active'];
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al cargar servicios adicionales')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
            $this->serviciosAdicionales = collect();
        }
    }

    public function getServiciosPaginadosProperty(): LengthAwarePaginator
    {
        $serviciosFiltrados = $this->serviciosAdicionales;
        
        // Filtro por búsqueda de texto
        if (! empty($this->busqueda)) {
            $terminoBusqueda = strtolower($this->busqueda);
            $serviciosFiltrados = $serviciosFiltrados->filter(function ($servicio) use ($terminoBusqueda) {
                return str_contains(strtolower($servicio['name']), $terminoBusqueda) ||
                       str_contains(strtolower($servicio['code']), $terminoBusqueda);
            });
        }

        // Filtro por marca
        if (! empty($this->filtroMarca)) {
            $serviciosFiltrados = $serviciosFiltrados->filter(function ($servicio) {
                // Si brand es un array, verificar si contiene la marca filtrada
                if (is_array($servicio['brand'])) {
                    return in_array($this->filtroMarca, $servicio['brand']);
                }
                // Compatibilidad con datos antiguos que pueden ser string
                return $servicio['brand'] === $this->filtroMarca;
            });
        }

        if ($serviciosFiltrados->count() > 0 && $this->currentPage > ceil($serviciosFiltrados->count() / $this->perPage)) {
            $this->currentPage = 1;
        }

        return new LengthAwarePaginator(
            $serviciosFiltrados->forPage($this->currentPage, $this->perPage),
            $serviciosFiltrados->count(),
            $this->perPage,
            $this->currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    public function toggleEstado(int $id): void
    {
        try {
            $this->estadoServicios[$id] = ! $this->estadoServicios[$id];

            $this->serviciosAdicionales = $this->serviciosAdicionales->map(function ($servicio) use ($id) {
                if ($servicio['id'] === $id) {
                    $servicio['is_active'] = $this->estadoServicios[$id];
                }

                return $servicio;
            });

            $servicio = AdditionalService::findOrFail($id);
            $servicio->is_active = $this->estadoServicios[$id];
            $servicio->save();

            $estado = $this->estadoServicios[$id] ? 'activado' : 'desactivado';
            \Filament\Notifications\Notification::make()
                ->title('Estado actualizado')
                ->body("El servicio adicional ha sido {$estado}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al actualizar estado')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
            $this->estadoServicios[$id] = ! $this->estadoServicios[$id];
            $this->cargarServiciosAdicionales();
        }
    }

    public function agregarServicio(): void
    {
        $this->accionFormulario = 'crear';
        $this->servicioEnEdicion = [
            'id' => null,
            'name' => '',
            'code' => '',
            'brand' => ['Toyota'], // Inicializar como array con Toyota por defecto
            'description' => '',
            'is_active' => true,
        ];
        $this->isFormModalOpen = true;
    }

    public function editarServicio(int $id): void
    {
        try {
            // Always fetch fresh data from database to avoid inconsistencies
            $servicioModel = AdditionalService::findOrFail($id);
            
            $this->accionFormulario = 'editar';
            $this->servicioEnEdicion = [
                'id' => $servicioModel->id,
                'name' => $servicioModel->name,
                'code' => $servicioModel->code,
                'brand' => is_array($servicioModel->brand) ? $servicioModel->brand : ($servicioModel->brand ? [$servicioModel->brand] : ['Toyota']),
                'description' => $servicioModel->description,
                'is_active' => $servicioModel->is_active,
            ];
            
            $this->isFormModalOpen = true;
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al editar servicio')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function guardarServicio(): void
    {
        try {
            // Asegurar que brand sea un array
            if (!is_array($this->servicioEnEdicion['brand'])) {
                $this->servicioEnEdicion['brand'] = [];
            }
            
            // Filtrar valores vacíos y duplicados
            $this->servicioEnEdicion['brand'] = array_values(array_unique(array_filter($this->servicioEnEdicion['brand'])));
            
            // Validación adicional manual para debugging en QA
            if (empty($this->servicioEnEdicion['brand'])) {
                throw new \Exception('Debe seleccionar al menos una marca');
            }
            
            foreach ($this->servicioEnEdicion['brand'] as $marca) {
                if (!in_array($marca, ['Toyota', 'Lexus', 'Hino'])) {
                    throw new \Exception("La marca '{$marca}' no es válida. Debe ser Toyota, Lexus o Hino");
                }
            }
            
            $this->validate([
                'servicioEnEdicion.name' => 'required|string|max:255',
                'servicioEnEdicion.code' => 'required|string|max:50',
                'servicioEnEdicion.brand' => 'required|array|min:1',
                'servicioEnEdicion.brand.*' => 'in:Toyota,Lexus,Hino',
            ], [
                'servicioEnEdicion.name.required' => 'El nombre es obligatorio',
                'servicioEnEdicion.code.required' => 'El código es obligatorio',
                'servicioEnEdicion.brand.required' => 'Debe seleccionar al menos una marca',
                'servicioEnEdicion.brand.min' => 'Debe seleccionar al menos una marca',
                'servicioEnEdicion.brand.*.in' => 'Las marcas deben ser Toyota, Lexus o Hino',
            ]);

            if ($this->accionFormulario === 'editar' && ! empty($this->servicioEnEdicion['id'])) {
                $servicio = AdditionalService::findOrFail($this->servicioEnEdicion['id']);
            } else {
                $servicio = new AdditionalService;
            }

            $servicio->name = $this->servicioEnEdicion['name'];
            $servicio->code = $this->servicioEnEdicion['code'];
            $servicio->brand = $this->servicioEnEdicion['brand'];
            $servicio->description = $this->servicioEnEdicion['description'] ?? null;
            $servicio->is_active = $this->servicioEnEdicion['is_active'] ?? true;
            $servicio->save();

            \Filament\Notifications\Notification::make()
                ->title('Servicio guardado')
                ->body('El servicio adicional ha sido guardado correctamente')
                ->success()
                ->send();

            $this->isFormModalOpen = false;
            $this->cargarServiciosAdicionales();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al guardar servicio')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function eliminarServicio(int $id): void
    {
        try {
            $servicio = AdditionalService::findOrFail($id);
            $nombreServicio = $servicio->name;
            $servicio->delete();

            // Recargar la lista
            $this->cargarServiciosAdicionales();

            \Filament\Notifications\Notification::make()
                ->title('Servicio eliminado')
                ->body("El servicio adicional '{$nombreServicio}' ha sido eliminado correctamente")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al eliminar servicio')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cerrarFormModal(): void
    {
        $this->isFormModalOpen = false;
    }

    public function limpiarBusqueda(): void
    {
        $this->busqueda = '';
        $this->currentPage = 1;
    }

    public function limpiarFiltroMarca(): void
    {
        $this->filtroMarca = '';
        $this->currentPage = 1;
    }

    public function limpiarFiltros(): void
    {
        $this->busqueda = '';
        $this->filtroMarca = '';
        $this->currentPage = 1;
    }

    public function updatedBusqueda(): void
    {
        $this->currentPage = 1;
    }

    public function updatedFiltroMarca(): void
    {
        $this->currentPage = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->currentPage = $page;
    }

    /**
     * Método para manejar la selección/deselección de marcas
     */
    public function toggleMarca(string $marca): void
    {
        // Asegurar que brand sea un array
        if (!is_array($this->servicioEnEdicion['brand'])) {
            $this->servicioEnEdicion['brand'] = [];
        }

        // Toggle de la marca
        if (in_array($marca, $this->servicioEnEdicion['brand'])) {
            // Remover la marca
            $this->servicioEnEdicion['brand'] = array_values(array_filter($this->servicioEnEdicion['brand'], function($m) use ($marca) {
                return $m !== $marca;
            }));
        } else {
            // Agregar la marca
            $this->servicioEnEdicion['brand'][] = $marca;
        }

        // Limpiar duplicados y reindexar
        $this->servicioEnEdicion['brand'] = array_values(array_unique($this->servicioEnEdicion['brand']));
    }

    /**
     * Verificar si una marca está seleccionada
     */
    public function isMarcaSeleccionada(string $marca): bool
    {
        return is_array($this->servicioEnEdicion['brand'] ?? []) && in_array($marca, $this->servicioEnEdicion['brand']);
    }
}