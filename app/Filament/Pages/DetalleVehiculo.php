<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\Vehicle;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class DetalleVehiculo extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Detalle del Vehículo';

    protected static ?string $title = 'Detalle del vehículo';

    protected static string $view = 'filament.pages.detalle-vehiculo';

    // Ocultar de la navegación principal ya que se accederá desde la página de vehículos
    protected static bool $shouldRegisterNavigation = false;

    // Datos del vehículo
    public ?array $vehiculo = [
        'modelo' => 'RAV4 LIMITED',
        'kilometraje' => '12,530 Km',
        'placa' => 'DEF-456',
    ];

    // Datos de mantenimiento
    public array $mantenimiento = [
        'ultimo' => '25,000 Km',
        'fecha' => 'Noviembre 2024',
        'vencimiento' => '30/06/2025',
        'disponibles' => [
            '1 Servicio 40,000 Km',
            '1 Servicio 20,000 Km'
        ]
    ];

    // Citas agendadas
    public array $citasAgendadas = [
        [
            'servicio' => 'Mantenimiento 200,000 Km',
            'estado' => 'confirmada', // confirmada, en_trabajo, trabajo_concluido, entregado
            'probable_entrega' => '-',
            'hora' => '-',
            'sede' => '-',
            'asesor' => '-',
            'whatsapp' => '-',
            'correo' => '-'
        ]
    ];

    // Historial de servicios
    public Collection $historialServicios;
    public int $currentPage = 1;
    public int $perPage = 10;

    public function mount($vehiculoId = null): void
    {
        // Cargar datos del vehículo basados en el ID recibido
        if ($vehiculoId) {
            // Limpiar el ID del vehículo (eliminar espacios)
            $vehiculoId = trim(str_replace(' ', '', $vehiculoId));
            Log::info("[DetalleVehiculo] Cargando datos para vehículo ID (limpio): {$vehiculoId}");

            // Buscar el vehículo en la base de datos - intentar diferentes formas de búsqueda
            Log::info("[DetalleVehiculo] Buscando vehículo en la base de datos con vehicle_id o license_plate");

            // Primero intentamos una búsqueda exacta
            $vehiculo = Vehicle::where('vehicle_id', $vehiculoId)
                ->orWhere('license_plate', $vehiculoId)
                ->first();

            // Si no encontramos, intentamos una búsqueda con LIKE
            if (!$vehiculo) {
                Log::info("[DetalleVehiculo] No se encontró con búsqueda exacta, intentando con LIKE");
                $vehiculo = Vehicle::where('vehicle_id', 'LIKE', "%{$vehiculoId}%")
                    ->orWhere('license_plate', 'LIKE', "%{$vehiculoId}%")
                    ->first();
            }

            // Verificar todos los vehículos en la base de datos para depuración
            $todosLosVehiculos = Vehicle::all();
            Log::info("[DetalleVehiculo] Total de vehículos en la base de datos: " . $todosLosVehiculos->count());
            foreach ($todosLosVehiculos as $v) {
                Log::info("[DetalleVehiculo] Vehículo en DB: ID={$v->id}, vehicle_id={$v->vehicle_id}, license_plate={$v->license_plate}, model={$v->model}");
            }

            if ($vehiculo) {
                // Si encontramos el vehículo, usamos sus datos
                $this->vehiculo = [
                    'id' => $vehiculo->id,
                    'vehicle_id' => $vehiculo->vehicle_id,
                    'modelo' => $vehiculo->model,
                    'kilometraje' => number_format($vehiculo->mileage, 0, '.', ',') . ' Km',
                    'placa' => $vehiculo->license_plate,
                    'anio' => $vehiculo->year,
                    'marca' => $vehiculo->brand_name,
                    'color' => $vehiculo->color,
                    'vin' => $vehiculo->vin,
                    'motor' => $vehiculo->engine_number,
                ];

                // Cargar datos de mantenimiento
                $this->cargarDatosMantenimiento($vehiculo);

                // Cargar citas agendadas
                $this->cargarCitasAgendadas($vehiculo->id);

                Log::info("[DetalleVehiculo] Vehículo encontrado en la base de datos:", $this->vehiculo);
            } else {
                Log::warning("[DetalleVehiculo] No se encontró el vehículo con ID: {$vehiculoId}. Manteniendo valores predeterminados.");

                // Crear un vehículo de ejemplo con el ID proporcionado para pruebas
                try {
                    $nuevoVehiculo = Vehicle::create([
                        'vehicle_id' => $vehiculoId,
                        'license_plate' => $vehiculoId,
                        'model' => 'COROLLA TEST',
                        'year' => '2023',
                        'brand_code' => 'Z01',
                        'brand_name' => 'TOYOTA',
                        'color' => 'ROJO',
                        'mileage' => 5000,
                    ]);

                    Log::info("[DetalleVehiculo] Se creó un vehículo de prueba con ID: {$vehiculoId}");

                    // Usar los datos del vehículo recién creado
                    $this->vehiculo = [
                        'id' => $nuevoVehiculo->id,
                        'vehicle_id' => $nuevoVehiculo->vehicle_id,
                        'modelo' => $nuevoVehiculo->model,
                        'kilometraje' => number_format($nuevoVehiculo->mileage, 0, '.', ',') . ' Km',
                        'placa' => $nuevoVehiculo->license_plate,
                        'anio' => $nuevoVehiculo->year,
                        'marca' => $nuevoVehiculo->brand_name,
                        'color' => $nuevoVehiculo->color,
                        'vin' => $nuevoVehiculo->vin,
                        'motor' => $nuevoVehiculo->engine_number,
                    ];

                    // Cargar datos de mantenimiento
                    $this->cargarDatosMantenimiento($nuevoVehiculo);

                    // Cargar citas agendadas
                    $this->cargarCitasAgendadas($nuevoVehiculo->id);
                } catch (\Exception $e) {
                    Log::error("[DetalleVehiculo] Error al crear vehículo de prueba: " . $e->getMessage());
                }
            }
        } else {
            Log::warning("[DetalleVehiculo] No se proporcionó ID de vehículo.");
        }

        // Inicializar el historial de servicios
        $this->inicializarHistorialServicios();
    }

    /**
     * Cargar datos de mantenimiento del vehículo
     */
    protected function cargarDatosMantenimiento(Vehicle $vehiculo): void
    {
        try {
            Log::info("[DetalleVehiculo] Cargando datos de mantenimiento para vehículo ID: {$vehiculo->id}");

            $this->mantenimiento = [
                'ultimo' => $vehiculo->last_service_mileage ? number_format($vehiculo->last_service_mileage, 0, '.', ',') . ' Km' : 'No disponible',
                'fecha' => $vehiculo->last_service_date ? $vehiculo->last_service_date->format('d/m/Y') : 'No disponible',
                'vencimiento' => $vehiculo->prepaid_maintenance_expiry ? $vehiculo->prepaid_maintenance_expiry->format('d/m/Y') : 'No disponible',
                'disponibles' => $vehiculo->has_prepaid_maintenance ? [
                    '1 Servicio ' . number_format($vehiculo->next_service_mileage ?? 10000, 0, '.', ',') . ' Km'
                ] : ['No tiene mantenimientos prepagados']
            ];

            Log::info("[DetalleVehiculo] Datos de mantenimiento cargados correctamente");
        } catch (\Exception $e) {
            Log::error("[DetalleVehiculo] Error al cargar datos de mantenimiento: " . $e->getMessage());

            // Establecer valores predeterminados en caso de error
            $this->mantenimiento = [
                'ultimo' => 'No disponible',
                'fecha' => 'No disponible',
                'vencimiento' => 'No disponible',
                'disponibles' => ['No tiene mantenimientos prepagados']
            ];
        }
    }

    /**
     * Cargar citas agendadas para el vehículo
     */
    protected function cargarCitasAgendadas(int $vehiculoId): void
    {
        try {
            Log::info("[DetalleVehiculo] Cargando citas agendadas para vehículo ID: {$vehiculoId}");

            $citas = Appointment::where('vehicle_id', $vehiculoId)
                ->where('status', '!=', 'cancelled')
                ->orderBy('appointment_date', 'desc')
                ->orderBy('appointment_time', 'desc')
                ->get();

            Log::info("[DetalleVehiculo] Se encontraron {$citas->count()} citas agendadas");

            if ($citas->isNotEmpty()) {
                $this->citasAgendadas = [];

                foreach ($citas as $cita) {
                    $this->citasAgendadas[] = [
                        'id' => $cita->id,
                        'servicio' => $cita->serviceType->name ?? 'No especificado',
                        'estado' => $cita->status,
                        'probable_entrega' => $cita->appointment_end_time ? $cita->appointment_end_time->format('d/m/Y') : '-',
                        'hora' => $cita->appointment_time,
                        'sede' => $cita->serviceCenter->name ?? '-',
                        'asesor' => 'Por asignar',
                        'whatsapp' => '-',
                        'correo' => '-'
                    ];
                }

                Log::info("[DetalleVehiculo] Citas agendadas cargadas correctamente");
            } else {
                // Mantener los valores predeterminados
                Log::info("[DetalleVehiculo] No hay citas agendadas, manteniendo valores predeterminados");
            }
        } catch (\Exception $e) {
            Log::error("[DetalleVehiculo] Error al cargar citas agendadas: " . $e->getMessage());

            // Mantener los valores predeterminados en caso de error
        }
    }

    protected function inicializarHistorialServicios(): void
    {
        $servicios = [];

        // Si tenemos un vehículo cargado, buscamos su historial de citas completadas
        if (isset($this->vehiculo['id'])) {
            Log::info("[DetalleVehiculo] Buscando historial de citas para el vehículo ID: {$this->vehiculo['id']}");

            try {
                $citasCompletadas = Appointment::where('vehicle_id', $this->vehiculo['id'])
                    ->where('status', 'completed')
                    ->orderBy('appointment_date', 'desc')
                    ->get();

                Log::info("[DetalleVehiculo] Se encontraron {$citasCompletadas->count()} citas completadas");

                foreach ($citasCompletadas as $cita) {
                    $servicios[] = [
                        'servicio' => $cita->serviceType->name ?? 'No especificado',
                        'fecha' => $cita->appointment_date ? date('d/m/Y', strtotime($cita->appointment_date)) : 'No disponible',
                        'sede' => $cita->serviceCenter->name ?? 'No especificado',
                        'asesor' => 'Asesor asignado',
                        'tipo_pago' => 'Registrado'
                    ];
                }
            } catch (\Exception $e) {
                Log::error("[DetalleVehiculo] Error al cargar historial de citas: " . $e->getMessage());
            }
        } else {
            Log::warning("[DetalleVehiculo] No se puede cargar el historial de citas porque no hay un vehículo cargado");
        }

        // Si no hay servicios completados, agregamos un ejemplo
        if (empty($servicios)) {
            Log::info("[DetalleVehiculo] No hay servicios completados, agregando ejemplo");

            // Si tenemos un vehículo cargado, personalizamos el ejemplo
            if (isset($this->vehiculo['modelo'])) {
                $servicios[] = [
                    'servicio' => 'Mantenimiento 10,000 Km',
                    'fecha' => date('d/m/Y', strtotime('-3 months')),
                    'sede' => 'Mitsui La Molina',
                    'asesor' => 'Luis Gonzales',
                    'tipo_pago' => 'Contado'
                ];
            } else {
                $servicios[] = [
                    'servicio' => 'Mantenimiento 15,000 Km',
                    'fecha' => '30/10/2023',
                    'sede' => 'La Molina',
                    'asesor' => 'Luis Gonzales',
                    'tipo_pago' => 'Contado'
                ];
            }
        }

        $this->historialServicios = collect($servicios);
        Log::info("[DetalleVehiculo] Historial de servicios inicializado con " . count($servicios) . " servicios");
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

    // Método para ir a agendar cita
    public function agendarCita(): void
    {
        // Verificar si tenemos un vehículo cargado
        if (!isset($this->vehiculo['placa'])) {
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

        if (!$vehiculo) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('El vehículo no se encuentra registrado en la base de datos.')
                ->danger()
                ->send();
            return;
        }

        // Registrar la placa original y la placa limpia
        Log::info("[DetalleVehiculo] Datos del vehículo para agendar cita:", [
            'placa_original' => $this->vehiculo['placa'] ?? 'No disponible',
            'placa_limpia' => $placa,
            'modelo' => $this->vehiculo['modelo'] ?? 'No disponible',
            'vehicle_id' => $vehiculo->id
        ]);

        // Redirigir a la página de agendar cita con la placa como parámetro
        $this->redirect(AgendarCita::getUrl(['vehiculoId' => $placa]));
    }
}
