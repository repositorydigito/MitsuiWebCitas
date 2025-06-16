<?php

namespace App\Filament\Pages;

use App\Models\Campana;
use App\Models\CampanaImagen;
use App\Models\Local;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CargaCampanasPage extends Page
{
    use HasPageShield, WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Cargar Campañas';

    protected static ?string $title = 'Carga de Campañas';

    protected static ?string $slug = 'cargar-campanas';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = '📢 Marketing';

    protected static string $view = 'filament.pages.carga-campanas-page';

    // Ocultar de la navegación principal ya que se accederá desde la página de campañas
    protected static bool $shouldRegisterNavigation = false;

    // Propiedades para los pasos
    public int $pasoActual = 1;

    public int $totalPasos = 3;

    // Propiedades para el paso 1
    public $archivoExcel = null;

    public $imagenes = [];

    public string $nombreArchivo = 'Sin selección';

    // Propiedades para el paso 2 (resumen)
    public array $campanasProcesadas = [];

    public array $errores = [];

    // Propiedades para el paso 3 (confirmación)
    public bool $procesoCompletado = false;

    public int $campanasAgregadas = 0;

    // Lista de locales disponibles
    public array $localesDisponibles = [];

    public function mount()
    {
        $this->pasoActual = 1;
        $this->cargarLocalesDisponibles();
    }

    /**
     * Cargar los locales disponibles
     */
    private function cargarLocalesDisponibles(): void
    {
        try {
            // Obtener los locales activos desde la base de datos
            $this->localesDisponibles = Local::getActivosParaSelector();

            Log::info('[CargaCampanas] Se cargaron '.count($this->localesDisponibles).' locales disponibles');
        } catch (\Exception $e) {
            Log::error('[CargaCampanas] Error al cargar locales disponibles: '.$e->getMessage());

            // Inicializar con un array vacío
            $this->localesDisponibles = [];
        }
    }

    /**
     * Método para actualizar el nombre del archivo cuando se selecciona uno nuevo
     */
    public function updatedArchivoExcel(): void
    {
        if ($this->archivoExcel) {
            $this->nombreArchivo = $this->archivoExcel->getClientOriginalName();
            Log::info("[CargaCampanas] Archivo seleccionado: {$this->nombreArchivo}");
        } else {
            $this->nombreArchivo = 'Sin selección';
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

            // Establecer las cabeceras con los nombres requeridos
            $sheet->setCellValue('A1', 'Codigo Campaña');
            $sheet->setCellValue('B1', 'Campaña');
            $sheet->setCellValue('C1', 'Local');
            $sheet->setCellValue('D1', 'Fecha de Inicio');
            $sheet->setCellValue('E1', 'Fecha de Fin');
            $sheet->setCellValue('F1', 'Estado');

            // Dar formato a las cabeceras
            $sheet->getStyle('A1:F1')->getFont()->setBold(true);
            $sheet->getStyle('A1:F1')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID);
            $sheet->getStyle('A1:F1')->getFill()->getStartColor()->setARGB('FF3B82F6'); // Color primary
            $sheet->getStyle('A1:F1')->getFont()->getColor()->setARGB('FFFFFFFF'); // Texto blanco

            // Ajustar el ancho de las columnas
            $sheet->getColumnDimension('A')->setWidth(20);
            $sheet->getColumnDimension('B')->setWidth(40);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(15);
            $sheet->getColumnDimension('E')->setWidth(15);
            $sheet->getColumnDimension('F')->setWidth(10);

            // Agregar algunos ejemplos
            $sheet->setCellValue('A2', 'CAMP-001');
            $sheet->setCellValue('B2', 'Campaña de Verano 2023');
            $sheet->setCellValue('C2', 'La Molina');
            $sheet->setCellValue('D2', '2023-12-01');
            $sheet->setCellValue('E2', '2024-02-28');
            $sheet->setCellValue('F2', 'Activo');

            $sheet->setCellValue('A3', 'CAMP-002');
            $sheet->setCellValue('B3', 'Promoción Toyota Corolla');
            $sheet->setCellValue('C3', 'Miraflores');
            $sheet->setCellValue('D3', '2023-11-15');
            $sheet->setCellValue('E3', '2024-01-15');
            $sheet->setCellValue('F3', 'Activo');

            // Crear el directorio si no existe
            if (! Storage::disk('public')->exists('plantillas')) {
                Storage::disk('public')->makeDirectory('plantillas');
            }

            // Guardar el archivo
            $writer = new Xlsx($spreadsheet);
            $path = storage_path('app/public/plantillas/plantilla_campanas.xlsx');
            $writer->save($path);

            // Generar la URL para descargar el archivo
            $url = asset('storage/plantillas/plantilla_campanas.xlsx');

            // Redirigir al usuario a la URL de descarga
            redirect()->away($url);

            Log::info('[CargaCampanas] Se generó la plantilla Excel para descargar');
        } catch (\Exception $e) {
            Log::error('[CargaCampanas] Error al generar plantilla Excel: '.$e->getMessage());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al generar plantilla')
                ->body('Ha ocurrido un error al generar la plantilla Excel: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Método para procesar el archivo Excel y las imágenes
     */
    public function procesarArchivo(): void
    {
        Log::info('[CargaCampanas] Iniciando procesamiento de archivo Excel y imágenes');

        // Validar que se haya seleccionado un archivo Excel
        if (! $this->archivoExcel) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Debes seleccionar un archivo Excel')
                ->danger()
                ->send();

            return;
        }

        // Validar que se hayan seleccionado imágenes
        if (count($this->imagenes) === 0) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Debes seleccionar al menos una imagen')
                ->danger()
                ->send();

            return;
        }

        try {
            // Verificar que el archivo existe y es accesible
            $realPath = $this->archivoExcel->getRealPath();
            if (! $realPath || ! file_exists($realPath)) {
                throw new \Exception('No se puede acceder al archivo. Ruta: '.($realPath ?: 'No disponible'));
            }

            Log::info("[CargaCampanas] Ruta del archivo: {$realPath}");

            // Verificar el tipo de archivo
            $mimeType = $this->archivoExcel->getMimeType();
            Log::info("[CargaCampanas] Tipo MIME del archivo: {$mimeType}");

            // Procesar el archivo Excel
            $spreadsheet = IOFactory::load($realPath);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            Log::info('[CargaCampanas] Archivo cargado correctamente. Filas encontradas: '.count($rows));

            // Depuración: Mostrar las primeras 5 filas del archivo (o menos si hay menos filas)
            $numFilasAMostrar = min(5, count($rows));
            for ($i = 0; $i < $numFilasAMostrar; $i++) {
                Log::info("[CargaCampanas] Fila {$i}: ".json_encode($rows[$i]));
            }

            // Verificar que el archivo tenga el formato correcto
            if (count($rows) < 2) {
                throw new \Exception('El archivo no contiene datos');
            }

            // Verificar que las cabeceras sean correctas
            $cabeceras = $rows[0];
            $cabecerasEsperadas = ['Codigo Campaña', 'Campaña', 'Local', 'Fecha de Inicio', 'Fecha de Fin', 'Estado'];

            // Normalizar las cabeceras (eliminar espacios, convertir a minúsculas)
            $cabecerasNormalizadas = array_map(function ($cabecera) {
                return trim(strtolower($cabecera));
            }, $cabeceras);

            $cabecerasEsperadasNormalizadas = array_map(function ($cabecera) {
                return trim(strtolower($cabecera));
            }, $cabecerasEsperadas);

            Log::info('[CargaCampanas] Cabeceras encontradas: '.implode(', ', $cabeceras));
            Log::info('[CargaCampanas] Cabeceras normalizadas: '.implode(', ', $cabecerasNormalizadas));

            // Verificar si hay cabeceras faltantes
            $cabecerasFaltantes = array_diff($cabecerasEsperadasNormalizadas, $cabecerasNormalizadas);
            if (count($cabecerasFaltantes) > 0) {
                throw new \Exception('El formato del archivo no es correcto. Faltan las siguientes cabeceras: '.implode(', ', $cabecerasFaltantes));
            }

            // Procesar los datos
            $this->campanasProcesadas = [];
            $this->errores = [];

            // Obtener los índices de las columnas
            $indiceCodigoCampana = array_search('codigo campaña', $cabecerasNormalizadas);
            $indiceCampana = array_search('campaña', $cabecerasNormalizadas);
            $indiceLocal = array_search('local', $cabecerasNormalizadas);
            $indiceFechaInicio = array_search('fecha de inicio', $cabecerasNormalizadas);
            $indiceFechaFin = array_search('fecha de fin', $cabecerasNormalizadas);
            $indiceEstado = array_search('estado', $cabecerasNormalizadas);

            // Procesar cada fila (excepto la cabecera)
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];

                // Verificar que la fila tenga datos
                if (empty($row[$indiceCodigoCampana]) && empty($row[$indiceCampana])) {
                    continue;
                }

                // Crear un array con los datos de la campaña
                $campana = [
                    'codigo' => $row[$indiceCodigoCampana] ?? '',
                    'nombre' => $row[$indiceCampana] ?? '',
                    'fecha_inicio' => $row[$indiceFechaInicio] ?? '',
                    'fecha_fin' => $row[$indiceFechaFin] ?? '',
                    'local' => $row[$indiceLocal] ?? '',
                    'estado' => strtolower(trim($row[$indiceEstado] ?? '')) === 'activo' ? 1 : 0,
                    'imagen' => isset($this->imagenes[$i - 1]) ? $i - 1 : null,
                ];

                // Validar los datos
                $erroresFila = [];

                if (empty($campana['codigo'])) {
                    $erroresFila[] = 'El código de campaña es obligatorio';
                }

                if (empty($campana['nombre'])) {
                    $erroresFila[] = 'El nombre de la campaña es obligatorio';
                }

                if (empty($campana['fecha_inicio'])) {
                    $erroresFila[] = 'La fecha de inicio es obligatoria';
                } elseif (! strtotime($campana['fecha_inicio'])) {
                    $erroresFila[] = 'La fecha de inicio no tiene un formato válido';
                }

                if (empty($campana['fecha_fin'])) {
                    $erroresFila[] = 'La fecha de fin es obligatoria';
                } elseif (! strtotime($campana['fecha_fin'])) {
                    $erroresFila[] = 'La fecha de fin no tiene un formato válido';
                }

                if (! empty($campana['fecha_inicio']) && ! empty($campana['fecha_fin']) &&
                    strtotime($campana['fecha_inicio']) > strtotime($campana['fecha_fin'])) {
                    $erroresFila[] = 'La fecha de inicio no puede ser posterior a la fecha de fin';
                }

                if (empty($campana['local'])) {
                    $erroresFila[] = 'El local es obligatorio';
                } elseif (! in_array($campana['local'], array_values($this->localesDisponibles))) {
                    $erroresFila[] = 'El local no es válido';
                }

                if ($campana['imagen'] === null) {
                    $erroresFila[] = 'No hay una imagen asociada a esta campaña';
                }

                // Agregar la campaña y sus errores a los arrays correspondientes
                $this->campanasProcesadas[] = $campana;
                $this->errores[$i] = $erroresFila;
            }

            // Verificar si hay errores
            $hayErrores = false;
            foreach ($this->errores as $erroresFila) {
                if (count($erroresFila) > 0) {
                    $hayErrores = true;
                    break;
                }
            }

            if ($hayErrores) {
                \Filament\Notifications\Notification::make()
                    ->title('Advertencia')
                    ->body('Hay errores en los datos. Por favor, revisa el resumen.')
                    ->warning()
                    ->send();
            } else {
                \Filament\Notifications\Notification::make()
                    ->title('Éxito')
                    ->body('Los datos se han procesado correctamente. Revisa el resumen y confirma para guardar.')
                    ->success()
                    ->send();
            }

            // Avanzar al siguiente paso
            $this->pasoActual = 2;

            Log::info('[CargaCampanas] Se procesaron '.count($this->campanasProcesadas).' campañas');
        } catch (\Exception $e) {
            Log::error('[CargaCampanas] Error al procesar archivo: '.$e->getMessage());
            Log::error('[CargaCampanas] Traza de la excepción: '.$e->getTraceAsString());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al procesar el archivo: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Método para guardar las campañas en la base de datos
     */
    public function guardarCampanas(): void
    {
        Log::info('[CargaCampanas] Iniciando guardado de campañas en la base de datos');

        try {
            // Iniciar una transacción para asegurar que todas las operaciones se completen o ninguna
            DB::beginTransaction();

            $this->campanasAgregadas = 0;

            // Procesar cada campaña
            foreach ($this->campanasProcesadas as $index => $campana) {
                // Verificar si hay errores para esta campaña
                if (isset($this->errores[$index + 1]) && count($this->errores[$index + 1]) > 0) {
                    Log::warning('[CargaCampanas] Omitiendo campaña con errores: '.$campana['nombre']);

                    continue;
                }

                // Crear la campaña en la base de datos
                $nuevaCampana = new Campana;
                $nuevaCampana->code = $campana['codigo']; // Usar el código proporcionado en el Excel
                $nuevaCampana->title = $campana['nombre'];
                $nuevaCampana->start_date = date('Y-m-d', strtotime($campana['fecha_inicio']));
                $nuevaCampana->end_date = date('Y-m-d', strtotime($campana['fecha_fin']));
                $nuevaCampana->status = $campana['estado'] ? 'Activo' : 'Inactivo';
                $nuevaCampana->all_day = true;
                $nuevaCampana->save();

                // Guardar la relación con el local
                $localCodigo = array_search($campana['local'], $this->localesDisponibles);
                if ($localCodigo) {
                    DB::table('campaign_premises')->insert([
                        'campaign_id' => $nuevaCampana->id,
                        'premise_code' => $localCodigo,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                // Procesar la imagen si existe
                if ($campana['imagen'] !== null && isset($this->imagenes[$campana['imagen']])) {
                    $imagen = $this->imagenes[$campana['imagen']];

                    // Generar un nombre único para la imagen
                    $nombreImagen = 'campana_'.$nuevaCampana->id.'_'.time().'.'.$imagen->getClientOriginalExtension();

                    // Guardar la imagen en el almacenamiento usando la ruta estándar
                    $rutaImagen = $imagen->storeAs('images/campanas', $nombreImagen, 'public');

                    // Crear el registro de la imagen en la base de datos
                    $campanaImagen = new CampanaImagen;
                    $campanaImagen->campaign_id = $nuevaCampana->id;
                    $campanaImagen->route = $rutaImagen;
                    $campanaImagen->original_name = $imagen->getClientOriginalName();
                    $campanaImagen->mime_type = $imagen->getMimeType();
                    $campanaImagen->size = $imagen->getSize();
                    $campanaImagen->save();

                    Log::info("[CargaCampanas] Imagen guardada para la campaña {$nuevaCampana->id}: {$rutaImagen}");
                }

                $this->campanasAgregadas++;
                Log::info("[CargaCampanas] Campaña guardada: {$nuevaCampana->id} - {$nuevaCampana->titulo}");
            }

            // Confirmar la transacción
            DB::commit();

            // Avanzar al siguiente paso
            $this->pasoActual = 3;
            $this->procesoCompletado = true;

            Log::info("[CargaCampanas] Se guardaron {$this->campanasAgregadas} campañas en la base de datos");

            // Mostrar notificación de éxito
            \Filament\Notifications\Notification::make()
                ->title('Éxito')
                ->body("Se han guardado {$this->campanasAgregadas} campañas correctamente")
                ->success()
                ->send();
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            DB::rollBack();

            Log::error('[CargaCampanas] Error al guardar campañas: '.$e->getMessage());
            Log::error('[CargaCampanas] Traza de la excepción: '.$e->getTraceAsString());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al guardar las campañas: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Método para volver al paso anterior
     */
    public function volverPasoAnterior(): void
    {
        if ($this->pasoActual > 1) {
            $this->pasoActual--;
        }
    }

    /**
     * Método para volver a la página de campañas
     */
    public function volverACampanas()
    {
        $this->redirect(Campanas::getUrl());
    }

    /**
     * Método para eliminar una imagen del array de imágenes
     */
    public function removeImage(int $index): void
    {
        // Verificar que el índice existe
        if (isset($this->imagenes[$index])) {
            // Crear un array temporal sin la imagen a eliminar
            $imagenes = $this->imagenes;
            unset($imagenes[$index]);

            // Reindexar el array para evitar índices faltantes
            $this->imagenes = array_values($imagenes);

            Log::info("[CargaCampanas] Imagen eliminada en el índice {$index}");

            // Mostrar notificación
            \Filament\Notifications\Notification::make()
                ->title('Imagen eliminada')
                ->body('La imagen se ha eliminado correctamente')
                ->success()
                ->send();
        } else {
            Log::warning("[CargaCampanas] Intento de eliminar imagen inexistente en el índice {$index}");
        }
    }
}
