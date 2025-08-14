<?php

namespace App\Console\Commands;

use Exception;
use Illuminate\Console\Command;
use SoapClient;

class SapTestCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sap:test-customer 
                            {documento : Número de documento del cliente}
                            {--tipo=DNI : Tipo de documento (DNI, RUC, CE, PASSPORT)}
                            {--vehicles : Incluir búsqueda de vehículos}
                            {--timeout=30 : Timeout en segundos para la conexión}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar consulta de cliente SAP y opcionalmente sus vehículos usando Z3PF_GETDATOSCLIENTE y Z3PF_GETLISTAVEHICULOS';

    /**
     * Cliente SOAP para reutilizar
     */
    private $soapClient;

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documento = $this->argument('documento');
        $tipo = strtoupper($this->option('tipo'));
        $incluirVehiculos = $this->option('vehicles');

        $this->info('🔧 Iniciando prueba de cliente SAP...');
        $this->info("📄 Documento: {$documento} (Tipo: {$tipo})");
        $this->newLine();

        // Verificar configuración
        if (! $this->verificarConfiguracion()) {
            return 1;
        }

        // Crear cliente SOAP
        if (! $this->crearClienteSoap()) {
            return 1;
        }

        // Buscar datos del cliente
        $datosCliente = $this->buscarDatosCliente($documento);
        if (! $datosCliente) {
            $this->error('❌ No se pudieron obtener los datos del cliente');

            return 1;
        }

        $this->mostrarDatosCliente($datosCliente);

        // Buscar vehículos si se solicita
        if ($incluirVehiculos) {
            $this->newLine();
            $this->info('🚗 Buscando vehículos del cliente...');
            $this->buscarVehiculosCliente($documento);
        }

        $this->newLine();
        $this->info('✅ Prueba de cliente SAP completada');

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

        if (empty($wsdlUrl) || empty($usuario) || empty($password)) {
            $this->error('❌ Configuración SAP incompleta. Verificar variables de entorno:');
            $this->error('   - SAP_3P_WSDL_URL');
            $this->error('   - SAP_3P_USUARIO');
            $this->error('   - SAP_3P_PASSWORD');

            return false;
        }

        $this->info("✅ WSDL URL: {$wsdlUrl}");
        $this->info("✅ Usuario: {$usuario}");
        $this->info('✅ Password: '.str_repeat('*', strlen($password)));

        return true;
    }

    /**
     * Crear cliente SOAP
     */
    private function crearClienteSoap(): bool
    {
        $this->info('🌐 Creando cliente SOAP...');

        $wsdlUrl = config('services.sap_3p.wsdl_url');
        $usuario = config('services.sap_3p.usuario');
        $password = config('services.sap_3p.password');
        $timeout = $this->option('timeout');

        try {
            // Intentar primero con WSDL local si existe
            $wsdlLocal = storage_path('wsdl/vehiculos.wsdl');
            if (file_exists($wsdlLocal)) {
                $this->info("📁 Usando WSDL local: {$wsdlLocal}");
                $wsdlPath = $wsdlLocal;
            } else {
                $this->info("🌐 Usando WSDL remoto: {$wsdlUrl}");
                $wsdlPath = $wsdlUrl;
            }

            $opciones = [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => $timeout,
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                    'http' => [
                        'timeout' => $timeout,
                    ],
                ]),
                'login' => $usuario,
                'password' => $password,
            ];

            $this->soapClient = new SoapClient($wsdlPath, $opciones);
            $this->info('✅ Cliente SOAP creado exitosamente');

            return true;

        } catch (Exception $e) {
            $this->error('❌ Error al crear cliente SOAP: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Buscar datos del cliente usando Z3PF_GETDATOSCLIENTE
     */
    private function buscarDatosCliente(string $documento): ?array
    {
        $this->info('👤 Consultando datos del cliente...');

        try {
            $parametros = [
                'PI_NUMDOCCLI' => $documento,
            ];

            $respuesta = $this->soapClient->Z3PF_GETDATOSCLIENTE($parametros);

            if (isset($respuesta->PE_NOMCLI) && ! empty($respuesta->PE_NOMCLI)) {
                return [
                    'nombre' => $respuesta->PE_NOMCLI ?? '',
                    'email' => $respuesta->PE_CORCLI ?? '',
                    'telefono' => $respuesta->PE_TELCLI ?? '',
                    'puntos_club' => $respuesta->PE_PUNCLU ?? null,
                ];
            } else {
                $this->warn('⚠️ Cliente no encontrado o sin datos');

                return null;
            }

        } catch (Exception $e) {
            $this->error('❌ Error al consultar cliente: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Mostrar datos del cliente
     */
    private function mostrarDatosCliente(array $datos): void
    {
        $this->info('✅ Cliente encontrado:');
        $this->line('   📝 Nombre: '.($datos['nombre'] ?: 'N/A'));
        $this->line('   📧 Email: '.($datos['email'] ?: 'N/A'));
        $this->line('   📞 Teléfono: '.($datos['telefono'] ?: 'N/A'));
        $this->line('   ⭐ Puntos Club Mitsui: '.(isset($datos['puntos_club']) ? $datos['puntos_club'] : 'N/A'));
    }

    /**
     * Buscar vehículos del cliente usando Z3PF_GETLISTAVEHICULOS
     */
    private function buscarVehiculosCliente(string $documento): void
    {
        $marcas = ['Z01', 'Z02', 'Z03']; // TOYOTA, LEXUS, HINO
        $totalVehiculos = 0;

        foreach ($marcas as $marca) {
            $this->info("🔍 Consultando marca {$marca}...");

            try {
                $parametros = [
                    'PI_NUMDOCCLI' => $documento,
                    'PI_MARCA' => $marca,
                ];

                $respuesta = $this->soapClient->Z3PF_GETLISTAVEHICULOS($parametros);

                if (isset($respuesta->TT_LISVEH) && ! empty($respuesta->TT_LISVEH)) {
                    $vehiculos = is_array($respuesta->TT_LISVEH) ? $respuesta->TT_LISVEH : [$respuesta->TT_LISVEH];
                    $count = count($vehiculos);
                    $totalVehiculos += $count;

                    $this->info("   ✅ {$count} vehículo(s) encontrado(s) para marca {$marca}");

                    foreach ($vehiculos as $vehiculo) {
                        $this->line('      🚗 Placa: '.($vehiculo->NUMPLA ?? 'N/A'));
                        $this->line('      📅 Modelo: '.($vehiculo->MODELO ?? 'N/A'));
                        $this->line('      🏭 Marca: '.($vehiculo->MARCA ?? 'N/A'));
                        $this->line('      ---');
                    }
                } else {
                    $this->line("   ⚪ Sin vehículos para marca {$marca}");
                }

            } catch (Exception $e) {
                $this->error("   ❌ Error consultando marca {$marca}: ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->info("📊 Total de vehículos encontrados: {$totalVehiculos}");
    }
}
