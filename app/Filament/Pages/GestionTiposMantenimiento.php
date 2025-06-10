<?php

namespace App\Filament\Pages;

use App\Models\MaintenanceType;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class GestionTiposMantenimiento extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Tipos de Mantenimiento';

    protected static ?string $title = 'Gestión de Tipos de Mantenimiento';

    protected static string $view = 'filament.pages.gestion-tipos-mantenimiento';

    protected static ?string $navigationGroup = '⚙️ Configuración';
    
    protected static ?int $navigationSort = 3;

    // Propiedades para la tabla
    public Collection $tiposMantenimiento;

    public int $perPage = 10;

    public int $currentPage = 1;

    // Propiedad para búsqueda
    public string $busqueda = '';

    // Estado de los tipos
    public array $estadoTipos = [];

    // Modal para agregar/editar tipo
    public bool $isFormModalOpen = false;

    public ?array $tipoEnEdicion = null;

    public string $accionFormulario = 'crear';

    public function mount(): void
    {
        $this->currentPage = request()->query('page', 1);
        $this->cargarTiposMantenimiento();
    }

    public function cargarTiposMantenimiento(): void
    {
        try {
            $tipos = MaintenanceType::orderBy('kilometers')->get();

            $this->tiposMantenimiento = $tipos->map(function ($tipo) {
                return [
                    'id' => $tipo->id,
                    'name' => $tipo->name,
                    'code' => $tipo->code,
                    'description' => $tipo->description,
                    'kilometers' => $tipo->kilometers,
                    'is_active' => $tipo->is_active,
                ];
            });

            // Inicializar el estado de los tipos
            foreach ($this->tiposMantenimiento as $tipo) {
                $this->estadoTipos[$tipo['id']] = $tipo['is_active'];
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al cargar tipos de mantenimiento')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
            $this->tiposMantenimiento = collect();
        }
    }

    public function getTiposPaginadosProperty(): LengthAwarePaginator
    {
        $tiposFiltrados = $this->tiposMantenimiento;
        if (! empty($this->busqueda)) {
            $terminoBusqueda = strtolower($this->busqueda);
            $tiposFiltrados = $this->tiposMantenimiento->filter(function ($tipo) use ($terminoBusqueda) {
                return str_contains(strtolower($tipo['name']), $terminoBusqueda) ||
                       str_contains(strtolower($tipo['code']), $terminoBusqueda);
            });
        }

        if ($tiposFiltrados->count() > 0 && $this->currentPage > ceil($tiposFiltrados->count() / $this->perPage)) {
            $this->currentPage = 1;
        }

        return new LengthAwarePaginator(
            $tiposFiltrados->forPage($this->currentPage, $this->perPage),
            $tiposFiltrados->count(),
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
            $this->estadoTipos[$id] = ! $this->estadoTipos[$id];

            $this->tiposMantenimiento = $this->tiposMantenimiento->map(function ($tipo) use ($id) {
                if ($tipo['id'] === $id) {
                    $tipo['is_active'] = $this->estadoTipos[$id];
                }
                return $tipo;
            });

            $tipo = MaintenanceType::findOrFail($id);
            $tipo->is_active = $this->estadoTipos[$id];
            $tipo->save();

            $estado = $this->estadoTipos[$id] ? 'activado' : 'desactivado';
            \Filament\Notifications\Notification::make()
                ->title('Estado actualizado')
                ->body("El tipo de mantenimiento ha sido {$estado}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al actualizar estado')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
            $this->estadoTipos[$id] = ! $this->estadoTipos[$id];
            $this->cargarTiposMantenimiento();
        }
    }

    public function agregarTipo(): void
    {
        $this->accionFormulario = 'crear';
        $this->tipoEnEdicion = [
            'id' => null,
            'name' => '',
            'code' => '',
            'description' => '',
            'kilometers' => '',
            'is_active' => true,
        ];
        $this->isFormModalOpen = true;
    }

    public function editarTipo(int $id): void
    {
        try {
            $tipo = $this->tiposMantenimiento->firstWhere('id', $id);
            if ($tipo) {
                $this->accionFormulario = 'editar';
                $this->tipoEnEdicion = $tipo;
                $this->isFormModalOpen = true;
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al editar tipo')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function guardarTipo(): void
    {
        try {
            $this->validate([
                'tipoEnEdicion.name' => 'required|string|max:255',
                'tipoEnEdicion.code' => 'required|string|max:50',
                'tipoEnEdicion.kilometers' => 'required|integer|min:1',
            ], [
                'tipoEnEdicion.name.required' => 'El nombre es obligatorio',
                'tipoEnEdicion.code.required' => 'El código es obligatorio',
                'tipoEnEdicion.kilometers.required' => 'Los kilómetros son obligatorios',
                'tipoEnEdicion.kilometers.integer' => 'Los kilómetros deben ser un número',
            ]);

            if ($this->accionFormulario === 'editar' && ! empty($this->tipoEnEdicion['id'])) {
                $tipo = MaintenanceType::findOrFail($this->tipoEnEdicion['id']);
            } else {
                $tipo = new MaintenanceType;
            }

            $tipo->name = $this->tipoEnEdicion['name'];
            $tipo->code = $this->tipoEnEdicion['code'];
            $tipo->description = $this->tipoEnEdicion['description'] ?? null;
            $tipo->kilometers = $this->tipoEnEdicion['kilometers'];
            $tipo->is_active = $this->tipoEnEdicion['is_active'] ?? true;
            $tipo->save();

            \Filament\Notifications\Notification::make()
                ->title('Tipo guardado')
                ->body('El tipo de mantenimiento ha sido guardado correctamente')
                ->success()
                ->send();

            $this->isFormModalOpen = false;
            $this->cargarTiposMantenimiento();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al guardar tipo')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function eliminarTipo(int $id): void
    {
        try {
            $tipo = MaintenanceType::findOrFail($id);
            $nombreTipo = $tipo->name;
            $tipo->delete();

            // Recargar la lista
            $this->cargarTiposMantenimiento();

            \Filament\Notifications\Notification::make()
                ->title('Tipo eliminado')
                ->body("El tipo de mantenimiento '{$nombreTipo}' ha sido eliminado correctamente")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al eliminar tipo')
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
