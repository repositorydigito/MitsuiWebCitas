<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Livewire\WithPagination;
use App\Models\Local;
use App\Models\Campana;

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
        if (!empty($this->fechaInicio) && !empty($this->fechaFin) && empty($this->rangoFechas)) {
            // Si tenemos fechas pero no rangoFechas, inicializar rangoFechas
            $this->rangoFechas = $this->fechaInicio . ' - ' . $this->fechaFin;
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
            // Obtener los locales activos
            $localesActivos = Local::where('activo', true)
                ->orderBy('nombre')
                ->get();

            // Convertir a array de nombres
            $this->ciudades = $localesActivos->pluck('nombre')->toArray();
        } catch (\Exception $e) {
            Log::error("[CampanasPage] Error al cargar ciudades: " . $e->getMessage());
            $this->ciudades = [];
        }
    }

    public function cargarCampanas(): void
    {
        try {
            Log::info("[CampanasPage] Iniciando carga de campañas");

            // Consulta base para obtener campañas con sus relaciones
            $query = Campana::with(['imagen', 'modelos', 'anos', 'locales']);

            // Filtrar por ciudad (local)
            if (!empty($this->ciudadSeleccionada)) {
                $query->whereHas('locales', function ($q) {
                    $local = Local::where('nombre', $this->ciudadSeleccionada)->first();
                    if ($local) {
                        $q->where('local_codigo', $local->codigo);
                    }
                });
            }

            // Filtrar por estado
            if (!empty($this->estadoSeleccionado)) {
                $query->where('estado', $this->estadoSeleccionado);
            }

            // Filtrar por fecha
            if (!empty($this->fechaInicio) && !empty($this->fechaFin)) {
                try {
                    $fechaInicio = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fechaInicio)->format('Y-m-d');
                    $fechaFin = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fechaFin)->format('Y-m-d');

                    $query->where(function ($q) use ($fechaInicio, $fechaFin) {
                        // La campaña está activa durante el período de filtro si:
                        // 1. La fecha de inicio de la campaña está dentro del período de filtro, o
                        // 2. La fecha de fin de la campaña está dentro del período de filtro, o
                        // 3. El período de filtro está completamente dentro del período de la campaña
                        $q->whereBetween('fecha_inicio', [$fechaInicio, $fechaFin])
                            ->orWhereBetween('fecha_fin', [$fechaInicio, $fechaFin])
                            ->orWhere(function ($q2) use ($fechaInicio, $fechaFin) {
                                $q2->where('fecha_inicio', '<=', $fechaInicio)
                                    ->where('fecha_fin', '>=', $fechaFin);
                            });
                    });
                } catch (\Exception $e) {
                    Log::error("[CampanasPage] Error al procesar fechas: " . $e->getMessage());
                    // No aplicamos el filtro de fechas si hay un error
                }
            }

            // Filtrar por búsqueda
            if (!empty($this->busqueda)) {
                $busqueda = '%' . $this->busqueda . '%';
                $query->where(function ($q) use ($busqueda) {
                    $q->where('codigo', 'like', $busqueda)
                        ->orWhere('titulo', 'like', $busqueda);
                });
            }

            // Ordenar por fecha de inicio (más reciente primero)
            $query->orderBy('fecha_inicio', 'desc');

            // Ejecutar la consulta
            $campanas = $query->get();

            Log::info("[CampanasPage] Consulta ejecutada, obtenidas {$campanas->count()} campañas");

            // Transformar los resultados para que coincidan con el formato esperado en la vista
            $campanasTransformadas = collect();

            foreach ($campanas as $campana) {
                try {
                    // Obtener los nombres de los locales
                    $localesNombres = [];

                    if ($campana->locales && $campana->locales->count() > 0) {
                        foreach ($campana->locales as $local) {
                            if (isset($local->pivot) && isset($local->pivot->local_codigo)) {
                                $nombreLocal = Local::where('codigo', $local->pivot->local_codigo)->value('nombre');
                                $localesNombres[] = $nombreLocal ?? $local->pivot->local_codigo;
                            }
                        }
                    }

                    // Usar el primer local como local principal para la vista
                    $localPrincipal = !empty($localesNombres) ? $localesNombres[0] : '';

                    // Obtener la URL de la imagen
                    $imagenUrl = 'https://via.placeholder.com/150';
                    if ($campana->imagen) {
                        try {
                            $imagenUrl = $this->getImageUrl($campana->imagen);
                        } catch (\Exception $e) {
                            Log::error("[CampanasPage] Error al obtener URL de imagen: " . $e->getMessage());
                        }
                    }

                    // Crear el array con los datos de la campaña
                    $campanasTransformadas->push([
                        'codigo' => $campana->codigo,
                        'nombre' => $campana->titulo,
                        'local' => $localPrincipal,
                        'fecha_inicio' => $campana->fecha_inicio->format('d/m/Y'),
                        'fecha_fin' => $campana->fecha_fin->format('d/m/Y'),
                        'estado' => $campana->estado,
                        // Campos adicionales para uso interno
                        'id' => $campana->id,
                        'modelos' => $campana->modelos ? $campana->modelos->pluck('nombre')->toArray() : [],
                        'anos' => $campana->anos ? $campana->anos->pluck('ano')->toArray() : [],
                        'locales' => $localesNombres,
                        'imagen' => $imagenUrl,
                    ]);
                } catch (\Exception $e) {
                    Log::error("[CampanasPage] Error al procesar campaña {$campana->id}: " . $e->getMessage());
                    // Continuamos con la siguiente campaña
                }
            }

            $this->campanas = $campanasTransformadas;

            Log::info("[CampanasPage] Campañas transformadas: {$campanasTransformadas->count()}");
        } catch (\Exception $e) {
            Log::error("[CampanasPage] Error al cargar campañas: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            $this->campanas = collect();
            \Filament\Notifications\Notification::make()
                ->title('Error al Cargar Campañas')
                ->body('No se pudo obtener la lista de campañas: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function filtrarCampanas(): Collection
    {
        return $this->campanas->filter(function ($campana) {
            $pasaFiltroCiudad = empty($this->ciudadSeleccionada) || $campana['local'] === $this->ciudadSeleccionada;
            $pasaFiltroEstado = empty($this->estadoSeleccionado) || $campana['estado'] === $this->estadoSeleccionado;
            $pasaFiltroBusqueda = empty($this->busqueda) ||
                str_contains(strtolower($campana['codigo']), strtolower($this->busqueda)) ||
                str_contains(strtolower($campana['nombre']), strtolower($this->busqueda));

            // Filtro de fechas
            $pasaFiltroFechas = true; // Por defecto, todas las campañas pasan el filtro de fechas

            // Solo aplicamos el filtro de fechas si se ha seleccionado un rango
            if (!empty($this->fechaInicio) && !empty($this->fechaFin) && !empty($this->rangoFechas)) {
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

                    Log::debug("[CampanasPage] Filtro de fechas para campaña {$campana['codigo']}: " .
                        "Campaña: {$fechaInicioCampana->format('d/m/Y')} - {$fechaFinCampana->format('d/m/Y')}, " .
                        "Filtro: {$fechaInicioFiltro->format('d/m/Y')} - {$fechaFinFiltro->format('d/m/Y')}, " .
                        "Resultado: " . ($pasaFiltroFechas ? 'Pasa' : 'No pasa'));

                } catch (\Exception $e) {
                    // Si hay un error en el formato de fecha, no aplicamos el filtro
                    Log::error("[CampanasPage] Error al filtrar por fechas: " . $e->getMessage());
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
        Log::info("[CampanasPage] Aplicando filtro de fechas con rangoFechas: " . $this->rangoFechas);

        if (!empty($this->rangoFechas)) {
            $fechas = explode(' - ', $this->rangoFechas);
            if (count($fechas) === 2) {
                $this->fechaInicio = trim($fechas[0]);
                $this->fechaFin = trim($fechas[1]);
                Log::info("[CampanasPage] Rango de fechas establecido: {$this->fechaInicio} - {$this->fechaFin}");
            } elseif (count($fechas) === 1) {
                $this->fechaInicio = trim($fechas[0]);
                $this->fechaFin = trim($fechas[0]);
                Log::info("[CampanasPage] Fecha única establecida: {$this->fechaInicio}");
            }
        } else {
            $this->fechaInicio = '';
            $this->fechaFin = '';
            Log::info("[CampanasPage] Filtro de fechas limpiado");
        }

        $this->resetPage();
    }

    public function getCampanasPaginadasProperty(): LengthAwarePaginator
    {
        $campanasFiltradas = $this->filtrarCampanas();
        $perPage = 5;
        $page = request()->query('page', 1);

        return new LengthAwarePaginator(
            $campanasFiltradas->forPage($page, $perPage),
            $campanasFiltradas->count(),
            $perPage,
            $page,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    public function aplicarFiltros(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['ciudadSeleccionada', 'estadoSeleccionado', 'busqueda', 'rangoFechas', 'fechaInicio', 'fechaFin']);
        $this->resetPage();
    }

    public function verDetalle($codigo): void
    {
        try {
            Log::info("[CampanasPage] Intentando ver detalle de campaña con código: {$codigo}");

            $campana = Campana::with(['imagen', 'modelos', 'anos', 'locales'])->where('codigo', $codigo)->first();

            if ($campana) {
                try {
                    // Obtener los nombres de los locales
                    $localesNombres = [];

                    if ($campana->locales && $campana->locales->count() > 0) {
                        foreach ($campana->locales as $local) {
                            if (isset($local->pivot) && isset($local->pivot->local_codigo)) {
                                $nombreLocal = Local::where('codigo', $local->pivot->local_codigo)->value('nombre');
                                $localesNombres[] = $nombreLocal ?? $local->pivot->local_codigo;
                            }
                        }
                    }

                    // Obtener la URL de la imagen
                    $imagenUrl = null;
                    if ($campana->imagen) {
                        try {
                            $imagenUrl = $this->getImageUrl($campana->imagen);
                        } catch (\Exception $e) {
                            Log::error("[CampanasPage] Error al obtener URL de imagen para detalle: " . $e->getMessage());
                        }
                    }

                    // Preparar los datos para el modal
                    $this->campanaDetalle = [
                        'id' => $campana->id,
                        'codigo' => $campana->codigo,
                        'nombre' => $campana->titulo,
                        'fecha_inicio' => $campana->fecha_inicio->format('d/m/Y'),
                        'fecha_fin' => $campana->fecha_fin->format('d/m/Y'),
                        'estado' => $campana->estado,
                        'modelos' => $campana->modelos ? $campana->modelos->pluck('nombre')->toArray() : [],
                        'anos' => $campana->anos ? $campana->anos->pluck('ano')->toArray() : [],
                        'locales' => $localesNombres,
                        'imagen' => $imagenUrl,
                    ];

                    // Mostrar el modal
                    $this->modalDetalleVisible = true;

                    Log::info("[CampanasPage] Mostrando detalle de campaña: {$campana->codigo}");
                } catch (\Exception $e) {
                    Log::error("[CampanasPage] Error al procesar detalle de campaña {$campana->id}: " . $e->getMessage());
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
            Log::error("[CampanasPage] Error al ver detalle de campaña: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al intentar ver el detalle de la campaña: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Cierra el modal de detalle
     */
    public function cerrarModalDetalle(): void
    {
        $this->modalDetalleVisible = false;
        $this->campanaDetalle = [];
    }

    public function editar($codigo)
    {
        try {
            Log::info("[CampanasPage] Intentando editar campaña con código: {$codigo}");

            $campana = Campana::where('codigo', $codigo)->first();

            if (!$campana) {
                Log::warning("[CampanasPage] Campaña no encontrada con código: {$codigo}");
                \Filament\Notifications\Notification::make()
                    ->title('Campaña no encontrada')
                    ->body("No se encontró la campaña con código: {$codigo}")
                    ->danger()
                    ->send();
                return;
            }

            // Construir la URL para la redirección
            $url = '/admin/crear-campana?campana_id=' . $campana->id;

            Log::info("[CampanasPage] Redirigiendo a edición de campaña: {$campana->codigo} con ID: {$campana->id} - URL: {$url}");

            // Usar el método redirect de Livewire
            return $this->redirect($url);

        } catch (\Exception $e) {
            Log::error("[CampanasPage] Error al editar campaña: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al intentar editar la campaña: ' . $e->getMessage())
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
        Log::info("[CampanasPage] Ruta de imagen original: " . $imagen->ruta);

        // Generar la URL directamente usando asset()
        $url = asset($imagen->ruta);

        // Registrar la URL generada para depuración
        Log::info("[CampanasPage] URL generada para imagen: " . $url);

        // Verificar si el archivo existe
        $rutaCompleta = public_path($imagen->ruta);
        $existe = file_exists($rutaCompleta);

        Log::info("[CampanasPage] Verificación de archivo: " . json_encode([
            'rutaCompleta' => $rutaCompleta,
            'existe' => $existe ? 'Sí' : 'No'
        ]));

        // Devolver la URL
        return $url;
    }

    public function eliminar($codigo): void
    {
        try {
            $campana = Campana::where('codigo', $codigo)->first();

            if ($campana) {
                // Eliminar la imagen si existe
                $imagen = $campana->imagen;
                if ($imagen) {
                    // Eliminar el archivo físico
                    $rutaCompleta = public_path($imagen->ruta);
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
                    ->body("La campaña ha sido eliminada correctamente.")
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
            Log::error("[CampanasPage] Error al eliminar campaña: " . $e->getMessage());
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al intentar eliminar la campaña.')
                ->danger()
                ->send();
        }
    }
}
