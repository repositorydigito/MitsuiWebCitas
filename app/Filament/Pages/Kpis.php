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
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class Kpis extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'KPIs';

    protected static ?string $navigationGroup = 'Reportes & KPIs';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Indicadores de Desempeño (KPIs)';

    protected static string $view = 'filament.pages.kpis';

    public string $fechaInicio = '';

    public string $fechaFin = '';

    public string $rangoFechas = '';

    public ?int $mesSeleccionado = null;

    public ?int $anioSeleccionado = null;

    public string $marcaSeleccionada = 'Todas';

    public string $localSeleccionado = 'Todos';

    public string $tipoSeleccionado = 'Post Venta';

    public bool $showModal = false;
    public string $kpiId = '';
    public ?string $modalBrand = null;
    public ?string $modalLocal = null;
    public ?int $modalMonth = null;
    public ?int $modalYear = null;
    public int $targetValue = 0;

    public Collection $kpis;

    public array $marcas = ['Todas'];

    public array $locales = [];

    public array $localesModal = [];

    public array $tipos = ['Post Venta', 'Venta', 'Todos'];

    public function mount(): void
    {
        $fechaFin = now();
        $fechaInicio = now()->subDays(30);

        $this->fechaInicio = $fechaInicio->format('d/m/Y');
        $this->fechaFin = $fechaFin->format('d/m/Y');
        $this->rangoFechas = $this->fechaInicio.' - '.$this->fechaFin;

        $this->cargarLocales();
        $this->cargarMarcas();
        
        $this->actualizarLocalesModal();

        $this->cargarKpis();
    }

    protected function cargarLocales(): void
    {
        try {
            $this->actualizarLocalesPorMarca();

        } catch (\Exception $e) {
            $this->locales = ['Todos' => 'Todos'];
        }
    }

    protected function actualizarLocalesPorMarca(): void
    {
        $query = Local::where('is_active', true);
        
        if ($this->marcaSeleccionada !== 'Todas') {
            $query->where('brand', $this->marcaSeleccionada);
        }
        
        $localesActivos = $query->orderBy('name')
            ->pluck('name', 'code')
            ->toArray();

        $this->locales = ['Todos' => 'Todos'] + $localesActivos;
        
        if ($this->localSeleccionado !== 'Todos' && !array_key_exists($this->localSeleccionado, $this->locales)) {
            $this->localSeleccionado = 'Todos';
        }
    }

    protected function actualizarLocalesModal(): void
    {
        $query = Local::where('is_active', true);
        
        if ($this->modalBrand) {
            $query->where('brand', $this->modalBrand);
        }
        
        $localesActivos = $query->orderBy('name')
            ->pluck('name', 'code')
            ->toArray();

        $this->localesModal = ['Todos' => 'Todos'] + $localesActivos;
        
        if ($this->modalLocal && $this->modalLocal !== 'Todos' && !array_key_exists($this->modalLocal, $this->localesModal)) {
            $this->modalLocal = null;
        }
    }

    protected function cargarMarcas(): void
    {
        try {
            $marcasDb = Vehicle::distinct('brand_name')
                ->whereNotNull('brand_name')
                ->where('brand_name', '!=', '')
                ->pluck('brand_name')
                ->sort()
                ->values()
                ->toArray();

            $this->marcas = array_merge(['Todas'], $marcasDb);

            if (empty($marcasDb)) {
                $this->marcas = ['Todas', 'Toyota', 'Lexus', 'Hino'];
            }

            $this->marcaSeleccionada = 'Todas';

        } catch (\Exception $e) {
            $this->marcas = ['Todas', 'Toyota', 'Lexus', 'Hino'];
            $this->marcaSeleccionada = 'Todas';
        }
    }

    public function cargarKpis(): void
    {
        try {
            $fechaInicio = null;
            $fechaFin = null;
            
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

            $query = \App\Models\Appointment::query();

            if ($fechaInicio && $fechaFin) {
                $fechaInicioStr = $fechaInicio->format('Y-m-d');
                $fechaFinStr = $fechaFin->format('Y-m-d');
                
                $query->whereBetween('appointment_date', [$fechaInicioStr, $fechaFinStr]);
            }

            if ($this->localSeleccionado !== 'Todos' && !empty($this->localSeleccionado)) {
                $localId = $this->obtenerIdLocal($this->localSeleccionado);
                if ($localId) {
                    $query->where('premise_id', $localId);
                } else {
                    \Log::info("Local no encontrado para el código: {$this->localSeleccionado}");
                }
            }

            if ($this->marcaSeleccionada !== 'Todas' && !empty($this->marcaSeleccionada)) {
                $query->whereHas('vehicle', function ($q) {
                    $q->where('brand_name', $this->marcaSeleccionada);
                });
            }
            
            $sql = $query->toSql();
            $bindings = $query->getBindings();
            $fullSql = $sql;
            foreach ($bindings as $binding) {
                $value = is_numeric($binding) ? $binding : "'$binding'";
                $fullSql = preg_replace('/\?/', $value, $fullSql, 1);
            }

            $afterDateFilter = (clone $query)->count();
            $afterLocalFilter = (clone $query)->count();
            $estadosExistentes = \App\Models\Appointment::distinct()->pluck('status')->toArray();
            $localesExistentes = \App\Models\Appointment::distinct()->pluck('premise_id')->toArray();
            $marcasExistentes = \App\Models\Appointment::distinct()->pluck('vehicle_brand_code')->toArray();

            $queryDebug = \App\Models\Appointment::query();
            if ($fechaInicio && $fechaFin) {
                $queryDebug->whereBetween('appointment_date', [$fechaInicio->format('Y-m-d'), $fechaFin->format('Y-m-d')]);
            } else {
                $queryDebug->where('appointment_date', '>=', now()->subYear()->format('Y-m-d'));
            }
            
            $todasLasCitas = (clone $query)->count();
            $todasSinFiltrosRestrictivos = (clone $queryDebug)->count();
            
            $citasGeneradas = (clone $query)->whereIn('status', ['pending', 'confirmed', 'generated', 'in_progress', 'completed'])->count();
            $citasEfectivas = (clone $query)
                ->where('status', 'confirmed')
                ->where(function($q) {
                    $q->whereRaw("JSON_EXTRACT(frontend_states, '$.en_trabajo.activo') = true")
                      ->orWhereRaw("JSON_EXTRACT(frontend_states, '$.en_trabajo.completado') = true");
                })
                ->count();
            $citasCanceladas = (clone $query)->where('status', 'cancelled')
                                          ->where('rescheduled', 0)
                                          ->count();
            $citasReprogramadas = (clone $query)->where('rescheduled', 1)->count();
            $citasMantenimiento = (clone $query)->whereNotNull('maintenance_type')
                                             ->where('maintenance_type', '!=', '')
                                             ->count();

            $cantidadUsuarios = $this->calcularCantidadUsuarios($fechaInicio, $fechaFin);
            
            $citasNoShow = (clone $query)
                ->where('status', 'confirmed')
                ->where(function($q) {
                    $q->whereRaw("JSON_EXTRACT(frontend_states, '$.cita_confirmada') IS NOT NULL")
                      ->whereRaw("JSON_EXTRACT(frontend_states, '$.en_trabajo.activo') IS NULL")
                      ->whereRaw("JSON_EXTRACT(frontend_states, '$.en_trabajo.completado') IS NULL")
                      ->whereRaw("TIMESTAMPDIFF(HOUR, STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(frontend_states, '$.cita_confirmada')), '%Y-%m-%d %H:%i:%s'), NOW()) > 10");
                })->orWhere(function($q) {
                    $q->whereRaw("JSON_EXTRACT(frontend_states, '$.cita_confirmada') IS NOT NULL")
                      ->where(function($q2) {
                          $q2->whereRaw("JSON_EXTRACT(frontend_states, '$.en_trabajo.activo') = true")
                             ->orWhereRaw("JSON_EXTRACT(frontend_states, '$.en_trabajo.completado') = true");
                      })
                      ->whereRaw("TIMESTAMPDIFF(HOUR, STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(frontend_states, '$.cita_confirmada')), '%Y-%m-%d %H:%i:%s'), STR_TO_DATE(JSON_UNQUOTE(JSON_EXTRACT(frontend_states, '$.en_trabajo.timestamp')), '%Y-%m-%d %H:%i:%s')) > 10");
                })
                ->count();

            $brand = $this->marcaSeleccionada !== 'Todas' ? $this->marcaSeleccionada : null;
            $local = $this->localSeleccionado !== 'Todos' ? $this->localSeleccionado : null;
            
            $metaGeneradas = KpiTarget::getTargetValue('1', $brand, $local, $this->mesSeleccionado, $this->anioSeleccionado);
            $metaEfectivas = KpiTarget::getTargetValue('2', $brand, $local, $this->mesSeleccionado, $this->anioSeleccionado);
            $metaCanceladas = KpiTarget::getTargetValue('3', $brand, $local, $this->mesSeleccionado, $this->anioSeleccionado);
            $metaReprogramadas = KpiTarget::getTargetValue('4', $brand, $local, $this->mesSeleccionado, $this->anioSeleccionado);
            $metaMantenimiento = KpiTarget::getTargetValue('5', $brand, $local, $this->mesSeleccionado, $this->anioSeleccionado);
            $metaUsuarios = KpiTarget::getTargetValue('6', $brand, $local, $this->mesSeleccionado, $this->anioSeleccionado);
            $metaNoShow = KpiTarget::getTargetValue('7', $brand, $local, $this->mesSeleccionado, $this->anioSeleccionado);
            
            $desviacionGeneradas = $this->calcularDesviacion($todasLasCitas, $metaGeneradas);
            $desviacionEfectivas = $this->calcularDesviacion($citasEfectivas, $metaEfectivas);
            $desviacionCanceladas = $this->calcularDesviacion($citasCanceladas, $metaCanceladas);
            $desviacionReprogramadas = $this->calcularDesviacion($citasReprogramadas, $metaReprogramadas);
            $desviacionMantenimiento = $this->calcularDesviacion($citasMantenimiento, $metaMantenimiento);
            $desviacionUsuarios = $this->calcularDesviacion($cantidadUsuarios, $metaUsuarios);
            $desviacionNoShow = $this->calcularDesviacion($citasNoShow, $metaNoShow);

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
                [
                    'id' => 7,
                    'nombre' => 'Cantidad de citas no show',
                    'cantidad' => $citasNoShow,
                    'meta' => $metaNoShow,
                    'contribucion' => true,
                    'desviacion' => $desviacionNoShow,
                ],
            ]);

        } catch (\Exception $e) {
            $this->kpis = collect([]);
        }
    }

    protected function calcularCantidadUsuarios($fechaInicio = null, $fechaFin = null): int
    {
        try {
            $query = User::role('Usuario');
            
            if ($fechaInicio && $fechaFin) {
                $fechaInicioStr = $fechaInicio->format('Y-m-d');
                $fechaFinStr = $fechaFin->format('Y-m-d');
                
                $query->whereBetween('created_at', [$fechaInicioStr . ' 00:00:00', $fechaFinStr . ' 23:59:59']);
                
            } else {
                \Log::info('[Kpis] Sin filtro de fecha para usuarios (mostrando todos)');
            }
            
            $cantidadUsuarios = $query->count();
            
            return $cantidadUsuarios;
        } catch (\Exception $e) {
            return 0;
        }
    }

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

    protected function obtenerIdLocal(string $codigoLocal): ?int
    {
        try {
            $local = Local::where('code', $codigoLocal)->first();
            return $local ? $local->id : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function aplicarFiltros(): void
    {
        if (! empty($this->rangoFechas)) {

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
                
                $fechaInicio = null;
                $fechaFin = null;
                
                try {
                    $fechaInicio = \Carbon\Carbon::createFromFormat('d/m/Y', $fechaInicioStr);
                    $fechaFin = \Carbon\Carbon::createFromFormat('d/m/Y', $fechaFinStr);
                    
                    if ($fechaInicio->greaterThan($fechaFin)) {
                        $temp = $fechaInicio;
                        $fechaInicio = $fechaFin;
                        $fechaFin = $temp;
                        $this->rangoFechas = $fechaInicio->format('d/m/Y') . ' - ' . $fechaFin->format('d/m/Y');
                    }
                    
                    $this->fechaInicio = $fechaInicio->format('d/m/Y');
                    $this->fechaFin = $fechaFin->format('d/m/Y');
                    
                } catch (\Exception $e) {
                    $this->fechaInicio = '';
                    $this->fechaFin = '';
                    return;
                }
            } else {
                return;
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
        $this->actualizarLocalesPorMarca();
        
        $this->cargarKpis();
    }

    public function updatedLocalSeleccionado(): void
    {
        $this->cargarKpis();
    }

    public function limpiarFiltros(): void
    {
        $fechaFin = now();
        $fechaInicio = now()->subDays(30);

        $this->fechaInicio = $fechaInicio->format('d/m/Y');
        $this->fechaFin = $fechaFin->format('d/m/Y');
        $this->rangoFechas = $this->fechaInicio.' - '.$this->fechaFin;
        
        $this->mesSeleccionado = null;
        $this->anioSeleccionado = null;
        $this->marcaSeleccionada = 'Todas';
        $this->localSeleccionado = 'Todos';
        
        $this->cargarKpis();
    }

    public function exportarExcel()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('KPIs');
        $sheet->setCellValue('A1', 'REPORTE DE KPIs');
        $sheet->mergeCells('A1:F1');
        
        $row = 2;
        $sheet->setCellValue('A' . $row, 'Filtros aplicados:');
        $row++;
        
        if ($this->marcaSeleccionada !== 'Todas') {
            $sheet->setCellValue('A' . $row, 'Marca: ' . $this->marcaSeleccionada);
            $row++;
        }
        
        if ($this->localSeleccionado !== 'Todos') {
            $localNombre = $this->locales[$this->localSeleccionado] ?? $this->localSeleccionado;
            $sheet->setCellValue('A' . $row, 'Local: ' . $localNombre);
            $row++;
        }
        
        if ($this->mesSeleccionado && $this->anioSeleccionado) {
            $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
            $mesNombre = $meses[$this->mesSeleccionado] ?? $this->mesSeleccionado;
            $sheet->setCellValue('A' . $row, 'Período: ' . $mesNombre . ' ' . $this->anioSeleccionado);
            $row++;
        } elseif (!empty($this->rangoFechas)) {
            $sheet->setCellValue('A' . $row, 'Rango de fechas: ' . $this->rangoFechas);
            $row++;
        }
        
        $sheet->setCellValue('A' . $row, 'Generado el: ' . now()->format('d/m/Y H:i:s'));
        $row += 2; 
        
        $sheet->setCellValue('A' . $row, '#');
        $sheet->setCellValue('B' . $row, 'KPI');
        $sheet->setCellValue('C' . $row, 'Cantidad');
        $sheet->setCellValue('D' . $row, 'Meta');
        $sheet->setCellValue('E' . $row, 'Cumplimiento');
        
        $titleStyle = [
            'font' => [
                'bold' => true,
                'size' => 14,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1f2937']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
            ]
        ];
        
        $sheet->getStyle('A1:E1')->applyFromArray($titleStyle);
        
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2563EB']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ];
        
        $headerRow = $row;
        $sheet->getStyle('A' . $headerRow . ':E' . $headerRow)->applyFromArray($headerStyle);
        
        $row++;
        foreach ($this->kpis as $kpi) {
            $sheet->setCellValue('A' . $row, $kpi['id']);
            $sheet->setCellValue('B' . $row, $kpi['nombre']);
            $sheet->setCellValue('C' . $row, $kpi['cantidad']);
            $sheet->setCellValue('D' . $row, $kpi['meta'] ?? '-');
            $sheet->setCellValue('E' . $row, $kpi['desviacion'] ?? '-');
            $row++;
        }
        
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        $fecha = now()->format('Y-m-d_H-i-s');
        $filtros = [];
        
        if ($this->marcaSeleccionada !== 'Todas') {
            $filtros[] = $this->marcaSeleccionada;
        }
        
        if ($this->localSeleccionado !== 'Todos') {
            $filtros[] = $this->localSeleccionado;
        }
        
        if ($this->mesSeleccionado && $this->anioSeleccionado) {
            $filtros[] = $this->mesSeleccionado . '-' . $this->anioSeleccionado;
        }
        
        $filtrosSufijo = !empty($filtros) ? '_' . implode('_', $filtros) : '';
        $fileName = "kpis{$filtrosSufijo}_{$fecha}.xlsx";
        
        $tempFilePath = storage_path('app/temp/' . $fileName);
        $tempDir = storage_path('app/temp');
        
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($tempFilePath);
        
        return response()->download($tempFilePath)->deleteFileAfterSend(true);
    }

    public function openModal(string $kpiId): void
    {
        $this->kpiId = $kpiId;
        $this->modalBrand = $this->marcaSeleccionada !== 'Todas' ? $this->marcaSeleccionada : null;
        $this->modalLocal = $this->localSeleccionado !== 'Todos' ? $this->localSeleccionado : null;
        $this->modalMonth = $this->mesSeleccionado;
        $this->modalYear = $this->anioSeleccionado;
        
        $this->actualizarLocalesModal();
        
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
        if ($this->targetValue < 0) {
            return;
        }

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

        $this->closeModal();
        $this->cargarKpis();
    }

    public function updatedModalBrand(): void
    {
        $this->actualizarLocalesModal();
    }
}