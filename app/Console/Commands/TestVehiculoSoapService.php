<?php

namespace App\Console\Commands;

use App\Services\VehiculoSoapService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestVehiculoSoapService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:vehiculo-soap-service 
                            {documento : Documento del cliente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar el VehiculoSoapService directamente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documento = $this->argument('documento');
        
        $this->info("🔧 Probando VehiculoSoapService para cliente: {$documento}");
        
        // Verificar configuración
        $sapEnabled = env('SAP_ENABLED', false);
        $webserviceEnabled = config('vehiculos_webservice.enabled', true);
        
        $this->info("✅ SAP Habilitado: " . ($sapEnabled ? 'SÍ' : 'NO'));
        $this->info("✅ Webservice Habilitado: " . ($webserviceEnabled ? 'SÍ' : 'NO'));
        
        if (!$sapEnabled) {
            $this->error("❌ SAP está deshabilitado. Habilítalo con SAP_ENABLED=true");
            return 1;
        }
        
        try {
            // Crear instancia del servicio
            $service = app(VehiculoSoapService::class);
            
            // Marcas a consultar
            $marcas = ['Z01', 'Z02', 'Z03'];
            
            $this->info("🚗 Consultando vehículos para marcas: " . implode(', ', $marcas));
            
            // Obtener vehículos
            $vehiculos = $service->getVehiculosCliente($documento, $marcas);
            
            $this->info("📊 Total de vehículos encontrados: " . $vehiculos->count());
            
            if ($vehiculos->isNotEmpty()) {
                $this->info("✅ Vehículos obtenidos:");
                
                $tableData = [];
                foreach ($vehiculos as $vehiculo) {
                    $tableData[] = [
                        'Placa' => $vehiculo['numpla'] ?? 'N/A',
                        'Modelo' => $vehiculo['modver'] ?? 'N/A',
                        'Año' => $vehiculo['aniomod'] ?? 'N/A',
                        'Marca' => $vehiculo['marca_codigo'] ?? 'N/A',
                        'Fuente' => $vehiculo['fuente_datos'] ?? 'N/A',
                    ];
                }
                
                $this->table(
                    ['Placa', 'Modelo', 'Año', 'Marca', 'Fuente'],
                    $tableData
                );
                
                // Mostrar el primer vehículo completo para debug
                if ($vehiculos->count() > 0) {
                    $this->info("🔍 Datos completos del primer vehículo:");
                    $primerVehiculo = $vehiculos->first();
                    foreach ($primerVehiculo as $key => $value) {
                        $this->line("  {$key}: " . (is_array($value) ? json_encode($value) : $value));
                    }
                }
            } else {
                $this->warn("⚠️ No se encontraron vehículos");
            }
            
            $this->info("🎉 Prueba completada exitosamente!");
            
        } catch (\Exception $e) {
            $this->error("❌ Error durante la prueba: " . $e->getMessage());
            $this->error("📋 Detalles: " . $e->getTraceAsString());
            return 1;
        }
        
        return 0;
    }
}
