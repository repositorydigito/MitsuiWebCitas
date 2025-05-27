<?php

namespace App\Services\C4C;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MockAppointmentQueryService
{
    /**
     * Get pending appointments for a customer.
     *
     * @return array
     */
    public function getPendingAppointments(string $customerId, array $options = [])
    {
        Log::info("MockAppointmentQueryService: Consultando citas pendientes para el cliente: {$customerId}");

        // Validar ID de cliente
        if (empty($customerId)) {
            Log::error('ID de cliente requerido');

            return [
                'success' => false,
                'error' => 'ID de cliente requerido',
                'data' => null,
            ];
        }

        // Generar datos de prueba
        $appointments = $this->generateMockAppointments($customerId, 2, $options);

        return [
            'success' => true,
            'error' => null,
            'data' => $appointments,
            'count' => count($appointments),
            'processing_conditions' => [
                'returned_query_hits_number_value' => count($appointments),
                'more_hits_available_indicator' => false,
            ],
        ];
    }

    /**
     * Get all appointments for a customer.
     *
     * @return array
     */
    public function getAllAppointments(string $customerId, array $options = [])
    {
        Log::info("MockAppointmentQueryService: Consultando todas las citas para el cliente: {$customerId}");

        // Validar ID de cliente
        if (empty($customerId)) {
            Log::error('ID de cliente requerido');

            return [
                'success' => false,
                'error' => 'ID de cliente requerido',
                'data' => null,
            ];
        }

        // Generar datos de prueba
        $appointments = $this->generateMockAppointments($customerId, 5, $options);

        return [
            'success' => true,
            'error' => null,
            'data' => $appointments,
            'count' => count($appointments),
            'processing_conditions' => [
                'returned_query_hits_number_value' => count($appointments),
                'more_hits_available_indicator' => false,
            ],
        ];
    }

    /**
     * Get appointments by vehicle plate.
     *
     * @return array
     */
    public function getAppointmentsByVehiclePlate(string $vehiclePlate, array $options = [])
    {
        Log::info("MockAppointmentQueryService: Consultando citas por placa de vehículo: {$vehiclePlate}");

        // Validar placa de vehículo
        if (empty($vehiclePlate)) {
            Log::error('Placa de vehículo requerida');

            return [
                'success' => false,
                'error' => 'Placa de vehículo requerida',
                'data' => null,
            ];
        }

        // Generar datos de prueba
        $customerId = $options['customer_id'] ?? '1270002726';
        $appointments = $this->generateMockAppointments($customerId, 3, array_merge($options, ['vehicle_plate' => $vehiclePlate]));

        return [
            'success' => true,
            'error' => null,
            'data' => $appointments,
            'count' => count($appointments),
            'processing_conditions' => [
                'returned_query_hits_number_value' => count($appointments),
                'more_hits_available_indicator' => false,
            ],
        ];
    }

    /**
     * Get appointments by center.
     *
     * @return array
     */
    public function getAppointmentsByCenter(string $centerId, array $options = [])
    {
        Log::info("MockAppointmentQueryService: Consultando citas por centro: {$centerId}");

        // Validar ID de centro
        if (empty($centerId)) {
            Log::error('ID de centro requerido');

            return [
                'success' => false,
                'error' => 'ID de centro requerido',
                'data' => null,
            ];
        }

        // Generar datos de prueba
        $customerId = $options['customer_id'] ?? '1270002726';
        $appointments = $this->generateMockAppointments($customerId, 4, array_merge($options, ['center_id' => $centerId]));

        return [
            'success' => true,
            'error' => null,
            'data' => $appointments,
            'count' => count($appointments),
            'processing_conditions' => [
                'returned_query_hits_number_value' => count($appointments),
                'more_hits_available_indicator' => false,
            ],
        ];
    }

