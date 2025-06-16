<?php

namespace App\Console\Commands;

use App\Services\C4C\AppointmentService;
use Illuminate\Console\Command;

class C4CUpdateAppointment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:update-appointment
                            {uuid : UUID de la cita a actualizar}
                            {--appointment_status= : Nuevo estado de la cita (1=Generada, 2=Confirmada, etc.)}
                            {--start_date= : Nueva fecha y hora de inicio (YYYY-MM-DD HH:MM)}
                            {--end_date= : Nueva fecha y hora de fin (YYYY-MM-DD HH:MM)}
                            {--customer_name= : Nuevo nombre del cliente}
                            {--license_plate= : Nueva placa del vehículo}
                            {--center_id= : Nuevo ID del centro}
                            {--notes= : Nuevas observaciones}
                            {--real : Usar servicio real en lugar de mock}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar una cita existente en C4C (Modificar Cita)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $uuid = $this->argument('uuid');

        // Validar que el UUID sea válido
        if (empty($uuid)) {
            $this->error('El UUID de la cita es requerido');

            return 1;
        }

        $this->info("Actualizando cita con UUID: {$uuid}");

        // Preparar datos de actualización
        $updateData = [];

        if ($this->option('appointment_status')) {
            $updateData['appointment_status'] = $this->option('appointment_status');
            $this->info('Nuevo estado de cita: '.$this->option('appointment_status'));
        }

        if ($this->option('start_date')) {
            $updateData['start_date'] = $this->option('start_date');
            $this->info('Nueva fecha de inicio: '.$this->option('start_date'));
        }

        if ($this->option('end_date')) {
            $updateData['end_date'] = $this->option('end_date');
            $this->info('Nueva fecha de fin: '.$this->option('end_date'));
        }

        if ($this->option('customer_name')) {
            $updateData['customer_name'] = $this->option('customer_name');
            $this->info('Nuevo nombre del cliente: '.$this->option('customer_name'));
        }

        if ($this->option('license_plate')) {
            $updateData['license_plate'] = $this->option('license_plate');
            $this->info('Nueva placa: '.$this->option('license_plate'));
        }

        if ($this->option('center_id')) {
            $updateData['center_id'] = $this->option('center_id');
            $this->info('Nuevo centro: '.$this->option('center_id'));
        }

        if ($this->option('notes')) {
            $updateData['notes'] = $this->option('notes');
            $this->info('Nuevas observaciones: '.$this->option('notes'));
        }

        if (empty($updateData)) {
            $this->warn('No se especificaron campos para actualizar.');
            $this->info('Opciones disponibles: --appointment_status, --start_date, --end_date, --customer_name, --license_plate, --center_id, --notes');

            return 1;
        }

        if ($this->option('real')) {
            $this->info('Usando servicio real (forzado por opción --real)...');
        } else {
            $this->info('Usando servicio mock (usar --real para servicio real)...');
        }

        try {
            $appointmentService = new AppointmentService;
            $result = $appointmentService->update($uuid, $updateData);

            if ($result['success']) {
                $this->info('✅ ¡Cita actualizada exitosamente!');

                if (isset($result['data'])) {
                    $appointment = $result['data'];

                    $this->info("\n<fg=green;options=bold>--- Detalles de la Cita Actualizada ---</>");
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
                $this->error('❌ Error al actualizar la cita');
                $this->error('Error: '.($result['error'] ?? 'Error desconocido'));

                if (isset($result['details'])) {
                    $this->info('Detalles adicionales:');
                    $this->info(json_encode($result['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }

                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Excepción al actualizar la cita: '.$e->getMessage());
            $this->error('Trace: '.$e->getTraceAsString());

            return 1;
        }
    }
}
