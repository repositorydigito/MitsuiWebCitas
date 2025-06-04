<?php

namespace App\Filament\Pages;

use App\Models\Campana;
use App\Models\Local;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\WithPagination;

class Campanas extends Page
{
    use WithPagination;

    public int $page = 1;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Campañas';

    protected static ?string $title = 'Campañas';

    protected static string $view = 'filament.pages.campanas';

    protected static ?int $navigationSort = 20;

    // Propiedades para filtros
    public string $ciudadSeleccionada = '';

    public string $estadoSeleccionado = '';

    public string $fechaInicio = '';

    public string $fechaFin = '';

    public string $busqueda = '';

    public string $rangoFechas = '';

    // Datos de campañas
    public Collection $campanas;

    // Modal de detalle
    public bool $modalDetalleVisible = false;

    public array $campanaDetalle = [];

    // Opciones para filtros
    public array $ciudades = [];

    public array $estados = [
        'Activo',
        'Inactivo',
    ];

    protected $queryString = [
        'ciudadSeleccionada',
        'estadoSeleccionado',
        'fechaInicio',
        'fechaFin',
        'busqueda',
        'rangoFechas',
        'page',
    ];

    public function mount(): void
    {
        // Por defecto, no aplicamos ningún filtro de fecha
        // Solo inicializamos rangoFechas si ya tenemos fechas establecidas (por ejemplo, desde la URL)
        if (! empty($this->fechaInicio) && ! empty($this->fechaFin) && empty($this->rangoFechas)) {
            // Si tenemos fechas pero no rangoFechas, inicializar rangoFechas
            $this->rangoFechas = $this->fechaInicio.' - '.$this->fechaFin;
            Log::info("[CampanasPage] Inicializando rangoFechas desde fechas existentes: {$this->rangoFechas}");
        }

        // Cargar las ciudades (locales) desde la base de datos
        $this->cargarCiudades();

        // Cargar las campañas
        $this->cargarCampanas();
    }

    /**
     * Carga las ciudades (locales) desde la base de datos
     */
    private function cargarCiudades(): void
    {
        try {
            // Obtener los locales activos usando el método del modelo
            $localesActivos = Local::getActivosParaSelector();

            // Extraer solo los nombres (valores) del array asociativo
            $this->ciudades = array_values($localesActivos);

            Log::info('[CampanasPage] Ciudades cargadas: '.implode(', ', $this->ciudades));
        } catch (\Exception $e) {
            Log::error('[CampanasPage] Error al cargar ciudades: '.$e->getMessage());
            $this->ciudades = [];
        }
    }

    // El método irACargaCampanas ha sido eliminado y reemplazado por un enlace HTML directo

