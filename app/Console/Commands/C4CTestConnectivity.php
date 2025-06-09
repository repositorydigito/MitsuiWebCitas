<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class C4CTestConnectivity extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:test-connectivity';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar conectividad básica con los servicios C4C';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 PROBANDO CONECTIVIDAD CON SERVICIOS C4C');
        $this->info(str_repeat('=', 60));

        $endpoints = [
            'Base URL' => 'https://my317791.crm.ondemand.com',
            'Customer Service' => 'https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/querycustomerin1?sap-vhost=my317791.crm.ondemand.com',
            'Appointment Service' => 'https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/manageappointmentactivityin1?sap-vhost=my317791.crm.ondemand.com',
            'Query Appointments' => 'https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/yy6saj0kgy_wscitas?sap-vhost=my317791.crm.ondemand.com',
        ];

        $username = config('c4c.auth.username');
        $password = config('c4c.auth.password');

        foreach ($endpoints as $name => $url) {
            $this->info("\n📡 Probando: {$name}");
            $this->info("URL: {$url}");

            try {
                $startTime = microtime(true);
                
                $response = Http::withBasicAuth($username, $password)
                    ->timeout(30)
                    ->head($url);
                
                $endTime = microtime(true);
                $duration = round(($endTime - $startTime) * 1000, 2);

                if ($response->successful()) {
                    $this->info("✅ ÉXITO - Status: {$response->status()} - Tiempo: {$duration}ms");
                } else {
                    $this->warn("⚠️ RESPUESTA - Status: {$response->status()} - Tiempo: {$duration}ms");
                }

                // Mostrar headers importantes
                $headers = $response->headers();
                if (isset($headers['Server'][0])) {
                    $this->info("   Servidor: {$headers['Server'][0]}");
                }
                if (isset($headers['Content-Type'][0])) {
                    $this->info("   Content-Type: {$headers['Content-Type'][0]}");
                }

            } catch (\Exception $e) {
                $this->error("❌ ERROR: {$e->getMessage()}");
            }
        }

        $this->info("\n🔧 DIAGNÓSTICO ADICIONAL:");
        $this->info("- Timeout configurado: " . config('c4c.timeout') . " segundos");
        $this->info("- Usuario: " . $username);
        $this->info("- Fecha/Hora: " . now()->format('Y-m-d H:i:s'));

        $this->info("\n💡 RECOMENDACIONES:");
        $this->info("1. Si todos fallan: Problema de red/firewall");
        $this->info("2. Si algunos fallan: Problema específico del servicio");
        $this->info("3. Si todos son lentos (>5000ms): Problema de latencia");
        $this->info("4. Si Status 401/403: Problema de autenticación");

        return 0;
    }
}
