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
        Schema::create('additional_services', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Código del servicio adicional');
            $table->string('name')->comment('Nombre del servicio adicional');
            $table->text('description')->nullable()->comment('Descripción del servicio adicional');
            $table->decimal('price', 10, 2)->nullable()->comment('Precio del servicio adicional');
            $table->integer('duration_minutes')->default(30)->comment('Duración estimada en minutos');
            $table->string('image_url')->nullable()->comment('URL de la imagen del servicio');
            $table->boolean('is_active')->default(true)->comment('Indica si el servicio adicional está activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('additional_services');
    }
};