    /**
     * Método para cargar las campañas desde la base de datos
     */
    public function cargarCampanas(): void
    {
        try {
            Log::info('[CampanasPage] Iniciando carga de campañas');

            // Consulta base para obtener campañas con sus relaciones
            $query = Campana::with(['imagen', 'modelos', 'anos', 'locales']);

            // Filtrar por ciudad (local)
            if (! empty($this->ciudadSeleccionada)) {
                $query->whereHas('locales', function ($q) {
                    $local = Local::where('name', $this->ciudadSeleccionada)->first();
                    if ($local) {
                        $q->where('premise_code', $local->code);
                    }
                });

                Log::info("[CampanasPage] Filtrando por ciudad: {$this->ciudadSeleccionada}");
            }

            // Filtrar por estado
            if (! empty($this->estadoSeleccionado)) {
                $query->where('status', $this->estadoSeleccionado === 'Activo' ? 'active' : 'inactive');
            }

            // Filtrar por fecha
            if (! empty($this->fechaInicio) && ! empty($this->fechaFin)) {
                try {
                    // Convertir las fechas de string a objetos Carbon
                    $fechaInicio = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fechaInicio)->startOfDay();
                    $fechaFin = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fechaFin)->endOfDay();

                    // Convertir a formato Y-m-d para la consulta SQL
                    $fechaInicioSql = $fechaInicio->format('Y-m-d');
                    $fechaFinSql = $fechaFin->format('Y-m-d');

                    Log::info("[CampanasPage] Aplicando filtro de fechas en consulta SQL: {$fechaInicioSql} - {$fechaFinSql}");

                    // Aplicar el filtro de fechas directamente
                    $query->where(function ($q) use ($fechaInicioSql, $fechaFinSql) {
                        // La campaña está activa durante el período de filtro si:
                        // 1. La fecha de inicio de la campaña está dentro del período de filtro, o
                        // 2. La fecha de fin de la campaña está dentro del período de filtro, o
                        // 3. El período de filtro está completamente dentro del período de la campaña
                        $q->whereBetween('start_date', [$fechaInicioSql, $fechaFinSql])
                            ->orWhereBetween('end_date', [$fechaInicioSql, $fechaFinSql])
                            ->orWhere(function ($q2) use ($fechaInicioSql, $fechaFinSql) {
                                $q2->where('start_date', '<=', $fechaInicioSql)
                                    ->where('end_date', '>=', $fechaFinSql);
                            });
                    });

                    // Registrar la consulta SQL generada para depuración
                    $bindings = $query->getBindings();
                    $sqlWithBindings = str_replace(['?'], array_map(function ($binding) {
                        return is_string($binding) ? "'{$binding}'" : $binding;
                    }, $bindings), $query->toSql());

                    Log::info("[CampanasPage] Consulta SQL con filtro de fechas: {$sqlWithBindings}");
                    Log::info('[CampanasPage] Filtro de fechas aplicado correctamente en la consulta SQL');

                } catch (\Exception $e) {
                    Log::error('[CampanasPage] Error al procesar fechas: '.$e->getMessage());
                    Log::error('[CampanasPage] Stack trace: '.$e->getTraceAsString());

                    // Notificar al usuario del error
                    \Filament\Notifications\Notification::make()
                        ->title('Error en filtro de fechas')
                        ->body('Ha ocurrido un error al aplicar el filtro de fechas: '.$e->getMessage())
                        ->danger()
                        ->send();

                    // Limpiar el filtro de fechas
                    $this->fechaInicio = '';
                    $this->fechaFin = '';
                    $this->rangoFechas = '';
                }
            } else {
                Log::info('[CampanasPage] No se aplicó filtro de fechas en la consulta SQL (fechas vacías)');
            }

            // Filtrar por búsqueda
            if (! empty($this->busqueda)) {
                $busqueda = '%'.$this->busqueda.'%';
                $query->where(function ($q) use ($busqueda) {
                    $q->where('title', 'like', $busqueda)
                        ->orWhere('description', 'like', $busqueda);
                });
            }

            // Ordenar por fecha de inicio (más reciente primero)
            $query->orderBy('start_date', 'desc');

            // Ejecutar la consulta
            $campanas = $query->get();

            Log::info("[CampanasPage] Consulta ejecutada, obtenidas {$campanas->count()} campañas");

            // Transformar los resultados para que coincidan con el formato esperado en la vista
            $campanasTransformadas = collect();

            foreach ($campanas as $campana) {
                try {
                    // Obtener los nombres de los locales
                    $localesNombres = [];

                    // Obtener los locales directamente de la tabla pivote
                    $localesPivot = \DB::table('campaign_premises')
                        ->where('campaign_id', $campana->id)
                        ->get();

                    foreach ($localesPivot as $pivotItem) {
                        $localCodigo = $pivotItem->premise_code;
                        $local = Local::where('code', $localCodigo)->first();
                        if ($local) {
                            $localesNombres[] = $local->name;
                        } else {
                            $localesNombres[] = $localCodigo.' (No encontrado)';
                        }
                    }

                    // Si no se encontraron locales en la tabla pivote, intentar con la relación
                    if (empty($localesNombres) && $campana->locales && $campana->locales->count() > 0) {
                        foreach ($campana->locales as $local) {
                            if (isset($local->pivot) && isset($local->pivot->premise_code)) {
                                $nombreLocal = Local::where('code', $local->pivot->premise_code)->value('name');
                                $localesNombres[] = $nombreLocal ?? $local->pivot->premise_code;
                            }
                        }
                    }

                    // Registrar información sobre los locales encontrados
                    Log::info("[CampanasPage] Campaña {$campana->id} tiene ".count($localesNombres).' locales: '.implode(', ', $localesNombres));

                    // Obtener la URL de la imagen
                    $imagenUrl = 'https://via.placeholder.com/150';
                    if ($campana->imagen) {
                        try {
                            $imagenUrl = $this->getImageUrl($campana->imagen);
                        } catch (\Exception $e) {
                            Log::error('[CampanasPage] Error al obtener URL de imagen: '.$e->getMessage());
                        }
                    }

                    // Obtener los años directamente de la tabla pivote
                    $anosPivot = \DB::table('campaign_years')
                        ->where('campaign_id', $campana->id)
                        ->pluck('year')
                        ->toArray();

                    // Obtener los modelos
                    $modelosNombres = [];
                    if ($campana->modelos && $campana->modelos->count() > 0) {
                        $modelosNombres = $campana->modelos->pluck('name')->toArray();
                    }

                    // Crear el array con los datos de la campaña
                    $campanasTransformadas->push([
                        'codigo' => $campana->id, // Usar ID como código temporal
                        'nombre' => $campana->title,
                        'fecha_inicio' => $campana->start_date->format('d/m/Y'),
                        'fecha_fin' => $campana->end_date->format('d/m/Y'),
                        'estado' => $campana->status === 'active' ? 'Activo' : 'Inactivo',
                        // Campos adicionales para uso interno
                        'id' => $campana->id,
                        'modelos' => $modelosNombres,
                        'anos' => $anosPivot,
                        'locales' => $localesNombres,
                        'imagen' => $imagenUrl,
                    ]);
                } catch (\Exception $e) {
                    Log::error("[CampanasPage] Error al procesar campaña {$campana->id}: ".$e->getMessage());
                    // Continuamos con la siguiente campaña
                }
            }

