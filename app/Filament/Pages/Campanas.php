<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
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

    // Opciones para filtros
    public array $ciudades = [
        'Lima',
        'La Molina',
        'Canadá',
        'Arequipa',
    ];

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

        $this->cargarCampanas();
    }

    public function cargarCampanas(): void
    {
        try {
            // Aquí se cargarían los datos reales de una API o base de datos
            // Por ahora, usamos datos de ejemplo
            $this->campanas = collect([
                [
                    'codigo' => 'PQTLUEX',
                    'nombre' => 'Lubrexpress',
                    'local' => 'La Molina',
                    'fecha_inicio' => '30/10/2023',
                    'fecha_fin' => '30/10/2023',
                    'estado' => 'Activo',
                ],
                [
                    'codigo' => 'PQTREGPAPA',
                    'nombre' => 'Regálale a Papá',
                    'local' => 'Canadá',
                    'fecha_inicio' => '30/10/2023',
                    'fecha_fin' => '30/10/2023',
                    'estado' => 'Inactivo',
                ],
                [
                    'codigo' => 'PQTENCE',
                    'nombre' => 'Encerado',
                    'local' => 'La Molina',
                    'fecha_inicio' => '30/10/2023',
                    'fecha_fin' => '30/10/2023',
                    'estado' => 'Activo',
                ],
                [
                    'codigo' => 'PQTRATAPIN',
                    'nombre' => 'Tratamiento de pintura',
                    'local' => 'Lima',
                    'fecha_inicio' => '30/10/2023',
                    'fecha_fin' => '30/10/2023',
                    'estado' => 'Inactivo',
                ],
                [
                    'codigo' => 'PQTCUMPLE',
                    'nombre' => 'Campaña cumpleaños',
                    'local' => 'Lima',
                    'fecha_inicio' => '30/10/2023',
                    'fecha_fin' => '30/10/2023',
                    'estado' => 'Activo',
                ],
                [
                    'codigo' => 'PQTANIV',
                    'nombre' => 'Campaña cumpleaños',
                    'local' => 'Lima',
                    'fecha_inicio' => '30/10/2023',
                    'fecha_fin' => '30/10/2023',
                    'estado' => 'Inactivo',
                ],
                [
                    'codigo' => 'PQTALINEA',
                    'nombre' => 'Campaña aniversario',
                    'local' => 'Lima',
                    'fecha_inicio' => '30/10/2023',
                    'fecha_fin' => '30/10/2023',
                    'estado' => 'Activo',
                ],
                [
                    'codigo' => 'PQTALINEA2',
                    'nombre' => 'Alineamiento de dirección 3D',
                    'local' => 'Lima',
                    'fecha_inicio' => '30/10/2023',
                    'fecha_fin' => '30/10/2023',
                    'estado' => 'Inactivo',
                ],
                [
                    'codigo' => 'PQTALINEA3',
                    'nombre' => 'Encerado',
                    'local' => 'Lima',
                    'fecha_inicio' => '30/10/2023',
                    'fecha_fin' => '30/10/2023',
                    'estado' => 'Activo',
                ],
                [
                    'codigo' => 'PQTALINEA4',
                    'nombre' => 'Encerado',
                    'local' => 'Lima',
                    'fecha_inicio' => '30/10/2023',
                    'fecha_fin' => '30/10/2023',
                    'estado' => 'Inactivo',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("[CampanasPage] Error al cargar campañas: " . $e->getMessage());
            $this->campanas = collect();
            \Filament\Notifications\Notification::make()
                ->title('Error al Cargar Campañas')
                ->body('No se pudo obtener la lista de campañas. Intente más tarde.')
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
        // Implementar la lógica para ver detalle
        \Filament\Notifications\Notification::make()
            ->title('Ver detalle')
            ->body("Ver detalle de la campaña con código: {$codigo}")
            ->success()
            ->send();
    }

    public function editar($codigo): void
    {
        // Implementar la lógica para editar
        \Filament\Notifications\Notification::make()
            ->title('Editar')
            ->body("Editar la campaña con código: {$codigo}")
            ->success()
            ->send();
    }

    public function eliminar($codigo): void
    {
        // Implementar la lógica para eliminar
        \Filament\Notifications\Notification::make()
            ->title('Eliminar')
            ->body("Eliminar la campaña con código: {$codigo}")
            ->success()
            ->send();
    }
}
