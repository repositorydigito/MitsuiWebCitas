<?php

namespace App\Filament\Pages;

use App\Models\Local;
use App\Models\VehiculoExpress;
use App\Models\MaintenanceType;
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
    use HasPageShield, WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-bolt';

    protected static ?string $navigationLabel = 'Servicio Express';

    protected static ?string $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Gestión servicio express';

    protected static string $view = 'filament.pages.servicio-express';

    // Propiedades para la tabla
    public Collection $vehiculos;

    public int $perPage = 10;

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
        'code' => '',
        'type' => '',
        'model' => '',
        'brand' => '',
        'year' => '',
        'maintenance' => '',
        'premises' => '',
    ];

    // Lista de locales disponibles para el selector
    public array $localesDisponibles = [];

    // Lista de tipos de mantenimiento disponibles para el selector
    public array $tiposMantenimientoDisponibles = [];

    // Propiedades para los filtros
    public string $filtroModelo = '';
    public string $filtroMarca = '';
    public string $filtroLocal = '';
    
    // Locales filtrados por marca
    public array $localesFiltrados = [];

    public function mount(): void
    {
        $this->currentPage = (int) request()->query('page', 1);
        $this->cargarVehiculos();
        $this->cargarLocalesDisponibles();
        $this->cargarTiposMantenimientoDisponibles();
        $this->actualizarLocalesFiltrados();
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

    /**
     * Actualizar los locales filtrados según la marca seleccionada
     */
    private function actualizarLocalesFiltrados(): void
    {
        try {
            if (empty($this->filtroMarca)) {
                // Si no hay marca seleccionada, mostrar todos los locales
                $this->localesFiltrados = $this->localesDisponibles;
            } else {
                // Filtrar locales por marca seleccionada
                $localesPorMarca = Local::where('is_active', true)
                    ->where('brand', $this->filtroMarca)
                    ->orderBy('name')
                    ->pluck('name', 'code')
                    ->toArray();
                
                $this->localesFiltrados = $localesPorMarca;
                
                // Si el local actualmente seleccionado no pertenece a la marca, limpiarlo
                if (!empty($this->filtroLocal) && !in_array($this->filtroLocal, $this->localesFiltrados)) {
                    $this->filtroLocal = '';
                }
            }

            Log::info("[ServicioExpress] Locales filtrados por marca '{$this->filtroMarca}': " . count($this->localesFiltrados));
        } catch (\Exception $e) {
            Log::error('[ServicioExpress] Error al filtrar locales por marca: ' . $e->getMessage());
            $this->localesFiltrados = [];
        }
    }



    /**
     * Cargar los tipos de mantenimiento disponibles para el selector
     */
    private function cargarTiposMantenimientoDisponibles(): void
    {
        try {
            $this->tiposMantenimientoDisponibles = MaintenanceType::getActivosParaSelector();
        } catch (\Exception $e) {
            $this->tiposMantenimientoDisponibles = [];
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
                    'code' => $vehiculo->code,
                    'type' => $vehiculo->type,
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
        // Aplicar filtros
        $vehiculosFiltrados = $this->vehiculos;

        // Filtro por modelo
        if (!empty($this->filtroModelo)) {
            $vehiculosFiltrados = $vehiculosFiltrados->filter(function ($vehiculo) {
                return stripos($vehiculo['model'], $this->filtroModelo) !== false;
            });
        }

        // Filtro por marca
        if (!empty($this->filtroMarca)) {
            $vehiculosFiltrados = $vehiculosFiltrados->filter(function ($vehiculo) {
                return strtoupper($vehiculo['brand']) === strtoupper($this->filtroMarca);
            });
        }

        // Filtro por local
        if (!empty($this->filtroLocal)) {
            $vehiculosFiltrados = $vehiculosFiltrados->filter(function ($vehiculo) {
                return stripos($vehiculo['premises'], $this->filtroLocal) !== false;
            });
        }

        return new LengthAwarePaginator(
            $vehiculosFiltrados->forPage($this->currentPage, $this->perPage),
            $vehiculosFiltrados->count(),
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
                'code' => $vehiculo->code,
                'type' => $vehiculo->type,
                'model' => $vehiculo->model,
                'brand' => $vehiculo->brand,
                'year' => is_array($vehiculo->year) ? implode(',', $vehiculo->year) : $vehiculo->year,
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
            'code' => '',
            'type' => '',
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

            if (empty($this->vehiculoEnEdicion['year']) || (!is_array($this->vehiculoEnEdicion['year']) && !is_string($this->vehiculoEnEdicion['year']))) {
                throw new \Exception('El código de motor es obligatorio');
            }

            if (empty($this->vehiculoEnEdicion['maintenance']) || ! is_array($this->vehiculoEnEdicion['maintenance'])) {
                throw new \Exception('Debe seleccionar al menos un tipo de mantenimiento');
            }

            if (empty($this->vehiculoEnEdicion['premises'])) {
                throw new \Exception('El local es obligatorio');
            }

            // Buscar el vehículo en la base de datos
            $vehiculo = VehiculoExpress::findOrFail($this->vehiculoEnEdicion['id']);

            // Procesar códigos de motor desde el input (convertir string separado por comas a array)
            $codigosMotor = [];
            if (is_string($this->vehiculoEnEdicion['year'])) {
                $codigosMotor = array_map('trim', explode(',', $this->vehiculoEnEdicion['year']));
                $codigosMotor = array_filter($codigosMotor); // Eliminar elementos vacíos
            } elseif (is_array($this->vehiculoEnEdicion['year'])) {
                $codigosMotor = $this->vehiculoEnEdicion['year'];
            }

            // Actualizar los datos
            $vehiculo->code = $this->vehiculoEnEdicion['code'];
            $vehiculo->type = $this->vehiculoEnEdicion['type'];
            $vehiculo->model = $this->vehiculoEnEdicion['model'];
            $vehiculo->brand = $this->vehiculoEnEdicion['brand'];
            $vehiculo->year = $codigosMotor; // Guardar como array
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
        
        // No mostrar notificaciones aquí para evitar duplicados
    }

    public function cargarArchivo(): void
    {
        Log::info('[ServicioExpress] Iniciando carga de archivo Excel');

        // Validación del archivo
        if (!$this->archivoExcel) {
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
            
            // Crear una copia temporal del archivo para evitar problemas con Livewire
            $tempPath = storage_path('app/temp/excel_upload_' . uniqid() . '.xlsx');
            $tempDir = dirname($tempPath);
            
            // Crear el directorio temporal si no existe
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Copiar el archivo a la ubicación temporal
            if (!copy($realPath, $tempPath)) {
                throw new \Exception('No se pudo crear una copia temporal del archivo');
            }
            
            Log::info("[ServicioExpress] Archivo copiado a: {$tempPath}");

            // Verificar el tipo de archivo
            $mimeType = $this->archivoExcel->getMimeType();
            $extension = strtolower($this->archivoExcel->getClientOriginalExtension());
            Log::info("[ServicioExpress] Tipo MIME del archivo: {$mimeType}");
            Log::info("[ServicioExpress] Extensión del archivo: {$extension}");

            // Información detallada del archivo para diagnóstico
            $fileSize = filesize($realPath);
            $fileInfo = pathinfo($realPath);
            
            Log::info("[ServicioExpress] Información del archivo:");
            Log::info("[ServicioExpress] - Tamaño: {$fileSize} bytes");
            Log::info("[ServicioExpress] - Extensión real: " . ($fileInfo['extension'] ?? 'sin extensión'));
            Log::info("[ServicioExpress] - Es legible: " . (is_readable($realPath) ? 'SÍ' : 'NO'));
            
            // Leer los primeros bytes del archivo para diagnóstico
            $handle = fopen($realPath, 'rb');
            $firstBytes = fread($handle, 16);
            fclose($handle);
            $hexBytes = bin2hex($firstBytes);
            Log::info("[ServicioExpress] - Primeros 16 bytes (hex): {$hexBytes}");
            
            // Validar que sea un archivo Excel válido
            $tiposPermitidos = [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                'application/vnd.ms-excel', // .xls
                'application/octet-stream', // Fallback para algunos navegadores
                'application/zip' // Los archivos .xlsx son básicamente ZIP
            ];

            $extensionesPermitidas = ['xlsx', 'xls'];

            // Verificar si el archivo está vacío
            if ($fileSize === 0) {
                throw new \Exception("El archivo está vacío (0 bytes)");
            }
            
            // Verificar si el archivo es muy pequeño para ser un Excel válido
            if ($fileSize < 100) {
                throw new \Exception("El archivo es demasiado pequeño para ser un Excel válido ({$fileSize} bytes)");
            }

            // Intentar múltiples métodos para cargar el archivo
            $spreadsheet = null;
            $metodosIntentados = [];
            
            // Método 1: Intentar identificar automáticamente usando la copia temporal
            try {
                $inputFileType = IOFactory::identify($tempPath);
                Log::info("[ServicioExpress] Tipo de archivo identificado: {$inputFileType}");
                
                $reader = IOFactory::createReader($inputFileType);
                $reader->setReadDataOnly(true); // Solo leer datos, no formato
                $spreadsheet = $reader->load($tempPath);
                $metodosIntentados[] = "Identificación automática ({$inputFileType})";
            } catch (\Exception $identifyException) {
                Log::warning("[ServicioExpress] Método 1 falló: {$identifyException->getMessage()}");
                $metodosIntentados[] = "Identificación automática (FALLÓ)";
            }
            
            // Método 2: Intentar como XLSX específicamente
            if (!$spreadsheet) {
                try {
                    $reader = IOFactory::createReader('Xlsx');
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load($tempPath);
                    $metodosIntentados[] = "XLSX específico (ÉXITO)";
                } catch (\Exception $xlsxException) {
                    Log::warning("[ServicioExpress] Método 2 (XLSX) falló: {$xlsxException->getMessage()}");
                    $metodosIntentados[] = "XLSX específico (FALLÓ)";
                }
            }
            
            // Método 3: Intentar como XLS específicamente
            if (!$spreadsheet) {
                try {
                    $reader = IOFactory::createReader('Xls');
                    $reader->setReadDataOnly(true);
                    $spreadsheet = $reader->load($tempPath);
                    $metodosIntentados[] = "XLS específico (ÉXITO)";
                } catch (\Exception $xlsException) {
                    Log::warning("[ServicioExpress] Método 3 (XLS) falló: {$xlsException->getMessage()}");
                    $metodosIntentados[] = "XLS específico (FALLÓ)";
                }
            }
            
            // Método 4: Intentar con CSV como último recurso
            if (!$spreadsheet) {
                try {
                    $reader = IOFactory::createReader('Csv');
                    $reader->setDelimiter(',');
                    $reader->setEnclosure('"');
                    $reader->setSheetIndex(0);
                    $spreadsheet = $reader->load($tempPath);
                    $metodosIntentados[] = "CSV (ÉXITO)";
                } catch (\Exception $csvException) {
                    Log::warning("[ServicioExpress] Método 4 (CSV) falló: {$csvException->getMessage()}");
                    $metodosIntentados[] = "CSV (FALLÓ)";
                }
            }
            
            // Si ningún método funcionó, lanzar excepción detallada
            if (!$spreadsheet) {
                $metodosTexto = implode(', ', $metodosIntentados);
                throw new \Exception("No se pudo cargar el archivo Excel. Métodos intentados: {$metodosTexto}. Asegúrate de que el archivo sea un Excel válido (.xlsx o .xls) y no esté corrupto.");
            }
            
            Log::info("[ServicioExpress] Archivo cargado exitosamente. Métodos intentados: " . implode(', ', $metodosIntentados));
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
            $cabecerasEsperadas = ['Model', 'Brand', 'Motor Code', 'Maintenance', 'Premises', 'Code', 'Type'];

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
                if (empty($row[0]) && empty($row[1]) && empty($row[2]) && empty($row[3]) && empty($row[4]) && empty($row[5]) && empty($row[6])) {
                    $filasVacias++;

                    continue;
                }

                // Registrar la fila que se está procesando
                Log::info("[ServicioExpress] Procesando fila {$i}: ".implode(', ', array_slice($row, 0, 5)));

                try {
                    // Procesar el campo de mantenimiento
                    $mantenimientoRaw = $row[3] ?? 'Sin mantenimiento';
                    $mantenimiento = $this->procesarMantenimiento($mantenimientoRaw);

                    // Procesar el campo de códigos de motor
                    $codigosMotorRaw = $row[2] ?? 'Sin código';
                    $codigosMotor = $this->procesarCodigosMotor($codigosMotorRaw);

                    // Crear el vehículo
                    $vehiculo = new VehiculoExpress;
                    $vehiculo->code = $row[5] ?? null; // Nueva columna Code
                    $vehiculo->type = $row[6] ?? null; // Nueva columna Type
                    $vehiculo->model = $row[0] ?? 'Sin modelo';
                    $vehiculo->brand = $row[1] ?? 'Sin marca';
                    $vehiculo->year = $codigosMotor; // Ahora es un array de códigos de motor
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
        } finally {
            // Limpiar el archivo temporal si existe
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
                Log::info("[ServicioExpress] Archivo temporal eliminado: {$tempPath}");
            }
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
     * Procesar el campo de códigos de motor desde Excel
     * Puede ser un string simple o un JSON array
     */
    private function procesarCodigosMotor($codigosMotorRaw): array
    {
        try {
            // Si está vacío, devolver array vacío
            if (empty($codigosMotorRaw) || $codigosMotorRaw === 'Sin código') {
                return [];
            }

            // Intentar decodificar como JSON primero
            if (is_string($codigosMotorRaw) && (str_starts_with($codigosMotorRaw, '[') || str_starts_with($codigosMotorRaw, '{"'))) {
                $decoded = json_decode($codigosMotorRaw, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    Log::info('[ServicioExpress] Códigos de motor procesados como JSON: '.json_encode($decoded));

                    return $decoded;
                }
            }

            // Si no es JSON válido, tratarlo como string simple
            $codigoLimpio = trim($codigosMotorRaw);
            Log::info("[ServicioExpress] Código de motor procesado como string: [{$codigoLimpio}]");

            return [$codigoLimpio];

        } catch (\Exception $e) {
            Log::error("[ServicioExpress] Error al procesar códigos de motor '{$codigosMotorRaw}': ".$e->getMessage());

            // En caso de error, devolver como array con el valor original
            return [trim($codigosMotorRaw)];
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

            // Establecer las cabeceras en inglés
            $sheet->setCellValue('A1', 'Model');
            $sheet->setCellValue('B1', 'Brand');
            $sheet->setCellValue('C1', 'Motor Code');
            $sheet->setCellValue('D1', 'Maintenance');
            $sheet->setCellValue('E1', 'Premises');
            $sheet->setCellValue('F1', 'Code');
            $sheet->setCellValue('G1', 'Type');

            // Verificar que las cabeceras se establecieron correctamente
            $headerA1 = $sheet->getCell('A1')->getValue();
            $headerB1 = $sheet->getCell('B1')->getValue();
            $headerC1 = $sheet->getCell('C1')->getValue();
            $headerD1 = $sheet->getCell('D1')->getValue();
            $headerE1 = $sheet->getCell('E1')->getValue();
            $headerF1 = $sheet->getCell('F1')->getValue();
            $headerG1 = $sheet->getCell('G1')->getValue();

            Log::info("[ServicioExpress] Verificación de cabeceras: A1={$headerA1}, B1={$headerB1}, C1={$headerC1}, D1={$headerD1}, E1={$headerE1}, F1={$headerF1}, G1={$headerG1}");

            // Dar formato a las cabeceras
            $sheet->getStyle('A1:G1')->getFont()->setBold(true);
            $sheet->getStyle('A1:G1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle('A1:G1')->getFill()->getStartColor()->setARGB('FF3B82F6'); // Color primary
            $sheet->getStyle('A1:G1')->getFont()->getColor()->setARGB('FFFFFFFF'); // Texto blanco

            // Ajustar el ancho de las columnas
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(15);
            $sheet->getColumnDimension('C')->setWidth(10);
            $sheet->getColumnDimension('D')->setWidth(35); // Más ancho para múltiples mantenimientos
            $sheet->getColumnDimension('E')->setWidth(20);
            $sheet->getColumnDimension('F')->setWidth(15); // Code
            $sheet->getColumnDimension('G')->setWidth(15); // Type

            // Crear el directorio si no existe
            if (! Storage::disk('public')->exists('plantillas')) {
                Storage::disk('public')->makeDirectory('plantillas');
            }

            // Agregar ejemplos con múltiples códigos de motor y mantenimientos
            $sheet->setCellValue('A2', 'YARIS CROSS');
            $sheet->setCellValue('B2', 'Toyota');
            $sheet->setCellValue('C2', '["2GD","3FR"]');
            $sheet->setCellValue('D2', '["10,000 Km","20,000 Km","30,000 Km"]');
            $sheet->setCellValue('E2', 'Mitsui La Molina');
            $sheet->setCellValue('F2', 'YC001');
            $sheet->setCellValue('G2', 'Express');

            $sheet->setCellValue('A3', 'Corolla');
            $sheet->setCellValue('B3', 'Toyota');
            $sheet->setCellValue('C3', '1ZZ');
            $sheet->setCellValue('D3', '["10,000 Km","20,000 Km"]');
            $sheet->setCellValue('E3', 'Mitsui Miraflores');
            $sheet->setCellValue('F3', 'COR001');
            $sheet->setCellValue('G3', 'Standard');

            $sheet->setCellValue('A4', 'RAV4');
            $sheet->setCellValue('B4', 'Toyota');
            $sheet->setCellValue('C4', '["2AR","3ZR","2GR"]');
            $sheet->setCellValue('D4', '["40,000 Km","50,000 Km","60,000 Km"]');
            $sheet->setCellValue('E4', 'Mitsui Canadá');
            $sheet->setCellValue('F4', 'RAV001');
            $sheet->setCellValue('G4', 'Premium');

            // Agregar comentarios explicativos
            $sheet->setCellValue('A6', 'INSTRUCCIONES:');
            $sheet->setCellValue('A7', '• Para UN código de motor: 2GD');
            $sheet->setCellValue('A8', '• Para MÚLTIPLES códigos: ["2GD","3FR","1ZZ"]');
            $sheet->setCellValue('A9', '• Para UN mantenimiento: 10,000 Km');
            $sheet->setCellValue('A10', '• Para MÚLTIPLES mantenimientos: ["10,000 Km","20,000 Km","30,000 Km"]');
            $sheet->setCellValue('A11', '• Use comillas dobles y corchetes para múltiples valores');
            $sheet->setCellValue('A12', '• Separe cada valor con coma');

            // Dar formato a las instrucciones
            $sheet->getStyle('A6')->getFont()->setBold(true);
            $sheet->getStyle('A6')->getFont()->setSize(12);
            $sheet->getStyle('A7:A10')->getFont()->setItalic(true);
            $sheet->getStyle('A7:A10')->getFont()->setSize(10);

            // Crear el directorio si no existe
            if (! Storage::disk('public')->exists('plantillas')) {
                Storage::disk('public')->makeDirectory('plantillas');
            }

            // Guardar el archivo con timestamp para evitar caché
            $timestamp = date('Y-m-d_H-i-s');
            $filename = "plantilla_vehiculos_express_{$timestamp}.xlsx";
            $writer = new Xlsx($spreadsheet);
            $path = storage_path("app/public/plantillas/{$filename}");
            $writer->save($path);

            Log::info("[ServicioExpress] Archivo guardado en: {$path}");

            // Generar la URL para descargar el archivo
            $url = asset("storage/plantillas/{$filename}");

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

    /**
     * Método para limpiar los filtros
     */
    public function limpiarFiltros(): void
    {
        $this->filtroModelo = '';
        $this->filtroMarca = '';
        $this->filtroLocal = '';
        $this->currentPage = 1; // Resetear a la primera página
        $this->actualizarLocalesFiltrados(); // Actualizar locales filtrados
    }

    /**
     * Método que se ejecuta cuando cambia el filtro de modelo
     */
    public function updatedFiltroModelo(): void
    {
        $this->currentPage = 1; // Resetear a la primera página cuando se filtra
    }

    /**
     * Método que se ejecuta cuando cambia el filtro de marca
     */
    public function updatedFiltroMarca(): void
    {
        Log::info("[ServicioExpress] Filtro de marca actualizado a: '{$this->filtroMarca}'");
        $this->actualizarLocalesFiltrados();
        $this->currentPage = 1; // Resetear a la primera página cuando se filtra
    }

    /**
     * Método que se ejecuta cuando cambia el filtro de local
     */
    public function updatedFiltroLocal(): void
    {
        $this->currentPage = 1; // Resetear a la primera página cuando se filtra
    }
}