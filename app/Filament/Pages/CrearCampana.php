<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class CrearCampana extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationLabel = 'Crear Campaña';

    protected static ?string $title = 'Crear una campaña';

    protected static string $view = 'filament.pages.crear-campana';

    // Ocultar de la navegación principal ya que se accederá desde la página de campañas
    protected static bool $shouldRegisterNavigation = false;

    // Propiedades para los pasos
    public int $pasoActual = 1;
    public int $totalPasos = 3;

    // Datos de la campaña - Paso 1
    public string $codigoCampana = '';
    public string $tituloCampana = '';
    public string $fechaInicio = '';
    public string $fechaFin = '';

    // Segmentación - Paso 1
    public array $modelosSeleccionados = [];
    public array $anosSeleccionados = [];
    public array $localesSeleccionados = [];

    // Horario - Paso 1
    public bool $todoElDia = true;
    public string $horaInicio = '08:00';
    public string $horaFin = '18:00';

    // Imagen y estado - Paso 1
    public $imagen = null;
    public string $estadoCampana = 'Activo';

    // Opciones para los selectores
    public array $modelos = [
        'COROLLA CROSS',
        'HILUX',
        'LAND CRUISER',
        'RAV4',
        'YARIS',
        'YARIS CROSS',
    ];

    public array $anos = [
        '2018',
        '2019',
        '2020',
        '2021',
        '2022',
        '2023',
        '2024',
    ];

    public array $locales = [
        'Lima',
        'La Molina',
        'Canadá',
        'Arequipa',
    ];

    public function mount(): void
    {
        $this->pasoActual = 1;
    }

    public function siguientePaso(): void
    {
        // Validar datos según el paso actual
        if ($this->pasoActual === 1) {
            $this->validate([
                'codigoCampana' => 'required|min:3',
                'tituloCampana' => 'required|min:3',
                'fechaInicio' => 'required|date_format:d/m/Y',
                'fechaFin' => 'required|date_format:d/m/Y|after_or_equal:fechaInicio',
                'localesSeleccionados' => 'required|array|min:1',
            ], [
                'codigoCampana.required' => 'El código de campaña es obligatorio',
                'tituloCampana.required' => 'El título de campaña es obligatorio',
                'fechaInicio.required' => 'La fecha de inicio es obligatoria',
                'fechaFin.required' => 'La fecha de fin es obligatoria',
                'fechaFin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio',
                'localesSeleccionados.required' => 'Debe seleccionar al menos un local',
                'localesSeleccionados.min' => 'Debe seleccionar al menos un local',
            ]);
        }

        if ($this->pasoActual < $this->totalPasos) {
            $this->pasoActual++;
        }
    }

    public function anteriorPaso(): void
    {
        if ($this->pasoActual > 1) {
            $this->pasoActual--;
        }
    }

    public function finalizarCreacion(): void
    {
        // Aquí iría la lógica para guardar la campaña en la base de datos

        // Mostrar notificación de éxito
        \Filament\Notifications\Notification::make()
            ->title('Campaña creada')
            ->body('La campaña se ha creado correctamente')
            ->success()
            ->send();

        // Ir al paso final
        $this->pasoActual = 3;
    }

    public function volverACampanas(): void
    {
        $this->redirect(Campanas::getUrl());
    }
}
