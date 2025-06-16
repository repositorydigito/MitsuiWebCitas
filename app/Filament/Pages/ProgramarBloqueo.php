<?php

namespace App\Filament\Pages;

use App\Models\Local;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ProgramarBloqueo extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected static string $view = 'filament.pages.programar-bloqueo';

    protected static ?string $navigationIcon = 'heroicon-o-lock-closed';

    protected static ?string $navigationLabel = 'Programar Bloqueo';

    protected static ?string $navigationGroup = '📅 Citas & Servicios';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Programar bloqueo';

    protected static ?string $slug = 'programar-bloqueo';

    // Ocultar de la navegación principal ya que se accederá desde la página de programación de citas
    protected static bool $shouldRegisterNavigation = false;

    public $currentStep = 1;

    public $totalSteps = 3;

    // Datos del formulario
    public $premises;

    public $start_date;

    public $end_date;

    public $start_time;

    public $end_time;

    public $all_day = false;

    public $comments;

    public $data = [];

    // Errores de validación
    public $errors = [
        'premises' => false,
        'start_date' => false,
        'end_date' => false,
        'start_time' => false,
        'end_time' => false,
    ];

    public function mount($premises = null): void
    {
        // Si se recibe un local, preseleccionarlo
        if ($premises) {
            $this->data['premises'] = $premises;
        }

        $this->form->fill($this->data);
    }

    public function nextStep()
    {
        // Resetear errores
        $this->errors = [
            'premises' => false,
            'start_date' => false,
            'end_date' => false,
            'start_time' => false,
            'end_time' => false,
        ];

        // Validar campos requeridos
        $hasErrors = false;

        if (empty($this->data['premises'])) {
            $this->errors['premises'] = true;
            $hasErrors = true;
        }

        if (empty($this->data['start_date'])) {
            $this->errors['start_date'] = true;
            $hasErrors = true;
        }

        if (empty($this->data['end_date'])) {
            $this->errors['end_date'] = true;
            $hasErrors = true;
        }

        // Solo validar horas si no está marcado "Todo el día"
        if (empty($this->data['all_day']) || ! $this->data['all_day']) {
            if (empty($this->data['start_time'])) {
                $this->errors['start_time'] = true;
                $hasErrors = true;
            }

            if (empty($this->data['end_time'])) {
                $this->errors['end_time'] = true;
                $hasErrors = true;
            }
        }

        // Si hay errores, no avanzar al siguiente paso
        if ($hasErrors) {
            return;
        }

        // Si está marcado "Todo el día", asegurarse de que los horarios sean los correctos
        if (! empty($this->data['all_day']) && $this->data['all_day']) {
            $this->data['start_time'] = '08:00'; // 8:00 AM
            $this->data['end_time'] = '18:00';    // 6:00 PM
        }

        // Guardar los datos del formulario
        $this->premises = $this->data['premises'];
        $this->start_date = $this->data['start_date'];
        $this->end_date = $this->data['end_date'];
        $this->start_time = $this->data['start_time'];
        $this->end_time = $this->data['end_time'];
        $this->all_day = ! empty($this->data['all_day']) ? $this->data['all_day'] : false;
        $this->comments = ! empty($this->data['comments']) ? $this->data['comments'] : '';

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
            'premises' => $this->premises,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'start_time' => $this->all_day ? '00:00' : $this->start_time,
            'end_time' => $this->all_day ? '12:00' : $this->end_time,
            'all_day' => $this->all_day,
            'comments' => $this->comments,
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
        // Redirigir a la página de programación de citas con parámetro para forzar recarga
        $this->redirect(route('filament.admin.pages.programacion-citas-servicio', ['refresh' => time()]));
    }

    public function updatedData($value, $name)
    {
        // Si se actualiza el checkbox de "Todo el día"
        if ($name === 'all_day') {
            if ($this->data['all_day']) {
                // Si se marca "Todo el día", establecer horarios predeterminados
                $this->data['start_time'] = '00:00'; // 12:00 AM
                $this->data['end_time'] = '12:00';    // 12:00 PM
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
                Select::make('premises')
                    ->options(function () {
                        return Local::getActivosParaSelector();
                    })
                    ->placeholder('Elegir local')
                    ->required(),

                DatePicker::make('start_date')
                    ->label('Fecha de inicio')
                    ->placeholder('Elige la fecha de inicio')
                    ->format('Y-m-d')
                    ->displayFormat('d/m/Y')
                    ->required(),

                DatePicker::make('end_date')
                    ->label('Fecha de fin')
                    ->placeholder('Elige la fecha de fin')
                    ->format('Y-m-d')
                    ->displayFormat('d/m/Y')
                    ->required(),

                Select::make('start_time')
                    ->label('Hora de inicio')
                    ->placeholder('Elige la hora de inicio')
                    ->options($this->getTimeOptions())
                    ->required()
                    ->disabled(fn (callable $get) => $get('all_day')),

                Select::make('end_time')
                    ->label('Hora de fin')
                    ->placeholder('Elige la hora de fin')
                    ->options($this->getTimeOptions())
                    ->required()
                    ->disabled(fn (callable $get) => $get('all_day')),

                Checkbox::make('all_day')
                    ->label('Todo el día')
                    ->reactive(),

                Textarea::make('comments')
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

    /**
     * Genera las opciones de tiempo cada 15 minutos
     */
    private function getTimeOptions(): array
    {
        $options = [];

        // Generar horarios desde las 8:00 AM hasta las 6:45 PM cada 15 minutos
        for ($hour = 8; $hour <= 18; $hour++) {
            for ($minute = 0; $minute < 60; $minute += 15) {
                // No agregar 7:00 PM (19:00) en adelante
                if ($hour == 18 && $minute > 45) {
                    break;
                }

                $time24 = sprintf('%02d:%02d', $hour, $minute);

                // Convertir a formato 12 horas para mostrar
                $hour12 = $hour;
                $ampm = 'AM';

                if ($hour >= 12) {
                    $ampm = 'PM';
                    if ($hour > 12) {
                        $hour12 = $hour - 12;
                    }
                }

                $timeDisplay = sprintf('%d:%02d %s', $hour12, $minute, $ampm);
                $options[$time24] = $timeDisplay;
            }
        }

        return $options;
    }
}
