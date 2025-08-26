<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Vehicle;
use App\Jobs\DeleteAppointmentC4CJob;
use App\Services\VehiculoSoapService;
use App\Services\C4C\AppointmentService;
use App\Mail\CitaCancelada;
use Illuminate\Support\Facades\Mail;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use SoapClient;
use SoapFault;

class DetalleVehiculo extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Detalle del Vehículo';

    protected static ?string $navigationGroup = '🚗 Vehículos';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = '';

    protected static string $view = 'filament.pages.detalle-vehiculo';

    // Ocultar de la navegación principal ya que se accederá desde la página de vehículos
    protected static bool $shouldRegisterNavigation = false;

    // Datos del vehículo - Se cargan dinámicamente en mount()
    public ?array $vehiculo = [
        'modelo' => 'Cargando...',
        'kilometraje' => 'Cargando...',
        'placa' => 'Cargando...',
    ];

    // Datos de mantenimiento - Se cargan dinámicamente en cargarDatosMantenimiento()
    public array $mantenimiento = [
        'ultimo' => 'Cargando...',
        'fecha' => 'Cargando...',
        'vencimiento' => 'Cargando...',
        'disponibles' => ['Cargando...'],
    ];

    // Citas agendadas - Se cargan dinámicamente en cargarCitasAgendadas()
    public array $citasAgendadas = [];

    // Historial de servicios
    public Collection $historialServicios;

    public int $currentPage = 1;

    public int $perPage = 10;

    // Parámetro de URL para el ID del vehículo
    public ?string $vehiculoId = null;

    // Cliente SOAP para SAP
    protected ?SoapClient $soapClient = null;

    // Indicador de si se están cargando datos desde SAP
    public bool $cargandoDesdeSAP = false;

    // Datos del asesor SAP para enriquecer las citas
    protected ?array $datosAsesorSAP = null;

    public function mount(): void
    {
        // Inicializar con valores por defecto
        $this->inicializarValoresPorDefecto();

        // Obtener el vehiculoId de diferentes fuentes
        $vehiculoId = $this->vehiculoId ?? request()->query('vehiculoId') ?? request()->input('vehiculoId');

        Log::info("[DetalleVehiculo] Parámetro recibido: vehiculoId = " . ($vehiculoId ?? 'NULL'));
        Log::info("[DetalleVehiculo] Property vehiculoId: " . ($this->vehiculoId ?? 'NULL'));
        Log::info("[DetalleVehiculo] Query params: " . json_encode(request()->query()));
        Log::info("[DetalleVehiculo] All request: " . json_encode(request()->all()));

        // Cargar datos del vehículo basados en el ID recibido
        if ($vehiculoId) {
            // Limpiar el ID del vehículo (eliminar espacios)
            $vehiculoId = trim(str_replace(' ', '', $vehiculoId));
            
            // IMPORTANTE: Asignar el vehiculoId limpio a la propiedad para uso posterior
            $this->vehiculoId = $vehiculoId;
            Log::info("[DetalleVehiculo] Cargando datos para vehículo ID (limpio): {$vehiculoId}");

            // Buscar el vehículo en la base de datos - intentar diferentes formas de búsqueda
            Log::info('[DetalleVehiculo] Buscando vehículo en la base de datos con vehicle_id o license_plate');

            // Primero intentamos una búsqueda exacta
            $vehiculo = Vehicle::where('vehicle_id', $vehiculoId)
                ->orWhere('license_plate', $vehiculoId)
                ->first();

            // Si no encontramos, intentamos una búsqueda con LIKE
            if (! $vehiculo) {
                Log::info('[DetalleVehiculo] No se encontró con búsqueda exacta, intentando con LIKE');
                $vehiculo = Vehicle::where('vehicle_id', 'LIKE', "%{$vehiculoId}%")
                    ->orWhere('license_plate', 'LIKE', "%{$vehiculoId}%")
                    ->first();
            }

            if ($vehiculo) {
                // Si encontramos el vehículo, usamos sus datos básicos
                $this->cargarDatosVehiculo($vehiculo);

                // Cargar datos de mantenimiento
                $this->cargarDatosMantenimiento($vehiculo);

                Log::info('[DetalleVehiculo] Vehículo encontrado en la base de datos:', $this->vehiculo);

                // PRIMERO: Cargar datos actualizados desde SAP (incluyendo datos del asesor)
                if (config('vehiculos_webservice.enabled', true)) {
                    Log::info("[DetalleVehiculo] Cargando datos actualizados desde SAP para vehículo: {$vehiculo->license_plate}");
                    $this->cargarDatosVehiculoDesdeSAP($vehiculo->license_plate);
                } else {
                    Log::info('[DetalleVehiculo] SAP deshabilitado, usando solo datos de BD');
                }

                // SEGUNDO: Cargar citas agendadas (ahora con datos SAP disponibles)
                $this->cargarCitasAgendadas($vehiculo->id);
            } else {
                Log::warning("[DetalleVehiculo] No se encontró el vehículo con ID: {$vehiculoId}.");

                // Intentar cargar datos desde SAP si está habilitado
                if (config('vehiculos_webservice.enabled', true)) {
                    Log::info("[DetalleVehiculo] Vehículo no encontrado en BD, intentando cargar desde SAP: {$vehiculoId}");
                    $this->cargarDatosVehiculoDesdeSAP($vehiculoId);
                } else {
                    // Establecer mensaje de error si SAP no está disponible
                    $this->vehiculo = [
                        'modelo' => 'Vehículo no encontrado',
                        'kilometraje' => 'No disponible',
                        'placa' => $vehiculoId,
                        'anio' => 'No disponible',
                        'marca' => 'No disponible',
                        'color' => 'No disponible',
                    ];

                    $this->mantenimiento = [
                        'ultimo' => 'No disponible',
                        'fecha' => 'No disponible',
                        'vencimiento' => 'No disponible',
                        'disponibles' => ['Vehículo no encontrado'],
                    ];

                    $this->citasAgendadas = [];
                }
            }
        } else {
            Log::warning('[DetalleVehiculo] No se proporcionó ID de vehículo.');
            // Inicializar historial vacío solo si no hay vehículo
            $this->historialServicios = collect();
        }
    }

    /**
     * Inicializar valores por defecto
     */
    protected function inicializarValoresPorDefecto(): void
    {
        $this->vehiculo = [
            'modelo' => 'No disponible',
            'kilometraje' => 'No disponible',
            'placa' => 'No disponible',
        ];

        $this->mantenimiento = [
            'ultimo' => 'No disponible',
            'fecha' => 'No disponible',
            'vencimiento' => 'No disponible',
            'disponibles' => ['No disponible'],
        ];

        $this->citasAgendadas = [];

        // Inicializar historial vacío
        $this->historialServicios = collect();
    }

    /**
     * Cargar datos del vehículo desde el modelo
     */
    protected function cargarDatosVehiculo(Vehicle $vehiculo): void
    {
        $this->vehiculo = [
            'id' => $vehiculo->id,
            'vehicle_id' => $vehiculo->vehicle_id,
            'modelo' => $vehiculo->model ?? 'No disponible',
            'kilometraje' => $vehiculo->mileage ? number_format($vehiculo->mileage, 0, '.', ',').' Km' : 'No disponible',
            'placa' => $vehiculo->license_plate ?? 'No disponible',
            'anio' => $vehiculo->year ?? 'No disponible',
            'marca' => $vehiculo->brand_name ?? 'No disponible',
            'color' => $vehiculo->color ?? 'No disponible',
            'vin' => $vehiculo->vin ?? 'No disponible',
            'motor' => $vehiculo->engine_number ?? 'No disponible',
        ];
    }

    /**
     * Cargar datos de mantenimiento del vehículo
     */
    protected function cargarDatosMantenimiento(Vehicle $vehiculo): void
    {
        try {
            Log::info("[DetalleVehiculo] Cargando datos de mantenimiento para vehículo ID: {$vehiculo->id}");

            $this->mantenimiento = [
                'ultimo' => $vehiculo->last_service_mileage ? number_format($vehiculo->last_service_mileage, 0, '.', ',').' Km' : 'No disponible',
                'fecha' => $vehiculo->last_service_date ? $vehiculo->last_service_date->format('d/m/Y') : 'No disponible',
                'vencimiento' => $vehiculo->prepaid_maintenance_expiry ? $vehiculo->prepaid_maintenance_expiry->format('d/m/Y') : 'No disponible',
                'disponibles' => $vehiculo->has_prepaid_maintenance ? [
                    '1 Servicio '.number_format($vehiculo->next_service_mileage ?? 10000, 0, '.', ',').' Km',
                ] : ['No disponible'],
            ];

            Log::info('[DetalleVehiculo] Datos de mantenimiento cargados correctamente');
        } catch (\Exception $e) {
            Log::error('[DetalleVehiculo] Error al cargar datos de mantenimiento: '.$e->getMessage());

            // Establecer valores predeterminados en caso de error
            $this->mantenimiento = [
                'ultimo' => 'No disponible',
                'fecha' => 'No disponible',
                'vencimiento' => 'No disponible',
                'disponibles' => ['No disponible'],
            ];
        }
    }

    /**
     * Cargar citas agendadas para el vehículo desde WSCitas (C4C)
     */
    protected function cargarCitasAgendadas(int $vehiculoId): void
    {
        try {
            Log::info("[DetalleVehiculo] Cargando citas agendadas desde WSCitas para vehículo ID: {$vehiculoId}");

            // Obtener el vehículo para conseguir la placa
            $vehiculo = Vehicle::find($vehiculoId);
            if (!$vehiculo) {
                Log::warning("[DetalleVehiculo] No se encontró el vehículo con ID: {$vehiculoId}");
                $this->citasAgendadas = [];
                return;
            }

            $placaVehiculo = $vehiculo->license_plate;
            Log::info("[DetalleVehiculo] Placa del vehículo: {$placaVehiculo}");

            // Obtener el usuario logueado y verificar si tiene datos reales de C4C
            $user = Auth::user();
            if (!$user || !$user->hasRealC4cData()) {
                Log::warning("[DetalleVehiculo] Usuario no tiene datos C4C válidos (es comodín o sin c4c_internal_id)", [
                    'user_id' => $user ? $user->id : 'N/A',
                    'c4c_internal_id' => $user ? $user->c4c_internal_id : 'N/A',
                    'is_comodin' => $user ? $user->is_comodin : 'N/A',
                    'has_real_c4c_data' => $user ? $user->hasRealC4cData() : 'N/A'
                ]);
                
                // Para clientes comodín, usar citas locales directamente
                $this->cargarCitasLocalesPendientes($vehiculoId);
                return;
            }

            $c4cInternalId = $user->c4c_internal_id;
            Log::info("[DetalleVehiculo] Usuario con datos C4C válidos - c4c_internal_id: {$c4cInternalId}");

            // Consultar WSCitas usando AppointmentService (datos reales)
            $appointmentService = new AppointmentService();

            // Obtener todas las citas pendientes del cliente
            $result = $appointmentService->queryPendingAppointments($c4cInternalId);

            Log::info("[DetalleVehiculo] Respuesta WSCitas:", $result);

            if ($result['success'] && !empty($result['data'])) {
                // Filtrar citas solo para este vehículo específico
                $citasVehiculo = array_filter($result['data'], function($cita) use ($placaVehiculo) {
                    // Verificar diferentes estructuras posibles para la placa
                    $placaCita = $cita['license_plate'] ?? 
                                $cita['vehicle']['plate'] ?? 
                                $cita['plate'] ?? 
                                null;
                    
                    Log::debug("[DetalleVehiculo] Comparando placas", [
                        'placa_vehiculo' => $placaVehiculo,
                        'placa_cita' => $placaCita,
                        'cita_estructura' => array_keys($cita),
                    ]);
                    
                    return $placaCita && trim($placaCita) === trim($placaVehiculo);
                });

                Log::info("[DetalleVehiculo] Citas filtradas para vehículo {$placaVehiculo}: " . count($citasVehiculo));

                if (!empty($citasVehiculo)) {
                    $this->citasAgendadas = [];

                    foreach ($citasVehiculo as $cita) {
                        // Log para depuración de la estructura de la cita
                        Log::info('[DetalleVehiculo] Asignando fecha de cita:', [
                            'fecha_sap' => $this->datosAsesorSAP['fecha_ult_serv'] ?? 'No existe',
                            'fecha_cita' => $cita['scheduled_start_date'] ?? 'No existe',
                            'fecha_formateada' => $this->formatearFechaC4C($cita['scheduled_start_date'] ?? '')
                        ]);

                        // Mapear campos de WSCitas a la estructura de la vista (estructura real)
                        $estadoInfo = $this->obtenerInformacionEstadoCompleta($cita['status']['appointment_code'] ?? $cita['appointment_status'] ?? '1', $cita);

                        // Enriquecer con datos SAP si están disponibles
                        $citaEnriquecida = $this->enriquecerCitaConDatosSAP($cita);

                        // Obtener maintenance_type desde la base de datos local
                        $maintenanceTypeLocal = $this->obtenerMaintenanceTypeLocal($cita['uuid'] ?? $cita['id'] ?? '');

                        $this->citasAgendadas[] = [
                            'id' => $cita['uuid'] ?? $cita['id'] ?? 'N/A',
                            'numero_cita' => $cita['id'] ?? 'N/A',
                            'servicio' => $maintenanceTypeLocal ?: $this->determinarTipoServicioC4C($cita),
                            'maintenance_type' => $maintenanceTypeLocal,
                            'estado' => $estadoInfo['nombre'],
                            'fecha_cita' => $this->formatearFechaC4C($cita['scheduled_start_date'] ?? ''),
                            'hora_cita' => $this->formatearHoraC4C($cita['dates']['start_time'] ?? $cita['start_time'] ?? ''),
                            'probable_entrega' => $citaEnriquecida['probable_entrega'],
                            'sede' => (\App\Models\Local::where('code', $cita['center']['id'] ?? $cita['center_id'] ?? '')->value('name') ?: ($cita['center']['id'] ?? $cita['center_id'] ?? 'No especificado')),
                            'asesor' => $citaEnriquecida['asesor'],
                            'whatsapp' => $citaEnriquecida['whatsapp'],
                            'correo' => $citaEnriquecida['correo'],
                            'comentarios' => $cita['subject'] ?? $cita['subject_name'] ?? '',
                            'status_raw' => $cita['status']['appointment_code'] ?? $cita['appointment_status'] ?? '1',
                            // Información completa del estado para la vista
                            'estado_info' => $estadoInfo,
                            // Datos adicionales de facturación SAP
                            'fecha_factura' => $citaEnriquecida['fecha_factura'],
                            'hora_factura' => $citaEnriquecida['hora_factura'],
                            'rut_pdf' => $citaEnriquecida['rut_pdf'],
                            // Campos adicionales de C4C
                            'cliente_nombre' => $cita['client_name'] ?? '',
                            'cliente_dni' => $cita['client_dni'] ?? '',
                            'vehiculo_modelo' => $cita['vehicle_model'] ?? '',
                            'vehiculo_vin' => $cita['vin'] ?? '',
                            'ubicacion' => $cita['location_name'] ?? '',
                            'prioridad' => $cita['priority_name'] ?? 'Normal',
                            // Campos adicionales del webservice
                            'centro_id' => $cita['center_id'] ?? '',
                            'solicitar_taxi' => $cita['request_taxi_name'] ?? '',
                            'telefono_fijo' => $cita['client_landline'] ?? '',
                            'direccion_cliente' => $cita['client_address'] ?? '',
                            'version_vehiculo' => $cita['vehicle_version'] ?? '',
                            'kilometraje_vehiculo' => $cita['vehicle_mileage'] ?? '',
                        ];
                    }

                    Log::info('[DetalleVehiculo] Citas agendadas cargadas desde WSCitas: ' . count($this->citasAgendadas));
                } else {
                    Log::info("[DetalleVehiculo] No hay citas pendientes para el vehículo {$placaVehiculo}");
                    $this->citasAgendadas = [];
                }
            } else {
                Log::info("[DetalleVehiculo] No se encontraron citas pendientes en WSCitas");
                $this->citasAgendadas = [];
            }

            // Si no hay citas de WSCitas, no se cargan citas locales.
            if (empty($this->citasAgendadas)) {
                Log::info("[DetalleVehiculo] No se encontraron citas en WSCitas y el fallback local está deshabilitado.");
            }

        } catch (\Exception $e) {
            Log::error('[DetalleVehiculo] Error al cargar citas desde WSCitas: ' . $e->getMessage());
            Log::error('[DetalleVehiculo] Stack trace: ' . $e->getTraceAsString());

            // En caso de error con WSCitas, no se usará el fallback local.
            $this->citasAgendadas = [];
            Log::info("[DetalleVehiculo] Fallback a citas locales deshabilitado tras error en WSCitas.");
        }
    }

    /**
     * Cargar citas pendientes desde la base de datos local como fallback
     */
    protected function cargarCitasLocalesPendientes(int $vehiculoId): void
    {
        try {
            Log::info("[DetalleVehiculo] Cargando citas locales para vehículo ID: {$vehiculoId}");

            // Obtener citas pendientes/confirmadas desde la BD local
            $citasLocales = Appointment::with(['premise'])
                ->where('vehicle_id', $vehiculoId)
                ->whereIn('status', ['pending', 'confirmed', 'generated'])
                ->where('appointment_date', '>=', now()->format('Y-m-d'))
                ->orderBy('appointment_date', 'asc')
                ->orderBy('appointment_time', 'asc')
                ->get();

            Log::info("[DetalleVehiculo] Citas locales encontradas: " . $citasLocales->count());

            if ($citasLocales->isNotEmpty()) {
                $this->citasAgendadas = [];

                foreach ($citasLocales as $cita) {
                    $estadoInfo = $this->obtenerInformacionEstadoCompletaLocal($cita->status, $cita);

                    // Crear datos base de la cita local
                    $citaLocal = [
                        'customer_phone' => $cita->customer_phone,
                        'customer_email' => $cita->customer_email,
                        'appointment_date' => $cita->appointment_date,
                        'appointment_time' => $cita->appointment_time,
                    ];

                    // Enriquecer con datos SAP si están disponibles
                    $citaEnriquecida = $this->enriquecerCitaConDatosSAP($citaLocal);

                    $this->citasAgendadas[] = [
                        'id' => $cita->c4c_uuid ?? 'local-' . $cita->id,
                        'numero_cita' => $cita->appointment_number ?? 'CITA-' . $cita->id,
                        'servicio' => $this->determinarTipoServicio($cita->maintenance_type, $cita->service_mode),
                        'maintenance_type' => $cita->maintenance_type,
                        'estado' => $estadoInfo['nombre'],
                        'fecha_cita' => $cita->appointment_date ? $cita->appointment_date->format('d/m/Y') : '-',
                        'hora_cita' => $cita->appointment_time ? $cita->appointment_time->format('H:i') : '-',
                        'probable_entrega' => $citaEnriquecida['probable_entrega'],
                        'sede' => $cita->premise->name ?? 'Por confirmar',
                        'asesor' => $citaEnriquecida['asesor'],
                        'whatsapp' => $citaEnriquecida['whatsapp'],
                        'correo' => $citaEnriquecida['correo'],
                        'comentarios' => $cita->comments ?? '',
                        'status_raw' => $this->mapearEstadoLocalAC4C($cita->status),
                        // Información completa del estado para la vista
                        'estado_info' => $estadoInfo,
                        // Campos adicionales de la BD local
                        'cliente_nombre' => $cita->customer_name . ' ' . $cita->customer_last_name,
                        'cliente_dni' => $cita->customer_ruc ?? '-',
                        'vehiculo_modelo' => $this->vehiculo['modelo'] ?? '-',
                        'vehiculo_vin' => '-',
                        'ubicacion' => $cita->premise->name ?? 'Por confirmar',
                        'prioridad' => 'Normal',
                        // Campos adicionales
                        'centro_id' => $cita->premise_id ?? '',
                        'solicitar_taxi' => '-',
                        'telefono_fijo' => '-',
                        'direccion_cliente' => '-',
                        'version_vehiculo' => '-',
                        'kilometraje_vehiculo' => '-',
                        // Indicador de que es cita local
                        'fuente' => 'local',
                        'sincronizada' => $cita->is_synced ? 'Sí' : 'Pendiente',
                    ];
                }

                Log::info('[DetalleVehiculo] Citas locales cargadas exitosamente: ' . count($this->citasAgendadas));
            } else {
                Log::info("[DetalleVehiculo] No hay citas locales pendientes para el vehículo ID: {$vehiculoId}");
                $this->citasAgendadas = [];
            }

        } catch (\Exception $e) {
            Log::error('[DetalleVehiculo] Error al cargar citas locales: ' . $e->getMessage());
            Log::error('[DetalleVehiculo] Stack trace: ' . $e->getTraceAsString());
            
            // En caso de error, asegurar que el array esté inicializado
            $this->citasAgendadas = [];
        }
    }

    /**
     * Calcular probable entrega para cita local
     */
    protected function calcularProbableEntregaLocal(Appointment $cita): string
    {
        if ($cita->appointment_end_time) {
            return $cita->appointment_date->format('d/m/Y') . ' ' . $cita->appointment_end_time->format('H:i');
        }

        // Estimar 4 horas después del inicio
        $fechaInicio = $cita->appointment_date;
        $horaInicio = $cita->appointment_time;

        if ($fechaInicio && $horaInicio) {
            $fechaHoraInicio = $fechaInicio->setTimeFrom($horaInicio);
            $fechaHoraFin = $fechaHoraInicio->copy()->addHours(4);
            return $fechaHoraFin->format('d/m/Y H:i');
        }

        return '-';
    }

    /**
     * Mapear estado local a código C4C
     */
    protected function mapearEstadoLocalAC4C(string $statusLocal): string
    {
        return match ($statusLocal) {
            'pending' => '1',      // Generada
            'confirmed' => '2',    // Confirmada
            'generated' => '1',    // Generada
            'in_progress' => '3',  // En proceso
            'completed' => '5',    // Completada
            'cancelled' => '6',    // Cancelada
            default => '1',
        };
    }

    /**
     * Obtener información de estado para citas locales
     */
    protected function obtenerInformacionEstadoCompletaLocal(string $statusLocal, $appointment = null): array
    {
        $estadoC4C = $this->mapearEstadoLocalAC4C($statusLocal);
        
        // Preparar datos de la cita si están disponibles
        $appointmentData = null;
        if ($appointment) {
            $appointmentData = [
                'appointment_date' => $appointment->appointment_date,
                'appointment_time' => $appointment->appointment_time,
                'scheduled_start_date' => $appointment->appointment_date ? $appointment->appointment_date->format('Y-m-d') : null,
            ];
        }
        
        return $this->obtenerInformacionEstadoCompleta($estadoC4C, $appointmentData);
    }

    /**
     * Obtener maintenance_type desde la base de datos local usando el UUID de C4C
     */
    protected function obtenerMaintenanceTypeLocal(string $uuid): ?string
    {
        try {
            if (empty($uuid)) {
                return null;
            }

            Log::info("[DetalleVehiculo] Buscando maintenance_type para UUID: {$uuid}");

            // Buscar la cita en la base de datos local usando el c4c_uuid
            $appointment = Appointment::where('c4c_uuid', $uuid)->first();

            if ($appointment && !empty($appointment->maintenance_type)) {
                Log::info("[DetalleVehiculo] Maintenance_type encontrado: {$appointment->maintenance_type}");
                return $appointment->maintenance_type;
            }

            Log::info("[DetalleVehiculo] No se encontró maintenance_type para UUID: {$uuid}");
            return null;

        } catch (\Exception $e) {
            Log::error("[DetalleVehiculo] Error al obtener maintenance_type: " . $e->getMessage());
            return null;
        }
    }

    protected function inicializarHistorialServicios(): void
    {
        $servicios = [];

        // Si tenemos un vehículo cargado, buscamos su historial de citas completadas
        if (isset($this->vehiculo['id'])) {
            Log::info("[DetalleVehiculo] Buscando historial de citas para el vehículo ID: {$this->vehiculo['id']}");

            try {
                $citasCompletadas = Appointment::with(['premise'])
                    ->where('vehicle_id', $this->vehiculo['id'])
                    ->where('status', 'completed')
                    ->orderBy('appointment_date', 'desc')
                    ->get();

                Log::info("[DetalleVehiculo] Se encontraron {$citasCompletadas->count()} citas completadas");

                foreach ($citasCompletadas as $cita) {
                    $tipoServicio = $this->determinarTipoServicio($cita->maintenance_type, $cita->service_mode);

                    $servicios[] = [
                        'servicio' => $tipoServicio,
                        'maintenance_type' => $cita->maintenance_type,
                        'fecha' => $cita->appointment_date ? $cita->appointment_date->format('d/m/Y') : 'No disponible',
                        'sede' => $cita->premise->name ?? 'No especificado',
                        'asesor' => 'Asesor asignado',
                        'tipo_pago' => $this->determinarTipoPago($cita),
                    ];
                }
            } catch (\Exception $e) {
                Log::error('[DetalleVehiculo] Error al cargar historial de citas: '.$e->getMessage());
            }
        } else {
            Log::warning('[DetalleVehiculo] No se puede cargar el historial de citas porque no hay un vehículo cargado');
        }

        // Si no hay servicios completados, agregamos un ejemplo
        if (empty($servicios)) {
            Log::info('[DetalleVehiculo] No hay servicios completados, agregando ejemplo');

            // Si tenemos un vehículo cargado, personalizamos el ejemplo
            if (isset($this->vehiculo['modelo'])) {
                $servicios[] = [
                    'servicio' => 'Mantenimiento 10,000 Km',
                    'fecha' => date('d/m/Y', strtotime('-3 months')),
                    'sede' => 'Mitsui La Molina',
                    'asesor' => 'Luis Gonzales',
                    'tipo_pago' => 'Contado',
                ];
            } else {
                $servicios[] = [
                    'servicio' => 'Mantenimiento 15,000 Km',
                    'fecha' => '30/10/2023',
                    'sede' => 'La Molina',
                    'asesor' => 'Luis Gonzales',
                    'tipo_pago' => 'Contado',
                ];
            }
        }

        $this->historialServicios = collect($servicios);
        Log::info('[DetalleVehiculo] Historial de servicios inicializado con '.count($servicios).' servicios');
    }

    public function getHistorialPaginadoProperty(): LengthAwarePaginator
    {
        $page = request()->query('page', $this->currentPage);

        return new LengthAwarePaginator(
            $this->historialServicios->forPage($page, $this->perPage),
            $this->historialServicios->count(),
            $this->perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    // Método para volver a la página de vehículos
    public function volver(): void
    {
        $this->redirect(Vehiculos::getUrl());
    }

    // Método para forzar recarga de datos SAP (útil para debugging)
    public function recargarDatosSAP(): void
    {
        if (!isset($this->vehiculo['placa'])) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('No hay vehículo cargado para recargar datos SAP.')
                ->danger()
                ->send();
            return;
        }

        $placa = trim(str_replace(' ', '', $this->vehiculo['placa']));
        
        Log::info("[DetalleVehiculo] 🔄 RECARGA MANUAL DE DATOS SAP solicitada para placa: {$placa}");
        
        if (config('vehiculos_webservice.enabled', true)) {
            $this->cargarDatosVehiculoDesdeSAP($placa);
            
            // Recargar citas con los nuevos datos SAP
            $vehiculo = Vehicle::where('license_plate', $placa)->first();
            if ($vehiculo) {
                $this->cargarCitasAgendadas($vehiculo->id);
            }
            
            \Filament\Notifications\Notification::make()
                ->title('Datos SAP Recargados')
                ->body('Los datos del vehículo y estados de citas han sido actualizados desde SAP.')
                ->success()
                ->send();
        } else {
            \Filament\Notifications\Notification::make()
                ->title('SAP Deshabilitado')
                ->body('El webservice SAP está deshabilitado en la configuración.')
                ->warning()
                ->send();
        }
    }

    /**
     * Refrescar citas agendadas - útil para re-aplicar el filtro de 24 horas
     */
    public function refrescarCitas(): void
    {
        Log::info('[DetalleVehiculo] Refrescando citas agendadas manualmente');
        
        if ($this->vehiculo && isset($this->vehiculo['id'])) {
            $this->cargarCitasAgendadas($this->vehiculo['id']);
            
            // Emitir evento para notificar el refresco
            $this->dispatch('citas-refrescadas', [
                'count' => count($this->citasAgendadas),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            \Filament\Notifications\Notification::make()
                ->title('Citas Actualizadas')
                ->body('Las citas han sido refrescadas. Se aplicaron los filtros de 24 horas para citas completadas.')
                ->success()
                ->send();
        }
    }

    // Método para ir a agendar cita
    public function agendarCita(): void
    {
        // Verificar si tenemos un vehículo cargado
        if (! isset($this->vehiculo['placa'])) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('No se ha seleccionado un vehículo válido.')
                ->danger()
                ->send();

            return;
        }

        // Asegurarse de que la placa no tenga espacios adicionales
        $placa = $this->vehiculo['placa'] ?? '';
        $placa = trim(str_replace(' ', '', $placa));

        // Verificar si el vehículo existe en la base de datos
        $vehiculo = Vehicle::where('license_plate', $placa)->first();

        if (! $vehiculo) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('El vehículo no se encuentra registrado en la base de datos.')
                ->danger()
                ->send();

            return;
        }

        // Registrar la placa original y la placa limpia
        Log::info('[DetalleVehiculo] Datos del vehículo para agendar cita:', [
            'placa_original' => $this->vehiculo['placa'] ?? 'No disponible',
            'placa_limpia' => $placa,
            'modelo' => $this->vehiculo['modelo'] ?? 'No disponible',
            'vehicle_id' => $vehiculo->id,
        ]);

        // Redirigir a la página de agendar cita con la placa como parámetro
        $this->redirect(AgendarCita::getUrl(['vehiculoId' => $placa]));
    }

    /**
     * Editar una cita existente - Redirige a agendar cita en modo edición
     */
    public function editarCita(array $citaData): void
    {
        Log::info('🔧 [DetalleVehiculo::editarCita] ========== INICIO EDICIÓN CITA ==========');
        Log::info('[DetalleVehiculo::editarCita] Iniciando edición de cita', [
            'cita_id' => $citaData['id'] ?? 'N/A',
            'vehiculo_id' => $this->vehiculoId,
            'numero_cita' => $citaData['numero_cita'] ?? 'N/A',
            'raw_cita_data' => $citaData
        ]);

        // Validar que tenemos los datos necesarios
        if (empty($citaData['id'])) {
            Log::error('[DetalleVehiculo::editarCita] ID de cita no disponible');
            return;
        }

        // Preparar parámetros para el modo edición con mejor mapeo
        $editParams = [
            'vehiculoId' => $this->vehiculoId,
            'editMode' => 'true',
            'originalCitaId' => $citaData['numero_cita'] ?? $citaData['id'],
            'originalUuid' => $citaData['id'],
            'originalCenterId' => $this->mapearCentroIdParaEdicion($citaData),
            'originalDate' => $citaData['fecha_cita'] ?? '',
            'originalTime' => $citaData['hora_cita'] ?? '',
            'originalServicio' => $citaData['servicio'] ?? '',
            'originalSede' => $citaData['sede'] ?? ''
        ];

        Log::info('[DetalleVehiculo::editarCita] Datos de cita mapeados', [
            'raw_centro_id' => $citaData['centro_id'] ?? 'N/A',
            'raw_sede' => $citaData['sede'] ?? 'N/A',
            'mapped_centro_id' => $editParams['originalCenterId'],
            'fecha_cita' => $citaData['fecha_cita'] ?? 'N/A',
            'hora_cita' => $citaData['hora_cita'] ?? 'N/A'
        ]);

        Log::info('[DetalleVehiculo::editarCita] Redirigiendo a modo edición', $editParams);
        Log::info('🚀 [DetalleVehiculo::editarCita] URL generada: ' . AgendarCita::getUrl($editParams));

        // Redirigir a AgendarCita con parámetros de edición
        $this->redirect(AgendarCita::getUrl($editParams));
        
        Log::info('✅ [DetalleVehiculo::editarCita] ========== REDIRECCIÓN EJECUTADA ==========');
    }

    /**
     * Mapear centro_id de C4C a código de local para edición
     */
    protected function mapearCentroIdParaEdicion(array $citaData): string
    {
        // Mapeo basado en los datos disponibles
        $centroId = $citaData['centro_id'] ?? '';
        $sede = $citaData['sede'] ?? '';
        
        // Mapeo directo si ya viene el código correcto
        if (in_array($centroId, ['M013', 'M023', 'M303', 'M313', 'L013', 'L023'])) {
            return $centroId;
        }
        
        // Mapeo por nombre de sede
        $mapeoSedes = [
            'MOLINA' => 'M013',
            'CANADA' => 'M023', 
            'MIRAFLORES' => 'M303',
            'AREQUIPA' => 'M313',
            'LEXUS' => 'L013'
        ];
        
        foreach ($mapeoSedes as $nombreSede => $codigoLocal) {
            if (stripos($sede, $nombreSede) !== false) {
                Log::info('[DetalleVehiculo::mapearCentroIdParaEdicion] Mapeo encontrado', [
                    'sede_original' => $sede,
                    'codigo_mapeado' => $codigoLocal
                ]);
                return $codigoLocal;
            }
        }
        
        // Fallback: retornar el centro_id original
        Log::warning('[DetalleVehiculo::mapearCentroIdParaEdicion] No se pudo mapear centro', [
            'centro_id' => $centroId,
            'sede' => $sede
        ]);
        
        return $centroId;
    }

    /**
     * Anular una cita existente (eliminar en C4C)
     */
    public function anularCita(array $citaData): void
    {
        Log::info('🗑️ [DetalleVehiculo::anularCita] ========== INICIO ANULACIÓN CITA ==========');
        Log::info('🗑️ [DetalleVehiculo::anularCita] MÉTODO EJECUTADO - Button clicked!');
        Log::info('[DetalleVehiculo::anularCita] Iniciando anulación de cita', [
            'cita_id' => $citaData['id'] ?? 'N/A',
            'numero_cita' => $citaData['numero_cita'] ?? 'N/A',
            'vehiculo_id' => $this->vehiculoId,
            'status_raw' => $citaData['status_raw'] ?? 'N/A',
            'full_cita_data' => $citaData,
        ]);

        // Validar que tenemos los datos necesarios
        if (empty($citaData['id'])) {
            Log::error('[DetalleVehiculo::anularCita] UUID de cita no disponible');
            
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('No se puede anular la cita: ID no disponible.')
                ->danger()
                ->send();
            return;
        }

        $uuid = $citaData['id'];
        $statusRaw = $citaData['status_raw'] ?? '1';

        // Validaciones de negocio - Solo permitir anular citas en estado 1 (Generada) o 2 (Confirmada)
        if (!in_array($statusRaw, ['1', '2'])) {
            $estadoNombre = $this->mapearEstadoCitaC4C($statusRaw);
            
            Log::warning('[DetalleVehiculo::anularCita] Intento de anular cita en estado no válido', [
                'status_raw' => $statusRaw,
                'estado_nombre' => $estadoNombre,
            ]);
            
            \Filament\Notifications\Notification::make()
                ->title('No se puede anular')
                ->body("No se puede anular una cita en estado: {$estadoNombre}. Solo se pueden anular citas Generadas o Confirmadas.")
                ->warning()
                ->send();
            return;
        }

        // Buscar la cita en la base de datos local para validaciones adicionales
        $appointment = Appointment::where('c4c_uuid', $uuid)->first();
        
        if ($appointment) {
            // Validar estado local también
            if (!in_array($appointment->status, ['pending', 'confirmed', 'generated'])) {
                Log::warning('[DetalleVehiculo::anularCita] Estado local no válido para anulación', [
                    'local_status' => $appointment->status,
                ]);
                
                \Filament\Notifications\Notification::make()
                    ->title('No se puede anular')
                    ->body("El estado local de la cita ({$appointment->status}) no permite la anulación.")
                    ->warning()
                    ->send();
                return;
            }

            // Verificar que no esté ya en proceso de eliminación
            if (in_array($appointment->status, ['deleting', 'delete_failed', 'deleted'])) {
                \Filament\Notifications\Notification::make()
                    ->title('Cita ya procesada')
                    ->body('La cita ya está siendo eliminada o ya fue eliminada.')
                    ->warning()
                    ->send();
                return;
            }
        }

        // Mostrar confirmación al usuario
        $this->dispatch('show-delete-confirmation', 
            $uuid,
            [
                'numero_cita' => $citaData['numero_cita'] ?? 'N/A',
                'fecha_cita' => $citaData['fecha_cita'] ?? 'N/A',
                'hora_cita' => $citaData['hora_cita'] ?? 'N/A',
            ]
        );
    }

    /**
     * Confirmar la anulación después de la confirmación del usuario
     */
    public function confirmarAnulacion(string $uuid, array $citaData): void
    {
        Log::info('[DetalleVehiculo::confirmarAnulacion] Confirmación recibida, procediendo con anulación', [
            'uuid' => $uuid,
        ]);

        try {
            // Buscar el appointment local si existe
            $appointment = Appointment::where('c4c_uuid', $uuid)->first();
            
            // Si no se encuentra por UUID exacto, buscar por patrón similar
            if (!$appointment) {
                Log::info('[DetalleVehiculo::confirmarAnulacion] UUID exacto no encontrado, buscando por patrón', [
                    'uuid_buscado' => $uuid,
                ]);
                
                // Extraer las primeras partes del UUID para búsqueda más flexible
                // Usamos solo las primeras 3 secciones del UUID para mayor flexibilidad
                $uuidBase = substr($uuid, 0, 18); // b7d671af-46bb-1fd0
                Log::info('[DetalleVehiculo::confirmarAnulacion] Patrón de búsqueda generado', [
                    'uuid_original' => $uuid,
                    'patron_busqueda' => $uuidBase . '%',
                ]);
                
                $appointment = Appointment::where('c4c_uuid', 'LIKE', $uuidBase . '%')->first();
                
                if ($appointment) {
                    Log::info('[DetalleVehiculo::confirmarAnulacion] Appointment encontrado por patrón UUID', [
                        'uuid_buscado' => $uuid,
                        'uuid_encontrado' => $appointment->c4c_uuid,
                        'appointment_id' => $appointment->id,
                    ]);
                } else {
                    Log::warning('[DetalleVehiculo::confirmarAnulacion] No se encontró appointment con patrón UUID', [
                        'uuid_buscado' => $uuid,
                        'patron_busqueda' => $uuidBase . '%',
                    ]);
                }
            }
            
            if ($appointment) {
                // Marcar como en proceso de eliminación
                $appointment->update([
                    'status' => 'deleting',
                    'c4c_status' => 'deleting',
                ]);
                
                Log::info('[DetalleVehiculo::confirmarAnulacion] Appointment marcado como deleting', [
                    'appointment_id' => $appointment->id,
                ]);

                // **ENVIAR EMAIL DE CITA CANCELADA** 📧
                $this->enviarEmailCitaCancelada($appointment, 'Cita anulada por solicitud del cliente');
            }

            // Generar job ID único para tracking
            $jobId = Str::uuid()->toString();
            
            // Debug: verificar qué appointment se está enviando al job
            $appointmentId = $appointment?->id ?? 0;
            Log::info('[DetalleVehiculo::confirmarAnulacion] Preparando dispatch del job', [
                'appointment_object' => $appointment ? 'exists' : 'null',
                'appointment_id_computed' => $appointmentId,
                'uuid' => $uuid,
                'job_id' => $jobId,
            ]);

            // Disparar job asíncrono para eliminación en C4C
            DeleteAppointmentC4CJob::dispatch($uuid, $appointmentId, $jobId)
                ->onQueue('c4c-delete');

            Log::info('[DetalleVehiculo::confirmarAnulacion] Job de eliminación disparado', [
                'job_id' => $jobId,
                'uuid' => $uuid,
            ]);

            // Mostrar notificación de éxito
            \Filament\Notifications\Notification::make()
                ->title('Anulación iniciada')
                ->body('Se ha iniciado el proceso de anulación de la cita. La cita será eliminada en unos momentos.')
                ->success()
                ->send();

            // Actualizar la vista removiendo la cita de la lista local temporalmente
            $this->removerCitaDeVista($uuid);

            // Opcional: Recargar citas después de un delay
            $this->dispatch('reload-citas-after-delay');

        } catch (\Exception $e) {
            Log::error('[DetalleVehiculo::confirmarAnulacion] Error al confirmar anulación', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            // Revertir el estado si hubo error
            if ($appointment) {
                $appointment->update([
                    'status' => $appointment->getOriginal('status'),
                    'c4c_status' => $appointment->getOriginal('c4c_status'),
                ]);
            }

            \Filament\Notifications\Notification::make()
                ->title('Error al anular')
                ->body('Error al iniciar la anulación: ' . $e->getMessage())
                ->danger()
                ->send();
        }

        Log::info('✅ [DetalleVehiculo::confirmarAnulacion] ========== ANULACIÓN PROCESADA ==========');
    }

    /**
     * Remover cita de la vista temporalmente (actualización optimista de UI)
     */
    private function removerCitaDeVista(string $uuid): void
    {
        try {
            // Filtrar las citas agendadas para remover la que se está anulando
            $this->citasAgendadas = array_filter($this->citasAgendadas, function($cita) use ($uuid) {
                return ($cita['id'] ?? '') !== $uuid;
            });

            // Reindexar el array
            $this->citasAgendadas = array_values($this->citasAgendadas);

            Log::info('[DetalleVehiculo::removerCitaDeVista] Cita removida de la vista', [
                'uuid' => $uuid,
                'citas_restantes' => count($this->citasAgendadas),
            ]);

        } catch (\Exception $e) {
            Log::error('[DetalleVehiculo::removerCitaDeVista] Error al remover cita de vista', [
                'uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Determinar el tipo de servicio basado en los datos de la cita
     */
    protected function determinarTipoServicio(?string $maintenanceType, ?string $serviceMode): string
    {
        if ($maintenanceType) {
            return match ($maintenanceType) {
                'mantenimiento_5000' => 'Mantenimiento 5,000 Km',
                'mantenimiento_10000' => 'Mantenimiento 10,000 Km',
                'mantenimiento_15000' => 'Mantenimiento 15,000 Km',
                'mantenimiento_20000' => 'Mantenimiento 20,000 Km',
                'mantenimiento_25000' => 'Mantenimiento 25,000 Km',
                'mantenimiento_30000' => 'Mantenimiento 30,000 Km',
                'reparacion_general' => 'Reparación General',
                'diagnostico' => 'Diagnóstico',
                'revision_tecnica' => 'Revisión Técnica',
                default => ucfirst(str_replace('_', ' ', $maintenanceType)),
            };
        }

        if ($serviceMode) {
            return match ($serviceMode) {
                'express' => 'Servicio Express',
                'normal' => 'Servicio Normal',
                'premium' => 'Servicio Premium',
                default => ucfirst($serviceMode),
            };
        }

        return 'Servicio no especificado';
    }

    /**
     * Mapear el estado de la cita a un formato legible
     */
    protected function mapearEstadoCita(string $status): string
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'confirmed' => 'Confirmada',
            'in_progress' => 'En progreso',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            default => ucfirst($status),
        };
    }

    /**
     * Determinar el tipo de pago basado en los datos de la cita
     */
    protected function determinarTipoPago(Appointment $cita): string
    {
        // Si tiene package_id, probablemente es prepagado
        if (!empty($cita->package_id)) {
            return 'Prepagado';
        }

        // Si tiene comentarios que indican prepago
        if ($cita->comments && str_contains(strtolower($cita->comments), 'prepag')) {
            return 'Prepagado';
        }

        return 'Por definir';
    }

    /**
     * Determinar el tipo de servicio basado en los datos de C4C
     */
    protected function determinarTipoServicioC4C(array $cita): string
    {
        // Usar el subject_name como base para el tipo de servicio
        $subjectName = $cita['subject_name'] ?? '';

        if (!empty($subjectName)) {
            // Extraer información útil del subject_name
            if (str_contains(strtolower($subjectName), 'mantenimiento')) {
                return 'Mantenimiento Programado';
            }
            if (str_contains(strtolower($subjectName), 'reparacion')) {
                return 'Reparación';
            }
            if (str_contains(strtolower($subjectName), 'revision')) {
                return 'Revisión Técnica';
            }
            if (str_contains(strtolower($subjectName), 'diagnostico')) {
                return 'Diagnóstico';
            }

            // Si no coincide con patrones conocidos, usar el subject_name limpio
            return $subjectName;
        }

        // Fallback basado en otros campos
        if (!empty($cita['vehicle_model'])) {
            return 'Servicio para ' . $cita['vehicle_model'];
        }

        return 'Servicio programado';
    }

    /**
     * Mapear el estado de la cita de C4C a formato legible
     */
    protected function mapearEstadoCitaC4C(string $appointmentStatus): string
    {
        return match ($appointmentStatus) {
            '1' => 'Generada',
            '2' => 'Confirmada',
            '3' => 'En proceso',
            '4' => 'Diferida',
            '5' => 'Completada',
            '6' => 'Cancelada',
            default => 'Estado ' . $appointmentStatus,
        };
    }

    /**
     * Formatear fecha de C4C (formato YYYY-MM-DD)
     */
    protected function formatearFechaC4C(string $fecha): string
    {
        if (empty($fecha) || $fecha === '0000-00-00') {
            return '-';
        }

        try {
            $fechaObj = \DateTime::createFromFormat('Y-m-d', $fecha);
            return $fechaObj ? $fechaObj->format('d/m/Y') : '-';
        } catch (\Exception $e) {
            return '-';
        }
    }

    /**
     * Formatear hora de C4C
     */
    protected function formatearHoraC4C(string $hora): string
    {
        if (empty($hora)) {
            return '';
        }

        try {
            // Crear un objeto Carbon con la zona horaria UTC
            $horaUTC = \Carbon\Carbon::createFromFormat('H:i:s', $hora, 'UTC');
            
            // Convertir a la zona horaria de Perú (UTC-5)
            $horaPeru = $horaUTC->copy()->setTimezone('America/Lima');
            
            // Devolver solo la hora y minutos en formato HH:MM
            return $horaPeru->format('H:i');
            
        } catch (\Exception $e) {
            // Si hay algún error, intentar con el formato antiguo como fallback
            try {
                // Si viene como "00", mostrar como hora válida
                if ($hora === '00') {
                    return '00:00';
                }

                // Si viene como "00:00:00", mostrar solo HH:MM
                if ($hora === '00:00:00') {
                    return '00:00';
                }

                // Si viene solo como número (ej: "14"), convertir a formato hora
                if (is_numeric($hora) && strlen($hora) <= 2) {
                    return str_pad($hora, 2, '0', STR_PAD_LEFT) . ':00';
                }

                // Si viene en formato HH:MM:SS, extraer solo HH:MM
                if (str_contains($hora, ':')) {
                    $partes = explode(':', $hora);
                    return $partes[0] . ':' . ($partes[1] ?? '00');
                }

                return $hora;
            } catch (\Exception $e) {
                return '';
            }
        }
    }

    /**
     * Formatear fecha y hora de entrega estimada de C4C
     */
    protected function formatearFechaHoraEntregaC4C(array $cita): string
    {
        $fechaSalida = $cita['exit_date'] ?? $cita['scheduled_end_date'] ?? '';
        $horaSalida = $cita['exit_time'] ?? '';

        if (empty($fechaSalida)) {
            return '-';
        }

        $fechaFormateada = $this->formatearFechaC4C($fechaSalida);
        $horaFormateada = $this->formatearHoraC4C($horaSalida);

        if ($fechaFormateada === '-') {
            return '-';
        }

        if ($horaFormateada === '-') {
            return $fechaFormateada;
        }

        return $fechaFormateada . ' ' . $horaFormateada;
    }

    /**
     * Obtener información completa del estado para mostrar dinámicamente
     * Ahora incluye lógica basada en datos SAP
     */
    protected function obtenerInformacionEstadoCompleta(string $appointmentStatus, array $currentAppointmentData = null): array
    {
        Log::info('📊 [ESTADO-FLOW] === MÉTODO obtenerInformacionEstadoCompleta EJECUTÁNDOSE ===', [
            'appointment_status' => $appointmentStatus,
            'citas_agendadas_count' => count($this->citasAgendadas),
            'tiene_datos_sap' => !empty($this->datosAsesorSAP),
            'current_appointment_provided' => !empty($currentAppointmentData),
            'current_appointment_keys' => $currentAppointmentData ? array_keys($currentAppointmentData) : 'null'
        ]);
        $estados = [
            '1' => [
                'codigo' => '1',
                'nombre' => 'Generada',
                'etapas' => [
                    'cita_confirmada' => ['activo' => false, 'completado' => true],
                    'en_trabajo' => ['activo' => false, 'completado' => false],
                    'trabajo_concluido' => ['activo' => false, 'completado' => false],
                    'entregado' => ['activo' => false, 'completado' => false],
                ]
            ],
            '2' => [
                'codigo' => '2',
                'nombre' => 'Confirmada',
                'etapas' => [
                    'cita_confirmada' => ['activo' => false, 'completado' => true],
                    'en_trabajo' => ['activo' => false, 'completado' => false], // ✅ CORREGIDO: Inicialmente solo confirmada
                    'trabajo_concluido' => ['activo' => false, 'completado' => false],
                    'entregado' => ['activo' => false, 'completado' => false],
                ]
            ],
            '3' => [
                'codigo' => '3',
                'nombre' => 'En proceso',
                'etapas' => [
                    'cita_confirmada' => ['activo' => false, 'completado' => true],
                    'en_trabajo' => ['activo' => false, 'completado' => true],
                    'trabajo_concluido' => ['activo' => true, 'completado' => true],
                    'entregado' => ['activo' => false, 'completado' => false],
                ]
            ],
            '4' => [
                'codigo' => '4',
                'nombre' => 'Diferida',
                'etapas' => [
                    'cita_confirmada' => ['activo' => true, 'completado' => true],
                    'en_trabajo' => ['activo' => false, 'completado' => false],
                    'trabajo_concluido' => ['activo' => false, 'completado' => false],
                    'entregado' => ['activo' => false, 'completado' => false],
                ]
            ],
            '5' => [
                'codigo' => '5',
                'nombre' => 'Completada',
                'etapas' => [
                    'cita_confirmada' => ['activo' => false, 'completado' => true],
                    'en_trabajo' => ['activo' => false, 'completado' => true],
                    'trabajo_concluido' => ['activo' => false, 'completado' => true],
                    'entregado' => ['activo' => true, 'completado' => true],
                ]
            ],
            '6' => [
                'codigo' => '6',
                'nombre' => 'Cancelada',
                'etapas' => [
                    'cita_confirmada' => ['activo' => false, 'completado' => false],
                    'en_trabajo' => ['activo' => false, 'completado' => false],
                    'trabajo_concluido' => ['activo' => false, 'completado' => false],
                    'entregado' => ['activo' => false, 'completado' => false],
                ]
            ],
        ];

        $estadoBase = $estados[$appointmentStatus] ?? $estados['1'];

        // Aplicar lógica SAP para modificar estados dinámicamente
        if ($this->datosAsesorSAP) {
            $estadoBase = $this->aplicarLogicaSAPAEstado($estadoBase, $currentAppointmentData);
        }

        return $estadoBase;
    }

    /**
     * Aplicar lógica SAP para modificar estados dinámicamente
     * Lógica progresiva según el proceso actual
     */
    protected function aplicarLogicaSAPAEstado(array $estadoBase, array $currentAppointmentData = null): array
    {
        // Obtener datos de SAP
        $tieneFechaUltServ = $this->datosAsesorSAP['tiene_fecha_ult_serv'] ?? false;
        $tieneFechaFactura = $this->datosAsesorSAP['tiene_fecha_factura'] ?? false;
        $fechaUltServ = $this->datosAsesorSAP['fecha_ult_serv'] ?? null;
        
        // Usar los datos de la cita actual si están disponibles
        $fechaCitaActual = null;
        if ($currentAppointmentData) {
            // Intentar obtener fecha de diferentes campos
            if (isset($currentAppointmentData['scheduled_start_date'])) {
                $fechaCitaActual = $currentAppointmentData['scheduled_start_date'];
            } elseif (isset($currentAppointmentData['appointment_date'])) {
                $fechaCitaActual = $currentAppointmentData['appointment_date'];
            }
            
            // Normalizar fecha si se obtuvo
            if ($fechaCitaActual) {
                // Si es objeto Carbon, convertir a string
                if (is_object($fechaCitaActual) && method_exists($fechaCitaActual, 'format')) {
                    $fechaCitaActual = $fechaCitaActual->format('Y-m-d');
                }
                // Si es string, normalizar formato
                elseif (is_string($fechaCitaActual)) {
                    $fechaCitaActual = substr($fechaCitaActual, 0, 10); // Solo YYYY-MM-DD
                }
            }
        }
        
        // FALLBACK: Solo si no se obtuvo fecha de currentAppointmentData, usar método anterior
        if (!$fechaCitaActual) {
            // Obtener la fecha de la cita del array de citas transformado
            $citaActual = $this->citasAgendadas[0] ?? null;
            
            // Intentar obtener la fecha de la cita de la base de datos local primero
            $fechaCitaActual = null;
            if ($citaActual) {
                // Intentar obtener el ID de diferentes maneras
                $citaId = null;
                $candidatosId = [];
                
                // 1. Verificar si el ID está en el formato numérico directo
                if (isset($citaActual['id']) && is_numeric($citaActual['id'])) {
                    $candidatosId[] = (int)$citaActual['id'];
                } 
                // 2. Verificar si el ID está en el formato 'local-123'
                elseif (isset($citaActual['id']) && strpos($citaActual['id'], 'local-') === 0) {
                    $candidatosId[] = (int)substr($citaActual['id'], 6);
                }
                // 3. Verificar si hay un número de cita disponible
                if (isset($citaActual['numero_cita'])) {
                    if (is_numeric($citaActual['numero_cita'])) {
                        $candidatosId[] = (int)$citaActual['numero_cita'];
                    } elseif (is_string($citaActual['numero_cita']) && strpos($citaActual['numero_cita'], 'CITA-') === 0) {
                        $candidatosId[] = (int)substr($citaActual['numero_cita'], 5);
                    }
                }
                
                // Buscar cita en la base de datos
                foreach ($candidatosId as $index => $id) {
                    $citaLocal = \App\Models\Appointment::find($id);
                    if ($citaLocal) {
                        $fechaCitaActual = $citaLocal->appointment_date ? $citaLocal->appointment_date->format('Y-m-d') : null;
                        break; // Usar el primer ID que encuentre
                    }
                }
            }
            
            // Si no se pudo obtener de la base de datos local, usar el valor del array
            if (!$fechaCitaActual) {
                $fechaCitaActual = $citaActual['fecha_cita'] ?? null;
                if ($fechaCitaActual) {
                    // Intentar convertir formato d/m/Y a Y-m-d si es necesario
                    if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fechaCitaActual)) {
                        try {
                            $fechaCitaActual = \Carbon\Carbon::createFromFormat('d/m/Y', $fechaCitaActual)->format('Y-m-d');
                        } catch (\Exception $e) {
                            // Mantener fecha original si hay error
                        }
                    }
                }
            }
        }
        
        // Asegurarse de que las fechas estén en el mismo formato para comparación (YYYY-MM-DD)
        if ($fechaUltServ) {
            $fechaUltServ = substr($fechaUltServ, 0, 10);
        }
        
        if ($fechaCitaActual) {
            $fechaCitaActual = substr($fechaCitaActual, 0, 10);
        }
        
        // CASO 1: Si tiene fecha de FACTURA -> TRABAJO CONCLUIDO (tiene prioridad sobre los demás estados)
        if ($tieneFechaFactura) {
            $estadoBase['etapas']['cita_confirmada']['activo'] = false;
            $estadoBase['etapas']['cita_confirmada']['completado'] = true;
            
            $estadoBase['etapas']['en_trabajo']['activo'] = false;
            $estadoBase['etapas']['en_trabajo']['completado'] = true;
            
            $estadoBase['etapas']['trabajo_concluido']['activo'] = true;
            $estadoBase['etapas']['trabajo_concluido']['completado'] = true;
            
            return $estadoBase;
        }
        
        // CASO 2: Si tiene fecha de servicio reciente -> EN TRABAJO
        if ($tieneFechaUltServ && $fechaUltServ) {
            // Verificar si la fecha de servicio es igual a la fecha de la cita (comparación directa de strings)
            if ($fechaCitaActual && $fechaUltServ == $fechaCitaActual) {
                $estadoBase['etapas']['cita_confirmada']['activo'] = false;
                $estadoBase['etapas']['cita_confirmada']['completado'] = true;
                
                $estadoBase['etapas']['en_trabajo']['activo'] = true;
                $estadoBase['etapas']['en_trabajo']['completado'] = false;
                
                $estadoBase['etapas']['trabajo_concluido']['activo'] = false;
                $estadoBase['etapas']['trabajo_concluido']['completado'] = false;
            }
        }
        
        return $estadoBase;
    }
    
    /**
     * Compara si dos fechas son iguales, independientemente de su formato
     * @param string|null $fechaSAP Fecha de SAP (formato YYYY-MM-DD)
     * @param string|null $fechaCita Fecha de la cita (puede estar en formato d/m/Y o YYYY-MM-DD)
     * @return bool
     */
    protected function fechasCoinciden(?string $fechaSAP, ?string $fechaCita): bool
    {
        if (empty($fechaSAP) || empty($fechaCita)) {
            return false;
        }

        try {
            // Intentar parsear las fechas con Carbon
            $carbonSAP = null;
            $carbonCita = null;
            $formatos = ['Y-m-d', 'd/m/Y', 'Y-m-d H:i:s', 'd/m/Y H:i:s', 'Ymd'];
            
            // Intentar parsear fecha SAP
            foreach ($formatos as $formato) {
                try {
                    $carbonSAP = \Carbon\Carbon::createFromFormat($formato, $fechaSAP);
                    if ($carbonSAP) break;
                } catch (\Exception $e) {
                    // Continuar con el siguiente formato
                    continue;
                }
            }
            
            // Intentar parsear fecha Cita
            foreach ($formatos as $formato) {
                try {
                    $carbonCita = \Carbon\Carbon::createFromFormat($formato, $fechaCita);
                    if ($carbonCita) break;
                } catch (\Exception $e) {
                    // Continuar con el siguiente formato
                    continue;
                }
            }
            
            // Si no se pudo parsear alguna fecha, intentar con parse genérico
            if (!$carbonSAP) {
                try {
                    $carbonSAP = \Carbon\Carbon::parse($fechaSAP);
                } catch (\Exception $e) {
                    // No se pudo parsear
                }
            }
            
            if (!$carbonCita) {
                try {
                    $carbonCita = \Carbon\Carbon::parse($fechaCita);
                } catch (\Exception $e) {
                    // No se pudo parsear
                }
            }
            
            if (!$carbonSAP || !$carbonCita) {
                return false;
            }
            
            // Normalizar a fecha sin hora para comparación
            $fechaSAPNormalizada = $carbonSAP->format('Y-m-d');
            $fechaCitaNormalizada = $carbonCita->format('Y-m-d');
            
            // Comparar las fechas normalizadas
            $coinciden = $fechaSAPNormalizada === $fechaCitaNormalizada;
            
            return $coinciden;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Enriquecer cita con datos SAP
     */
    protected function enriquecerCitaConDatosSAP(array $cita): array
    {
        $citaEnriquecida = [
            'probable_entrega' => $this->formatearFechaHoraEntregaC4C($cita),
            'asesor' => $cita['telemarketing_advisor'] ?? 'Por asignar',
            'whatsapp' => $cita['client_phone'] ?? '-',
            'correo' => $this->obtenerCorreoDinamico($cita),
            'fecha_factura' => '',
            'hora_factura' => '',
            'rut_pdf' => '',
        ];

        // Enriquecer con datos SAP si están disponibles
        if ($this->datosAsesorSAP) {
            // Solo usar datos SAP si están realmente disponibles y válidos
            if (!empty($this->datosAsesorSAP['nombre_asesor'])) {
                $citaEnriquecida['asesor'] = $this->datosAsesorSAP['nombre_asesor'];
            } else {
                $citaEnriquecida['asesor'] = 'Por asignar';
            }
            
            if (!empty($this->datosAsesorSAP['telefono_asesor'])) {
                $citaEnriquecida['whatsapp'] = $this->datosAsesorSAP['telefono_asesor'];
            } else {
                $citaEnriquecida['whatsapp'] = 'Por asignar';
            }
            
            if (!empty($this->datosAsesorSAP['correo_asesor'])) {
                $citaEnriquecida['correo'] = $this->datosAsesorSAP['correo_asesor'];
            } else {
                $citaEnriquecida['correo'] = 'Por asignar';
            }
            
            // Formatear fecha y hora de entrega SAP solo si son válidas
            if ($this->datosAsesorSAP['tiene_fecha_entrega']) {
                $fechaEntrega = $this->formatearFechaSAP($this->datosAsesorSAP['fecha_entrega']);
                $horaEntrega = $this->datosAsesorSAP['hora_entrega'];
                $citaEnriquecida['probable_entrega'] = $fechaEntrega . ' ' . $horaEntrega;
            } else {
                $citaEnriquecida['probable_entrega'] = 'Por asignar';
            }
            
            // Si hay fecha de factura SAP válida, agregar datos de facturación
            if ($this->datosAsesorSAP['tiene_fecha_factura']) {
                $citaEnriquecida['fecha_factura'] = $this->formatearFechaSAP($this->datosAsesorSAP['fecha_factura']);
                $citaEnriquecida['hora_factura'] = $this->datosAsesorSAP['hora_factura'];
                $citaEnriquecida['rut_pdf'] = $this->datosAsesorSAP['rut_pdf'];
            }
        }

        return $citaEnriquecida;
    }

    /**
     * Obtener correo dinámico del webservice o fallback
     */
    protected function obtenerCorreoDinamico(array $cita): string
    {
        // Intentar obtener correo de diferentes campos del webservice
        $correo = $cita['client_email'] ??
                  $cita['customer_email'] ??
                  $cita['contact_email'] ??
                  null;

        // Si no hay correo en el webservice, usar el fallback
        return $correo ?: 'info@mitsui.com.pe';
    }

    /**
     * Crear cliente SOAP para SAP usando WSDL local
     */
    protected function crearClienteSAP(): ?SoapClient
    {
        if ($this->soapClient !== null) {
            return $this->soapClient;
        }

        try {
            $usuario = config('services.sap_3p.usuario');
            $password = config('services.sap_3p.password');

            if (empty($usuario) || empty($password)) {
                Log::error('[DetalleVehiculo] Configuración SAP incompleta');
                return null;
            }

            // Usar WSDL local como lo hace VehiculoSoapService
            $wsdlLocal = storage_path('wsdl/vehiculos.wsdl');

            if (!file_exists($wsdlLocal)) {
                Log::error('[DetalleVehiculo] WSDL local no encontrado: ' . $wsdlLocal);
                return null;
            }

            $opciones = [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => 10,
                'default_socket_timeout' => 10,
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                    'http' => [
                        'timeout' => 10,
                    ],
                ]),
                'login' => $usuario,
                'password' => $password,
            ];

            $this->soapClient = new SoapClient($wsdlLocal, $opciones);
            Log::info('[DetalleVehiculo] Cliente SOAP SAP creado exitosamente usando WSDL local');

            return $this->soapClient;

        } catch (\Exception $e) {
            Log::error('[DetalleVehiculo] Error al crear cliente SOAP SAP: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cargar datos del vehículo desde SAP
     */
    protected function cargarDatosVehiculoDesdeSAP(string $placa): void
    {
        $this->cargandoDesdeSAP = true;

        try {
            Log::info("[DetalleVehiculo] Iniciando carga de datos SAP para placa: {$placa}");

            // Crear cliente SOAP
            $cliente = $this->crearClienteSAP();
            if (!$cliente) {
                throw new \Exception('No se pudo crear el cliente SOAP');
            }

            // FLUJO CORRECTO:
            // 1. Obtener DNI del usuario logueado
            $documentoCliente = $this->obtenerDocumentoCliente();

            if (!$documentoCliente) {
                throw new \Exception('No se pudo obtener el documento del usuario logueado');
            }

            // 2. Sincronizar TODOS los vehículos del usuario con SAP
            $this->sincronizarVehiculosUsuarioConSAP($cliente, $documentoCliente);

            // 3. Buscar el vehículo específico en la BD (ya actualizada)
            $vehiculoActualizado = $this->buscarVehiculoEnBD($placa);

            if ($vehiculoActualizado) {
                // 4. Cargar datos del vehículo desde BD actualizada
                $this->cargarDatosVehiculo($vehiculoActualizado);

                // 5. Cargar historial de servicios específico de esta placa
                $this->cargarHistorialServiciosSAP($cliente, $placa);
            } else {
                throw new \Exception("El vehículo con placa {$placa} no pertenece al usuario logueado");
            }

            // TERCERO: Cargar datos del asesor si hay servicio en proceso
            $this->cargarDatosAsesorSAP($cliente, $placa);

            // CUARTO: Cargar prepagos disponibles
            $this->cargarPrepagosSAP($cliente, $placa);

            Log::info('[DetalleVehiculo] Datos SAP cargados exitosamente');

        } catch (\Exception $e) {
            Log::error('[DetalleVehiculo] Error al cargar datos desde SAP: ' . $e->getMessage());

            // Establecer valores de error
            $this->vehiculo = [
                'modelo' => 'Error al consultar SAP',
                'kilometraje' => 'No disponible',
                'placa' => $placa,
                'anio' => 'No disponible',
                'marca' => 'No disponible',
                'color' => 'No disponible',
            ];

            $this->mantenimiento = [
                'ultimo' => 'Error al consultar SAP',
                'fecha' => 'No disponible',
                'vencimiento' => 'No disponible',
                'disponibles' => ['Error al consultar datos'],
            ];
        } finally {
            $this->cargandoDesdeSAP = false;
        }
    }

    /**
     * Sincronizar TODOS los vehículos del usuario logueado con SAP usando Z3PF_GETLISTAVEHICULOS
     */
    protected function sincronizarVehiculosUsuarioConSAP(SoapClient $cliente, string $documentoCliente): void
    {
        try {
            Log::info("[DetalleVehiculo] Sincronizando vehículos del usuario con DNI: {$documentoCliente}");

            // Usar VehiculoSoapService que ya tiene la lógica implementada
            $vehiculoService = app(VehiculoSoapService::class);
            $marcas = ['Z01', 'Z02', 'Z03']; // TOYOTA, LEXUS, HINO

            // Obtener TODOS los vehículos del usuario desde SAP
            $vehiculosSAP = $vehiculoService->getVehiculosDesdeSAP($documentoCliente, $marcas);

            if ($vehiculosSAP->isNotEmpty()) {
                Log::info("[DetalleVehiculo] SAP devolvió {$vehiculosSAP->count()} vehículos para el usuario");

                // Obtener usuario logueado
                $user = Auth::user();

                // Sincronizar cada vehículo en la BD
                foreach ($vehiculosSAP as $vehiculoSAP) {
                    $this->sincronizarVehiculoIndividual($user, $vehiculoSAP);
                }

                Log::info("[DetalleVehiculo] Sincronización completada exitosamente");
            } else {
                Log::warning("[DetalleVehiculo] SAP no devolvió vehículos para el usuario con DNI: {$documentoCliente}");
            }

        } catch (\Exception $e) {
            Log::error("[DetalleVehiculo] Error al sincronizar vehículos con SAP: " . $e->getMessage());
            // No lanzar excepción para que no interrumpa el flujo
        }
    }

    /**
     * Sincronizar un vehículo individual en la BD
     */
    protected function sincronizarVehiculoIndividual($user, array $vehiculoSAP): void
    {
        try {
            $placa = $vehiculoSAP['numpla'] ?? null;

            if (!$placa) {
                Log::warning("[DetalleVehiculo] Vehículo SAP sin placa, saltando");
                return;
            }

            // Buscar si el vehículo ya existe en la BD
            $vehiculoExistente = Vehicle::where('license_plate', $placa)
                                      ->where('user_id', $user->id)
                                      ->first();

            if ($vehiculoExistente) {
                // Actualizar vehículo existente
                $vehiculoExistente->update([
                    'model' => $vehiculoSAP['modver'] ?? $vehiculoExistente->model,
                    'year' => $vehiculoSAP['aniomod'] ?? $vehiculoExistente->year,
                    'brand_code' => $vehiculoSAP['marca_codigo'] ?? $vehiculoExistente->brand_code,
                    'brand_name' => $this->obtenerNombreMarca($vehiculoSAP['marca_codigo'] ?? ''),
                    'updated_at' => now(),
                ]);

                Log::info("[DetalleVehiculo] Vehículo actualizado en BD: {$placa}");
            } else {
                // Crear nuevo vehículo
                Vehicle::create([
                    'user_id' => $user->id,
                    'vehicle_id' => $vehiculoSAP['vhclie'] ?? 'VH' . uniqid(),
                    'license_plate' => $placa,
                    'model' => $vehiculoSAP['modver'] ?? 'Modelo no especificado',
                    'year' => $vehiculoSAP['aniomod'] ?? date('Y'),
                    'brand_code' => $vehiculoSAP['marca_codigo'] ?? 'Z01',
                    'brand_name' => $this->obtenerNombreMarca($vehiculoSAP['marca_codigo'] ?? 'Z01'),
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                Log::info("[DetalleVehiculo] Nuevo vehículo creado en BD: {$placa}");
            }

        } catch (\Exception $e) {
            Log::error("[DetalleVehiculo] Error al sincronizar vehículo individual: " . $e->getMessage());
        }
    }

    /**
     * Buscar vehículo específico en la BD
     */
    protected function buscarVehiculoEnBD(string $placa): ?Vehicle
    {
        try {
            $user = Auth::user();

            $vehiculo = Vehicle::where('license_plate', $placa)
                              ->where('user_id', $user->id)
                              ->first();

            if ($vehiculo) {
                Log::info("[DetalleVehiculo] Vehículo encontrado en BD: {$placa}");
                return $vehiculo;
            } else {
                Log::warning("[DetalleVehiculo] Vehículo no encontrado en BD: {$placa}");
                return null;
            }

        } catch (\Exception $e) {
            Log::error("[DetalleVehiculo] Error al buscar vehículo en BD: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Buscar datos básicos del vehículo usando Z3PF_GETLISTAVEHICULOS desde SAP
     * Si no se encuentra, usa datos de la base de datos local como fallback
     */
    protected function buscarDatosVehiculoSAP(SoapClient $cliente, string $placa, ?string $documentoCliente = null): ?array
    {
        try {
            Log::info("[DetalleVehiculo] Buscando datos del vehículo con placa: {$placa}");

            // PRIMERO: Intentar obtener datos desde SAP usando Z3PF_GETLISTAVEHICULOS
            if ($documentoCliente) {
                Log::info("[DetalleVehiculo] Consultando SAP Z3PF_GETLISTAVEHICULOS para placa: {$placa} con documento: {$documentoCliente}");

                // Usar el servicio VehiculoSoapService que ya tiene la lógica implementada
                $vehiculoService = app(VehiculoSoapService::class);

                // Buscar en todas las marcas usando el documento del cliente
                $marcas = ['Z01', 'Z02', 'Z03']; // TOYOTA, LEXUS, HINO

                $vehiculosSAP = $vehiculoService->getVehiculosDesdeSAP($documentoCliente, $marcas);
            } else {
                Log::warning("[DetalleVehiculo] No se proporcionó documento del cliente, saltando consulta SAP");
                $vehiculosSAP = collect();
            }

            // Buscar el vehículo específico por placa
            $vehiculoEncontrado = $vehiculosSAP->first(function ($vehiculo) use ($placa) {
                return isset($vehiculo['numpla']) && trim($vehiculo['numpla']) === trim($placa);
            });

            if ($vehiculoEncontrado) {
                Log::info("[DetalleVehiculo] Vehículo encontrado en SAP Z3PF_GETLISTAVEHICULOS");
                return [
                    'vhclie' => $vehiculoEncontrado['vhclie'] ?? '',
                    'numpla' => $vehiculoEncontrado['numpla'] ?? $placa,
                    'aniomod' => $vehiculoEncontrado['aniomod'] ?? '',
                    'modver' => $vehiculoEncontrado['modver'] ?? '',
                    'marca_codigo' => $vehiculoEncontrado['marca_codigo'] ?? 'Z01',
                ];
            }

            Log::info("[DetalleVehiculo] Vehículo no encontrado en SAP, intentando BD local");

            // SEGUNDO: Si no se encuentra en SAP, intentar base de datos local
            $vehiculoLocal = \App\Models\Vehicle::where('license_plate', $placa)->first();

            if ($vehiculoLocal) {
                Log::info("[DetalleVehiculo] Vehículo encontrado en BD local");
                return [
                    'vhclie' => $vehiculoLocal->vehicle_id,
                    'numpla' => $vehiculoLocal->license_plate,
                    'aniomod' => (string) $vehiculoLocal->year,
                    'modver' => $vehiculoLocal->model,
                    'marca_codigo' => $vehiculoLocal->brand_code,
                ];
            }

            // TERCERO: Si no se encuentra en ningún lado, retornar null
            Log::warning("[DetalleVehiculo] Vehículo con placa {$placa} no encontrado en SAP ni en BD local");
            return null;

        } catch (\Exception $e) {
            Log::error("[DetalleVehiculo] Error al buscar datos del vehículo: " . $e->getMessage());

            // Como fallback, intentar BD local
            try {
                $vehiculoLocal = \App\Models\Vehicle::where('license_plate', $placa)->first();
                if ($vehiculoLocal) {
                    Log::info("[DetalleVehiculo] Usando BD local como fallback después del error SAP");
                    return [
                        'vhclie' => $vehiculoLocal->vehicle_id,
                        'numpla' => $vehiculoLocal->license_plate,
                        'aniomod' => (string) $vehiculoLocal->year,
                        'modver' => $vehiculoLocal->model,
                        'marca_codigo' => $vehiculoLocal->brand_code,
                    ];
                }
            } catch (\Exception $fallbackError) {
                Log::error("[DetalleVehiculo] Error también en fallback BD local: " . $fallbackError->getMessage());
            }

            return null;
        }
    }

    /**
     * Actualizar datos básicos del vehículo con datos de Z3PF_GETLISTAVEHICULOS
     */
    protected function actualizarDatosBasicosVehiculo(array $datosVehiculo, string $placa): void
    {
        try {
            Log::info("[DetalleVehiculo] Actualizando datos básicos del vehículo con datos de SAP");

            $this->vehiculo = [
                'modelo' => $datosVehiculo['modver'] ?? 'No disponible',
                'kilometraje' => 'Consultando SAP...', // Se actualizará con Z3PF_GETLISTASERVICIOS
                'placa' => $placa,
                'anio' => $datosVehiculo['aniomod'] ?? 'No disponible',
                'marca' => $this->obtenerNombreMarca($datosVehiculo['marca_codigo'] ?? ''),
                'color' => 'No disponible', // Este campo no viene en Z3PF_GETLISTAVEHICULOS
                'fuente' => 'SAP',
            ];

            Log::info("[DetalleVehiculo] Datos básicos actualizados:", $this->vehiculo);

        } catch (\Exception $e) {
            Log::error("[DetalleVehiculo] Error al actualizar datos básicos: " . $e->getMessage());
        }
    }

    /**
     * Cargar historial de servicios desde SAP usando Z3PF_GETLISTASERVICIOS
     */
    protected function cargarHistorialServiciosSAP(SoapClient $cliente, string $placa): void
    {
        try {
            Log::info("[DetalleVehiculo] Consultando historial de servicios SAP para placa: {$placa}");

            // Llamar a Z3PF_GETLISTASERVICIOS con la placa
            $parametros = ['PI_PLACA' => $placa];
            $respuesta = $cliente->Z3PF_GETLISTASERVICIOS($parametros);

            Log::info('[DetalleVehiculo] Respuesta Z3PF_GETLISTASERVICIOS:', (array) $respuesta);

            // Extraer datos desde Z3PF_GETLISTASERVICIOS
            $kilometraje = $respuesta->PE_KILOMETRAJE ?? 0;
            $fechaUltimoServicio = $respuesta->PE_ULT_FEC_SERVICIO ?? '';
            $fechaVencimientoPrepago = $respuesta->PE_ULT_FEC_PREPAGO ?? '';
            $ultimoKm = $respuesta->PE_ULT_KM_ ?? 0;

            // Actualizar SOLO el kilometraje (los demás datos ya vienen de la BD actualizada)
            if ($kilometraje > 0) {
                $this->vehiculo['kilometraje'] = number_format($kilometraje, 0, '.', ',') . ' Km';
                Log::info("[DetalleVehiculo] Kilometraje actualizado desde Z3PF_GETLISTASERVICIOS: {$this->vehiculo['kilometraje']}");
            }

            // Actualizar datos de mantenimiento
            $fechaFormateada = $this->formatearFechaSAP($fechaUltimoServicio);
            $vencimientoFormateado = $this->formatearFechaSAP($fechaVencimientoPrepago);

            $this->mantenimiento = [
                'ultimo' => 'Procesando historial...', // Se actualizará con el historial real
                'fecha' => $fechaFormateada,
                'vencimiento' => $vencimientoFormateado,
                'disponibles' => ['Consultando prepagos...'],
                'ultimo_km' => $ultimoKm > 0 ? number_format($ultimoKm, 0, '.', ',') . ' Km' : 'No disponible',
            ];

            // PROCESAR HISTORIAL DE SERVICIOS (TT_LISSRV) - ESTO ES LO PRINCIPAL
            if (isset($respuesta->TT_LISSRV) && !empty($respuesta->TT_LISSRV)) {
                Log::info('[DetalleVehiculo] Procesando historial de servicios desde TT_LISSRV');
                $this->procesarHistorialServiciosSAP($respuesta->TT_LISSRV);
            } else {
                Log::warning('[DetalleVehiculo] No se encontró historial de servicios en TT_LISSRV');
                $this->historialServicios = collect();
            }

        } catch (SoapFault $e) {
            Log::error('[DetalleVehiculo] Error SOAP al consultar Z3PF_GETLISTASERVICIOS: ' . $e->getMessage());
            $this->historialServicios = collect();
            // No lanzar excepción para que no interrumpa el flujo
        }
    }

    /**
     * Obtener el documento del cliente desde el contexto de usuario autenticado
     */
    protected function obtenerDocumentoCliente(): ?string
    {
        try {
            $user = Auth::user();

            if (!$user) {
                Log::warning("[DetalleVehiculo] No hay usuario autenticado");
                return null;
            }

            // Obtener el documento del usuario autenticado
            $documento = $user->document_number ?? null;

            if ($documento) {
                Log::info("[DetalleVehiculo] Documento del cliente obtenido: {$documento}");
                return $documento;
            } else {
                Log::warning("[DetalleVehiculo] Usuario autenticado no tiene document_number");
                return null;
            }

        } catch (\Exception $e) {
            Log::error("[DetalleVehiculo] Error al obtener documento del cliente: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtener el nombre de la marca basado en el código
     */
    protected function obtenerNombreMarca(string $codigoMarca): string
    {
        $marcas = [
            'Z01' => 'TOYOTA',
            'Z02' => 'LEXUS',
            'Z03' => 'HINO',
        ];

        return $marcas[$codigoMarca] ?? 'No disponible';
    }

    /**
     * Cargar datos del asesor desde SAP usando Z3PF_GETDATOSASESORPROCESO
     */
    protected function cargarDatosAsesorSAP(SoapClient $cliente, string $placa): void
    {
        try {
            Log::info("[DetalleVehiculo] Consultando asesor SAP para placa: {$placa}");

            $parametros = ['PI_PLACA' => $placa];
            $respuesta = $cliente->Z3PF_GETDATOSASESORPROCESO($parametros);
            
            // Capturar XML raw para extracción manual de campos
            $xmlResponse = $cliente->__getLastResponse();
            
            Log::info('[DetalleVehiculo] Respuesta SAP asesor:', (array) $respuesta);


            // Extraer todos los datos del asesor SAP
            $nombreAsesor = $respuesta->PE_NOM_ASE ?? '';
            $telefonoAsesor = $respuesta->PE_TEL_ASER ?? '';
            $correoAsesor = $respuesta->PE_COR_ASE ?? '';
            $fechaEntrega = $respuesta->PE_FEC_ENTREGA ?? '';
            $horaEntrega = $respuesta->PE_HOR_ENTREGA ?? '';
            $local = $respuesta->PE_LOCAL ?? '';
            $fechaFactura = $respuesta->PE_FEC_FACTURA ?? '';
            $horaFactura = $respuesta->PE_HOR_FACTURA ?? '';
            $rutPdf = $respuesta->PE_RUT_PDF ?? '';
            $fechaUltServ = $respuesta->PE_FEC_ULT_SERV ?? '';
            
            // Extraer PE_FEC_ULT_SERV del XML si no está en el objeto (problema de parsing PHP)
            if (empty($fechaUltServ) && !empty($xmlResponse)) {
                if (preg_match('/<PE_FEC_ULT_SERV[^>]*>([^<]*)<\/PE_FEC_ULT_SERV>/', $xmlResponse, $matches)) {
                    $fechaUltServ = trim($matches[1]);
                }
            }

            // Validación de fechas SAP
            $validacionFechaUltServ = !empty($fechaUltServ) && $fechaUltServ !== '0000-00-00';
            $validacionFechaFactura = !empty($fechaFactura) && $fechaFactura !== '0000-00-00';
            $validacionFechaEntrega = !empty($fechaEntrega) && $fechaEntrega !== '0000-00-00';



            // Guardar datos del asesor SAP para enriquecer las citas
            $this->datosAsesorSAP = [
                'nombre_asesor' => $nombreAsesor,
                'telefono_asesor' => $telefonoAsesor,
                'correo_asesor' => $correoAsesor,
                'fecha_entrega' => $fechaEntrega,
                'hora_entrega' => $horaEntrega,
                'local' => $local,
                'fecha_factura' => $fechaFactura,
                'hora_factura' => $horaFactura,
                'rut_pdf' => $rutPdf,
                'fecha_ult_serv' => $fechaUltServ,
                'tiene_fecha_entrega' => $validacionFechaEntrega,
                'tiene_fecha_factura' => $validacionFechaFactura,
                'tiene_fecha_ult_serv' => $validacionFechaUltServ,
            ];

            Log::info('[DetalleVehiculo] Datos del asesor SAP guardados:', $this->datosAsesorSAP);

        } catch (SoapFault $e) {
            Log::warning('[DetalleVehiculo] Error SOAP al consultar asesor (no crítico): ' . $e->getMessage());
            // Inicializar con valores vacíos si hay error
            $this->datosAsesorSAP = [
                'nombre_asesor' => '',
                'telefono_asesor' => '',
                'correo_asesor' => '',
                'fecha_entrega' => '',
                'hora_entrega' => '',
                'local' => '',
                'fecha_factura' => '',
                'hora_factura' => '',
                'rut_pdf' => '',
                'fecha_ult_serv' => '',
                'tiene_fecha_entrega' => false,
                'tiene_fecha_factura' => false,
                'tiene_fecha_ult_serv' => false,
            ];
        }
    }

    /**
     * Cargar prepagos disponibles desde SAP usando Z3PF_GETLISTAPREPAGOPEN
     */
    protected function cargarPrepagosSAP(SoapClient $cliente, string $placa): void
    {
        try {
            Log::info("[DetalleVehiculo] Consultando prepagos SAP para placa: {$placa}");

            $parametros = [
                'PI_PLACA' => $placa,
                'PI_PEND' => '', // Campo de uso interno según documentación
            ];
            $respuesta = $cliente->Z3PF_GETLISTAPREPAGOPEN($parametros);

            Log::info('[DetalleVehiculo] Respuesta SAP prepagos:', (array) $respuesta);

            $prepagosDisponibles = [];

            // Procesar lista de prepagos si existe
            if (isset($respuesta->PE_SERV_PREPAGO->item)) {
                // Normalizar a array
                $items = is_array($respuesta->PE_SERV_PREPAGO->item)
                    ? $respuesta->PE_SERV_PREPAGO->item
                    : [$respuesta->PE_SERV_PREPAGO->item];
            
                foreach ($items as $item) {
                    if (isset($item->MAKTX) && !empty($item->MAKTX)) {
                        $prepagosDisponibles[] = $item->MAKTX;
                    }
                }
            }

            // Actualizar datos de mantenimiento con prepagos
            if (!empty($prepagosDisponibles)) {
                $this->mantenimiento['disponibles'] = $prepagosDisponibles;
                Log::info('[DetalleVehiculo] Prepagos encontrados: ' . count($prepagosDisponibles));
            } else {
                $this->mantenimiento['disponibles'] = ['No disponible'];
                Log::info('[DetalleVehiculo] No se encontraron prepagos disponibles');
            }

        } catch (SoapFault $e) {
            Log::warning('[DetalleVehiculo] Error SOAP al consultar prepagos (no crítico): ' . $e->getMessage());
            $this->mantenimiento['disponibles'] = ['Error al consultar prepagos'];
        }
    }

    /**
     * Formatear fecha desde SAP (formato YYYY-MM-DD o 0000-00-00)
     */
    protected function formatearFechaSAP(string $fecha): string
    {
        if (empty($fecha) || $fecha === '0000-00-00') {
            return 'No disponible';
        }

        try {
            $fechaObj = \DateTime::createFromFormat('Y-m-d', $fecha);
            return $fechaObj ? $fechaObj->format('d/m/Y') : 'No disponible';
        } catch (\Exception $e) {
            return 'No disponible';
        }
    }

    /**
     * Formatear hora desde SAP (formato HH:MM:SS o 00:00:00)
     */
    protected function formatearHoraSAP(string $hora): string
    {
        if (empty($hora) || $hora === '00:00:00') {
            return 'No disponible';
        }

        try {
            $horaObj = \DateTime::createFromFormat('H:i:s', $hora);
            return $horaObj ? $horaObj->format('H:i') : 'No disponible';
        } catch (\Exception $e) {
            return 'No disponible';
        }
    }

    /**
     * Procesar historial de servicios desde SAP usando TT_LISSRV
     */
    protected function procesarHistorialServiciosSAP($ttLissrv): void
    {
        try {
            Log::info('[DetalleVehiculo] Procesando historial de servicios SAP');
            Log::info('[DetalleVehiculo] Estructura TT_LISSRV recibida:', (array) $ttLissrv);

            $servicios = [];

            // Verificar si hay items en TT_LISSRV
            if (isset($ttLissrv->item)) {
                $listaServicios = is_array($ttLissrv->item) ? $ttLissrv->item : [$ttLissrv->item];

                Log::info('[DetalleVehiculo] Procesando ' . count($listaServicios) . ' servicios');

                foreach ($listaServicios as $index => $servicio) {
                    Log::info("[DetalleVehiculo] Procesando servicio {$index}:", (array) $servicio);

                    // Extraer todos los campos disponibles del servicio
                    $servicioFormateado = [
                        'fecha' => $this->formatearFechaSAP($servicio->FECSRV ?? ''),
                        'servicio' => $servicio->DESSRV ?? 'Servicio no especificado',
                        'sede' => $servicio->SEDSRV ?? 'No especificado',
                        'asesor' => $servicio->ASESRV ?? 'No especificado',
                        'tipo_pago' => $servicio->TIPPAGSRV ?? 'No especificado',
                        'fecha_raw' => $servicio->FECSRV ?? '',
                    ];

                    $servicios[] = $servicioFormateado;
                    Log::info("[DetalleVehiculo] Servicio {$index} formateado:", $servicioFormateado);
                }
            } else {
                Log::warning('[DetalleVehiculo] No se encontró estructura item en TT_LISSRV');
            }

            if (!empty($servicios)) {
                // Ordenar servicios por fecha (más reciente primero)
                usort($servicios, function($a, $b) {
                    return strcmp($b['fecha_raw'], $a['fecha_raw']);
                });

                $this->historialServicios = collect($servicios);
                Log::info('[DetalleVehiculo] Historial de servicios SAP procesado exitosamente: ' . count($servicios) . ' servicios');

                // Actualizar el último servicio en los datos de mantenimiento
                if (count($servicios) > 0) {
                    $ultimoServicio = $servicios[0];
                    $this->mantenimiento['ultimo'] = $ultimoServicio['servicio'];
                    $this->mantenimiento['fecha'] = $ultimoServicio['fecha'];

                    Log::info('[DetalleVehiculo] Último servicio actualizado: ' . $ultimoServicio['servicio']);
                }
            } else {
                $this->historialServicios = collect();
                Log::info('[DetalleVehiculo] No se encontraron servicios en el historial');
            }

        } catch (\Exception $e) {
            Log::error('[DetalleVehiculo] Error al procesar historial SAP: ' . $e->getMessage());
            $this->historialServicios = collect();
        }
    }

    protected function evaluarEstadoCitas(array $citasDelVehiculo): bool
    {
        foreach ($citasDelVehiculo as $cita) {
            $statusCode = $cita['status']['appointment_code'] ?? '';
            $fechaProgramada = $cita['dates']['scheduled_start_date'] ?? '';

            if (in_array($statusCode, ['1', '2'])) {
                if (empty($fechaProgramada) || strtotime($fechaProgramada) >= strtotime('today')) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Enviar email de notificación de cita cancelada
     */
    protected function enviarEmailCitaCancelada(Appointment $appointment, string $motivoCancelacion = ''): void
    {
        try {
            Log::info('📧 [CitaCancelada] Enviando email de cita cancelada', [
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
                'customer_email' => $appointment->customer_email,
                'motivo' => $motivoCancelacion,
            ]);

            // Preparar datos del cliente
            $datosCliente = [
                'nombres' => $appointment->customer_name,
                'apellidos' => $appointment->customer_last_name,
                'email' => $appointment->customer_email,
                'celular' => $appointment->customer_phone,
            ];

            // Preparar datos del vehículo
            $datosVehiculo = [
                'marca' => $this->vehiculo['marca'] ?? 'No especificado',
                'modelo' => $this->vehiculo['modelo'] ?? 'No especificado',
                'placa' => $this->vehiculo['placa'] ?? 'No especificado',
            ];

            // Enviar el correo de cancelación
            Mail::to($appointment->customer_email)
                ->send(new CitaCancelada($appointment, $datosCliente, $datosVehiculo, $motivoCancelacion));

            Log::info('📧 [CitaCancelada] Email de cita cancelada enviado exitosamente', [
                'appointment_id' => $appointment->id,
                'customer_email' => $appointment->customer_email,
            ]);

        } catch (\Exception $e) {
            Log::error('📧 [CitaCancelada] Error enviando email de cita cancelada', [
                'appointment_id' => $appointment->id,
                'customer_email' => $appointment->customer_email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // No lanzar excepción para no interrumpir el proceso de cancelación
            // Solo registrar el error
        }
    }

}
