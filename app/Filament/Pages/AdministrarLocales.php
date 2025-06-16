<?php

namespace App\Filament\Pages;

use App\Models\Local;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AdministrarLocales extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected static string $view = 'filament.pages.administrar-locales';

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Administrar Locales';

    protected static ?string $navigationGroup = '⚙️ Configuración';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Administrar Locales';

    protected static ?string $slug = 'administrar-locales';

    // Variables para el formulario
    public $locales = [];

    public $modalVisible = false;

    public $editMode = false;

    public $currentLocalId = null;

    public $formData = [
        'code' => '',
        'name' => '',
        'brand' => '',
        'address' => '',
        'phone' => '',
        'opening_time' => '08:00',
        'closing_time' => '18:00',
        'is_active' => true,
        'waze_url' => '',
        'maps_url' => '',
    ];

    // Errores de validación
    public $errors = [
        'code' => false,
        'name' => false,
        'brand' => false,
    ];

    public function mount(): void
    {
        $this->cargarLocales();
    }

    public function cargarLocales()
    {
        $this->locales = Local::orderBy('name')->get();
    }

    public function abrirModal($id = null)
    {
        $this->resetearFormulario();

        if ($id) {
            $local = Local::findOrFail($id);
            $this->formData = [
                'code' => $local->code,
                'name' => $local->name,
                'brand' => $local->brand ?? '',
                'address' => $local->address,
                'phone' => $local->phone,
                'opening_time' => $local->opening_time,
                'closing_time' => $local->closing_time,
                'is_active' => $local->is_active,
                'waze_url' => $local->waze_url ?? '',
                'maps_url' => $local->maps_url ?? '',
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
            'code' => '',
            'name' => '',
            'brand' => '',
            'address' => '',
            'phone' => '',
            'opening_time' => '08:00',
            'closing_time' => '18:00',
            'is_active' => true,
            'waze_url' => '',
            'maps_url' => '',
        ];

        $this->errors = [
            'code' => false,
            'name' => false,
            'brand' => false,
        ];
    }

    public function guardarLocal()
    {
        // Resetear errores
        $this->errors = [
            'code' => false,
            'name' => false,
            'brand' => false,
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

        if (empty($this->formData['brand'])) {
            $this->errors['brand'] = true;
            $hasErrors = true;
        }

        // Validar que la marca sea válida
        if (! empty($this->formData['brand']) && ! in_array($this->formData['brand'], ['Toyota', 'Lexus', 'Hino'])) {
            $this->errors['brand'] = true;
            $hasErrors = true;
        }

        // Si hay errores, no continuar
        if ($hasErrors) {
            return;
        }

        // Validar que el código sea único
        if (! $this->editMode) {
            $existeCodigo = Local::where('code', $this->formData['code'])->exists();
            if ($existeCodigo) {
                $this->errors['code'] = true;
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
                    'name' => $this->formData['name'],
                    'brand' => $this->formData['brand'],
                    'address' => $this->formData['address'],
                    'phone' => $this->formData['phone'],
                    'opening_time' => $this->formData['opening_time'],
                    'closing_time' => $this->formData['closing_time'],
                    'is_active' => $this->formData['is_active'],
                    'waze_url' => $this->formData['waze_url'],
                    'maps_url' => $this->formData['maps_url'],
                ]);

                Notification::make()
                    ->success()
                    ->title('Local actualizado')
                    ->body('El local ha sido actualizado correctamente.')
                    ->send();
            } else {
                // Crear nuevo local
                Local::create([
                    'code' => $this->formData['code'],
                    'name' => $this->formData['name'],
                    'brand' => $this->formData['brand'],
                    'address' => $this->formData['address'],
                    'phone' => $this->formData['phone'],
                    'opening_time' => $this->formData['opening_time'],
                    'closing_time' => $this->formData['closing_time'],
                    'is_active' => $this->formData['is_active'],
                    'waze_url' => $this->formData['waze_url'],
                    'maps_url' => $this->formData['maps_url'],
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
                'is_active' => ! $local->is_active,
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
