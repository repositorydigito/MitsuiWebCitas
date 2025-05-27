<?php

namespace App\Filament\Pages;

use App\Models\Local;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ProgramarBloqueo extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.programar-bloqueo';

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Programar Bloqueo';

    protected static ?string $title = 'Programar bloqueo';

    protected static ?string $slug = 'programar-bloqueo';

    // Ocultar de la navegación principal ya que se accederá desde la página de programación de citas
    protected static bool $shouldRegisterNavigation = false;

    public $currentStep = 1;

    public $totalSteps = 3;

    // Datos del formulario
    public $local;

    public $fechaInicio;

    public $fechaFin;

    public $horaInicio;

    public $horaFin;

    public $todoDia = false;

    public $comentarios;

    public $data = [];

    // Errores de validación
    public $errors = [
        'local' => false,
        'fechaInicio' => false,
        'fechaFin' => false,
        'horaInicio' => false,
        'horaFin' => false,
    ];

    public function mount($local = null): void
    {
        // Si se recibe un local, preseleccionarlo
        if ($local) {
            $this->data['local'] = $local;
        }

        $this->form->fill($this->data);
    }

    public function nextStep()
    {
        // Resetear errores
        $this->errors = [
            'local' => false,
            'fechaInicio' => false,
            'fechaFin' => false,
            'horaInicio' => false,
            'horaFin' => false,
        ];

        // Validar campos requeridos
        $hasErrors = false;

        if (empty($this->data['local'])) {
            $this->errors['local'] = true;
            $hasErrors = true;
        }

        if (empty($this->data['fechaInicio'])) {
            $this->errors['fechaInicio'] = true;
            $hasErrors = true;
        }

        if (empty($this->data['fechaFin'])) {
            $this->errors['fechaFin'] = true;
            $hasErrors = true;
        }

        // Solo validar horas si no está marcado "Todo el día"
        if (empty($this->data['todoDia']) || ! $this->data['todoDia']) {
            if (empty($this->data['horaInicio'])) {
                $this->errors['horaInicio'] = true;
                $hasErrors = true;
            }

            if (empty($this->data['horaFin'])) {
                $this->errors['horaFin'] = true;
                $hasErrors = true;
            }
        }

        // Si hay errores, no avanzar al siguiente paso
        if ($hasErrors) {
            return;
        }

        // Si está marcado "Todo el día", asegurarse de que los horarios sean los correctos
        if (! empty($this->data['todoDia']) && $this->data['todoDia']) {
            $this->data['horaInicio'] = '08:00'; // 8:00 AM
            $this->data['horaFin'] = '18:00';    // 6:00 PM
        }

        // Guardar los datos del formulario
        $this->local = $this->data['local'];
        $this->fechaInicio = $this->data['fechaInicio'];
        $this->fechaFin = $this->data['fechaFin'];
        $this->horaInicio = $this->data['horaInicio'];
        $this->horaFin = $this->data['horaFin'];
        $this->todoDia = ! empty($this->data['todoDia']) ? $this->data['todoDia'] : false;
        $this->comentarios = ! empty($this->data['comentarios']) ? $this->data['comentarios'] : '';

        // Avanzar al siguiente paso
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        // Retroceder al paso anterior
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function confirmarBloqueo()
    {
        // Guardar el bloqueo en la base de datos
        \App\Models\Bloqueo::create([
            'local' => $this->local,
            'start_date' => $this->fechaInicio,
            'end_date' => $this->fechaFin,
            'start_time' => $this->todoDia ? '00:00' : $this->horaInicio,
            'end_time' => $this->todoDia ? '12:00' : $this->horaFin,
            'all_day' => $this->todoDia,
            'comentarios' => $this->comentarios,
        ]);

        // Avanzar al paso de confirmación
        $this->currentStep = 3;

        // Mostrar notificación de éxito
        Notification::make()
            ->success()
            ->title('Bloqueo programado correctamente')
            ->send();
    }

    public function cerrarYVolver()
    {
        // Redirigir a la página de programación de citas
        return redirect()->route('filament.admin.pages.programacion-citas-servicio');
    }

    public function updatedData($value, $name)
    {
        // Si se actualiza el checkbox de "Todo el día"
        if ($name === 'todoDia') {
            if ($this->data['todoDia']) {
                // Si se marca "Todo el día", establecer horarios predeterminados
                $this->data['horaInicio'] = '00:00'; // 12:00 AM
                $this->data['horaFin'] = '12:00';    // 12:00 PM
            }
            // Forzar una actualización de la vista
            $this->dispatch('refresh');
        }
    }

    // Método para refrescar la vista cuando se hace clic en el checkbox
    public function refresh()
    {
        // Este método está vacío, pero es necesario para que funcione el wire:click="$refresh"
    }

    public function debug()
    {
        // Método para depurar
        dd([
            'data' => $this->data,
            'errors' => $this->errors,
            'currentStep' => $this->currentStep,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('local')
                    ->options(function () {
                        return Local::getActivosParaSelector();
                    })
                    ->placeholder('Elegir local')
                    ->required(),

                DatePicker::make('fechaInicio')
                    ->label('Fecha de inicio')
                    ->placeholder('Elige la fecha de inicio')
                    ->format('Y-m-d')
                    ->displayFormat('d/m/Y')
                    ->required(),

                DatePicker::make('fechaFin')
                    ->label('Fecha de fin')
                    ->placeholder('Elige la fecha de fin')
                    ->format('Y-m-d')
                    ->displayFormat('d/m/Y')
                    ->required(),

                TimePicker::make('horaInicio')
                    ->label('Hora de inicio')
                    ->placeholder('Elige la hora de inicio')
                    ->seconds(false)
                    ->required()
                    ->disabled(fn (callable $get) => $get('todoDia')),

                TimePicker::make('horaFin')
                    ->label('Hora de fin')
                    ->placeholder('Elige la hora de fin')
                    ->seconds(false)
                    ->required()
                    ->disabled(fn (callable $get) => $get('todoDia')),

                Checkbox::make('todoDia')
                    ->label('Todo el día')
                    ->reactive(),

                Textarea::make('comentarios')
                    ->label('Comentarios o observaciones')
                    ->placeholder('Comentarios o observaciones')
                    ->rows(4),
            ])
            ->statePath('data');
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Gestión de Citas';
    }
}
