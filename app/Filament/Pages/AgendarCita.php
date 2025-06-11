<?php

namespace App\Filament\Pages;

use App\Models\AdditionalService;
use App\Models\Appointment;
use App\Models\Bloqueo;
use App\Models\Campana;
use App\Models\Local;
use App\Models\MaintenanceType;
use App\Models\Vehicle;
use App\Services\VehiculoSoapService;
use App\Services\C4C\AppointmentService;
use App\Jobs\EnviarCitaC4CJob;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AgendarCita extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationLabel = 'Agendar Cita';
    
    protected static ?string $navigationGroup = '📅 Citas & Servicios';
    
    protected static ?int $navigationSort = 1;

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

    // Datos del formulario (se cargarán automáticamente desde el usuario autenticado)
    public string $nombreCliente = '';

    public string $emailCliente = '';

    public string $apellidoCliente = '';

    public string $celularCliente = '';

    // Datos de la cita
    public string $fechaSeleccionada = '';

    public string $horaSeleccionada = '';

    public string $localSeleccionado = '';

    public array $serviciosSeleccionados = [];

    public string $tipoMantenimiento = '';

    public string $modalidadServicio = '';

    // Datos del calendario
    public int $mesActual;

    public int $anoActual;

    public array $diasCalendario = [];

    public array $horariosDisponibles = [];

    // Servicios adicionales
    public array $serviciosAdicionales = [];

    // Comentarios
    public string $comentarios = '';

    // Pasos del formulario
    public int $pasoActual = 1;

    public int $totalPasos = 3;

    public bool $citaAgendada = false;

    // Estados del job de C4C
    public ?string $citaJobId = null;
    
    public string $citaStatus = 'idle'; // idle, processing, completed, failed
    
    public int $citaProgress = 0;
    
    public string $citaMessage = '';
    
    public ?string $appointmentNumber = null;

    // Modales de pop-ups
    public bool $mostrarModalPopups = false;

    public bool $mostrarModalResumenPopups = false;

    public array $popupsSeleccionados = [];

    public array $popupsDisponibles = [];

    // Locales disponibles
    public array $locales = [];

    // Horarios disponibles
    public array $horarios = [];

    // Servicios adicionales disponibles
    public array $opcionesServiciosAdicionales = [];

    // Campañas disponibles
    public array $campanasDisponibles = [];

    // Modalidades disponibles
    public array $modalidadesDisponibles = [];

    // Nuevas propiedades para los maestros
    public array $tiposMantenimientoDisponibles = [];

    public array $serviciosAdicionalesDisponibles = [];

    public string $servicioAdicionalSeleccionado = '';

    /**
     * Carga los pop-ups disponibles desde la base de datos
     */
    protected function cargarPopups(): void
    {
        try {
            Log::info('[AgendarCita] Iniciando carga de pop-ups');

            // Obtener los pop-ups activos
            $popups = \App\Models\PopUp::where('is_active', true)->get();

            Log::info('[AgendarCita] Pop-ups activos encontrados: ' . $popups->count());

            if ($popups->isNotEmpty()) {
                $this->popupsDisponibles = [];

                foreach ($popups as $popup) {
                    $imagenUrl = $popup->image_path;

                    // Si la imagen es una ruta relativa, convertirla a URL completa
                    if (! filter_var($imagenUrl, FILTER_VALIDATE_URL)) {
                        $imagenUrl = asset('storage/'.$imagenUrl);
                    }

                    $this->popupsDisponibles[] = [
                        'id' => $popup->id,
                        'nombre' => $popup->name,
                        'imagen' => $imagenUrl,
                        'url_wp' => $popup->url_wp,
                    ];

                    Log::info("[AgendarCita] Pop-up cargado: ID {$popup->id}, Nombre: {$popup->name}");
                }

                Log::info('[AgendarCita] Total pop-ups disponibles: ' . count($this->popupsDisponibles));
            } else {
                // Si no hay pop-ups en la base de datos, crear algunos pop-ups de ejemplo
                $this->popupsDisponibles = [];
                Log::info('[AgendarCita] No hay pop-ups activos en la base de datos');
            }
        } catch (\Exception $e) {
            Log::error('[AgendarCita] Error al cargar pop-ups: '.$e->getMessage());
            $this->popupsDisponibles = [];
        }
    }

    /**
     * Cargar datos del cliente autenticado automáticamente
     */
    protected function cargarDatosCliente(): void
    {
        try {
            $user = Auth::user();
            
            if ($user) {
                // Dividir el nombre completo en nombres y apellidos
                $nombreCompleto = $user->name ?? '';
                $partesNombre = explode(' ', trim($nombreCompleto));
                
                // Si hay al menos una palabra, la primera va a nombres
                if (count($partesNombre) >= 1) {
                    $this->nombreCliente = $partesNombre[0];
                }
                
                // Si hay más de una palabra, el resto va a apellidos
                if (count($partesNombre) > 1) {
                    $this->apellidoCliente = implode(' ', array_slice($partesNombre, 1));
                }
                
                // Asignar email y teléfono
                $this->emailCliente = $user->email ?? '';
                $this->celularCliente = $user->phone ?? '';
                
                Log::info('[AgendarCita] Datos del cliente cargados automáticamente:', [
                    'nombre' => $this->nombreCliente,
                    'apellido' => $this->apellidoCliente,
                    'email' => $this->emailCliente,
                    'celular' => $this->celularCliente,
                    'user_id' => $user->id,
                    'document_type' => $user->document_type,
                    'document_number' => $user->document_number,
                ]);
            } else {
                Log::warning('[AgendarCita] No hay usuario autenticado. Manteniendo campos vacíos.');
            }
        } catch (\Exception $e) {
            Log::error('[AgendarCita] Error al cargar datos del cliente: ' . $e->getMessage());
            // Los campos quedarán vacíos en caso de error
        }
    }

    public function mount($vehiculoId = null): void
    {
        // Registrar todos los parámetros recibidos para depuración
        Log::info('[AgendarCita] Parámetros recibidos en mount:', [
            'vehiculoId' => $vehiculoId,
            'request_all' => request()->all(),
            'query_string' => request()->getQueryString(),
        ]);

        // CARGAR DATOS DEL CLIENTE AUTENTICADO AUTOMÁTICAMENTE
        $this->cargarDatosCliente();

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

                Log::info('[AgendarCita] Vehículo encontrado en la base de datos:', $this->vehiculo);
            } else {
                // Si no encontramos el vehículo en la base de datos, intentamos buscarlo en el servicio SOAP
                try {
                    $service = app(VehiculoSoapService::class);
                    
                    // Obtener documento del usuario autenticado
                    $user = Auth::user();
                    $documentoCliente = $user ? $user->document_number : null;
                    
                    if (!$documentoCliente) {
                        Log::warning('[AgendarCita] No se encontró documento del usuario autenticado, usando documento por defecto');
                        $documentoCliente = '20605414410'; // Fallback
                    }
                    
                    Log::info("[AgendarCita] Consultando vehículos con documento: {$documentoCliente}");
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

                        Log::info('[AgendarCita] Vehículo encontrado en el servicio SOAP:', $this->vehiculo);
                    } else {
                        // Si no encontramos el vehículo en ninguna parte, mantenemos los valores predeterminados
                        Log::warning("[AgendarCita] No se encontró el vehículo con ID: {$vehiculoId}. Manteniendo valores predeterminados.");
                        Log::info('[AgendarCita] Valores predeterminados mantenidos:', $this->vehiculo);
                    }
                } catch (\Exception $e) {
                    // En caso de error, mantener los valores predeterminados
                    Log::error('[AgendarCita] Error al cargar datos del vehículo desde el servicio SOAP: '.$e->getMessage());
                    Log::info('[AgendarCita] Valores predeterminados mantenidos en caso de error:', $this->vehiculo);
                }
            }
        } else {
            // Si no se proporcionó un ID de vehículo, mantener los valores predeterminados
            Log::warning('[AgendarCita] No se proporcionó ID de vehículo. Manteniendo valores predeterminados.');
        }

        // Cargar los locales desde la base de datos
        $this->cargarLocales();

        // Cargar los servicios adicionales desde la base de datos
        $this->cargarServiciosAdicionales();

        // Cargar las modalidades disponibles
        $this->cargarModalidadesDisponibles();

        // Cargar los tipos de mantenimiento disponibles
        $this->cargarTiposMantenimiento();

        // Cargar los servicios adicionales disponibles
        $this->cargarServiciosAdicionalesDisponibles();

        // Cargar los pop-ups disponibles
        $this->cargarPopups();

        // Inicializar el calendario con el mes y año actual
        $fechaActual = Carbon::now();
        $this->mesActual = $fechaActual->month;
        $this->anoActual = $fechaActual->year;

        // Generar el calendario para el mes actual
        $this->generarCalendario();

        // Verificar el estado final de la variable vehiculo
        Log::info('[AgendarCita] Estado final de la variable vehiculo:', $this->vehiculo ?? ['vehiculo' => 'null']);
    }

    /**
     * Fuerza la recarga del calendario
     */
    public function recargarCalendario(): void
    {
        $this->generarCalendario();
    }

    /**
     * Alterna la selección de un servicio
     */
    public function toggleServicio(string $servicio): void
    {
        if (in_array($servicio, $this->serviciosSeleccionados)) {
            // Remover el servicio
            $this->serviciosSeleccionados = array_filter($this->serviciosSeleccionados, function($s) use ($servicio) {
                return $s !== $servicio;
            });

            // Limpiar campos relacionados si se deselecciona
            if ($servicio === 'Mantenimiento periódico') {
                $this->tipoMantenimiento = '';
                $this->modalidadServicio = '';
            }
        } else {
            // Agregar el servicio
            $this->serviciosSeleccionados[] = $servicio;
        }

        Log::info("[AgendarCita] Servicios seleccionados actualizados: " . json_encode($this->serviciosSeleccionados));
    }

    /**
     * Verifica si un servicio está seleccionado
     */
    public function isServicioSeleccionado(string $servicio): bool
    {
        return in_array($servicio, $this->serviciosSeleccionados);
    }

    /**
     * Cargar los locales desde la tabla de locales
     */
    protected function cargarLocales(): void
    {
        // Obtener los locales activos de la tabla locales
        $localesActivos = \App\Models\Local::where('is_active', true)->get();

        Log::info('[AgendarCita] Consultando locales activos. Total encontrados: '.$localesActivos->count());

        // Verificar si hay locales en la base de datos
        $todosLosLocales = \App\Models\Local::all();
        Log::info('[AgendarCita] Total de locales en la base de datos: '.$todosLosLocales->count());

        foreach ($todosLosLocales as $index => $local) {
            Log::info("[AgendarCita] Local #{$index} en DB: ID: {$local->id}, Código: {$local->code}, Nombre: {$local->name}, Activo: ".($local->is_active ? 'Sí' : 'No'));
        }

        if ($localesActivos->isNotEmpty()) {
            $this->locales = [];

            foreach ($localesActivos as $local) {
                $key = $local->code;
                $this->locales[$key] = [
                    'nombre' => $local->name,
                    'direccion' => $local->address,
                    'telefono' => $local->phone,
                    'opening_time' => $local->opening_time ?: '08:00:00',
                    'closing_time' => $local->closing_time ?: '17:00:00',
                    'waze_url' => $local->waze_url,
                    'maps_url' => $local->maps_url,
                    'id' => $local->id,
                ];

                Log::info("[AgendarCita] Local cargado: Código: {$key}, ID: {$local->id}, Nombre: {$local->name}, Horario: {$local->opening_time} - {$local->closing_time}");
            }

            Log::info('[AgendarCita] Locales cargados: '.count($this->locales));

            // Establecer el primer local como seleccionado por defecto
            if (empty($this->localSeleccionado) && ! empty($this->locales)) {
                $this->localSeleccionado = array_key_first($this->locales);
                Log::info("[AgendarCita] Local seleccionado por defecto: {$this->localSeleccionado}, ID: {$this->locales[$this->localSeleccionado]['id']}");

                // Regenerar el calendario para actualizar la disponibilidad según el local seleccionado
                if (isset($this->mesActual) && isset($this->anoActual)) {
                    $this->generarCalendario();
                }
            }
        } else {
            Log::warning('[AgendarCita] No se encontraron locales activos en la base de datos');

            // Si no hay locales en la base de datos, crear algunos locales de prueba
            $this->locales = [];
            // Establecer el primer local como seleccionado por defecto
            if (empty($this->localSeleccionado)) {
                $this->localSeleccionado = array_key_first($this->locales);
            }
        }
    }

    /**
     * Cargar los servicios adicionales desde la base de datos
     */
    protected function cargarServiciosAdicionales(): void
    {
        // Inicializar opciones de servicios adicionales
        $this->opcionesServiciosAdicionales = [];

        // Cargar servicios adicionales del maestro
        try {
            $serviciosAdicionales = AdditionalService::where('is_active', true)->get();
            foreach ($serviciosAdicionales as $servicio) {
                $this->opcionesServiciosAdicionales['servicio_' . $servicio->id] = $servicio->name;
            }
            Log::info('[AgendarCita] Servicios adicionales del maestro cargados: ' . count($serviciosAdicionales));
        } catch (\Exception $e) {
            Log::error('[AgendarCita] Error al cargar servicios adicionales del maestro: ' . $e->getMessage());
        }

        // Cargar campañas activas
        $this->cargarCampanas();
    }

    /**
     * Cargar las modalidades disponibles según el vehículo y local
     */
    protected function cargarModalidadesDisponibles(): void
    {
        try {
            // Modalidad Regular siempre está disponible
            $this->modalidadesDisponibles = [
                'Regular' => 'Regular',
            ];

            // Verificar si Express está disponible para este vehículo y local
            $expressDisponible = $this->esExpressDisponible();

            if ($expressDisponible) {
                $this->modalidadesDisponibles['Express (Duración 1h-30 min)'] = 'Express (Duración 1h 30 min)';
            }
        } catch (\Exception $e) {
            // En caso de error, solo mostrar Regular
            $this->modalidadesDisponibles = [
                'Regular' => 'Regular',
            ];
        }
    }

    /**
     * Cargar los tipos de mantenimiento disponibles desde la base de datos
     */
    protected function cargarTiposMantenimiento(): void
    {
        try {
            $this->tiposMantenimientoDisponibles = MaintenanceType::getActivosParaSelector();
            Log::info('[AgendarCita] Tipos de mantenimiento cargados: '.count($this->tiposMantenimientoDisponibles));
        } catch (\Exception $e) {
            Log::error('[AgendarCita] Error al cargar tipos de mantenimiento: '.$e->getMessage());
            $this->tiposMantenimientoDisponibles = [];
        }
    }

    /**
     * Cargar los servicios adicionales disponibles desde la base de datos
     */
    protected function cargarServiciosAdicionalesDisponibles(): void
    {
        try {
            $this->serviciosAdicionalesDisponibles = AdditionalService::getActivosParaSelector();
            Log::info('[AgendarCita] Servicios adicionales cargados: '.count($this->serviciosAdicionalesDisponibles));
        } catch (\Exception $e) {
            Log::error('[AgendarCita] Error al cargar servicios adicionales: '.$e->getMessage());
            $this->serviciosAdicionalesDisponibles = [];
        }
    }

    /**
     * Método que se ejecuta cuando se actualiza la selección del servicio adicional
     */
    public function updatedServicioAdicionalSeleccionado($value): void
    {
        if (!empty($value) && !in_array($value, $this->serviciosAdicionales)) {
            // Agregar el servicio seleccionado a la lista de servicios adicionales
            $this->serviciosAdicionales[] = $value;
            Log::info("[AgendarCita] Servicio adicional agregado: {$value}");

            // Limpiar la selección para permitir agregar más servicios
            $this->servicioAdicionalSeleccionado = '';
        }
    }

    /**
     * Eliminar un servicio adicional de la lista
     */
    public function eliminarServicioAdicional($servicio): void
    {
        $this->serviciosAdicionales = array_values(array_filter($this->serviciosAdicionales, function($item) use ($servicio) {
            return $item !== $servicio;
        }));
        Log::info("[AgendarCita] Servicio adicional eliminado: {$servicio}");
    }

    /**
     * Verificar si la modalidad Express está disponible para el vehículo y local actual
     */
    protected function esExpressDisponible(): bool
    {
        try {
            // Si no hay vehículo, local o tipo de mantenimiento seleccionado, Express no está disponible
            if (empty($this->vehiculo) || empty($this->localSeleccionado) || empty($this->tipoMantenimiento)) {
                return false;
            }

            $modelo = $this->vehiculo['modelo'] ?? '';
            $marca = $this->vehiculo['marca'] ?? '';
            $anio = $this->vehiculo['anio'] ?? '';

            if (empty($modelo) || empty($marca) || empty($anio)) {
                return false;
            }

            // Obtener el nombre del local seleccionado
            $nombreLocal = '';
            try {
                $localObj = \App\Models\Local::where('code', $this->localSeleccionado)->first();
                $nombreLocal = $localObj ? $localObj->name : '';
            } catch (\Exception $e) {
                // Silenciar error
            }

            // Normalizar el tipo de mantenimiento para la búsqueda
            $formatosABuscar = [
                $this->tipoMantenimiento, // Formato original
            ];

            // Si contiene "Mantenimiento", agregar variaciones
            if (strpos($this->tipoMantenimiento, 'Mantenimiento') !== false) {
                $formato1 = str_replace('Mantenimiento ', '', $this->tipoMantenimiento);
                $formato2 = str_replace('000 Km', ',000 Km', $formato1);
                $formatosABuscar[] = $formato1;
                $formatosABuscar[] = $formato2;
            }

            // Buscar vehículos que coincidan con modelo, marca, año y local
            $vehiculosExpress = \App\Models\VehiculoExpress::where('is_active', true)
                ->where('model', 'like', "%{$modelo}%")
                ->where('brand', 'like', "%{$marca}%")
                ->where('year', $anio)
                ->where(function($query) use ($nombreLocal) {
                    $query->where('premises', $this->localSeleccionado)  // Buscar por código
                          ->orWhere('premises', $nombreLocal);            // Buscar por nombre
                })
                ->get();

            $vehiculoExpress = null;
            foreach ($vehiculosExpress as $vehiculo) {
                $mantenimientos = $vehiculo->mantenimiento;

                // Manejar diferentes formatos de mantenimiento
                if (is_string($mantenimientos)) {
                    // Si es un string JSON, decodificarlo
                    if (str_starts_with($mantenimientos, '[') || str_starts_with($mantenimientos, '{')) {
                        $decoded = json_decode($mantenimientos, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            $mantenimientos = $decoded;
                        } else {
                            $mantenimientos = [$mantenimientos];
                        }
                    } else {
                        $mantenimientos = [$mantenimientos];
                    }
                } elseif (! is_array($mantenimientos)) {
                    $mantenimientos = [$mantenimientos];
                }

                // Verificar si alguno de los formatos está en el array
                $coincidenciaEncontrada = false;
                if (is_array($mantenimientos)) {
                    foreach ($formatosABuscar as $formato) {
                        // Buscar coincidencia exacta o parcial
                        foreach ($mantenimientos as $mantenimientoVehiculo) {
                            if ($formato === $mantenimientoVehiculo ||
                                stripos($mantenimientoVehiculo, $formato) !== false ||
                                stripos($formato, $mantenimientoVehiculo) !== false) {
                                $vehiculoExpress = $vehiculo;
                                $coincidenciaEncontrada = true;
                                break 3; // Salir de todos los loops
                            }
                        }
                    }
                }
            }

            return $vehiculoExpress !== null;

        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Cargar las campañas activas desde la base de datos
     * Filtradas por modelo del vehículo y local seleccionado
     */
    protected function cargarCampanas(): void
    {
        try {
            Log::info('[AgendarCita] Iniciando carga de campañas filtradas');
            Log::info('[AgendarCita] Vehículo seleccionado: '.json_encode($this->vehiculo ?? []));
            Log::info('[AgendarCita] Local seleccionado: '.($this->localSeleccionado ?? 'ninguno'));

            // Verificar específicamente la campaña 10214 para depuración
            $this->verificarCampanaEspecifica('10214');

            // Obtener campañas activas con filtros inteligentes
            $query = Campana::where('status', 'active');

            // Filtrar por modelo del vehículo si está disponible
            if (! empty($this->vehiculo['modelo'])) {
                $modeloVehiculo = $this->vehiculo['modelo'];
                Log::info("[AgendarCita] Filtrando campañas por modelo: {$modeloVehiculo}");

                $query->whereExists(function ($q) use ($modeloVehiculo) {
                    $q->select(DB::raw(1))
                        ->from('campaign_models')
                        ->join('models', 'campaign_models.model_id', '=', 'models.id')
                        ->whereColumn('campaign_models.campaign_id', 'campaigns.id')
                        ->where('models.name', 'like', "%{$modeloVehiculo}%");
                });
            }

            // Filtrar por local si está seleccionado
            if (! empty($this->localSeleccionado)) {
                Log::info("[AgendarCita] Filtrando campañas por local: {$this->localSeleccionado}");

                $query->whereExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('campaign_premises')
                        ->whereColumn('campaign_premises.campaign_id', 'campaigns.id')
                        ->where('campaign_premises.premise_code', $this->localSeleccionado);
                });
            }

            // Filtrar por año del vehículo si está disponible
            if (! empty($this->vehiculo['anio']) && is_numeric($this->vehiculo['anio'])) {
                $anioVehiculo = (int) $this->vehiculo['anio'];
                Log::info("[AgendarCita] Filtrando campañas por año: {$anioVehiculo}");

                $query->whereExists(function ($q) use ($anioVehiculo) {
                    $q->select(DB::raw(1))
                        ->from('campaign_years')
                        ->whereColumn('campaign_years.campaign_id', 'campaigns.id')
                        ->where('campaign_years.year', $anioVehiculo);
                });
            }

            $campanas = $query->get();

            Log::info('[AgendarCita] Campañas filtradas encontradas: '.$campanas->count());

            // Verificar si hay campañas activas en la base de datos
            $todasLasCampanas = Campana::where('status', 'active')->get();
            Log::info('[AgendarCita] Total de campañas activas en la base de datos: '.$todasLasCampanas->count());

            // No reinicializar opcionesServiciosAdicionales aquí para mantener los servicios del maestro
            // $this->opcionesServiciosAdicionales = [];

            foreach ($todasLasCampanas as $index => $campana) {
                Log::info("[AgendarCita] Campaña #{$index} en DB: ID: {$campana->id}, Título: {$campana->title}, Estado: {$campana->status}, Fecha inicio: {$campana->start_date}, Fecha fin: {$campana->end_date}");

                // Verificar si tiene imagen
                try {
                    $imagen = DB::table('campaign_images')->where('campaign_id', $campana->id)->first();
                    $tieneImagen = $imagen ? "Sí (ID: {$imagen->id}, Ruta: {$imagen->route})" : 'No';
                    Log::info("[AgendarCita] Campaña #{$index} tiene imagen: {$tieneImagen}");
                } catch (\Exception $e) {
                    Log::error("[AgendarCita] Error al verificar imagen de campaña #{$index}: ".$e->getMessage());
                }

                // Verificar modelos asociados
                try {
                    $modelos = DB::table('campaign_models')
                        ->join('models', 'campaign_models.model_id', '=', 'models.id')
                        ->where('campaign_models.campaign_id', $campana->id)
                        ->pluck('models.name')
                        ->toArray();
                    Log::info("[AgendarCita] Campaña #{$index} modelos: ".(empty($modelos) ? 'Ninguno' : implode(', ', $modelos)));
                } catch (\Exception $e) {
                    Log::error("[AgendarCita] Error al verificar modelos de campaña #{$index}: ".$e->getMessage());
                }

                // Verificar años asociados
                try {
                    $anos = DB::table('campaign_years')
                        ->where('campaign_id', $campana->id)
                        ->pluck('year')
                        ->toArray();
                    Log::info("[AgendarCita] Campaña #{$index} años: ".(empty($anos) ? 'Ninguno' : implode(', ', $anos)));
                } catch (\Exception $e) {
                    Log::error("[AgendarCita] Error al verificar años de campaña #{$index}: ".$e->getMessage());
                }

                // Verificar locales asociados
                try {
                    $locales = DB::table('campaign_premises')
                        ->join('premises', 'campaign_premises.premise_code', '=', 'premises.code')
                        ->where('campaign_premises.campaign_id', $campana->id)
                        ->pluck('premises.name')
                        ->toArray();
                    Log::info("[AgendarCita] Campaña #{$index} locales: ".(empty($locales) ? 'Ninguno' : implode(', ', $locales)));
                } catch (\Exception $e) {
                    Log::error("[AgendarCita] Error al verificar locales de campaña #{$index}: ".$e->getMessage());
                }
            }

            if ($campanas->isNotEmpty()) {
                $this->campanasDisponibles = [];

                foreach ($campanas as $campana) {
                    // Verificar que la campaña esté activa
                    if ($campana->status !== 'active') {
                        Log::info("[AgendarCita] Campaña {$campana->id} no está activa, omitiendo");

                        continue;
                    }

                    // Obtener la imagen de la campaña desde la tabla campaign_images
                    $imagenObj = DB::table('campaign_images')->where('campaign_id', $campana->id)->first();

                    // Construir la URL correcta para la imagen
                    if ($imagenObj && $imagenObj->route) {
                        try {
                            // Intentar diferentes enfoques para obtener la URL de la imagen
                            $rutaCompleta = $imagenObj->route;
                            Log::info("[AgendarCita] Ruta completa de la imagen: {$rutaCompleta}");

                            // Método 1: Usar route('imagen.campana', ['id' => $campana->id])
                            $imagen = route('imagen.campana', ['id' => $campana->id]);
                            Log::info("[AgendarCita] URL de imagen generada con route: {$imagen}");

                            // Registrar información detallada para depuración
                            Log::info("[AgendarCita] Campaña {$campana->id} tiene imagen: {$rutaCompleta}, URL generada: {$imagen}");
                        } catch (\Exception $e) {
                            // Si hay algún error, usar una imagen por defecto
                            $imagen = asset('images/default-campaign.jpg');
                            Log::error('[AgendarCita] Error al generar URL de imagen: '.$e->getMessage());
                        }
                    } else {
                        // Si no hay imagen, usar una imagen por defecto
                        $imagen = asset('images/default-campaign.jpg');
                        Log::info("[AgendarCita] Campaña {$campana->id} no tiene imagen, usando imagen por defecto");
                    }

                    $this->campanasDisponibles[] = [
                        'id' => $campana->id,
                        'titulo' => $campana->title,
                        'descripcion' => $campana->title, // Usamos el título como descripción ya que no hay campo de descripción
                        'imagen' => $imagen,
                        'fecha_inicio' => $campana->start_date,
                        'fecha_fin' => $campana->end_date,
                    ];

                    // Agregar al array de opciones de servicios adicionales para mostrar en el resumen
                    $this->opcionesServiciosAdicionales['campana_'.$campana->id] = $campana->title;

                    Log::info("[AgendarCita] Campaña cargada: ID: {$campana->id}, Título: {$campana->title}, Imagen: {$imagen}");
                }

                Log::info('[AgendarCita] Campañas disponibles cargadas: '.count($this->campanasDisponibles));
            } else {
                Log::info('[AgendarCita] No se encontraron campañas activas');

                // Si no hay campañas en la base de datos, crear algunas campañas de prueba
                $this->campanasDisponibles = [];

                // Agregar al array de opciones de servicios adicionales para mostrar en el resumen
                foreach ($this->campanasDisponibles as $campana) {
                    $this->opcionesServiciosAdicionales['campana_'.$campana['id']] = $campana['titulo'];
                }

                Log::info('[AgendarCita] Creadas campañas de prueba: '.count($this->campanasDisponibles));
            }
        } catch (\Exception $e) {
            Log::error('[AgendarCita] Error al cargar campañas: '.$e->getMessage());

            // En caso de error, crear algunas campañas de prueba
            $this->campanasDisponibles = [];

            // Agregar al array de opciones de servicios adicionales para mostrar en el resumen
            foreach ($this->campanasDisponibles as $campana) {
                $this->opcionesServiciosAdicionales['campana_'.$campana['id']] = $campana['titulo'];
            }

            Log::info('[AgendarCita] Creadas campañas de prueba por error: '.count($this->campanasDisponibles));
        }
    }

    /**
     * Verificar específicamente una campaña para depuración
     */
    protected function verificarCampanaEspecifica($codigoCampana): void
    {
        try {
            Log::info("[AgendarCita] === VERIFICACIÓN ESPECÍFICA DE CAMPAÑA {$codigoCampana} ===");

            // Buscar la campaña por ID (ya no hay código)
            $campana = Campana::where('id', $codigoCampana)->first();

            if (! $campana) {
                Log::warning("[AgendarCita] Campaña {$codigoCampana} no encontrada en la base de datos");

                return;
            }

            Log::info("[AgendarCita] Campaña encontrada: ID {$campana->id}, Título: {$campana->title}, Estado: {$campana->status}");

            // Verificar modelos asociados
            $modelosAsociados = DB::table('campaign_models')
                ->join('models', 'campaign_models.model_id', '=', 'models.id')
                ->where('campaign_models.campaign_id', $campana->id)
                ->select('models.name', 'models.code')
                ->get();

            Log::info("[AgendarCita] Modelos asociados a campaña {$codigoCampana}:");
            foreach ($modelosAsociados as $modelo) {
                Log::info("[AgendarCita]   - {$modelo->name} (código: {$modelo->code})");
            }

            // Verificar años asociados
            $anosAsociados = DB::table('campaign_years')
                ->where('campaign_id', $campana->id)
                ->pluck('year')
                ->toArray();

            Log::info("[AgendarCita] Años asociados a campaña {$codigoCampana}: ".implode(', ', $anosAsociados));

            // Verificar locales asociados
            $localesAsociados = DB::table('campaign_premises')
                ->join('premises', 'campaign_premises.premise_code', '=', 'premises.code')
                ->where('campaign_premises.campaign_id', $campana->id)
                ->select('premises.name', 'premises.code')
                ->get();

            Log::info("[AgendarCita] Locales asociados a campaña {$codigoCampana}:");
            foreach ($localesAsociados as $local) {
                Log::info("[AgendarCita]   - {$local->name} (código: {$local->code})");
            }

            // Verificar coincidencias con el vehículo actual
            $modeloVehiculo = $this->vehiculo['modelo'] ?? '';
            $anioVehiculo = $this->vehiculo['anio'] ?? '';
            $localSeleccionado = $this->localSeleccionado ?? '';

            Log::info('[AgendarCita] Comparación con vehículo actual:');
            Log::info("[AgendarCita]   Modelo vehículo: '{$modeloVehiculo}'");
            Log::info("[AgendarCita]   Año vehículo: '{$anioVehiculo}'");
            Log::info("[AgendarCita]   Local seleccionado: '{$localSeleccionado}'");

            // Verificar coincidencia de modelo
            $modeloCoincide = false;
            foreach ($modelosAsociados as $modelo) {
                if (stripos($modelo->name, $modeloVehiculo) !== false ||
                    stripos($modeloVehiculo, $modelo->name) !== false) {
                    $modeloCoincide = true;
                    Log::info("[AgendarCita]   ✓ Modelo coincide: '{$modelo->name}' contiene '{$modeloVehiculo}'");
                    break;
                }
            }

            if (! $modeloCoincide) {
                Log::warning('[AgendarCita]   ✗ Modelo NO coincide');
            }

            // Verificar coincidencia de año
            $anioCoincide = in_array($anioVehiculo, $anosAsociados);
            if ($anioCoincide) {
                Log::info("[AgendarCita]   ✓ Año coincide: '{$anioVehiculo}'");
            } else {
                Log::warning("[AgendarCita]   ✗ Año NO coincide: '{$anioVehiculo}' no está en [".implode(', ', $anosAsociados).']');
            }

            // Verificar coincidencia de local
            $localCoincide = false;
            foreach ($localesAsociados as $local) {
                if ($local->code === $localSeleccionado) {
                    $localCoincide = true;
                    Log::info("[AgendarCita]   ✓ Local coincide: '{$local->code}'");
                    break;
                }
            }

            if (! $localCoincide) {
                Log::warning("[AgendarCita]   ✗ Local NO coincide: '{$localSeleccionado}' no está en la lista");
            }

            $aplicaCampana = $modeloCoincide && $anioCoincide && $localCoincide;
            Log::info("[AgendarCita] RESULTADO: Campaña {$codigoCampana} ".($aplicaCampana ? 'SÍ APLICA' : 'NO APLICA'));
            Log::info('[AgendarCita] === FIN VERIFICACIÓN ESPECÍFICA ===');

        } catch (\Exception $e) {
            Log::error("[AgendarCita] Error al verificar campaña específica {$codigoCampana}: ".$e->getMessage());
        }
    }

    /**
     * Verificar si una campaña aplica para el vehículo y local seleccionados
     */
    protected function verificarCampanaAplicaParaVehiculo($campana): bool
    {
        try {
            $aplicaCampana = true;
            $razonesExclusion = [];

            // Verificar modelo del vehículo
            if (! empty($this->vehiculo['modelo'])) {
                $modeloVehiculo = $this->vehiculo['modelo'];
                $modelosAsociados = DB::table('campaign_models')
                    ->join('models', 'campaign_models.model_id', '=', 'models.id')
                    ->where('campaign_models.campaign_id', $campana->id)
                    ->pluck('models.name')
                    ->toArray();

                if (! empty($modelosAsociados)) {
                    $modeloCoincide = false;
                    foreach ($modelosAsociados as $modeloAsociado) {
                        if (stripos($modeloAsociado, $modeloVehiculo) !== false ||
                            stripos($modeloVehiculo, $modeloAsociado) !== false) {
                            $modeloCoincide = true;
                            break;
                        }
                    }

                    if (! $modeloCoincide) {
                        $aplicaCampana = false;
                        $razonesExclusion[] = "Modelo '{$modeloVehiculo}' no coincide con modelos de campaña: ".implode(', ', $modelosAsociados);
                    }
                }
            }

            // Verificar local seleccionado
            if (! empty($this->localSeleccionado)) {
                $localesAsociados = DB::table('campaign_premises')
                    ->where('campaign_id', $campana->id)
                    ->pluck('premise_code')
                    ->toArray();

                if (! empty($localesAsociados) && ! in_array($this->localSeleccionado, $localesAsociados)) {
                    $aplicaCampana = false;
                    $razonesExclusion[] = "Local '{$this->localSeleccionado}' no está en la lista de locales de campaña: ".implode(', ', $localesAsociados);
                }
            }

            // Verificar año del vehículo
            if (! empty($this->vehiculo['anio']) && is_numeric($this->vehiculo['anio'])) {
                $anioVehiculo = (int) $this->vehiculo['anio'];
                $anosAsociados = DB::table('campaign_years')
                    ->where('campaign_id', $campana->id)
                    ->pluck('year')
                    ->toArray();

                if (! empty($anosAsociados) && ! in_array($anioVehiculo, $anosAsociados)) {
                    $aplicaCampana = false;
                    $razonesExclusion[] = "Año '{$anioVehiculo}' no está en la lista de años de campaña: ".implode(', ', $anosAsociados);
                }
            }

            if ($aplicaCampana) {
                Log::info("[AgendarCita] Campaña {$campana->id} ({$campana->title}) APLICA para el vehículo y local seleccionados");
            } else {
                Log::info("[AgendarCita] Campaña {$campana->id} ({$campana->title}) NO APLICA. Razones: ".implode('; ', $razonesExclusion));
            }

            return $aplicaCampana;
        } catch (\Exception $e) {
            Log::error('[AgendarCita] Error al verificar si campaña aplica: '.$e->getMessage());

            // En caso de error, mostrar la campaña
            return true;
        }
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

    /**
     * Verificar el estado del job de C4C (llamado por polling Ajax)
     */
    public function checkJobStatus(): void
    {
        if (!$this->citaJobId) {
            return;
        }

        $jobData = Cache::get("cita_job_{$this->citaJobId}");
        
        if ($jobData) {
            $newStatus = $jobData['status'] ?? 'idle';
            $newProgress = $jobData['progress'] ?? 0;
            $newMessage = $jobData['message'] ?? '';
            
            // Solo actualizar si hay cambios
            if ($newStatus !== $this->citaStatus || $newProgress !== $this->citaProgress) {
                $this->citaStatus = $newStatus;
                $this->citaProgress = $newProgress;
                $this->citaMessage = $newMessage;
                
                Log::info("[AgendarCita] Job status actualizado", [
                    'job_id' => $this->citaJobId,
                    'status' => $this->citaStatus,
                    'progress' => $this->citaProgress,
                    'message' => $this->citaMessage
                ]);
                
                // Si se completó exitosamente
                if ($this->citaStatus === 'completed') {
                    $this->appointmentNumber = $jobData['appointment_number'] ?? null;
                    $this->citaAgendada = true;
                    $this->pasoActual = 3; // Ir al paso de confirmación
                    
                    \Filament\Notifications\Notification::make()
                        ->title('¡Cita Confirmada!')
                        ->body('Tu cita ha sido agendada exitosamente.')
                        ->success()
                        ->send();
                        
                    // Detener el polling
                    $this->dispatch('stop-polling');
                }
                
                // Si falló
                elseif ($this->citaStatus === 'failed') {
                    \Filament\Notifications\Notification::make()
                        ->title('Error al Agendar Cita')
                        ->body($this->citaMessage)
                        ->danger()
                        ->send();
                        
                    // Detener el polling
                    $this->dispatch('stop-polling');
                    
                    // NO resetear estado automáticamente para que el usuario vea el error
                }
            }
        }
    }

    /**
     * Resetear el estado de la cita para intentar de nuevo
     */
    public function resetearEstadoCita(): void
    {
        $this->citaStatus = 'idle';
        $this->citaProgress = 0;
        $this->citaMessage = '';
        $this->citaJobId = null;
        $this->appointmentNumber = null;
        
        Log::info("[AgendarCita] Estado de cita reseteado para nuevo intento");
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

            if (! $vehicle) {
                \Filament\Notifications\Notification::make()
                    ->title('Error al Agendar Cita')
                    ->body('No se encontró el vehículo seleccionado.')
                    ->danger()
                    ->send();

                return;
            }

            // Obtener el local seleccionado
            $localSeleccionado = null;
            if (! empty($this->localSeleccionado)) {
                $localSeleccionado = Local::where('code', $this->localSeleccionado)->first();
            }

            if (! $localSeleccionado) {
                \Filament\Notifications\Notification::make()
                    ->title('Error al Agendar Cita')
                    ->body('No se encontró el local seleccionado.')
                    ->danger()
                    ->send();

                return;
            }

            Log::info("[AgendarCita] Local seleccionado para la cita: {$localSeleccionado->name} (ID: {$localSeleccionado->id})");

            // Convertir la fecha de formato DD/MM/YYYY a YYYY-MM-DD
            $fechaPartes = explode('/', $this->fechaSeleccionada);
            $fechaFormateada = $fechaPartes[2].'-'.$fechaPartes[1].'-'.$fechaPartes[0];

            // Convertir la hora de formato "11:15 AM" a formato "HH:MM:SS"
            $horaFormateada = date('H:i:s', strtotime($this->horaSeleccionada));

            // Obtener el usuario autenticado
            $user = Auth::user();

            // 🚀 **NUEVA IMPLEMENTACIÓN CON JOBS - SIN TIMEOUT** 🚀
            Log::info("[AgendarCita] Iniciando proceso asíncrono de cita...");
            
            // Generar ID único para el job
            $this->citaJobId = (string) Str::uuid();
            
            // **PASO 1: CREAR APPOINTMENT EN BD PRIMERO** 💾
            $appointment = new Appointment;
            $appointment->appointment_number = 'CITA-'.date('Ymd').'-'.strtoupper(Str::random(5));
            $appointment->vehicle_id = $vehicle->id;
            $appointment->premise_id = $localSeleccionado->id;
            $appointment->customer_ruc = $user ? $user->document_number : '20605414410';
            $appointment->customer_name = $this->nombreCliente;
            $appointment->customer_last_name = $this->apellidoCliente;
            $appointment->customer_email = $this->emailCliente;
            $appointment->customer_phone = $this->celularCliente;
            $appointment->appointment_date = $fechaFormateada;
            $appointment->appointment_time = $horaFormateada;

            // Determinar el service_mode basado en los servicios seleccionados
            $serviceModes = [];
            if (in_array('Mantenimiento periódico', $this->serviciosSeleccionados)) {
                $serviceModes[] = 'Mantenimiento periódico';
            }
            if (in_array('Campañas / otros', $this->serviciosSeleccionados)) {
                $serviceModes[] = 'Campañas / otros';
            }
            if (in_array('Reparación', $this->serviciosSeleccionados)) {
                $serviceModes[] = 'Reparación';
            }
            if (in_array('Llamado a revisión', $this->serviciosSeleccionados)) {
                $serviceModes[] = 'Llamado a revisión';
            }

            $appointment->service_mode = implode(', ', $serviceModes);
            $appointment->maintenance_type = $this->tipoMantenimiento;
            $appointment->comments = $this->comentarios;
            $appointment->status = 'pending'; // Pendiente hasta que C4C confirme
            $appointment->is_synced = false;
            
            $appointment->save();

            Log::info("[AgendarCita] Appointment creado en BD con ID: {$appointment->id}");

            // **PASO 2: PREPARAR DATOS PARA C4C** 📋
            $fechaHoraInicio = Carbon::createFromFormat('Y-m-d H:i:s', $fechaFormateada . ' ' . $horaFormateada);
            $fechaHoraFin = $fechaHoraInicio->copy()->addMinutes(45); // 45 minutos por defecto
            
            $citaData = [
                'customer_id' => $user->c4c_internal_id ?? '1270002726', // Cliente de prueba si no tiene C4C ID
                'employee_id' => '1740', // ID del asesor por defecto
                'start_date' => $fechaHoraInicio->format('Y-m-d H:i:s'),
                'end_date' => $fechaHoraFin->format('Y-m-d H:i:s'),
                'center_id' => $localSeleccionado->code,
                'vehicle_plate' => $vehicle->license_plate,
                'customer_name' => $this->nombreCliente . ' ' . $this->apellidoCliente,
                'notes' => $this->comentarios ?: 'Cita agendada desde la aplicación web',
                'express' => false,
            ];

            $appointmentData = [
                'appointment_number' => $appointment->appointment_number,
                'servicios_adicionales' => $this->serviciosAdicionales,
                'campanas_disponibles' => $this->campanasDisponibles ?? []
            ];

            // **PASO 3: INICIALIZAR JOB STATUS** ⏳
            Cache::put("cita_job_{$this->citaJobId}", [
                'status' => 'queued',
                'progress' => 0,
                'message' => 'Preparando envío a C4C...',
                'updated_at' => now()
            ], 600); // 10 minutos

            // **PASO 4: DESPACHAR JOB EN BACKGROUND** 🚀
            EnviarCitaC4CJob::dispatch($citaData, $appointmentData, $this->citaJobId, $appointment->id);

            // **PASO 5: ACTUALIZAR UI INMEDIATAMENTE** ⚡
            $this->citaStatus = 'processing';
            $this->citaProgress = 0;
            $this->citaMessage = 'Enviando cita a C4C...';
            
            Log::info("[AgendarCita] Job despachado exitosamente", [
                'job_id' => $this->citaJobId,
                'appointment_id' => $appointment->id
            ]);

            // **PASO 6: NOTIFICAR AL USUARIO** ✅
            \Filament\Notifications\Notification::make()
                ->title('Procesando Cita')
                ->body('Tu cita está siendo procesada. Por favor espera...')
                ->info()
                ->send();

            // **PASO 7: INICIAR POLLING** 🔄
            $this->dispatch('start-polling', jobId: $this->citaJobId);

            // **GUARDAR SERVICIOS ADICIONALES** (mantenemos esta lógica)
            if (! empty($this->serviciosAdicionales)) {
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
                            $nombreServicio = 'Campaña: '.$campanaEncontrada['titulo'];
                            // Generar un código único para el servicio adicional basado en el ID de la campaña
                            $codigoServicio = 'CAMP-'.str_pad($campanaId, 5, '0', STR_PAD_LEFT);

                            $additionalService = AdditionalService::firstOrCreate(
                                ['code' => $codigoServicio],
                                [
                                    'name' => $nombreServicio,
                                    'description' => $campanaEncontrada['descripcion'],
                                    'is_active' => true,
                                    'price' => 0, // Precio promocional
                                ]
                            );

                            // Adjuntar el servicio a la cita
                            $appointment->additionalServices()->attach($additionalService->id, [
                                'notes' => "Campaña ID: {$campanaId}, Válida hasta: ".$campanaEncontrada['fecha_fin'],
                            ]);

                            Log::info("[AgendarCita] Campaña adjuntada a la cita: {$nombreServicio}");
                        } else {
                            Log::warning("[AgendarCita] No se encontró la campaña con ID: {$campanaId}");
                        }
                    } else {
                        // Procesar servicios adicionales tradicionales
                        Log::info("[AgendarCita] Procesando servicio adicional tradicional: {$servicioAdicionalKey}");
                        // NOTA: Funcionalidad de servicios adicionales removida
                        Log::info("[AgendarCita] Servicio adicional registrado (sin BD): {$servicioAdicionalKey}");
                    }
                }
            }

        } catch (\Exception $e) {
            // Registrar el error
            Log::error('[AgendarCita] Error al iniciar proceso de cita: '.$e->getMessage());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al Procesar Cita')
                ->body('Ocurrió un error al procesar la cita: '.$e->getMessage())
                ->danger()
                ->send();

            // Resetear estado
            $this->citaStatus = 'idle';
            $this->citaProgress = 0;
            $this->citaJobId = null;
        }
    }

    // Método para finalizar el proceso de agendamiento
    public function finalizarAgendamiento(): void
    {
        // Verificar el estado de la variable vehiculo antes de avanzar
        Log::info('[AgendarCita] Estado de la variable vehiculo antes de avanzar al paso 2:', $this->vehiculo ?? ['vehiculo' => 'null']);

        // Verificar que se haya seleccionado un local
        if (empty($this->localSeleccionado)) {
            // Si no se ha seleccionado un local, seleccionar el primero por defecto
            if (! empty($this->locales)) {
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

            if (! empty($this->vehiculo['placa'])) {
                $vehiculoEncontrado = Vehicle::where('license_plate', $this->vehiculo['placa'])->first();
            }

            if (! $vehiculoEncontrado && ! empty($this->vehiculo['id'])) {
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

                Log::info('[AgendarCita] Vehículo actualizado desde la base de datos:', $this->vehiculo);
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

                Log::info('[AgendarCita] Vehículo actualizado con valores predeterminados:', $this->vehiculo);
            }
        }

        // Registrar los valores seleccionados
        Log::info('[AgendarCita] Valores seleccionados:', [
            'vehiculo' => $this->vehiculo,
            'localSeleccionado' => $this->localSeleccionado,
            'fechaSeleccionada' => $this->fechaSeleccionada,
            'horaSeleccionada' => $this->horaSeleccionada,
            'serviciosSeleccionados' => $this->serviciosSeleccionados,
            'tipoMantenimiento' => $this->tipoMantenimiento,
            'modalidadServicio' => $this->modalidadServicio,
            'serviciosAdicionales' => $this->serviciosAdicionales,
            'comentarios' => $this->comentarios,
        ]);

        // Avanzar al paso 2 (resumen)
        $this->pasoActual = 2;

        // Verificar el estado de la variable vehiculo después de avanzar
        Log::info('[AgendarCita] Estado de la variable vehiculo después de avanzar al paso 2:', $this->vehiculo ?? ['vehiculo' => 'null']);
    }

    // Método para cerrar y mostrar el modal de pop-ups
    public function cerrarYVolverACitas(): void
    {
        // Mostrar el modal de pop-ups si hay pop-ups disponibles
        if (! empty($this->popupsDisponibles)) {
            $this->mostrarModalPopups = true;
            Log::info('[AgendarCita] Mostrando modal de pop-ups con '.count($this->popupsDisponibles).' opciones');
        } else {
            Log::info('[AgendarCita] No hay pop-ups disponibles para mostrar');
            // Si no hay pop-ups disponibles, redirigir a la página de vehículos
            $this->redirect(Vehiculos::getUrl());
        }
    }

    // Método para cancelar desde el modal de éxito y volver a vehículos con pestañas
    public function cancelarYVolverAVehiculos(): void
    {
        Log::info('[AgendarCita] Cancelando desde modal de éxito y volviendo a vehículos con pestañas');

        // Resetear el estado de la cita
        $this->citaStatus = 'idle';
        $this->citaProgress = 0;
        $this->citaMessage = '';
        $this->citaJobId = null;
        $this->appointmentNumber = null;

        // Redirigir específicamente a la página de vehículos con pestañas
        $this->redirect(Vehiculos::getUrl());
    }

    // Método para continuar después del éxito (mostrar pop-ups o ir a vehículos)
    public function continuarDespuesDeExito(): void
    {
        Log::info('[AgendarCita] Continuando después del éxito del modal');

        // Mostrar el modal de pop-ups si hay pop-ups disponibles
        if (! empty($this->popupsDisponibles)) {
            $this->mostrarModalPopups = true;
            Log::info('[AgendarCita] Mostrando modal de pop-ups con '.count($this->popupsDisponibles).' opciones');
        } else {
            Log::info('[AgendarCita] No hay pop-ups disponibles para mostrar, redirigiendo a vehículos');
            // Si no hay pop-ups disponibles, redirigir a la página de vehículos con pestañas
            $this->redirect(Vehiculos::getUrl());
        }
    }

    /**
     * Método para seleccionar/deseleccionar un pop-up
     */
    public function togglePopup(int $popupId): void
    {
        // Verificar si el pop-up ya está seleccionado
        $index = array_search($popupId, $this->popupsSeleccionados);

        if ($index !== false) {
            // Si ya está seleccionado, quitarlo
            unset($this->popupsSeleccionados[$index]);
            $this->popupsSeleccionados = array_values($this->popupsSeleccionados); // Reindexar el array
            Log::info("[AgendarCita] Pop-up {$popupId} deseleccionado");
        } else {
            // Si no está seleccionado, agregarlo
            $this->popupsSeleccionados[] = $popupId;
            Log::info("[AgendarCita] Pop-up {$popupId} seleccionado");
        }
    }

    /**
     * Método para solicitar información sobre los pop-ups seleccionados
     */
    public function solicitarInformacion(): void
    {
        // Verificar si hay pop-ups seleccionados
        if (empty($this->popupsSeleccionados)) {
            // Si no hay pop-ups seleccionados, mostrar notificación
            \Filament\Notifications\Notification::make()
                ->title('Sin selección')
                ->body('No has seleccionado ningún servicio adicional.')
                ->warning()
                ->send();

            // Cerrar el modal de pop-ups
            $this->mostrarModalPopups = false;

            return;
        }

        // Cerrar el modal de pop-ups y mostrar el modal de resumen
        $this->mostrarModalPopups = false;
        $this->mostrarModalResumenPopups = true;

        Log::info('[AgendarCita] Mostrando resumen de pop-ups seleccionados: '.implode(', ', $this->popupsSeleccionados));
    }

    /**
     * Método para cerrar el modal de resumen y volver a la página de vehículos
     */
    public function cerrarResumen(): void
    {
        // Cerrar el modal de resumen
        $this->mostrarModalResumenPopups = false;

        // Guardar los pop-ups seleccionados en la base de datos o enviar notificación
        $this->guardarPopupsSeleccionados();

        // Redirigir a la página de vehículos
        $this->redirect(Vehiculos::getUrl());
    }

    /**
     * Método para guardar los pop-ups seleccionados
     */
    protected function guardarPopupsSeleccionados(): void
    {
        try {
            // Si no hay pop-ups seleccionados, no hacer nada
            if (empty($this->popupsSeleccionados)) {
                Log::info('[AgendarCita] No hay pop-ups seleccionados para guardar');

                return;
            }

            // Obtener los pop-ups seleccionados
            $popupsSeleccionados = [];
            foreach ($this->popupsDisponibles as $popup) {
                if (in_array($popup['id'], $this->popupsSeleccionados)) {
                    $popupsSeleccionados[] = $popup;
                }
            }

            // Aquí se podría implementar la lógica para guardar los pop-ups seleccionados
            // Por ejemplo, enviar un correo, guardar en la base de datos, etc.

            Log::info('[AgendarCita] Pop-ups seleccionados guardados: '.json_encode($popupsSeleccionados));

            // Mostrar notificación de éxito
            \Filament\Notifications\Notification::make()
                ->title('Solicitud Enviada')
                ->body('Tu solicitud de información ha sido enviada. Pronto te contactaremos.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('[AgendarCita] Error al guardar pop-ups seleccionados: '.$e->getMessage());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Ocurrió un error al procesar tu solicitud. Por favor, intenta nuevamente.')
                ->danger()
                ->send();
        }
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
            $fecha = Carbon::createFromDate($this->anoActual, $this->mesActual, $dia)->startOfDay();

            // Verificar si la fecha es pasada o es hoy (ambos no disponibles)
            $esPasado = $fecha->lt($fechaHoy);
            $esHoy = $fecha->isSameDay($fechaHoy);

            // Verificar si hay bloqueos para esta fecha y local
            // Solo está disponible si no es pasado, no es hoy, y no tiene bloqueos
            $disponible = ! $esPasado && ! $esHoy && $this->verificarDisponibilidadFecha($fecha);

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
        if (! empty($this->fechaSeleccionada)) {
            $this->cargarHorariosDisponibles();
        }
    }

    /**
     * Verifica si una fecha está disponible (no tiene bloqueos completos)
     */
    private function verificarDisponibilidadFecha(Carbon $fecha): bool
    {
        // Si la fecha es hoy o anterior, no está disponible
        if ($fecha->startOfDay()->lte(Carbon::today()) || $fecha->isSameDay(Carbon::today())) {
            return false;
        }

        // Si no hay local seleccionado, no podemos verificar disponibilidad
        if (empty($this->localSeleccionado) || empty($this->locales[$this->localSeleccionado]['id'])) {
            return true;
        }

        $localId = $this->locales[$this->localSeleccionado]['id'];
        $fechaStr = $fecha->format('Y-m-d');

        Log::info("[AgendarCita] Verificando disponibilidad para fecha: {$fechaStr}, local ID: {$localId}");

        // Buscar bloqueos para esta fecha y local que sean de todo el día
        $bloqueoCompleto = Bloqueo::where('premises', $localId)
            ->where('start_date', '<=', $fechaStr)
            ->where('end_date', '>=', $fechaStr)
            ->where('all_day', true)
            ->exists();

        // Depuración detallada de la consulta de bloqueos completos
        $queryBloqueoCompleto = Bloqueo::where('premises', $localId)
            ->where('start_date', '<=', $fechaStr)
            ->where('end_date', '>=', $fechaStr)
            ->where('all_day', true)
            ->toSql();
        Log::info("[AgendarCita] Consulta SQL para bloqueos completos: {$queryBloqueoCompleto}");
        Log::info("[AgendarCita] Parámetros: local={$localId}, fecha={$fechaStr}, resultado=".($bloqueoCompleto ? 'Sí' : 'No'));

        // Si hay un bloqueo completo, la fecha no está disponible
        if ($bloqueoCompleto) {
            Log::info("[AgendarCita] Fecha {$fechaStr} bloqueada completamente para local ID: {$localId}");

            return false;
        }

        // Verificar si hay al menos un horario disponible en esta fecha
        $bloqueosParciales = Bloqueo::where('premises', $localId)
            ->where('start_date', '<=', $fechaStr)
            ->where('end_date', '>=', $fechaStr)
            ->where('all_day', false)
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
            $horaInicio = $bloqueo->start_time;
            $horaFin = $bloqueo->end_time;

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
                $horariosDisponibles = array_filter($horariosDisponibles, function ($hora) use ($inicioStr, $finStr) {
                    // Verificar si la hora está dentro del rango bloqueado
                    $dentroDelRango = ($hora >= $inicioStr && $hora <= $finStr);
                    if ($dentroDelRango) {
                        Log::info("[AgendarCita] Verificación: Hora {$hora} está dentro del rango bloqueado {$inicioStr} - {$finStr}");
                    }

                    return ! $dentroDelRango;
                });

                // Registrar los horarios que fueron eliminados
                $horariosEliminados = array_diff($horariosAntesFiltro, $horariosDisponibles);
                if (! empty($horariosEliminados)) {
                    Log::info('[AgendarCita] Horarios eliminados en verificación: '.json_encode(array_values($horariosEliminados)));
                }
            } catch (\Exception $e) {
                Log::error('[AgendarCita] Error al procesar bloqueo en verificación: '.$e->getMessage()."\n".$e->getTraceAsString());
            }
        }

        // Buscar citas existentes para esta fecha y local
        $citas = Appointment::where('premise_id', $localId)
            ->where('appointment_date', $fechaStr)
            ->get();

        foreach ($citas as $cita) {
            // Filtrar los horarios que ya están ocupados por citas
            $horaOcupada = $cita->appointment_time;

            Log::info("[AgendarCita] Cita existente encontrada a las: {$horaOcupada}");

            $horariosDisponibles = array_filter($horariosDisponibles, function ($hora) use ($horaOcupada) {
                return $hora !== $horaOcupada;
            });
        }

        $disponible = ! empty($horariosDisponibles);
        Log::info("[AgendarCita] Fecha {$fechaStr} ".($disponible ? 'disponible' : 'no disponible')." para local ID: {$localId}. Horarios disponibles: ".count($horariosDisponibles));

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
            Log::info('[AgendarCita] No se pueden cargar horarios: fecha o local no seleccionados');

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

            Log::info('[AgendarCita] Horarios base: '.json_encode($horariosBase));

            // Los bloqueos están guardados con el código del local, no el nombre
            $codigoLocal = $this->localSeleccionado;
            Log::info("[AgendarCita] Código del local para buscar bloqueos: '{$codigoLocal}'");

            // Buscar bloqueos para esta fecha y local (por código)
            $bloqueos = Bloqueo::where('premises', $codigoLocal)
                ->where('start_date', '<=', $fechaStr)
                ->where('end_date', '>=', $fechaStr)
                ->get();

            // Depuración detallada de la consulta de bloqueos
            Log::info("[AgendarCita] Consultando bloqueos para local: '{$codigoLocal}' y fecha: {$fechaStr}");

            // Verificar si hay bloqueos en la base de datos para cualquier local
            $todosLosBloqueos = Bloqueo::all();
            Log::info('[AgendarCita] Total de bloqueos en la base de datos: '.$todosLosBloqueos->count());

            foreach ($todosLosBloqueos as $index => $bloqueo) {
                Log::info("[AgendarCita] Bloqueo #{$index} en DB: Local: {$bloqueo->premises}, Fecha: {$bloqueo->start_date} a {$bloqueo->end_date}, Hora: {$bloqueo->start_time} a {$bloqueo->end_time}, Todo día: ".($bloqueo->all_day ? 'Sí' : 'No'));
            }

            // Depuración detallada de la consulta de bloqueos
            $query = Bloqueo::where('premises', $codigoLocal)
                ->where('start_date', '<=', $fechaStr)
                ->where('end_date', '>=', $fechaStr)
                ->toSql();
            $bindings = [
                'premises' => $codigoLocal,
                'start_date' => $fechaStr,
                'end_date' => $fechaStr,
            ];
            Log::info("[AgendarCita] Consulta SQL para bloqueos: {$query}");
            Log::info('[AgendarCita] Parámetros de consulta: '.json_encode($bindings));

            Log::info('[AgendarCita] Bloqueos encontrados: '.$bloqueos->count());

            if ($bloqueos->count() > 0) {
                Log::info('[AgendarCita] Detalles de bloqueos encontrados:');
                foreach ($bloqueos as $index => $bloqueo) {
                    Log::info("[AgendarCita] Bloqueo #{$index}: Local: {$bloqueo->premises}, Fecha: {$bloqueo->start_date} a {$bloqueo->end_date}, Hora: {$bloqueo->start_time} a {$bloqueo->end_time}, Todo día: ".($bloqueo->all_day ? 'Sí' : 'No'));
                }
            }

            foreach ($bloqueos as $bloqueo) {
                // Si es un bloqueo de todo el día, no hay horarios disponibles
                if ($bloqueo->all_day) {
                    Log::info("[AgendarCita] Bloqueo de todo el día encontrado para fecha: {$fechaStr}, local: {$codigoLocal}");
                    $horariosDisponibles = [];
                    break;
                }

                // Filtrar los horarios que están dentro del rango bloqueado
                $horaInicio = $bloqueo->start_time;
                $horaFin = $bloqueo->end_time;

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
                    $horariosDisponibles = array_filter($horariosDisponibles, function ($hora) use ($inicioStr, $finStr) {
                        // Verificar si la hora está dentro del rango bloqueado
                        $dentroDelRango = ($hora >= $inicioStr && $hora <= $finStr);
                        if ($dentroDelRango) {
                            Log::info("[AgendarCita] Hora {$hora} está dentro del rango bloqueado {$inicioStr} - {$finStr}");
                        }

                        return ! $dentroDelRango;
                    });

                    // Registrar los horarios que fueron eliminados
                    $horariosEliminados = array_diff($horariosAntesFiltro, $horariosDisponibles);
                    if (! empty($horariosEliminados)) {
                        Log::info('[AgendarCita] Horarios eliminados por bloqueo: '.json_encode(array_values($horariosEliminados)));
                    }

                    $horariosDespues = count($horariosDisponibles);
                    Log::info("[AgendarCita] Horarios filtrados por bloqueo: {$horariosAntes} -> {$horariosDespues}");

                    if ($horariosAntes > $horariosDespues) {
                        $cantidadEliminados = $horariosAntes - $horariosDespues;
                        Log::info("[AgendarCita] Se eliminaron {$cantidadEliminados} horarios debido al bloqueo");
                    }
                } catch (\Exception $e) {
                    Log::error('[AgendarCita] Error al procesar bloqueo: '.$e->getMessage()."\n".$e->getTraceAsString());
                }
            }

            // Buscar citas existentes para esta fecha y local
            $citas = Appointment::where('premise_id', $localId)
                ->where('appointment_date', $fechaStr)
                ->get();

            Log::info('[AgendarCita] Citas existentes encontradas: '.$citas->count());

            foreach ($citas as $cita) {
                // Filtrar los horarios que ya están ocupados por citas
                $horaOcupada = $cita->appointment_time;

                Log::info("[AgendarCita] Filtrando horario ocupado por cita: {$horaOcupada}");

                $horariosAntes = count($horariosDisponibles);
                $horariosDisponibles = array_filter($horariosDisponibles, function ($hora) use ($horaOcupada) {
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

            Log::info('[AgendarCita] Horarios disponibles finales: '.json_encode($horariosFormateados));

            // Si la hora seleccionada no está disponible, limpiarla
            if (! empty($this->horaSeleccionada) && ! in_array($this->horaSeleccionada, $horariosFormateados)) {
                Log::info("[AgendarCita] Hora seleccionada '{$this->horaSeleccionada}' ya no está disponible, limpiando selección");
                $this->horaSeleccionada = '';
            }
        } catch (\Exception $e) {
            Log::error('[AgendarCita] Error al cargar horarios disponibles: '.$e->getMessage());
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
            Log::warning('[AgendarCita] No hay local seleccionado para obtener horarios base, usando predeterminados');

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
        $horaApertura = $local['opening_time'];
        $horaCierre = $local['closing_time'];

        Log::info("[AgendarCita] Horarios del local seleccionado: {$horaApertura} - {$horaCierre}");

        // Convertir a objetos Carbon para facilitar la manipulación
        try {
            $apertura = Carbon::createFromFormat('H:i:s', $horaApertura);
            $cierre = Carbon::createFromFormat('H:i:s', $horaCierre);

            // Asegurarse de que la hora de cierre sea posterior a la de apertura
            if ($apertura->gt($cierre)) {
                Log::warning('[AgendarCita] Hora de apertura posterior a la de cierre, usando horarios predeterminados');

                return $this->obtenerHorariosBaseDefault();
            }

            // Obtener la hora de apertura y cierre en formato de hora
            $horaAperturaInt = (int) $apertura->format('H');
            $horaCierreInt = (int) $cierre->format('H');

            // Generar horarios cada 30 minutos desde la apertura hasta el cierre
            for ($hora = $horaAperturaInt; $hora <= $horaCierreInt; $hora++) {
                // Agregar la hora en punto
                $horarios[] = sprintf('%02d:00:00', $hora);

                // Agregar la media hora, excepto para la última hora
                if ($hora < $horaCierreInt) {
                    $horarios[] = sprintf('%02d:30:00', $hora);
                }
            }

            Log::info('[AgendarCita] Horarios base generados: '.count($horarios));

            return $horarios;
        } catch (\Exception $e) {
            Log::error('[AgendarCita] Error al procesar horarios del local: '.$e->getMessage());

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
            $fechaCarbon = Carbon::createFromFormat('d/m/Y', $fecha)->startOfDay();

            // Verificar si la fecha es pasada o es hoy
            if ($fechaCarbon->lte(Carbon::today()) || $fechaCarbon->isSameDay(Carbon::today())) {
                Log::warning("[AgendarCita] Intento de seleccionar fecha pasada o actual: {$fecha}");

                // No permitir seleccionar fechas pasadas o el día de hoy
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
            Log::error('[AgendarCita] Error al seleccionar fecha: '.$e->getMessage());
        }
    }

    /**
     * Selecciona una hora
     */
    public function seleccionarHora(string $hora): void
    {
        Log::info("[AgendarCita] Intentando seleccionar hora: {$hora}");
        Log::info('[AgendarCita] Horarios disponibles: '.json_encode($this->horariosDisponibles));

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
        if (! empty($this->fechaSeleccionada)) {
            Log::info("[AgendarCita] Recargando horarios para fecha: {$this->fechaSeleccionada} y local: {$value}");
            $this->cargarHorariosDisponibles();
        }

        // Recargar las campañas
        $this->cargarCampanas();
        Log::info("[AgendarCita] Campañas recargadas después de cambiar el local a: {$value}. Total: ".count($this->campanasDisponibles));

        // Recargar las modalidades disponibles para el nuevo local
        $this->cargarModalidadesDisponibles();

        // Si la modalidad actual ya no está disponible, cambiar a Regular
        if (! array_key_exists($this->modalidadServicio, $this->modalidadesDisponibles)) {
            $this->modalidadServicio = 'Regular';
            Log::info('[AgendarCita] Modalidad cambiada a Regular porque la anterior no está disponible en el nuevo local');
        }

        // Forzar la actualización de la vista
        $this->dispatch('horarios-actualizados');
    }

    /**
     * Método que se ejecuta cuando se actualiza el tipo de mantenimiento
     */
    public function updatedTipoMantenimiento(): void
    {
        // Recargar las modalidades disponibles para el nuevo tipo de mantenimiento
        $this->cargarModalidadesDisponibles();

        // Si la modalidad actual ya no está disponible, cambiar a Regular
        if (! array_key_exists($this->modalidadServicio, $this->modalidadesDisponibles)) {
            $this->modalidadServicio = 'Regular';
        }
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
