<?php

namespace App\Filament\Pages;

use App\Models\Modelo;
use App\Models\ModeloAno;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\WithFileUploads;

class AdministrarModelos extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms, WithFileUploads;

    protected static string $view = 'filament.pages.administrar-modelos';

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Administrar Modelos';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Administrar Modelos';

    protected static ?string $slug = 'administrar-modelos';

    // Variables para el formulario de modelos
    public $modelos = [];

    public $modalVisible = false;

    public $editMode = false;

    public $currentImage = null;

    public $currentModeloId = null;

    public $formData = [
        'code' => '',
        'name' => '',
        'brand' => 'TOYOTA',
        'description' => '',
        'is_active' => true,
        'image' => null, // Nuevo campo para la imagen
    ];

    // Variables para el formulario de años
    public $anosModalVisible = false;

    public $currentModeloNombre = '';

    public $modeloAnosData = [];

    public $nuevoAno = '';

    // Errores de validación
    public $erroresCampos  = [
        'code' => false,
        'name' => false,
        'nuevoAno' => false,
    ];

    // Propiedades para los filtros
    public string $filtroNombre = '';
    public string $filtroMarca = '';

    public function mount(): void
    {
        $this->cargarModelos();
    }

    public function cargarModelos()
    {
        $query = Modelo::with('anos');

        // Aplicar filtro por nombre
        if (!empty($this->filtroNombre)) {
            $query->where('name', 'like', '%' . $this->filtroNombre . '%');
        }

        // Aplicar filtro por marca
        if (!empty($this->filtroMarca)) {
            $query->where('brand', $this->filtroMarca);
        }

        $this->modelos = $query->orderBy('name')->get();
    }

    public function abrirModal($id = null)
    {
        $this->resetearFormulario();

        if ($id) {
            $modelo = Modelo::findOrFail($id);
            $this->formData = [
                'code' => $modelo->code,
                'name' => $modelo->name,
                'brand' => $modelo->brand,
                'description' => $modelo->description,
                'is_active' => $modelo->is_active,
                'image' => null, // Cargar imagen existente
            ];
            $this->currentImage = $modelo->image; // ← imagen actual
            $this->editMode = true;
            $this->currentModeloId = $id;
        } else {
            $this->editMode = false;
            $this->currentModeloId = null;
        }

        $this->modalVisible = true;
        $this->dispatch('modal-opened');
    }

    public function cerrarModal()
    {
        $this->modalVisible = false;
    }

    public function resetearFormulario()
    {
        $this->formData = [
            'code' => '',
            'name' => '',
            'brand' => 'TOYOTA',
            'description' => '',
            'is_active' => true,
            'image' => null, // Limpiar imagen
        ];

        $this->erroresCampos  = [
            'code' => false,
            'name' => false,
            'nuevoAno' => false,
        ];
        
        $this->currentImage = null;
    }

    public function limpiarImagen()
    {
        $this->formData['image'] = null;
        $this->dispatch('image-cleared');
    }

    public function guardarModelo()
    {
        // Resetear errores
        $this->erroresCampos = [
            'code' => false,
            'name' => false,
        ];

        // Validar campos requeridos
        $hasErrors = false;

        if (empty($this->formData['code'])) {
            $this->erroresCampos ['code'] = true;
            $hasErrors = true;
        }

        if (empty($this->formData['name'])) {
            $this->erroresCampos ['name'] = true;
            $hasErrors = true;
        }

        // Si hay errores, no continuar
        if ($hasErrors) {
            return;
        }

        // Validar que el código sea único
        if (! $this->editMode) {
            $existeCodigo = Modelo::where('code', $this->formData['code'])->exists();
            if ($existeCodigo) {
                $this->erroresCampos ['code'] = true;
                Notification::make()
                    ->danger()
                    ->title('El código ya existe')
                    ->body('Por favor, elige otro código para el modelo.')
                    ->send();

                return;
            }
        }

        try {
            $imagePath = null;
            $imageUpdated = false;
            
            // Debug: Log del estado inicial
            \Log::info('Iniciando guardado de modelo', [
                'editMode' => $this->editMode,
                'currentImage' => $this->currentImage,
                'formData_image_type' => gettype($this->formData['image']),
                'formData_image_is_string' => is_string($this->formData['image']),
                'formData_image_exists' => isset($this->formData['image']),
                'formData_image_not_null' => !is_null($this->formData['image'])
            ]);
            
            // Procesar imagen si se subió una nueva
            if (isset($this->formData['image']) && $this->formData['image'] && !is_string($this->formData['image'])) {
                try {
                    // Validar la imagen antes de procesarla
                    $this->validate([
                        'formData.image' => 'image|mimes:jpeg,jpg,png,gif,webp|max:10240', // 10MB, tipos específicos
                    ]);
                    
                    // Debug: Log de la imagen antes de procesar
                    \Log::info('Procesando nueva imagen', [
                        'original_name' => $this->formData['image']->getClientOriginalName(),
                        'size' => $this->formData['image']->getSize(),
                        'mime' => $this->formData['image']->getMimeType(),
                        'extension' => $this->formData['image']->getClientOriginalExtension()
                    ]);
                    
                    // Eliminar imagen anterior si existe (solo en modo edición)
                    if ($this->editMode && $this->currentImage) {
                        if (\Storage::disk('public')->exists($this->currentImage)) {
                            \Storage::disk('public')->delete($this->currentImage);
                            \Log::info('Imagen anterior eliminada', ['path' => $this->currentImage]);
                        }
                    }
                    
                    // Guardar nueva imagen
                    $imagePath = $this->formData['image']->store('modelos', 'public');
                    $imageUpdated = true;
                    
                    \Log::info('Imagen procesada correctamente', [
                        'path' => $imagePath,
                        'size' => $this->formData['image']->getSize(),
                        'mime' => $this->formData['image']->getMimeType(),
                        'stored_successfully' => \Storage::disk('public')->exists($imagePath)
                    ]);
                    
                } catch (\Illuminate\Validation\ValidationException $e) {
                    \Log::error('Error de validación de imagen', [
                        'errors' => $e->errors(),
                        'file_info' => [
                            'name' => $this->formData['image']->getClientOriginalName(),
                            'size' => $this->formData['image']->getSize(),
                            'mime' => $this->formData['image']->getMimeType()
                        ]
                    ]);
                    throw $e;
                }
            }

            if ($this->editMode) {
                // Actualizar modelo existente
                $modelo = Modelo::findOrFail($this->currentModeloId);
                $updateData = [
                    'name' => $this->formData['name'],
                    'brand' => $this->formData['brand'],
                    'description' => $this->formData['description'],
                    'is_active' => $this->formData['is_active'],
                ];
                
                // Solo actualizar imagen si se subió una nueva
                if ($imageUpdated && $imagePath) {
                    $updateData['image'] = $imagePath;
                }
                
                $modelo->update($updateData);

                Notification::make()
                    ->success()
                    ->title('Modelo actualizado')
                    ->body('El modelo ha sido actualizado correctamente.')
                    ->send();
            } else {
                // Crear nuevo modelo
                $createData = [
                    'code' => $this->formData['code'],
                    'name' => $this->formData['name'],
                    'brand' => $this->formData['brand'],
                    'description' => $this->formData['description'],
                    'is_active' => $this->formData['is_active'],
                ];
                
                // Agregar imagen si se procesó correctamente
                if ($imageUpdated && $imagePath) {
                    $createData['image'] = $imagePath;
                }
                
                $modelo = Modelo::create($createData);

                // Crear años por defecto para el nuevo modelo
                $anos = ['2018', '2019', '2020', '2021', '2022', '2023', '2024', '2025'];
                foreach ($anos as $ano) {
                    ModeloAno::create([
                        'model_id' => $modelo->id,
                        'year' => $ano,
                        'is_active' => true,
                    ]);
                }

                Notification::make()
                    ->success()
                    ->title('Modelo creado')
                    ->body('El modelo ha sido creado correctamente.')
                    ->send();
            }

            // Recargar modelos y cerrar modal
            $this->cargarModelos();
            $this->cerrarModal();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->send();
        }
    }

    public function eliminarModelo($id)
    {
        try {
            $modelo = Modelo::findOrFail($id);
            $modelo->delete();

            Notification::make()
                ->success()
                ->title('Modelo eliminado')
                ->body('El modelo ha sido eliminado correctamente.')
                ->send();

            $this->cargarModelos();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->send();
        }
    }

    public function toggleEstado($id)
    {
        try {
            $modelo = Modelo::findOrFail($id);
            $modelo->update([
                'is_active' => ! $modelo->is_active,
            ]);

            Notification::make()
                ->success()
                ->title('Estado actualizado')
                ->body('El estado del modelo ha sido actualizado correctamente.')
                ->send();

            $this->cargarModelos();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->send();
        }
    }

    public function abrirModalAnos($id)
    {
        $modelo = Modelo::with('anos')->findOrFail($id);
        $this->currentModeloId = $id;
        $this->currentModeloNombre = $modelo->name;
        $this->modeloAnosData = $modelo->anos->sortByDesc('year')->values()->toArray();
        $this->nuevoAno = '';
        $this->erroresCampos ['nuevoAno'] = false;
        $this->anosModalVisible = true;
    }

    public function cerrarModalAnos()
    {
        $this->anosModalVisible = false;
    }

    public function agregarAno()
    {
        // Resetear error
        $this->erroresCampos ['nuevoAno'] = false;

        // Validar año
        if (empty($this->nuevoAno) || ! is_numeric($this->nuevoAno) || strlen($this->nuevoAno) !== 4) {
            $this->erroresCampos ['nuevoAno'] = true;

            return;
        }

        try {
            // Verificar si el año ya existe para este modelo
            $existeAno = ModeloAno::where('model_id', $this->currentModeloId)
                ->where('year', $this->nuevoAno)
                ->exists();

            if ($existeAno) {
                Notification::make()
                    ->warning()
                    ->title('Año duplicado')
                    ->body('Este año ya existe para este modelo.')
                    ->send();

                return;
            }

            // Crear nuevo año
            ModeloAno::create([
                'model_id' => $this->currentModeloId,
                'year' => $this->nuevoAno,
                'is_active' => true,
            ]);

            // Recargar años
            $modelo = Modelo::with('anos')->findOrFail($this->currentModeloId);
            $this->modeloAnosData = $modelo->anos->sortByDesc('year')->values()->toArray();
            $this->nuevoAno = '';

            Notification::make()
                ->success()
                ->title('Año agregado')
                ->body('El año ha sido agregado correctamente.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->send();
        }
    }

    public function toggleEstadoAno($id)
    {
        try {
            $modeloAno = ModeloAno::findOrFail($id);
            $modeloAno->update([
                'is_active' => ! $modeloAno->is_active,
            ]);

            // Recargar años
            $modelo = Modelo::with('anos')->findOrFail($this->currentModeloId);
            $this->modeloAnosData = $modelo->anos->sortByDesc('year')->values()->toArray();

            Notification::make()
                ->success()
                ->title('Estado actualizado')
                ->body('El estado del año ha sido actualizado correctamente.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->send();
        }
    }

    public function eliminarAno($id)
    {
        try {
            $modeloAno = ModeloAno::findOrFail($id);
            $modeloAno->delete();

            // Recargar años
            $modelo = Modelo::with('anos')->findOrFail($this->currentModeloId);
            $this->modeloAnosData = $modelo->anos->sortByDesc('year')->values()->toArray();

            Notification::make()
                ->success()
                ->title('Año eliminado')
                ->body('El año ha sido eliminado correctamente.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->send();
        }
    }

    /**
     * Método para limpiar los filtros
     */
    public function limpiarFiltros(): void
    {
        $this->filtroNombre = '';
        $this->filtroMarca = '';
        $this->cargarModelos();
    }

    /**
     * Método que se ejecuta cuando cambia el filtro de nombre
     */
    public function updatedFiltroNombre(): void
    {
        $this->cargarModelos();
    }

    /**
     * Método que se ejecuta cuando cambia el filtro de marca
     */
    public function updatedFiltroMarca(): void
    {
        $this->cargarModelos();
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Configuración';
    }
}
