<?php

namespace App\Filament\Pages;

use App\Models\Campana;
use App\Models\Local;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithPagination;

class Campanas extends Page
{
    use WithPagination, HasPageShield;

    public int $page = 1;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationLabel = 'Campañas';
    
    protected static ?string $navigationGroup = '📢 Marketing';
    
    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Campañas';

    protected static string $view = 'filament.pages.campanas';

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
        if (! empty($this->fechaInicio) && ! empty($this->fechaFin) && empty($this->rangoFechas)) {
            $this->rangoFechas = $this->fechaInicio.' - '.$this->fechaFin;
            Log::info("[CampanasPage] Inicializando rangoFechas desde fechas existentes: {$this->rangoFechas}");
        }
        $this->cargarCiudades();
        $this->cargarCampanas();
    }

    private function cargarCiudades(): void
    {
        try {
            $localesActivos = Local::getActivosParaSelector();
            $this->ciudades = array_values($localesActivos);
        } catch (\Exception $e) {
            $this->ciudades = [];
        }
    }

    public function cargarCampanas(): void
    {
        try {
            $query = Campana::with(['imagen', 'modelos', 'anos', 'locales']);
            if (! empty($this->ciudadSeleccionada)) {
                $query->whereHas('locales', function ($q) {
                    $local = Local::where('name', $this->ciudadSeleccionada)->first();
                    if ($local) {
                        $q->where('premise_code', $local->code);
                    }
                });
            }

            // Filtrar por estado
            if (! empty($this->estadoSeleccionado)) {
                $query->where('status', $this->estadoSeleccionado);
            }

            // Filtrar por fecha
            if (! empty($this->fechaInicio) && ! empty($this->fechaFin)) {
                try {
                    $fechaInicio = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fechaInicio)->startOfDay();
                    $fechaFin = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fechaFin)->endOfDay();

                    $fechaInicioSql = $fechaInicio->format('Y-m-d');
                    $fechaFinSql = $fechaFin->format('Y-m-d');

                    $query->where(function ($q) use ($fechaInicioSql, $fechaFinSql) {
                        $q->whereBetween('start_date', [$fechaInicioSql, $fechaFinSql])
                            ->orWhereBetween('end_date', [$fechaInicioSql, $fechaFinSql])
                            ->orWhere(function ($q2) use ($fechaInicioSql, $fechaFinSql) {
                                $q2->where('start_date', '<=', $fechaInicioSql)
                                    ->where('end_date', '>=', $fechaFinSql);
                            });
                    });
                    $bindings = $query->getBindings();
                    $sqlWithBindings = str_replace(['?'], array_map(function ($binding) {
                        return is_string($binding) ? "'{$binding}'" : $binding;
                    }, $bindings), $query->toSql());

                } catch (\Exception $e) {
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
            $query->orderBy('start_date', 'desc');
            $campanas = $query->get();
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
                        'codigo' => $campana->code, // Usar el código real de la campaña
                        'nombre' => $campana->title,
                        'fecha_inicio' => $campana->start_date->format('d/m/Y'),
                        'fecha_fin' => $campana->end_date->format('d/m/Y'),
                        'estado' => $campana->status, // Ya viene como 'Activo' o 'Inactivo' desde la BD
                        // Campos adicionales para uso interno
                        'id' => $campana->id,
                        'modelos' => $modelosNombres,
                        'anos' => $anosPivot,
                        'locales' => $localesNombres,
                        'imagen' => $imagenUrl,
                    ]);
                } catch (\Exception $e) {
                    Log::error("[CampanasPage] Error al procesar campaña {$campana->id}: ".$e->getMessage());
                }
            }

            $this->campanas = $campanasTransformadas;
        } catch (\Exception $e) {
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

    public function aplicarFiltroFechas(): void
    {
        $this->aplicarFiltroFechasDirecto($this->rangoFechas);
    }

    public function aplicarFiltroFechasDirecto(string $rangoFechas): void
    {
        $this->rangoFechas = $rangoFechas;
        if (! empty($rangoFechas)) {
            try {
                $fechas = explode(' - ', $rangoFechas);
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
        $page = $this->page;
        $page = max(1, intval($page));

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

        return $paginador;
    }

    public function aplicarFiltros(): void
    {
        $this->resetPage();
    }

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

    public function limpiarFiltroFechas(): void
    {
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

    public function verDetalleJS($codigo): void
    {
        try {
            $campana = Campana::with(['imagen', 'modelos', 'anos', 'locales'])->where('code', $codigo)->first();

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
        } catch (\Exception $e) {
            $this->dispatch('mostrarNotificacion', [
                'tipo' => 'error',
                'titulo' => 'Error',
                'mensaje' => 'Ha ocurrido un error al intentar ver el detalle de la campaña: '.$e->getMessage(),
            ]);
        }
    }

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
            } else {
                Log::info('[CampanasPage] No hay imagen asociada a la campaña');
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
                'codigo' => $campana->code, // Usar el código real de la campaña
                'nombre' => $campana->title,
                'fecha_inicio' => $campana->start_date->format('d/m/Y'),
                'fecha_fin' => $campana->end_date->format('d/m/Y'),
                'estado' => $campana->status, // Ya viene como 'Activo' o 'Inactivo' desde la BD
                'modelos' => $modelosNombres,
                'anos' => $anosPivot,
                'locales' => $localesNombres,
                'imagen' => $imagenUrl,
            ];
        } catch (\Exception $e) {
            throw $e; // Re-lanzar la excepción para que sea capturada por el método que llamó a este
        }
    }

    public function verDetalle($codigo): void
    {
        try {
            $campana = Campana::with(['imagen', 'modelos', 'anos', 'locales'])->where('code', $codigo)->first();
            if ($campana) {
                try {
                    $localesNombres = [];
                    if ($campana->relationLoaded('locales')) {
                        Log::info('[CampanasPage] Cantidad de locales relacionados: '.$campana->locales->count());
                    }
                    // Obtener los locales directamente de la tabla pivote para mayor seguridad
                    $localesPivot = \DB::table('campaign_premises')
                        ->where('campaign_id', $campana->id)
                        ->get();

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
                        $modelosNombres = $campana->modelos->pluck('nombre')->toArray();
                        Log::info('[CampanasPage] Modelos encontrados: '.count($modelosNombres));
                        Log::info('[CampanasPage] Modelos: '.implode(', ', $modelosNombres));
                    }

                    // Preparar los datos para el modal
                    $this->campanaDetalle = [
                        'id' => $campana->id,
                        'codigo' => $campana->code,
                        'nombre' => $campana->title,
                        'fecha_inicio' => $campana->start_date->format('d/m/Y'),
                        'fecha_fin' => $campana->end_date->format('d/m/Y'),
                        'estado' => $campana->status,
                        'modelos' => $modelosNombres,
                        'anos' => $anosPivot,
                        'locales' => $localesNombres,
                        'imagen' => $imagenUrl,
                    ];

                    // Mostrar el modal
                    $this->modalDetalleVisible = true;
                } catch (\Exception $e) {
                    throw $e; 
                }
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Campaña no encontrada')
                    ->body("No se encontró la campaña con código: {$codigo}")
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al intentar ver el detalle de la campaña: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cerrarModalDetalle(): void
    {
        // Simplemente cerrar el modal sin hacer nada más
        $this->modalDetalleVisible = false;
        $this->campanaDetalle = [];
    }

    public function editar($codigo)
    {
        try {
            $campana = Campana::where('code', $codigo)->first();

            if (! $campana) { 
                \Filament\Notifications\Notification::make()
                    ->title('Campaña no encontrada')
                    ->body("No se encontró la campaña con código: {$codigo}")
                    ->danger()
                    ->send();

                return;
            }
            $url = '/admin/crear-campana?campana_id='.$campana->id;
            $this->redirect($url);

        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al intentar editar la campaña: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    private function getImageUrl($imagen): string
    {
        $rutaOriginal = $imagen->route;

        // Verificar si la imagen está en la carpeta private (imágenes antiguas)
        if (str_contains($rutaOriginal, 'private/public/')) {
            // Para imágenes en private, crear una ruta especial
            $nombreArchivo = basename($rutaOriginal);
            $url = route('imagen.campana', ['idOrFilename' => $nombreArchivo]);
            return $url;
        }

        // Para imágenes nuevas en public
        $rutaLimpia = str_replace('public/', '', $rutaOriginal);
        $url = asset('storage/' . $rutaLimpia);
        return $url;
    }

    public function eliminar($codigo): void
    {
        try {
            $campana = Campana::where('code', $codigo)->first();

            if ($campana) {
                // Eliminar la imagen si existe
                $imagen = $campana->imagen;
                if ($imagen) {
                    // Eliminar el archivo físico usando Storage
                    try {
                        // Limpiar la ruta para Storage
                        $rutaLimpia = str_replace('public/', '', $imagen->route);
                        if (Storage::exists($rutaLimpia)) {
                            Storage::delete($rutaLimpia);
                            Log::info("[CampanasPage] Archivo eliminado: {$rutaLimpia}");
                        } else {
                            Log::warning("[CampanasPage] El archivo no existe: {$rutaLimpia}");
                        }
                    } catch (\Exception $e) {
                        Log::warning("[CampanasPage] Error al eliminar archivo: {$imagen->route} - ".$e->getMessage());
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
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al intentar eliminar la campaña.')
                ->danger()
                ->send();
        }
    }
}
