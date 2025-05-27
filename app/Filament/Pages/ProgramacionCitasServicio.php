<?php

namespace App\Filament\Pages;

use App\Models\Local;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ProgramacionCitasServicio extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.pages.programacion-citas-servicio';

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Programación Citas Servicio';

    protected static ?string $title = 'Programación citas de servicio';

    protected static ?string $slug = 'programacion-citas-servicio';

    public $selectedLocal = '';

    public $selectedWeek;

    public $timeSlots = [];

    public $minReservationTime = '';

    public $maxReservationTime = '';

    public $minTimeUnit = 'days';

    public $maxTimeUnit = 'days';

    public $blockingPeriods = [];

    public $data = [];

    // Variables para rastrear la celda seleccionada
    public $selectedTime = null;

    public $selectedDate = null;

    public $selectedSlotStatus = null; // 'disponible', 'bloqueado', 'reservado'

    public function mount(): void
    {
        $this->selectedWeek = now()->startOfWeek();

        // Seleccionar el primer local activo por defecto
        if (empty($this->selectedLocal)) {
            $primerLocal = \App\Models\Local::where('activo', true)->orderBy('nombre')->first();
            if ($primerLocal) {
                $this->selectedLocal = $primerLocal->codigo;
            }
        }

        $this->form->fill([
            'selectedLocal' => $this->selectedLocal,
            'minReservationTime' => $this->minReservationTime,
            'maxReservationTime' => $this->maxReservationTime,
            'minTimeUnit' => $this->minTimeUnit,
            'maxTimeUnit' => $this->maxTimeUnit,
        ]);

        $this->generateTimeSlots();
    }

    public function updatedData($value, $name)
    {
        // Si se actualiza el local seleccionado, regenerar los slots de tiempo
        if ($name === 'selectedLocal') {
            $this->selectedLocal = $value;
            $this->resetSelection();
            $this->generateTimeSlots();
        }
    }

    public function nextWeek()
    {
        $this->selectedWeek->addWeek();
        $this->resetSelection();
        $this->generateTimeSlots();
    }

    public function previousWeek()
    {
        $this->selectedWeek->subWeek();
        $this->resetSelection();
        $this->generateTimeSlots();
    }

    /**
     * Resetea la selección actual
     */
    private function resetSelection()
    {
        // Limpiar la celda seleccionada si existe
        if ($this->selectedTime !== null && $this->selectedDate !== null) {
            if (isset($this->timeSlots[$this->selectedTime][$this->selectedDate])) {
                $this->timeSlots[$this->selectedTime][$this->selectedDate]['seleccionado'] = false;
            }
        }

        $this->selectedTime = null;
        $this->selectedDate = null;
        $this->selectedSlotStatus = null;
    }

    private function generateTimeSlots()
    {
        $this->timeSlots = [
            '8:00 AM' => [],
            '8:30 AM' => [],
            '9:00 AM' => [],
            '9:30 AM' => [],
            '10:00 AM' => [],
            '10:30 AM' => [],
            '11:00 AM' => [],
            '11:30 AM' => [],
            '12:00 PM' => [],
            '12:30 PM' => [],
            '1:00 PM' => [],
            '1:30 PM' => [],
            '2:00 PM' => [],
            '2:30 PM' => [],
            '3:00 PM' => [],
            '3:30 PM' => [],
            '4:00 PM' => [],
            '4:30 PM' => [],
            '5:00 PM' => [],
            '5:30 PM' => [],
            '6:00 PM' => [],
            '6:30 PM' => [],
        ];

        // Obtener los días de la semana
        $weekDays = $this->getWeekDays();

        // Cargar los bloqueos para esta semana
        foreach ($weekDays as $day) {
            $fecha = $day['date'];

            foreach ($this->timeSlots as $time => $slots) {
                // Convertir el formato de hora para comparar con la base de datos
                $hora = $this->convertirFormatoHora($time);

                // Verificar si hay un bloqueo para este slot
                $localSeleccionado = $this->data['selectedLocal'] ?? $this->selectedLocal;
                $bloqueado = \App\Models\Bloqueo::estaBloquedo($localSeleccionado, $fecha, $hora);

                // Marcar el slot como bloqueado si corresponde
                $this->timeSlots[$time][$fecha->format('Y-m-d')] = [
                    'bloqueado' => $bloqueado,
                    'reservado' => false, // Aquí se podría verificar si hay una reserva
                    'seleccionado' => false,
                ];
            }
        }
    }

    /**
     * Convierte el formato de hora de "8:00 AM" a "08:00"
     */
    private function convertirFormatoHora($hora)
    {
        // Extraer la hora y el periodo (AM/PM)
        preg_match('/(\d+):(\d+)\s(AM|PM)/', $hora, $matches);

        if (count($matches) < 4) {
            return '00:00';
        }

        $hora = (int) $matches[1];
        $minutos = $matches[2];
        $periodo = $matches[3];

        // Convertir a formato 24 horas
        if ($periodo === 'PM' && $hora < 12) {
            $hora += 12;
        } elseif ($periodo === 'AM' && $hora === 12) {
            $hora = 0;
        }

        // Formatear la hora con ceros a la izquierda
        return sprintf('%02d:%s', $hora, $minutos);
    }

    /**
     * Determina si un slot de tiempo está dentro de un rango de bloqueo
     */
    private function slotDentroDeBloqueo($horaSlot, $horaInicio, $horaFin)
    {
        // Convertir todas las horas a minutos desde medianoche para facilitar la comparación
        $slotMinutos = $this->horaAMinutos($horaSlot);
        $inicioMinutos = $this->horaAMinutos($horaInicio);
        $finMinutos = $this->horaAMinutos($horaFin);

        return $slotMinutos >= $inicioMinutos && $slotMinutos < $finMinutos;
    }

    /**
     * Convierte una hora en formato "HH:MM" a minutos desde medianoche
     */
    private function horaAMinutos($hora)
    {
        [$horas, $minutos] = explode(':', $hora);

        return ((int) $horas * 60) + (int) $minutos;
    }

    public function saveSettings()
    {
        $data = $this->form->getState();

        // Save the settings
        $this->minReservationTime = $data['minReservationTime'];
        $this->maxReservationTime = $data['maxReservationTime'];
        $this->minTimeUnit = $data['minTimeUnit'];
        $this->maxTimeUnit = $data['maxTimeUnit'];

        // Add notification
        Notification::make()
            ->success()
            ->title('Configuración guardada')
            ->send();
    }

    public function programBlock()
    {
        // Redirigir a la página de programación de bloqueo con el local seleccionado
        $localSeleccionado = $this->data['selectedLocal'] ?? $this->selectedLocal;

        return redirect()->route('filament.admin.pages.programar-bloqueo', ['local' => $localSeleccionado]);
    }

    /**
     * Selecciona un slot de tiempo (solo uno a la vez)
     */
    public function toggleSlot($time, $date, $status = null)
    {
        // Si el slot no existe, inicializarlo
        if (! isset($this->timeSlots[$time][$date])) {
            $this->timeSlots[$time][$date] = [
                'bloqueado' => false,
                'reservado' => false,
                'seleccionado' => false,
            ];
        }

        // Obtener el estado del slot (bloqueado, reservado o disponible)
        $slotStatus = 'disponible';
        if ($status !== null) {
            $slotStatus = $status;
        } elseif ($this->timeSlots[$time][$date]['bloqueado']) {
            $slotStatus = 'bloqueado';
        } elseif ($this->timeSlots[$time][$date]['reservado']) {
            $slotStatus = 'reservado';
        }

        // Verificar si estamos seleccionando la misma celda que ya está seleccionada
        if ($this->selectedTime === $time && $this->selectedDate === $date) {
            // Deseleccionar la celda actual
            $this->timeSlots[$time][$date]['seleccionado'] = false;
            $this->selectedTime = null;
            $this->selectedDate = null;
            $this->selectedSlotStatus = null;
        } else {
            // Deseleccionar la celda anterior si existe
            if ($this->selectedTime !== null && $this->selectedDate !== null) {
                if (isset($this->timeSlots[$this->selectedTime][$this->selectedDate])) {
                    $this->timeSlots[$this->selectedTime][$this->selectedDate]['seleccionado'] = false;
                }
            }

            // Seleccionar la nueva celda
            $this->timeSlots[$time][$date]['seleccionado'] = true;
            $this->selectedTime = $time;
            $this->selectedDate = $date;
            $this->selectedSlotStatus = $slotStatus;
        }
    }

    protected function getViewData(): array
    {
        return [
            'weekDays' => $this->getWeekDays(),
            'timeSlots' => $this->timeSlots,
            'selectedSlotStatus' => $this->selectedSlotStatus,
        ];
    }

    private function getWeekDays()
    {
        $days = [];
        $currentDay = $this->selectedWeek->copy();

        for ($i = 0; $i < 6; $i++) {
            // Saltamos el domingo (0 = domingo, 6 = sábado)
            if ($currentDay->dayOfWeek === 0) {
                $currentDay->addDay();
            }

            $days[] = [
                'date' => $currentDay->copy(),
                'dayName' => $this->getDayName($currentDay->dayOfWeek),
                'dayNumber' => $currentDay->format('d'),
                'fullDate' => $currentDay->format('d/m'),
            ];
            $currentDay->addDay();
        }

        return $days;
    }

    private function getDayName($dayOfWeek)
    {
        $days = [
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            0 => 'Domingo',
        ];

        return $days[$dayOfWeek];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Gestión de Citas';
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selectedLocal')
                    ->options(function () {
                        return Local::getActivosParaSelector();
                    })
                    ->placeholder('Seleccione un local')
                    ->live()
                    ->required(),
                TextInput::make('minReservationTime')
                    ->numeric()
                    ->minValue(0)
                    ->label('Tiempo mínimo'),
                Select::make('minTimeUnit')
                    ->options([
                        'days' => 'Días',
                        'hours' => 'Horas',
                        'minutes' => 'Minutos',
                    ])
                    ->label('Unidad de tiempo mínimo'),
                TextInput::make('maxReservationTime')
                    ->numeric()
                    ->minValue(0)
                    ->label('Tiempo máximo'),
                Select::make('maxTimeUnit')
                    ->options([
                        'days' => 'Días',
                        'hours' => 'Horas',
                        'minutes' => 'Minutos',
                    ])
                    ->label('Unidad de tiempo máximo'),
            ])
            ->statePath('data');
    }
}
