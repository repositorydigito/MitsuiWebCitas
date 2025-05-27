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
        Schema::create('model_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('models')->onDelete('cascade');
            $table->string('year');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Índice único para model_id y year
            $table->unique(['model_id', 'year']);
        });

        // Insertar datos iniciales
        $modelos = DB::table('models')->get();
        $anos = ['2018', '2019', '2020', '2021', '2022', '2023', '2024'];

        foreach ($modelos as $modelo) {
            foreach ($anos as $ano) {
                DB::table('model_years')->insert([
                    'model_id' => $modelo->id,
                    'year' => $ano,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_years');
    }
};
