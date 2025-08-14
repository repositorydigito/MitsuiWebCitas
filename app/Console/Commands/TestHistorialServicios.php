<?php

namespace App\Console\Commands;

use App\Filament\Pages\DetalleVehiculo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use SoapClient;

class TestHistorialServicios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:historial-servicios 
                            {placa : Placa del vehículo a consultar}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar Z3PF_GETLISTASERVICIOS en DetalleVehiculo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $placa = $this->argument('placa');
        
        $this->info("🔧 Probando Z3PF_GETLISTASERVICIOS para placa: {$placa}");
        
        try {
            // Crear instancia de DetalleVehiculo
            $detalleVehiculo = new DetalleVehiculo();
            
            // Crear cliente SOAP
            $cliente = $this->crearClienteSAP();
            if (!$cliente) {
                $this->error("❌ No se pudo crear el cliente SOAP");
                return 1;
            }

            // Usar reflexión para acceder al método protegido
            $reflection = new \ReflectionClass($detalleVehiculo);
            
            // Llamar directamente al método de historial de servicios
            $method = $reflection->getMethod('cargarHistorialServiciosSAP');
            $method->setAccessible(true);
            
            // Datos de vehículo simulados
            $datosVehiculo = [
                'vhclie' => 'VH001BJD733',
                'numpla' => $placa,
                'aniomod' => '2021',
                'modver' => '4X2 D/C 2GD',
                'marca_codigo' => 'Z01',
            ];
            
            // Ejecutar el método
            $method->invoke($detalleVehiculo, $cliente, $placa, $datosVehiculo);
            
            // Obtener el historial de servicios
            $historialProperty = $reflection->getProperty('historialServicios');
            $historialProperty->setAccessible(true);
            $historial = $historialProperty->getValue($detalleVehiculo);
            
            // Mostrar resultados
            $this->info("✅ Historial de servicios obtenido:");
            $this->info("📊 Total de servicios: " . $historial->count());
            
            if ($historial->count() > 0) {
                $tableData = [];
                foreach ($historial as $servicio) {
                    $tableData[] = [
                        'Fecha' => $servicio['fecha'] ?? 'N/A',
                        'Servicio' => $servicio['servicio'] ?? 'N/A',
                        'Sede' => $servicio['sede'] ?? 'N/A',
                        'Asesor' => $servicio['asesor'] ?? 'N/A',
                        'Tipo Pago' => $servicio['tipo_pago'] ?? 'N/A',
                    ];
                }
                
                $this->table(
                    ['Fecha', 'Servicio', 'Sede', 'Asesor', 'Tipo Pago'],
                    $tableData
                );
            } else {
                $this->warn("⚠️ No se encontraron servicios en el historial");
            }
            
            // Obtener datos de mantenimiento
            $mantenimientoProperty = $reflection->getProperty('mantenimiento');
            $mantenimientoProperty->setAccessible(true);
            $mantenimiento = $mantenimientoProperty->getValue($detalleVehiculo);
            
            $this->info("✅ Datos de mantenimiento:");
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Último servicio', $mantenimiento['ultimo'] ?? 'N/A'],
                    ['Fecha', $mantenimiento['fecha'] ?? 'N/A'],
                    ['Vencimiento', $mantenimiento['vencimiento'] ?? 'N/A'],
                    ['Último KM', $mantenimiento['ultimo_km'] ?? 'N/A'],
                ]
            );
            
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
    protected function crearClienteSAP(): ?SoapClient
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

            return new SoapClient($wsdlLocal, $options);

        } catch (\Exception $e) {
            $this->error("❌ Error al crear cliente SOAP: " . $e->getMessage());
            return null;
        }
    }
}
