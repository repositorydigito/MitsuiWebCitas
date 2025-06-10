<?php

namespace App\Filament\Pages;

use App\Models\Local;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class DashboardKpi extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Dashboard KPI';

    protected static ?string $title = 'Dashboard de Indicadores';

    protected static string $view = 'filament.pages.dashboard-kpi';

    protected static ?int $navigationSort = 24;

    // Propiedades para filtros
    public string $fechaInicio = '';

    public string $fechaFin = '';

    public string $rangoFechas = '';

    public string $marcaSeleccionada = 'Toyota';

    public string $localSeleccionado = 'Todos';

    public string $localSeleccionadoGraficos = 'Todos';

    public string $marcaSeleccionadaGraficos = 'Toyota';

    // Datos para los KPIs
    public int $citasGeneradas = 70;

    public int $citasEfectivas = 50;

    public int $porcentajeEfectividad = 71;

    public int $citasDiferidas = 9;

    public int $citasCanceladas = 5;

    public int $porcentajeNoShow = 8;

    public int $citasMantenimiento = 49;

    public int $citasMantenimientoPrepagados = 15;

    public int $porcentajeMantenimiento = 70;

    public int $porcentajePrepagados = 21;

    // Datos para los gráficos
    public array $datosCantidadCitas = [];

    public array $datosTiempoPromedio = [];

    // Opciones para los selectores
    public array $marcas = ['Toyota', 'Lexus', 'Hino'];

    public array $locales = [];

    public function mount(): void
    {
        // Establecer fechas por defecto (última semana)
        $fechaFin = now();
        $fechaInicio = now()->subDays(7);

        $this->fechaInicio = $fechaInicio->format('d/m/Y');
        $this->fechaFin = $fechaFin->format('d/m/Y');
        $this->rangoFechas = $this->fechaInicio.' - '.$this->fechaFin;

        // Cargar locales desde la base de datos
        $this->cargarLocales();

        // Cargar datos iniciales
        $this->cargarDatos();
    }

    /**
     * Carga los locales activos desde la base de datos
     */
    protected function cargarLocales(): void
    {
        try {
            // Obtener los locales activos usando el método del modelo
            $localesActivos = Local::getActivosParaSelector();

            // Agregar la opción "Todos" al principio
            $this->locales = ['Todos' => 'Todos'] + $localesActivos;

            Log::info('[DashboardKpi] Locales cargados: '.json_encode($this->locales));
        } catch (\Exception $e) {
            Log::error('[DashboardKpi] Error al cargar locales: '.$e->getMessage());

            // Si hay un error, usar algunos valores por defecto
            $this->locales = ['Todos' => 'Todos'];
        }
    }

    public function cargarDatos(): void
    {
        try {
            // Obtener el nombre del local seleccionado para mostrar en los logs
            $nombreLocalSeleccionado = $this->localSeleccionado;
            if ($this->localSeleccionado !== 'Todos' && isset($this->locales[$this->localSeleccionado])) {
                $nombreLocalSeleccionado = $this->locales[$this->localSeleccionado];
            }

            // Obtener el nombre del local seleccionado para los gráficos
            $nombreLocalSeleccionadoGraficos = $this->localSeleccionadoGraficos;
            if ($this->localSeleccionadoGraficos !== 'Todos' && isset($this->locales[$this->localSeleccionadoGraficos])) {
                $nombreLocalSeleccionadoGraficos = $this->locales[$this->localSeleccionadoGraficos];
            }

            Log::info('[DashboardKpi] Cargando datos con filtros: ', [
                'fechaInicio' => $this->fechaInicio,
                'fechaFin' => $this->fechaFin,
                'marca' => $this->marcaSeleccionada,
                'local_codigo' => $this->localSeleccionado,
                'local_nombre' => $nombreLocalSeleccionado,
                'marca_graficos' => $this->marcaSeleccionadaGraficos,
                'local_codigo_graficos' => $this->localSeleccionadoGraficos,
                'local_nombre_graficos' => $nombreLocalSeleccionadoGraficos,
            ]);

            // Aquí normalmente consultarías a la base de datos
            // Por ahora, usaremos datos de ejemplo

            // Datos para el gráfico de cantidad de citas
            $this->datosCantidadCitas = [
                'labels' => ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
                'generadas' => [0, 0, 110, 30, 0, 0, 0, 0, 0, 0, 0, 0],
                'efectivas' => [0, 0, 0, 20, 0, 0, 0, 0, 0, 0, 0, 0],
            ];

            // Datos para el gráfico de tiempo promedio
            $this->datosTiempoPromedio = [
                'labels' => ['ENE 2024', 'MAY 2024', 'JUN 2024', 'JUL 2024', 'AGO 2024', 'SET 2024'],
                'tiempos' => [13, 8, 6, 4, 8, 11],
            ];

        } catch (\Exception $e) {
            Log::error('[DashboardKpi] Error al cargar datos: '.$e->getMessage());
        }
    }

    public function aplicarFiltros(): void
    {
        // Procesar el rango de fechas si está presente
        if (! empty($this->rangoFechas)) {
            $fechas = explode(' - ', $this->rangoFechas);
            if (count($fechas) === 2) {
                $this->fechaInicio = trim($fechas[0]);
                $this->fechaFin = trim($fechas[1]);
            }
        }

        $this->cargarDatos();
    }
}