    /**
     * Get appointments by date range.
     *
     * @return array
     */
    public function getAppointmentsByDateRange(string $startDate, string $endDate, array $options = [])
    {
        Log::info("MockAppointmentQueryService: Consultando citas por rango de fechas: {$startDate} - {$endDate}");

        // Validar fechas
        try {
            $startDateObj = Carbon::parse($startDate);
            $endDateObj = Carbon::parse($endDate);
        } catch (\Exception $e) {
            Log::error('Formato de fecha inválido: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Formato de fecha inválido: '.$e->getMessage(),
                'data' => null,
            ];
        }

        // Generar datos de prueba
        $customerId = $options['customer_id'] ?? '1270002726';
        $appointments = $this->generateMockAppointments($customerId, 6, array_merge($options, [
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]));

        return [
            'success' => true,
            'error' => null,
            'data' => $appointments,
            'count' => count($appointments),
            'processing_conditions' => [
                'returned_query_hits_number_value' => count($appointments),
                'more_hits_available_indicator' => false,
            ],
        ];
    }

    /**
     * Generate mock appointments.
     *
     * @return array
     */
    protected function generateMockAppointments(string $customerId, int $count, array $options = [])
    {
        $appointments = [];

        // Configurar opciones
        $vehiclePlate = $options['vehicle_plate'] ?? 'APP-001';
        $centerId = $options['center_id'] ?? 'M013';
        $status = $options['status'] ?? null;
        $startDate = isset($options['start_date']) ? Carbon::parse($options['start_date']) : Carbon::now();
        $endDate = isset($options['end_date']) ? Carbon::parse($options['end_date']) : Carbon::now()->addDays(30);

        // Generar citas
        for ($i = 0; $i < $count; $i++) {
            // Generar fechas aleatorias dentro del rango
            $appointmentDate = Carbon::createFromTimestamp(rand($startDate->timestamp, $endDate->timestamp));
            $appointmentEndDate = (clone $appointmentDate)->addMinutes(15);

            // Generar estado aleatorio si no se especificó
            $appointmentStatus = $status ?? rand(1, 5);
            $lifecycleStatus = 1; // Por defecto: Abierto

            // Mapear estado de cita a estado de ciclo de vida
            switch ($appointmentStatus) {
                case 1: // Generada
                case 2: // Confirmada
                    $lifecycleStatus = 1; // Abierto
                    break;
                case 3: // En progreso
                    $lifecycleStatus = 2; // En proceso
                    break;
                case 4: // Diferida
                    $lifecycleStatus = 4; // Cancelada
                    break;
                case 5: // Completada
                    $lifecycleStatus = 3; // Completada
                    break;
                case 6: // Eliminada
                    $lifecycleStatus = 4; // Cancelada
                    break;
            }

            // Mapear estado a nombre
            $statusNames = [
                1 => 'Generada',
                2 => 'Confirmada',
                3 => 'En Progreso',
                4 => 'Diferida',
                5 => 'Completada',
                6 => 'Eliminada',
            ];

            $lifecycleStatusNames = [
                1 => 'Open',
                2 => 'In Process',
                3 => 'Completed',
                4 => 'Cancelled',
            ];

            // Generar ID y UUID
            $id = 5000 + $i;
            $uuid = Str::uuid();

            // Generar datos de vehículo
            $vehicleModel = 'YARIS XLI 1.3 GSL';
            $vehicleYear = rand(2015, 2023);
            $vehicleColor = 'YARIS_070';
            $vehicleVersion = 'XLI 1.3 GSL';
            $vehicleVin = 'VINAPP'.str_pad($i, 11, '0', STR_PAD_LEFT);
            $vehicleMileage = rand(10000, 100000);
            $vehicleMotor = '2NZ-APP'.str_pad($i, 3, '0', STR_PAD_LEFT);

            // Generar datos de cliente
            $customerPhone = '+51 '.rand(900000000, 999999999);
            $customerFixedPhone = '+51 '.rand(1, 9).' '.rand(100, 999).'-'.rand(1000, 9999);
            $customerAddress = 'AV. LA MOLINA '.rand(1000, 9999);
            $customerName = 'CLIENTE DE PRUEBA '.($i + 1);

            // Generar datos de centro
            $centerDescription = 'MOLINA SERVICIO';

            // Generar datos de cita
            $appointment = [
                'id' => (string) $id,
                'uuid' => $uuid,
                'subject' => "{$vehiclePlate} {$vehicleModel} {$statusNames[$appointmentStatus]} {$customerName}",
                'location' => "{$customerName} / {$customerAddress} / LIMA 1 LIMA-LIMA / PE",
                'type' => [
                    'code' => '12',
                    'name' => 'Appointment',
                    'processing_code' => '0001',
                    'processing_name' => 'Appointment',
                ],
                'status' => [
                    'lifecycle_code' => (string) $lifecycleStatus,
                    'lifecycle_name' => $lifecycleStatusNames[$lifecycleStatus],
                    'appointment_code' => (string) $appointmentStatus,
                    'appointment_name' => $statusNames[$appointmentStatus],
                    'priority_code' => '3',
                    'priority_name' => 'Normal',
                    'initiator_code' => '3',
                    'initiator_name' => 'Outbound',
                    'information_sensitivity_code' => '1',
                    'information_sensitivity_name' => 'Normal',
                    'group_code' => '0027',
                    'group_name' => 'Visit',
                    'data_origin_type_code' => '1',
                    'data_origin_type_name' => 'Manual Data Entry',
                ],
                'dates' => [
                    'scheduled_start' => $appointmentDate->format('Y-m-d H:i:s'),
                    'scheduled_end' => $appointmentEndDate->format('Y-m-d H:i:s'),
                    'scheduled_start_date' => $appointmentDate->format('Y-m-d'),
                    'scheduled_end_date' => $appointmentEndDate->format('Y-m-d'),
                    'actual_start' => $lifecycleStatus >= 2 ? $appointmentDate->format('Y-m-d H:i:s') : null,
                    'actual_end' => $lifecycleStatus >= 3 ? $appointmentEndDate->format('Y-m-d H:i:s') : null,
                    'reported' => $appointmentDate->format('Y-m-d H:i:s'),
                    'reported_date' => $appointmentDate->format('Y-m-d'),
                    'creation' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d H:i:s'),
                    'last_change' => Carbon::now()->subDays(rand(0, 5))->format('Y-m-d H:i:s'),
                    'creation_date' => Carbon::now()->subDays(rand(1, 30))->format('Y-m-d'),
                    'last_change_date' => Carbon::now()->subDays(rand(0, 5))->format('Y-m-d'),
                    'appointment_date' => $appointmentDate->format('Y-m-d'),
                    'probable_exit_date' => $appointmentDate->format('Y-m-d'),
                    'probable_exit_time' => $appointmentEndDate->format('H:i:s'),
                    'start_time' => $appointmentDate->format('H:i:s'),
                ],
                'organization' => [
                    'sales_organization_id' => 'DM07',
                    'sales_organization_uuid' => Str::uuid(),
                    'distribution_channel_code' => 'D4',
                    'distribution_channel_name' => 'D4',
                    'division_code' => 'D1',
                    'division_name' => 'D1 - TOYOTA',
                ],
                'vehicle' => [
                    'plate' => $vehiclePlate,
                    'vin' => $vehicleVin,
                    'vin_tmp' => $vehicleVin,
                    'model_code' => '0720',
                    'model_description' => $vehicleModel,
                    'version' => $vehicleVersion,
                    'year' => (string) $vehicleYear,
                    'color' => $vehicleColor,
                    'motor' => $vehicleMotor,
                    'mileage' => (string) $vehicleMileage,
                ],
                'customer' => [
                    'id' => $customerId,
                    'phone' => $customerPhone,
                    'fixed_phone' => $customerFixedPhone,
                    'address' => $customerAddress,
                ],
                'driver' => [
                    'name' => $customerName,
                    'phone' => $customerPhone,
                    'dni' => rand(10000000, 99999999),
                ],
                'center' => [
                    'id' => $centerId,
                    'description' => $centerDescription,
                ],
                'taxi' => [
                    'requested' => '1',
                    'description' => 'Reserva Taxi',
                ],
                'telemarketing' => [
                    'advisor' => '1740',
                ],
                'flags' => [
                    'variable' => 'true',
                    'flagtre' => 'true',
                    'flagone' => 'true',
                ],
                'geo' => [
                    'start' => [
                        'latitude' => '-12.0684544',
                        'longitude' => '-76.955648',
                    ],
                    'end' => [
                        'latitude' => '-12.0653394',
                        'longitude' => '-76.9571159',
                    ],
                ],
            ];

            $appointments[] = $appointment;
        }

        return $appointments;
    }
}
