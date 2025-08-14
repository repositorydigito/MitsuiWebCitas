<?php

namespace App\Console\Commands;

use App\Services\C4C\ProductService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestC4CProductFilters extends Command
{
    protected $signature = 'c4c:test-product-filters {package_id=M2275-010}';
    protected $description = 'Test C4C product filters to verify URL construction';

    public function handle()
    {
        $packageId = $this->argument('package_id');
        
        $this->info("🧪 Testing C4C Product Filters for package: {$packageId}");
        
        // Crear una instancia temporal de ProductService para debuggear
        $productService = new ProductService();
        
        // Simular los filtros que usa el job
        $filtros = [
            'zIDPadre' => $packageId,
            'zEstado' => '02'
        ];
        
        $this->info("Filtros a aplicar:");
        $this->table(['Campo', 'Valor'], [
            ['zIDPadre', $filtros['zIDPadre']],
            ['zEstado', $filtros['zEstado']]
        ]);
        
        // Construir URL manualmente para ver cómo queda
        $baseUrl = env('C4C_PRODUCTS_URL', 'https://my317791.crm.ondemand.com/sap/c4c/odata/cust/v1/obtenerlistadoproductos/BOListaProductosProductosVinculadosCollection');
        
        $filterQuery = [];
        foreach ($filtros as $campo => $valor) {
            $filterQuery[] = "{$campo} eq '{$valor}'";
        }
        $filterString = implode(' and ', $filterQuery);
        
        $this->info("\n📋 Análisis de construcción de URL:");
        $this->line("Base URL: {$baseUrl}");
        $this->line("Filter string (raw): {$filterString}");
        $this->line("Filter string (encoded): " . urlencode($filterString));
        
        // URL final
        $finalUrl = $baseUrl . '?$filter=' . urlencode($filterString);
        $this->line("URL final: {$finalUrl}");
        
        // Probar también sin urlencode para comparar
        $finalUrlNoEncode = $baseUrl . '?$filter=' . $filterString;
        $this->line("URL sin encode: {$finalUrlNoEncode}");
        
        $this->info("\n🔍 Haciendo petición real...");
        
        // Hacer la petición real usando el servicio
        $resultado = $productService->obtenerProductosVinculados($filtros);
        
        if ($resultado['success']) {
            $productos = $resultado['data'];
            $this->info("✅ Petición exitosa!");
            $this->line("Total productos obtenidos: " . count($productos));
            
            // Verificar manualmente que los filtros se aplicaron
            $filtrosAplicados = [
                'todos_estado_02' => true,
                'todos_package_correcto' => true,
                'diferentes_estados' => [],
                'diferentes_packages' => []
            ];
            
            foreach ($productos as $producto) {
                $estado = $producto->zEstado ?? 'N/A';
                $package = $producto->zIDPadre ?? 'N/A';
                
                if ($estado !== '02') {
                    $filtrosAplicados['todos_estado_02'] = false;
                    $filtrosAplicados['diferentes_estados'][] = $estado;
                }
                
                if ($package !== $packageId) {
                    $filtrosAplicados['todos_package_correcto'] = false;
                    $filtrosAplicados['diferentes_packages'][] = $package;
                }
            }
            
            $this->info("\n📊 Verificación de filtros:");
            $this->line("¿Todos tienen estado '02'? " . ($filtrosAplicados['todos_estado_02'] ? 'SÍ' : 'NO'));
            $this->line("¿Todos tienen package correcto? " . ($filtrosAplicados['todos_package_correcto'] ? 'SÍ' : 'NO'));
            
            if (!empty($filtrosAplicados['diferentes_estados'])) {
                $this->warn("Estados encontrados diferentes a '02': " . implode(', ', array_unique($filtrosAplicados['diferentes_estados'])));
            }
            
            if (!empty($filtrosAplicados['diferentes_packages'])) {
                $this->warn("Packages encontrados diferentes a '{$packageId}': " . implode(', ', array_unique($filtrosAplicados['diferentes_packages'])));
            }
            
            // Mostrar algunos productos de ejemplo
            $this->info("\n📦 Productos de ejemplo (primeros 5):");
            $headers = ['ID Producto', 'Descripción', 'Estado', 'Package ID', 'Tipo Posición'];
            $rows = [];
            
            foreach (array_slice($productos, 0, 5) as $producto) {
                $rows[] = [
                    $producto->zIDProductoVinculado ?? 'N/A',
                    substr($producto->zDescripcionProductoVinculado ?? 'N/A', 0, 30),
                    $producto->zEstado ?? 'N/A',
                    $producto->zIDPadre ?? 'N/A',
                    $producto->zTipoPosicion ?? 'N/A'
                ];
            }
            
            $this->table($headers, $rows);
            
        } else {
            $this->error("❌ Error en la petición: " . $resultado['error']);
        }
        
        return Command::SUCCESS;
    }
}