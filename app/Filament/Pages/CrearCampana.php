<?php

namespace App\Filament\Pages;

use App\Models\Campana;
use App\Models\CampanaImagen;
use App\Models\Local;
use App\Models\Modelo;
use App\Models\ModeloAno;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\WithFileUploads;

class CrearCampana extends Page
{
    use WithFileUploads, HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationLabel = 'Crear Campaña';
    
    protected static ?string $navigationGroup = '📢 Marketing';
    
    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Gestionar campaña';

    public function getTitle(): string
    {
        return $this->modoEdicion ? 'Editar campaña' : 'Crear campaña';
    }

    protected static string $view = 'filament.pages.crear-campana';

    // Ocultar de la navegación principal ya que se accederá desde la página de campañas
    protected static bool $shouldRegisterNavigation = false;

    // Definir los parámetros de URL que puede recibir esta página
    public static function getRouteParameters(): array
    {
        return [
            'campana_id',
        ];
    }

    // Listeners para eventos
    protected $listeners = [
        'refresh' => '$refresh',
        'imagenError' => 'handleImagenError',
    ];

    // Propiedades para los pasos
    public int $pasoActual = 1;

    public int $totalPasos = 3;

    // ID de la campaña a editar (si es una edición)
    public ?int $campana_id = null;

    public bool $modoEdicion = false;

    // Datos de la campaña - Paso 1
    public string $codigoCampana = '';

    public string $tituloCampana = '';

    public string $fechaInicio = '';

    public string $fechaFin = '';

    // Segmentación - Paso 1
    public array $modelosSeleccionados = [];

    public array $anosSeleccionados = [];

    public array $localesSeleccionados = [];

    // Horario - Paso 1
    public bool $todoElDia = true;

    public string $horaInicio = '08:00';

    public string $horaFin = '18:00';

    // Imagen y estado - Paso 1
    public $imagen = null;

    public $imagenPreview = null;

    public string $estadoCampana = 'Activo';

    // Opciones para los selectores
    public array $modelos = [];

    public array $anos = [];

    public array $locales = [];

    public function mount(): void
    {
        $this->pasoActual = 1;

        // Obtener el ID de la campaña de la solicitud (query parameter)
        $campana_id = request()->query('campana_id');

        // Convertir a entero si existe
        $this->campana_id = ! empty($campana_id) ? (int) $campana_id : null;
        $this->modoEdicion = ! empty($this->campana_id);

        // Registrar información detallada para depuración
        Log::info('[CrearCampana] Query params: '.json_encode(request()->query()));

        Log::info('[CrearCampana] Montando página con campana_id: '.($this->campana_id ?? 'null').', modoEdicion: '.($this->modoEdicion ? 'true' : 'false'));

        // Cargar los datos desde la base de datos
        $this->cargarLocales();
        $this->cargarModelos();
        $this->cargarAnos();

        // Si estamos en modo edición, cargar los datos de la campaña
        if ($this->modoEdicion) {
            $this->cargarDatosCampana();
        }
    }

