<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar si la tabla y la columna existen antes de intentar eliminarla
        if (Schema::hasTable('model_maintenances') && Schema::hasColumn('model_maintenances', 'model')) {
            Schema::table('model_maintenances', function (Blueprint $table) {
                $table->dropColumn('model');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Agregar de vuelta la columna model si es necesario
        if (Schema::hasTable('model_maintenances') && !Schema::hasColumn('model_maintenances', 'model')) {
            Schema::table('model_maintenances', function (Blueprint $table) {
                $table->string('model')->nullable()->after('brand');
            });
        }
    }
};
