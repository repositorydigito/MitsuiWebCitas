<?php

namespace App\Console\Commands;

use App\Filament\Pages\DetalleVehiculo;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TestDetalleVehiculoCompleto extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:detalle-vehiculo-completo 
                            {placa : Placa del vehículo a consultar}
                            {--documento= : Documento del cliente}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar el flujo completo de DetalleVehiculo incluyendo historial de servicios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $placa = $this->argument('placa');
        $documento = $this->option('documento') ?? '73061637';
        
        $this->info("🔧 Probando DetalleVehiculo completo para placa: {$placa}");
        $this->info("📄 Usando documento del cliente: {$documento}");
        
        try {
            // Simular usuario autenticado
            $user = User::where('document_number', $documento)->first();
            if (!$user) {
                $this->error("❌ Usuario con documento {$documento} no encontrado");
                return 1;
            }
            
            Auth::login($user);
            $this->info("👤 Usuario autenticado: {$user->name} ({$documento})");
            
            // Crear instancia de DetalleVehiculo
            $detalleVehiculo = new DetalleVehiculo();
            
            // Simular el parámetro vehiculoId
            $detalleVehiculo->vehiculoId = $placa;
            
            // Ejecutar mount() que es lo que se ejecuta cuando se carga la página
            $detalleVehiculo->mount();
            
            // Usar reflexión para acceder a las propiedades
            $reflection = new \ReflectionClass($detalleVehiculo);
            
            // Obtener datos del vehículo
            $vehiculoProperty = $reflection->getProperty('vehiculo');
            $vehiculoProperty->setAccessible(true);
            $vehiculo = $vehiculoProperty->getValue($detalleVehiculo);
            
            // Obtener datos de mantenimiento
            $mantenimientoProperty = $reflection->getProperty('mantenimiento');
            $mantenimientoProperty->setAccessible(true);
            $mantenimiento = $mantenimientoProperty->getValue($detalleVehiculo);
            
            // Obtener historial de servicios
            $historialProperty = $reflection->getProperty('historialServicios');
            $historialProperty->setAccessible(true);
            $historial = $historialProperty->getValue($detalleVehiculo);
            
            // Mostrar resultados
            $this->info("✅ Datos del vehículo:");
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Placa', $vehiculo['placa'] ?? 'N/A'],
                    ['Modelo', $vehiculo['modelo'] ?? 'N/A'],
                    ['Año', $vehiculo['anio'] ?? 'N/A'],
                    ['Marca', $vehiculo['marca'] ?? 'N/A'],
                    ['Kilometraje', $vehiculo['kilometraje'] ?? 'N/A'],
                    ['Color', $vehiculo['color'] ?? 'N/A'],
                ]
            );
            
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
            
            $this->info("✅ Historial de servicios:");
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
            
            // Probar el método getHistorialPaginadoProperty
            try {
                $historialPaginado = $detalleVehiculo->getHistorialPaginadoProperty();
                $this->info("✅ Historial paginado:");
                $this->info("📊 Total items: " . $historialPaginado->total());
                $this->info("📊 Items por página: " . $historialPaginado->perPage());
                $this->info("📊 Página actual: " . $historialPaginado->currentPage());
            } catch (\Exception $e) {
                $this->error("❌ Error en historial paginado: " . $e->getMessage());
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
