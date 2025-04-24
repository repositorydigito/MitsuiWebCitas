<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use App\Services\VehiculoSoapService;

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
    public string $nombreCliente = '';
    public string $emailCliente = '';
    public string $apellidoCliente = '';
    public string $celularCliente = '';

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
        // Cargar datos del vehículo basados en el ID recibido
        if ($vehiculoId) {
            // Limpiar el ID del vehículo (eliminar espacios)
            $vehiculoId = trim(str_replace(' ', '', $vehiculoId));
            Log::info("[AgendarCita] Cargando datos para vehículo ID (limpio): {$vehiculoId}");

            // Obtener los datos del vehículo desde el servicio
            try {
                $service = app(VehiculoSoapService::class);
                $documentoCliente = '20605414410'; // En un caso real, esto vendría del usuario autenticado
                $marcas = ['Z01', 'Z02', 'Z03']; // Todas las marcas disponibles

                // Obtener todos los vehículos del cliente
                $vehiculos = $service->getVehiculosCliente($documentoCliente, $marcas);
                Log::info("[AgendarCita] Total de vehículos obtenidos: {$vehiculos->count()}");

                // Registrar todas las placas disponibles para depuración
                $placasDisponibles = $vehiculos->pluck('numpla')->toArray();
                Log::info("[AgendarCita] Placas disponibles:", $placasDisponibles);

                // Buscar el vehículo por ID (puede ser placa o vhclie)
                $vehiculoEncontrado = $vehiculos->first(function ($vehiculo) use ($vehiculoId) {
                    $coincidePlaca = strtoupper($vehiculo['numpla']) == strtoupper($vehiculoId);
                    $coincideVhclie = $vehiculo['vhclie'] == $vehiculoId;

                    // Registrar cada comparación para depuración
                    if ($coincidePlaca || $coincideVhclie) {
                        Log::info("[AgendarCita] Coincidencia encontrada:", [
                            'vehiculoId' => $vehiculoId,
                            'numpla' => $vehiculo['numpla'],
                            'vhclie' => $vehiculo['vhclie'],
                            'coincidePlaca' => $coincidePlaca,
                            'coincideVhclie' => $coincideVhclie
                        ]);
                    }

                    return $coincidePlaca || $coincideVhclie;
                });

                if ($vehiculoEncontrado) {
                    // Si encontramos el vehículo, usamos sus datos reales
                    $this->vehiculo = [
                        'id' => $vehiculoEncontrado['vhclie'],
                        'placa' => $vehiculoEncontrado['numpla'],
                        'modelo' => $vehiculoEncontrado['modver'],
                        'anio' => $vehiculoEncontrado['aniomod'],
                        'marca' => isset($vehiculoEncontrado['marca_codigo']) ?
                                  ($vehiculoEncontrado['marca_codigo'] == 'Z01' ? 'TOYOTA' :
                                   ($vehiculoEncontrado['marca_codigo'] == 'Z02' ? 'LEXUS' : 'HINO')) : 'TOYOTA',
                    ];

                    Log::info("[AgendarCita] Vehículo encontrado:", $this->vehiculo);

                    // Verificar que los datos se hayan asignado correctamente
                    Log::info("[AgendarCita] Datos del vehículo asignados:", [
                        'modelo' => $this->vehiculo['modelo'] ?? 'No asignado',
                        'placa' => $this->vehiculo['placa'] ?? 'No asignado',
                    ]);
                } else {
                    // Si no encontramos el vehículo, mantenemos los valores predeterminados
                    Log::warning("[AgendarCita] No se encontró el vehículo con ID: {$vehiculoId}. Manteniendo valores predeterminados.");
                    // No sobrescribimos $this->vehiculo para mantener los valores predeterminados
                    Log::info("[AgendarCita] Valores predeterminados mantenidos:", $this->vehiculo);
                }
            } catch (\Exception $e) {
                // En caso de error, mantener los valores predeterminados
                Log::error("[AgendarCita] Error al cargar datos del vehículo: " . $e->getMessage());
                // No sobrescribimos $this->vehiculo para mantener los valores predeterminados
                Log::info("[AgendarCita] Valores predeterminados mantenidos en caso de error:", $this->vehiculo);
            }
        } else {
            // Si no se proporcionó un ID de vehículo, mantener los valores predeterminados
            Log::warning("[AgendarCita] No se proporcionó ID de vehículo. Manteniendo valores predeterminados.");
            // No sobrescribimos $this->vehiculo para mantener los valores predeterminados
        }

        // Verificar el estado final de la variable vehiculo
        Log::info("[AgendarCita] Estado final de la variable vehiculo:", $this->vehiculo ?? ['vehiculo' => 'null']);
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
        // Aquí iría la lógica para guardar la cita en la base de datos
        // Por ahora solo simulamos el guardado
        $this->citaAgendada = true;

        // Mostramos una notificación de éxito
        \Filament\Notifications\Notification::make()
            ->title('Cita Agendada')
            ->body('Tu cita ha sido agendada exitosamente.')
            ->success()
            ->send();
    }

    // Método para finalizar el proceso de agendamiento
    public function finalizarAgendamiento(): void
    {
        // Verificar el estado de la variable vehiculo antes de avanzar
        Log::info("[AgendarCita] Estado de la variable vehiculo antes de avanzar al paso 2:", $this->vehiculo ?? ['vehiculo' => 'null']);

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
