<?php

namespace App\Filament\Pages;

use App\Models\Local;
use App\Models\Appointment;
use App\Models\Vehicle;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardKpi extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Dashboard KPI';

    protected static ?string $navigationGroup = 'Reportes & KPIs';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Dashboard de Indicadores';

    protected static string $view = 'filament.pages.dashboard-kpi';

    // Propiedades para filtros
    public string $fechaInicio = '';

    public string $fechaFin = '';

    public string $rangoFechas = '';

    public string $marcaSeleccionada = 'Todos';

    public string $localSeleccionado = 'Todos';

    public string $localSeleccionadoGraficos = 'Todos';

    public string $marcaSeleccionadaGraficos = 'Todos';

    // Datos para los KPIs
    public int $citasGeneradas = 0;

    public int $citasEfectivas = 0;

    public int $porcentajeEfectividad = 0;

    public int $citasDiferidas = 0;

    public int $citasCanceladas = 0;

    public int $porcentajeCancelacion = 0; // Changed from porcentajeNoShow

    public int $citasMantenimiento = 0;

    public int $citasMantenimientoPrepagados = 0;

    public int $porcentajeMantenimiento = 0;

    public int $porcentajePrepagados = 0;

    public int $cantidadUsuarios = 0;

    // Datos para los gráficos
    public array $datosCantidadCitas = [];

    public array $datosTiempoPromedio = [];

    // Opciones para los selectores
    public array $marcas = ['Todos'];

    public array $locales = [];

    public array $localesGraficos = [];

    public function mount(): void
    {
        // Establecer fechas por defecto (último mes)
        $fechaFin = now();
        $fechaInicio = now()->subMonth();

        $this->fechaInicio = $fechaInicio->format('d/m/Y');
        $this->fechaFin = $fechaFin->format('d/m/Y');
        $this->rangoFechas = $this->fechaInicio.' - '.$this->fechaFin;

        // Cargar locales y marcas desde la base de datos
        $this->cargarLocales();
        $this->cargarMarcas();
        
        // Inicializar locales para gráficos
        $this->actualizarLocalesPorMarcaGraficos();

        // Cargar datos iniciales
        $this->cargarDatos();
    }

    /**
     * Carga los locales activos desde la base de datos
     */
    protected function cargarLocales(): void
    {
        try {
            // Obtener los locales activos filtrados por marca si es necesario
            $this->actualizarLocalesPorMarca();

            Log::info('[DashboardKpi] Locales cargados: '.json_encode($this->locales));
        } catch (\Exception $e) {
            Log::error('[DashboardKpi] Error al cargar locales: '.$e->getMessage());

            // Si hay un error, usar algunos valores por defecto
            $this->locales = ['Todos' => 'Todos'];
        }
    }

    /**
     * Actualiza la lista de locales basada en la marca seleccionada
     */
    protected function actualizarLocalesPorMarca(): void
    {
        $query = Local::where('is_active', true);
        
        // Si hay una marca seleccionada que no sea "Todos", filtrar por marca
        if ($this->marcaSeleccionada !== 'Todos') {
            $query->where('brand', $this->marcaSeleccionada);
        }
        
        $localesActivos = $query->orderBy('name')
            ->pluck('name', 'code')
            ->toArray();

        // Agregar la opción "Todos" al principio
        $this->locales = ['Todos' => 'Todos'] + $localesActivos;
        
        // Si el local actualmente seleccionado no está en la nueva lista, resetear a "Todos"
        if ($this->localSeleccionado !== 'Todos' && !array_key_exists($this->localSeleccionado, $this->locales)) {
            $this->localSeleccionado = 'Todos';
        }
    }

    /**
     * Actualiza la lista de locales para gráficos basada en la marca seleccionada
     */
    protected function actualizarLocalesPorMarcaGraficos(): void
    {
        $query = Local::where('is_active', true);
        
        // Si hay una marca seleccionada que no sea "Todos", filtrar por marca
        if ($this->marcaSeleccionadaGraficos !== 'Todos') {
            $query->where('brand', $this->marcaSeleccionadaGraficos);
        }
        
        $localesActivos = $query->orderBy('name')
            ->pluck('name', 'code')
            ->toArray();

        // Agregar la opción "Todos" al principio
        $this->localesGraficos = ['Todos' => 'Todos'] + $localesActivos;
        
        // Si el local actualmente seleccionado para gráficos no está en la nueva lista, resetear a "Todos"
        if ($this->localSeleccionadoGraficos !== 'Todos' && !array_key_exists($this->localSeleccionadoGraficos, $this->localesGraficos)) {
            $this->localSeleccionadoGraficos = 'Todos';
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
            $this->marcas = array_merge(['Todos'], $marcasDb);

            // Si no hay marcas en la BD, usar valores por defecto
            if (empty($marcasDb)) {
                $this->marcas = ['Todos', 'Toyota', 'Lexus', 'Hino'];
            }

            // Establecer marca por defecto
            $this->marcaSeleccionada = 'Todos';
            $this->marcaSeleccionadaGraficos = 'Todos';

            Log::info('[DashboardKpi] Marcas cargadas: '.json_encode($this->marcas));
        } catch (\Exception $e) {
            Log::error('[DashboardKpi] Error al cargar marcas: '.$e->getMessage());

            // Si hay un error, usar valores por defecto
            $this->marcas = ['Todos', 'Toyota', 'Lexus', 'Hino'];
            $this->marcaSeleccionada = 'Todos';
            $this->marcaSeleccionadaGraficos = 'Todos';
        }
    }

    public function cargarDatos(): void
    {
        try {
            Log::info('[DashboardKpi] Cargando datos con filtros: ', [
                'rangoFechas' => $this->rangoFechas,
                'fechaInicio' => $this->fechaInicio,
                'fechaFin' => $this->fechaFin,
                'marca' => $this->marcaSeleccionada,
                'local' => $this->localSeleccionado,
            ]);

            // Validar que tenemos fechas válidas
            if (empty($this->fechaInicio) || empty($this->fechaFin)) {
                Log::warning('[DashboardKpi] Fechas vacías, usando valores por defecto');
                $this->establecerValoresPorDefecto();
                return;
            }

            // Convertir fechas del formato d/m/Y a Y-m-d para consultas
            $fechaInicioCarbon = $this->parsearFecha($this->fechaInicio);
            $fechaFinCarbon = $this->parsearFecha($this->fechaFin);

            Log::info('[DashboardKpi] Fechas convertidas: ', [
                'fechaInicioSQL' => $fechaInicioCarbon->format('Y-m-d'),
                'fechaFinSQL' => $fechaFinCarbon->format('Y-m-d'),
            ]);

            // Construir query base con filtros
            $query = $this->construirQueryBase($fechaInicioCarbon, $fechaFinCarbon);

            // Calcular KPIs principales
            $this->calcularKpisPrincipales($query);

            // Calcular cantidad de usuarios
            $this->calcularCantidadUsuarios();

            // Cargar datos para gráficos
            $this->cargarDatosGraficos($fechaInicioCarbon, $fechaFinCarbon);

            // Ejecutar JavaScript directamente para actualizar gráficos
            $this->js("
                if (typeof window.updateAllCharts === 'function') {
                    window.updateAllCharts({
                        porcentajeMantenimiento: {$this->porcentajeMantenimiento},
                        porcentajePrepagados: {$this->porcentajePrepagados},
                        datosCantidadCitas: " . json_encode($this->datosCantidadCitas) . "
                    });
                }
            ");

        } catch (\Exception $e) {
            Log::error('[DashboardKpi] Error al cargar datos: '.$e->getMessage());
            $this->establecerValoresPorDefecto();
        }
    }

    /**
     * Parsea una fecha del formato d/m/Y a Carbon
     */
    protected function parsearFecha(string $fecha): Carbon
    {
        try {
            return Carbon::createFromFormat('d/m/Y', $fecha)->startOfDay();
        } catch (\Exception $e) {
            Log::warning("[DashboardKpi] Error parseando fecha '{$fecha}', usando fecha actual");
            return Carbon::now()->startOfDay();
        }
    }

    /**
     * Construye la query base con filtros aplicados
     */
    protected function construirQueryBase(Carbon $fechaInicio, Carbon $fechaFin)
    {
        $query = Appointment::query()
            ->whereBetween('appointment_date', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')]);

        // Filtro por local
        if ($this->localSeleccionado !== 'Todos') {
            $localId = $this->obtenerIdLocal($this->localSeleccionado);
            if ($localId) {
                $query->where('premise_id', $localId);
            }
        }

        // Filtro por marca
        if ($this->marcaSeleccionada !== 'Todos') {
            $query->whereHas('vehicle', function ($q) {
                $q->where('brand_name', 'like', '%' . $this->marcaSeleccionada . '%');
            });
        }

        return $query;
    }

    /**
     * Calcula los KPIs principales
     */
    protected function calcularKpisPrincipales($query): void
    {
        // Citas generadas (solo confirmed y cancelled sin reprogramación)
        $this->citasGeneradas = (clone $query)
            ->where(function($q) {
                $q->where('status', 'confirmed')
                  ->orWhere(function($q2) {
                      $q2->where('status', 'cancelled')
                         ->where('rescheduled', 0);
                  });
            })
            ->count();

        // Log para debugging
        Log::info('[DashboardKpi] Citas encontradas en el rango: ' . $this->citasGeneradas);

        // Citas efectivas (confirmed)
        $this->citasEfectivas = (clone $query)->where('status', 'confirmed')->count();

        // Citas canceladas (solo las que no fueron reprogramadas)
        $this->citasCanceladas = (clone $query)
            ->where('status', 'cancelled')
            ->where('rescheduled', 0)
            ->count();

        // Porcentaje de efectividad
        $this->porcentajeEfectividad = $this->citasGeneradas > 0 
            ? round(($this->citasEfectivas / $this->citasGeneradas) * 100) 
            : 0;

        // Porcentaje de cancelación (citas canceladas / citas generadas)
        $this->porcentajeCancelacion = $this->citasGeneradas > 0 
            ? round(($this->citasCanceladas / $this->citasGeneradas) * 100) 
            : 0;

        // Citas por mantenimiento (de las citas generadas que tienen maintenance_type lleno)
        $citasConMantenimiento = (clone $query)
            ->where(function($q) {
                $q->where('status', 'confirmed')
                  ->orWhere(function($q2) {
                      $q2->where('status', 'cancelled')
                         ->where('rescheduled', 0);
                  });
            })
            ->whereNotNull('maintenance_type')
            ->where('maintenance_type', '!=', '')
            ->count();

        $this->citasMantenimiento = $citasConMantenimiento;
        $this->porcentajeMantenimiento = $this->citasGeneradas > 0 
            ? round(($citasConMantenimiento / $this->citasGeneradas) * 100) 
            : 0;

        // Citas diferidas/reprogramadas (usando la nueva columna rescheduled)
        $this->citasDiferidas = (clone $query)->where('rescheduled', 1)->count();

        // Citas sin mantenimiento (que tienen datos en wildcard_selections)
        $citasSinMantenimiento = (clone $query)->whereNotNull('wildcard_selections')
            ->where('wildcard_selections', '!=', 'null')
            ->where('wildcard_selections', '!=', '')
            ->count();

        $this->citasMantenimientoPrepagados = $citasSinMantenimiento;
        $this->porcentajePrepagados = $this->citasGeneradas > 0 
            ? round(($citasSinMantenimiento / $this->citasGeneradas) * 100) 
            : 0;

        Log::info('[DashboardKpi] KPIs calculados:', [
            'citasGeneradas' => $this->citasGeneradas,
            'citasEfectivas' => $this->citasEfectivas,
            'citasCanceladas' => $this->citasCanceladas,
            'porcentajeCancelacion' => $this->porcentajeCancelacion, // Changed from porcentajeNoShow
            'citasDiferidas' => $this->citasDiferidas,
            'porcentajeEfectividad' => $this->porcentajeEfectividad,
            'citasMantenimiento' => $this->citasMantenimiento,
            'porcentajeMantenimiento' => $this->porcentajeMantenimiento,
            'citasMantenimientoPrepagados' => $this->citasMantenimientoPrepagados,
            'porcentajePrepagados' => $this->porcentajePrepagados,
        ]);
    }

    /**
     * Calcula la cantidad de usuarios con rol "Usuario"
     */
    protected function calcularCantidadUsuarios(): void
    {
        try {
            // Contar usuarios que tienen el rol "Usuario"
            $this->cantidadUsuarios = User::role('Usuario')->count();
            
            Log::info('[DashboardKpi] Cantidad de usuarios con rol "Usuario": ' . $this->cantidadUsuarios);
        } catch (\Exception $e) {
            Log::error('[DashboardKpi] Error al calcular cantidad de usuarios: ' . $e->getMessage());
            $this->cantidadUsuarios = 0;
        }
    }

    /**
     * Carga datos para los gráficos
     */
    protected function cargarDatosGraficos(Carbon $fechaInicio, Carbon $fechaFin): void
    {
        // Query base para gráficos
        $queryGraficos = $this->construirQueryBaseGraficos($fechaInicio, $fechaFin);

        // Determinar el tipo de agrupación basado en el rango de fechas
        $diasDiferencia = $fechaInicio->diffInDays($fechaFin);
        
        Log::info('[DashboardKpi] Configurando agrupación de datos', [
            'fecha_inicio' => $fechaInicio->format('Y-m-d'),
            'fecha_fin' => $fechaFin->format('Y-m-d'),
            'dias_diferencia' => $diasDiferencia
        ]);

        if ($diasDiferencia <= 31) {
            // Rango de 1 mes o menos: agrupar por semana
            $this->cargarDatosPorSemana($queryGraficos, $fechaInicio, $fechaFin);
        } elseif ($diasDiferencia <= 365) {
            // Rango de hasta 1 año: agrupar por mes
            $this->cargarDatosPorMes($queryGraficos, $fechaInicio, $fechaFin);
        } else {
            // Rango mayor a 1 año: agrupar por trimestre
            $this->cargarDatosPorTrimestre($queryGraficos, $fechaInicio, $fechaFin);
        }
    }

    /**
     * Cargar datos agrupados por semana
     */
    protected function cargarDatosPorSemana($query, Carbon $fechaInicio, Carbon $fechaFin): void
    {
        $datosPorSemana = $query
            ->select(
                DB::raw('YEAR(appointment_date) as year'),
                DB::raw('WEEK(appointment_date, 1) as week'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as efectivas')
            )
            ->groupBy('year', 'week')
            ->orderBy('year')
            ->orderBy('week')
            ->get();

        // Generar todas las semanas en el rango
        $labels = [];
        $generadas = [];
        $efectivas = [];
        
        $fechaActual = $fechaInicio->copy()->startOfWeek();
        while ($fechaActual <= $fechaFin) {
            $year = $fechaActual->year;
            $week = $fechaActual->week;
            
            // Buscar datos para esta semana
            $datoSemana = $datosPorSemana->first(function ($item) use ($year, $week) {
                return $item->year == $year && $item->week == $week;
            });
            
            $labels[] = 'Sem ' . $fechaActual->format('d/m');
            $generadas[] = $datoSemana ? $datoSemana->total : 0;
            $efectivas[] = $datoSemana ? $datoSemana->efectivas : 0;
            
            $fechaActual->addWeek();
        }

        $this->datosCantidadCitas = [
            'labels' => $labels,
            'generadas' => $generadas,
            'efectivas' => $efectivas,
        ];

        Log::info('[DashboardKpi] Datos cargados por semana', [
            'total_semanas' => count($labels),
            'labels' => $labels
        ]);
    }

    /**
     * Cargar datos agrupados por mes
     */
    protected function cargarDatosPorMes($query, Carbon $fechaInicio, Carbon $fechaFin): void
    {
        $datosPorMes = $query
            ->select(
                DB::raw('YEAR(appointment_date) as year'),
                DB::raw('MONTH(appointment_date) as month'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as efectivas')
            )
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        // Generar todos los meses en el rango
        $labels = [];
        $generadas = [];
        $efectivas = [];
        
        $fechaActual = $fechaInicio->copy()->startOfMonth();
        while ($fechaActual <= $fechaFin) {
            $year = $fechaActual->year;
            $month = $fechaActual->month;
            
            // Buscar datos para este mes
            $datoMes = $datosPorMes->first(function ($item) use ($year, $month) {
                return $item->year == $year && $item->month == $month;
            });
            
            $labels[] = $fechaActual->format('M Y');
            $generadas[] = $datoMes ? $datoMes->total : 0;
            $efectivas[] = $datoMes ? $datoMes->efectivas : 0;
            
            $fechaActual->addMonth();
        }

        $this->datosCantidadCitas = [
            'labels' => $labels,
            'generadas' => $generadas,
            'efectivas' => $efectivas,
        ];

        Log::info('[DashboardKpi] Datos cargados por mes', [
            'total_meses' => count($labels),
            'labels' => $labels
        ]);
    }

    /**
     * Cargar datos agrupados por trimestre
     */
    protected function cargarDatosPorTrimestre($query, Carbon $fechaInicio, Carbon $fechaFin): void
    {
        $datosPorTrimestre = $query
            ->select(
                DB::raw('YEAR(appointment_date) as year'),
                DB::raw('QUARTER(appointment_date) as quarter'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "confirmed" THEN 1 ELSE 0 END) as efectivas')
            )
            ->groupBy('year', 'quarter')
            ->orderBy('year')
            ->orderBy('quarter')
            ->get();

        // Generar todos los trimestres en el rango
        $labels = [];
        $generadas = [];
        $efectivas = [];
        
        $fechaActual = $fechaInicio->copy()->startOfQuarter();
        while ($fechaActual <= $fechaFin) {
            $year = $fechaActual->year;
            $quarter = $fechaActual->quarter;
            
            // Buscar datos para este trimestre
            $datoTrimestre = $datosPorTrimestre->first(function ($item) use ($year, $quarter) {
                return $item->year == $year && $item->quarter == $quarter;
            });
            
            $labels[] = 'Q' . $quarter . ' ' . $year;
            $generadas[] = $datoTrimestre ? $datoTrimestre->total : 0;
            $efectivas[] = $datoTrimestre ? $datoTrimestre->efectivas : 0;
            
            $fechaActual->addQuarter();
        }

        $this->datosCantidadCitas = [
            'labels' => $labels,
            'generadas' => $generadas,
            'efectivas' => $efectivas,
        ];

        Log::info('[DashboardKpi] Datos cargados por trimestre', [
            'total_trimestres' => count($labels),
            'labels' => $labels
        ]);
    }

    /**
     * Construye query base para gráficos (puede tener filtros diferentes)
     */
    protected function construirQueryBaseGraficos(Carbon $fechaInicio, Carbon $fechaFin)
    {
        $query = Appointment::query()
            ->whereBetween('appointment_date', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')]);

        // Filtro por local para gráficos
        if ($this->localSeleccionadoGraficos !== 'Todos') {
            $localId = $this->obtenerIdLocal($this->localSeleccionadoGraficos);
            if ($localId) {
                $query->where('premise_id', $localId);
            }
        }

        // Filtro por marca para gráficos
        if ($this->marcaSeleccionadaGraficos !== 'Todos') {
            $query->whereHas('vehicle', function ($q) {
                $q->where('brand_name', 'like', '%' . $this->marcaSeleccionadaGraficos . '%');
            });
        }

        return $query;
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
            Log::error("[DashboardKpi] Error obteniendo ID del local '{$codigoLocal}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Establece valores por defecto en caso de error
     */
    protected function establecerValoresPorDefecto(): void
    {
        $this->citasGeneradas = 0;
        $this->citasEfectivas = 0;
        $this->porcentajeEfectividad = 0;
        $this->citasDiferidas = 0;
        $this->citasCanceladas = 0;
        $this->porcentajeCancelacion = 0; // Changed from porcentajeNoShow
        $this->citasMantenimiento = 0;
        $this->citasMantenimientoPrepagados = 0;
        $this->porcentajeMantenimiento = 0;
        $this->porcentajePrepagados = 0;
        $this->cantidadUsuarios = 0;
        $this->datosCantidadCitas = ['labels' => [], 'generadas' => [], 'efectivas' => []];
        $this->datosTiempoPromedio = ['labels' => [], 'tiempos' => []];
    }

    public function aplicarFiltros(): void
    {
        // Procesar el rango de fechas si está presente
        if (! empty($this->rangoFechas)) {
            Log::info('[DashboardKpi] Rango original: ' . $this->rangoFechas);
            
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
                $this->fechaInicio = trim($fechas[0]);
                $this->fechaFin = trim($fechas[1]);
                
                Log::info('[DashboardKpi] Fechas parseadas - Inicio: ' . $this->fechaInicio . ', Fin: ' . $this->fechaFin);
                
                // Forzar la actualización del rango para mantener consistencia
                $this->rangoFechas = $this->fechaInicio . ' - ' . $this->fechaFin;
            } else {
                Log::warning('[DashboardKpi] No se pudo parsear el rango: ' . $this->rangoFechas);
                return; // No cargar datos si no se pudo parsear
            }
        }

        $this->cargarDatos();
    }

    /**
     * Se ejecuta cuando cambia el local seleccionado
     */
    public function updatedLocalSeleccionado(): void
    {
        $this->cargarDatos();
    }

    /**
     * Se ejecuta cuando cambia la marca seleccionada
     */
    public function updatedMarcaSeleccionada(): void
    {
        // Actualizar la lista de locales basada en la nueva marca
        $this->actualizarLocalesPorMarca();
        
        // Cargar datos con los nuevos filtros
        $this->cargarDatos();
    }

    /**
     * Se ejecuta cuando cambia el local seleccionado para gráficos
     */
    public function updatedLocalSeleccionadoGraficos(): void
    {
        $this->cargarDatos();
    }

    /**
     * Se ejecuta cuando cambia la marca seleccionada para gráficos
     */
    public function updatedMarcaSeleccionadaGraficos(): void
    {
        // Actualizar la lista de locales basada en la nueva marca para gráficos
        $this->actualizarLocalesPorMarcaGraficos();
        
        // Cargar datos con los nuevos filtros
        $this->cargarDatos();
    }

    /**
     * Se ejecuta cuando cambia el rango de fechas
     */
    public function updatedRangoFechas(): void
    {
        Log::info('[DashboardKpi] Rango de fechas actualizado: ' . $this->rangoFechas);
        
        // Solo procesar si el rango tiene contenido válido
        if (empty($this->rangoFechas)) {
            return;
        }
        
        // Evitar procesamiento si es solo una fecha (rango incompleto)
        if (!strpos($this->rangoFechas, ' - ') && !strpos($this->rangoFechas, ' a ') && !strpos($this->rangoFechas, ' to ')) {
            Log::info('[DashboardKpi] Rango incompleto, esperando segunda fecha...');
            return;
        }
        
        // Evitar loops infinitos - solo procesar si el rango realmente cambió
        $rangoAnterior = $this->fechaInicio . ' - ' . $this->fechaFin;
        if ($this->rangoFechas === $rangoAnterior) {
            Log::info('[DashboardKpi] Rango sin cambios, saltando procesamiento...');
            return;
        }
        
        $this->aplicarFiltros();
    }

    /**
     * Se ejecuta cuando cambia la fecha de inicio
     */
    public function updatedFechaInicio(): void
    {
        Log::info('[DashboardKpi] Fecha inicio actualizada: ' . $this->fechaInicio);
        $this->cargarDatos();
    }

    /**
     * Se ejecuta cuando cambia la fecha de fin
     */
    public function updatedFechaFin(): void
    {
        Log::info('[DashboardKpi] Fecha fin actualizada: ' . $this->fechaFin);
        $this->cargarDatos();
    }

    /**
     * Método público para obtener datos actualizados de los gráficos
     */
    public function getChartData(): array
    {
        return [
            'porcentajeMantenimiento' => $this->porcentajeMantenimiento,
            'porcentajePrepagados' => $this->porcentajePrepagados,
            'datosCantidadCitas' => $this->datosCantidadCitas,
        ];
    }


}
