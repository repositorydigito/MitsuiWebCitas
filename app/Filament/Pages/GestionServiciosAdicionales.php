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

    protected static ?string $navigationGroup = '⚙️ Configuración';

    protected static ?int $navigationSort = 4;

    // Propiedades para la tabla
    public Collection $serviciosAdicionales;

    public int $perPage = 10;

    public int $currentPage = 1;

    // Propiedad para búsqueda
    public string $busqueda = '';

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
                return [
                    'id' => $servicio->id,
                    'name' => $servicio->name,
                    'code' => $servicio->code,
                    'description' => $servicio->description,
                    'price' => $servicio->price,
                    'duration_minutes' => $servicio->duration_minutes,
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
        if (! empty($this->busqueda)) {
            $terminoBusqueda = strtolower($this->busqueda);
            $serviciosFiltrados = $this->serviciosAdicionales->filter(function ($servicio) use ($terminoBusqueda) {
                return str_contains(strtolower($servicio['name']), $terminoBusqueda) ||
                       str_contains(strtolower($servicio['code']), $terminoBusqueda);
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
            'description' => '',
            'price' => '',
            'duration_minutes' => 60,
            'is_active' => true,
        ];
        $this->isFormModalOpen = true;
    }

    public function editarServicio(int $id): void
    {
        try {
            $servicio = $this->serviciosAdicionales->firstWhere('id', $id);
            if ($servicio) {
                $this->accionFormulario = 'editar';
                $this->servicioEnEdicion = $servicio;
                $this->isFormModalOpen = true;
            }
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
            $this->validate([
                'servicioEnEdicion.name' => 'required|string|max:255',
                'servicioEnEdicion.code' => 'required|string|max:50',
                'servicioEnEdicion.duration_minutes' => 'required|integer|min:1',
                'servicioEnEdicion.price' => 'nullable|numeric|min:0',
            ], [
                'servicioEnEdicion.name.required' => 'El nombre es obligatorio',
                'servicioEnEdicion.code.required' => 'El código es obligatorio',
                'servicioEnEdicion.duration_minutes.required' => 'La duración es obligatoria',
                'servicioEnEdicion.price.numeric' => 'El precio debe ser un número',
            ]);

            if ($this->accionFormulario === 'editar' && ! empty($this->servicioEnEdicion['id'])) {
                $servicio = AdditionalService::findOrFail($this->servicioEnEdicion['id']);
            } else {
                $servicio = new AdditionalService;
            }

            $servicio->name = $this->servicioEnEdicion['name'];
            $servicio->code = $this->servicioEnEdicion['code'];
            $servicio->description = $this->servicioEnEdicion['description'] ?? null;
            $servicio->price = $this->servicioEnEdicion['price'] ?? null;
            $servicio->duration_minutes = $this->servicioEnEdicion['duration_minutes'];
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

    public function updatedBusqueda(): void
    {
        $this->currentPage = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->currentPage = $page;
    }
}
