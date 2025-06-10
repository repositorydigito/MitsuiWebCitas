<?php

namespace App\Filament\Pages;

use App\Models\Local;
use App\Models\VehiculoExpress;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ServicioExpress extends Page
{
    use WithFileUploads, HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Servicio Express';
    
    protected static ?string $navigationGroup = '📅 Citas & Servicios';
    
    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Gestión servicio express';

    protected static string $view = 'filament.pages.servicio-express';

    // Propiedades para la tabla
    public Collection $vehiculos;

    public int $perPage = 5;

    public int $currentPage = 1;

    public int $page = 1;

    // Estado de los vehículos
    public array $estadoVehiculos = [];

    // Propiedades para el modal de carga
    public bool $isModalOpen = false;

    public $archivoExcel = null;

    public string $nombreArchivo = 'Sin selección';

    // Propiedades para el modal de edición
    public bool $isEditModalOpen = false;

    public array $vehiculoEnEdicion = [
        'id' => null,
        'model' => '',
        'brand' => '',
        'year' => '',
        'maintenance' => '',
        'premises' => '',
    ];

    // Lista de locales disponibles para el selector
    public array $localesDisponibles = [];

    public function mount(): void
    {
        $this->currentPage = request()->query('page', 1);
        $this->cargarVehiculos();
        $this->cargarLocalesDisponibles();
    }

    /**
     * Cargar los locales disponibles para el selector
     */
    private function cargarLocalesDisponibles(): void
    {
        try {
            // Obtener los locales activos desde la base de datos
            $this->localesDisponibles = Local::getActivosParaSelector();

            Log::info('[ServicioExpress] Se cargaron '.count($this->localesDisponibles).' locales disponibles');
        } catch (\Exception $e) {
            Log::error('[ServicioExpress] Error al cargar locales disponibles: '.$e->getMessage());

            // Inicializar con un array vacío
            $this->localesDisponibles = [];
        }
    }

    public function cargarVehiculos(): void
    {
        try {
            // Obtener los vehículos desde la base de datos
            $vehiculosDB = VehiculoExpress::all();

            // Transformar los datos para que coincidan con el formato esperado
            $this->vehiculos = collect();

            foreach ($vehiculosDB as $vehiculo) {
                $this->vehiculos->push([
                    'id' => $vehiculo->id,
                    'model' => $vehiculo->model,
                    'brand' => $vehiculo->brand,
                    'year' => $vehiculo->year,
                    'maintenance' => $vehiculo->maintenance,
                    'premises' => $vehiculo->premises,
                    'is_active' => $vehiculo->is_active,
                ]);
            }

            // Inicializar el estado de los vehículos
            foreach ($this->vehiculos as $vehiculo) {
                $this->estadoVehiculos[$vehiculo['id']] = $vehiculo['is_active'];
            }

            Log::info('[ServicioExpress] Se cargaron '.$this->vehiculos->count().' vehículos desde la base de datos');
        } catch (\Exception $e) {
            Log::error('[ServicioExpress] Error al cargar vehículos: '.$e->getMessage());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al cargar vehículos')
                ->body('Ha ocurrido un error al cargar los vehículos: '.$e->getMessage())
                ->danger()
                ->send();

            // Inicializar con una colección vacía
            $this->vehiculos = collect();
        }
    }

    public function getVehiculosPaginadosProperty(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $this->vehiculos->forPage($this->currentPage, $this->perPage),
            $this->vehiculos->count(),
            $this->perPage,
            $this->currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    public function toggleEstado(int $id): void
    {
        try {
            // Invertir el estado actual
            $this->estadoVehiculos[$id] = ! $this->estadoVehiculos[$id];

            // Actualizar el estado en la colección de vehículos
            $this->vehiculos = $this->vehiculos->map(function ($vehiculo) use ($id) {
                if ($vehiculo['id'] === $id) {
                    $vehiculo['is_active'] = $this->estadoVehiculos[$id];
                }

                return $vehiculo;
            });

            // Actualizar el estado en la base de datos
            $vehiculo = VehiculoExpress::findOrFail($id);
            $vehiculo->is_active = $this->estadoVehiculos[$id];
            $vehiculo->save();

            // Mostrar notificación con el estado actual
            $estado = $this->estadoVehiculos[$id] ? 'activado' : 'desactivado';
            \Filament\Notifications\Notification::make()
                ->title('Estado actualizado')
                ->body("El vehículo ha sido {$estado}")
                ->success()
                ->send();

            Log::info("[ServicioExpress] Se actualizó el estado del vehículo {$id} a {$estado}");
        } catch (\Exception $e) {
            Log::error("[ServicioExpress] Error al actualizar estado del vehículo {$id}: ".$e->getMessage());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al actualizar estado')
                ->body('Ha ocurrido un error al actualizar el estado del vehículo: '.$e->getMessage())
                ->danger()
                ->send();

            // Revertir el cambio en la memoria
            $this->estadoVehiculos[$id] = ! $this->estadoVehiculos[$id];

            // Recargar los vehículos para asegurar consistencia
            $this->cargarVehiculos();
        }
    }

    public function editar(int $id): void
    {
        try {
            // Buscar el vehículo en la base de datos
            $vehiculo = VehiculoExpress::findOrFail($id);

            // Cargar los datos del vehículo en el array de edición
            $this->vehiculoEnEdicion = [
                'id' => $vehiculo->id,
                'model' => $vehiculo->model,
                'brand' => $vehiculo->brand,
                'year' => $vehiculo->year,
                'maintenance' => $vehiculo->maintenance,
                'premises' => $vehiculo->premises,
            ];

            // Abrir el modal de edición
            $this->isEditModalOpen = true;

            Log::info("[ServicioExpress] Se inició la edición del vehículo {$id}");
        } catch (\Exception $e) {
            Log::error("[ServicioExpress] Error al editar vehículo {$id}: ".$e->getMessage());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al editar vehículo')
                ->body('Ha ocurrido un error al editar el vehículo: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Método para cerrar el modal de edición
     */
    public function cerrarModalEdicion(): void
    {
        // Cerrar el modal y resetear valores
        $this->isEditModalOpen = false;
        $this->resetVehiculoEnEdicion();
    }

    /**
     * Método para resetear el vehículo en edición
     */
    private function resetVehiculoEnEdicion(): void
    {
        $this->vehiculoEnEdicion = [
            'id' => null,
            'model' => '',
            'brand' => '',
            'year' => '',
            'maintenance' => '',
            'premises' => '',
        ];
    }

    /**
     * Método para guardar los cambios del vehículo en edición
     */
    public function guardarCambios(): void
    {
        try {
            // Validar los datos
            if (empty($this->vehiculoEnEdicion['model'])) {
                throw new \Exception('El modelo es obligatorio');
            }

            if (empty($this->vehiculoEnEdicion['brand'])) {
                throw new \Exception('La marca es obligatoria');
            }

            if (empty($this->vehiculoEnEdicion['year'])) {
                throw new \Exception('El año es obligatorio');
            }

            if (empty($this->vehiculoEnEdicion['maintenance']) || ! is_array($this->vehiculoEnEdicion['maintenance'])) {
                throw new \Exception('Debe seleccionar al menos un tipo de mantenimiento');
            }

            if (empty($this->vehiculoEnEdicion['premises'])) {
                throw new \Exception('El local es obligatorio');
            }

            // Buscar el vehículo en la base de datos
            $vehiculo = VehiculoExpress::findOrFail($this->vehiculoEnEdicion['id']);

            // Actualizar los datos
            $vehiculo->model = $this->vehiculoEnEdicion['model'];
            $vehiculo->brand = $this->vehiculoEnEdicion['brand'];
            $vehiculo->year = $this->vehiculoEnEdicion['year'];
            $vehiculo->maintenance = $this->vehiculoEnEdicion['maintenance'];
            $vehiculo->premises = $this->vehiculoEnEdicion['premises'];

            // Guardar los cambios
            $vehiculo->save();

            // Recargar los vehículos
            $this->cargarVehiculos();

            // Mostrar notificación de éxito
            \Filament\Notifications\Notification::make()
                ->title('Vehículo actualizado')
                ->body('El vehículo ha sido actualizado correctamente')
                ->success()
                ->send();

            Log::info("[ServicioExpress] Se actualizó el vehículo {$vehiculo->id}");

            // Cerrar el modal
            $this->cerrarModalEdicion();
        } catch (\Exception $e) {
            Log::error('[ServicioExpress] Error al guardar cambios del vehículo: '.$e->getMessage());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al guardar cambios')
                ->body('Ha ocurrido un error al guardar los cambios: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function eliminar(int $id): void
    {
        try {
            // Buscar el vehículo en la base de datos
            $vehiculo = VehiculoExpress::findOrFail($id);

            // Guardar información para la notificación
            $modelo = $vehiculo->model;
            $marca = $vehiculo->brand;

            // Eliminar el vehículo
            $vehiculo->delete();

            // Actualizar la colección de vehículos
            $this->vehiculos = $this->vehiculos->filter(function ($vehiculo) use ($id) {
                return $vehiculo['id'] !== $id;
            });

            // Eliminar el estado del vehículo
            unset($this->estadoVehiculos[$id]);

            // Mostrar notificación
            \Filament\Notifications\Notification::make()
                ->title('Vehículo eliminado')
                ->body("El vehículo {$modelo} {$marca} ha sido eliminado")
                ->success()
                ->send();

            Log::info("[ServicioExpress] Se eliminó el vehículo {$id} ({$modelo} {$marca})");
        } catch (\Exception $e) {
            Log::error("[ServicioExpress] Error al eliminar vehículo {$id}: ".$e->getMessage());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al eliminar vehículo')
                ->body('Ha ocurrido un error al eliminar el vehículo: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function registrarVehiculo(): void
    {
        // Abrir el modal
        $this->isModalOpen = true;
    }

    public function cerrarModal(): void
    {
        // Cerrar el modal y resetear valores
        $this->isModalOpen = false;
        $this->archivoExcel = null;
        $this->nombreArchivo = 'Sin selección';
    }

    // Este método ya no es necesario porque Livewire maneja automáticamente
    // la actualización de la propiedad archivoExcel a través de wire:model

    // Método para actualizar el nombre del archivo cuando se selecciona uno nuevo
    public function updatedArchivoExcel(): void
    {
        if ($this->archivoExcel) {
            $this->nombreArchivo = $this->archivoExcel->getClientOriginalName();
            Log::info("[ServicioExpress] Archivo seleccionado: {$this->nombreArchivo}");
        } else {
            $this->nombreArchivo = 'Sin selección';
        }
    }

    public function cargarArchivo(): void
    {
        Log::info('[ServicioExpress] Iniciando carga de archivo Excel');

        if (! $this->archivoExcel) {
            Log::warning('[ServicioExpress] No se ha seleccionado ningún archivo');
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Debes seleccionar un archivo Excel')
                ->danger()
                ->send();

            return;
        }

        Log::info("[ServicioExpress] Archivo seleccionado: {$this->nombreArchivo}");

        try {
            // Verificar que el archivo existe y es accesible
            $realPath = $this->archivoExcel->getRealPath();
            if (! $realPath || ! file_exists($realPath)) {
                throw new \Exception('No se puede acceder al archivo. Ruta: '.($realPath ?: 'No disponible'));
            }

            Log::info("[ServicioExpress] Ruta del archivo: {$realPath}");

            // Verificar el tipo de archivo
            $mimeType = $this->archivoExcel->getMimeType();
            Log::info("[ServicioExpress] Tipo MIME del archivo: {$mimeType}");

            // Procesar el archivo Excel
            $spreadsheet = IOFactory::load($realPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            Log::info('[ServicioExpress] Archivo cargado correctamente. Filas encontradas: '.count($rows));

            // Depuración: Mostrar las primeras 5 filas del archivo (o menos si hay menos filas)
            $numFilasAMostrar = min(5, count($rows));
            for ($i = 0; $i < $numFilasAMostrar; $i++) {
                Log::info("[ServicioExpress] Fila {$i}: ".json_encode($rows[$i]));
            }

            // Verificar que el archivo tenga el formato correcto
            if (count($rows) < 2) {
                throw new \Exception('El archivo no contiene datos');
            }

            // Verificar que las cabeceras sean correctas
            $cabeceras = $rows[0];
            $cabecerasEsperadas = ['Model', 'Brand', 'Year', 'Maintenance', 'Premises'];

            // Normalizar las cabeceras (eliminar espacios, convertir a minúsculas)
            $cabecerasNormalizadas = array_map(function ($cabecera) {
                return trim(strtolower($cabecera));
            }, $cabeceras);

            $cabecerasEsperadasNormalizadas = array_map(function ($cabecera) {
                return trim(strtolower($cabecera));
            }, $cabecerasEsperadas);

            Log::info('[ServicioExpress] Cabeceras encontradas: '.implode(', ', $cabeceras));
            Log::info('[ServicioExpress] Cabeceras normalizadas: '.implode(', ', $cabecerasNormalizadas));

            // Verificar si hay cabeceras faltantes
            $cabecerasFaltantes = array_diff($cabecerasEsperadasNormalizadas, $cabecerasNormalizadas);
            if (count($cabecerasFaltantes) > 0) {
                throw new \Exception('El formato del archivo no es correcto. Faltan las siguientes cabeceras: '.implode(', ', $cabecerasFaltantes));
            }

            // Verificar si hay cabeceras adicionales (esto no es un error, solo informativo)
            $cabecerasAdicionales = array_diff($cabecerasNormalizadas, $cabecerasEsperadasNormalizadas);
            if (count($cabecerasAdicionales) > 0) {
                Log::info('[ServicioExpress] Se encontraron cabeceras adicionales que serán ignoradas: '.implode(', ', $cabecerasAdicionales));
            }

            // Procesar los datos
            $vehiculosAgregados = 0;
            $filasProcesadas = 0;
            $filasVacias = 0;

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $filasProcesadas++;

                // Verificar que la fila tenga datos
                if (empty($row[0]) && empty($row[1]) && empty($row[2]) && empty($row[3]) && empty($row[4])) {
                    $filasVacias++;

                    continue;
                }

                // Registrar la fila que se está procesando
                Log::info("[ServicioExpress] Procesando fila {$i}: ".implode(', ', array_slice($row, 0, 5)));

                try {
                    // Procesar el campo de mantenimiento
                    $mantenimientoRaw = $row[3] ?? 'Sin mantenimiento';
                    $mantenimiento = $this->procesarMantenimiento($mantenimientoRaw);

                    // Crear el vehículo
                    $vehiculo = new VehiculoExpress;
                    $vehiculo->model = $row[0] ?? 'Sin modelo';
                    $vehiculo->brand = $row[1] ?? 'Sin marca';
                    $vehiculo->year = $row[2] ?? 'Sin año';
                    $vehiculo->maintenance = $mantenimiento;
                    $vehiculo->premises = $row[4] ?? 'Sin local';
                    $vehiculo->is_active = true;
                    $vehiculo->save();

                    Log::info("[ServicioExpress] Vehículo guardado: ID {$vehiculo->id}, Mantenimientos: ".json_encode($mantenimiento));
                    $vehiculosAgregados++;
                } catch (\Exception $innerEx) {
                    Log::error("[ServicioExpress] Error al guardar vehículo en fila {$i}: ".$innerEx->getMessage());
                    // Continuamos con la siguiente fila
                }
            }

            Log::info("[ServicioExpress] Resumen de procesamiento: Filas procesadas: {$filasProcesadas}, Filas vacías: {$filasVacias}, Vehículos agregados: {$vehiculosAgregados}");

            // Recargar los vehículos
            $this->cargarVehiculos();

            // Mostrar notificación de éxito
            \Filament\Notifications\Notification::make()
                ->title('Archivo cargado')
                ->body("Se han agregado {$vehiculosAgregados} vehículos correctamente")
                ->success()
                ->send();

            Log::info("[ServicioExpress] Se cargaron {$vehiculosAgregados} vehículos desde el archivo {$this->nombreArchivo}");
        } catch (\Exception $e) {
            Log::error('[ServicioExpress] Error al cargar archivo: '.$e->getMessage());
            Log::error('[ServicioExpress] Traza de la excepción: '.$e->getTraceAsString());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al cargar archivo')
                ->body('Ha ocurrido un error al cargar el archivo: '.$e->getMessage())
                ->danger()
                ->send();
        }

        $this->cerrarModal();
    }

    /**
     * Procesar el campo de mantenimiento desde Excel
     * Puede ser un string simple o un JSON array
     */
    private function procesarMantenimiento($mantenimientoRaw): array
    {
        try {
            // Si está vacío, devolver array vacío
            if (empty($mantenimientoRaw) || $mantenimientoRaw === 'Sin mantenimiento') {
                return [];
            }

            // Intentar decodificar como JSON primero
            if (is_string($mantenimientoRaw) && (str_starts_with($mantenimientoRaw, '[') || str_starts_with($mantenimientoRaw, '{"'))) {
                $decoded = json_decode($mantenimientoRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    Log::info('[ServicioExpress] Mantenimiento procesado como JSON: '.json_encode($decoded));

                    return $decoded;
                }
            }

            // Si no es JSON válido, tratarlo como string simple
            $mantenimientoLimpio = trim($mantenimientoRaw);
            Log::info("[ServicioExpress] Mantenimiento procesado como string: [{$mantenimientoLimpio}]");

            return [$mantenimientoLimpio];

        } catch (\Exception $e) {
            Log::error("[ServicioExpress] Error al procesar mantenimiento '{$mantenimientoRaw}': ".$e->getMessage());

            // En caso de error, devolver como array con el valor original
            return [trim($mantenimientoRaw)];
        }
    }

    /**
     * Método para descargar la plantilla Excel
     */
    public function descargarPlantilla(): void
    {
        try {
            // Crear un nuevo archivo Excel
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();

            // Establecer las cabeceras
            $sheet->setCellValue('A1', 'Model');
            $sheet->setCellValue('B1', 'Brand');
            $sheet->setCellValue('C1', 'Year');
            $sheet->setCellValue('D1', 'Maintenance');
            $sheet->setCellValue('E1', 'Premises');

            // Dar formato a las cabeceras
            $sheet->getStyle('A1:E1')->getFont()->setBold(true);
            $sheet->getStyle('A1:E1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle('A1:E1')->getFill()->getStartColor()->setARGB('FF3B82F6'); // Color primary
            $sheet->getStyle('A1:E1')->getFont()->getColor()->setARGB('FFFFFFFF'); // Texto blanco

            // Ajustar el ancho de las columnas
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(10);
            $sheet->getColumnDimension('D')->setWidth(35); // Más ancho para múltiples mantenimientos
            $sheet->getColumnDimension('E')->setWidth(20);

            // Agregar ejemplos con múltiples mantenimientos
            $sheet->setCellValue('A2', 'YARIS CROSS');
            $sheet->setCellValue('B2', 'Toyota');
            $sheet->setCellValue('C2', '2024');
            $sheet->setCellValue('D2', '["10,000 Km","20,000 Km","30,000 Km"]');
            $sheet->setCellValue('E2', 'Mitsui La Molina');

            $sheet->setCellValue('A3', 'Corolla');
            $sheet->setCellValue('B3', 'Toyota');
            $sheet->setCellValue('C3', '2023');
            $sheet->setCellValue('D3', '["10,000 Km","20,000 Km"]');
            $sheet->setCellValue('E3', 'Mitsui Miraflores');

            $sheet->setCellValue('A4', 'RAV4');
            $sheet->setCellValue('B4', 'Toyota');
            $sheet->setCellValue('C4', '2024');
            $sheet->setCellValue('D4', '["40,000 Km","50,000 Km","60,000 Km"]');
            $sheet->setCellValue('E4', 'Mitsui Canadá');

            // Agregar comentarios explicativos
            $sheet->setCellValue('A6', 'INSTRUCCIONES:');
            $sheet->setCellValue('A7', '• Para UN mantenimiento: 10,000 Km');
            $sheet->setCellValue('A8', '• Para MÚLTIPLES mantenimientos: ["10,000 Km","20,000 Km","30,000 Km"]');
            $sheet->setCellValue('A9', '• Use comillas dobles y corchetes para múltiples valores');
            $sheet->setCellValue('A10', '• Separe cada mantenimiento con coma');

            // Dar formato a las instrucciones
            $sheet->getStyle('A6')->getFont()->setBold(true);
            $sheet->getStyle('A6')->getFont()->setSize(12);
            $sheet->getStyle('A7:A10')->getFont()->setItalic(true);
            $sheet->getStyle('A7:A10')->getFont()->setSize(10);

            // Crear el directorio si no existe
            if (! Storage::disk('public')->exists('plantillas')) {
                Storage::disk('public')->makeDirectory('plantillas');
            }

            // Guardar el archivo
            $writer = new Xlsx($spreadsheet);
            $path = storage_path('app/public/plantillas/plantilla_vehiculos_express.xlsx');
            $writer->save($path);

            // Generar la URL para descargar el archivo
            $url = asset('storage/plantillas/plantilla_vehiculos_express.xlsx');

            // Redirigir al usuario a la URL de descarga
            redirect()->away($url);

            Log::info('[ServicioExpress] Se generó la plantilla Excel para descargar');
        } catch (\Exception $e) {
            Log::error('[ServicioExpress] Error al generar plantilla Excel: '.$e->getMessage());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al generar plantilla')
                ->body('Ha ocurrido un error al generar la plantilla Excel: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
