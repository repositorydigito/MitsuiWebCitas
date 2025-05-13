<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Local;
use App\Models\Modelo;
use App\Models\ModeloAno;
use App\Models\Campana;
use App\Models\CampanaImagen;
use Livewire\WithFileUploads;

class CrearCampana extends Page
{
    use WithFileUploads;
    protected static ?string $navigationIcon = 'heroicon-o-document-plus';

    protected static ?string $navigationLabel = 'Crear Campaña';

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
        $this->campana_id = !empty($campana_id) ? (int)$campana_id : null;
        $this->modoEdicion = !empty($this->campana_id);

        // Registrar información detallada para depuración
        Log::info("[CrearCampana] Query params: " . json_encode(request()->query()));

        Log::info("[CrearCampana] Montando página con campana_id: " . ($this->campana_id ?? 'null') . ", modoEdicion: " . ($this->modoEdicion ? 'true' : 'false'));

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
                Log::warning("[CrearCampana] Intentando cargar datos de campaña sin ID");
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
                Log::info("[CrearCampana] Campaña encontrada: " . json_encode([
                    'id' => $campana->id,
                    'codigo' => $campana->codigo,
                    'titulo' => $campana->titulo
                ]));
            } else {
                Log::warning("[CrearCampana] No se encontró ninguna campaña con ID: {$this->campana_id}");
            }

            if (!$campana) {
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
            $this->fechaInicio = $campana->fecha_inicio->format('d/m/Y');
            $this->fechaFin = $campana->fecha_fin->format('d/m/Y');
            $this->todoElDia = $campana->todo_dia;
            $this->horaInicio = $campana->hora_inicio ?? '08:00';
            $this->horaFin = $campana->hora_fin ?? '18:00';
            $this->estadoCampana = $campana->estado;

            // Cargar modelos seleccionados
            $this->modelosSeleccionados = $campana->modelos->pluck('nombre')->toArray();

            // Cargar años seleccionados
            $this->anosSeleccionados = $campana->anos->pluck('ano')->toArray();

            // Cargar locales seleccionados
            $locales = [];
            foreach ($campana->locales as $local) {
                $locales[] = $local->pivot->local_codigo;
            }
            $this->localesSeleccionados = $locales;

            // Registrar los locales cargados para depuración
            Log::info("[CrearCampana] Locales cargados para edición: " . json_encode($this->localesSeleccionados));

            // Cargar imagen si existe
            if ($campana->imagen) {
                // No podemos cargar directamente el archivo, pero podemos mostrar la imagen existente
                $this->imagenPreview = $this->getImageUrl($campana->imagen);
                Log::info("[CrearCampana] Cargada imagen de campaña: " . $this->imagenPreview);
            }

            Log::info("[CrearCampana] Cargados datos de campaña para edición: {$campana->codigo}");

            // Mostrar notificación
            \Filament\Notifications\Notification::make()
                ->title('Editando campaña')
                ->body("Está editando la campaña: {$campana->titulo}")
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error("[CrearCampana] Error al cargar datos de campaña: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());

            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ha ocurrido un error al cargar los datos de la campaña: ' . $e->getMessage())
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
            $localesActivos = Local::where('activo', true)
                ->orderBy('nombre')
                ->get();

            // Convertir a array de códigos
            $this->locales = $localesActivos->pluck('codigo')->toArray();
        } catch (\Exception $e) {
            Log::error("[CrearCampana] Error al cargar locales: " . $e->getMessage());
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
            $modelosActivos = Modelo::where('activo', true)
                ->orderBy('nombre')
                ->get();

            // Convertir a array de nombres
            $this->modelos = $modelosActivos->pluck('nombre')->toArray();
        } catch (\Exception $e) {
            Log::error("[CrearCampana] Error al cargar modelos: " . $e->getMessage());
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
                ->orderBy('ano', 'desc')
                ->get();

            // Convertir a array de años únicos
            $this->anos = $anosActivos->pluck('ano')->unique()->toArray();
        } catch (\Exception $e) {
            Log::error("[CrearCampana] Error al cargar años: " . $e->getMessage());
            $this->anos = [];
        }
    }

    public function siguientePaso(): void
    {
        // Registrar los valores seleccionados para depuración
        Log::info("[CrearCampana] Valores seleccionados: " . json_encode([
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
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'hora_inicio' => $this->todoElDia ? null : $this->horaInicio,
                'hora_fin' => $this->todoElDia ? null : $this->horaFin,
                'todo_dia' => $this->todoElDia,
                'estado' => $this->estadoCampana,
            ];

            if ($this->modoEdicion) {
                // Actualizar la campaña existente
                $campana = Campana::find($this->campana_id);

                if (!$campana) {
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
            if (!empty($this->modelosSeleccionados)) {
                foreach ($this->modelosSeleccionados as $modeloNombre) {
                    $modelo = Modelo::where('nombre', $modeloNombre)->first();
                    if ($modelo) {
                        $campana->modelos()->attach($modelo->id);
                    }
                }
            }

            // Guardar los años seleccionados
            if (!empty($this->anosSeleccionados)) {
                foreach ($this->anosSeleccionados as $ano) {
                    $campana->anos()->attach(null, ['ano' => $ano]);
                }
            }

            // Guardar los locales seleccionados
            if (!empty($this->localesSeleccionados)) {
                Log::info("[CrearCampana] Guardando locales seleccionados: " . json_encode($this->localesSeleccionados));

                // Limpiar la tabla pivote para esta campaña
                \DB::table('campana_locales')->where('campana_id', $campana->id)->delete();

                // Insertar directamente en la tabla pivote
                $now = now();
                $data = [];

                foreach ($this->localesSeleccionados as $localCodigo) {
                    Log::info("[CrearCampana] Guardando local: {$localCodigo}");

                    $data[] = [
                        'campana_id' => $campana->id,
                        'local_codigo' => $localCodigo,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                // Insertar todos los registros de una vez
                \DB::table('campana_locales')->insert($data);

                // Verificar que se guardaron correctamente
                $localesGuardados = \DB::table('campana_locales')
                    ->where('campana_id', $campana->id)
                    ->pluck('local_codigo')
                    ->toArray();

                Log::info("[CrearCampana] Locales guardados: " . json_encode($localesGuardados));
            } else {
                Log::warning("[CrearCampana] No hay locales seleccionados para guardar");
            }

            // Guardar la imagen si se ha seleccionado una nueva
            if ($this->imagen) {
                try {
                    // Si estamos en modo edición y ya existe una imagen, eliminarla
                    if ($this->modoEdicion) {
                        $imagenExistente = CampanaImagen::where('campana_id', $campana->id)->first();
                        if ($imagenExistente) {
                            // Intentar eliminar el archivo físico desde diferentes ubicaciones posibles
                            $rutasAVerificar = [
                                storage_path('app/' . $imagenExistente->ruta),
                                storage_path('app/private/' . $imagenExistente->ruta),
                                storage_path('app/private/public/images/campanas/' . basename($imagenExistente->ruta)),
                                public_path($imagenExistente->ruta)
                            ];

                            $eliminado = false;
                            foreach ($rutasAVerificar as $ruta) {
                                if (file_exists($ruta)) {
                                    unlink($ruta);
                                    Log::info("[CrearCampana] Archivo eliminado: {$ruta}");
                                    $eliminado = true;
                                    break;
                                }
                            }

                            if (!$eliminado) {
                                Log::warning("[CrearCampana] No se pudo eliminar el archivo porque no existe en ninguna ubicación conocida");
                            }

                            // Eliminar el registro
                            $imagenExistente->delete();
                            Log::info("[CrearCampana] Registro de imagen eliminado para campaña ID: {$campana->id}");
                        }
                    }

                    // Generar un nombre único para la imagen
                    $nombreArchivo = Str::slug($this->codigoCampana) . '-' . time() . '.' . $this->imagen->getClientOriginalExtension();

                    // Ruta para guardar la imagen en storage/app/private/public/images/campanas
                    $rutaStorage = 'private/public/images/campanas';

                    // Guardar la imagen usando Storage
                    $rutaArchivo = $this->imagen->storeAs($rutaStorage, $nombreArchivo);

                    if (!$rutaArchivo) {
                        throw new \Exception("No se pudo guardar la imagen en el almacenamiento");
                    }

                    // La ruta que guardaremos en la base de datos
                    $rutaRelativa = $rutaArchivo;

                    // Registrar información detallada para depuración
                    Log::info("[CrearCampana] Rutas de imagen: " . json_encode([
                        'rutaStorage' => $rutaStorage,
                        'nombreArchivo' => $nombreArchivo,
                        'rutaArchivo' => $rutaArchivo,
                        'rutaCompleta' => storage_path('app/' . $rutaArchivo)
                    ]));

                    // Registrar la imagen en la base de datos
                    CampanaImagen::create([
                        'campana_id' => $campana->id,
                        'ruta' => $rutaRelativa,
                        'nombre_original' => $this->imagen->getClientOriginalName(),
                        'mime_type' => $this->imagen->getMimeType(),
                        'tamano' => $this->imagen->getSize(),
                    ]);

                    Log::info("[CrearCampana] Imagen guardada: {$rutaRelativa}");

                    // Notificar éxito
                    \Filament\Notifications\Notification::make()
                        ->title('Éxito')
                        ->body('La imagen se ha guardado correctamente.')
                        ->success()
                        ->send();

                } catch (\Exception $e) {
                    Log::error("[CrearCampana] Error al guardar imagen: " . $e->getMessage());
                    Log::error("[CrearCampana] Stack trace: " . $e->getTraceAsString());

                    // Notificar al usuario pero continuar con la creación/actualización de la campaña
                    \Filament\Notifications\Notification::make()
                        ->title('Advertencia')
                        ->body('La campaña se ha ' . ($this->modoEdicion ? 'actualizado' : 'creado') . ', pero hubo un problema al guardar la imagen.')
                        ->warning()
                        ->send();
                }
            }

            // Mostrar notificación de éxito
            \Filament\Notifications\Notification::make()
                ->title($this->modoEdicion ? 'Campaña actualizada' : 'Campaña creada')
                ->body('La campaña se ha ' . ($this->modoEdicion ? 'actualizado' : 'creado') . ' correctamente')
                ->success()
                ->send();

            // Registrar en el log
            Log::info("[CrearCampana] Campaña " . ($this->modoEdicion ? 'actualizada' : 'creada') . " con éxito: {$this->codigoCampana}");

            // Ir al paso final
            $this->pasoActual = 3;
        } catch (\Exception $e) {
            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al ' . ($this->modoEdicion ? 'actualizar' : 'crear') . ' la campaña')
                ->body('Ha ocurrido un error: ' . $e->getMessage())
                ->danger()
                ->send();

            // Registrar en el log con stack trace para mejor depuración
            Log::error("[CrearCampana] Error al " . ($this->modoEdicion ? 'actualizar' : 'crear') . " campaña: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString());
        }
    }

    public function volverACampanas()
    {
        return $this->redirect(Campanas::getUrl());
    }

    /**
     * Genera la URL correcta para una imagen de campaña
     */
    private function getImageUrl($imagen): string
    {
        // Registrar la ruta original para depuración
        Log::info("[CrearCampana] Ruta de imagen original: " . $imagen->ruta);

        // Usar la ruta de imagen.campana para generar la URL
        $url = route('imagen.campana', ['id' => $imagen->campana_id]);

        // Registrar la URL generada para depuración
        Log::info("[CrearCampana] URL generada para imagen: " . $url);

        // Verificar si el archivo existe en diferentes ubicaciones
        $rutasAVerificar = [
            storage_path('app/' . $imagen->ruta),
            storage_path('app/private/' . $imagen->ruta),
            storage_path('app/private/public/images/campanas/' . basename($imagen->ruta)),
            public_path($imagen->ruta)
        ];

        $existe = false;
        $rutaEncontrada = '';

        foreach ($rutasAVerificar as $ruta) {
            if (file_exists($ruta)) {
                $existe = true;
                $rutaEncontrada = $ruta;
                break;
            }
        }

        Log::info("[CrearCampana] Verificación de archivo: " . json_encode([
            'rutasVerificadas' => $rutasAVerificar,
            'rutaEncontrada' => $rutaEncontrada,
            'existe' => $existe ? 'Sí' : 'No'
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
                // Validar que sea una imagen
                $this->validate([
                    'imagen' => 'image|max:2048', // Máximo 2MB
                ], [
                    'imagen.image' => 'El archivo debe ser una imagen',
                    'imagen.max' => 'La imagen no debe superar los 2MB',
                ]);

                // Generar URL temporal para la vista previa
                $this->imagenPreview = $this->imagen->temporaryUrl();

                Log::info("[CrearCampana] Imagen cargada: {$this->imagen->getClientOriginalName()}");
            } else {
                $this->imagenPreview = null;
            }
        } catch (\Exception $e) {
            $this->imagen = null;
            $this->imagenPreview = null;

            Log::error("[CrearCampana] Error al cargar imagen: " . $e->getMessage());

            \Filament\Notifications\Notification::make()
                ->title('Error al cargar imagen')
                ->body('Ha ocurrido un error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
