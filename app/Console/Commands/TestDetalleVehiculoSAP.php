<?php

namespace App\Console\Commands;

use App\Filament\Pages\DetalleVehiculo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestDetalleVehiculoSAP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:detalle-vehiculo-sap
                            {placa : Placa del vehículo a consultar}
                            {--documento= : Documento del cliente (opcional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar la integración SAP en la página de detalle del vehículo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $placa = $this->argument('placa');
        $documento = $this->option('documento');

        $this->info("🔧 Probando integración SAP para vehículo: {$placa}");
        if ($documento) {
            $this->info("📄 Usando documento del cliente: {$documento}");
        }
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
        
        $this->info("🚗 Simulando consulta de detalle del vehículo...");
        
        try {
            // Crear una instancia de la página para probar
            $detalleVehiculo = new DetalleVehiculo();

            // Si se proporcionó un documento, simular un usuario autenticado
            if ($documento) {
                $this->simularUsuarioAutenticado($documento);
            }

            // Simular la carga de datos
            $this->info("📞 Consultando datos SAP...");

            // Usar reflexión para acceder al método protegido
            $reflection = new \ReflectionClass($detalleVehiculo);
            $method = $reflection->getMethod('cargarDatosVehiculoDesdeSAP');
            $method->setAccessible(true);

            // Ejecutar el método
            $method->invoke($detalleVehiculo, $placa);
            
            // Mostrar resultados
            $vehiculo = $reflection->getProperty('vehiculo');
            $vehiculo->setAccessible(true);
            $datosVehiculo = $vehiculo->getValue($detalleVehiculo);
            
            $mantenimiento = $reflection->getProperty('mantenimiento');
            $mantenimiento->setAccessible(true);
            $datosMantenimiento = $mantenimiento->getValue($detalleVehiculo);
            
            $this->info("✅ Datos del vehículo obtenidos:");
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Placa', $datosVehiculo['placa'] ?? 'N/A'],
                    ['Modelo', $datosVehiculo['modelo'] ?? 'N/A'],
                    ['Kilometraje', $datosVehiculo['kilometraje'] ?? 'N/A'],
                    ['Fuente', $datosVehiculo['fuente'] ?? 'N/A'],
                ]
            );
            
            $this->info("✅ Datos de mantenimiento:");
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['Último servicio', $datosMantenimiento['ultimo'] ?? 'N/A'],
                    ['Fecha', $datosMantenimiento['fecha'] ?? 'N/A'],
                    ['Vencimiento', $datosMantenimiento['vencimiento'] ?? 'N/A'],
                    ['Disponibles', implode(', ', $datosMantenimiento['disponibles'] ?? [])],
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
     * Simular un usuario autenticado con el documento proporcionado
     */
    private function simularUsuarioAutenticado(string $documento): void
    {
        // Buscar un usuario existente con ese documento o crear uno temporal
        $user = \App\Models\User::where('document_number', $documento)->first();

        if (!$user) {
            // Crear un usuario temporal para la prueba
            $user = new \App\Models\User();
            $user->document_number = $documento;
            $user->name = "Usuario Test {$documento}";
            $user->email = "test{$documento}@example.com";
            $user->document_type = 'dni';
            // No guardamos el usuario, solo lo usamos para la simulación
        }

        // Simular autenticación
        \Illuminate\Support\Facades\Auth::login($user);

        $this->info("👤 Usuario simulado autenticado con documento: {$documento}");
    }
}
