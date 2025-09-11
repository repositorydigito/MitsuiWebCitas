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
        Schema::create('kpi_targets', function (Blueprint $table) {
            $table->id();
            $table->string('kpi_id'); // ID del KPI (1 para citas generadas, 2 para citas efectivas)
            $table->string('brand')->nullable(); // Marca (puede ser null para "Todas")
            $table->string('local')->nullable(); // Local (puede ser null para "Todos")
            $table->integer('month')->nullable(); // Mes (1-12, puede ser null para todos los meses)
            $table->integer('year')->nullable(); // Año (puede ser null para todos los años)
            $table->integer('target_value'); // Valor meta
            $table->timestamps();
            
            // Índices para mejorar el rendimiento de las consultas
            $table->index(['kpi_id', 'brand', 'local', 'month', 'year']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_targets');
    }
};