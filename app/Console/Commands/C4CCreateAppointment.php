<?php

namespace App\Console\Commands;

use App\Services\C4C\AppointmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class C4CCreateAppointment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:create-appointment
                            {business_partner_id : ID del cliente en C4C}
                            {--employee_id=7000002 : ID del empleado}
                            {--start_datetime= : Fecha y hora de inicio (YYYY-MM-DD HH:MM)}
                            {--end_datetime= : Fecha y hora de fin (YYYY-MM-DD HH:MM)}
                            {--center_id=M013 : ID del centro}
                            {--license_plate=TEST-123 : Placa del vehículo}
                            {--client_name= : Nombre del cliente}
                            {--observation= : Observaciones de la cita}
                            {--express=false : Cita express (true/false)}
                            {--real : Usar servicio real en lugar de mock}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear una nueva cita en C4C (como Python)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $businessPartnerId = $this->argument('business_partner_id');

        // Validar que el business_partner_id sea válido
        if (empty($businessPartnerId)) {
            $this->error('El business_partner_id es requerido');

            return 1;
        }

        $this->info("Creando cita para cliente: {$businessPartnerId}");

        // Configurar fechas por defecto si no se proporcionan
        $startDateTime = $this->option('start_datetime');
        $endDateTime = $this->option('end_datetime');

        if (empty($startDateTime)) {
            $startDateTime = Carbon::now()->addHour()->format('Y-m-d H:i:s');
            $this->info("Usando fecha de inicio por defecto: {$startDateTime}");
        }

        if (empty($endDateTime)) {
            $endDateTime = Carbon::parse($startDateTime)->addMinutes(30)->format('Y-m-d H:i:s');
            $this->info("Usando fecha de fin por defecto: {$endDateTime}");
        }

        // Preparar datos de la cita (formato compatible con el método create existente)
        $appointmentData = [
            'customer_id' => $businessPartnerId,
            'employee_id' => $this->option('employee_id'),
            'start_date' => $startDateTime,
            'end_date' => $endDateTime,
            'center_id' => $this->option('center_id'),
            'vehicle_plate' => $this->option('license_plate'),
            'customer_name' => $this->option('client_name') ?: 'Cliente de Prueba',
            'notes' => $this->option('observation') ?: 'Cita creada desde comando Laravel',
            'express' => $this->option('express') ? 'true' : 'false',
        ];

        $this->info('Datos de la cita:');
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Customer ID', $appointmentData['customer_id']],
                ['Employee ID', $appointmentData['employee_id']],
                ['Fecha/Hora Inicio', $appointmentData['start_date']],
                ['Fecha/Hora Fin', $appointmentData['end_date']],
                ['Centro', $appointmentData['center_id']],
                ['Placa', $appointmentData['vehicle_plate']],
                ['Cliente', $appointmentData['customer_name']],
                ['Observación', $appointmentData['notes']],
                ['Express', $appointmentData['express']],
            ]
        );

        if ($this->option('real')) {
            $this->info('Usando servicio real (forzado por opción --real)...');
        } else {
            $this->info('Usando servicio mock (usar --real para servicio real)...');
        }

        try {
            $appointmentService = new AppointmentService;
            $result = $appointmentService->create($appointmentData);

            if ($result['success']) {
                $this->info('✅ ¡Cita procesada exitosamente!');

                if (isset($result['data'])) {
                    $appointment = $result['data'];

                    $this->info("\n<fg=green;options=bold>--- Detalles de la Cita ---</>");
                    $this->info('Estado: '.($appointment['status'] ?? 'N/A'));

                    if (isset($appointment['uuid'])) {
                        $this->info('UUID: '.$appointment['uuid']);
                    }
                    if (isset($appointment['id'])) {
                        $this->info('ID: '.$appointment['id']);
                    }
                    if (isset($appointment['change_state_id'])) {
                        $this->info('Change State ID: '.$appointment['change_state_id']);
                    }
                    if (isset($appointment['message'])) {
                        $this->info('Mensaje: '.$appointment['message']);
                    }
                }

                // Mostrar warnings si existen
                if (isset($result['warnings']) && ! empty($result['warnings'])) {
                    $this->info("\n<fg=yellow;options=bold>⚠️ Advertencias:</>");
                    foreach ($result['warnings'] as $warning) {
                        $this->warn('  - '.$warning);
                    }
                }

                return 0;
            } else {
                $this->error('❌ Error al crear la cita');
                $this->error('Error: '.($result['error'] ?? 'Error desconocido'));

                if (isset($result['details'])) {
                    $this->info('Detalles adicionales:');
                    $this->info(json_encode($result['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }

                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Excepción al crear la cita: '.$e->getMessage());
            $this->error('Trace: '.$e->getTraceAsString());

            return 1;
        }
    }
}
