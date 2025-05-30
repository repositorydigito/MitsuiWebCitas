<?php

namespace App\Console\Commands;

use App\Services\C4C\CustomerService;
use App\Services\C4C\AppointmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class C4CTestAppointmentFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:test-appointment-flow
                            {document_type : Tipo de documento (DNI, RUC, CE, PASSPORT)}
                            {document_number : Número de documento}
                            {--real : Usar servicio real en lugar de mock}
                            {--create-appointment : Crear una cita de prueba si se encuentra el cliente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar flujo completo: buscar cliente y gestionar citas (como Python)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documentType = strtoupper($this->argument('document_type'));
        $documentNumber = $this->argument('document_number');

        $this->info("🔍 FLUJO DE PRUEBA C4C: {$documentType} {$documentNumber}");
        $this->info(str_repeat('=', 60));

        if ($this->option('real')) {
            $this->info('Usando servicios reales (forzado por opción --real)...');
        } else {
            $this->info('Usando servicios mock (usar --real para servicios reales)...');
        }

        // PASO 1: Buscar cliente
        $this->info("\n📋 PASO 1: Buscando cliente...");
        $customer = $this->findCustomer($documentType, $documentNumber);

        if (!$customer) {
            $this->error('❌ No se pudo encontrar el cliente. Terminando flujo.');
            return 1;
        }

        $this->info("✅ Cliente encontrado: {$customer['name']} (ID: {$customer['internal_id']})");

        // PASO 2: Consultar citas pendientes
        $this->info("\n📅 PASO 2: Consultando citas pendientes...");
        $appointments = $this->queryPendingAppointments($customer['internal_id']);

        if ($appointments !== null) {
            $appointmentCount = count($appointments);
            $this->info("✅ Consulta exitosa - {$appointmentCount} citas pendientes encontradas");

            if ($appointmentCount > 0) {
                $this->info("📋 Resumen de citas pendientes:");
                foreach ($appointments as $index => $appointment) {
                    $this->info("  - Cita " . ($index + 1) . ": " . ($appointment['start_date_time'] ?? 'N/A') . " (Estado: " . ($appointment['appointment_status'] ?? 'N/A') . ")");
                }
            }
        } else {
            $this->warn("⚠️ No se pudieron consultar las citas pendientes");
        }

        // PASO 3: Crear cita de prueba (opcional)
        if ($this->option('create-appointment')) {
            $this->info("\n🆕 PASO 3: Creando cita de prueba...");
            $newAppointment = $this->createTestAppointment($customer);

            if ($newAppointment) {
                $this->info("✅ Cita de prueba creada exitosamente");
                $this->info("   UUID: " . ($newAppointment['uuid'] ?? 'N/A'));
                $this->info("   ID: " . ($newAppointment['id'] ?? 'N/A'));
                $this->info("   Change State ID: " . ($newAppointment['change_state_id'] ?? 'N/A'));
            } else {
                $this->warn("⚠️ No se pudo crear la cita de prueba");
            }
        }

        // RESUMEN FINAL
        $this->info("\n🎯 RESUMEN DEL FLUJO:");
        $this->info("✅ Cliente encontrado: {$customer['name']}");
        $this->info("✅ Citas consultadas: " . ($appointments !== null ? 'Sí' : 'No'));
        if ($this->option('create-appointment')) {
            $this->info("✅ Cita de prueba: " . (isset($newAppointment) && $newAppointment ? 'Creada' : 'Error'));
        }

        $this->info("\n🎉 Flujo completado exitosamente!");
        return 0;
    }

    /**
     * Find customer by document.
     *
     * @param string $documentType
     * @param string $documentNumber
     * @return array|null
     */
    private function findCustomer(string $documentType, string $documentNumber): ?array
    {
        try {
            $customerService = new CustomerService();

            $result = match($documentType) {
                'DNI' => $customerService->findByDNI($documentNumber),
                'RUC' => $customerService->findByRUC($documentNumber),
                'CE' => $customerService->findByCE($documentNumber),
                'PASSPORT' => $customerService->findByPassport($documentNumber),
                default => null
            };

            if ($result && $result['success'] && !empty($result['data'])) {
                $customer = $result['data'][0];
                return [
                    'internal_id' => $customer['internal_id'] ?? null,
                    'external_id' => $customer['external_id'] ?? null,
                    'name' => $customer['organisation']['first_line_name'] ?? 'N/A',
                    'uuid' => $customer['uuid'] ?? null,
                ];
            }

            return null;
        } catch (\Exception $e) {
            $this->error("Error al buscar cliente: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Query pending appointments for customer.
     *
     * @param string $clientId
     * @return array|null
     */
    private function queryPendingAppointments(string $clientId): ?array
    {
        try {
            $appointmentService = new AppointmentService();
            $result = $appointmentService->queryPendingAppointments($clientId);

            if ($result['success']) {
                return $result['data'] ?? [];
            }

            return null;
        } catch (\Exception $e) {
            $this->error("Error al consultar citas: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create test appointment for customer.
     *
     * @param array $customer
     * @return array|null
     */
    private function createTestAppointment(array $customer): ?array
    {
        try {
            $appointmentService = new AppointmentService();

            $startDateTime = Carbon::now()->addHours(2);
            $endDateTime = $startDateTime->copy()->addMinutes(30);

            $appointmentData = [
                'customer_id' => $customer['internal_id'],
                'employee_id' => '7000002',
                'start_date' => $startDateTime->format('Y-m-d H:i:s'),
                'end_date' => $endDateTime->format('Y-m-d H:i:s'),
                'center_id' => 'M013',
                'vehicle_plate' => 'TEST-' . rand(100, 999),
                'customer_name' => $customer['name'],
                'notes' => 'Cita de prueba creada desde flujo de testing Laravel',
                'express' => 'false',
            ];

            $result = $appointmentService->create($appointmentData);

            if ($result['success']) {
                return $result['data'] ?? [];
            }

            return null;
        } catch (\Exception $e) {
            $this->error("Error al crear cita: " . $e->getMessage());
            return null;
        }
    }
}
