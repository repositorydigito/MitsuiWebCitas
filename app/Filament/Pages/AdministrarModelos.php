<?php

namespace App\Filament\Pages;

use App\Models\Modelo;
use App\Models\ModeloAno;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

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
        'code' => '',
        'name' => '',
        'brand' => 'TOYOTA',
        'description' => '',
        'is_active' => true,
    ];

    // Variables para el formulario de años
    public $anosModalVisible = false;

    public $currentModeloNombre = '';

    public $modeloAnosData = [];

    public $nuevoAno = '';

    // Errores de validación
    public $errors = [
        'code' => false,
        'name' => false,
        'nuevoAno' => false,
    ];

    public function mount(): void
    {
        $this->cargarModelos();
    }

    public function cargarModelos()
    {
        $this->modelos = Modelo::with('anos')->orderBy('name')->get();
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
            'code' => '',
            'name' => '',
            'brand' => 'TOYOTA',
            'description' => '',
            'is_active' => true,
        ];

        $this->errors = [
            'code' => false,
            'name' => false,
            'nuevoAno' => false,
        ];
    }

    public function guardarModelo()
    {
        // Resetear errores
        $this->errors = [
            'code' => false,
            'name' => false,
        ];

        // Validar campos requeridos
        $hasErrors = false;

        if (empty($this->formData['code'])) {
            $this->errors['code'] = true;
            $hasErrors = true;
        }

        if (empty($this->formData['name'])) {
            $this->errors['name'] = true;
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
                $this->errors['code'] = true;
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
                    'name' => $this->formData['name'],
                    'brand' => $this->formData['brand'],
                    'description' => $this->formData['description'],
                    'is_active' => $this->formData['is_active'],
                ]);

                Notification::make()
                    ->success()
                    ->title('Modelo actualizado')
                    ->body('El modelo ha sido actualizado correctamente.')
                    ->send();
            } else {
                // Crear nuevo modelo
                $modelo = Modelo::create([
                    'code' => $this->formData['code'],
                    'name' => $this->formData['name'],
                    'brand' => $this->formData['brand'],
                    'description' => $this->formData['description'],
                    'is_active' => $this->formData['is_active'],
                ]);

                // Crear años por defecto para el nuevo modelo
                $anos = ['2018', '2019', '2020', '2021', '2022', '2023', '2024'];
                foreach ($anos as $ano) {
                    ModeloAno::create([
                        'model_id' => $modelo->id,
                        'year' => $ano,
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
        if (empty($this->nuevoAno) || ! is_numeric($this->nuevoAno) || strlen($this->nuevoAno) !== 4) {
            $this->errors['nuevoAno'] = true;

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
                'activo' => true,
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
                'activo' => ! $modeloAno->activo,
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

    public static function getNavigationGroup(): ?string
    {
        return 'Configuración';
    }
}
