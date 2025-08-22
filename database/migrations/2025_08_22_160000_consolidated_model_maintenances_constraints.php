<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Verificar si la tabla existe
        if (!Schema::hasTable('model_maintenances')) {
            return;
        }

        // Eliminar restricciones únicas existentes de manera segura
        $this->dropIndexIfExists('model_maintenances', 'model_maintenances_brand_kilometers_unique');
        $this->dropIndexIfExists('model_maintenances', 'model_maintenances_code_unique');
        $this->dropIndexIfExists('model_maintenances', 'model_maintenances_code_tipo_valor_trabajo_unique');
        $this->dropIndexIfExists('model_maintenances', 'model_maintenances_brand_kilometers_tipo_valor_trabajo_unique');

        // Eliminar duplicados basados en code + tipo_valor_trabajo
        DB::statement('DELETE t1 FROM model_maintenances t1
            INNER JOIN model_maintenances t2 
            WHERE t1.id > t2.id 
            AND t1.code = t2.code 
            AND (t1.tipo_valor_trabajo = t2.tipo_valor_trabajo 
                 OR (t1.tipo_valor_trabajo IS NULL AND t2.tipo_valor_trabajo IS NULL))');

        // Eliminar duplicados basados en brand + kilometers + tipo_valor_trabajo
        DB::statement('DELETE t1 FROM model_maintenances t1
            INNER JOIN model_maintenances t2 
            WHERE t1.id > t2.id 
            AND t1.brand = t2.brand 
            AND t1.kilometers = t2.kilometers
            AND (t1.tipo_valor_trabajo = t2.tipo_valor_trabajo 
                 OR (t1.tipo_valor_trabajo IS NULL AND t2.tipo_valor_trabajo IS NULL))');

        // Crear restricción única para code + tipo_valor_trabajo
        if (!Schema::hasColumn('model_maintenances', 'code') || !Schema::hasColumn('model_maintenances', 'tipo_valor_trabajo')) {
            throw new \Exception('Required columns (code, tipo_valor_trabajo) do not exist in model_maintenances table');
        }

        // Crear restricción única para brand + kilometers + tipo_valor_trabajo
        if (!Schema::hasColumn('model_maintenances', 'brand') || 
            !Schema::hasColumn('model_maintenances', 'kilometers')) {
            throw new \Exception('Required columns (brand, kilometers) do not exist in model_maintenances table');
        }

        // Crear índices únicos
        try {
            // Verificar primero si los índices ya existen
            $existingIndexes = DB::select("SELECT index_name FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = 'model_maintenances' AND index_name IN ('model_maintenances_code_tipo_valor_trabajo_unique', 'model_maintenances_brand_kilometers_tipo_valor_trabajo_unique')");
            
            $existingIndexNames = array_column($existingIndexes, 'index_name');
            
            if (!in_array('model_maintenances_code_tipo_valor_trabajo_unique', $existingIndexNames)) {
                DB::statement('ALTER TABLE model_maintenances 
                    ADD UNIQUE INDEX model_maintenances_code_tipo_valor_trabajo_unique 
                    (code, tipo_valor_trabajo)');
            }

            if (!in_array('model_maintenances_brand_kilometers_tipo_valor_trabajo_unique', $existingIndexNames)) {
                DB::statement('ALTER TABLE model_maintenances 
                    ADD UNIQUE INDEX model_maintenances_brand_kilometers_tipo_valor_trabajo_unique 
                    (brand, kilometers, tipo_valor_trabajo)');
            }
        } catch (\Exception $e) {
            \Log::error('Error creating indexes: ' . $e->getMessage());
            
            // Si falla, intentar con Schema builder como fallback
            try {
                Schema::table('model_maintenances', function ($table) {
                    $table->unique(['code', 'tipo_valor_trabajo'], 'model_maintenances_code_tipo_valor_trabajo_unique');
                    $table->unique(['brand', 'kilometers', 'tipo_valor_trabajo'], 'model_maintenances_brand_kilometers_tipo_valor_trabajo_unique');
                });
            } catch (\Exception $fallbackException) {
                \Log::error('Fallback index creation also failed: ' . $fallbackException->getMessage());
                throw $e; // Lanzar el error original
            }
        }
    }

    public function down()
    {
        // Eliminar índices únicos si existen
        $this->dropIndexIfExists('model_maintenances', 'model_maintenances_code_tipo_valor_trabajo_unique');
        $this->dropIndexIfExists('model_maintenances', 'model_maintenances_brand_kilometers_tipo_valor_trabajo_unique');
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
            }
        } catch (\Exception $e) {
            // Ignorar errores si el índice no existe
            \Log::info("Index {$indexName} might not exist: " . $e->getMessage());
        }
    }
};
