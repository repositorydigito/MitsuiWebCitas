<?php

namespace App\Console\Commands;

use App\Services\C4C\AppointmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class C4CManageAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:manage-appointments 
                            {action : Acción a realizar (create, update, delete, query)}
                            {--customer_id= : ID del cliente (para create)}
                            {--uuid= : UUID de la cita (para update/delete)}
                            {--start_date= : Fecha y hora de inicio (YYYY-MM-DD HH:MM)}
                            {--end_date= : Fecha y hora de fin (YYYY-MM-DD HH:MM)}
                            {--center_id=M013 : ID del centro}
                            {--license_plate= : Placa del vehículo}
                            {--customer_name= : Nombre del cliente}
                            {--notes= : Observaciones}
                            {--status= : Estado del ciclo de vida}
                            {--appointment_status= : Estado de la cita}
                            {--real : Usar servicio real en lugar de mock}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gestión completa de citas en C4C (Crear, Actualizar, Eliminar, Consultar)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = strtolower($this->argument('action'));
        
        $this->info("🎯 GESTIÓN DE CITAS C4C - Acción: " . strtoupper($action));
        $this->info(str_repeat('=', 60));

        if ($this->option('real')) {
            $this->info('Usando servicio real (forzado por opción --real)...');
        } else {
            $this->info('Usando servicio mock (usar --real para servicio real)...');
        }

        try {
            $appointmentService = new AppointmentService();
            
            switch ($action) {
                case 'create':
                    return $this->handleCreate($appointmentService);
                    
                case 'update':
                    return $this->handleUpdate($appointmentService);
                    
                case 'delete':
                    return $this->handleDelete($appointmentService);
                    
                case 'query':
                    return $this->handleQuery($appointmentService);
                    
                default:
                    $this->error("Acción no válida: {$action}");
                    $this->info("Acciones disponibles: create, update, delete, query");
                    return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Excepción en gestión de citas: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Handle create appointment action.
     */
    private function handleCreate(AppointmentService $service): int
    {
        $customerId = $this->option('customer_id');
        if (empty($customerId)) {
            $this->error('--customer_id es requerido para crear una cita');
            return 1;
        }

        $startDate = $this->option('start_date') ?: Carbon::now()->addHour()->format('Y-m-d H:i:s');
        $endDate = $this->option('end_date') ?: Carbon::parse($startDate)->addMinutes(30)->format('Y-m-d H:i:s');

        $data = [
            'customer_id' => $customerId,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'center_id' => $this->option('center_id'),
            'vehicle_plate' => $this->option('license_plate') ?: 'TEST-' . rand(100, 999),
            'customer_name' => $this->option('customer_name') ?: 'Cliente Test',
            'notes' => $this->option('notes') ?: 'Cita creada desde comando de gestión',
        ];

        $this->info("\n📋 CREANDO NUEVA CITA:");
        $this->displayAppointmentData($data);

        $result = $service->create($data);
        return $this->displayResult($result, 'crear');
    }

    /**
     * Handle update appointment action.
     */
    private function handleUpdate(AppointmentService $service): int
    {
        $uuid = $this->option('uuid');
        if (empty($uuid)) {
            $this->error('--uuid es requerido para actualizar una cita');
            return 1;
        }

        $updateData = [];
        if ($this->option('status')) $updateData['status'] = $this->option('status');
        if ($this->option('appointment_status')) $updateData['appointment_status'] = $this->option('appointment_status');
        if ($this->option('start_date')) $updateData['start_date'] = $this->option('start_date');
        if ($this->option('end_date')) $updateData['end_date'] = $this->option('end_date');

        if (empty($updateData)) {
            $this->error('Debe especificar al menos un campo para actualizar (--status, --appointment_status, --start_date, --end_date)');
            return 1;
        }

        $this->info("\n📝 ACTUALIZANDO CITA: {$uuid}");
        $this->displayUpdateData($updateData);

        $result = $service->update($uuid, $updateData);
        return $this->displayResult($result, 'actualizar');
    }

    /**
     * Handle delete appointment action.
     */
    private function handleDelete(AppointmentService $service): int
    {
        $uuid = $this->option('uuid');
        if (empty($uuid)) {
            $this->error('--uuid es requerido para eliminar una cita');
            return 1;
        }

        $this->info("\n🗑️ ELIMINANDO CITA: {$uuid}");
        
        if (!$this->confirm('¿Está seguro de que desea eliminar esta cita?')) {
            $this->info('Operación cancelada.');
            return 0;
        }

        $result = $service->delete($uuid);
        return $this->displayResult($result, 'eliminar');
    }

    /**
     * Handle query appointments action.
     */
    private function handleQuery(AppointmentService $service): int
    {
        $customerId = $this->option('customer_id');
        if (empty($customerId)) {
            $this->error('--customer_id es requerido para consultar citas');
            return 1;
        }

        $this->info("\n🔍 CONSULTANDO CITAS PARA CLIENTE: {$customerId}");

        $result = $service->queryPendingAppointments($customerId);
        
        if ($result['success']) {
            $count = $result['count'] ?? 0;
            $this->info("✅ Se encontraron {$count} citas");
            
            if ($count > 0) {
                foreach ($result['data'] as $index => $appointment) {
                    $this->info("\n--- Cita " . ($index + 1) . " ---");
                    $this->info('UUID: ' . ($appointment['uuid'] ?? 'N/A'));
                    $this->info('ID: ' . ($appointment['id'] ?? 'N/A'));
                    $this->info('Cliente: ' . ($appointment['client_name'] ?? 'N/A'));
                    $this->info('Placa: ' . ($appointment['license_plate'] ?? 'N/A'));
                    $this->info('Estado: ' . ($appointment['appointment_status'] ?? 'N/A'));
                    $this->info('Centro: ' . ($appointment['center_id'] ?? 'N/A'));
                }
            }
            return 0;
        } else {
            $this->error('❌ Error al consultar citas: ' . ($result['error'] ?? 'Error desconocido'));
            return 1;
        }
    }

    /**
     * Display appointment data.
     */
    private function displayAppointmentData(array $data): void
    {
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Customer ID', $data['customer_id']],
                ['Fecha Inicio', $data['start_date']],
                ['Fecha Fin', $data['end_date']],
                ['Centro', $data['center_id']],
                ['Placa', $data['vehicle_plate']],
                ['Cliente', $data['customer_name']],
                ['Observaciones', $data['notes']],
            ]
        );
    }

    /**
     * Display update data.
     */
    private function displayUpdateData(array $data): void
    {
        $rows = [];
        foreach ($data as $key => $value) {
            $rows[] = [ucfirst(str_replace('_', ' ', $key)), $value];
        }
        $this->table(['Campo', 'Nuevo Valor'], $rows);
    }

    /**
     * Display operation result.
     */
    private function displayResult(array $result, string $operation): int
    {
        if ($result['success']) {
            $this->info("✅ ¡Cita {$operation}da exitosamente!");
            
            if (isset($result['warnings']) && !empty($result['warnings'])) {
                $this->info("\n⚠️ Advertencias:");
                foreach ($result['warnings'] as $warning) {
                    $this->warn('  - ' . $warning);
                }
            }
            
            return 0;
        } else {
            $this->error("❌ Error al {$operation} la cita");
            $this->error('Error: ' . ($result['error'] ?? 'Error desconocido'));
            return 1;
        }
    }
}
