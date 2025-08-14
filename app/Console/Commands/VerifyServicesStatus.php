<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use SoapClient;

class VerifyServicesStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:verify 
                            {--timeout=30 : Timeout en segundos para las conexiones}
                            {--detailed : Mostrar información detallada de configuración}
                            {--quick : Verificación rápida sin pruebas de conectividad}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar estado y disponibilidad de servicios C4C y SAP leyendo configuración del .env';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 VERIFICADOR DE SERVICIOS C4C Y SAP');
        $this->info(str_repeat('=', 60));
        $this->newLine();

        $timeout = $this->option('timeout');
        $detailed = $this->option('detailed');
        $quick = $this->option('quick');

        // 1. Verificar configuración del .env
        $this->info('📋 VERIFICANDO CONFIGURACIÓN DEL .ENV');
        $this->line(str_repeat('-', 40));
        $this->verificarConfiguracionEnv($detailed);
        $this->newLine();

        $c4cStatus = ['enabled' => env('C4C_WEBSERVICE_ENABLED', false)];
        $sapStatus = ['enabled' => env('SAP_WEBSERVICE_ENABLED', false) && env('SAP_ENABLED', false)];

        if (!$quick) {
            // 2. Verificar estado de servicios C4C
            $this->info('☁️  VERIFICANDO SERVICIOS C4C');
            $this->line(str_repeat('-', 40));
            $c4cStatus = $this->verificarServiciosC4C($timeout, $detailed);
            $this->newLine();

            // 3. Verificar estado de servicios SAP
            $this->info('🔧 VERIFICANDO SERVICIOS SAP 3P');
            $this->line(str_repeat('-', 40));
            $sapStatus = $this->verificarServiciosSAP($timeout, $detailed);
            $this->newLine();
        } else {
            $this->comment('⚡ Modo rápido: Saltando pruebas de conectividad...');
            $this->newLine();
        }

        // 4. Verificar archivos WSDL locales
        $this->info('📁 VERIFICANDO WSDL LOCALES');
        $this->line(str_repeat('-', 40));
        $this->verificarWSDLLocales();
        $this->newLine();

        // 5. Resumen final
        $this->mostrarResumenFinal($c4cStatus, $sapStatus);

        return 0;
    }

    private function verificarConfiguracionEnv(bool $detailed): void
    {
        $configuraciones = [
            // Configuración general
            'USE_MOCK_SERVICES' => env('USE_MOCK_SERVICES', false) ? 'true' : 'false',
            'C4C_WEBSERVICE_ENABLED' => env('C4C_WEBSERVICE_ENABLED', false) ? 'true' : 'false',
            'SAP_WEBSERVICE_ENABLED' => env('SAP_WEBSERVICE_ENABLED', false) ? 'true' : 'false',
            'SAP_ENABLED' => env('SAP_ENABLED', false) ? 'true' : 'false',
            'AUTO_FALLBACK_TO_MOCK' => env('AUTO_FALLBACK_TO_MOCK', false) ? 'true' : 'false',
            
            // Configuración de vehículos
            'VEHICULOS_WEBSERVICE_ENABLED' => env('VEHICULOS_WEBSERVICE_ENABLED', false) ? 'true' : 'false',
            'VEHICULOS_PREFER_LOCAL_WSDL' => env('VEHICULOS_PREFER_LOCAL_WSDL', false) ? 'true' : 'false',
            
            // Credenciales C4C
            'C4C_USERNAME' => env('C4C_USERNAME') ? '✓ Configurado' : '❌ No configurado',
            'C4C_PASSWORD' => env('C4C_PASSWORD') ? '✓ Configurado' : '❌ No configurado',
            
            // Credenciales SAP
            'SAP_3P_USUARIO' => env('SAP_3P_USUARIO') ? '✓ Configurado' : '❌ No configurado',
            'SAP_3P_PASSWORD' => env('SAP_3P_PASSWORD') ? '✓ Configurado' : '❌ No configurado',
        ];

        foreach ($configuraciones as $key => $value) {
            $status = $this->evaluarEstado($key, $value);
            $this->line("  {$status} {$key}: {$value}");
        }

        if ($detailed) {
            $this->newLine();
            $this->comment('📍 URLs configuradas:');
            $urls = [
                'C4C_CUSTOMER_WSDL' => env('C4C_CUSTOMER_WSDL'),
                'C4C_APPOINTMENT_WSDL' => env('C4C_APPOINTMENT_WSDL'),
                'C4C_VEHICLES_URL' => env('C4C_VEHICLES_URL'),
                'SAP_3P_WSDL_URL' => env('SAP_3P_WSDL_URL'),
            ];

            foreach ($urls as $key => $url) {
                $this->line("  • {$key}:");
                $this->line("    {$url}");
            }
        }
    }

    private function verificarServiciosC4C(int $timeout, bool $detailed): array
    {
        $status = [];
        $isEnabled = env('C4C_WEBSERVICE_ENABLED', false);

        if (!$isEnabled) {
            $this->warn('⚠️  C4C WebService está DESHABILITADO en .env');
            $status['enabled'] = false;
            return $status;
        }

        $this->info('✅ C4C WebService está HABILITADO');
        $status['enabled'] = true;

        // Verificar servicios C4C
        $servicios = [
            'Customer Service (WSDL)' => env('C4C_CUSTOMER_WSDL'),
            'Appointment Service (WSDL)' => env('C4C_APPOINTMENT_WSDL'),
            'Vehicles Service (OData)' => env('C4C_VEHICLES_URL'),
            'Products Service (OData)' => env('C4C_PRODUCTS_URL'),
            'Availability Service (OData)' => env('C4C_AVAILABILITY_BASE_URL'),
        ];

        $username = env('C4C_USERNAME');
        $password = env('C4C_PASSWORD');

        foreach ($servicios as $nombre => $url) {
            if (!$url) {
                $this->error("  ❌ {$nombre}: URL no configurada");
                $status[$nombre] = false;
                continue;
            }

            $this->line("  🔍 Verificando: {$nombre}");
            
            try {
                $startTime = microtime(true);
                
                if (str_contains($nombre, 'WSDL')) {
                    // Verificar servicios WSDL/SOAP
                    $response = Http::withBasicAuth($username, $password)
                        ->timeout($timeout)
                        ->get($url);
                } else {
                    // Verificar servicios OData
                    $odataUsername = str_contains($nombre, 'Vehicles') ? env('C4C_VEHICLES_USERNAME') : env('C4C_PRODUCTS_USERNAME');
                    $odataPassword = str_contains($nombre, 'Vehicles') ? env('C4C_VEHICLES_PASSWORD') : env('C4C_PRODUCTS_PASSWORD');
                    
                    $response = Http::withBasicAuth($odataUsername, $odataPassword)
                        ->timeout($timeout)
                        ->get($url);
                }

                $responseTime = round((microtime(true) - $startTime) * 1000, 2);

                if ($response->successful()) {
                    $this->info("    ✅ Disponible ({$responseTime}ms) - HTTP {$response->status()}");
                    $status[$nombre] = true;
                    
                    if ($detailed) {
                        $this->comment("      Content-Type: " . $response->header('Content-Type'));
                    }
                } else {
                    $this->error("    ❌ Error HTTP {$response->status()} ({$responseTime}ms)");
                    $status[$nombre] = false;
                }

            } catch (Exception $e) {
                $this->error("    ❌ Error de conexión: " . $e->getMessage());
                $status[$nombre] = false;
            }
        }

        return $status;
    }

    private function verificarServiciosSAP(int $timeout, bool $detailed): array
    {
        $status = [];
        $isEnabled = env('SAP_WEBSERVICE_ENABLED', false);
        $sapEnabled = env('SAP_ENABLED', false);

        if (!$isEnabled || !$sapEnabled) {
            $this->warn('⚠️  SAP WebService está DESHABILITADO en .env');
            $this->line("  SAP_WEBSERVICE_ENABLED: " . (env('SAP_WEBSERVICE_ENABLED', false) ? 'true' : 'false'));
            $this->line("  SAP_ENABLED: " . (env('SAP_ENABLED', false) ? 'true' : 'false'));
            $status['enabled'] = false;
            return $status;
        }

        $this->info('✅ SAP WebService está HABILITADO');
        $status['enabled'] = true;

        $wsdlUrl = env('SAP_3P_WSDL_URL');
        $usuario = env('SAP_3P_USUARIO');
        $password = env('SAP_3P_PASSWORD');

        if (!$wsdlUrl) {
            $this->error('  ❌ SAP_3P_WSDL_URL no está configurada');
            $status['wsdl'] = false;
            return $status;
        }

        $this->line("  🔍 Verificando: SAP WSDL Service");
        
        try {
            $startTime = microtime(true);

            // Verificar disponibilidad del WSDL
            $response = Http::timeout($timeout)->get($wsdlUrl);
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);

            if ($response->successful()) {
                $this->info("    ✅ WSDL disponible ({$responseTime}ms) - HTTP {$response->status()}");
                $status['wsdl'] = true;

                // Intentar crear cliente SOAP
                $this->line("  🔍 Verificando cliente SOAP...");
                try {
                    $soapClient = new SoapClient($wsdlUrl, [
                        'login' => $usuario,
                        'password' => $password,
                        'connection_timeout' => $timeout,
                        'cache_wsdl' => WSDL_CACHE_NONE,
                        'trace' => true,
                        'exceptions' => true
                    ]);

                    $this->info("    ✅ Cliente SOAP creado exitosamente");
                    $status['soap_client'] = true;

                    if ($detailed) {
                        $methods = $soapClient->__getFunctions();
                        $z3pfMethods = array_filter($methods, fn($method) => str_contains($method, 'Z3PF_'));
                        
                        $this->comment("    📋 Métodos Z3PF_ disponibles: " . count($z3pfMethods));
                        if (count($z3pfMethods) > 0) {
                            foreach (array_slice($z3pfMethods, 0, 3) as $method) {
                                $this->line("      • " . trim(explode('(', $method)[0]));
                            }
                            if (count($z3pfMethods) > 3) {
                                $this->line("      • ... y " . (count($z3pfMethods) - 3) . " más");
                            }
                        }
                    }

                } catch (Exception $e) {
                    $this->error("    ❌ Error creando cliente SOAP: " . $e->getMessage());
                    $status['soap_client'] = false;
                }

            } else {
                $this->error("    ❌ WSDL no disponible - HTTP {$response->status()} ({$responseTime}ms)");
                $status['wsdl'] = false;
            }

        } catch (Exception $e) {
            $this->error("    ❌ Error de conexión: " . $e->getMessage());
            $status['wsdl'] = false;
        }

        return $status;
    }

    private function verificarWSDLLocales(): void
    {
        $preferLocal = env('VEHICULOS_PREFER_LOCAL_WSDL', false);
        
        $this->line("  📋 VEHICULOS_PREFER_LOCAL_WSDL: " . ($preferLocal ? '✅ true' : '⚠️  false'));

        if (!$preferLocal) {
            $this->comment("  ℹ️  Configurado para usar WSDL remotos");
            return;
        }

        // Verificar archivos WSDL locales comunes
        $wsdlPaths = [
            'storage/app/wsdl/',
            'storage/wsdl/',
            'resources/wsdl/',
            'public/wsdl/',
        ];

        $wsdlEncontrados = [];
        
        foreach ($wsdlPaths as $path) {
            $fullPath = base_path($path);
            if (File::exists($fullPath)) {
                $files = File::files($fullPath);
                $wsdlFiles = array_filter($files, fn($file) => str_ends_with($file->getFilename(), '.wsdl'));
                
                if (count($wsdlFiles) > 0) {
                    $wsdlEncontrados[$path] = $wsdlFiles;
                }
            }
        }

        if (empty($wsdlEncontrados)) {
            $this->warn("  ⚠️  No se encontraron archivos WSDL locales");
            $this->comment("  💡 Directorios verificados: " . implode(', ', $wsdlPaths));
        } else {
            $this->info("  ✅ Archivos WSDL locales encontrados:");
            foreach ($wsdlEncontrados as $path => $files) {
                $this->line("    📁 {$path}:");
                foreach ($files as $file) {
                    $size = File::size($file->getPathname());
                    $sizeFormatted = $this->formatBytes($size);
                    $modified = date('Y-m-d H:i:s', File::lastModified($file->getPathname()));
                    $this->line("      • {$file->getFilename()} ({$sizeFormatted}) - {$modified}");
                }
            }
        }
    }

    private function mostrarResumenFinal(array $c4cStatus, array $sapStatus): void
    {
        $this->info('📊 RESUMEN FINAL');
        $this->line(str_repeat('=', 40));

        // Estado general de servicios
        $this->line('🔧 Estado de Servicios:');
        $c4cEnabled = $c4cStatus['enabled'] ?? false;
        $sapEnabled = $sapStatus['enabled'] ?? false;
        $useMock = env('USE_MOCK_SERVICES', false);

        $this->line("  C4C WebService: " . ($c4cEnabled ? '✅ Habilitado' : '❌ Deshabilitado'));
        $this->line("  SAP WebService: " . ($sapEnabled ? '✅ Habilitado' : '❌ Deshabilitado'));
        $this->line("  Servicios Mock: " . ($useMock ? '⚠️  Habilitado' : '✅ Deshabilitado'));

        // Recomendaciones
        $this->newLine();
        $this->line('💡 Recomendaciones:');
        
        if (!$c4cEnabled && !$sapEnabled) {
            $this->error('  ⚠️  Ambos servicios están deshabilitados - el sistema funcionará con mocks');
        } elseif ($useMock) {
            $this->warn('  ⚠️  Servicios mock están habilitados - esto puede sobrescribir servicios reales');
        } else {
            $this->info('  ✅ Configuración de servicios parece correcta');
        }

        // Estado de conectividad
        if ($c4cEnabled) {
            $c4cOk = collect($c4cStatus)->except(['enabled'])->filter()->count();
            $c4cTotal = collect($c4cStatus)->except(['enabled'])->count();
            if ($c4cTotal > 0) {
                $this->line("  🌐 C4C Conectividad: {$c4cOk}/{$c4cTotal} servicios disponibles");
            }
        }

        if ($sapEnabled) {
            $sapOk = ($sapStatus['wsdl'] ?? false) && ($sapStatus['soap_client'] ?? false) ? '2' : (($sapStatus['wsdl'] ?? false) ? '1' : '0');
            $this->line("  🌐 SAP Conectividad: {$sapOk}/2 verificaciones exitosas");
        }
    }

    private function evaluarEstado(string $key, $value): string
    {
        $habilitadores = ['C4C_WEBSERVICE_ENABLED', 'SAP_WEBSERVICE_ENABLED', 'SAP_ENABLED'];
        $deshabilitadores = ['USE_MOCK_SERVICES', 'AUTO_FALLBACK_TO_MOCK'];

        if (in_array($key, $habilitadores)) {
            return $value === 'true' ? '✅' : '❌';
        }

        if (in_array($key, $deshabilitadores)) {
            return $value === 'true' ? '⚠️ ' : '✅';
        }

        if (str_contains($value, '✓ Configurado')) {
            return '✅';
        }

        if (str_contains($value, '❌ No configurado')) {
            return '❌';
        }

        return '📋';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' B';
        }
    }
}