            $this->campanas = $campanasTransformadas;

            Log::info("[CampanasPage] Campañas transformadas: {$campanasTransformadas->count()}");
        } catch (\Exception $e) {
            Log::error('[CampanasPage] Error al cargar campañas: '.$e->getMessage()."\nTrace: ".$e->getTraceAsString());
            $this->campanas = collect();
            \Filament\Notifications\Notification::make()
                ->title('Error al Cargar Campañas')
                ->body('No se pudo obtener la lista de campañas: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function filtrarCampanas(): Collection
    {
        return $this->campanas->filter(function ($campana) {
            // Verificar si la ciudad seleccionada está en el array de locales de la campaña
            $pasaFiltroCiudad = empty($this->ciudadSeleccionada) ||
                (isset($campana['locales']) && is_array($campana['locales']) &&
                in_array($this->ciudadSeleccionada, $campana['locales']));

            // Registrar información de depuración para el filtro de ciudad
            if (! empty($this->ciudadSeleccionada)) {
                Log::debug("[CampanasPage] Filtro de ciudad para campaña {$campana['codigo']}: ".
                    "Ciudad seleccionada: {$this->ciudadSeleccionada}, ".
                    'Locales de la campaña: '.(isset($campana['locales']) ? implode(', ', $campana['locales']) : 'Ninguno').', '.
                    'Resultado: '.($pasaFiltroCiudad ? 'Pasa' : 'No pasa'));
            }

            $pasaFiltroEstado = empty($this->estadoSeleccionado) || $campana['estado'] === $this->estadoSeleccionado;
            $pasaFiltroBusqueda = empty($this->busqueda) ||
                str_contains(strtolower($campana['codigo']), strtolower($this->busqueda)) ||
                str_contains(strtolower($campana['nombre']), strtolower($this->busqueda));

            // Filtro de fechas
            $pasaFiltroFechas = true; // Por defecto, todas las campañas pasan el filtro de fechas

            // Solo aplicamos el filtro de fechas si se ha seleccionado un rango
            if (! empty($this->fechaInicio) && ! empty($this->fechaFin)) {
                try {
                    // Convertir las fechas de string a objetos Carbon
                    $fechaInicioCampana = \Carbon\Carbon::createFromFormat('d/m/Y', $campana['fecha_inicio'])->startOfDay();
                    $fechaFinCampana = \Carbon\Carbon::createFromFormat('d/m/Y', $campana['fecha_fin'])->endOfDay();
                    $fechaInicioFiltro = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fechaInicio)->startOfDay();
                    $fechaFinFiltro = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fechaFin)->endOfDay();

                    // Verificar si hay superposición de fechas
                    // Una campaña pasa el filtro si:
                    // - Su fecha de inicio está dentro del rango de filtro, o
                    // - Su fecha de fin está dentro del rango de filtro, o
                    // - El rango de filtro está completamente dentro del rango de la campaña
                    $pasaFiltroFechas = (
                        // Fecha inicio de campaña está en el rango de filtro
                        ($fechaInicioCampana->gte($fechaInicioFiltro) && $fechaInicioCampana->lte($fechaFinFiltro)) ||
                        // Fecha fin de campaña está en el rango de filtro
                        ($fechaFinCampana->gte($fechaInicioFiltro) && $fechaFinCampana->lte($fechaFinFiltro)) ||
                        // El rango de filtro está dentro del rango de la campaña
                        ($fechaInicioCampana->lte($fechaInicioFiltro) && $fechaFinCampana->gte($fechaFinFiltro))
                    );

                    Log::info("[CampanasPage] Filtro de fechas para campaña {$campana['codigo']}: ".
                        "Campaña: {$fechaInicioCampana->format('d/m/Y')} - {$fechaFinCampana->format('d/m/Y')}, ".
                        "Filtro: {$fechaInicioFiltro->format('d/m/Y')} - {$fechaFinFiltro->format('d/m/Y')}, ".
                        'Resultado: '.($pasaFiltroFechas ? 'Pasa' : 'No pasa'));

                } catch (\Exception $e) {
                    // Si hay un error en el formato de fecha, no aplicamos el filtro
                    Log::error('[CampanasPage] Error al filtrar por fechas: '.$e->getMessage());
                    Log::error('[CampanasPage] Stack trace: '.$e->getTraceAsString());

                    // Mostrar notificación al usuario
                    \Filament\Notifications\Notification::make()
                        ->title('Error en filtro de fechas')
                        ->body('Ha ocurrido un error al aplicar el filtro de fechas: '.$e->getMessage())
                        ->danger()
                        ->send();
                }
            } else {
                // Si no hay fechas seleccionadas, todas las campañas pasan el filtro
                Log::debug("[CampanasPage] No hay filtro de fechas aplicado para campaña {$campana['codigo']}");
            }

            return $pasaFiltroCiudad && $pasaFiltroEstado && $pasaFiltroBusqueda && $pasaFiltroFechas;
        });
    }

    /**
     * Procesa el rango de fechas seleccionado en el datepicker
     */
    public function aplicarFiltroFechas(): void
    {
        Log::info('[CampanasPage] Aplicando filtro de fechas con rangoFechas: '.$this->rangoFechas);
        $this->aplicarFiltroFechasDirecto($this->rangoFechas);
    }

    /**
     * Procesa directamente un string de rango de fechas
     */
    public function aplicarFiltroFechasDirecto(string $rangoFechas): void
    {
        Log::info('[CampanasPage] Aplicando filtro de fechas directo con rangoFechas: '.$rangoFechas);

        // Actualizar el valor del modelo
        $this->rangoFechas = $rangoFechas;

        if (! empty($rangoFechas)) {
            try {
                $fechas = explode(' - ', $rangoFechas);
                Log::info('[CampanasPage] Fechas extraídas del rango: '.json_encode($fechas));

                if (count($fechas) === 2) {
                    $this->fechaInicio = trim($fechas[0]);
                    $this->fechaFin = trim($fechas[1]);
                } elseif (count($fechas) === 1 && ! empty(trim($fechas[0]))) {
                    $this->fechaInicio = trim($fechas[0]);
                    $this->fechaFin = trim($fechas[0]);
                } else {
                    throw new \Exception('Formato de rango de fechas inesperado');
                }

                // Validar el formato de las fechas
                $fechaInicioObj = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fechaInicio);
                $fechaFinObj = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fechaFin);

                // Si la fecha de fin es anterior a la fecha de inicio, intercambiarlas
                if ($fechaFinObj->lt($fechaInicioObj)) {
                    $temp = $this->fechaInicio;
                    $this->fechaInicio = $this->fechaFin;
                    $this->fechaFin = $temp;
                }

                Log::info("[CampanasPage] Fechas procesadas: Inicio = {$this->fechaInicio}, Fin = {$this->fechaFin}");

                // Notificar al usuario
                if ($this->fechaInicio === $this->fechaFin) {
                    \Filament\Notifications\Notification::make()
                        ->title('Filtro de fechas aplicado')
                        ->body("Mostrando campañas del día {$this->fechaInicio}")
                        ->success()
                        ->send();
                } else {
                    \Filament\Notifications\Notification::make()
                        ->title('Filtro de fechas aplicado')
                        ->body("Mostrando campañas del {$this->fechaInicio} al {$this->fechaFin}")
                        ->success()
                        ->send();
                }

                // Forzar la recarga de campañas
                $this->cargarCampanas();

            } catch (\Exception $e) {
                Log::error('[CampanasPage] Error al procesar rango de fechas: '.$e->getMessage());

                // Notificar al usuario del error
                \Filament\Notifications\Notification::make()
                    ->title('Error en filtro de fechas')
                    ->body('El formato de fechas no es válido. Por favor, seleccione un rango de fechas válido.')
                    ->danger()
                    ->send();

                // Limpiar el filtro de fechas
                $this->fechaInicio = '';
                $this->fechaFin = '';
                $this->rangoFechas = '';

                // Recargar las campañas sin filtro
                $this->cargarCampanas();
            }
        } else {
            // Limpiar el filtro de fechas
            $this->fechaInicio = '';
            $this->fechaFin = '';
            $this->rangoFechas = '';

            // Notificar al usuario
            \Filament\Notifications\Notification::make()
                ->title('Filtro de fechas limpiado')
                ->body('Mostrando todas las campañas sin filtro de fechas')
                ->success()
                ->send();

            // Recargar las campañas sin filtro
            $this->cargarCampanas();
        }

        $this->resetPage();
    }

    public function getCampanasPaginadasProperty(): LengthAwarePaginator
    {
        $campanasFiltradas = $this->filtrarCampanas();
        $perPage = 5;

        // Usar la propiedad page del componente en lugar de obtenerla de la URL
        // Esto permite que los botones de paginación funcionen correctamente
        $page = $this->page;

        // Asegurarnos de que la página sea un número válido
        $page = max(1, intval($page));

        // Crear el paginador con la configuración adecuada
        $paginador = new LengthAwarePaginator(
            $campanasFiltradas->forPage($page, $perPage),
            $campanasFiltradas->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );

        // Registrar información para depuración
        Log::info("[CampanasPage] Paginación: Página actual = {$page}, Total items = {$campanasFiltradas->count()}, Items por página = {$perPage}");

        return $paginador;
    }

    public function aplicarFiltros(): void
    {
        $this->resetPage();
    }

    /**
     * Método para ir a una página específica
     * Este método es llamado por los botones de paginación en la vista
     */
    public function gotoPage($page)
    {
        // Asegurarnos de que la página sea un número válido
        $page = max(1, intval($page));

        // Actualizar la propiedad page del componente
        $this->page = $page;

        // Registrar información para depuración
        Log::info("[CampanasPage] Cambiando a página: {$page}");

        // Forzar la actualización del componente
        $this->dispatch('refresh');
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['ciudadSeleccionada', 'estadoSeleccionado', 'busqueda', 'rangoFechas', 'fechaInicio', 'fechaFin']);
        $this->resetPage();
        $this->cargarCampanas();
    }

    /**
     * Limpia solo el filtro de fechas
     */
    public function limpiarFiltroFechas(): void
    {
        Log::info('[CampanasPage] Limpiando filtro de fechas');

        // Limpiar los valores
        $this->rangoFechas = '';
        $this->fechaInicio = '';
        $this->fechaFin = '';

        // Notificar al usuario que el filtro se ha limpiado
        \Filament\Notifications\Notification::make()
            ->title('Filtro de fechas limpiado')
            ->body('Mostrando todas las campañas sin filtro de fechas')
            ->success()
            ->send();

        // Recargar las campañas sin filtro de fechas
        $this->cargarCampanas();
        $this->resetPage();
    }

    /**
     * Método para ver detalle de campaña usando JavaScript
     * Este método es llamado desde el botón "Ver detalle" en la vista
     */
    public function verDetalleJS($codigo): void
    {
        try {
            Log::info("[CampanasPage] Intentando ver detalle de campaña con JavaScript, código: {$codigo}");

            // Obtener la campaña con sus relaciones
            $campana = Campana::with(['imagen', 'modelos', 'anos', 'locales'])->where('id', $codigo)->first();

            if (! $campana) {
                Log::warning("[CampanasPage] Campaña no encontrada con código: {$codigo}");
                $this->dispatch('mostrarNotificacion', [
                    'tipo' => 'error',
                    'titulo' => 'Campaña no encontrada',
                    'mensaje' => "No se encontró la campaña con código: {$codigo}",
                ]);

                return;
            }

            // Procesar los datos de la campaña (similar a verDetalle)
            $this->procesarDatosCampana($campana);

            // Mostrar el modal sin recargar la página
            $this->modalDetalleVisible = true;

            Log::info("[CampanasPage] Modal mostrado correctamente para campaña: {$codigo}");
        } catch (\Exception $e) {
            Log::error('[CampanasPage] Error al ver detalle de campaña con JavaScript: '.$e->getMessage());
            $this->dispatch('mostrarNotificacion', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Ha ocurrido un error al intentar ver el detalle de la campaña: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Método para procesar los datos de una campaña
     * Este método es utilizado por verDetalleJS
     */
    private function procesarDatosCampana($campana): void
    {
        try {
            // Obtener los nombres de los locales
            $localesNombres = [];

            // Obtener los locales directamente de la tabla pivote
            $localesPivot = \DB::table('campaign_premises')
                ->where('campaign_id', $campana->id)
                ->get();

            foreach ($localesPivot as $pivotItem) {
                $localCodigo = $pivotItem->premise_code;
                $local = Local::where('code', $localCodigo)->first();
                if ($local) {
                    $localesNombres[] = $local->name;
                } else {
                    $localesNombres[] = $localCodigo.' (No encontrado)';
                }
            }

            // Si no se encontraron locales en la tabla pivote, intentar con la relación
            if (empty($localesNombres) && $campana->locales && $campana->locales->count() > 0) {
                foreach ($campana->locales as $local) {
                    if (isset($local->pivot) && isset($local->pivot->premise_code)) {
                        $nombreLocal = Local::where('code', $local->pivot->premise_code)->value('name');
                        $localesNombres[] = $nombreLocal ?? $local->pivot->premise_code;
                    }
                }
            }

            // Obtener la URL de la imagen
            $imagenUrl = null;
            if ($campana->imagen) {
                try {
                    $imagenUrl = $this->getImageUrl($campana->imagen);
                } catch (\Exception $e) {
                    Log::error('[CampanasPage] Error al obtener URL de imagen para detalle: '.$e->getMessage());
                }
            }

            // Obtener los años directamente de la tabla pivote
            $anosPivot = \DB::table('campaign_years')
                ->where('campaign_id', $campana->id)
                ->pluck('year')
                ->toArray();

            // Obtener los modelos
            $modelosNombres = [];
            if ($campana->modelos && $campana->modelos->count() > 0) {
                $modelosNombres = $campana->modelos->pluck('name')->toArray();
            }

            // Preparar los datos para el modal
            $this->campanaDetalle = [
                'id' => $campana->id,
                'codigo' => $campana->id, // Usar ID como código temporal
                'nombre' => $campana->title,
                'fecha_inicio' => $campana->start_date->format('d/m/Y'),
                'fecha_fin' => $campana->end_date->format('d/m/Y'),
                'estado' => $campana->status === 'active' ? 'Activo' : 'Inactivo',
                'modelos' => $modelosNombres,
                'anos' => $anosPivot,
                'locales' => $localesNombres,
                'imagen' => $imagenUrl,
            ];

            Log::info("[CampanasPage] Datos de campaña procesados correctamente: {$campana->id}");
        } catch (\Exception $e) {
            Log::error('[CampanasPage] Error al procesar datos de campaña: '.$e->getMessage());
            throw $e; // Re-lanzar la excepción para que sea capturada por el método que llamó a este
        }
    }

    /**
     * Método original para ver detalle de campaña (mantenido por compatibilidad)
     */
    public function verDetalle($codigo): void
    {
        try {
            Log::info("[CampanasPage] Intentando ver detalle de campaña con código: {$codigo}");

            $campana = Campana::with(['imagen', 'modelos', 'anos', 'locales'])->where('id', $codigo)->first();

            if ($campana) {
                try {
                    // Obtener los nombres de los locales
                    $localesNombres = [];

                    // Registrar información detallada para depuración
                    Log::info("[CampanasPage] Campaña ID: {$campana->id}");
                    Log::info('[CampanasPage] Relación locales cargada: '.($campana->relationLoaded('locales') ? 'Sí' : 'No'));

                    if ($campana->relationLoaded('locales')) {
                        Log::info('[CampanasPage] Cantidad de locales relacionados: '.$campana->locales->count());
                    }

                    // Obtener los locales directamente de la tabla pivote para mayor seguridad
                    $localesPivot = \DB::table('campaign_premises')
                        ->where('campaign_id', $campana->id)
                        ->get();

                    Log::info('[CampanasPage] Locales encontrados en tabla pivote: '.$localesPivot->count());

                    foreach ($localesPivot as $pivotItem) {
                        $localCodigo = $pivotItem->premise_code;
                        Log::info("[CampanasPage] Procesando local con código: {$localCodigo}");

                        $local = Local::where('code', $localCodigo)->first();
                        if ($local) {
                            $localesNombres[] = $local->name;
                            Log::info("[CampanasPage] Local encontrado: {$local->name}");
                        } else {
                            $localesNombres[] = $localCodigo.' (No encontrado)';
                            Log::warning("[CampanasPage] Local no encontrado con código: {$localCodigo}");
                        }
                    }

                    // Si no se encontraron locales en la tabla pivote, intentar con la relación
                    if (empty($localesNombres) && $campana->locales && $campana->locales->count() > 0) {
                        Log::info('[CampanasPage] Intentando obtener locales desde la relación');
                        foreach ($campana->locales as $local) {
                            if (isset($local->pivot) && isset($local->pivot->premise_code)) {
                                $nombreLocal = Local::where('code', $local->pivot->premise_code)->value('name');
                                $localesNombres[] = $nombreLocal ?? $local->pivot->premise_code;
                                Log::info('[CampanasPage] Local desde relación: '.($nombreLocal ?? $local->pivot->premise_code));
                            }
                        }
                    }

                    Log::info('[CampanasPage] Total de locales obtenidos: '.count($localesNombres));
                    Log::info('[CampanasPage] Locales: '.implode(', ', $localesNombres));

                    // Obtener la URL de la imagen
                    $imagenUrl = null;
                    if ($campana->imagen) {
                        try {
                            $imagenUrl = $this->getImageUrl($campana->imagen);
                        } catch (\Exception $e) {
                            Log::error('[CampanasPage] Error al obtener URL de imagen para detalle: '.$e->getMessage());
                        }
                    }

                    // Obtener los años directamente de la tabla pivote
                    $anosPivot = \DB::table('campaign_years')
                        ->where('campaign_id', $campana->id)
                        ->pluck('year')
                        ->toArray();

                    Log::info('[CampanasPage] Años encontrados en tabla pivote: '.count($anosPivot));
                    Log::info('[CampanasPage] Años: '.implode(', ', $anosPivot));

                    // Obtener los modelos
                    $modelosNombres = [];
                    if ($campana->modelos && $campana->modelos->count() > 0) {
                        $modelosNombres = $campana->modelos->pluck('nombre')->toArray();
                        Log::info('[CampanasPage] Modelos encontrados: '.count($modelosNombres));
                        Log::info('[CampanasPage] Modelos: '.implode(', ', $modelosNombres));
                    }

                    // Preparar los datos para el modal
                    $this->campanaDetalle = [
                        'id' => $campana->id,
                        'codigo' => $campana->codigo,
                        'nombre' => $campana->titulo,
                        'fecha_inicio' => $campana->start_date->format('d/m/Y'),
                        'fecha_fin' => $campana->end_date->format('d/m/Y'),
                        'estado' => $campana->estado,
                        'modelos' => $modelosNombres,
                        'anos' => $anosPivot,
                        'locales' => $localesNombres,
                        'imagen' => $imagenUrl,
                    ];

                    // Mostrar el modal
                    $this->modalDetalleVisible = true;

                    Log::info("[CampanasPage] Mostrando detalle de campaña: {$campana->codigo}");
                } catch (\Exception $e) {
                    Log::error("[CampanasPage] Error al procesar detalle de campaña {$campana->id}: ".$e->getMessage());
                    throw $e; // Re-lanzar la excepción para que sea capturada por el bloque catch exterior
                }
            } else {
                Log::warning("[CampanasPage] Campaña no encontrada con código: {$codigo}");
                \Filament\Notifications\Notification::make()
                    ->title('Campaña no encontrada')
                    ->body("No se encontró la campaña con código: {$codigo}")
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('[CampanasPage] Error al ver detalle de campaña: '.$e->getMessage()."\nTrace: ".$e->getTraceAsString());
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al intentar ver el detalle de la campaña: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Cierra el modal de detalle
     * Este método ya no se usa directamente desde la vista, pero se mantiene por compatibilidad
     */
    public function cerrarModalDetalle(): void
    {
        // Simplemente cerrar el modal sin hacer nada más
        $this->modalDetalleVisible = false;
        $this->campanaDetalle = [];

        Log::info('[CampanasPage] Cerrando modal desde método PHP');
    }

    public function editar($codigo)
    {
        try {
            Log::info("[CampanasPage] Intentando editar campaña con código: {$codigo}");

            $campana = Campana::where('codigo', $codigo)->first();

            if (! $campana) {
                Log::warning("[CampanasPage] Campaña no encontrada con código: {$codigo}");
                \Filament\Notifications\Notification::make()
                    ->title('Campaña no encontrada')
                    ->body("No se encontró la campaña con código: {$codigo}")
                    ->danger()
                    ->send();

                return;
            }

            // Construir la URL para la redirección
            $url = '/admin/crear-campana?campana_id='.$campana->id;

            Log::info("[CampanasPage] Redirigiendo a edición de campaña: {$campana->codigo} con ID: {$campana->id} - URL: {$url}");

            // Usar el método redirect de Livewire
            return $this->redirect($url);

        } catch (\Exception $e) {
            Log::error('[CampanasPage] Error al editar campaña: '.$e->getMessage()."\nTrace: ".$e->getTraceAsString());
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al intentar editar la campaña: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Genera la URL correcta para una imagen de campaña
     */
    private function getImageUrl($imagen): string
    {
        // Registrar la ruta original para depuración
        Log::info('[CampanasPage] Ruta de imagen original: '.$imagen->image_path);

        // Usar la ruta de imagen.campana para generar la URL, igual que en AgendarCita
        $url = route('imagen.campana', ['id' => $imagen->campaign_id]);

        // Registrar la URL generada para depuración
        Log::info('[CampanasPage] URL generada para imagen: '.$url);

        // Verificar si el archivo existe en diferentes ubicaciones
        $rutasAVerificar = [
            storage_path('app/'.$imagen->image_path),
            storage_path('app/private/'.$imagen->image_path),
            storage_path('app/private/public/images/campanas/'.basename($imagen->image_path)),
            public_path($imagen->image_path),
        ];

        $existe = false;
        foreach ($rutasAVerificar as $ruta) {
            if (file_exists($ruta)) {
                $existe = true;
                break;
            }
        }

        Log::info('[CampanasPage] Verificación de archivo: '.json_encode([
            'rutasVerificadas' => $rutasAVerificar,
            'existe' => $existe ? 'Sí' : 'No',
        ]));

        // Devolver la URL
        return $url;
    }

    public function eliminar($codigo): void
    {
        try {
            $campana = Campana::where('id', $codigo)->first();

            if ($campana) {
                // Eliminar la imagen si existe
                $imagen = $campana->imagen;
                if ($imagen) {
                    // Eliminar el archivo físico
                    $rutaCompleta = public_path($imagen->image_path);
                    if (file_exists($rutaCompleta)) {
                        unlink($rutaCompleta);
                        Log::info("[CampanasPage] Archivo eliminado: {$rutaCompleta}");
                    } else {
                        Log::warning("[CampanasPage] No se pudo eliminar el archivo porque no existe: {$rutaCompleta}");
                    }
                }

                // Eliminar la campaña y sus relaciones (las relaciones se eliminarán automáticamente por las restricciones de clave foránea)
                $campana->delete();

                \Filament\Notifications\Notification::make()
                    ->title('Campaña eliminada')
                    ->body('La campaña ha sido eliminada correctamente.')
                    ->success()
                    ->send();

                // Recargar las campañas
                $this->cargarCampanas();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Campaña no encontrada')
                    ->body("No se encontró la campaña con código: {$codigo}")
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('[CampanasPage] Error al eliminar campaña: '.$e->getMessage());
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al intentar eliminar la campaña.')
                ->danger()
                ->send();
        }
    }
}
