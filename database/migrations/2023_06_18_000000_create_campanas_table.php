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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('titulo');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('all_day')->default(false);
            $table->enum('estado', ['Activo', 'Inactivo'])->default('Activo');
            $table->timestamps();
        });

        // Tabla pivote para la relación muchos a muchos entre campañas y modelos
        Schema::create('campaign_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->foreignId('model_id')->constrained('models')->onDelete('cascade');
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['campaign_id', 'model_id']);
        });

        // Tabla pivote para la relación muchos a muchos entre campañas y años
        Schema::create('campaign_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->string('year');
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['campaign_id', 'year']);
        });

        // Tabla pivote para la relación muchos a muchos entre campañas y locales
        Schema::create('campaign_premises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->string('premise_code');
            $table->timestamps();

            // Índice único para evitar duplicados
            $table->unique(['campaign_id', 'premise_code']);

            // Clave foránea para premise_code
            $table->foreign('premise_code')
                ->references('codigo')
                ->on('premises')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_premises');
        Schema::dropIfExists('campaign_years');
        Schema::dropIfExists('campaign_models');
        Schema::dropIfExists('campaigns');
    }
};
