<?php

namespace App\Console\Commands;

use App\Services\C4C\AppointmentQueryService;
use Illuminate\Console\Command;

class C4CTestBulkAppointments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:test-bulk-appointments 
                            {--clients= : Comma-separated list of client IDs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test C4C bulk appointment verification (Ejemplo 4 de Python)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $appointmentService = app(AppointmentQueryService::class);

        $this->info('📊 EJEMPLO 4: VERIFICACIÓN MASIVA DE CITAS');
        $this->line(str_repeat('=', 60));

        // Obtener lista de clientes
        if ($this->option('clients')) {
            $clientIds = explode(',', $this->option('clients'));
            $clientIds = array_map('trim', $clientIds);
        } else {
            // Usar los mismos IDs del ejemplo Python
            $clientIds = ['1270002726', '1000000001', '1270000347'];
            $this->info('Usando clientes por defecto del ejemplo Python');
        }

        $this->info('Verificando '.count($clientIds).' clientes: '.implode(', ', $clientIds));
        $this->line('');

        // Ejecutar verificación masiva
        $result = $appointmentService->bulkCheckPendingAppointments($clientIds);

        // Mostrar resumen
        $this->displaySummary($result);

        // Mostrar detalles
        $this->displayDetailedResults($result['detailed_results']);

        return 0;
    }

    /**
     * Display summary results.
     */
    protected function displaySummary(array $result)
    {
        $this->info('📊 RESUMEN DE VERIFICACIÓN MASIVA');
        $this->line(str_repeat('-', 40));

        $this->table(['Métrica', 'Valor'], [
            ['Total clientes verificados', $result['total_clients_checked']],
            ['Verificaciones exitosas', $result['successful_checks']],
            ['Verificaciones fallidas', $result['failed_checks']],
            ['Total citas pendientes', $result['total_pending_appointments']],
            ['Clientes con citas', $result['clients_with_appointments']],
            ['Clientes sin citas', $result['clients_without_appointments']],
        ]);

        // Mostrar porcentajes
        $successRate = $result['total_clients_checked'] > 0
            ? round(($result['successful_checks'] / $result['total_clients_checked']) * 100, 1)
            : 0;

        $clientsWithAppointmentsRate = $result['successful_checks'] > 0
            ? round(($result['clients_with_appointments'] / $result['successful_checks']) * 100, 1)
            : 0;

        $this->line('');
        $this->info("✅ Tasa de éxito: {$successRate}%");
        $this->info("📅 Clientes con citas: {$clientsWithAppointmentsRate}%");
        $this->line('');
    }

    /**
     * Display detailed results for each client.
     */
    protected function displayDetailedResults(array $results)
    {
        $this->info('📋 RESULTADOS DETALLADOS POR CLIENTE');
        $this->line(str_repeat('-', 40));

        $tableData = [];

        foreach ($results as $result) {
            $status = $result['success'] ? '✅' : '❌';
            $appointments = $result['success'] ? $result['pending_appointments'] : 'Error';
            $error = $result['success'] ? '-' : ($result['error'] ?? 'Unknown error');

            $tableData[] = [
                $result['client_id'],
                $status,
                $appointments,
                $result['success'] ? ($result['has_appointments'] ? 'Sí' : 'No') : '-',
                $error,
            ];
        }

        $this->table([
            'Cliente ID',
            'Estado',
            'Citas Pendientes',
            'Tiene Citas',
            'Error',
        ], $tableData);

        // Mostrar detalles de citas si hay
        foreach ($results as $result) {
            if ($result['success'] && $result['has_appointments'] && ! empty($result['appointments_data'])) {
                $this->line('');
                $this->info("📅 Citas del cliente {$result['client_id']}:");

                $appointmentTableData = [];
                foreach ($result['appointments_data'] as $appointment) {
                    $appointmentTableData[] = [
                        $appointment['uuid'] ?? 'N/A',
                        $appointment['start_datetime'] ?? 'N/A',
                        $appointment['end_datetime'] ?? 'N/A',
                        $appointment['status_name'] ?? 'N/A',
                        $appointment['center_id'] ?? 'N/A',
                        $appointment['vehicle_plate'] ?? 'N/A',
                    ];
                }

                $this->table([
                    'UUID',
                    'Inicio',
                    'Fin',
                    'Estado',
                    'Centro',
                    'Placa',
                ], $appointmentTableData);
            }
        }
    }
}
