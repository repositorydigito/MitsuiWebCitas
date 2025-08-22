<?php

namespace App\Filament\Pages;

use App\Models\ModelMaintenance;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class GestionMantenimientosPorModelo extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Mantenimientos por Tipo Valor Trabajo';

    protected static ?string $title = 'Gestión de Mantenimientos por Tipo Valor Trabajo';

    protected static string $view = 'filament.pages.gestion-mantenimientos-por-modelo';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 4;

    // Propiedades para la tabla
    public Collection $mantenimientosModelo;

    public int $perPage = 10;

    public int $currentPage = 1;

    // Propiedad para búsqueda
    public string $busqueda = '';

    // Propiedades para filtros
    public string $filtroMarca = '';
    public string $filtroModelo = '';

    // Estado de los mantenimientos
    public array $estadoMantenimientos = [];

    // Modal para agregar/editar mantenimiento
    public bool $isFormModalOpen = false;

    public ?array $mantenimientoEnEdicion = null;

    public string $accionFormulario = 'crear';

    public function mount(): void
    {
        $this->currentPage = request()->query('page', 1);
        $this->cargarMantenimientosModelo();
    }

    public function cargarMantenimientosModelo(): void
    {
        try {
            $mantenimientos = ModelMaintenance::ordenadoPorModelo()->get();

            $this->mantenimientosModelo = $mantenimientos->map(function ($mantenimiento) {
                return [
                    'id' => $mantenimiento->id,
                    'name' => $mantenimiento->name,
                    'code' => $mantenimiento->code,
                    'brand' => $mantenimiento->brand,
                    'tipo_valor_trabajo' => $mantenimiento->tipo_valor_trabajo,
                    'kilometers' => $mantenimiento->kilometers,
                    'description' => $mantenimiento->description,
                    'is_active' => $mantenimiento->is_active,
                ];
            });

            // Inicializar el estado de los mantenimientos
            foreach ($this->mantenimientosModelo as $mantenimiento) {
                $this->estadoMantenimientos[$mantenimiento['id']] = $mantenimiento['is_active'];
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al cargar mantenimientos por modelo')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
            $this->mantenimientosModelo = collect();
        }
    }

    public function getMantenimientosPaginadosProperty(): LengthAwarePaginator
    {
        $mantenimientosFiltrados = $this->mantenimientosModelo;
        
        // Filtro por búsqueda de texto
        if (! empty($this->busqueda)) {
            $terminoBusqueda = strtolower($this->busqueda);
            $mantenimientosFiltrados = $mantenimientosFiltrados->filter(function ($mantenimiento) use ($terminoBusqueda) {
                return str_contains(strtolower($mantenimiento['name']), $terminoBusqueda) ||
                       str_contains(strtolower($mantenimiento['code']), $terminoBusqueda) ||
                       str_contains(strtolower($mantenimiento['tipo_valor_trabajo'] ?? ''), $terminoBusqueda);
            });
        }

        // Filtro por marca
        if (! empty($this->filtroMarca)) {
            $mantenimientosFiltrados = $mantenimientosFiltrados->filter(function ($mantenimiento) {
                return $mantenimiento['brand'] === $this->filtroMarca;
            });
        }

        // Filtro por tipo valor trabajo (coincidencia parcial - filtra mientras escribes)
        if (! empty($this->filtroModelo)) {
            $mantenimientosFiltrados = $mantenimientosFiltrados->filter(function ($mantenimiento) {
                return str_contains(strtolower($mantenimiento['tipo_valor_trabajo'] ?? ''), strtolower($this->filtroModelo));
            });
        }

        if ($mantenimientosFiltrados->count() > 0 && $this->currentPage > ceil($mantenimientosFiltrados->count() / $this->perPage)) {
            $this->currentPage = 1;
        }

        return new LengthAwarePaginator(
            $mantenimientosFiltrados->forPage($this->currentPage, $this->perPage),
            $mantenimientosFiltrados->count(),
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
            $this->estadoMantenimientos[$id] = ! $this->estadoMantenimientos[$id];

            $this->mantenimientosModelo = $this->mantenimientosModelo->map(function ($mantenimiento) use ($id) {
                if ($mantenimiento['id'] === $id) {
                    $mantenimiento['is_active'] = $this->estadoMantenimientos[$id];
                }

                return $mantenimiento;
            });

            $mantenimiento = ModelMaintenance::findOrFail($id);
            $mantenimiento->is_active = $this->estadoMantenimientos[$id];
            $mantenimiento->save();

            $estado = $this->estadoMantenimientos[$id] ? 'activado' : 'desactivado';
            \Filament\Notifications\Notification::make()
                ->title('Estado actualizado')
                ->body("El mantenimiento por modelo ha sido {$estado}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al actualizar estado')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
            $this->estadoMantenimientos[$id] = ! $this->estadoMantenimientos[$id];
            $this->cargarMantenimientosModelo();
        }
    }

    public function agregarMantenimiento(): void
    {
        $this->accionFormulario = 'crear';
        $this->mantenimientoEnEdicion = [
            'id' => null,
            'name' => '',
            'code' => '',
            'brand' => 'Toyota',
            'tipo_valor_trabajo' => '',
            'kilometers' => '',
            'description' => '',
            'is_active' => true,
        ];
        $this->isFormModalOpen = true;
    }

    public function editarMantenimiento(int $id): void
    {
        try {
            // Encontrar el mantenimiento seleccionado
            $mantenimientoSeleccionado = $this->mantenimientosModelo->firstWhere('id', $id);
            
            if ($mantenimientoSeleccionado) {
                // Obtener todos los mantenimientos con el mismo código
                $mantenimientosGrupo = $this->mantenimientosModelo
                    ->where('code', $mantenimientoSeleccionado['code'])
                    ->where('brand', $mantenimientoSeleccionado['brand'])
                    ->where('kilometers', $mantenimientoSeleccionado['kilometers']);
                
                // Preparar los datos para el formulario
                $this->accionFormulario = 'editar';
                $this->mantenimientoEnEdicion = [
                    'id' => $id,
                    'name' => $mantenimientoSeleccionado['name'],
                    'code' => $mantenimientoSeleccionado['code'],
                    'brand' => $mantenimientoSeleccionado['brand'],
                    'tipo_valor_trabajo' => $mantenimientosGrupo->pluck('tipo_valor_trabajo')->implode(', '),
                    'kilometers' => $mantenimientoSeleccionado['kilometers'],
                    'description' => $mantenimientoSeleccionado['description'],
                    'is_active' => $mantenimientoSeleccionado['is_active'],
                ];
                
                $this->isFormModalOpen = true;
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al cargar el mantenimiento')
                ->body('Ha ocurrido un error al cargar los datos del mantenimiento: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function guardarMantenimiento(): void
    {
        // Iniciar transacción para asegurar la consistencia de los datos
        \DB::beginTransaction();
        
        try {
            // Validar los datos del formulario
            $this->validate([
                'mantenimientoEnEdicion.name' => 'required|string|max:255',
                'mantenimientoEnEdicion.code' => 'required|string|max:50',
                'mantenimientoEnEdicion.brand' => 'required|in:Toyota,Lexus,Hino',
                'mantenimientoEnEdicion.tipo_valor_trabajo' => 'required|string|max:1000',
                'mantenimientoEnEdicion.kilometers' => 'required|integer|min:1',
            ], [
                'mantenimientoEnEdicion.name.required' => 'El nombre es obligatorio',
                'mantenimientoEnEdicion.code.required' => 'El código es obligatorio',
                'mantenimientoEnEdicion.brand.required' => 'La marca es obligatoria',
                'mantenimientoEnEdicion.brand.in' => 'La marca debe ser Toyota, Lexus o Hino',
                'mantenimientoEnEdicion.tipo_valor_trabajo.required' => 'El tipo valor trabajo es obligatorio',
                'mantenimientoEnEdicion.kilometers.required' => 'Los kilómetros son obligatorios',
                'mantenimientoEnEdicion.kilometers.integer' => 'Los kilómetros deben ser un número',
            ]);

            // Procesar los tipos de valor de trabajo
            $tiposValorTrabajo = array_map('trim', 
                explode(',', $this->mantenimientoEnEdicion['tipo_valor_trabajo'])
            );
            
            // Eliminar valores vacíos
            $tiposValorTrabajo = array_filter($tiposValorTrabajo, function($value) {
                return !empty($value);
            });

            if (empty($tiposValorTrabajo)) {
                throw new \Exception('Debe ingresar al menos un tipo de valor de trabajo');
            }

            // Validar cada tipo de valor de trabajo
            foreach ($tiposValorTrabajo as $tipo) {
                if (strlen($tipo) > 100) {
                    throw new \Exception("El tipo de valor de trabajo '{$tipo}' excede el máximo de 100 caracteres");
                }
            }

            // Verificar que no existan conflictos con combinaciones únicas (solo para nuevos registros)
            if ($this->accionFormulario === 'crear') {
                foreach ($tiposValorTrabajo as $tipo) {
                    $existingRecord = ModelMaintenance::where('brand', $this->mantenimientoEnEdicion['brand'])
                        ->where('kilometers', $this->mantenimientoEnEdicion['kilometers'])
                        ->where('tipo_valor_trabajo', $tipo);
                    
                    if ($existingRecord->exists()) {
                        $existingName = $existingRecord->first()->name;
                        throw new \Exception("El tipo de valor de trabajo '{$tipo}' ya existe para la marca {$this->mantenimientoEnEdicion['brand']} con {$this->mantenimientoEnEdicion['kilometers']} km en el mantenimiento '{$existingName}'");
                    }
                }
            }

            // Si estamos editando, eliminar los registros antiguos con este código, marca y kilómetros
            if ($this->accionFormulario === 'editar') {
                ModelMaintenance::where('code', $this->mantenimientoEnEdicion['code'])
                    ->where('brand', $this->mantenimientoEnEdicion['brand'])
                    ->where('kilometers', $this->mantenimientoEnEdicion['kilometers'])
                    ->delete();
            }

            // Crear un nuevo registro para cada tipo de valor de trabajo
            $savedCount = 0;
            $skippedCount = 0;
            
            foreach ($tiposValorTrabajo as $tipo) {
                // Para edición, ya eliminamos los registros anteriores, así que no hay que verificar duplicados
                // Para creación, ya verificamos duplicados arriba
                
                $mantenimiento = new ModelMaintenance();
                $mantenimiento->name = $this->mantenimientoEnEdicion['name'];
                $mantenimiento->code = $this->mantenimientoEnEdicion['code'];
                $mantenimiento->brand = $this->mantenimientoEnEdicion['brand'];
                $mantenimiento->tipo_valor_trabajo = $tipo;
                $mantenimiento->kilometers = $this->mantenimientoEnEdicion['kilometers'];
                $mantenimiento->description = $this->mantenimientoEnEdicion['description'] ?? null;
                $mantenimiento->is_active = $this->mantenimientoEnEdicion['is_active'] ?? true;
                
                if ($mantenimiento->save()) {
                    $savedCount++;
                }
            }
            
            if ($savedCount === 0) {
                throw new \Exception('No se pudo guardar ningún mantenimiento');
            }
            
            // Confirmar la transacción
            \DB::commit();
            
            // Mostrar mensaje de éxito
            $message = $savedCount === 1 
                ? 'Se ha guardado 1 tipo de valor de trabajo correctamente' 
                : "Se han guardado {$savedCount} tipos de valor de trabajo correctamente";
            
            \Filament\Notifications\Notification::make()
                ->title('Mantenimiento guardado')
                ->body($message)
                ->success()
                ->send();
            
            $this->isFormModalOpen = false;
            $this->cargarMantenimientosModelo();
            
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            \DB::rollBack();
            
            // Mostrar mensaje de error
            \Filament\Notifications\Notification::make()
                ->title('Error al guardar mantenimiento')
                ->body('Ha ocurrido un error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function confirmarEliminacion(int $id): void
    {
        try {
            // Buscar el mantenimiento para obtener información del grupo
            $mantenimiento = ModelMaintenance::findOrFail($id);
            
            // Obtener todos los mantenimientos del mismo grupo (mismo código, marca y kilómetros)
            $mantenimientosGrupo = ModelMaintenance::where('code', $mantenimiento->code)
                ->where('brand', $mantenimiento->brand)
                ->where('kilometers', $mantenimiento->kilometers)
                ->get();
            
            $tiposValorTrabajo = $mantenimientosGrupo->pluck('tipo_valor_trabajo')->filter()->implode(', ');
            
            // Crear mensaje sin saltos de línea para evitar errores de JavaScript
            $mensaje = "¿Estás seguro de que deseas eliminar el mantenimiento '{$mantenimiento->name}'? ";
            $mensaje .= "Esto eliminará todos los tipos de valor de trabajo asociados: {$tiposValorTrabajo}. ";
            $mensaje .= "Esta acción no se puede deshacer.";
            
            // Escapar comillas para JavaScript
            $mensajeEscapado = addslashes($mensaje);
            
            // Ejecutar confirmación directamente
            $this->js("if (confirm('{$mensajeEscapado}')) { \$wire.eliminarGrupoMantenimiento({$id}); }");
            
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al preparar la eliminación: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function eliminarGrupoMantenimiento(int $id): void
    {
        try {
            // Buscar el mantenimiento de referencia
            $mantenimiento = ModelMaintenance::findOrFail($id);
            $nombreMantenimiento = $mantenimiento->name;
            
            // Eliminar todos los mantenimientos del mismo grupo (mismo código, marca y kilómetros)
            $eliminados = ModelMaintenance::where('code', $mantenimiento->code)
                ->where('brand', $mantenimiento->brand)
                ->where('kilometers', $mantenimiento->kilometers)
                ->delete();

            // Recargar la lista
            $this->cargarMantenimientosModelo();

            \Filament\Notifications\Notification::make()
                ->title('Mantenimientos eliminados')
                ->body("El grupo de mantenimientos '{$nombreMantenimiento}' ha sido eliminado correctamente ({$eliminados} registros)")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al eliminar mantenimientos')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function eliminarMantenimiento(int $id): void
    {
        try {
            $mantenimiento = ModelMaintenance::findOrFail($id);
            $nombreMantenimiento = $mantenimiento->name;
            $mantenimiento->delete();

            // Recargar la lista
            $this->cargarMantenimientosModelo();

            \Filament\Notifications\Notification::make()
                ->title('Mantenimiento eliminado')
                ->body("El mantenimiento '{$nombreMantenimiento}' ha sido eliminado correctamente")
                ->success()
                ->send();
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error al eliminar mantenimiento')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cerrarFormModal(): void
    {
        $this->isFormModalOpen = false;
    }

    public function limpiarFiltros(): void
    {
        $this->busqueda = '';
        $this->filtroMarca = '';
        $this->filtroModelo = '';
        $this->currentPage = 1;
    }

    public function updatedBusqueda(): void
    {
        $this->currentPage = 1;
    }

    public function updatedFiltroMarca(): void
    {
        $this->currentPage = 1;
        // No limpiar filtro de modelo ya que ahora es búsqueda libre
    }

    public function updatedFiltroModelo(): void
    {
        $this->currentPage = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->currentPage = $page;
    }

    /**
     * Obtener modelos únicos para referencia (ya no se usa en select, pero se mantiene por compatibilidad)
     */
    public function getModelosDisponibles(): array
    {
        $mantenimientos = $this->mantenimientosModelo;
        
        // Si hay filtro de marca aplicado, filtrar primero por marca
        if (! empty($this->filtroMarca)) {
            $mantenimientos = $mantenimientos->filter(function ($mantenimiento) {
                return $mantenimiento['brand'] === $this->filtroMarca;
            });
        }
        
        return $mantenimientos
            ->pluck('tipo_valor_trabajo')
            ->filter() // Filtrar valores null/vacíos
            ->unique()
            ->sort()
            ->values()
            ->toArray();
    }

    /**
     * Obtener estadísticas del módulo
     */
    public function getEstadisticas(): array
    {
        return [
            'total' => $this->mantenimientosModelo->count(),
            'activos' => $this->mantenimientosModelo->where('is_active', true)->count(),
            'inactivos' => $this->mantenimientosModelo->where('is_active', false)->count(),
            'por_marca' => $this->mantenimientosModelo->groupBy('brand')->map->count()->toArray(),
        ];
    }
}