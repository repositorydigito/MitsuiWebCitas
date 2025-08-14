<?php

namespace App\Console\Commands;

use App\Jobs\DownloadProductsJob;
use App\Models\Product;
use App\Services\C4C\ProductService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestProductDownload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:products {package_id=M2275-010} {appointment_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test product download functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $packageId = $this->argument('package_id');
        $appointmentId = $this->argument('appointment_id');

        $this->info("🧪 Testing product download for package: {$packageId}");
        
        if ($appointmentId) {
            $this->info("📋 Appointment ID: {$appointmentId}");
        }

        // 1. Test ProductService directly
        $this->info("\n1. Testing ProductService directly...");
        
        try {
            $productService = app(ProductService::class);
            
            $this->info("🔧 Service configuration:");
            $this->line("   - Base URL: " . env('C4C_PRODUCTS_URL'));
            $this->line("   - Username: " . env('C4C_PRODUCTS_USERNAME'));
            $this->line("   - Password: " . (env('C4C_PRODUCTS_PASSWORD') ? '[SET]' : '[NOT SET]'));
            
            $filtros = [
                'zIDPadre' => $packageId,
                'zEstado' => '02'
            ];
            
            $this->info("🔍 Calling obtenerProductosVinculados with filters:");
            $this->line("   - zIDPadre: {$packageId}");
            $this->line("   - zEstado: 02");
            
            $resultado = $productService->obtenerProductosVinculados($filtros);
            
            if ($resultado['success']) {
                $this->info("✅ ProductService call successful!");
                $this->line("   - Total products: " . $resultado['total']);
                
                if (!empty($resultado['data'])) {
                    $this->info("📦 First 3 products:");
                    foreach (array_slice($resultado['data'], 0, 3) as $i => $product) {
                        $this->line("   " . ($i + 1) . ". " . ($product->zDescripcionProductoVinculado ?? 'N/A') . 
                                   " (Type: " . ($product->zTipoPosicion ?? 'N/A') . ")");
                    }
                }
            } else {
                $this->error("❌ ProductService call failed:");
                $this->line("   Error: " . $resultado['error']);
                return;
            }
            
        } catch (\Exception $e) {
            $this->error("💥 Exception in ProductService:");
            $this->line("   " . $e->getMessage());
            return;
        }

        // 2. Check existing products in DB
        $this->info("\n2. Checking existing products in database...");
        
        $existingProducts = Product::forPackage($packageId)->master()->active()->get();
        $this->line("   - Master products in DB: " . $existingProducts->count());
        
        if ($appointmentId) {
            $appointmentProducts = Product::forAppointment($appointmentId)->get();
            $this->line("   - Products for appointment {$appointmentId}: " . $appointmentProducts->count());
        }

        // 3. Test job dispatch
        $this->info("\n3. Testing job dispatch...");
        
        if ($this->confirm('Do you want to dispatch DownloadProductsJob?')) {
            try {
                DownloadProductsJob::dispatch($packageId, $appointmentId);
                $this->info("✅ DownloadProductsJob dispatched successfully!");
                $this->line("   - Package ID: {$packageId}");
                $this->line("   - Appointment ID: " . ($appointmentId ?? 'none'));
                $this->line("   - Queue: products");
                
                $this->info("\n📋 Run 'php artisan queue:work' to process the job");
                
            } catch (\Exception $e) {
                $this->error("💥 Failed to dispatch job:");
                $this->line("   " . $e->getMessage());
            }
        }

        // 4. Check cache
        $this->info("\n4. Checking cache status...");
        $cacheExists = Product::existsMasterProductsForPackage($packageId, 24);
        $this->line("   - Cache valid (24h): " . ($cacheExists ? 'YES' : 'NO'));

        $this->info("\n🎯 Test completed!");
    }
}