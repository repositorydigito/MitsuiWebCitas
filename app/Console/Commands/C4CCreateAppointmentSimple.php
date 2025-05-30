<?php

namespace App\Console\Commands;

use App\Services\C4C\AppointmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class C4CCreateAppointmentSimple extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:create-appointment-simple 
                            {customer_id : ID del cliente en C4C}
                            {--employee_id=1740 : ID del empleado}
                            {--start_date= : Fecha y hora de inicio (YYYY-MM-DD HH:MM)}
                            {--end_date= : Fecha y hora de fin (YYYY-MM-DD HH:MM)}
                            {--center_id=M013 : ID del centro}
                            {--license_plate=APP-001 : Placa del vehículo}
                            {--customer_name=ALEX TOLEDO : Nombre del cliente}
                            {--customer_phone=+51 994151561 : Teléfono del cliente}
                            {--vin=VINAPP01234567891 : VIN del vehículo}
                            {--vehicle_model_code=0720 : Código del modelo}
                            {--vehicle_model=YARIS XLI 1.3 GSL : Descripción del modelo}
                            {--mileage=30252.00 : Kilometraje del vehículo}
                            {--vehicle_year=2018 : Año del vehículo}
                            {--vehicle_color=YARIS_070 : Color del vehículo}
                            {--request_taxi=1 : Solicitar taxi (1=Sí, 0=No)}
                            {--notes= : Observaciones de la cita}
                            {--real : Usar servicio real en lugar de mock}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear una nueva cita en C4C usando estructura simplificada (como el ejemplo SOAP)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerId = $this->argument('customer_id');
        
        // Validar que el customer_id sea válido
        if (empty($customerId)) {
            $this->error('El customer_id es requerido');
            return 1;
        }

        $this->info("Creando cita (método simplificado) para cliente: {$customerId}");

        // Configurar fechas por defecto si no se proporcionan
        $startDate = $this->option('start_date');
        $endDate = $this->option('end_date');
        
        if (empty($startDate)) {
            $startDate = Carbon::now()->addHour()->format('Y-m-d H:i:s');
            $this->info("Usando fecha de inicio por defecto: {$startDate}");
        }
        
        if (empty($endDate)) {
            $endDate = Carbon::parse($startDate)->addHour()->format('Y-m-d H:i:s');
            $this->info("Usando fecha de fin por defecto: {$endDate}");
        }

        // Preparar datos de la cita usando la estructura del ejemplo
        $appointmentData = [
            'customer_id' => $customerId,
            'employee_id' => $this->option('employee_id'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'center_id' => $this->option('center_id'),
            'license_plate' => $this->option('license_plate'),
            'customer_name' => $this->option('customer_name'),
            'customer_phone' => $this->option('customer_phone'),
            'vin' => $this->option('vin'),
            'vehicle_model_code' => $this->option('vehicle_model_code'),
            'vehicle_model' => $this->option('vehicle_model'),
            'mileage' => $this->option('mileage'),
            'vehicle_year' => $this->option('vehicle_year'),
            'vehicle_color' => $this->option('vehicle_color'),
            'request_taxi' => $this->option('request_taxi'),
            'notes' => $this->option('notes') ?: 'Nueva cita para ' . $this->option('license_plate') . ' ' . $this->option('vehicle_model') . ' creada desde Laravel',
        ];

        $this->info('Datos de la cita (estructura simplificada):');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Customer ID', $appointmentData['customer_id']],
                ['Employee ID', $appointmentData['employee_id']],
                ['Fecha/Hora Inicio', $appointmentData['start_date']],
                ['Fecha/Hora Fin', $appointmentData['end_date']],
                ['Centro', $appointmentData['center_id']],
                ['Placa', $appointmentData['license_plate']],
                ['Cliente', $appointmentData['customer_name']],
                ['Teléfono', $appointmentData['customer_phone']],
                ['VIN', $appointmentData['vin']],
                ['Modelo', $appointmentData['vehicle_model']],
                ['Año', $appointmentData['vehicle_year']],
                ['Color', $appointmentData['vehicle_color']],
                ['Kilometraje', $appointmentData['mileage']],
                ['Solicitar Taxi', $appointmentData['request_taxi'] === '1' ? 'Sí' : 'No'],
                ['Observaciones', $appointmentData['notes']],
            ]
        );

        if ($this->option('real')) {
            $this->info('Usando servicio real (forzado por opción --real)...');
        } else {
            $this->info('Usando servicio mock (usar --real para servicio real)...');
        }

        try {
            $appointmentService = new AppointmentService();
            $result = $appointmentService->createSimple($appointmentData);

            if ($result['success']) {
                $this->info('✅ ¡Cita creada exitosamente (método simplificado)!');
                
                if (isset($result['data'])) {
                    $appointment = $result['data'];
                    
                    $this->info("\n<fg=green;options=bold>--- Detalles de la Cita Creada ---</>");
                    $this->info('Estado: ' . ($appointment['status'] ?? 'N/A'));
                    
                    if (isset($appointment['uuid'])) {
                        $this->info('UUID: ' . $appointment['uuid']);
                    }
                    if (isset($appointment['id'])) {
                        $this->info('ID: ' . $appointment['id']);
                    }
                    if (isset($appointment['change_state_id'])) {
                        $this->info('Change State ID: ' . $appointment['change_state_id']);
                    }
                    if (isset($appointment['message'])) {
                        $this->info('Mensaje: ' . $appointment['message']);
                    }
                }

                // Mostrar warnings si existen
                if (isset($result['warnings']) && !empty($result['warnings'])) {
                    $this->info("\n<fg=yellow;options=bold>⚠️ Advertencias:</>");
                    foreach ($result['warnings'] as $warning) {
                        $this->warn('  - ' . $warning);
                    }
                }

                $this->info("\n<fg=blue;options=bold>💡 TIP:</> Usa el UUID para actualizar o eliminar esta cita más tarde.");

                return 0;
            } else {
                $this->error('❌ Error al crear la cita');
                $this->error('Error: ' . ($result['error'] ?? 'Error desconocido'));
                
                if (isset($result['details'])) {
                    $this->info('Detalles adicionales:');
                    $this->info(json_encode($result['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }

                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Excepción al crear la cita: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}
