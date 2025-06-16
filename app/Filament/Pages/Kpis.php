<?php

namespace App\Filament\Pages;

use App\Models\Local;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class Kpis extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'KPIs';

    protected static ?string $navigationGroup = '📊 Reportes & KPIs';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Indicadores de Desempeño (KPIs)';

    protected static string $view = 'filament.pages.kpis';

    // Propiedades para filtros
    public string $fechaInicio = '';

    public string $fechaFin = '';

    public string $rangoFechas = '';

    public string $marcaSeleccionada = 'Toyota';

    public string $localSeleccionado = 'Todos';

    public string $tipoSeleccionado = 'Post Venta';

    // Datos de KPIs
    public Collection $kpis;

    // Opciones para los selectores
    public array $marcas = ['Toyota', 'Lexus', 'Hino'];

    public array $locales = [];

    public array $tipos = ['Post Venta', 'Venta', 'Todos'];

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
        $this->cargarKpis();
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

        } catch (\Exception $e) {
            // Si hay un error, usar algunos valores por defecto
            $this->locales = ['Todos' => 'Todos'];
        }
    }

    public function cargarKpis(): void
    {
        try {
            // Obtener el nombre del local seleccionado para mostrar en los logs
            $nombreLocalSeleccionado = $this->localSeleccionado;
            if ($this->localSeleccionado !== 'Todos' && isset($this->locales[$this->localSeleccionado])) {
                $nombreLocalSeleccionado = $this->locales[$this->localSeleccionado];
            }

            // Aquí normalmente consultarías a la base de datos
            // Por ahora, usaremos datos de ejemplo
            $this->kpis = collect([
                [
                    'id' => 1,
                    'nombre' => 'Cantidad de citas generadas',
                    'cantidad' => 70,
                    'meta' => 80,
                    'contribucion' => false,
                    'desviacion' => '-13%',
                ],
                [
                    'id' => 2,
                    'nombre' => 'Cantidad de citas efectivas',
                    'cantidad' => 5,
                    'meta' => 10,
                    'contribucion' => false,
                    'desviacion' => '-50%',
                ],
                [
                    'id' => 3,
                    'nombre' => 'Cantidad de citas canceladas',
                    'cantidad' => 10,
                    'meta' => null,
                    'contribucion' => true,
                    'desviacion' => null,
                ],
                [
                    'id' => 4,
                    'nombre' => 'Cantidad de citas diferidas / reprogramadas',
                    'cantidad' => 5,
                    'meta' => null,
                    'contribucion' => true,
                    'desviacion' => null,
                ],
                [
                    'id' => 5,
                    'nombre' => 'Cantidad citas por mantenimiento',
                    'cantidad' => 40,
                    'meta' => null,
                    'contribucion' => true,
                    'desviacion' => null,
                ],
                [
                    'id' => 6,
                    'nombre' => 'Cantidad de citas de mantenimientos prepagados generadas',
                    'cantidad' => 10,
                    'meta' => 10,
                    'contribucion' => true,
                    'desviacion' => null,
                ],
                [
                    'id' => 7,
                    'nombre' => 'Cantidad de citas de mantenimientos prepagados realizadas',
                    'cantidad' => 10,
                    'meta' => 10,
                    'contribucion' => true,
                    'desviacion' => '-5%',
                ],
                [
                    'id' => 8,
                    'nombre' => 'Cantidad de citas con no show',
                    'cantidad' => 15,
                    'meta' => null,
                    'contribucion' => true,
                    'desviacion' => null,
                ],
            ]);

        } catch (\Exception $e) {
            $this->kpis = collect([]);
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

        $this->cargarKpis();
    }

    public function exportarExcel(): void
    {
        // Aquí iría la lógica para exportar a Excel
        // Por ahora, solo mostraremos una notificación

        \Filament\Notifications\Notification::make()
            ->title('Exportación iniciada')
            ->body('El archivo Excel se está generando y se descargará en breve.')
            ->success()
            ->send();
    }
}
