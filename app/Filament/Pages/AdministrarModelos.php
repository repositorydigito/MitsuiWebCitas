<?php

namespace App\Filament\Pages;

use App\Models\Modelo;
use App\Models\ModeloAno;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Validator;

class AdministrarModelos extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.administrar-modelos';
    protected static ?string $navigationIcon = 'heroicon-o-truck';
    protected static ?string $navigationLabel = 'Administrar Modelos';
    protected static ?string $title = 'Administrar Modelos';
    protected static ?string $slug = 'administrar-modelos';

    // Variables para el formulario de modelos
    public $modelos = [];
    public $modalVisible = false;
    public $editMode = false;
    public $currentModeloId = null;
    public $formData = [
        'codigo' => '',
        'nombre' => '',
        'marca' => 'TOYOTA',
        'descripcion' => '',
        'activo' => true,
    ];
    
    // Variables para el formulario de años
    public $anosModalVisible = false;
    public $currentModeloNombre = '';
    public $modeloAnosData = [];
    public $nuevoAno = '';
    
    // Errores de validación
    public $errors = [
        'codigo' => false,
        'nombre' => false,
        'nuevoAno' => false,
    ];

    public function mount(): void
    {
        $this->cargarModelos();
    }

    public function cargarModelos()
    {
        $this->modelos = Modelo::with('anos')->orderBy('nombre')->get();
    }

    public function abrirModal($id = null)
    {
        $this->resetearFormulario();
        
        if ($id) {
            $modelo = Modelo::findOrFail($id);
            $this->formData = [
                'codigo' => $modelo->codigo,
                'nombre' => $modelo->nombre,
                'marca' => $modelo->marca,
                'descripcion' => $modelo->descripcion,
                'activo' => $modelo->activo,
            ];
            $this->editMode = true;
            $this->currentModeloId = $id;
        } else {
            $this->editMode = false;
            $this->currentModeloId = null;
        }
        
        $this->modalVisible = true;
    }

    public function cerrarModal()
    {
        $this->modalVisible = false;
    }

    public function resetearFormulario()
    {
        $this->formData = [
            'codigo' => '',
            'nombre' => '',
            'marca' => 'TOYOTA',
            'descripcion' => '',
            'activo' => true,
        ];
        
        $this->errors = [
            'codigo' => false,
            'nombre' => false,
            'nuevoAno' => false,
        ];
    }

    public function guardarModelo()
    {
        // Resetear errores
        $this->errors = [
            'codigo' => false,
            'nombre' => false,
        ];
        
        // Validar campos requeridos
        $hasErrors = false;
        
        if (empty($this->formData['codigo'])) {
            $this->errors['codigo'] = true;
            $hasErrors = true;
        }
        
        if (empty($this->formData['nombre'])) {
            $this->errors['nombre'] = true;
            $hasErrors = true;
        }
        
        // Si hay errores, no continuar
        if ($hasErrors) {
            return;
        }
        
        // Validar que el código sea único
        if (!$this->editMode) {
            $existeCodigo = Modelo::where('codigo', $this->formData['codigo'])->exists();
            if ($existeCodigo) {
                $this->errors['codigo'] = true;
                Notification::make()
                    ->danger()
                    ->title('El código ya existe')
                    ->body('Por favor, elige otro código para el modelo.')
                    ->send();
                return;
            }
        }
        
        try {
            if ($this->editMode) {
                // Actualizar modelo existente
                $modelo = Modelo::findOrFail($this->currentModeloId);
                $modelo->update([
                    'nombre' => $this->formData['nombre'],
                    'marca' => $this->formData['marca'],
                    'descripcion' => $this->formData['descripcion'],
                    'activo' => $this->formData['activo'],
                ]);
                
                Notification::make()
                    ->success()
                    ->title('Modelo actualizado')
                    ->body('El modelo ha sido actualizado correctamente.')
                    ->send();
            } else {
                // Crear nuevo modelo
                $modelo = Modelo::create([
                    'codigo' => $this->formData['codigo'],
                    'nombre' => $this->formData['nombre'],
                    'marca' => $this->formData['marca'],
                    'descripcion' => $this->formData['descripcion'],
                    'activo' => $this->formData['activo'],
                ]);
                
                // Crear años por defecto para el nuevo modelo
                $anos = ['2018', '2019', '2020', '2021', '2022', '2023', '2024'];
                foreach ($anos as $ano) {
                    ModeloAno::create([
                        'modelo_id' => $modelo->id,
                        'ano' => $ano,
                        'activo' => true,
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
                ->body('Ha ocurrido un error: ' . $e->getMessage())
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
                ->body('Ha ocurrido un error: ' . $e->getMessage())
                ->send();
        }
    }

    public function toggleEstado($id)
    {
        try {
            $modelo = Modelo::findOrFail($id);
            $modelo->update([
                'activo' => !$modelo->activo,
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
                ->body('Ha ocurrido un error: ' . $e->getMessage())
                ->send();
        }
    }

    public function abrirModalAnos($id)
    {
        $modelo = Modelo::with('anos')->findOrFail($id);
        $this->currentModeloId = $id;
        $this->currentModeloNombre = $modelo->nombre;
        $this->modeloAnosData = $modelo->anos->sortByDesc('ano')->values()->toArray();
        $this->nuevoAno = '';
        $this->errors['nuevoAno'] = false;
        $this->anosModalVisible = true;
    }

    public function cerrarModalAnos()
    {
        $this->anosModalVisible = false;
    }

    public function agregarAno()
    {
        // Resetear error
        $this->errors['nuevoAno'] = false;
        
        // Validar año
        if (empty($this->nuevoAno) || !is_numeric($this->nuevoAno) || strlen($this->nuevoAno) !== 4) {
            $this->errors['nuevoAno'] = true;
            return;
        }
        
        try {
            // Verificar si el año ya existe para este modelo
            $existeAno = ModeloAno::where('modelo_id', $this->currentModeloId)
                ->where('ano', $this->nuevoAno)
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
                'modelo_id' => $this->currentModeloId,
                'ano' => $this->nuevoAno,
                'activo' => true,
            ]);
            
            // Recargar años
            $modelo = Modelo::with('anos')->findOrFail($this->currentModeloId);
            $this->modeloAnosData = $modelo->anos->sortByDesc('ano')->values()->toArray();
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
                ->body('Ha ocurrido un error: ' . $e->getMessage())
                ->send();
        }
    }

    public function toggleEstadoAno($id)
    {
        try {
            $modeloAno = ModeloAno::findOrFail($id);
            $modeloAno->update([
                'activo' => !$modeloAno->activo,
            ]);
            
            // Recargar años
            $modelo = Modelo::with('anos')->findOrFail($this->currentModeloId);
            $this->modeloAnosData = $modelo->anos->sortByDesc('ano')->values()->toArray();
            
            Notification::make()
                ->success()
                ->title('Estado actualizado')
                ->body('El estado del año ha sido actualizado correctamente.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Ha ocurrido un error: ' . $e->getMessage())
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
            $this->modeloAnosData = $modelo->anos->sortByDesc('ano')->values()->toArray();
            
            Notification::make()
                ->success()
                ->title('Año eliminado')
                ->body('El año ha sido eliminado correctamente.')
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Ha ocurrido un error: ' . $e->getMessage())
                ->send();
        }
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Configuración';
    }
}
