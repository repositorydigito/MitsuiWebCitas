<?php

namespace App\Console\Commands;

use App\Services\CustomerValidationService;
use Exception;
use Illuminate\Console\Command;
use SoapClient;

class DiagnoseCustomerValidation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'diagnose:customer-validation 
                            {documento : Número de documento del cliente}
                            {--tipo=DNI : Tipo de documento (DNI, RUC, CE, PASSPORT)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnosticar problemas de validación de clientes en QA';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documento = $this->argument('documento');
        $tipo = strtoupper($this->option('tipo'));

        $this->info('🔍 Diagnóstico de CustomerValidationService');
        $this->info("📄 Documento: {$documento} (Tipo: {$tipo})");
        $this->info("🌍 Entorno: " . app()->environment());
        $this->newLine();

        // 1. Verificar configuraciones
        $this->checkConfigurations();
        $this->newLine();

        // 2. Probar conexión SAP
        $this->testSAPConnection($documento);
        $this->newLine();

        // 3. Probar conexión C4C
        $this->testC4CConnection($tipo, $documento);
        $this->newLine();

        // 4. Probar servicio completo
        $this->testFullService($tipo, $documento);

        return 0;
    }

    protected function checkConfigurations()
    {
        $this->info('📋 Verificando configuraciones...');

        // SAP
        $sapWsdl = config('services.sap_3p.wsdl_url');
        $sapUser = config('services.sap_3p.usuario');
        $sapPass = config('services.sap_3p.password');

        $this->line("   SAP WSDL: " . ($sapWsdl ? '✅ Configurado' : '❌ Faltante'));
        $this->line("   SAP Usuario: " . ($sapUser ? '✅ Configurado' : '❌ Faltante'));
        $this->line("   SAP Password: " . ($sapPass ? '✅ Configurado' : '❌ Faltante'));

        // C4C
        $c4cWsdl = config('c4c.services.customer.wsdl');
        $c4cUser = config('c4c.username');
        $c4cPass = config('c4c.password');

        $this->line("   C4C WSDL: " . ($c4cWsdl ? '✅ Configurado' : '❌ Faltante'));
        $this->line("   C4C Usuario: " . ($c4cUser ? '✅ Configurado' : '❌ Faltante'));
        $this->line("   C4C Password: " . ($c4cPass ? '✅ Configurado' : '❌ Faltante'));

        // WSDL local
        $wsdlLocal = storage_path('wsdl/vehiculos.wsdl');
        $this->line("   WSDL Local: " . (file_exists($wsdlLocal) ? '✅ Existe' : '❌ No existe'));
    }

    protected function testSAPConnection(string $documento)
    {
        $this->info('🌐 Probando conexión SAP...');

        try {
            $wsdlUrl = config('services.sap_3p.wsdl_url');
            $usuario = config('services.sap_3p.usuario');
            $password = config('services.sap_3p.password');

            if (empty($wsdlUrl) || empty($usuario) || empty($password)) {
                $this->error('   ❌ Configuración SAP incompleta');
                return;
            }

            // Intentar crear cliente SOAP
            $wsdlLocal = storage_path('wsdl/vehiculos.wsdl');
            $wsdlPath = file_exists($wsdlLocal) ? $wsdlLocal : $wsdlUrl;

            $this->line("   📁 Usando WSDL: " . (file_exists($wsdlLocal) ? 'Local' : 'Remoto'));

            $opciones = [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => 10,
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                    'http' => [
                        'timeout' => 10,
                    ],
                ]),
                'login' => $usuario,
                'password' => $password,
            ];

            $soapClient = new SoapClient($wsdlPath, $opciones);
            $this->line('   ✅ Cliente SOAP creado exitosamente');

            // Probar consulta
            $parametros = [
                'PI_NUMDOCCLI' => $documento,
            ];

            $respuesta = $soapClient->Z3PF_GETDATOSCLIENTE($parametros);

            if (isset($respuesta->PE_NOMCLI) && !empty($respuesta->PE_NOMCLI)) {
                $this->line("   ✅ Cliente encontrado: {$respuesta->PE_NOMCLI}");
                $this->line("   📧 Email: " . ($respuesta->PE_CORCLI ?: 'N/A'));
                $this->line("   📞 Teléfono: " . ($respuesta->PE_TELCLI ?: 'N/A'));
            } else {
                $this->line('   ⚠️ Cliente no encontrado en SAP');
            }

        } catch (Exception $e) {
            $this->error('   ❌ Error SAP: ' . $e->getMessage());
            $this->line('   🔍 Detalles: ' . get_class($e));
        }
    }

    protected function testC4CConnection(string $tipo, string $documento)
    {
        $this->info('🌐 Probando conexión C4C...');

        try {
            $customerService = app(\App\Services\C4C\CustomerService::class);
            $resultado = $customerService->findByDocument($tipo, $documento);

            if ($resultado) {
                $this->line('   ✅ Cliente encontrado en C4C');
                $this->line("   👤 Nombre: " . ($resultado['organisation']['first_line_name'] ?? 'N/A'));
                $this->line("   📧 Email: " . ($resultado['address_information']['address']['email']['uri'] ?? 'N/A'));
                $this->line("   🆔 Internal ID: " . ($resultado['internal_id'] ?? 'N/A'));
            } else {
                $this->line('   ⚠️ Cliente no encontrado en C4C');
            }

        } catch (Exception $e) {
            $this->error('   ❌ Error C4C: ' . $e->getMessage());
            $this->line('   🔍 Detalles: ' . get_class($e));
        }
    }

    protected function testFullService(string $tipo, string $documento)
    {
        $this->info('🔧 Probando servicio completo...');

        try {
            $validationService = app(CustomerValidationService::class);
            $resultado = $validationService->validateCustomerByDocument($tipo, $documento);

            if ($resultado) {
                $this->line('   ✅ Validación exitosa');
                $this->line("   🏢 Fuente: {$resultado['source']}");
                $this->line("   👤 Nombre: {$resultado['name']}");
                $this->line("   📧 Email: " . ($resultado['email'] ?: 'N/A'));
                $this->line("   📞 Teléfono: " . ($resultado['phone'] ?: 'N/A'));
            } else {
                $this->line('   ⚠️ No se encontraron datos (cliente comodín)');
            }

        } catch (Exception $e) {
            $this->error('   ❌ Error en servicio: ' . $e->getMessage());
            $this->line('   🔍 Detalles: ' . get_class($e));
        }
    }
}