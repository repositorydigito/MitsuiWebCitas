<?php

namespace App\Console\Commands;

use App\Services\C4C\AppointmentQueryService;
use App\Services\C4C\AppointmentService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class C4CTestBookingFlow extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:test-booking-flow 
                            {--client-id= : Client internal ID}
                            {--datetime= : Appointment datetime (Y-m-d H:i)}
                            {--duration= : Duration in minutes (default: 45)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test C4C complete appointment booking flow (Ejemplo 2 de Python)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $appointmentService = app(AppointmentService::class);
        $queryService = app(AppointmentQueryService::class);

        $this->info('📅 EJEMPLO 2: FLUJO COMPLETO DE RESERVA DE CITA');
        $this->line(str_repeat('=', 60));

        // Obtener parámetros
        $clientId = $this->option('client-id') ?: '1270000347'; // Default del ejemplo Python
        $duration = (int) ($this->option('duration') ?: 45);

        // Obtener fecha/hora
        if ($this->option('datetime')) {
            $appointmentDateTime = Carbon::parse($this->option('datetime'));
        } else {
            // Mañana a las 3 PM (como en el ejemplo Python)
            $appointmentDateTime = Carbon::tomorrow()->setTime(15, 0, 0);
        }

        $this->info("Cliente: {$clientId}");
        $this->info('Fecha/Hora: '.$appointmentDateTime->format('Y-m-d H:i'));
        $this->info("Duración: {$duration} minutos");
        $this->line('');

        // PASO 1: Verificar citas pendientes existentes
        $this->info('🔍 PASO 1: Verificando citas pendientes existentes...');

        $pendingResult = $queryService->getPendingAppointments($clientId);

        if (! $pendingResult['success']) {
            $this->error('❌ Error al consultar citas pendientes: '.$pendingResult['error']);

            return 1;
        }

        $previousAppointments = $pendingResult['count'] ?? 0;
        $this->info("📊 Cliente tiene {$previousAppointments} cita(s) pendiente(s)");
        $this->line('');

        // PASO 2: Crear nueva cita
        $this->info('📝 PASO 2: Creando nueva cita...');

        $endDateTime = $appointmentDateTime->copy()->addMinutes($duration);

        $appointmentData = [
            'business_partner_id' => $clientId,
            'employee_id' => '7000002', // Empleado por defecto (como Python)
            'start_datetime' => $appointmentDateTime->format('Y-m-d\TH:i:s\Z'),
            'end_datetime' => $endDateTime->format('Y-m-d\TH:i:s\Z'),
            'observation' => 'Cita reservada via comando artisan - '.now()->format('Y-m-d H:i'),
            'client_name' => 'Cliente Sistema Web',
            'exit_date' => $appointmentDateTime->format('Y-m-d'),
            'exit_time' => $endDateTime->format('H:i:s'),
            'center_id' => 'M013', // Centro por defecto (como Python)
            'license_plate' => 'WEB-001', // Placa por defecto (como Python)
            'appointment_status' => '1', // Generada
            'is_express' => false,
        ];

        $this->info('Creando cita para '.$appointmentDateTime->format('Y-m-d H:i'));

        $createResult = $appointmentService->create($appointmentData);

        if (! $createResult['success']) {
            $this->error('❌ Error al crear la cita: '.$createResult['error']);

            return 1;
        }

        $this->info('✅ Cita creada exitosamente');
        $this->line('');

        // PASO 3: Verificar que la cita fue creada
        $this->info('🔍 PASO 3: Verificando que la cita fue creada...');

        $verifyResult = $queryService->getPendingAppointments($clientId);

        if (! $verifyResult['success']) {
            $this->error('❌ Error al verificar citas: '.$verifyResult['error']);

            return 1;
        }

        $currentAppointments = $verifyResult['count'] ?? 0;

        // Mostrar resultados finales
        $this->displayResults([
            'appointment_created' => true,
            'previous_appointments' => $previousAppointments,
            'current_appointments' => $currentAppointments,
            'appointment_data' => $appointmentData,
            'creation_response' => $createResult,
        ]);

        return 0;
    }

    /**
     * Display booking flow results.
     */
    protected function displayResults(array $result)
    {
        $this->info('🏁 RESULTADOS DEL FLUJO DE RESERVA');
        $this->line(str_repeat('=', 60));

        // Resumen
        $this->table(['Métrica', 'Valor'], [
            ['Cita creada', $result['appointment_created'] ? '✅ Sí' : '❌ No'],
            ['Citas previas', $result['previous_appointments']],
            ['Citas actuales', $result['current_appointments']],
            ['Incremento', $result['current_appointments'] - $result['previous_appointments']],
        ]);

        // Datos de la cita creada
        if ($result['appointment_created']) {
            $this->line('');
            $this->info('📋 DATOS DE LA CITA CREADA:');

            $appointmentData = $result['appointment_data'];
            $this->table(['Campo', 'Valor'], [
                ['Cliente ID', $appointmentData['business_partner_id']],
                ['Empleado ID', $appointmentData['employee_id']],
                ['Fecha/Hora Inicio', $appointmentData['start_datetime']],
                ['Fecha/Hora Fin', $appointmentData['end_datetime']],
                ['Centro', $appointmentData['center_id']],
                ['Placa', $appointmentData['license_plate']],
                ['Estado', $appointmentData['appointment_status']],
                ['Observación', $appointmentData['observation']],
            ]);

            // UUID de la cita creada (si está disponible)
            if (isset($result['creation_response']['uuid'])) {
                $this->line('');
                $this->info('🆔 UUID de la cita: '.$result['creation_response']['uuid']);
            }
        }

        $this->line('');
        $this->info('✅ Flujo de reserva completado exitosamente');
    }
}
