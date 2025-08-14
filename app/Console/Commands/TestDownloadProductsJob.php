<?php

namespace App\Console\Commands;

use App\Jobs\DownloadProductsJob;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestDownloadProductsJob extends Command
{
    protected $signature = 'c4c:test-download-job {package_id=M2275-010} {--clean : Limpiar productos existentes antes de la prueba}';
    protected $description = 'Test DownloadProductsJob to compare with direct ProductService call';

    public function handle()
    {
        $packageId = $this->argument('package_id');
        $clean = $this->option('clean');
        
        $this->info("🧪 Testing DownloadProductsJob for package: {$packageId}");
        
        if ($clean) {
            $this->info("🧹 Limpiando productos existentes...");
            $deleted = Product::where('package_id', $packageId)
                ->whereNull('appointment_id')
                ->delete();
            $this->line("Eliminados: {$deleted} productos maestros");
        }
        
        // Contar productos antes
        $productosAntes = Product::where('package_id', $packageId)
            ->whereNull('appointment_id')
            ->count();
        
        $this->info("📊 Productos maestros existentes antes: {$productosAntes}");
        
        // Ejecutar el job de forma síncrona
        $this->info("🚀 Ejecutando DownloadProductsJob...");
        
        $job = new DownloadProductsJob($packageId);
        $job->handle(app(\App\Services\C4C\ProductService::class));
        
        // Contar productos después
        $productosDespu = Product::where('package_id', $packageId)
            ->whereNull('appointment_id')
            ->count();
        
        $this->info("📊 Productos maestros después: {$productosDespu}");
        $this->line("Productos creados en esta ejecución: " . ($productosDespu - $productosAntes));
        
        // Mostrar algunos productos creados
        $productos = Product::where('package_id', $packageId)
            ->whereNull('appointment_id')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
        
        $this->info("\n📦 Productos en base de datos (últimos 10):");
        $headers = ['ID', 'C4C Product ID', 'Descripción', 'Tipo Posición', 'Estado', 'Created At'];
        $rows = [];
        
        foreach ($productos as $producto) {
            $rows[] = [
                $producto->id,
                $producto->c4c_product_id,
                substr($producto->description ?? 'N/A', 0, 25),
                $producto->position_type,
                $producto->status,
                $producto->created_at->format('H:i:s')
            ];
        }
        
        $this->table($headers, $rows);
        
        // Verificar consistency
        $this->info("\n🔍 Verificando consistencia:");
        $estadosDistintos = Product::where('package_id', $packageId)
            ->whereNull('appointment_id')
            ->distinct('status')
            ->pluck('status')
            ->toArray();
        
        $packagesDistintos = Product::where('package_id', $packageId)
            ->whereNull('appointment_id')
            ->distinct('package_id')
            ->pluck('package_id')
            ->toArray();
        
        $this->line("Estados encontrados: " . implode(', ', $estadosDistintos));
        $this->line("Package IDs encontrados: " . implode(', ', $packagesDistintos));
        
        if (count($estadosDistintos) > 1) {
            $this->warn("⚠️ Se encontraron múltiples estados, posible problema de filtro");
        }
        
        if (count($packagesDistintos) > 1) {
            $this->warn("⚠️ Se encontraron múltiples package IDs, posible problema de filtro");
        }
        
        return Command::SUCCESS;
    }
}