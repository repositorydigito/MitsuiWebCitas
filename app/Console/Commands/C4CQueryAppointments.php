<?php

namespace App\Console\Commands;

use App\Services\C4C\AppointmentService;
use Illuminate\Console\Command;

class C4CQueryAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:query-appointments
                            {client_id : ID del cliente en C4C}
                            {--real : Usar servicio real en lugar de mock}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consultar citas pendientes de un cliente en C4C (como Python)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $clientId = $this->argument('client_id');

        // Validar que el client_id sea válido
        if (empty($clientId)) {
            $this->error('El client_id es requerido');

            return 1;
        }

        $this->info("Consultando citas pendientes para cliente: {$clientId}");

        if ($this->option('real')) {
            $this->info('Usando servicio real (forzado por opción --real)...');
        } else {
            $this->info('Usando servicio mock (usar --real para servicio real)...');
        }

        try {
            $appointmentService = new AppointmentService;
            $result = $appointmentService->queryPendingAppointments($clientId);

            if ($result['success']) {
                $appointmentCount = $result['count'] ?? 0;

                if ($appointmentCount > 0) {
                    $this->info("✅ Se encontraron {$appointmentCount} citas pendientes");

                    foreach ($result['data'] as $index => $appointment) {
                        $appointmentNumber = $index + 1;
                        $this->info("\n<fg=green;options=bold>--- Cita {$appointmentNumber} ---</>");

                        // Información básica
                        $this->info('<fg=green;options=bold>Información Básica:</>');
                        $this->info('UUID: '.($appointment['uuid'] ?? 'N/A'));
                        $this->info('ID: '.($appointment['id'] ?? 'N/A'));
                        $this->info('Estado del Ciclo: '.($appointment['life_cycle_status_code'] ?? 'N/A').' ('.($appointment['life_cycle_status_name'] ?? 'N/A').')');
                        $this->info('Asunto: '.($appointment['subject_name'] ?? 'N/A'));
                        $this->info('Ubicación: '.($appointment['location_name'] ?? 'N/A'));
                        $this->info('Prioridad: '.($appointment['priority_code'] ?? 'N/A').' ('.($appointment['priority_name'] ?? 'N/A').')');

                        // Fechas y horarios
                        $this->info("\n<fg=green;options=bold>Fechas y Horarios:</>");
                        $this->info('Fecha Programada Inicio: '.($appointment['scheduled_start_date'] ?? 'N/A'));
                        $this->info('Fecha Programada Fin: '.($appointment['scheduled_end_date'] ?? 'N/A'));
                        $this->info('Fecha/Hora Inicio: '.($appointment['start_date_time'] ?? 'N/A'));
                        $this->info('Fecha/Hora Fin: '.($appointment['end_date_time'] ?? 'N/A'));
                        $this->info('Fecha de Reporte: '.($appointment['reported_date'] ?? 'N/A'));
                        $this->info('Fecha de Creación: '.($appointment['creation_date'] ?? 'N/A'));
                        $this->info('Última Modificación: '.($appointment['last_change_date'] ?? 'N/A'));

                        // Información del cliente
                        $this->info("\n<fg=green;options=bold>Información del Cliente:</>");
                        $this->info('Nombre: '.($appointment['client_name'] ?? 'N/A'));
                        $this->info('DNI: '.($appointment['client_dni'] ?? 'N/A'));
                        $this->info('ID Cliente: '.($appointment['client_id'] ?? 'N/A'));
                        $this->info('Teléfono: '.($appointment['client_phone'] ?? 'N/A'));
                        $this->info('Teléfono Fijo: '.($appointment['client_landline'] ?? 'N/A'));
                        $this->info('Dirección: '.($appointment['client_address'] ?? 'N/A'));

                        // Información del vehículo
                        $this->info("\n<fg=green;options=bold>Información del Vehículo:</>");
                        $this->info('Placa: '.($appointment['license_plate'] ?? 'N/A'));
                        $this->info('VIN: '.($appointment['vin'] ?? 'N/A'));
                        $this->info('Modelo: '.($appointment['vehicle_model'] ?? 'N/A'));
                        $this->info('Versión: '.($appointment['vehicle_version'] ?? 'N/A'));
                        $this->info('Año: '.($appointment['vehicle_year'] ?? 'N/A'));
                        $this->info('Color: '.($appointment['vehicle_color'] ?? 'N/A'));
                        $this->info('Kilometraje: '.($appointment['vehicle_mileage'] ?? 'N/A'));
                        $this->info('Motor: '.($appointment['engine'] ?? 'N/A'));

                        // Información específica de la cita
                        $this->info("\n<fg=green;options=bold>Detalles de la Cita:</>");
                        $this->info('Estado de Cita: '.($appointment['appointment_status'] ?? 'N/A').' ('.($appointment['appointment_status_name'] ?? 'N/A').')');
                        $this->info('Centro: '.($appointment['center_id'] ?? 'N/A').' ('.($appointment['center_description'] ?? 'N/A').')');
                        $this->info('Fecha de Salida: '.($appointment['exit_date'] ?? 'N/A'));
                        $this->info('Hora de Salida: '.($appointment['exit_time'] ?? 'N/A'));
                        $this->info('Hora de Inicio: '.($appointment['start_time'] ?? 'N/A'));

                        // Servicios adicionales
                        if (! empty($appointment['request_taxi']) || ! empty($appointment['telemarketing_advisor'])) {
                            $this->info("\n<fg=green;options=bold>Servicios Adicionales:</>");
                            if (! empty($appointment['request_taxi'])) {
                                $this->info('Solicitar Taxi: '.($appointment['request_taxi_name'] ?? $appointment['request_taxi']));
                            }
                            if (! empty($appointment['telemarketing_advisor'])) {
                                $this->info('Asesor Telemarketing: '.$appointment['telemarketing_advisor']);
                            }
                        }

                        // Información organizacional
                        if (! empty($appointment['sales_organization_id']) || ! empty($appointment['distribution_channel_code'])) {
                            $this->info("\n<fg=green;options=bold>Información Organizacional:</>");
                            if (! empty($appointment['sales_organization_id'])) {
                                $this->info('Organización de Ventas: '.$appointment['sales_organization_id']);
                            }
                            if (! empty($appointment['distribution_channel_code'])) {
                                $this->info('Canal de Distribución: '.$appointment['distribution_channel_code'].' ('.($appointment['distribution_channel_name'] ?? 'N/A').')');
                            }
                            if (! empty($appointment['division_code'])) {
                                $this->info('División: '.$appointment['division_code'].' ('.($appointment['division_name'] ?? 'N/A').')');
                            }
                        }

                        // Coordenadas geográficas
                        if (! empty($appointment['start_latitude']) || ! empty($appointment['end_latitude'])) {
                            $this->info("\n<fg=green;options=bold>Coordenadas Geográficas:</>");
                            if (! empty($appointment['start_latitude'])) {
                                $this->info('Inicio: '.$appointment['start_latitude'].', '.($appointment['start_longitude'] ?? 'N/A'));
                            }
                            if (! empty($appointment['end_latitude'])) {
                                $this->info('Fin: '.$appointment['end_latitude'].', '.($appointment['end_longitude'] ?? 'N/A'));
                            }
                        }
                    }

                    // Información de procesamiento
                    if (isset($result['processing_conditions'])) {
                        $this->info("\n<fg=green;options=bold>Información de Procesamiento:</>");
                        $processing = $result['processing_conditions'];
                        $this->info('Resultados devueltos: '.($processing['returned_query_hits_number_value'] ?? 'N/A'));
                        $this->info('Más resultados disponibles: '.($processing['more_hits_available_indicator'] ? 'Sí' : 'No'));
                        if (! empty($processing['last_returned_object_id'])) {
                            $this->info('Último Object ID: '.$processing['last_returned_object_id']);
                        }
                    }

                } else {
                    $this->info('✅ Consulta exitosa - No se encontraron citas pendientes para este cliente');

                    // Mostrar información de procesamiento si está disponible
                    if (isset($result['processing_conditions'])) {
                        $this->info("\n<fg=green;options=bold>Información de Procesamiento:</>");
                        $processing = $result['processing_conditions'];
                        $this->info('Resultados devueltos: '.($processing['returned_query_hits_number_value'] ?? 'N/A'));
                        $this->info('Más resultados disponibles: '.($processing['more_hits_available_indicator'] ? 'Sí' : 'No'));
                    }
                }

                $this->info("\n<fg=green;options=bold>Total de citas encontradas: {$appointmentCount}</>");

                return 0;

            } else {
                $this->error('❌ Error al consultar las citas');
                $this->error('Error: '.($result['error'] ?? 'Error desconocido'));

                if (isset($result['details'])) {
                    $this->info('Detalles adicionales:');
                    $this->info(json_encode($result['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }

                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Excepción al consultar las citas: '.$e->getMessage());
            $this->error('Trace: '.$e->getTraceAsString());

            return 1;
        }
    }
}
