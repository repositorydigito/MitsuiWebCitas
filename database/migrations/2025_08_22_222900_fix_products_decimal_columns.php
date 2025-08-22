<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Corregir los tipos DECIMAL de la tabla products para permitir valores más grandes
     * que los actuales DECIMAL(15,14) que solo permiten 1 dígito antes del punto decimal.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Cambiar de DECIMAL(15,14) a DECIMAL(10,2) para cantidades normales
            $table->decimal('base_quantity', 10, 2)->default(0)->change();
            $table->decimal('quantity', 10, 2)->default(0)->change();
            $table->decimal('alt_quantity', 10, 2)->default(0)->change();
            
            // Para work_time_value mantener más precisión pero permitir valores mayores
            $table->decimal('work_time_value', 12, 4)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Revertir a los valores originales
            $table->decimal('base_quantity', 15, 14)->default(0)->change();
            $table->decimal('quantity', 15, 14)->default(0)->change();
            $table->decimal('alt_quantity', 15, 14)->default(0)->change();
            $table->decimal('work_time_value', 15, 14)->default(0)->change();
        });
    }
};