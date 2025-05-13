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
        Schema::create('campanas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('titulo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->boolean('todo_dia')->default(false);
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamps();
        });

        // Tabla pivote para la relación muchos a muchos entre campañas y modelos
        Schema::create('campana_modelos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->constrained('campanas')->onDelete('cascade');
            $table->foreignId('modelo_id')->constrained('modelos')->onDelete('cascade');
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['campana_id', 'modelo_id']);
        });

        // Tabla pivote para la relación muchos a muchos entre campañas y años
        Schema::create('campana_anos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->constrained('campanas')->onDelete('cascade');
            $table->string('ano');
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['campana_id', 'ano']);
        });

        // Tabla pivote para la relación muchos a muchos entre campañas y locales
        Schema::create('campana_locales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campana_id')->constrained('campanas')->onDelete('cascade');
            $table->string('local_codigo');
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['campana_id', 'local_codigo']);

            // Clave foránea para local_codigo
            $table->foreign('local_codigo')
                ->references('codigo')
                ->on('locales')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campana_locales');
        Schema::dropIfExists('campana_anos');
        Schema::dropIfExists('campana_modelos');
        Schema::dropIfExists('campanas');
    }
};
