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
        Schema::create('service_types', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Código del tipo de servicio');
            $table->string('name')->comment('Nombre del tipo de servicio');
            $table->text('description')->nullable()->comment('Descripción del tipo de servicio');
            $table->string('category')->nullable()->comment('Categoría del servicio (mantenimiento, reparación, etc.)');
            $table->integer('duration_minutes')->default(60)->comment('Duración estimada en minutos');
            $table->boolean('is_express_available')->default(false)->comment('Indica si está disponible en modalidad express');
            $table->integer('express_duration_minutes')->nullable()->comment('Duración en modalidad express');
            $table->boolean('is_active')->default(true)->comment('Indica si el tipo de servicio está activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_types');
    }
};
