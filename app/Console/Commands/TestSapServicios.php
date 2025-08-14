<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

class TestSapServicios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:sap-servicios 
                            {placa : Placa del vehículo a consultar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar Z3PF_GETLISTASERVICIOS con una placa específica';

    protected ?SoapClient $soapClient = null;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $placa = $this->argument('placa');
        
        $this->info("🔧 Probando Z3PF_GETLISTASERVICIOS para placa: {$placa}");
        $this->info("📋 Verificando configuración SAP...");
        
        // Verificar configuración
        $sapEnabled = env('SAP_ENABLED', false);
        $wsdlUrl = config('services.sap_3p.wsdl_url');
        $usuario = config('services.sap_3p.usuario');
        
        $this->info("✅ SAP Habilitado: " . ($sapEnabled ? 'SÍ' : 'NO'));
        $this->info("✅ WSDL URL: " . ($wsdlUrl ? 'Configurado' : 'NO configurado'));
        $this->info("✅ Usuario: " . ($usuario ? 'Configurado' : 'NO configurado'));
        
        if (!$sapEnabled) {
            $this->error("❌ SAP está deshabilitado. Habilítalo con SAP_ENABLED=true");
            return 1;
        }

        $this->info("🚗 Consultando servicios para placa: {$placa}");
        
        try {
            // Crear cliente SOAP
            $this->crearClienteSAP();
            
            if (!$this->soapClient) {
                $this->error("❌ No se pudo crear el cliente SOAP");
                return 1;
            }

            // Consultar Z3PF_GETLISTASERVICIOS
            $this->consultarServicios($placa);
            
            $this->info("🎉 Prueba completada exitosamente!");
            
        } catch (\Exception $e) {
            $this->error("❌ Error durante la prueba: " . $e->getMessage());
            $this->error("📋 Detalles: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }

    /**
     * Crear cliente SOAP para SAP
     */
    protected function crearClienteSAP(): void
    {
        try {
            $usuario = config('services.sap_3p.usuario');
            $password = config('services.sap_3p.password');

            if (empty($usuario) || empty($password)) {
                throw new \Exception('Configuración SAP incompleta');
            }

            // Usar WSDL local
            $wsdlLocal = storage_path('wsdl/vehiculos.wsdl');

            if (!file_exists($wsdlLocal)) {
                throw new \Exception("WSDL local no encontrado: {$wsdlLocal}");
            }

            $this->info("📄 Usando WSDL local: {$wsdlLocal}");

            $options = [
                'login' => $usuario,
                'password' => $password,
                'soap_version' => SOAP_1_1,
                'trace' => true,
                'exceptions' => true,
                'connection_timeout' => 10,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
            ];

            $this->soapClient = new SoapClient($wsdlLocal, $options);
            $this->info("✅ Cliente SOAP creado exitosamente");

        } catch (\Exception $e) {
            $this->error("❌ Error al crear cliente SOAP: " . $e->getMessage());
            $this->soapClient = null;
        }
    }

    /**
     * Consultar servicios usando Z3PF_GETLISTASERVICIOS
     */
    protected function consultarServicios(string $placa): void
    {
        try {
            $this->info("📞 Llamando Z3PF_GETLISTASERVICIOS...");
            
            $parametros = ['PI_PLACA' => $placa];
            $this->info("📋 Parámetros: " . json_encode($parametros));
            
            // Realizar llamada SOAP
            $respuesta = $this->soapClient->Z3PF_GETLISTASERVICIOS($parametros);
            
            // Mostrar request y response raw
            $this->mostrarDetallesSOAP();
            
            // Procesar respuesta
            $this->procesarRespuestaServicios($respuesta);
            
        } catch (SoapFault $e) {
            $this->error("❌ Error SOAP: " . $e->getMessage());
            $this->mostrarDetallesSOAP();
        }
    }

    /**
     * Procesar y mostrar la respuesta de servicios
     */
    protected function procesarRespuestaServicios($respuesta): void
    {
        $this->info("✅ Respuesta recibida:");
        $this->info("📊 Respuesta completa (JSON): " . json_encode($respuesta, JSON_PRETTY_PRINT));
        
        // Extraer datos principales
        $kilometraje = $respuesta->PE_KILOMETRAJE ?? 'N/A';
        $placa = $respuesta->PE_PLACA ?? 'N/A';
        $ultimaFechaPrepago = $respuesta->PE_ULT_FEC_PREPAGO ?? 'N/A';
        $ultimaFechaServicio = $respuesta->PE_ULT_FEC_SERVICIO ?? 'N/A';
        $ultimoKm = $respuesta->PE_ULT_KM_ ?? 'N/A';
        
        // Mostrar datos principales
        $this->table(
            ['Campo', 'Valor'],
            [
                ['PE_KILOMETRAJE', $kilometraje],
                ['PE_PLACA', $placa],
                ['PE_ULT_FEC_PREPAGO', $ultimaFechaPrepago],
                ['PE_ULT_FEC_SERVICIO', $ultimaFechaServicio],
                ['PE_ULT_KM_', $ultimoKm],
            ]
        );
        
        // Procesar historial de servicios (TT_LISSRV)
        if (isset($respuesta->TT_LISSRV)) {
            $this->procesarHistorialServicios($respuesta->TT_LISSRV);
        } else {
            $this->warn("⚠️ No se encontró historial de servicios (TT_LISSRV)");
        }
    }

    /**
     * Procesar historial de servicios
     */
    protected function procesarHistorialServicios($ttLissrv): void
    {
        $this->info("📋 Procesando historial de servicios (TT_LISSRV):");
        $this->info("🔍 Estructura TT_LISSRV: " . json_encode($ttLissrv, JSON_PRETTY_PRINT));
        
        // Verificar si hay items
        if (isset($ttLissrv->item)) {
            $servicios = is_array($ttLissrv->item) ? $ttLissrv->item : [$ttLissrv->item];
            
            $this->info("✅ Encontrados " . count($servicios) . " servicios:");
            
            $tableData = [];
            foreach ($servicios as $index => $servicio) {
                $tableData[] = [
                    'Fecha' => $servicio->FECSRV ?? 'N/A',
                    'Servicio' => $servicio->DESSRV ?? 'N/A', 
                    'Asesor' => $servicio->ASESRV ?? 'N/A',
                    'Sede' => $servicio->SEDSRV ?? 'N/A',
                    'Tipo Pago' => $servicio->TIPPAGSRV ?? 'N/A',
                ];
            }
            
            $this->table(
                ['Fecha', 'Servicio', 'Asesor', 'Sede', 'Tipo Pago'],
                $tableData
            );
            
        } else {
            $this->warn("⚠️ No hay servicios en el historial");
        }
    }

    /**
     * Mostrar detalles de la comunicación SOAP
     */
    protected function mostrarDetallesSOAP(): void
    {
        if ($this->soapClient) {
            $this->info("📤 SOAP Request:");
            $this->line($this->soapClient->__getLastRequest() ?? 'N/A');
            
            $this->info("📥 SOAP Response:");
            $this->line($this->soapClient->__getLastResponse() ?? 'N/A');
        }
    }
}
