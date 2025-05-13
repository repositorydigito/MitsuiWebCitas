<?php

namespace App\Filament\Pages;

use App\Models\AdditionalService;
use App\Models\Appointment;
use App\Models\Bloqueo;
use App\Models\Campana;
use App\Models\Local;
use App\Models\ServiceCenter;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Services\VehiculoSoapService;
use Illuminate\Support\Str;

class AgendarCita extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Agendar Cita';

    protected static ?string $title = 'Agendar Cita';

    protected static string $view = 'filament.pages.agendar-cita';

    // Ocultar de la navegación principal ya que se accederá desde la página de vehículos
    protected static bool $shouldRegisterNavigation = false;

    // Datos del vehículo seleccionado (se pasarán como parámetros)
    public array $vehiculo = [
        'id' => '',
        'placa' => 'DEF-456',
        'modelo' => 'RAV4 LIMITED',
        'anio' => '2022',
        'marca' => 'TOYOTA',
    ];

    // Datos del formulario
    public string $nombreCliente = 'PABLO';
    public string $emailCliente = 'pablo@mitsui.com.pe';
    public string $apellidoCliente = 'RODRIGUEZ MENDOZA';
    public string $celularCliente = '987654321';

    // Datos de la cita
    public string $fechaSeleccionada = '';
    public string $horaSeleccionada = '';
    public string $localSeleccionado = '';
    public string $servicioSeleccionado = 'Mantenimiento periódico';
    public string $tipoMantenimiento = '20,000 Km';
    public string $modalidadServicio = 'Regular';

    // Datos del calendario
    public int $mesActual;
    public int $anoActual;
    public array $diasCalendario = [];
    public array $horariosDisponibles = [];

    // Servicios adicionales
    public array $serviciosAdicionales = ['restauracion_faros'];

    // Comentarios
    public string $comentarios = '';

    // Pasos del formulario
    public int $pasoActual = 1;
    public int $totalPasos = 3;
    public bool $citaAgendada = false;


    // Locales disponibles
    public array $locales = [
        'molina' => [
            'nombre' => 'Mitsui La Molina',
            'direccion' => 'Av. Javier Prado Este 6042, La Molina 15024'
        ],
        'miraflores' => [
            'nombre' => 'Mitsui Miraflores',
            'direccion' => 'Av. Comandante Espinar 428, Miraflores 15074'
        ],
        'canada' => [
            'nombre' => 'Mitsui Canadá',
            'direccion' => 'Av. Canadá 120, La Victoria 15034'
        ],
        'arequipa' => [
            'nombre' => 'Mitsui Arequipa',
            'direccion' => 'Av. Villa Hermosa 1151 Cerro Colorado - Arequipa'
        ]
    ];

    // Horarios disponibles
    public array $horarios = [
        '08:00 AM',
        '09:15 AM',
        '10:15 AM',
        '11:15 AM',
        '01:00 PM',
        '02:00 PM',
    ];

    // Servicios adicionales disponibles
    public array $opcionesServiciosAdicionales = [];

    // Campañas disponibles
    public array $campanasDisponibles = [];

    public function mount($vehiculoId = null): void
    {
        // Registrar todos los parámetros recibidos para depuración
        Log::info("[AgendarCita] Parámetros recibidos en mount:", [
            'vehiculoId' => $vehiculoId,
            'request_all' => request()->all(),
            'query_string' => request()->getQueryString(),
        ]);

        // Intentar obtener el ID del vehículo de diferentes fuentes
        if (empty($vehiculoId) && request()->has('vehiculoId')) {
            $vehiculoId = request()->input('vehiculoId');
            Log::info("[AgendarCita] Obteniendo vehiculoId desde request()->input(): {$vehiculoId}");
        }

        // Cargar datos del vehículo basados en el ID recibido
        if ($vehiculoId) {
            // Limpiar el ID del vehículo (eliminar espacios)
            $vehiculoId = trim(str_replace(' ', '', $vehiculoId));
            Log::info("[AgendarCita] Cargando datos para vehículo ID (limpio): {$vehiculoId}");

            // Primero intentamos buscar el vehículo en la base de datos
            $vehiculoEncontrado = Vehicle::where('license_plate', $vehiculoId)
                ->orWhere('vehicle_id', $vehiculoId)
                ->first();

            if ($vehiculoEncontrado) {
                // Si encontramos el vehículo en la base de datos, usamos sus datos
                $this->vehiculo = [
                    'id' => $vehiculoEncontrado->vehicle_id,
                    'placa' => $vehiculoEncontrado->license_plate,
                    'modelo' => $vehiculoEncontrado->model,
                    'anio' => $vehiculoEncontrado->year,
                    'marca' => $vehiculoEncontrado->brand_name,
                ];

                Log::info("[AgendarCita] Vehículo encontrado en la base de datos:", $this->vehiculo);
            } else {
                // Si no encontramos el vehículo en la base de datos, intentamos buscarlo en el servicio SOAP
                try {
                    $service = app(VehiculoSoapService::class);
                    $documentoCliente = '20605414410'; // En un caso real, esto vendría del usuario autenticado
                    $marcas = ['Z01', 'Z02', 'Z03']; // Todas las marcas disponibles

                    // Obtener todos los vehículos del cliente
                    $vehiculos = $service->getVehiculosCliente($documentoCliente, $marcas);
                    Log::info("[AgendarCita] Total de vehículos obtenidos del servicio SOAP: {$vehiculos->count()}");

                    // Buscar el vehículo por ID (puede ser placa o vhclie)
                    $vehiculoEncontradoSoap = $vehiculos->first(function ($vehiculo) use ($vehiculoId) {
                        return strtoupper($vehiculo['numpla']) == strtoupper($vehiculoId) || $vehiculo['vhclie'] == $vehiculoId;
                    });

                    if ($vehiculoEncontradoSoap) {
                        // Si encontramos el vehículo en el servicio SOAP, usamos sus datos
                        $this->vehiculo = [
                            'id' => $vehiculoEncontradoSoap['vhclie'],
                            'placa' => $vehiculoEncontradoSoap['numpla'],
                            'modelo' => $vehiculoEncontradoSoap['modver'],
                            'anio' => $vehiculoEncontradoSoap['aniomod'],
                            'marca' => isset($vehiculoEncontradoSoap['marca_codigo']) ?
                                      ($vehiculoEncontradoSoap['marca_codigo'] == 'Z01' ? 'TOYOTA' :
                                       ($vehiculoEncontradoSoap['marca_codigo'] == 'Z02' ? 'LEXUS' : 'HINO')) : 'TOYOTA',
                        ];

                        Log::info("[AgendarCita] Vehículo encontrado en el servicio SOAP:", $this->vehiculo);
                    } else {
                        // Si no encontramos el vehículo en ninguna parte, mantenemos los valores predeterminados
                        Log::warning("[AgendarCita] No se encontró el vehículo con ID: {$vehiculoId}. Manteniendo valores predeterminados.");
                        Log::info("[AgendarCita] Valores predeterminados mantenidos:", $this->vehiculo);
                    }
                } catch (\Exception $e) {
                    // En caso de error, mantener los valores predeterminados
                    Log::error("[AgendarCita] Error al cargar datos del vehículo desde el servicio SOAP: " . $e->getMessage());
                    Log::info("[AgendarCita] Valores predeterminados mantenidos en caso de error:", $this->vehiculo);
                }
            }
        } else {
            // Si no se proporcionó un ID de vehículo, mantener los valores predeterminados
            Log::warning("[AgendarCita] No se proporcionó ID de vehículo. Manteniendo valores predeterminados.");
        }

        // Cargar los locales desde la base de datos
        $this->cargarLocales();

        // Cargar los servicios adicionales desde la base de datos
        $this->cargarServiciosAdicionales();

        // Inicializar el calendario con el mes y año actual
        $fechaActual = Carbon::now();
        $this->mesActual = $fechaActual->month;
        $this->anoActual = $fechaActual->year;

        // Generar el calendario para el mes actual
        $this->generarCalendario();

        // Verificar el estado final de la variable vehiculo
        Log::info("[AgendarCita] Estado final de la variable vehiculo:", $this->vehiculo ?? ['vehiculo' => 'null']);
    }

    /**
     * Cargar los locales desde la tabla de locales
     */
    protected function cargarLocales(): void
    {
        // Obtener los locales activos de la tabla locales
        $localesActivos = \App\Models\Local::where('activo', true)->get();

        Log::info("[AgendarCita] Consultando locales activos. Total encontrados: " . $localesActivos->count());

        // Verificar si hay locales en la base de datos
        $todosLosLocales = \App\Models\Local::all();
        Log::info("[AgendarCita] Total de locales en la base de datos: " . $todosLosLocales->count());

        foreach ($todosLosLocales as $index => $local) {
            Log::info("[AgendarCita] Local #{$index} en DB: ID: {$local->id}, Código: {$local->codigo}, Nombre: {$local->nombre}, Activo: " . ($local->activo ? 'Sí' : 'No'));
        }

        if ($localesActivos->isNotEmpty()) {
            $this->locales = [];

            foreach ($localesActivos as $local) {
                $key = $local->codigo;
                $this->locales[$key] = [
                    'nombre' => $local->nombre,
                    'direccion' => $local->direccion,
                    'telefono' => $local->telefono,
                    'horario_apertura' => $local->horario_apertura ?: '08:00:00',
                    'horario_cierre' => $local->horario_cierre ?: '17:00:00',
                    'id' => $local->id,
                ];

                Log::info("[AgendarCita] Local cargado: Código: {$key}, ID: {$local->id}, Nombre: {$local->nombre}, Horario: {$local->horario_apertura} - {$local->horario_cierre}");
            }

            Log::info("[AgendarCita] Locales cargados: " . count($this->locales));

            // Establecer el primer local como seleccionado por defecto
            if (empty($this->localSeleccionado) && !empty($this->locales)) {
                $this->localSeleccionado = array_key_first($this->locales);
                Log::info("[AgendarCita] Local seleccionado por defecto: {$this->localSeleccionado}, ID: {$this->locales[$this->localSeleccionado]['id']}");

                // Regenerar el calendario para actualizar la disponibilidad según el local seleccionado
                if (isset($this->mesActual) && isset($this->anoActual)) {
                    $this->generarCalendario();
                }
            }
        } else {
            Log::warning("[AgendarCita] No se encontraron locales activos en la base de datos");

            // Si no hay locales en la base de datos, crear algunos locales de prueba
            $this->locales = [
                'sede1' => [
                    'nombre' => 'Sede Principal',
                    'direccion' => 'Av. Principal 123',
                    'telefono' => '123-456-789',
                    'horario_apertura' => '08:00:00',
                    'horario_cierre' => '17:00:00',
                    'id' => 1,
                ],
                'sede2' => [
                    'nombre' => 'Sede Secundaria',
                    'direccion' => 'Av. Secundaria 456',
                    'telefono' => '987-654-321',
                    'horario_apertura' => '09:00:00',
                    'horario_cierre' => '18:00:00',
                    'id' => 2,
                ],
            ];

            Log::info("[AgendarCita] Creados locales de prueba: " . count($this->locales));

            // Establecer el primer local como seleccionado por defecto
            if (empty($this->localSeleccionado)) {
                $this->localSeleccionado = array_key_first($this->locales);
                Log::info("[AgendarCita] Local de prueba seleccionado por defecto: {$this->localSeleccionado}");
            }
        }
    }

    /**
     * Cargar los servicios adicionales desde la base de datos
     */
    protected function cargarServiciosAdicionales(): void
    {
        // Por solicitud del cliente, no cargamos los servicios adicionales tradicionales
        $this->opcionesServiciosAdicionales = [];

        Log::info("[AgendarCita] Servicios adicionales tradicionales no cargados por solicitud del cliente");

        // Cargar campañas activas
        $this->cargarCampanas();
    }

    /**
     * Cargar las campañas activas desde la base de datos
     */
    protected function cargarCampanas(): void
    {
        try {
            // Obtener todas las campañas sin filtrar por fecha o estado
            $campanas = Campana::all();

            Log::info("[AgendarCita] Consultando campañas activas. Total encontradas: " . $campanas->count());

            // Verificar si hay campañas en la base de datos
            $todasLasCampanas = Campana::all();
            Log::info("[AgendarCita] Total de campañas en la base de datos: " . $todasLasCampanas->count());

            foreach ($todasLasCampanas as $index => $campana) {
                Log::info("[AgendarCita] Campaña #{$index} en DB: ID: {$campana->id}, Código: {$campana->codigo}, Título: {$campana->titulo}, Estado: {$campana->estado}, Fecha inicio: {$campana->fecha_inicio}, Fecha fin: {$campana->fecha_fin}");

                // Verificar si tiene imagen
                try {
                    $imagen = DB::table('campana_imagenes')->where('campana_id', $campana->id)->first();
                    $tieneImagen = $imagen ? "Sí (ID: {$imagen->id}, Ruta: {$imagen->ruta})" : "No";
                    Log::info("[AgendarCita] Campaña #{$index} tiene imagen: {$tieneImagen}");
                } catch (\Exception $e) {
                    Log::error("[AgendarCita] Error al verificar imagen de campaña #{$index}: " . $e->getMessage());
                }

                // Verificar modelos asociados
                try {
                    $modelos = DB::table('campana_modelos')
                        ->join('modelos', 'campana_modelos.modelo_id', '=', 'modelos.id')
                        ->where('campana_modelos.campana_id', $campana->id)
                        ->pluck('modelos.nombre')
                        ->toArray();
                    Log::info("[AgendarCita] Campaña #{$index} modelos: " . (empty($modelos) ? "Ninguno" : implode(', ', $modelos)));
                } catch (\Exception $e) {
                    Log::error("[AgendarCita] Error al verificar modelos de campaña #{$index}: " . $e->getMessage());
                }

                // Verificar años asociados
                try {
                    $anos = DB::table('campana_anos')
                        ->where('campana_id', $campana->id)
                        ->pluck('ano')
                        ->toArray();
                    Log::info("[AgendarCita] Campaña #{$index} años: " . (empty($anos) ? "Ninguno" : implode(', ', $anos)));
                } catch (\Exception $e) {
                    Log::error("[AgendarCita] Error al verificar años de campaña #{$index}: " . $e->getMessage());
                }

                // Verificar locales asociados
                try {
                    $locales = DB::table('campana_locales')
                        ->join('locales', 'campana_locales.local_codigo', '=', 'locales.codigo')
                        ->where('campana_locales.campana_id', $campana->id)
                        ->pluck('locales.nombre')
                        ->toArray();
                    Log::info("[AgendarCita] Campaña #{$index} locales: " . (empty($locales) ? "Ninguno" : implode(', ', $locales)));
                } catch (\Exception $e) {
                    Log::error("[AgendarCita] Error al verificar locales de campaña #{$index}: " . $e->getMessage());
                }
            }

            if ($campanas->isNotEmpty()) {
                $this->campanasDisponibles = [];

                foreach ($campanas as $campana) {
                    // Todas las campañas se muestran sin filtrar
                    // Obtener la imagen de la campaña desde la tabla campana_imagenes
                    $imagenObj = DB::table('campana_imagenes')->where('campana_id', $campana->id)->first();

                    // Construir la URL correcta para la imagen
                    if ($imagenObj && $imagenObj->ruta) {
                        try {
                            // Intentar diferentes enfoques para obtener la URL de la imagen
                            $rutaCompleta = $imagenObj->ruta;
                            Log::info("[AgendarCita] Ruta completa de la imagen: {$rutaCompleta}");

                            // Método 1: Usar route('imagen.campana', ['id' => $campana->id])
                            $imagen = route('imagen.campana', ['id' => $campana->id]);
                            Log::info("[AgendarCita] URL de imagen generada con route: {$imagen}");

                            // Registrar información detallada para depuración
                            Log::info("[AgendarCita] Campaña {$campana->id} tiene imagen: {$rutaCompleta}, URL generada: {$imagen}");
                        } catch (\Exception $e) {
                            // Si hay algún error, usar una imagen por defecto
                            $imagen = asset('images/default-campaign.jpg');
                            Log::error("[AgendarCita] Error al generar URL de imagen: " . $e->getMessage());
                        }
                    } else {
                        // Si no hay imagen, usar una imagen por defecto
                        $imagen = asset('images/default-campaign.jpg');
                        Log::info("[AgendarCita] Campaña {$campana->id} no tiene imagen, usando imagen por defecto");
                    }

                    $this->campanasDisponibles[] = [
                        'id' => $campana->id,
                        'titulo' => $campana->titulo,
                        'descripcion' => $campana->titulo, // Usamos el título como descripción ya que no hay campo de descripción
                        'imagen' => $imagen,
                        'fecha_inicio' => $campana->fecha_inicio,
                        'fecha_fin' => $campana->fecha_fin,
                    ];

                    Log::info("[AgendarCita] Campaña cargada: ID: {$campana->id}, Título: {$campana->titulo}, Imagen: {$imagen}");
                }

                Log::info("[AgendarCita] Campañas disponibles cargadas: " . count($this->campanasDisponibles));
            } else {
                Log::info("[AgendarCita] No se encontraron campañas activas");

                // Si no hay campañas en la base de datos, crear algunas campañas de prueba
                $this->campanasDisponibles = [
                    [
                        'id' => 1,
                        'titulo' => 'Mantenimiento con 20% de descuento',
                        'descripcion' => 'Aprovecha esta promoción por tiempo limitado',
                        'imagen' => route('imagen.campana', ['id' => 1]),
                        'fecha_inicio' => now()->format('Y-m-d'),
                        'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
                    ],
                    [
                        'id' => 2,
                        'titulo' => 'Revisión gratuita de frenos',
                        'descripcion' => 'Incluye revisión de pastillas y discos',
                        'imagen' => route('imagen.campana', ['id' => 2]),
                        'fecha_inicio' => now()->format('Y-m-d'),
                        'fecha_fin' => now()->addDays(15)->format('Y-m-d'),
                    ],
                    [
                        'id' => 3,
                        'titulo' => 'Cambio de aceite a precio especial',
                        'descripcion' => 'Incluye filtro de aceite y revisión de 10 puntos',
                        'imagen' => route('imagen.campana', ['id' => 3]),
                        'fecha_inicio' => now()->format('Y-m-d'),
                        'fecha_fin' => now()->addDays(45)->format('Y-m-d'),
                    ],
                ];

                Log::info("[AgendarCita] Creadas campañas de prueba: " . count($this->campanasDisponibles));
            }
        } catch (\Exception $e) {
            Log::error("[AgendarCita] Error al cargar campañas: " . $e->getMessage());

            // En caso de error, crear algunas campañas de prueba
            $this->campanasDisponibles = [
                [
                    'id' => 1,
                    'titulo' => 'Mantenimiento con 20% de descuento',
                    'descripcion' => 'Aprovecha esta promoción por tiempo limitado',
                    'imagen' => route('imagen.campana', ['id' => 1]),
                    'fecha_inicio' => now()->format('Y-m-d'),
                    'fecha_fin' => now()->addDays(30)->format('Y-m-d'),
                ],
                [
                    'id' => 2,
                    'titulo' => 'Revisión gratuita de frenos',
                    'descripcion' => 'Incluye revisión de pastillas y discos',
                    'imagen' => route('imagen.campana', ['id' => 2]),
                    'fecha_inicio' => now()->format('Y-m-d'),
                    'fecha_fin' => now()->addDays(15)->format('Y-m-d'),
                ],
            ];

            Log::info("[AgendarCita] Creadas campañas de prueba por error: " . count($this->campanasDisponibles));
        }
    }

    /**
     * Verificar si una campaña aplica para el vehículo seleccionado
     * Por solicitud del cliente, mostramos todas las campañas sin filtrar
     */
    protected function verificarCampanaAplicaParaVehiculo($campana): bool
    {
        // Mostrar todas las campañas sin filtrar
        Log::info("[AgendarCita] Mostrando todas las campañas sin filtrar por solicitud del cliente");
        return true;
    }

    // Método para volver a la página de vehículos
    public function volverAVehiculos(): void
    {
        $this->redirect(Vehiculos::getUrl());
    }

    // Método para continuar al siguiente paso
    public function continuar(): void
    {
        // Verificar el estado de la variable vehiculo antes de continuar
        Log::info("[AgendarCita] Estado de la variable vehiculo antes de continuar (paso {$this->pasoActual}):", $this->vehiculo ?? ['vehiculo' => 'null']);

        // Validación básica para el paso 1
        if ($this->pasoActual == 1) {
            // Aquí podríamos agregar validaciones para los campos
            // Por ahora simplemente avanzamos al siguiente paso
            $this->pasoActual++;
        }
        // En el paso 2, guardamos la cita y avanzamos al paso 3
        elseif ($this->pasoActual == 2) {
            // Guardar la cita
            $this->guardarCita();
            // Avanzar al paso 3 (confirmación)
            $this->pasoActual++;
        }

        // Verificar el estado de la variable vehiculo después de continuar
        Log::info("[AgendarCita] Estado de la variable vehiculo después de continuar (paso {$this->pasoActual}):", $this->vehiculo ?? ['vehiculo' => 'null']);
    }

    // Método para volver al paso anterior
    public function volver(): void
    {
        if ($this->pasoActual > 1) {
            $this->pasoActual--;
        } else {
            // Si estamos en el primer paso, volver a la página de vehículos
            $this->volverAVehiculos();
        }
    }

    // Método para guardar la cita
    protected function guardarCita(): void
    {
        try {
            // Validar datos básicos
            if (empty($this->nombreCliente) || empty($this->apellidoCliente) || empty($this->localSeleccionado)) {
                \Filament\Notifications\Notification::make()
                    ->title('Error al Agendar Cita')
                    ->body('Por favor complete todos los campos obligatorios.')
                    ->danger()
                    ->send();
                return;
            }

            // Obtener el vehículo
            $vehicle = Vehicle::where('license_plate', $this->vehiculo['placa'])->first();

            if (!$vehicle) {
                \Filament\Notifications\Notification::make()
                    ->title('Error al Agendar Cita')
                    ->body('No se encontró el vehículo seleccionado.')
                    ->danger()
                    ->send();
                return;
            }

            // Obtener el local seleccionado
            $localSeleccionado = null;
            if (!empty($this->localSeleccionado)) {
                $localSeleccionado = Local::where('codigo', $this->localSeleccionado)->first();
            }

            if (!$localSeleccionado) {
                \Filament\Notifications\Notification::make()
                    ->title('Error al Agendar Cita')
                    ->body('No se encontró el local seleccionado.')
                    ->danger()
                    ->send();
                return;
            }

            Log::info("[AgendarCita] Local seleccionado para la cita: {$localSeleccionado->nombre} (ID: {$localSeleccionado->id})");

            // Obtener el tipo de servicio
            $serviceType = ServiceType::where('name', 'like', "%{$this->servicioSeleccionado}%")->first();

            if (!$serviceType) {
                // Si no encontramos el tipo de servicio, creamos uno nuevo
                $serviceType = ServiceType::create([
                    'code' => 'SERV-' . Str::random(5),
                    'name' => $this->servicioSeleccionado,
                    'category' => $this->servicioSeleccionado === 'Mantenimiento periódico' ? 'maintenance' : 'other',
                    'duration_minutes' => 120,
                    'is_active' => true,
                ]);
            }

            // Convertir la fecha de formato DD/MM/YYYY a YYYY-MM-DD
            $fechaPartes = explode('/', $this->fechaSeleccionada);
            $fechaFormateada = $fechaPartes[2] . '-' . $fechaPartes[1] . '-' . $fechaPartes[0];

            // Convertir la hora de formato "11:15 AM" a formato "HH:MM:SS"
            $horaFormateada = date('H:i:s', strtotime($this->horaSeleccionada));

            // Crear la cita
            $appointment = new Appointment();
            $appointment->appointment_number = 'CITA-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            $appointment->vehicle_id = $vehicle->id;
            $appointment->service_center_id = $localSeleccionado->id;
            $appointment->service_type_id = $serviceType->id;
            $appointment->customer_ruc = '20605414410';
            $appointment->customer_name = $this->nombreCliente;
            $appointment->customer_last_name = $this->apellidoCliente;
            $appointment->customer_email = $this->emailCliente;
            $appointment->customer_phone = $this->celularCliente;
            $appointment->appointment_date = $fechaFormateada;
            $appointment->appointment_time = $horaFormateada;
            $appointment->service_mode = $this->modalidadServicio;
            $appointment->maintenance_type = $this->tipoMantenimiento;
            $appointment->comments = $this->comentarios;
            $appointment->status = 'pending';
            $appointment->save();

            // Guardar los servicios adicionales
            if (!empty($this->serviciosAdicionales)) {
                foreach ($this->serviciosAdicionales as $servicioAdicionalKey) {
                    // Verificar si es una campaña
                    if (strpos($servicioAdicionalKey, 'campana_') === 0) {
                        // Es una campaña, extraer el ID
                        $campanaId = substr($servicioAdicionalKey, 8);
                        Log::info("[AgendarCita] Procesando campaña con ID: {$campanaId}");

                        // Buscar la campaña en el array de campañas disponibles
                        $campanaEncontrada = null;
                        foreach ($this->campanasDisponibles as $campana) {
                            if ($campana['id'] == $campanaId) {
                                $campanaEncontrada = $campana;
                                break;
                            }
                        }

                        if ($campanaEncontrada) {
                            // Crear un servicio adicional para la campaña si no existe
                            $nombreServicio = "Campaña: " . $campanaEncontrada['titulo'];
                            $additionalService = AdditionalService::firstOrCreate(
                                ['name' => $nombreServicio],
                                [
                                    'description' => $campanaEncontrada['descripcion'],
                                    'is_active' => true,
                                    'price' => 0, // Precio promocional
                                ]
                            );

                            // Adjuntar el servicio a la cita
                            $appointment->additionalServices()->attach($additionalService->id, [
                                'notes' => "Campaña ID: {$campanaId}, Válida hasta: " . $campanaEncontrada['fecha_fin']
                            ]);

                            Log::info("[AgendarCita] Campaña adjuntada a la cita: {$nombreServicio}");
                        } else {
                            Log::warning("[AgendarCita] No se encontró la campaña con ID: {$campanaId}");
                        }
                    } else {
                        // Ignoramos los servicios adicionales tradicionales por solicitud del cliente
                        Log::info("[AgendarCita] Ignorando servicio adicional tradicional: {$servicioAdicionalKey}");
                    }
                }
            }

            // Marcar la cita como agendada
            $this->citaAgendada = true;

            // Registrar en el log
            Log::info("[AgendarCita] Cita agendada exitosamente:", [
                'appointment_number' => $appointment->appointment_number,
                'vehicle' => $vehicle->license_plate,
                'customer' => $appointment->customer_name . ' ' . $appointment->customer_last_name,
                'date' => $appointment->appointment_date,
                'time' => $appointment->appointment_time,
            ]);

            // Mostrar notificación de éxito
            \Filament\Notifications\Notification::make()
                ->title('Cita Agendada')
                ->body('Tu cita ha sido agendada exitosamente.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            // Registrar el error
            Log::error("[AgendarCita] Error al guardar la cita: " . $e->getMessage());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al Agendar Cita')
                ->body('Ocurrió un error al agendar la cita: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    // Método para finalizar el proceso de agendamiento
    public function finalizarAgendamiento(): void
    {
        // Verificar el estado de la variable vehiculo antes de avanzar
        Log::info("[AgendarCita] Estado de la variable vehiculo antes de avanzar al paso 2:", $this->vehiculo ?? ['vehiculo' => 'null']);

        // Verificar que se haya seleccionado un local
        if (empty($this->localSeleccionado)) {
            // Si no se ha seleccionado un local, seleccionar el primero por defecto
            if (!empty($this->locales)) {
                $this->localSeleccionado = array_key_first($this->locales);
                Log::info("[AgendarCita] Seleccionando local por defecto: {$this->localSeleccionado}");
            }
        }

        // Verificar que se haya seleccionado una fecha
        if (empty($this->fechaSeleccionada)) {
            // Si no se ha seleccionado una fecha, usar la fecha actual
            $this->fechaSeleccionada = date('d/m/Y');
            Log::info("[AgendarCita] Seleccionando fecha por defecto: {$this->fechaSeleccionada}");
        }

        // Verificar que se haya seleccionado una hora
        if (empty($this->horaSeleccionada)) {
            // Si no se ha seleccionado una hora, usar la primera disponible
            $this->horaSeleccionada = $this->horarios[0] ?? '11:15 AM';
            Log::info("[AgendarCita] Seleccionando hora por defecto: {$this->horaSeleccionada}");
        }

        // Verificar que los datos del vehículo estén completos
        if (empty($this->vehiculo['id']) || empty($this->vehiculo['placa']) || empty($this->vehiculo['modelo'])) {
            // Intentar buscar el vehículo en la base de datos
            $vehiculoEncontrado = null;

            if (!empty($this->vehiculo['placa'])) {
                $vehiculoEncontrado = Vehicle::where('license_plate', $this->vehiculo['placa'])->first();
            }

            if (!$vehiculoEncontrado && !empty($this->vehiculo['id'])) {
                $vehiculoEncontrado = Vehicle::where('vehicle_id', $this->vehiculo['id'])->first();
            }

            if ($vehiculoEncontrado) {
                // Si encontramos el vehículo en la base de datos, usamos sus datos
                $this->vehiculo = [
                    'id' => $vehiculoEncontrado->vehicle_id,
                    'placa' => $vehiculoEncontrado->license_plate,
                    'modelo' => $vehiculoEncontrado->model,
                    'anio' => $vehiculoEncontrado->year,
                    'marca' => $vehiculoEncontrado->brand_name,
                ];

                Log::info("[AgendarCita] Vehículo actualizado desde la base de datos:", $this->vehiculo);
            } else {
                // Si no encontramos el vehículo, asegurarnos de que al menos tenga valores predeterminados
                if (empty($this->vehiculo['modelo'])) {
                    $this->vehiculo['modelo'] = 'No especificado';
                }
                if (empty($this->vehiculo['placa'])) {
                    $this->vehiculo['placa'] = 'No especificado';
                }
                if (empty($this->vehiculo['anio'])) {
                    $this->vehiculo['anio'] = 'No especificado';
                }
                if (empty($this->vehiculo['marca'])) {
                    $this->vehiculo['marca'] = 'No especificado';
                }

                Log::info("[AgendarCita] Vehículo actualizado con valores predeterminados:", $this->vehiculo);
            }
        }

        // Registrar los valores seleccionados
        Log::info("[AgendarCita] Valores seleccionados:", [
            'vehiculo' => $this->vehiculo,
            'localSeleccionado' => $this->localSeleccionado,
            'fechaSeleccionada' => $this->fechaSeleccionada,
            'horaSeleccionada' => $this->horaSeleccionada,
            'servicioSeleccionado' => $this->servicioSeleccionado,
            'tipoMantenimiento' => $this->tipoMantenimiento,
            'modalidadServicio' => $this->modalidadServicio,
            'serviciosAdicionales' => $this->serviciosAdicionales,
            'comentarios' => $this->comentarios,
        ]);

        // Avanzar al paso 2 (resumen)
        $this->pasoActual = 2;

        // Verificar el estado de la variable vehiculo después de avanzar
        Log::info("[AgendarCita] Estado de la variable vehiculo después de avanzar al paso 2:", $this->vehiculo ?? ['vehiculo' => 'null']);
    }

    // Método para cerrar y volver a la página de citas
    public function cerrarYVolverACitas(): void
    {
        // Redirigir a la página de vehículos (o citas en el futuro)
        $this->redirect(Vehiculos::getUrl());
    }

    /**
     * Genera el calendario para el mes y año actual
     */
    public function generarCalendario(): void
    {
        // Crear una fecha para el primer día del mes actual
        $primerDia = Carbon::createFromDate($this->anoActual, $this->mesActual, 1);

        // Obtener el día de la semana del primer día (0 = domingo, 1 = lunes, ..., 6 = sábado)
        $diaSemana = $primerDia->dayOfWeek;

        // Ajustar para que la semana comience en lunes (0 = lunes, ..., 6 = domingo)
        $diaSemana = $diaSemana == 0 ? 6 : $diaSemana - 1;

        // Obtener el número de días en el mes actual
        $diasEnMes = $primerDia->daysInMonth;

        // Crear un array para almacenar los días del calendario
        $diasCalendario = [];

        // Agregar los días del mes anterior
        $mesAnterior = $primerDia->copy()->subMonth();
        $diasEnMesAnterior = $mesAnterior->daysInMonth;

        for ($i = 0; $i < $diaSemana; $i++) {
            $dia = $diasEnMesAnterior - $diaSemana + $i + 1;
            $fecha = $mesAnterior->copy()->setDay($dia);

            $diasCalendario[] = [
                'dia' => $dia,
                'mes' => $mesAnterior->month,
                'ano' => $mesAnterior->year,
                'esActual' => false,
                'fecha' => $fecha->format('d/m/Y'),
                'disponible' => false, // Los días del mes anterior no están disponibles
                'esPasado' => $fecha->isPast(),
                'esHoy' => $fecha->isToday(),
            ];
        }

        // Agregar los días del mes actual
        $fechaActual = Carbon::now();
        $fechaHoy = Carbon::today();

        for ($dia = 1; $dia <= $diasEnMes; $dia++) {
            $fecha = Carbon::createFromDate($this->anoActual, $this->mesActual, $dia);

            // Verificar si la fecha es pasada o es hoy
            $esPasado = $fecha->lt($fechaHoy);
            $esHoy = $fecha->eq($fechaHoy);

            // Verificar si hay bloqueos para esta fecha y local
            $disponible = !$esPasado && $this->verificarDisponibilidadFecha($fecha);

            $diasCalendario[] = [
                'dia' => $dia,
                'mes' => $this->mesActual,
                'ano' => $this->anoActual,
                'esActual' => true,
                'fecha' => $fecha->format('d/m/Y'),
                'disponible' => $disponible,
                'esPasado' => $esPasado,
                'esHoy' => $esHoy,
                'seleccionado' => $fecha->format('d/m/Y') === $this->fechaSeleccionada,
            ];
        }

        // Agregar los días del mes siguiente para completar la última semana
        $totalDias = count($diasCalendario);
        $diasRestantes = 42 - $totalDias; // 6 semanas x 7 días = 42 días en total

        $mesSiguiente = $primerDia->copy()->addMonth();

        for ($dia = 1; $dia <= $diasRestantes; $dia++) {
            $fecha = $mesSiguiente->copy()->setDay($dia);

            $diasCalendario[] = [
                'dia' => $dia,
                'mes' => $mesSiguiente->month,
                'ano' => $mesSiguiente->year,
                'esActual' => false,
                'fecha' => $fecha->format('d/m/Y'),
                'disponible' => false, // Los días del mes siguiente no están disponibles
                'esPasado' => false,
                'esHoy' => false,
            ];
        }

        $this->diasCalendario = $diasCalendario;

        // Si hay una fecha seleccionada, cargar los horarios disponibles
        if (!empty($this->fechaSeleccionada)) {
            $this->cargarHorariosDisponibles();
        }
    }

    /**
     * Verifica si una fecha está disponible (no tiene bloqueos completos)
     */
    private function verificarDisponibilidadFecha(Carbon $fecha): bool
    {
        // Si no hay local seleccionado, no podemos verificar disponibilidad
        if (empty($this->localSeleccionado) || empty($this->locales[$this->localSeleccionado]['id'])) {
            return true;
        }

        $localId = $this->locales[$this->localSeleccionado]['id'];
        $fechaStr = $fecha->format('Y-m-d');

        Log::info("[AgendarCita] Verificando disponibilidad para fecha: {$fechaStr}, local ID: {$localId}");

        // Buscar bloqueos para esta fecha y local que sean de todo el día
        $bloqueoCompleto = Bloqueo::where('local', $localId)
            ->where('fecha_inicio', '<=', $fechaStr)
            ->where('fecha_fin', '>=', $fechaStr)
            ->where('todo_dia', true)
            ->exists();

        // Depuración detallada de la consulta de bloqueos completos
        $queryBloqueoCompleto = Bloqueo::where('local', $localId)
            ->where('fecha_inicio', '<=', $fechaStr)
            ->where('fecha_fin', '>=', $fechaStr)
            ->where('todo_dia', true)
            ->toSql();
        Log::info("[AgendarCita] Consulta SQL para bloqueos completos: {$queryBloqueoCompleto}");
        Log::info("[AgendarCita] Parámetros: local={$localId}, fecha={$fechaStr}, resultado=" . ($bloqueoCompleto ? 'Sí' : 'No'));

        // Si hay un bloqueo completo, la fecha no está disponible
        if ($bloqueoCompleto) {
            Log::info("[AgendarCita] Fecha {$fechaStr} bloqueada completamente para local ID: {$localId}");
            return false;
        }

        // Verificar si hay al menos un horario disponible en esta fecha
        $bloqueosParciales = Bloqueo::where('local', $localId)
            ->where('fecha_inicio', '<=', $fechaStr)
            ->where('fecha_fin', '>=', $fechaStr)
            ->where('todo_dia', false)
            ->get();

        // Si no hay bloqueos parciales, la fecha está disponible
        if ($bloqueosParciales->isEmpty()) {
            Log::info("[AgendarCita] No hay bloqueos parciales para fecha {$fechaStr}, local ID: {$localId}");
            return true;
        }

        // Verificar si todos los horarios están bloqueados
        $horariosBase = $this->obtenerHorariosBase();
        $horariosDisponibles = $horariosBase;

        foreach ($bloqueosParciales as $bloqueo) {
            $horaInicio = $bloqueo->hora_inicio;
            $horaFin = $bloqueo->hora_fin;

            // Asegurarse de que las horas tengan el formato correcto (HH:MM:SS)
            if (strlen($horaInicio) <= 5) {
                $horaInicio .= ':00';
            }
            if (strlen($horaFin) <= 5) {
                $horaFin .= ':00';
            }

            Log::info("[AgendarCita] Bloqueo parcial encontrado: {$horaInicio} - {$horaFin}");

            // Convertir horas a objetos Carbon para comparación más precisa
            try {
                // Intentar crear objetos Carbon para las horas de inicio y fin
                try {
                    $inicioCarbon = Carbon::createFromFormat('H:i:s', $horaInicio);
                } catch (\Exception $e) {
                    // Si falla, intentar con formato H:i
                    try {
                        $inicioCarbon = Carbon::createFromFormat('H:i', $horaInicio);
                    } catch (\Exception $e2) {
                        Log::error("[AgendarCita] No se pudo parsear la hora de inicio en verificación: {$horaInicio}");
                        continue;
                    }
                }

                try {
                    $finCarbon = Carbon::createFromFormat('H:i:s', $horaFin);
                } catch (\Exception $e) {
                    // Si falla, intentar con formato H:i
                    try {
                        $finCarbon = Carbon::createFromFormat('H:i', $horaFin);
                    } catch (\Exception $e2) {
                        Log::error("[AgendarCita] No se pudo parsear la hora de fin en verificación: {$horaFin}");
                        continue;
                    }
                }

                // Convertir a strings en formato H:i:s para comparación directa
                $inicioStr = $inicioCarbon->format('H:i:s');
                $finStr = $finCarbon->format('H:i:s');

                Log::info("[AgendarCita] Verificando bloqueo con horario normalizado: {$inicioStr} - {$finStr}");

                // Guardar los horarios antes del filtrado para poder ver qué se eliminó
                $horariosAntesFiltro = $horariosDisponibles;

                // Filtrar los horarios que están dentro del rango bloqueado usando comparación directa de strings
                $horariosDisponibles = array_filter($horariosDisponibles, function($hora) use ($inicioStr, $finStr) {
                    // Verificar si la hora está dentro del rango bloqueado
                    $dentroDelRango = ($hora >= $inicioStr && $hora <= $finStr);
                    if ($dentroDelRango) {
                        Log::info("[AgendarCita] Verificación: Hora {$hora} está dentro del rango bloqueado {$inicioStr} - {$finStr}");
                    }
                    return !$dentroDelRango;
                });

                // Registrar los horarios que fueron eliminados
                $horariosEliminados = array_diff($horariosAntesFiltro, $horariosDisponibles);
                if (!empty($horariosEliminados)) {
                    Log::info("[AgendarCita] Horarios eliminados en verificación: " . json_encode(array_values($horariosEliminados)));
                }
            } catch (\Exception $e) {
                Log::error("[AgendarCita] Error al procesar bloqueo en verificación: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            }
        }

        // Buscar citas existentes para esta fecha y local
        $citas = Appointment::where('service_center_id', $localId)
            ->where('appointment_date', $fechaStr)
            ->get();

        foreach ($citas as $cita) {
            // Filtrar los horarios que ya están ocupados por citas
            $horaOcupada = $cita->appointment_time;

            Log::info("[AgendarCita] Cita existente encontrada a las: {$horaOcupada}");

            $horariosDisponibles = array_filter($horariosDisponibles, function($hora) use ($horaOcupada) {
                return $hora !== $horaOcupada;
            });
        }

        $disponible = !empty($horariosDisponibles);
        Log::info("[AgendarCita] Fecha {$fechaStr} " . ($disponible ? "disponible" : "no disponible") . " para local ID: {$localId}. Horarios disponibles: " . count($horariosDisponibles));

        // Si no quedan horarios disponibles, la fecha no está disponible
        return $disponible;
    }

    /**
     * Carga los horarios disponibles para la fecha seleccionada
     */
    public function cargarHorariosDisponibles(): void
    {
        // Si no hay fecha seleccionada o local seleccionado, no podemos cargar horarios
        if (empty($this->fechaSeleccionada) || empty($this->localSeleccionado)) {
            $this->horariosDisponibles = [];
            Log::info("[AgendarCita] No se pueden cargar horarios: fecha o local no seleccionados");
            return;
        }

        // Convertir la fecha seleccionada a un objeto Carbon
        try {
            $fecha = Carbon::createFromFormat('d/m/Y', $this->fechaSeleccionada);
            $fechaStr = $fecha->format('Y-m-d');
            $localId = $this->locales[$this->localSeleccionado]['id'];

            Log::info("[AgendarCita] Cargando horarios para fecha: {$fechaStr}, local ID: {$localId}");

            // Obtener los horarios base
            $horariosBase = $this->obtenerHorariosBase();
            $horariosDisponibles = $horariosBase;

            Log::info("[AgendarCita] Horarios base: " . json_encode($horariosBase));

            // Buscar bloqueos para esta fecha y local
            $bloqueos = Bloqueo::where('local', $localId)
                ->where('fecha_inicio', '<=', $fechaStr)
                ->where('fecha_fin', '>=', $fechaStr)
                ->get();

            // Depuración detallada de la consulta de bloqueos
            Log::info("[AgendarCita] Consultando bloqueos para local ID: {$localId} y fecha: {$fechaStr}");

            // Verificar si hay bloqueos en la base de datos para cualquier local
            $todosLosBloqueos = Bloqueo::all();
            Log::info("[AgendarCita] Total de bloqueos en la base de datos: " . $todosLosBloqueos->count());

            foreach ($todosLosBloqueos as $index => $bloqueo) {
                Log::info("[AgendarCita] Bloqueo #{$index} en DB: Local: {$bloqueo->local}, Fecha: {$bloqueo->fecha_inicio} a {$bloqueo->fecha_fin}, Hora: {$bloqueo->hora_inicio} a {$bloqueo->hora_fin}, Todo día: " . ($bloqueo->todo_dia ? 'Sí' : 'No'));
            }

            // Depuración detallada de la consulta de bloqueos
            $query = Bloqueo::where('local', $localId)
                ->where('fecha_inicio', '<=', $fechaStr)
                ->where('fecha_fin', '>=', $fechaStr)
                ->toSql();
            $bindings = [
                'local' => $localId,
                'fecha_inicio' => $fechaStr,
                'fecha_fin' => $fechaStr,
            ];
            Log::info("[AgendarCita] Consulta SQL para bloqueos: {$query}");
            Log::info("[AgendarCita] Parámetros de consulta: " . json_encode($bindings));

            Log::info("[AgendarCita] Bloqueos encontrados: " . $bloqueos->count());

            if ($bloqueos->count() > 0) {
                Log::info("[AgendarCita] Detalles de bloqueos encontrados:");
                foreach ($bloqueos as $index => $bloqueo) {
                    Log::info("[AgendarCita] Bloqueo #{$index}: Local: {$bloqueo->local}, Fecha: {$bloqueo->fecha_inicio} a {$bloqueo->fecha_fin}, Hora: {$bloqueo->hora_inicio} a {$bloqueo->hora_fin}, Todo día: " . ($bloqueo->todo_dia ? 'Sí' : 'No'));
                }
            }

            foreach ($bloqueos as $bloqueo) {
                // Si es un bloqueo de todo el día, no hay horarios disponibles
                if ($bloqueo->todo_dia) {
                    Log::info("[AgendarCita] Bloqueo de todo el día encontrado para fecha: {$fechaStr}, local ID: {$localId}");
                    $horariosDisponibles = [];
                    break;
                }

                // Filtrar los horarios que están dentro del rango bloqueado
                $horaInicio = $bloqueo->hora_inicio;
                $horaFin = $bloqueo->hora_fin;

                // Asegurarse de que las horas tengan el formato correcto (HH:MM:SS)
                if (strlen($horaInicio) <= 5) {
                    $horaInicio .= ':00';
                }
                if (strlen($horaFin) <= 5) {
                    $horaFin .= ':00';
                }

                Log::info("[AgendarCita] Aplicando bloqueo parcial: {$horaInicio} - {$horaFin}");

                $horariosAntes = count($horariosDisponibles);

                // Convertir horas a objetos Carbon para comparación más precisa
                try {
                    // Intentar crear objetos Carbon para las horas de inicio y fin
                    try {
                        $inicioCarbon = Carbon::createFromFormat('H:i:s', $horaInicio);
                    } catch (\Exception $e) {
                        // Si falla, intentar con formato H:i
                        try {
                            $inicioCarbon = Carbon::createFromFormat('H:i', $horaInicio);
                        } catch (\Exception $e2) {
                            Log::error("[AgendarCita] No se pudo parsear la hora de inicio: {$horaInicio}");
                            continue;
                        }
                    }

                    try {
                        $finCarbon = Carbon::createFromFormat('H:i:s', $horaFin);
                    } catch (\Exception $e) {
                        // Si falla, intentar con formato H:i
                        try {
                            $finCarbon = Carbon::createFromFormat('H:i', $horaFin);
                        } catch (\Exception $e2) {
                            Log::error("[AgendarCita] No se pudo parsear la hora de fin: {$horaFin}");
                            continue;
                        }
                    }

                    // Convertir a strings en formato H:i:s para comparación directa
                    $inicioStr = $inicioCarbon->format('H:i:s');
                    $finStr = $finCarbon->format('H:i:s');

                    Log::info("[AgendarCita] Procesando bloqueo con horario normalizado: {$inicioStr} - {$finStr}");

                    // Guardar los horarios antes del filtrado para poder ver qué se eliminó
                    $horariosAntesFiltro = $horariosDisponibles;

                    // Filtrar los horarios que están dentro del rango bloqueado usando comparación directa de strings
                    $horariosDisponibles = array_filter($horariosDisponibles, function($hora) use ($inicioStr, $finStr) {
                        // Verificar si la hora está dentro del rango bloqueado
                        $dentroDelRango = ($hora >= $inicioStr && $hora <= $finStr);
                        if ($dentroDelRango) {
                            Log::info("[AgendarCita] Hora {$hora} está dentro del rango bloqueado {$inicioStr} - {$finStr}");
                        }
                        return !$dentroDelRango;
                    });

                    // Registrar los horarios que fueron eliminados
                    $horariosEliminados = array_diff($horariosAntesFiltro, $horariosDisponibles);
                    if (!empty($horariosEliminados)) {
                        Log::info("[AgendarCita] Horarios eliminados por bloqueo: " . json_encode(array_values($horariosEliminados)));
                    }

                    $horariosDespues = count($horariosDisponibles);
                    Log::info("[AgendarCita] Horarios filtrados por bloqueo: {$horariosAntes} -> {$horariosDespues}");

                    if ($horariosAntes > $horariosDespues) {
                        $cantidadEliminados = $horariosAntes - $horariosDespues;
                        Log::info("[AgendarCita] Se eliminaron {$cantidadEliminados} horarios debido al bloqueo");
                    }
                } catch (\Exception $e) {
                    Log::error("[AgendarCita] Error al procesar bloqueo: " . $e->getMessage() . "\n" . $e->getTraceAsString());
                }
            }

            // Buscar citas existentes para esta fecha y local
            $citas = Appointment::where('service_center_id', $localId)
                ->where('appointment_date', $fechaStr)
                ->get();

            Log::info("[AgendarCita] Citas existentes encontradas: " . $citas->count());

            foreach ($citas as $cita) {
                // Filtrar los horarios que ya están ocupados por citas
                $horaOcupada = $cita->appointment_time;

                Log::info("[AgendarCita] Filtrando horario ocupado por cita: {$horaOcupada}");

                $horariosAntes = count($horariosDisponibles);
                $horariosDisponibles = array_filter($horariosDisponibles, function($hora) use ($horaOcupada) {
                    return $hora !== $horaOcupada;
                });
                $horariosDespues = count($horariosDisponibles);

                Log::info("[AgendarCita] Horarios filtrados por cita: {$horariosAntes} -> {$horariosDespues}");
            }

            // Convertir los horarios a formato 12 horas para mostrar en la vista
            $horariosFormateados = [];

            foreach ($horariosDisponibles as $hora) {
                $carbon = Carbon::createFromFormat('H:i:s', $hora);
                $horariosFormateados[] = $carbon->format('h:i A');
            }

            $this->horariosDisponibles = $horariosFormateados;

            Log::info("[AgendarCita] Horarios disponibles finales: " . json_encode($horariosFormateados));

            // Si la hora seleccionada no está disponible, limpiarla
            if (!empty($this->horaSeleccionada) && !in_array($this->horaSeleccionada, $horariosFormateados)) {
                Log::info("[AgendarCita] Hora seleccionada '{$this->horaSeleccionada}' ya no está disponible, limpiando selección");
                $this->horaSeleccionada = '';
            }
        } catch (\Exception $e) {
            Log::error("[AgendarCita] Error al cargar horarios disponibles: " . $e->getMessage());
            $this->horariosDisponibles = [];
        }
    }

    /**
     * Obtiene los horarios base disponibles según el horario del local seleccionado
     */
    private function obtenerHorariosBase(): array
    {
        $horarios = [];

        // Si no hay local seleccionado, usar horarios predeterminados
        if (empty($this->localSeleccionado) || empty($this->locales[$this->localSeleccionado])) {
            Log::warning("[AgendarCita] No hay local seleccionado para obtener horarios base, usando predeterminados");

            // Horarios predeterminados de 8:00 AM a 5:00 PM, cada 30 minutos
            for ($hora = 8; $hora <= 17; $hora++) {
                $horarios[] = sprintf('%02d:00:00', $hora);

                // No agregar los 30 minutos para las 5:00 PM
                if ($hora < 17) {
                    $horarios[] = sprintf('%02d:30:00', $hora);
                }
            }

            return $horarios;
        }

        // Obtener los horarios del local seleccionado
        $local = $this->locales[$this->localSeleccionado];
        $horaApertura = $local['horario_apertura'];
        $horaCierre = $local['horario_cierre'];

        Log::info("[AgendarCita] Horarios del local seleccionado: {$horaApertura} - {$horaCierre}");

        // Convertir a objetos Carbon para facilitar la manipulación
        try {
            $apertura = Carbon::createFromFormat('H:i:s', $horaApertura);
            $cierre = Carbon::createFromFormat('H:i:s', $horaCierre);

            // Asegurarse de que la hora de cierre sea posterior a la de apertura
            if ($apertura->gt($cierre)) {
                Log::warning("[AgendarCita] Hora de apertura posterior a la de cierre, usando horarios predeterminados");
                return $this->obtenerHorariosBaseDefault();
            }

            // Obtener la hora de apertura y cierre en formato de hora
            $horaAperturaInt = (int)$apertura->format('H');
            $horaCierreInt = (int)$cierre->format('H');

            // Generar horarios cada 30 minutos desde la apertura hasta el cierre
            for ($hora = $horaAperturaInt; $hora <= $horaCierreInt; $hora++) {
                // Agregar la hora en punto
                $horarios[] = sprintf('%02d:00:00', $hora);

                // Agregar la media hora, excepto para la última hora
                if ($hora < $horaCierreInt) {
                    $horarios[] = sprintf('%02d:30:00', $hora);
                }
            }

            Log::info("[AgendarCita] Horarios base generados: " . count($horarios));

            return $horarios;
        } catch (\Exception $e) {
            Log::error("[AgendarCita] Error al procesar horarios del local: " . $e->getMessage());
            return $this->obtenerHorariosBaseDefault();
        }
    }

    /**
     * Obtiene los horarios base predeterminados (8:00 AM a 5:00 PM, cada 30 minutos)
     */
    private function obtenerHorariosBaseDefault(): array
    {
        $horarios = [];

        // Horarios predeterminados de 8:00 AM a 5:00 PM, cada 30 minutos
        for ($hora = 8; $hora <= 17; $hora++) {
            $horarios[] = sprintf('%02d:00:00', $hora);

            // No agregar los 30 minutos para las 5:00 PM
            if ($hora < 17) {
                $horarios[] = sprintf('%02d:30:00', $hora);
            }
        }

        return $horarios;
    }

    /**
     * Cambia el mes del calendario
     */
    public function cambiarMes(int $cambio): void
    {
        // Crear una fecha con el mes y año actual
        $fecha = Carbon::createFromDate($this->anoActual, $this->mesActual, 1);

        // Sumar o restar meses según el cambio
        $nuevaFecha = $cambio > 0 ? $fecha->addMonths(abs($cambio)) : $fecha->subMonths(abs($cambio));

        // Actualizar el mes y año actual
        $this->mesActual = $nuevaFecha->month;
        $this->anoActual = $nuevaFecha->year;

        // Regenerar el calendario
        $this->generarCalendario();
    }

    /**
     * Cambia el año del calendario
     */
    public function cambiarAno(int $cambio): void
    {
        // Sumar o restar años según el cambio
        $this->anoActual += $cambio;

        // Regenerar el calendario
        $this->generarCalendario();
    }

    /**
     * Selecciona una fecha y carga los horarios disponibles
     */
    public function seleccionarFecha(string $fecha): void
    {
        // Verificar si la fecha es válida
        try {
            $fechaCarbon = Carbon::createFromFormat('d/m/Y', $fecha);

            // Verificar si la fecha es pasada
            if ($fechaCarbon->lt(Carbon::today())) {
                Log::warning("[AgendarCita] Intento de seleccionar fecha pasada: {$fecha}");
                // No permitir seleccionar fechas pasadas
                return;
            }

            // Si la fecha ya está seleccionada, deseleccionarla
            if ($this->fechaSeleccionada === $fecha) {
                Log::info("[AgendarCita] Deseleccionando fecha: {$fecha}");
                $this->fechaSeleccionada = '';
                $this->horariosDisponibles = [];
                $this->horaSeleccionada = '';
                return;
            }

            // Actualizar la fecha seleccionada
            $this->fechaSeleccionada = $fecha;
            Log::info("[AgendarCita] Fecha seleccionada: {$this->fechaSeleccionada}");

            // Regenerar el calendario para actualizar la visualización
            $this->generarCalendario();

            // Cargar los horarios disponibles para esta fecha
            $this->cargarHorariosDisponibles();

            // Limpiar la hora seleccionada
            $this->horaSeleccionada = '';
        } catch (\Exception $e) {
            Log::error("[AgendarCita] Error al seleccionar fecha: " . $e->getMessage());
        }
    }

    /**
     * Selecciona una hora
     */
    public function seleccionarHora(string $hora): void
    {
        Log::info("[AgendarCita] Intentando seleccionar hora: {$hora}");
        Log::info("[AgendarCita] Horarios disponibles: " . json_encode($this->horariosDisponibles));

        // Verificar si la hora está disponible
        if (in_array($hora, $this->horariosDisponibles)) {
            // Si ya está seleccionada, deseleccionarla
            if ($this->horaSeleccionada === $hora) {
                Log::info("[AgendarCita] Deseleccionando hora: {$hora}");
                $this->horaSeleccionada = '';
            } else {
                // Actualizar la hora seleccionada
                $this->horaSeleccionada = $hora;
                Log::info("[AgendarCita] Hora seleccionada: {$this->horaSeleccionada}");
            }
        } else {
            Log::warning("[AgendarCita] Intento de seleccionar hora no disponible: {$hora}");
            // Notificar al usuario
            $this->notify('error', 'La hora seleccionada no está disponible');
        }
    }

    /**
     * Muestra una notificación al usuario
     */
    protected function notify(string $type, string $message): void
    {
        $this->dispatch('notify', [
            'type' => $type,
            'message' => $message,
        ]);
    }

    /**
     * Actualiza los horarios cuando cambia el local seleccionado
     */
    public function updatedLocalSeleccionado($value): void
    {
        Log::info("[AgendarCita] Local seleccionado cambiado a: {$value}");

        // Limpiar la hora seleccionada
        $this->horaSeleccionada = '';

        // Limpiar los horarios disponibles para forzar su recarga
        $this->horariosDisponibles = [];

        // Regenerar el calendario para actualizar la disponibilidad según el local seleccionado
        $this->generarCalendario();

        // Si hay una fecha seleccionada, cargar los horarios disponibles para el nuevo local
        if (!empty($this->fechaSeleccionada)) {
            Log::info("[AgendarCita] Recargando horarios para fecha: {$this->fechaSeleccionada} y local: {$value}");
            $this->cargarHorariosDisponibles();
        }

        // Recargar las campañas
        $this->cargarCampanas();
        Log::info("[AgendarCita] Campañas recargadas después de cambiar el local a: {$value}. Total: " . count($this->campanasDisponibles));

        // Forzar la actualización de la vista
        $this->dispatch('horarios-actualizados');
    }

    /**
     * Obtiene el nombre del mes actual
     */
    public function getNombreMesActualProperty(): string
    {
        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return $meses[$this->mesActual] ?? '';
    }


}
