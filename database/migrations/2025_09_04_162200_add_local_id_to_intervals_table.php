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
        // Primero, verificar si la tabla intervals existe
        if (!Schema::hasTable('intervals')) {
            return;
        }

        // Hacer que los campos existentes sean anulables temporalmente
        if (Schema::hasColumn('intervals', 'min_reservation_time')) {
            DB::statement('ALTER TABLE intervals MODIFY min_reservation_time INTEGER NULL');
        }
        if (Schema::hasColumn('intervals', 'min_time_unit')) {
            DB::statement('ALTER TABLE intervals MODIFY min_time_unit VARCHAR(10) NULL');
        }
        if (Schema::hasColumn('intervals', 'max_reservation_time')) {
            DB::statement('ALTER TABLE intervals MODIFY max_reservation_time INTEGER NULL');
        }
        if (Schema::hasColumn('intervals', 'max_time_unit')) {
            DB::statement('ALTER TABLE intervals MODIFY max_time_unit VARCHAR(10) NULL');
        }
        
        // Agregar la columna local_id si no existe
        if (!Schema::hasColumn('intervals', 'local_id')) {
            Schema::table('intervals', function (Blueprint $table) {
                $table->foreignId('local_id')
                    ->after('id')
                    ->nullable()
                    ->constrained('premises', 'id')
                    ->onDelete('cascade');
                
                // Hacer que el local_id sea único para evitar duplicados
                $table->unique('local_id');
            });
        }
        
        // Actualizar el registro existente para que esté asociado con el primer local activo
        if (Schema::hasTable('premises')) {
            $firstPremise = DB::table('premises')->where('is_active', true)->first();
            
            if ($firstPremise) {
                DB::table('intervals')->update(['local_id' => $firstPremise->id]);
                
                // Hacer que el local_id no sea nulo después de la migración
                DB::statement('ALTER TABLE intervals MODIFY local_id BIGINT UNSIGNED NOT NULL');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('intervals')) {
            // Eliminar la restricción única y la clave foránea si existen
            if (Schema::hasColumn('intervals', 'local_id')) {
                Schema::table('intervals', function (Blueprint $table) {
                    // Eliminar la restricción única
                    $sm = Schema::getConnection()->getDoctrineSchemaManager();
                    $indexesFound = $sm->listTableIndexes('intervals');
                    
                    foreach ($indexesFound as $index) {
                        if (in_array('local_id', $index->getColumns())) {
                            $table->dropUnique($index->getName());
                        }
                    }
                    
                    // Eliminar la clave foránea
                    $table->dropForeign(['local_id']);
                    
                    // Eliminar la columna local_id
                    $table->dropColumn('local_id');
                });
            }
            
            // Restaurar los valores por defecto
            if (Schema::hasColumn('intervals', 'min_reservation_time')) {
                DB::statement('ALTER TABLE intervals MODIFY min_reservation_time INTEGER NOT NULL');
            }
            if (Schema::hasColumn('intervals', 'min_time_unit')) {
                DB::statement('ALTER TABLE intervals MODIFY min_time_unit VARCHAR(10) NOT NULL DEFAULT "days"');
            }
            if (Schema::hasColumn('intervals', 'max_reservation_time')) {
                DB::statement('ALTER TABLE intervals MODIFY max_reservation_time INTEGER NOT NULL');
            }
            if (Schema::hasColumn('intervals', 'max_time_unit')) {
                DB::statement('ALTER TABLE intervals MODIFY max_time_unit VARCHAR(10) NOT NULL DEFAULT "days"');
            }
        }
    }
};
