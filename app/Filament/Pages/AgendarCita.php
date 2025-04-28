<?php

namespace App\Filament\Pages;

use App\Models\AdditionalService;
use App\Models\Appointment;
use App\Models\ServiceCenter;
use App\Models\ServiceType;
use App\Models\Vehicle;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
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
    public string $fechaSeleccionada = '28/04/2025';
    public string $horaSeleccionada = '11:15 AM';
    public string $localSeleccionado = '';
    public string $servicioSeleccionado = 'Mantenimiento periódico';
    public string $tipoMantenimiento = '20,000 Km';
    public string $modalidadServicio = 'Regular';

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
    public array $opcionesServiciosAdicionales = [
        'restauracion_faros' => 'Restauración de faros',
        'restauracion_rines' => 'Restauración de rines',
        'restauracion_focos' => 'Restauración de focos',
    ];

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

        // Verificar el estado final de la variable vehiculo
        Log::info("[AgendarCita] Estado final de la variable vehiculo:", $this->vehiculo ?? ['vehiculo' => 'null']);
    }

    /**
     * Cargar los locales desde la base de datos
     */
    protected function cargarLocales(): void
    {
        $serviceCenters = ServiceCenter::active()->get();

        if ($serviceCenters->isNotEmpty()) {
            $this->locales = [];

            foreach ($serviceCenters as $serviceCenter) {
                $key = Str::slug($serviceCenter->name);
                $this->locales[$key] = [
                    'nombre' => $serviceCenter->name,
                    'direccion' => $serviceCenter->address,
                    'id' => $serviceCenter->id,
                ];
            }

            // Establecer el primer local como seleccionado por defecto
            if (empty($this->localSeleccionado) && !empty($this->locales)) {
                $this->localSeleccionado = array_key_first($this->locales);
            }
        }
    }

    /**
     * Cargar los servicios adicionales desde la base de datos
     */
    protected function cargarServiciosAdicionales(): void
    {
        $additionalServices = AdditionalService::active()->get();

        if ($additionalServices->isNotEmpty()) {
            $this->opcionesServiciosAdicionales = [];

            foreach ($additionalServices as $additionalService) {
                $key = Str::slug($additionalService->name);
                $this->opcionesServiciosAdicionales[$key] = $additionalService->name;
            }
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

            // Obtener el centro de servicio
            $serviceCenter = null;
            foreach ($this->locales as $key => $local) {
                if ($key === $this->localSeleccionado) {
                    $serviceCenter = ServiceCenter::find($local['id']);
                    break;
                }
            }

            if (!$serviceCenter) {
                \Filament\Notifications\Notification::make()
                    ->title('Error al Agendar Cita')
                    ->body('No se encontró el centro de servicio seleccionado.')
                    ->danger()
                    ->send();
                return;
            }

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
            $appointment->service_center_id = $serviceCenter->id;
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
                    $servicioAdicionalNombre = $this->opcionesServiciosAdicionales[$servicioAdicionalKey] ?? null;

                    if ($servicioAdicionalNombre) {
                        $additionalService = AdditionalService::where('name', $servicioAdicionalNombre)->first();

                        if ($additionalService) {
                            $appointment->additionalServices()->attach($additionalService->id);
                        }
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


}
