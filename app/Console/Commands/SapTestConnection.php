<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SoapClient;
use Exception;

class SapTestConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:test-connection
                            {--timeout=30 : Timeout en segundos para la conexión}
                            {--detailed : Mostrar información detallada}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar la conexión con los servicios SAP 3P y verificar disponibilidad de métodos Z3PF_*';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Iniciando prueba de conexión SAP...');
        $this->newLine();

        // Verificar configuración
        if (!$this->verificarConfiguracion()) {
            return 1;
        }

        // Probar conexión SOAP
        if (!$this->probarConexionSoap()) {
            return 1;
        }

        // Verificar métodos disponibles
        $this->verificarMetodosDisponibles();

        $this->newLine();
        $this->info('✅ Prueba de conexión SAP completada exitosamente');

        return 0;
    }

    /**
     * Verificar que la configuración SAP esté presente
     */
    private function verificarConfiguracion(): bool
    {
        $this->info('📋 Verificando configuración SAP...');

        $wsdlUrl = config('services.sap_3p.wsdl_url');
        $usuario = config('services.sap_3p.usuario');
        $password = config('services.sap_3p.password');

        if (empty($wsdlUrl)) {
            $this->error('❌ SAP_3P_WSDL_URL no está configurado en .env');
            return false;
        }

        if (empty($usuario)) {
            $this->error('❌ SAP_3P_USUARIO no está configurado en .env');
            return false;
        }

        if (empty($password)) {
            $this->error('❌ SAP_3P_PASSWORD no está configurado en .env');
            return false;
        }

        $this->info("✅ WSDL URL: {$wsdlUrl}");
        $this->info("✅ Usuario: {$usuario}");
        $this->info("✅ Password: " . str_repeat('*', strlen($password)));

        return true;
    }

    /**
     * Probar la conexión SOAP con SAP
     */
    private function probarConexionSoap(): bool
    {
        $this->info('🌐 Probando conexión SOAP...');

        $wsdlUrl = config('services.sap_3p.wsdl_url');
        $usuario = config('services.sap_3p.usuario');
        $password = config('services.sap_3p.password');
        $timeout = $this->option('timeout');

        try {
            $startTime = microtime(true);

            // Configurar opciones SOAP
            $options = [
                'login' => $usuario,
                'password' => $password,
                'connection_timeout' => $timeout,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'trace' => true,
                'exceptions' => true,
                'soap_version' => SOAP_1_1,
            ];

            if ($this->option('detailed')) {
                $this->info("🔧 Opciones SOAP: " . json_encode($options, JSON_PRETTY_PRINT));
            }

            // Crear cliente SOAP
            $this->info("🔗 Conectando a: {$wsdlUrl}");
            $soapClient = new SoapClient($wsdlUrl, $options);

            $endTime = microtime(true);
            $connectionTime = round(($endTime - $startTime) * 1000, 2);

            $this->info("✅ Conexión SOAP exitosa en {$connectionTime}ms");

            // Guardar cliente para uso posterior
            $this->soapClient = $soapClient;

            return true;

        } catch (Exception $e) {
            $this->error("❌ Error de conexión SOAP: " . $e->getMessage());

            if ($this->option('detailed')) {
                $this->error("Detalles del error: " . $e->getTraceAsString());
            }

            return false;
        }
    }

    /**
     * Verificar métodos SAP disponibles
     */
    private function verificarMetodosDisponibles(): void
    {
        $this->info('📋 Verificando métodos SAP disponibles...');

        if (!isset($this->soapClient)) {
            $this->warn('⚠️ No se puede verificar métodos sin conexión SOAP');
            return;
        }

        try {
            // Obtener funciones disponibles
            $functions = $this->soapClient->__getFunctions();

            $this->info("📊 Total de funciones disponibles: " . count($functions));

            // Métodos Z3PF que esperamos encontrar
            $metodosEsperados = [
                'Z3PF_GETDATOSCLIENTE' => 'Obtener datos del cliente',
                'Z3PF_GETLISTAVEHICULOS' => 'Lista de vehículos del cliente',
                'Z3PF_GETLISTASERVICIOS' => 'Historial de servicios por placa',
                'Z3PF_GETDATOSASESORPROCESO' => 'Datos del asesor asignado',
                'Z3PF_GETLISTAPREPAGOPEN' => 'Prepagos pendientes',
            ];

            $this->newLine();
            $this->info('🔍 Verificando métodos Z3PF_*:');

            foreach ($metodosEsperados as $metodo => $descripcion) {
                $encontrado = false;

                foreach ($functions as $function) {
                    if (strpos($function, $metodo) !== false) {
                        $this->info("  ✅ {$metodo} - {$descripcion}");
                        $encontrado = true;
                        break;
                    }
                }

                if (!$encontrado) {
                    $this->warn("  ⚠️ {$metodo} - {$descripcion} (NO ENCONTRADO)");
                }
            }

            if ($this->option('detailed')) {
                $this->newLine();
                $this->info('📋 Todas las funciones disponibles:');
                foreach ($functions as $function) {
                    $this->line("  • {$function}");
                }
            }

        } catch (Exception $e) {
            $this->error("❌ Error al obtener funciones: " . $e->getMessage());
        }
    }

    /**
     * Cliente SOAP para reutilizar
     */
    private $soapClient;
}
