<?php

namespace App\Console\Commands;

use App\Services\C4C\AppointmentService;
use Illuminate\Console\Command;

class C4CDeleteAppointment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:delete-appointment 
                            {uuid : UUID de la cita a eliminar}
                            {--confirm : Confirmar eliminación sin preguntar}
                            {--real : Usar servicio real en lugar de mock}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Eliminar una cita existente en C4C (Borrar Cita)';

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

        $this->info("Eliminando cita con UUID: {$uuid}");

        // Confirmación de seguridad
        if (!$this->option('confirm')) {
            if (!$this->confirm('¿Está seguro de que desea eliminar esta cita? Esta acción no se puede deshacer.')) {
                $this->info('Operación cancelada por el usuario.');
                return 0;
            }
        }

        if ($this->option('real')) {
            $this->info('Usando servicio real (forzado por opción --real)...');
        } else {
            $this->info('Usando servicio mock (usar --real para servicio real)...');
        }

        try {
            $appointmentService = new AppointmentService();
            $result = $appointmentService->delete($uuid);

            if ($result['success']) {
                $this->info('✅ ¡Cita eliminada exitosamente!');
                
                if (isset($result['data'])) {
                    $appointment = $result['data'];
                    
                    $this->info("\n<fg=green;options=bold>--- Confirmación de Eliminación ---</>");
                    $this->info('Estado: ' . ($appointment['status'] ?? 'Eliminada'));
                    
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

                $this->info("\n<fg=red;options=bold>⚠️ IMPORTANTE:</> La cita ha sido marcada como eliminada en el sistema.");

                return 0;
            } else {
                $this->error('❌ Error al eliminar la cita');
                $this->error('Error: ' . ($result['error'] ?? 'Error desconocido'));
                
                if (isset($result['details'])) {
                    $this->info('Detalles adicionales:');
                    $this->info(json_encode($result['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                }

                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Excepción al eliminar la cita: ' . $e->getMessage());
            $this->error('Trace: ' . $e->getTraceAsString());
            return 1;
        }
    }
}
