<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si la tabla existe
        if (!Schema::hasTable('model_maintenances')) {
            return;
        }

        // Eliminar constraint problemático brand_kilometers si existe
        $this->dropIndexIfExists('model_maintenances', 'brand_kilometers');
        $this->dropIndexIfExists('model_maintenances', 'model_maintenances_brand_kilometers_unique');
        
        // Eliminar otros posibles constraints problemáticos
        $this->dropIndexIfExists('model_maintenances', 'idx_brand_kilometers');
        $this->dropIndexIfExists('model_maintenances', 'brand_kilometers_unique');
        
        // Asegurar que los constraints correctos existan
        $this->ensureCorrectConstraints();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No recrear los constraints problemáticos en down()
        // Solo log que se ejecutó el rollback
        \Log::info('Rolling back brand_kilometers constraint fix - no action needed');
    }
    
    /**
     * Elimina un índice si existe
     */
    protected function dropIndexIfExists(string $table, string $indexName): void
    {
        try {
            // Verificar si el índice existe usando una consulta directa
            $indexExists = DB::select("SELECT COUNT(*) as count FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?", [$table, $indexName]);
            
            if ($indexExists[0]->count > 0) {
                DB::statement("ALTER TABLE {$table} DROP INDEX {$indexName}");
                \Log::info("Dropped problematic index: {$indexName}");
            }
        } catch (\Exception $e) {
            // Ignorar errores si el índice no existe
            \Log::info("Index {$indexName} might not exist: " . $e->getMessage());
        }
    }
    
    /**
     * Asegurar que los constraints correctos existan
     */
    protected function ensureCorrectConstraints(): void
    {
        try {
            // Verificar que existan los constraints correctos
            $existingIndexes = DB::select("SELECT index_name FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'model_maintenances' AND index_name IN ('model_maintenances_brand_kilometers_tipo_valor_trabajo_unique', 'model_maintenances_code_tipo_valor_trabajo_unique')");
            
            $existingIndexNames = array_column($existingIndexes, 'index_name');
            
            // Crear el constraint correcto si no existe
            if (!in_array('model_maintenances_brand_kilometers_tipo_valor_trabajo_unique', $existingIndexNames)) {
                try {
                    DB::statement('ALTER TABLE model_maintenances 
                        ADD UNIQUE INDEX model_maintenances_brand_kilometers_tipo_valor_trabajo_unique 
                        (brand, kilometers, tipo_valor_trabajo)');
                    \Log::info('Created correct constraint: model_maintenances_brand_kilometers_tipo_valor_trabajo_unique');
                } catch (\Exception $e) {
                    \Log::warning('Could not create brand_kilometers_tipo_valor_trabajo constraint: ' . $e->getMessage());
                }
            }
            
            if (!in_array('model_maintenances_code_tipo_valor_trabajo_unique', $existingIndexNames)) {
                try {
                    DB::statement('ALTER TABLE model_maintenances 
                        ADD UNIQUE INDEX model_maintenances_code_tipo_valor_trabajo_unique 
                        (code, tipo_valor_trabajo)');
                    \Log::info('Created correct constraint: model_maintenances_code_tipo_valor_trabajo_unique');
                } catch (\Exception $e) {
                    \Log::warning('Could not create code_tipo_valor_trabajo constraint: ' . $e->getMessage());
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Error ensuring correct constraints: ' . $e->getMessage());
        }
    }
};
