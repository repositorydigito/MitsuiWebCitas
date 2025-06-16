<?php

namespace App\Console\Commands;

use App\Services\C4C\CustomerService;
use Illuminate\Console\Command;

class C4CTestFallback extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:test-fallback 
                            {--dni= : DNI to search first}
                            {--ruc= : RUC to search as fallback}
                            {--documents= : Comma-separated list of documents for multiple search}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test C4C customer search with fallback (Ejemplo 1 y 3 de Python)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customerService = app(CustomerService::class);

        // Test Ejemplo 1: Flujo de registro con fallback
        if ($this->option('dni') || $this->option('ruc')) {
            $this->info('🔍 EJEMPLO 1: FLUJO DE REGISTRO CON FALLBACK');
            $this->line(str_repeat('=', 60));

            $dni = $this->option('dni') ?: $this->ask('Ingrese DNI');
            $ruc = $this->option('ruc') ?: $this->ask('Ingrese RUC (opcional)', null);

            $this->info("Buscando cliente - DNI: {$dni}, RUC: {$ruc}");

            $result = $customerService->findWithFallback($dni, $ruc);

            $this->displayResult($result);
        }

        // Test Ejemplo 3: Búsqueda múltiple
        if ($this->option('documents')) {
            $this->info('🔍 EJEMPLO 3: BÚSQUEDA CON MÚLTIPLES DOCUMENTOS');
            $this->line(str_repeat('=', 60));

            $documents = explode(',', $this->option('documents'));
            $documents = array_map('trim', $documents);

            $this->info('Buscando con documentos: '.implode(', ', $documents));

            $result = $customerService->findMultiple($documents);

            $this->displayResult($result);
        }

        // Si no se proporcionan opciones, ejecutar ejemplos por defecto
        if (! $this->option('dni') && ! $this->option('ruc') && ! $this->option('documents')) {
            $this->runDefaultExamples($customerService);
        }

        return 0;
    }

    /**
     * Run default examples like Python script.
     */
    protected function runDefaultExamples(CustomerService $customerService)
    {
        // Ejemplo 1: Flujo de registro (como Python)
        $this->info('🔍 EJEMPLO 1: FLUJO DE REGISTRO DE CLIENTE');
        $this->line(str_repeat('=', 60));

        $result1 = $customerService->findWithFallback('40359482', '20558638223');
        $this->displayResult($result1);

        $this->line('');

        // Ejemplo 3: Búsqueda con fallback (como Python)
        $this->info('🔍 EJEMPLO 3: BÚSQUEDA CON FALLBACK');
        $this->line(str_repeat('=', 60));

        $result3 = $customerService->findMultiple(['12345678', '40359482', '99999999999']);
        $this->displayResult($result3);
    }

    /**
     * Display search result.
     */
    protected function displayResult(array $result)
    {
        if ($result['success']) {
            $this->info('✅ Cliente encontrado!');

            $this->table(['Campo', 'Valor'], [
                ['Tipo de búsqueda', $result['search_type'] ?? 'N/A'],
                ['Documento usado', $result['document_used'] ?? 'N/A'],
                ['Fallback usado', isset($result['fallback_used']) ? ($result['fallback_used'] ? 'Sí' : 'No') : 'N/A'],
                ['Intentos', isset($result['attempt_number']) ? "{$result['attempt_number']}/{$result['total_attempts']}" : 'N/A'],
                ['Clientes encontrados', $result['count'] ?? 0],
            ]);

            if (! empty($result['data'])) {
                $customer = $result['data'][0];
                $this->line('');
                $this->info('📋 Datos del cliente:');
                $this->table(['Campo', 'Valor'], [
                    ['UUID', $customer['uuid'] ?? 'N/A'],
                    ['ID Interno', $customer['internal_id'] ?? 'N/A'],
                    ['ID Externo', $customer['external_id'] ?? 'N/A'],
                    ['Nombre', $customer['organisation']['first_line_name'] ?? 'N/A'],
                ]);
            }
        } else {
            $this->error('❌ Cliente no encontrado');
            $this->line('Error: '.($result['error'] ?? 'Unknown error'));

            if (isset($result['documents_tried'])) {
                $this->line('Documentos probados: '.implode(', ', $result['documents_tried']));
            }
        }

        $this->line('');
    }
}
