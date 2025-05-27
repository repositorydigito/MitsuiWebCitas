<?php

namespace App\Filament\Pages;

use App\Models\Local;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AdministrarLocales extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.administrar-locales';

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Administrar Locales';

    protected static ?string $title = 'Administrar Locales';

    protected static ?string $slug = 'administrar-locales';

    // Variables para el formulario
    public $locales = [];

    public $modalVisible = false;

    public $editMode = false;

    public $currentLocalId = null;

    public $formData = [
        'codigo' => '',
        'nombre' => '',
        'direccion' => '',
        'telefono' => '',
        'opening_time' => '08:00',
        'closing_time' => '18:00',
        'activo' => true,
    ];

    // Errores de validación
    public $errors = [
        'codigo' => false,
        'nombre' => false,
    ];

    public function mount(): void
    {
        $this->cargarLocales();
    }

    public function cargarLocales()
    {
        $this->locales = Local::orderBy('nombre')->get();
    }

    public function abrirModal($id = null)
    {
        $this->resetearFormulario();

        if ($id) {
            $local = Local::findOrFail($id);
            $this->formData = [
                'codigo' => $local->codigo,
                'nombre' => $local->nombre,
                'direccion' => $local->direccion,
                'telefono' => $local->telefono,
                'opening_time' => $local->opening_time,
                'closing_time' => $local->closing_time,
                'activo' => $local->activo,
            ];
            $this->editMode = true;
            $this->currentLocalId = $id;
        } else {
            $this->editMode = false;
            $this->currentLocalId = null;
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
            'direccion' => '',
            'telefono' => '',
            'opening_time' => '08:00',
            'closing_time' => '18:00',
            'activo' => true,
        ];

        $this->errors = [
            'codigo' => false,
            'nombre' => false,
        ];
    }

    public function guardarLocal()
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
        if (! $this->editMode) {
            $existeCodigo = Local::where('codigo', $this->formData['codigo'])->exists();
            if ($existeCodigo) {
                $this->errors['codigo'] = true;
                Notification::make()
                    ->danger()
                    ->title('El código ya existe')
                    ->body('Por favor, elige otro código para el local.')
                    ->send();

                return;
            }
        }

        try {
            if ($this->editMode) {
                // Actualizar local existente
                $local = Local::findOrFail($this->currentLocalId);
                $local->update([
                    'nombre' => $this->formData['nombre'],
                    'direccion' => $this->formData['direccion'],
                    'telefono' => $this->formData['telefono'],
                    'opening_time' => $this->formData['opening_time'],
                    'closing_time' => $this->formData['closing_time'],
                    'activo' => $this->formData['activo'],
                ]);

                Notification::make()
                    ->success()
                    ->title('Local actualizado')
                    ->body('El local ha sido actualizado correctamente.')
                    ->send();
            } else {
                // Crear nuevo local
                Local::create([
                    'codigo' => $this->formData['codigo'],
                    'nombre' => $this->formData['nombre'],
                    'direccion' => $this->formData['direccion'],
                    'telefono' => $this->formData['telefono'],
                    'opening_time' => $this->formData['opening_time'],
                    'closing_time' => $this->formData['closing_time'],
                    'activo' => $this->formData['activo'],
                ]);

                Notification::make()
                    ->success()
                    ->title('Local creado')
                    ->body('El local ha sido creado correctamente.')
                    ->send();
            }

            // Recargar locales y cerrar modal
            $this->cargarLocales();
            $this->cerrarModal();
        } catch (\Exception $e) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->send();
        }
    }

    public function eliminarLocal($id)
    {
        try {
            $local = Local::findOrFail($id);
            $local->delete();

            Notification::make()
                ->success()
                ->title('Local eliminado')
                ->body('El local ha sido eliminado correctamente.')
                ->send();

            $this->cargarLocales();
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
            $local = Local::findOrFail($id);
            $local->update([
                'activo' => ! $local->activo,
            ]);

            Notification::make()
                ->success()
                ->title('Estado actualizado')
                ->body('El estado del local ha sido actualizado correctamente.')
                ->send();

            $this->cargarLocales();
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
