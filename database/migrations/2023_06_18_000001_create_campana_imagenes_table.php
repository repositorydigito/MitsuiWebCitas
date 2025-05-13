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
        Schema::create('campana_imagenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->constrained('campanas')->onDelete('cascade');
            $table->string('ruta');
            $table->string('nombre_original');
            $table->string('mime_type');
            $table->unsignedBigInteger('tamano');
            $table->timestamps();

            // Índice único para asegurar que cada campaña tenga solo una imagen
            $table->unique('campana_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campana_imagenes');
    }
};
