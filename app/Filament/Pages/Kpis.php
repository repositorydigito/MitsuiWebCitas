<?php

namespace App\Filament\Pages;

use App\Models\Local;
use App\Models\Vehicle;
use App\Models\KpiTarget;
use App\Models\User;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

    public ?int $mesSeleccionado = null;

    public ?int $anioSeleccionado = null;

    public string $marcaSeleccionada = 'Todas';

    public string $localSeleccionado = 'Todos';

    public string $tipoSeleccionado = 'Post Venta';

    // Propiedades para el modal de configuración de metas
    public bool $showModal = false;
    public string $kpiId = '';
    public ?string $modalBrand = null;
    public ?string $modalLocal = null;
    public ?int $modalMonth = null;
    public ?int $modalYear = null;
    public int $targetValue = 0;

    // Datos de KPIs
    public Collection $kpis;

    // Opciones para los selectores
    public array $marcas = ['Todas'];

    public array $locales = [];

    public array $localesModal = [];

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
        
        // Inicializar locales para modal
        $this->actualizarLocalesModal();

        // Cargar datos iniciales
        $this->cargarKpis();
    }

    /**
     * Carga los locales activos desde la base de datos
     */
    protected function cargarLocales(): void
    {
        try {
            // Obtener los locales activos filtrados por marca si es necesario
            $this->actualizarLocalesPorMarca();

        } catch (\Exception $e) {
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
        
        // Si hay una marca seleccionada que no sea "Todas", filtrar por marca
        if ($this->marcaSeleccionada !== 'Todas') {
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
     * Actualiza la lista de locales para el modal basada en la marca seleccionada en el modal
     */
    protected function actualizarLocalesModal(): void
    {
        $query = Local::where('is_active', true);
        
        // Si hay una marca seleccionada en el modal que no sea null, filtrar por marca
        if ($this->modalBrand) {
            $query->where('brand', $this->modalBrand);
        }
        
        $localesActivos = $query->orderBy('name')
            ->pluck('name', 'code')
            ->toArray();

        // Agregar la opción "Todos" al principio
        $this->localesModal = ['Todos' => 'Todos'] + $localesActivos;
        
        // Si el local actualmente seleccionado en el modal no está en la nueva lista, resetear a null
        if ($this->modalLocal && $this->modalLocal !== 'Todos' && !array_key_exists($this->modalLocal, $this->localesModal)) {
            $this->modalLocal = null;
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
            
            // Si se han seleccionado mes y año, usar esos valores
            if ($this->mesSeleccionado && $this->anioSeleccionado) {
                $fechaInicio = Carbon::create($this->anioSeleccionado, $this->mesSeleccionado, 1);
                $fechaFin = $fechaInicio->copy()->endOfMonth();
                $this->fechaInicio = $fechaInicio->format('d/m/Y');
                $this->fechaFin = $fechaFin->format('d/m/Y');
                $this->rangoFechas = $this->fechaInicio.' - '.$this->fechaFin;
            } else if (!empty($this->rangoFechas)) {
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

            // KPI 6: Cantidad de clientes registrados (usuarios con rol "Usuario") filtrados por fecha
            $cantidadUsuarios = $this->calcularCantidadUsuarios($fechaInicio, $fechaFin);

            \Log::info("KPIs calculados - Con filtros: {$todasLasCitas}, Solo fecha: {$todasSinFiltrosRestrictivos}, Generadas: {$citasGeneradas}, Usuarios: {$cantidadUsuarios}");

            // Obtener valores meta de la base de datos para todos los KPIs
            // Convertir "Todas"/"Todos" a null para la búsqueda
            $brand = $this->marcaSeleccionada !== 'Todas' ? $this->marcaSeleccionada : null;
            $local = $this->localSeleccionado !== 'Todos' ? $this->localSeleccionado : null;
            
            // Obtener metas para todos los KPIs
            $metaGeneradas = KpiTarget::getTargetValue('1', $brand, $local, $this->mesSeleccionado, $this->anioSeleccionado);
            $metaEfectivas = KpiTarget::getTargetValue('2', $brand, $local, $this->mesSeleccionado, $this->anioSeleccionado);
            $metaCanceladas = KpiTarget::getTargetValue('3', $brand, $local, $this->mesSeleccionado, $this->anioSeleccionado);
            $metaReprogramadas = KpiTarget::getTargetValue('4', $brand, $local, $this->mesSeleccionado, $this->anioSeleccionado);
            $metaMantenimiento = KpiTarget::getTargetValue('5', $brand, $local, $this->mesSeleccionado, $this->anioSeleccionado);
            $metaUsuarios = KpiTarget::getTargetValue('6', $brand, $local, $this->mesSeleccionado, $this->anioSeleccionado);
            
            // Calcular desviaciones para todos los KPIs
            $desviacionGeneradas = $this->calcularDesviacion($todasLasCitas, $metaGeneradas);
            $desviacionEfectivas = $this->calcularDesviacion($citasEfectivas, $metaEfectivas);
            $desviacionCanceladas = $this->calcularDesviacion($citasCanceladas, $metaCanceladas);
            $desviacionReprogramadas = $this->calcularDesviacion($citasReprogramadas, $metaReprogramadas);
            $desviacionMantenimiento = $this->calcularDesviacion($citasMantenimiento, $metaMantenimiento);
            $desviacionUsuarios = $this->calcularDesviacion($cantidadUsuarios, $metaUsuarios);

            $this->kpis = collect([
                [
                    'id' => 1,
                    'nombre' => 'Cantidad de citas generadas',
                    'cantidad' => $todasLasCitas,
                    'meta' => $metaGeneradas,
                    'contribucion' => false,
                    'desviacion' => $desviacionGeneradas,
                ],
                [
                    'id' => 2,
                    'nombre' => 'Cantidad de citas efectivas',
                    'cantidad' => $citasEfectivas,
                    'meta' => $metaEfectivas,
                    'contribucion' => false,
                    'desviacion' => $desviacionEfectivas,
                ],
                [
                    'id' => 3,
                    'nombre' => 'Cantidad de citas canceladas',
                    'cantidad' => $citasCanceladas,
                    'meta' => $metaCanceladas,
                    'contribucion' => true,
                    'desviacion' => $desviacionCanceladas,
                ],
                [
                    'id' => 4,
                    'nombre' => 'Cantidad de citas diferidas / reprogramadas',
                    'cantidad' => $citasReprogramadas,
                    'meta' => $metaReprogramadas,
                    'contribucion' => true,
                    'desviacion' => $desviacionReprogramadas,
                ],
                [
                    'id' => 5,
                    'nombre' => 'Cantidad citas por mantenimiento',
                    'cantidad' => $citasMantenimiento,
                    'meta' => $metaMantenimiento,
                    'contribucion' => true,
                    'desviacion' => $desviacionMantenimiento,
                ],
                [
                    'id' => 6,
                    'nombre' => 'Cantidad de clientes registrados',
                    'cantidad' => $cantidadUsuarios,
                    'meta' => $metaUsuarios,
                    'contribucion' => false,
                    'desviacion' => $desviacionUsuarios,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al cargar KPIs: ' . $e->getMessage());
            $this->kpis = collect([]);
        }
    }

    /**
     * Calcula la cantidad de usuarios con rol "Usuario" filtrados por fecha de creación
     */
    protected function calcularCantidadUsuarios($fechaInicio = null, $fechaFin = null): int
    {
        try {
            // Construir query base para usuarios con rol "Usuario"
            $query = User::role('Usuario');
            
            // Aplicar filtro de fecha si se proporcionan fechas válidas
            if ($fechaInicio && $fechaFin) {
                $fechaInicioStr = $fechaInicio->format('Y-m-d');
                $fechaFinStr = $fechaFin->format('Y-m-d');
                
                $query->whereBetween('created_at', [$fechaInicioStr . ' 00:00:00', $fechaFinStr . ' 23:59:59']);
                
                \Log::info('[Kpis] Filtro de fecha aplicado para usuarios: ' . $fechaInicioStr . ' - ' . $fechaFinStr);
            } else {
                \Log::info('[Kpis] Sin filtro de fecha para usuarios (mostrando todos)');
            }
            
            $cantidadUsuarios = $query->count();
            
            \Log::info('[Kpis] Cantidad de usuarios con rol "Usuario" (filtrados): ' . $cantidadUsuarios);
            
            return $cantidadUsuarios;
        } catch (\Exception $e) {
            \Log::error('[Kpis] Error al calcular cantidad de usuarios: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Calcula la desviación porcentual entre el valor actual y la meta
     */
    protected function calcularDesviacion(?int $valorActual, ?int $meta): ?string
    {
        if ($meta === null || $meta == 0) {
            return null;
        }
        
        $desviacion = round((($valorActual - $meta) / $meta) * 100, 1);
        
        if ($desviacion == 0) {
            return "0%";
        } elseif ($desviacion > 0) {
            return "+{$desviacion}%";
        } else {
            return "{$desviacion}%";
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

    public function updatedMesSeleccionado(): void
    {
        $this->cargarKpis();
    }

    public function updatedAnioSeleccionado(): void
    {
        $this->cargarKpis();
    }

    public function updatedMarcaSeleccionada(): void
    {
        // Actualizar la lista de locales basada en la nueva marca
        $this->actualizarLocalesPorMarca();
        
        // Cargar datos con los nuevos filtros
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
        $this->mesSeleccionado = null;
        $this->anioSeleccionado = null;
        $this->marcaSeleccionada = 'Todas';
        $this->localSeleccionado = 'Todos';
        
        // Recargar datos
        $this->cargarKpis();
    }

    public function exportarExcel()
    {
        // Crear un nuevo libro de trabajo
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Establecer el título de la hoja
        $sheet->setTitle('KPIs');
        
        // Agregar encabezados
        $sheet->setCellValue('A1', '#');
        $sheet->setCellValue('B1', 'KPI');
        $sheet->setCellValue('C1', 'Cantidad');
        $sheet->setCellValue('D1', 'Meta');
        $sheet->setCellValue('E1', 'Contribución');
        $sheet->setCellValue('F1', 'Desviación');
        
        // Aplicar estilo a los encabezados
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB']
            ]
        ];
        
        $sheet->getStyle('A1:F1')->applyFromArray($headerStyle);
        
        // Agregar datos de los KPIs
        $row = 2;
        foreach ($this->kpis as $kpi) {
            $sheet->setCellValue('A' . $row, $kpi['id']);
            $sheet->setCellValue('B' . $row, $kpi['nombre']);
            $sheet->setCellValue('C' . $row, $kpi['cantidad']);
            $sheet->setCellValue('D' . $row, $kpi['meta'] ?? '');
            $sheet->setCellValue('E' . $row, $kpi['contribucion'] ? 'SÍ' : '');
            $sheet->setCellValue('F' . $row, $kpi['desviacion'] ?? '');
            $row++;
        }
        
        // Ajustar el ancho de las columnas
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Crear el nombre del archivo con la fecha actual
        $fecha = now()->format('Y-m-d_H-i-s');
        $fileName = "kpis_{$fecha}.xlsx";
        
        // Guardar el archivo temporalmente
        $tempFilePath = storage_path('app/temp/' . $fileName);
        $tempDir = storage_path('app/temp');
        
        // Crear el directorio temporal si no existe
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        // Guardar el archivo
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFilePath);
        
        // Devolver el archivo como descarga
        return response()->download($tempFilePath)->deleteFileAfterSend(true);
    }

    // Métodos para el modal de configuración de metas
    public function openModal(string $kpiId): void
    {
        $this->kpiId = $kpiId;
        $this->modalBrand = $this->marcaSeleccionada !== 'Todas' ? $this->marcaSeleccionada : null;
        $this->modalLocal = $this->localSeleccionado !== 'Todos' ? $this->localSeleccionado : null;
        $this->modalMonth = $this->mesSeleccionado;
        $this->modalYear = $this->anioSeleccionado;
        
        // Actualizar locales del modal basado en la marca seleccionada
        $this->actualizarLocalesModal();
        
        // Obtener el valor actual si existe
        $currentTarget = KpiTarget::getTargetValue($kpiId, $this->modalBrand, $this->modalLocal, $this->modalMonth, $this->modalYear);
        $this->targetValue = $currentTarget ?? 0;
        
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetModal();
    }

    public function resetModal(): void
    {
        $this->kpiId = '';
        $this->modalBrand = null;
        $this->modalLocal = null;
        $this->modalMonth = null;
        $this->modalYear = null;
        $this->targetValue = 0;
    }

    public function saveTarget(): void
    {
        // Validar que se haya proporcionado un valor
        if ($this->targetValue < 0) {
            // Mostrar mensaje de error
            // En un entorno real, usarías notificaciones de Filament
            return;
        }

        // Crear o actualizar el registro
        $target = KpiTarget::updateOrCreate(
            [
                'kpi_id' => $this->kpiId,
                'brand' => $this->modalBrand,
                'local' => $this->modalLocal,
                'month' => $this->modalMonth,
                'year' => $this->modalYear,
            ],
            [
                'target_value' => $this->targetValue,
            ]
        );

        // Cerrar el modal y recargar los KPIs
        $this->closeModal();
        $this->cargarKpis();
    }

    /**
     * Se ejecuta cuando cambia la marca seleccionada en el modal
     */
    public function updatedModalBrand(): void
    {
        // Actualizar la lista de locales del modal basada en la nueva marca
        $this->actualizarLocalesModal();
    }
}