    /**
     * Carga los datos de la campaña a editar
     */
    private function cargarDatosCampana(): void
    {
        try {
            if (empty($this->campana_id)) {
                Log::warning('[CrearCampana] Intentando cargar datos de campaña sin ID');
                \Filament\Notifications\Notification::make()
                    ->title('Error al cargar campaña')
                    ->body('No se especificó un ID de campaña para editar.')
                    ->danger()
                    ->send();

                $this->redirect(Campanas::getUrl());

                return;
            }

            Log::info("[CrearCampana] Cargando datos de campaña con ID: {$this->campana_id}");

            // Intentar encontrar la campaña con el ID proporcionado
            $campana = Campana::with(['imagen', 'modelos', 'anos', 'locales'])->find($this->campana_id);

            // Registrar el resultado de la búsqueda
            if ($campana) {
                Log::info('[CrearCampana] Campaña encontrada: '.json_encode([
                    'id' => $campana->id,
                    'codigo' => $campana->codigo,
                    'titulo' => $campana->titulo,
                ]));
            } else {
                Log::warning("[CrearCampana] No se encontró ninguna campaña con ID: {$this->campana_id}");
            }

            if (! $campana) {
                // Si no se encuentra la campaña, mostrar error y redirigir
                Log::warning("[CrearCampana] No se encontró la campaña con ID: {$this->campana_id}");
                \Filament\Notifications\Notification::make()
                    ->title('Campaña no encontrada')
                    ->body('No se encontró la campaña que intenta editar.')
                    ->danger()
                    ->send();

                $this->redirect(Campanas::getUrl());

                return;
            }

            // Cargar datos básicos
            $this->codigoCampana = $campana->codigo;
            $this->tituloCampana = $campana->titulo;
            $this->fechaInicio = $campana->start_date->format('d/m/Y');
            $this->fechaFin = $campana->end_date->format('d/m/Y');
            $this->todoElDia = $campana->all_day;
            $this->horaInicio = $campana->start_time ?? '08:00';
            $this->horaFin = $campana->end_time ?? '18:00';
            $this->estadoCampana = $campana->estado;

            // Cargar modelos seleccionados
            $this->modelosSeleccionados = $campana->modelos->pluck('nombre')->toArray();

            // Cargar años seleccionados directamente de la tabla pivote
            $anos = \DB::table('campaign_years')
                ->where('campaign_id', $campana->id)
                ->pluck('year')
                ->toArray();

            Log::info("[CrearCampana] Años cargados para campaña {$campana->id}: ".json_encode($anos));

            $this->anosSeleccionados = $anos;

            // Cargar locales seleccionados
            $locales = [];
            foreach ($campana->locales as $local) {
                $locales[] = $local->pivot->premise_code;
            }
            $this->localesSeleccionados = $locales;

            // Registrar los locales cargados para depuración
            Log::info('[CrearCampana] Locales cargados para edición: '.json_encode($this->localesSeleccionados));

            // Cargar imagen si existe
            if ($campana->imagen) {
                // No podemos cargar directamente el archivo, pero podemos mostrar la imagen existente
                $this->imagenPreview = $this->getImageUrl($campana->imagen);
                Log::info('[CrearCampana] Cargada imagen de campaña: '.$this->imagenPreview);
            }

            Log::info("[CrearCampana] Cargados datos de campaña para edición: {$campana->codigo}");

            // Mostrar notificación
            \Filament\Notifications\Notification::make()
                ->title('Editando campaña')
                ->body("Está editando la campaña: {$campana->titulo}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('[CrearCampana] Error al cargar datos de campaña: '.$e->getMessage()."\nTrace: ".$e->getTraceAsString());

            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al cargar los datos de la campaña: '.$e->getMessage())
                ->danger()
                ->send();

            $this->redirect(Campanas::getUrl());
        }
    }

    /**
     * Carga los locales activos desde la base de datos
     */
    private function cargarLocales(): void
    {
        try {
            // Obtener los locales activos
            $localesActivos = Local::where('is_active', true)
                ->orderBy('name')
                ->get();

            // Convertir a array de códigos
            $this->locales = $localesActivos->pluck('code')->toArray();
        } catch (\Exception $e) {
            Log::error('[CrearCampana] Error al cargar locales: '.$e->getMessage());
            $this->locales = [];
        }
    }

    /**
     * Carga los modelos activos desde la base de datos
     */
    private function cargarModelos(): void
    {
        try {
            // Obtener los modelos activos
            $modelosActivos = Modelo::where('is_active', true)
                ->orderBy('name')
                ->get();

            // Convertir a array de nombres
            $this->modelos = $modelosActivos->pluck('name')->toArray();
        } catch (\Exception $e) {
            Log::error('[CrearCampana] Error al cargar modelos: '.$e->getMessage());
            $this->modelos = [];
        }
    }

    /**
     * Carga los años activos desde la base de datos
     */
    private function cargarAnos(): void
    {
        try {
            // Obtener los años activos
            $anosActivos = ModeloAno::where('activo', true)
                ->orderBy('year', 'desc')
                ->get();

            // Convertir a array de años únicos
            $this->anos = $anosActivos->pluck('year')->unique()->toArray();
        } catch (\Exception $e) {
            Log::error('[CrearCampana] Error al cargar años: '.$e->getMessage());
            $this->anos = [];
        }
    }

    public function siguientePaso(): void
    {
        // Registrar los valores seleccionados para depuración
        Log::info('[CrearCampana] Valores seleccionados: '.json_encode([
            'localesSeleccionados' => $this->localesSeleccionados,
            'modelosSeleccionados' => $this->modelosSeleccionados,
            'anosSeleccionados' => $this->anosSeleccionados,
        ]));

        // Validar datos según el paso actual
        if ($this->pasoActual === 1) {
            $this->validate([
                'codigoCampana' => 'required|min:3',
                'tituloCampana' => 'required|min:3',
                'fechaInicio' => 'required|date_format:d/m/Y',
                'fechaFin' => 'required|date_format:d/m/Y|after_or_equal:fechaInicio',
                'localesSeleccionados' => 'required|array|min:1',
            ], [
                'codigoCampana.required' => 'El código de campaña es obligatorio',
                'tituloCampana.required' => 'El título de campaña es obligatorio',
                'fechaInicio.required' => 'La fecha de inicio es obligatoria',
                'fechaFin.required' => 'La fecha de fin es obligatoria',
                'fechaFin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio',
                'localesSeleccionados.required' => 'Debe seleccionar al menos un local',
                'localesSeleccionados.min' => 'Debe seleccionar al menos un local',
            ]);
        }

        if ($this->pasoActual < $this->totalPasos) {
            $this->pasoActual++;
        }
    }

    public function anteriorPaso(): void
    {
        if ($this->pasoActual > 1) {
            $this->pasoActual--;
        }
    }

    public function finalizarCreacion(): void
    {
        try {
            // Convertir fechas al formato correcto para la base de datos
            $fechaInicio = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fechaInicio)->format('Y-m-d');
            $fechaFin = \Carbon\Carbon::createFromFormat('d/m/Y', $this->fechaFin)->format('Y-m-d');

            // Datos comunes para crear o actualizar
            $datosCampana = [
                'codigo' => $this->codigoCampana,
                'titulo' => $this->tituloCampana,
                'start_date' => $fechaInicio,
                'end_date' => $fechaFin,
                'start_time' => $this->todoElDia ? null : $this->horaInicio,
                'end_time' => $this->todoElDia ? null : $this->horaFin,
                'all_day' => $this->todoElDia,
                'estado' => $this->estadoCampana,
            ];

            if ($this->modoEdicion) {
                // Actualizar la campaña existente
                $campana = Campana::find($this->campana_id);

                if (! $campana) {
                    throw new \Exception("No se encontró la campaña con ID: {$this->campana_id}");
                }

                Log::info("[CrearCampana] Actualizando campaña con ID: {$this->campana_id}, Código: {$this->codigoCampana}");

                // Actualizar los datos de la campaña
                $campana->update($datosCampana);

                // Eliminar relaciones existentes para volver a crearlas
                $campana->modelos()->detach();
                $campana->anos()->detach();
                $campana->locales()->detach();

                Log::info("[CrearCampana] Campaña actualizada: {$this->codigoCampana}");
            } else {
                // Crear una nueva campaña
                $campana = Campana::create($datosCampana);
                Log::info("[CrearCampana] Campaña creada: {$this->codigoCampana}");
            }

            // Guardar los modelos seleccionados
            if (! empty($this->modelosSeleccionados)) {
                foreach ($this->modelosSeleccionados as $modeloNombre) {
                    $modelo = Modelo::where('name', $modeloNombre)->first();
                    if ($modelo) {
                        $campana->modelos()->attach($modelo->id);
                    }
                }
            }

            // Guardar los años seleccionados
            if (! empty($this->anosSeleccionados)) {
                Log::info('[CrearCampana] Guardando años seleccionados: '.json_encode($this->anosSeleccionados));

                // Limpiar la tabla pivote para esta campaña
                \DB::table('campaign_years')->where('campaign_id', $campana->id)->delete();

                // Insertar directamente en la tabla pivote
                $now = now();
                $data = [];

                foreach ($this->anosSeleccionados as $ano) {
                    Log::info("[CrearCampana] Guardando año: {$ano}");

                    $data[] = [
                        'campaign_id' => $campana->id,
                        'year' => $ano,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Insertar todos los registros de una vez
                \DB::table('campaign_years')->insert($data);

                // Verificar que se guardaron correctamente
                $anosGuardados = \DB::table('campaign_years')
                    ->where('campaign_id', $campana->id)
                    ->pluck('year')
                    ->toArray();

                Log::info('[CrearCampana] Años guardados: '.json_encode($anosGuardados));
            } else {
                Log::warning('[CrearCampana] No hay años seleccionados para guardar');
            }

            // Guardar los locales seleccionados
            if (! empty($this->localesSeleccionados)) {
                Log::info('[CrearCampana] Guardando locales seleccionados: '.json_encode($this->localesSeleccionados));

                // Limpiar la tabla pivote para esta campaña
                \DB::table('campaign_premises')->where('campaign_id', $campana->id)->delete();

                // Insertar directamente en la tabla pivote
                $now = now();
                $data = [];

                foreach ($this->localesSeleccionados as $localCodigo) {
                    Log::info("[CrearCampana] Guardando local: {$localCodigo}");

                    $data[] = [
                        'campaign_id' => $campana->id,
                        'premise_code' => $localCodigo,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Insertar todos los registros de una vez
                \DB::table('campaign_premises')->insert($data);

                // Verificar que se guardaron correctamente
                $localesGuardados = \DB::table('campaign_premises')
                    ->where('campaign_id', $campana->id)
                    ->pluck('premise_code')
                    ->toArray();

                Log::info('[CrearCampana] Locales guardados: '.json_encode($localesGuardados));
            } else {
                Log::warning('[CrearCampana] No hay locales seleccionados para guardar');
            }

            // Guardar la imagen si se ha seleccionado una nueva
            if ($this->imagen) {
                try {
                    Log::info("[CrearCampana] Iniciando proceso de guardado de imagen para campaña ID: {$campana->id}");

                    // Si estamos en modo edición y ya existe una imagen, eliminarla
                    if ($this->modoEdicion) {
                        $imagenExistente = CampanaImagen::where('campana_id', $campana->id)->first();
                        if ($imagenExistente) {
                            Log::info("[CrearCampana] Encontrada imagen existente para campaña ID: {$campana->id}");

                            // Intentar eliminar el archivo físico desde diferentes ubicaciones posibles
                            $rutasAVerificar = [
                                storage_path('app/'.$imagenExistente->ruta),
                                storage_path('app/private/'.$imagenExistente->ruta),
                                storage_path('app/private/public/images/campanas/'.basename($imagenExistente->ruta)),
                                public_path($imagenExistente->ruta),
                            ];

                            $eliminado = false;
                            foreach ($rutasAVerificar as $ruta) {
                                Log::info("[CrearCampana] Verificando existencia de archivo en: {$ruta}");
                                if (file_exists($ruta)) {
                                    try {
                                        unlink($ruta);
                                        Log::info("[CrearCampana] Archivo eliminado: {$ruta}");
                                        $eliminado = true;
                                        break;
                                    } catch (\Exception $e) {
                                        Log::warning("[CrearCampana] Error al eliminar archivo: {$ruta} - ".$e->getMessage());
                                    }
                                }
                            }

                            if (! $eliminado) {
                                Log::warning('[CrearCampana] No se pudo eliminar el archivo porque no existe en ninguna ubicación conocida');
                            }

                            // Eliminar el registro
                            $imagenExistente->delete();
                            Log::info("[CrearCampana] Registro de imagen eliminado para campaña ID: {$campana->id}");
                        }
                    }

                    // Generar un nombre único para la imagen
                    $nombreArchivo = Str::slug($this->codigoCampana).'-'.time().'.'.$this->imagen->getClientOriginalExtension();
                    Log::info("[CrearCampana] Nombre de archivo generado: {$nombreArchivo}");

                    // Ruta para guardar la imagen en storage/app/private/public/images/campanas
                    $rutaStorage = 'private'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'campanas';

                    // Asegurar que el directorio exista
                    $directorioCompleto = storage_path('app'.DIRECTORY_SEPARATOR.$rutaStorage);
                    Log::info("[CrearCampana] Directorio para guardar imagen: {$directorioCompleto}");

                    if (! file_exists($directorioCompleto)) {
                        Log::info("[CrearCampana] Creando directorio: {$directorioCompleto}");
                        if (! mkdir($directorioCompleto, 0755, true)) {
                            throw new \Exception("No se pudo crear el directorio para guardar la imagen: {$directorioCompleto}");
                        }
                    }

                    // Verificar permisos del directorio
                    if (! is_writable($directorioCompleto)) {
                        Log::warning("[CrearCampana] El directorio no tiene permisos de escritura: {$directorioCompleto}");
                        chmod($directorioCompleto, 0755);
                        if (! is_writable($directorioCompleto)) {
                            throw new \Exception("El directorio no tiene permisos de escritura: {$directorioCompleto}");
                        }
                    }

                    // Intentar guardar la imagen directamente en el sistema de archivos
                    try {
                        Log::info("[CrearCampana] Intentando guardar imagen directamente en: {$directorioCompleto}/{$nombreArchivo}");
                        $rutaArchivoCompleta = $directorioCompleto.DIRECTORY_SEPARATOR.$nombreArchivo;

                        // Obtener el contenido del archivo
                        $contenido = file_get_contents($this->imagen->getRealPath());

                        // Guardar el archivo
                        if (file_put_contents($rutaArchivoCompleta, $contenido) === false) {
                            throw new \Exception('No se pudo escribir el archivo directamente');
                        }

                        // Establecer la ruta relativa para la base de datos
                        $rutaArchivo = $rutaStorage.DIRECTORY_SEPARATOR.$nombreArchivo;
                        Log::info("[CrearCampana] Imagen guardada directamente en: {$rutaArchivoCompleta}");
                    } catch (\Exception $e) {
                        // Si falla, intentar con el método Storage
                        Log::warning('[CrearCampana] Error al guardar directamente: '.$e->getMessage().'. Intentando con Storage::disk');

                        // Convertir separadores para Storage
                        $rutaStorageNormalizada = str_replace(DIRECTORY_SEPARATOR, '/', $rutaStorage);
                        Log::info("[CrearCampana] Intentando guardar imagen con Storage en: {$rutaStorageNormalizada}/{$nombreArchivo}");

                        $rutaArchivo = $this->imagen->storeAs($rutaStorageNormalizada, $nombreArchivo);
                        if (! $rutaArchivo) {
                            throw new \Exception('No se pudo guardar la imagen usando Storage');
                        }
                    }

                    if (! $rutaArchivo) {
                        throw new \Exception('No se pudo guardar la imagen en el almacenamiento');
                    }

                    // La ruta que guardaremos en la base de datos
                    $rutaRelativa = $rutaArchivo;

                    // Normalizar la ruta para evitar mezcla de separadores
                    $rutaArchivo = str_replace('/', DIRECTORY_SEPARATOR, $rutaArchivo);

                    // Verificar que el archivo se haya guardado correctamente
                    $rutaCompleta = storage_path('app'.DIRECTORY_SEPARATOR.$rutaArchivo);
                    Log::info("[CrearCampana] Verificando existencia del archivo en: {$rutaCompleta}");

                    // Esperar un momento para asegurarse de que el archivo se haya escrito completamente
                    usleep(500000); // Esperar 0.5 segundos

                    if (! file_exists($rutaCompleta)) {
                        // Intentar buscar el archivo con una ruta alternativa
                        $rutaAlternativa = storage_path('app/private/public/images/campanas/'.basename($nombreArchivo));
                        Log::info("[CrearCampana] Intentando ruta alternativa: {$rutaAlternativa}");

                        if (file_exists($rutaAlternativa)) {
                            Log::info('[CrearCampana] Archivo encontrado en ruta alternativa');
                            $rutaCompleta = $rutaAlternativa;
                        } else {
                            throw new \Exception("El archivo no se guardó correctamente. Verificadas las rutas: {$rutaCompleta} y {$rutaAlternativa}");
                        }
                    }

                    // Registrar información detallada para depuración
                    Log::info('[CrearCampana] Rutas de imagen: '.json_encode([
                        'rutaStorage' => $rutaStorage,
                        'nombreArchivo' => $nombreArchivo,
                        'rutaArchivo' => $rutaArchivo,
                        'rutaCompleta' => $rutaCompleta,
                        'existe' => file_exists($rutaCompleta) ? 'Sí' : 'No',
                        'tamaño' => file_exists($rutaCompleta) ? filesize($rutaCompleta) : 'N/A',
                    ]));

                    // Registrar la imagen en la base de datos
                    $imagenDB = CampanaImagen::create([
                        'campana_id' => $campana->id,
                        'ruta' => $rutaRelativa,
                        'nombre_original' => $this->imagen->getClientOriginalName(),
                        'mime_type' => $this->imagen->getMimeType(),
                        'tamano' => $this->imagen->getSize(),
                    ]);

                    Log::info("[CrearCampana] Imagen guardada en base de datos con ID: {$imagenDB->id}");

                    // Notificar éxito
                    \Filament\Notifications\Notification::make()
                        ->title('Éxito')
                        ->body('La imagen se ha guardado correctamente.')
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    Log::error('[CrearCampana] Error al guardar imagen: '.$e->getMessage());
                    Log::error('[CrearCampana] Stack trace: '.$e->getTraceAsString());

                    // Notificar al usuario pero continuar con la creación/actualización de la campaña
                    \Filament\Notifications\Notification::make()
                        ->title('Advertencia')
                        ->body('La campaña se ha '.($this->modoEdicion ? 'actualizado' : 'creado').', pero hubo un problema al guardar la imagen: '.$e->getMessage())
                        ->warning()
                        ->send();
                }
            }

            // Mostrar notificación de éxito
            \Filament\Notifications\Notification::make()
                ->title($this->modoEdicion ? 'Campaña actualizada' : 'Campaña creada')
                ->body('La campaña se ha '.($this->modoEdicion ? 'actualizado' : 'creado').' correctamente')
                ->success()
                ->send();

            // Registrar en el log
            Log::info('[CrearCampana] Campaña '.($this->modoEdicion ? 'actualizada' : 'creada')." con éxito: {$this->codigoCampana}");

            // Ir al paso final
            $this->pasoActual = 3;
        } catch (\Exception $e) {
            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al '.($this->modoEdicion ? 'actualizar' : 'crear').' la campaña')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();

            // Registrar en el log con stack trace para mejor depuración
            Log::error('[CrearCampana] Error al '.($this->modoEdicion ? 'actualizar' : 'crear').' campaña: '.$e->getMessage()."\nTrace: ".$e->getTraceAsString());
        }
    }

    public function volverACampanas()
    {
        $this->redirect(Campanas::getUrl());
    }

    /**
     * Maneja los errores de carga de imágenes reportados desde JavaScript
     */
    public function handleImagenError($data)
    {
        Log::warning('[CrearCampana] Error de imagen reportado desde JavaScript: '.json_encode($data));

        // Intentar recuperar la imagen si es posible
        if ($this->imagen) {
            try {
                // Intentar un enfoque alternativo para la vista previa
                $tempPath = $this->imagen->store('temp/previews');
                $this->imagenPreview = Storage::url($tempPath);
                Log::info("[CrearCampana] URL alternativa generada después de error: {$this->imagenPreview}");

                // Notificar al usuario
                \Filament\Notifications\Notification::make()
                    ->title('Recuperación de imagen')
                    ->body('Se ha intentado recuperar la vista previa de la imagen.')
                    ->success()
                    ->send();
            } catch (\Exception $e) {
                Log::error('[CrearCampana] Error al intentar recuperar imagen: '.$e->getMessage());

                // Si no se puede recuperar, notificar al usuario
                \Filament\Notifications\Notification::make()
                    ->title('Error con la imagen')
                    ->body('No se pudo mostrar la vista previa, pero la imagen se guardará correctamente al confirmar.')
                    ->warning()
                    ->send();
            }
        }
    }

    /**
     * Genera la URL correcta para una imagen de campaña
     */
    private function getImageUrl($imagen): string
    {
        // Registrar la ruta original para depuración
        Log::info('[CrearCampana] Ruta de imagen original: '.$imagen->ruta);

        // Normalizar la ruta para evitar problemas con separadores
        $rutaNormalizada = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $imagen->ruta);
        Log::info('[CrearCampana] Ruta normalizada: '.$rutaNormalizada);

        // Usar la ruta de imagen.campana para generar la URL
        $url = route('imagen.campana', ['id' => $imagen->campaign_id]);

        // Registrar la URL generada para depuración
        Log::info('[CrearCampana] URL generada para imagen: '.$url);

        // Verificar si el archivo existe en diferentes ubicaciones
        $rutasAVerificar = [
            storage_path('app'.DIRECTORY_SEPARATOR.$rutaNormalizada),
            storage_path('app'.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'campanas'.DIRECTORY_SEPARATOR.basename($rutaNormalizada)),
            storage_path('app'.DIRECTORY_SEPARATOR.'private'.DIRECTORY_SEPARATOR.$rutaNormalizada),
            public_path($rutaNormalizada),
        ];

        $existe = false;
        $rutaEncontrada = '';

        foreach ($rutasAVerificar as $ruta) {
            Log::info('[CrearCampana] Verificando existencia de archivo en: '.$ruta);
            if (file_exists($ruta)) {
                $existe = true;
                $rutaEncontrada = $ruta;
                Log::info('[CrearCampana] Archivo encontrado en: '.$ruta);
                break;
            }
        }

        if (! $existe) {
            Log::warning('[CrearCampana] No se encontró el archivo de imagen en ninguna ubicación conocida');
        }

        Log::info('[CrearCampana] Verificación de archivo: '.json_encode([
            'rutasVerificadas' => $rutasAVerificar,
            'rutaEncontrada' => $rutaEncontrada,
            'existe' => $existe ? 'Sí' : 'No',
        ]));

        // Devolver la URL
        return $url;
    }

    /**
     * Actualiza la vista previa de la imagen cuando se selecciona una
     */
    public function updatedImagen(): void
    {
        try {
            if ($this->imagen) {
                // Registrar información detallada sobre el archivo
                Log::info('[CrearCampana] Información del archivo subido: '.json_encode([
                    'nombre' => $this->imagen->getClientOriginalName(),
                    'tamaño' => $this->imagen->getSize(),
                    'tipo' => $this->imagen->getMimeType(),
                    'extensión' => $this->imagen->getClientOriginalExtension(),
                ]));

                // Validar que sea una imagen
                $this->validate([
                    'imagen' => 'image|max:10240', // Máximo 10MB
                ], [
                    'imagen.image' => 'El archivo debe ser una imagen',
                    'imagen.max' => 'La imagen no debe superar los 10MB',
                ]);

                try {
                    // Generar URL temporal para la vista previa
                    $this->imagenPreview = $this->imagen->temporaryUrl();
                    Log::info('[CrearCampana] URL temporal generada correctamente');
                } catch (\Exception $e) {
                    Log::error('[CrearCampana] Error al generar URL temporal: '.$e->getMessage());

                    // Intentar un enfoque alternativo para la vista previa
                    try {
                        // Guardar temporalmente la imagen para mostrarla
                        $tempPath = $this->imagen->store('temp/previews');
                        $this->imagenPreview = Storage::url($tempPath);
                        Log::info("[CrearCampana] URL alternativa generada: {$this->imagenPreview}");
                    } catch (\Exception $e2) {
                        Log::error('[CrearCampana] Error al generar URL alternativa: '.$e2->getMessage());
                        throw $e; // Lanzar el error original
                    }
                }

                Log::info("[CrearCampana] Imagen cargada exitosamente: {$this->imagen->getClientOriginalName()}");

                // Notificar al usuario que la imagen se ha cargado correctamente
                \Filament\Notifications\Notification::make()
                    ->title('Imagen cargada')
                    ->body('La imagen se ha cargado correctamente y está lista para ser guardada.')
                    ->success()
                    ->send();
            } else {
                $this->imagenPreview = null;
                Log::info('[CrearCampana] Se eliminó la imagen seleccionada');
            }
        } catch (\Exception $e) {
            $this->imagen = null;
            $this->imagenPreview = null;

            Log::error('[CrearCampana] Error al cargar imagen: '.$e->getMessage());
            Log::error('[CrearCampana] Stack trace: '.$e->getTraceAsString());

            \Filament\Notifications\Notification::make()
                ->title('Error al cargar imagen')
                ->body('Ha ocurrido un error: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }
}
