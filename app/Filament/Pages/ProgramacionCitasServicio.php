<?php

namespace App\Filament\Pages;

use App\Models\Local;
use App\Models\Appointment;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class ProgramacionCitasServicio extends Page implements HasForms
{
    use HasPageShield, InteractsWithForms;

    protected static string $view = 'filament.pages.programacion-citas-servicio';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Programación Citas';

    protected static ?string $navigationGroup = '📅 Citas & Servicios';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Programación citas de servicio';

    protected static ?string $slug = 'programacion-citas-servicio';

    public $selectedLocal = '';

    public $selectedWeek;

    public $timeSlots = [];

    public $minReservationTime = null;

    public $maxReservationTime = null;

    public $minTimeUnit = null;

    public $maxTimeUnit = null;

    public $blockingPeriods = [];

    public $data = [];

    // Variables para rastrear la celda seleccionada
    public $selectedTime = null;

    public $selectedDate = null;

    public $selectedSlotStatus = null; // 'disponible', 'bloqueado', 'reservado'

    public function mount(): void
    {
        $this->selectedWeek = now()->startOfWeek();

        // Inicializar la propiedad data si no existe
        if (empty($this->data) || !is_array($this->data)) {
            $this->data = [];
        }

        // Si hay un local en la URL, usarlo
        if (request()->has('local')) {
            $this->data['selectedLocal'] = request()->query('local');
            $this->selectedLocal = $this->data['selectedLocal'];
        }

        // Cargar la configuración del local si ya hay uno seleccionado
        if (!empty($this->selectedLocal)) {
            $this->loadIntervalForSelectedLocal();
        } else {
            // Valores por defecto cuando no hay local seleccionado
            $this->minReservationTime = 1;
            $this->maxReservationTime = 30;
            $this->minTimeUnit = 'days';
            $this->maxTimeUnit = 'days';
        }

        $this->form->fill([
            'selectedLocal' => $this->selectedLocal,
            'minReservationTime' => $this->minReservationTime,
            'maxReservationTime' => $this->maxReservationTime,
            'minTimeUnit' => $this->minTimeUnit,
            'maxTimeUnit' => $this->maxTimeUnit,
        ]);

        // Solo generar los slots de tiempo si hay un local seleccionado
        if (!empty($this->selectedLocal)) {
            $this->generateTimeSlots();
        }
    }

    public function updatedData($value, $name)
    {
        // Si se actualiza el local seleccionado, cargar la configuración del local y regenerar los slots de tiempo
        if ($name === 'selectedLocal') {
            $this->selectedLocal = $value;
            $this->data['selectedLocal'] = $value; // Asegurarse de que data.selectedLocal se actualice
            $this->loadIntervalForSelectedLocal();
            $this->form->fill([
                'selectedLocal' => $this->selectedLocal,
                'minReservationTime' => $this->minReservationTime,
                'maxReservationTime' => $this->maxReservationTime,
                'minTimeUnit' => $this->minTimeUnit,
                'maxTimeUnit' => $this->maxTimeUnit,
            ]);
            $this->resetSelection();
            if (!empty($this->selectedLocal)) {
                $this->generateTimeSlots();
            }
        }
    }
    
    /**
     * Carga la configuración de intervalo para el local seleccionado
     */
    private function loadIntervalForSelectedLocal(): void
    {
        if (empty($this->selectedLocal)) {
            return;
        }
        
        $local = \App\Models\Local::where('code', $this->selectedLocal)->first();
        
        if ($local && $local->interval) {
            // Si existe la configuración para este local, cargarla
            $this->minReservationTime = $local->interval->min_reservation_time;
            $this->maxReservationTime = $local->interval->max_reservation_time;
            $this->minTimeUnit = $local->interval->min_time_unit;
            $this->maxTimeUnit = $local->interval->max_time_unit;
        } else {
            // Valores por defecto si no hay configuración para este local
            $this->minReservationTime = 1;
            $this->maxReservationTime = 30;
            $this->minTimeUnit = 'days';
            $this->maxTimeUnit = 'days';
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

    private function getPremiseIdFromCode($code)
    {
        // Ajusta el modelo si tu tabla de locales se llama diferente
        $local = \App\Models\Local::where('code', $code)->first();
        return $local ? $local->id : null;
    }

    private function generateTimeSlots()
    {
        $this->timeSlots = [
            '8:00 AM' => [],
            '8:15 AM' => [],
            '8:30 AM' => [],
            '8:45 AM' => [],
            '9:00 AM' => [],
            '9:15 AM' => [],
            '9:30 AM' => [],
            '9:45 AM' => [],
            '10:00 AM' => [],
            '10:15 AM' => [],
            '10:30 AM' => [],
            '10:45 AM' => [],
            '11:00 AM' => [],
            '11:15 AM' => [],
            '11:30 AM' => [],
            '11:45 AM' => [],
            '12:00 PM' => [],
            '12:15 PM' => [],
            '12:30 PM' => [],
            '12:45 PM' => [],
            '1:00 PM' => [],
            '1:15 PM' => [],
            '1:30 PM' => [],
            '1:45 PM' => [],
            '2:00 PM' => [],
            '2:15 PM' => [],
            '2:30 PM' => [],
            '2:45 PM' => [],
            '3:00 PM' => [],
            '3:15 PM' => [],
            '3:30 PM' => [],
            '3:45 PM' => [],
            '4:00 PM' => [],
            '4:15 PM' => [],
            '4:30 PM' => [],
            '4:45 PM' => [],
            '5:00 PM' => [],
            '5:15 PM' => [],
            '5:30 PM' => [],
            '5:45 PM' => [],
            '6:00 PM' => [],
            '6:15 PM' => [],
            '6:30 PM' => [],
            '6:45 PM' => [],
        ];

        // Obtener los días de la semana
        $weekDays = $this->getWeekDays();

        // Obtener el ID numérico del local seleccionado
        $localSeleccionadoCode = $this->data['selectedLocal'] ?? $this->selectedLocal;
        $premiseId = $this->getPremiseIdFromCode($localSeleccionadoCode);
        $startDate = $weekDays[0]['date']->copy()->startOfDay();
        $endDate = end($weekDays)['date']->copy()->endOfDay();
        $appointments = Appointment::where('premise_id', $premiseId)
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->get();

        // Cargar los bloqueos para esta semana
        foreach ($weekDays as $day) {
            $fecha = $day['date'];

            foreach ($this->timeSlots as $time => $slots) {
                // Convertir el formato de hora para comparar con la base de datos
                $hora = $this->convertirFormatoHora($time);

                // Verificar si hay un bloqueo para este slot
                $bloqueado = \App\Models\Bloqueo::estaBloquedo($localSeleccionadoCode, $fecha, $hora);

                // Buscar si hay una cita agendada para este slot
                $cita = $appointments->first(function($cita) use ($fecha, $hora) {
                    // appointment_date puede ser Carbon o string
                    $fechaCita = $cita->appointment_date instanceof \Carbon\Carbon ? $cita->appointment_date->format('Y-m-d') : (is_string($cita->appointment_date) ? date('Y-m-d', strtotime($cita->appointment_date)) : null);
                    // appointment_time puede ser Carbon, string o null
                    if ($cita->appointment_time instanceof \Carbon\Carbon) {
                        $horaCita = $cita->appointment_time->format('H:i');
                    } elseif (is_string($cita->appointment_time)) {
                        // Puede venir como '08:00:00', '8:00', etc.
                        $horaCita = substr($cita->appointment_time, 0, 5); // '08:00'
                    } else {
                        $horaCita = null;
                    }
                    return $fechaCita === $fecha->format('Y-m-d') && $horaCita === $hora;
                });

                $reservado = $cita ? true : false;

                $this->timeSlots[$time][$fecha->format('Y-m-d')] = [
                    'bloqueado' => $bloqueado,
                    'reservado' => $reservado,
                    'seleccionado' => false,
                    'cita' => $cita, // Para mostrar info adicional si se requiere
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

        // Validar que se haya seleccionado un local
        if (empty($this->selectedLocal)) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('Debe seleccionar un local antes de guardar la configuración.')
                ->send();
            return;
        }

        // Obtener el local seleccionado
        $local = \App\Models\Local::where('code', $this->selectedLocal)->first();
        
        if (!$local) {
            Notification::make()
                ->danger()
                ->title('Error')
                ->body('No se encontró el local seleccionado.')
                ->send();
            return;
        }

        // Actualizar o crear la configuración para este local
        $interval = $local->interval ?? new \App\Models\Interval();
        
        $interval->fill([
            'local_id' => $local->id,
            'min_reservation_time' => $data['minReservationTime'],
            'min_time_unit' => $data['minTimeUnit'],
            'max_reservation_time' => $data['maxReservationTime'],
            'max_time_unit' => $data['maxTimeUnit'],
        ]);
        
        $interval->save();
        
        // Actualizar las propiedades locales
        $this->minReservationTime = $data['minReservationTime'];
        $this->maxReservationTime = $data['maxReservationTime'];
        $this->minTimeUnit = $data['minTimeUnit'];
        $this->maxTimeUnit = $data['maxTimeUnit'];

        // Notificación de éxito
        Notification::make()
            ->success()
            ->title('Configuración guardada')
            ->body('La configuración se ha guardado correctamente para el local ' . $local->name)
            ->send();
    }

    public function programBlock()
    {
        // Redirigir a la página de programación de bloqueo con el local seleccionado
        $localSeleccionado = $this->data['selectedLocal'] ?? $this->selectedLocal;

        $this->redirect(route('filament.admin.pages.programar-bloqueo', ['local' => $localSeleccionado]));
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

    /**
     * Desbloquea un slot de tiempo, haciéndolo disponible de nuevo
     */
    public function desbloquearSlot($time, $date)
    {
        // Si el slot no existe, inicializarlo
        if (! isset($this->timeSlots[$time][$date])) {
            $this->timeSlots[$time][$date] = [
                'bloqueado' => false,
                'reservado' => false,
                'seleccionado' => false,
            ];
        }
        // Marcar como disponible
        $this->timeSlots[$time][$date]['bloqueado'] = false;
        $this->timeSlots[$time][$date]['seleccionado'] = false;
        // Limpiar selección
        $this->selectedTime = null;
        $this->selectedDate = null;
        $this->selectedSlotStatus = null;
        // Regenerar los slots para refrescar la vista
        $this->generateTimeSlots();
    }

    /**
     * Elimina el rango de bloqueo correspondiente en la base de datos y refresca la grilla.
     */
    public function desbloquearRangoBloqueado()
    {
        if (!$this->selectedTime || !$this->selectedDate) {
            session()->flash('bloqueo_unlocked', 'No se pudo identificar el rango bloqueado.');
            return;
        }
        $local = $this->data['selectedLocal'] ?? $this->selectedLocal;
        $fecha = $this->selectedDate;
        $horaOriginal = $this->selectedTime;

        // Convertir la hora seleccionada a formato 24 horas (HH:MM)
        try {
            $horaCarbon = \Carbon\Carbon::createFromFormat('g:i A', $horaOriginal);
        } catch (\Exception $e) {
            try {
                $horaCarbon = \Carbon\Carbon::createFromFormat('H:i', $horaOriginal);
            } catch (\Exception $e2) {
                $horaCarbon = null;
            }
        }
        $hora = $horaCarbon ? $horaCarbon->format('H:i') : $horaOriginal;

        // Buscar el bloqueo que afecta este slot (all_day o rango parcial)
        $bloqueo = \App\Models\Bloqueo::where('premises', $local)
            ->where('start_date', '<=', $fecha)
            ->where('end_date', '>=', $fecha)
            ->where(function ($query) use ($hora) {
                $query->where('all_day', true)
                      ->orWhere(function ($q) use ($hora) {
                          $q->where('start_time', '<=', $hora)
                            ->where('end_time', '>', $hora);
                      });
            })
            ->first();

        if ($bloqueo) {
            $bloqueo->delete();
            session()->flash('bloqueo_unlocked', 'El rango bloqueado ha sido desbloqueado correctamente.');
        } else {
            session()->flash('bloqueo_unlocked', 'No se encontró el rango bloqueado para desbloquear.');
        }

        $this->resetSelection();
        $this->generateTimeSlots();
    }

    protected function getViewData(): array
    {
        // Obtener los días de la semana
        $weekDays = $this->getWeekDays();
        $localSeleccionadoCode = $this->data['selectedLocal'] ?? $this->selectedLocal;
        $premiseId = $this->getPremiseIdFromCode($localSeleccionadoCode);
        $startDate = $weekDays[0]['date']->copy()->startOfDay();
        $endDate = end($weekDays)['date']->copy()->endOfDay();
        $appointments = \App\Models\Appointment::where('premise_id', $premiseId)
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->get();

        return [
            'weekDays' => $weekDays,
            'timeSlots' => $this->timeSlots,
            'selectedSlotStatus' => $this->selectedSlotStatus,
            'appointments' => $appointments,
            'selectedLocal' => $localSeleccionadoCode, // para evitar error en blade
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

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['minReservationTime', 'maxReservationTime', 'minTimeUnit', 'maxTimeUnit'])) {
            $this->data[$propertyName] = $this->{$propertyName};
        }
    }
}
