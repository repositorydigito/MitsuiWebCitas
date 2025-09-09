<?php

namespace App\Filament\Pages;

use App\Models\Local;
use App\Models\Vehicle;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class Kpis extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'KPIs';

    protected static ?string $navigationGroup = 'Reportes & KPIs';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Indicadores de Desempeño (KPIs)';

    protected static string $view = 'filament.pages.kpis';

    // Propiedades para filtros
    public string $fechaInicio = '';

    public string $fechaFin = '';

    public string $rangoFechas = '';

    public string $marcaSeleccionada = 'Todas';

    public string $localSeleccionado = 'Todos';

    public string $tipoSeleccionado = 'Post Venta';

    // Datos de KPIs
    public Collection $kpis;

    // Opciones para los selectores
    public array $marcas = ['Todas'];

    public array $locales = [];

    public array $tipos = ['Post Venta', 'Venta', 'Todos'];

    public function mount(): void
    {
        // Establecer fechas por defecto (último mes)
        $fechaFin = now();
        $fechaInicio = now()->subDays(30);

        $this->fechaInicio = $fechaInicio->format('d/m/Y');
        $this->fechaFin = $fechaFin->format('d/m/Y');
        $this->rangoFechas = $this->fechaInicio.' - '.$this->fechaFin;

        // Cargar locales y marcas desde la base de datos
        $this->cargarLocales();
        $this->cargarMarcas();

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

    /**
     * Carga las marcas disponibles desde la base de datos
     */
    protected function cargarMarcas(): void
    {
        try {
            // Obtener marcas únicas de los vehículos
            $marcasDb = Vehicle::distinct('brand_name')
                ->whereNotNull('brand_name')
                ->where('brand_name', '!=', '')
                ->pluck('brand_name')
                ->sort()
                ->values()
                ->toArray();

            // Agregar la opción "Todos" al principio
            $this->marcas = array_merge(['Todas'], $marcasDb);

            // Si no hay marcas en la BD, usar valores por defecto
            if (empty($marcasDb)) {
                $this->marcas = ['Todas', 'Toyota', 'Lexus', 'Hino'];
            }

            // Establecer marca por defecto
            $this->marcaSeleccionada = 'Todas';

            \Log::info('[Kpis] Marcas cargadas: '.json_encode($this->marcas));
        } catch (\Exception $e) {
            \Log::error('[Kpis] Error al cargar marcas: '.$e->getMessage());

            // Si hay un error, usar valores por defecto
            $this->marcas = ['Todas', 'Toyota', 'Lexus', 'Hino'];
            $this->marcaSeleccionada = 'Todas';
        }
    }

    public function cargarKpis(): void
    {
        try {
            \Log::info('[Kpis] Cargando datos con filtros: ', [
                'rangoFechas' => $this->rangoFechas,
                'fechaInicio' => $this->fechaInicio,
                'fechaFin' => $this->fechaFin,
                'marca' => $this->marcaSeleccionada,
                'local' => $this->localSeleccionado,
            ]);

            // Parsear fechas del filtro
            $fechaInicio = null;
            $fechaFin = null;
            
            if (!empty($this->rangoFechas)) {
                $fechas = explode(' - ', $this->rangoFechas);
                if (count($fechas) === 2) {
                    $fechaInicio = \Carbon\Carbon::createFromFormat('d/m/Y', trim($fechas[0]));
                    $fechaFin = \Carbon\Carbon::createFromFormat('d/m/Y', trim($fechas[1]));
                }
            }

            // Construir query base con filtros
            $query = \App\Models\Appointment::query();

            // Filtro por rango de fechas (solo si hay fechas válidas)
            if ($fechaInicio && $fechaFin) {
                // Mantener las fechas en formato local sin convertir a UTC
                $fechaInicioStr = $fechaInicio->format('Y-m-d');
                $fechaFinStr = $fechaFin->format('Y-m-d');
                
                $query->whereBetween('appointment_date', [$fechaInicioStr, $fechaFinStr]);
                \Log::info("Filtro fechas aplicado: {$fechaInicioStr} - {$fechaFinStr}");
            }
            // Si no hay fechas válidas, no aplicamos ningún filtro de fecha por defecto
            // Esto hará que se muestren 0 resultados cuando se selecciona un rango sin datos

            // Aplicar filtro de local (premise_id)
            if ($this->localSeleccionado !== 'Todos' && !empty($this->localSeleccionado)) {
                // Obtener el ID del local a partir del código
                $localId = $this->obtenerIdLocal($this->localSeleccionado);
                if ($localId) {
                    $query->where('premise_id', $localId);
                    \Log::info("Filtro local aplicado (premise_id): {$this->localSeleccionado} -> {$localId}");
                } else {
                    \Log::info("Local no encontrado para el código: {$this->localSeleccionado}");
                }
            }

            // Aplicar filtro de marca (vehicle_brand_code)
            if ($this->marcaSeleccionada !== 'Todas' && !empty($this->marcaSeleccionada)) {
                $query->whereHas('vehicle', function ($q) {
                    $q->where('brand_name', $this->marcaSeleccionada);
                });
                \Log::info("Filtro marca aplicado (brand_name): {$this->marcaSeleccionada}");
            }
            
            // Debug: Mostrar la consulta SQL generada con bindings
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            $fullSql = $sql;
            foreach ($bindings as $binding) {
                $value = is_numeric($binding) ? $binding : "'$binding'";
                $fullSql = preg_replace('/\?/', $value, $fullSql, 1);
            }
            \Log::info("Consulta SQL: " . $sql);
            \Log::info("Bindings: " . json_encode($bindings));
            \Log::info("Consulta SQL completa: " . $fullSql);

            // Debug: Contar después de cada filtro
            $afterDateFilter = (clone $query)->count();
            \Log::info("Después de filtro fecha: {$afterDateFilter}");

            // Aplicar filtro local y contar
            $afterLocalFilter = (clone $query)->count();
            \Log::info("Después de filtro local: {$afterLocalFilter}");

            // Debug: Ver qué estados y premise_ids existen
            $estadosExistentes = \App\Models\Appointment::distinct()->pluck('status')->toArray();
            $localesExistentes = \App\Models\Appointment::distinct()->pluck('premise_id')->toArray();
            $marcasExistentes = \App\Models\Appointment::distinct()->pluck('vehicle_brand_code')->toArray();
            \Log::info("Estados existentes: " . implode(', ', $estadosExistentes));
            \Log::info("Premise IDs existentes: " . implode(', ', $localesExistentes));
            \Log::info("Marcas existentes: " . implode(', ', $marcasExistentes));

            // Para debug: crear query sin filtros restrictivos
            $queryDebug = \App\Models\Appointment::query();
            if ($fechaInicio && $fechaFin) {
                $queryDebug->whereBetween('appointment_date', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')]);
            } else {
                $queryDebug->where('appointment_date', '>=', now()->subYear()->format('Y-m-d'));
            }
            
            // Calcular KPIs - Usar query con filtros aplicados
            $todasLasCitas = (clone $query)->count();
            $todasSinFiltrosRestrictivos = (clone $queryDebug)->count();
            
            // Luego con filtros específicos
            $citasGeneradas = (clone $query)->whereIn('status', ['pending', 'confirmed', 'generated', 'in_progress', 'completed'])->count();
            // KPI 2: Citas efectivas son las que tienen estado 'confirmed'
            $citasEfectivas = (clone $query)->where('status', 'confirmed')->count();
            // KPI 3: Citas canceladas son las que tienen estado 'cancelled' y rescheduled = 0
            $citasCanceladas = (clone $query)->where('status', 'cancelled')
                                          ->where('rescheduled', 0)
                                          ->count();
            $citasReprogramadas = (clone $query)->where('rescheduled', 1)->count();
            // KPI 5: Citas de mantenimiento son las que tienen maintenance_type no vacío y no nulo
            $citasMantenimiento = (clone $query)->whereNotNull('maintenance_type')
                                             ->where('maintenance_type', '!=', '')
                                             ->count();

            \Log::info("KPIs calculados - Con filtros: {$todasLasCitas}, Solo fecha: {$todasSinFiltrosRestrictivos}, Generadas: {$citasGeneradas}");

            // Calcular desviaciones
            $metaGeneradas = 80;
            $metaEfectivas = 10;
            
            $desviacionGeneradas = $metaGeneradas > 0 ? round((($citasGeneradas - $metaGeneradas) / $metaGeneradas) * 100, 1) : 0;
            $desviacionEfectivas = $metaEfectivas > 0 ? round((($citasEfectivas - $metaEfectivas) / $metaEfectivas) * 100, 1) : -100;

            $this->kpis = collect([
                [
                    'id' => 1,
                    'nombre' => 'Cantidad de citas generadas',
                    'cantidad' => $todasLasCitas, // Usar el conteo con todos los filtros aplicados
                    'meta' => $metaGeneradas,
                    'contribucion' => false,
                    'desviacion' => $desviacionGeneradas != 0 ? ($desviacionGeneradas > 0 ? "+{$desviacionGeneradas}%" : "{$desviacionGeneradas}%") : "0%",
                ],
                [
                    'id' => 2,
                    'nombre' => 'Cantidad de citas efectivas',
                    'cantidad' => $citasEfectivas,
                    'meta' => $metaEfectivas,
                    'contribucion' => false,
                    'desviacion' => $desviacionEfectivas != -100 ? ($desviacionEfectivas > 0 ? "+{$desviacionEfectivas}%" : "{$desviacionEfectivas}%") : "-100%",
                ],
                [
                    'id' => 3,
                    'nombre' => 'Cantidad de citas canceladas',
                    'cantidad' => $citasCanceladas,
                    'meta' => null,
                    'contribucion' => true,
                    'desviacion' => null,
                ],
                [
                    'id' => 4,
                    'nombre' => 'Cantidad de citas diferidas / reprogramadas',
                    'cantidad' => $citasReprogramadas,
                    'meta' => null,
                    'contribucion' => true,
                    'desviacion' => null,
                ],
                [
                    'id' => 5,
                    'nombre' => 'Cantidad citas por mantenimiento',
                    'cantidad' => $citasMantenimiento,
                    'meta' => null,
                    'contribucion' => true,
                    'desviacion' => null,
                ],
                [
                    'id' => 6,
                    'nombre' => 'Cantidad de citas de mantenimientos prepagados generadas',
                    'cantidad' => '',
                    'meta' => null,
                    'contribucion' => false,
                    'desviacion' => null,
                ],
                [
                    'id' => 7,
                    'nombre' => 'Cantidad de citas de mantenimientos prepagados realizadas',
                    'cantidad' => '',
                    'meta' => null,
                    'contribucion' => false,
                    'desviacion' => null,
                ],
                [
                    'id' => 8,
                    'nombre' => 'Cantidad de citas con no show',
                    'cantidad' => '',
                    'meta' => null,
                    'contribucion' => false,
                    'desviacion' => null,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al cargar KPIs: ' . $e->getMessage());
            $this->kpis = collect([]);
        }
    }

    /**
     * Obtiene el ID del local por su código
     */
    protected function obtenerIdLocal(string $codigoLocal): ?int
    {
        try {
            $local = Local::where('code', $codigoLocal)->first();
            return $local ? $local->id : null;
        } catch (\Exception $e) {
            \Log::error("Error obteniendo ID del local '{$codigoLocal}': " . $e->getMessage());
            return null;
        }
    }

    public function aplicarFiltros(): void
    {
        // Procesar el rango de fechas si está presente
        if (! empty($this->rangoFechas)) {
            \Log::info('[Kpis] Rango original: ' . $this->rangoFechas);
            
            // Intentar diferentes separadores
            $fechas = [];
            if (strpos($this->rangoFechas, ' - ') !== false) {
                $fechas = explode(' - ', $this->rangoFechas);
            } elseif (strpos($this->rangoFechas, ' a ') !== false) {
                $fechas = explode(' a ', $this->rangoFechas);
            } elseif (strpos($this->rangoFechas, ' to ') !== false) {
                $fechas = explode(' to ', $this->rangoFechas);
            }
            
            if (count($fechas) === 2) {
                $fechaInicioStr = trim($fechas[0]);
                $fechaFinStr = trim($fechas[1]);
                
                // Validar que las fechas tengan el formato correcto
                $fechaInicio = null;
                $fechaFin = null;
                
                try {
                    $fechaInicio = \Carbon\Carbon::createFromFormat('d/m/Y', $fechaInicioStr);
                    $fechaFin = \Carbon\Carbon::createFromFormat('d/m/Y', $fechaFinStr);
                    
                    // Verificar que la fecha de inicio no sea posterior a la fecha de fin
                    if ($fechaInicio->greaterThan($fechaFin)) {
                        \Log::warning('[Kpis] Fecha de inicio posterior a fecha de fin: ' . $fechaInicioStr . ' > ' . $fechaFinStr);
                        // Intercambiar fechas si están en orden incorrecto
                        $temp = $fechaInicio;
                        $fechaInicio = $fechaFin;
                        $fechaFin = $temp;
                        $this->rangoFechas = $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y');
                    }
                    
                    $this->fechaInicio = $fechaInicio->format('d/m/Y');
                    $this->fechaFin = $fechaFin->format('d/m/Y');
                    
                    \Log::info('[Kpis] Fechas parseadas - Inicio: ' . $this->fechaInicio . ', Fin: ' . $this->fechaFin);
                } catch (\Exception $e) {
                    \Log::error('[Kpis] Error parseando fechas: ' . $e->getMessage());
                    // Si hay error en el parseo, limpiar las fechas
                    $this->fechaInicio = '';
                    $this->fechaFin = '';
                    return;
                }
            } else {
                \Log::warning('[Kpis] No se pudo parsear el rango: ' . $this->rangoFechas);
                return; // No cargar datos si no se pudo parsear
            }
        }

        $this->cargarKpis();
    }

    public function updatedMarcaSeleccionada(): void
    {
        $this->cargarKpis();
    }

    public function updatedLocalSeleccionado(): void
    {
        $this->cargarKpis();
    }

    public function limpiarFiltros(): void
    {
        // Restablecer fechas por defecto (último mes)
        $fechaFin = now();
        $fechaInicio = now()->subDays(30);

        $this->fechaInicio = $fechaInicio->format('d/m/Y');
        $this->fechaFin = $fechaFin->format('d/m/Y');
        $this->rangoFechas = $this->fechaInicio.' - '.$this->fechaFin;
        
        // Restablecer otros filtros
        $this->marcaSeleccionada = 'Todas';
        $this->localSeleccionado = 'Todos';
        
        // Recargar datos
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