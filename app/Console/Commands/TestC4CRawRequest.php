<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestC4CRawRequest extends Command
{
    protected $signature = 'c4c:test-raw-request {package_id=M2275-010} {--no-cache : Ignorar cache}';
    protected $description = 'Test raw HTTP request exactly like Postman would do';

    public function handle()
    {
        $packageId = $this->argument('package_id');
        $noCache = $this->option('no-cache');
        
        $this->info("🧪 Testing Raw HTTP Request for package: {$packageId}");
        
        // URLs to test
        $baseUrl = 'https://my317791.crm.ondemand.com/sap/c4c/odata/cust/v1/obtenerlistadoproductos/BOListaProductosProductosVinculadosCollection';
        
        $urls = [
            'Sin filtros' => $baseUrl,
            'Solo estado' => $baseUrl . "?\$filter=zEstado eq '02'",
            'Solo package' => $baseUrl . "?\$filter=zIDPadre eq '{$packageId}'",
            'Ambos filtros (encoded)' => $baseUrl . '?$filter=' . urlencode("zIDPadre eq '{$packageId}' and zEstado eq '02'"),
            'Ambos filtros (no encoded)' => $baseUrl . "?\$filter=zIDPadre eq '{$packageId}' and zEstado eq '02'"
        ];
        
        $username = env('C4C_PRODUCTS_USERNAME', '_ODATA');
        $password = env('C4C_PRODUCTS_PASSWORD');
        
        $this->info("Credenciales: {$username} / " . substr($password, 0, 3) . "...");
        
        foreach ($urls as $description => $url) {
            $this->info("\n🔍 Testing: {$description}");
            $this->line("URL: {$url}");
            
            try {
                $startTime = microtime(true);
                
                // Hacer petición usando Laravel HTTP client (similar a Postman)
                $response = Http::withBasicAuth($username, $password)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json'
                    ])
                    ->timeout(120)
                    ->get($url);
                
                $responseTime = round((microtime(true) - $startTime) * 1000, 2);
                
                if ($response->successful()) {
                    $data = $response->json();
                    $productos = $data['d']['results'] ?? [];
                    
                    $this->info("✅ Success! ({$responseTime}ms)");
                    $this->line("Total productos: " . count($productos));
                    
                    if (!empty($productos)) {
                        // Verificar filtros manualmente
                        $estadosEncontrados = array_unique(array_map(function($p) {
                            return $p['zEstado'] ?? 'N/A';
                        }, $productos));
                        
                        $packagesEncontrados = array_unique(array_map(function($p) {
                            return $p['zIDPadre'] ?? 'N/A';
                        }, $productos));
                        
                        $this->line("Estados encontrados: " . implode(', ', $estadosEncontrados));
                        $this->line("Packages encontrados: " . implode(', ', $packagesEncontrados));
                        
                        // Mostrar tipos de posición
                        $tiposEncontrados = array_count_values(array_map(function($p) {
                            return $p['zTipoPosicion'] ?? 'N/A';
                        }, $productos));
                        
                        $this->line("Tipos de posición:");
                        foreach ($tiposEncontrados as $tipo => $cantidad) {
                            $this->line("  - {$tipo}: {$cantidad}");
                        }
                        
                        // Mostrar primeros 3 productos
                        $this->line("\nPrimeros 3 productos:");
                        foreach (array_slice($productos, 0, 3) as $i => $producto) {
                            $this->line("  " . ($i+1) . ". " . ($producto['zIDProductoVinculado'] ?? 'N/A') . " - " . ($producto['zDescripcionProductoVinculado'] ?? 'Sin descripción'));
                        }
                    }
                    
                } else {
                    $this->error("❌ HTTP {$response->status()}");
                    $this->line("Response: " . substr($response->body(), 0, 200));
                }
                
            } catch (\Exception $e) {
                $this->error("💥 Exception: " . $e->getMessage());
            }
        }
        
        return Command::SUCCESS;
    }
}