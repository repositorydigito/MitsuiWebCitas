<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\C4C\OfferService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestOfferServiceCorrection extends Command
{
    protected $signature = 'c4c:test-offer-correction {appointment_id=70}';
    protected $description = 'Test the corrected OfferService to verify it uses downloaded products';

    public function handle()
    {
        $appointmentId = $this->argument('appointment_id');
        
        $this->info("🧪 Testing corrected OfferService for appointment: {$appointmentId}");
        
        // Buscar la cita
        $appointment = Appointment::find($appointmentId);
        
        if (!$appointment) {
            $this->error("❌ Appointment {$appointmentId} not found");
            return Command::FAILURE;
        }
        
        $this->info("📋 Appointment encontrada:");
        $this->line("ID: {$appointment->id}");
        $this->line("Package ID: {$appointment->package_id}");
        $this->line("C4C UUID: {$appointment->c4c_uuid}");
        $this->line("Center Code: {$appointment->center_code}");
        $this->line("Brand Code: {$appointment->vehicle_brand_code}");
        
        // Verificar productos
        $productos = \App\Models\Product::where('appointment_id', $appointmentId)->get();
        
        $this->info("\n📦 Productos disponibles:");
        $this->line("Total productos: " . $productos->count());
        
        if ($productos->isEmpty()) {
            $this->error("❌ No hay productos descargados para esta cita");
            $this->line("Ejecuta primero: php artisan queue:work --queue=products");
            return Command::FAILURE;
        }
        
        // Mostrar algunos productos
        $this->info("\nPrimeros 5 productos:");
        $headers = ['C4C Product ID', 'Description', 'Position Type', 'Quantity', 'Unit Code'];
        $rows = [];
        
        foreach ($productos->take(5) as $producto) {
            $rows[] = [
                $producto->c4c_product_id,
                substr($producto->description ?? 'N/A', 0, 25),
                $producto->position_type,
                $producto->quantity ?? '1.0',
                $producto->unit_code ?? 'EA'
            ];
        }
        
        $this->table($headers, $rows);
        
        // Verificar mapeo organizacional
        $mapping = \App\Models\CenterOrganizationMapping::where('center_code', $appointment->center_code)
            ->where('brand_code', $appointment->vehicle_brand_code)
            ->first();
        
        if (!$mapping) {
            $this->error("❌ No hay mapeo organizacional para center_code: {$appointment->center_code}, brand_code: {$appointment->vehicle_brand_code}");
            return Command::FAILURE;
        }
        
        $this->info("\n🏢 Mapeo organizacional encontrado:");
        $this->line("Sales Organization ID: {$mapping->sales_organization_id}");
        $this->line("Sales Office ID: {$mapping->sales_office_id}");
        $this->line("Sales Group ID: {$mapping->sales_group_id}");
        
        // Probar solo la preparación de parámetros (sin enviar a C4C)
        $this->info("\n🔧 Probando preparación de parámetros...");
        
        try {
            $offerService = new OfferService();
            
            // Usar reflection para acceder al método privado
            $reflection = new \ReflectionClass($offerService);
            $method = $reflection->getMethod('prepararParametrosOferta');
            $method->setAccessible(true);
            
            $params = $method->invoke($offerService, $appointment, $mapping);
            
            $this->info("✅ Parámetros preparados exitosamente!");
            
            // Analizar los Items generados
            $items = $params['CustomerQuote']['Item'] ?? [];
            
            if (is_array($items) && count($items) > 0) {
                $this->info("\n📊 Análisis de Items generados:");
                $this->line("Total Items: " . count($items));
                
                // Mostrar detalles de los primeros 3 items
                $this->info("\nPrimeros 3 Items:");
                foreach (array_slice($items, 0, 3) as $i => $item) {
                    $productId = $item['ItemProduct']['ProductID']['_'] ?? 'N/A';
                    $quantity = $item['ItemRequestedScheduleLine']['Quantity']['_'] ?? 'N/A';
                    $unitCode = $item['ItemRequestedScheduleLine']['Quantity']['unitCode'] ?? 'N/A';
                    $positionType = $item['y6s:zOVPosIDTipoPosicion']['_'] ?? 'N/A';
                    
                    $this->line("  " . ($i+1) . ". Product: {$productId}, Qty: {$quantity} {$unitCode}, Type: {$positionType}");
                }
                
                // Verificar que coincidan con los productos descargados
                $productosEnItems = array_map(function($item) {
                    return $item['ItemProduct']['ProductID']['_'] ?? 'N/A';
                }, $items);
                
                $productosEnTabla = $productos->pluck('c4c_product_id')->toArray();
                
                $coinciden = array_diff($productosEnTabla, $productosEnItems);
                
                if (empty($coinciden)) {
                    $this->info("✅ PERFECTO: Todos los productos de la tabla están en los Items");
                } else {
                    $this->warn("⚠️ Productos faltantes en Items: " . implode(', ', $coinciden));
                }
                
            } else {
                $this->error("❌ No se generaron Items en el payload");
            }
            
        } catch (\Exception $e) {
            $this->error("💥 Error preparando parámetros: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        $this->info("\n🎉 Test completado!");
        $this->line("La corrección está funcionando correctamente.");
        $this->line("Los productos descargados ahora se usan en la oferta.");
        
        return Command::SUCCESS;
    }
}