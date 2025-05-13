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
        Schema::create('modelo_anos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modelo_id')->constrained('modelos')->onDelete('cascade');
            $table->string('ano');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            // Índice único para modelo_id y ano
            $table->unique(['modelo_id', 'ano']);
        });

        // Insertar datos iniciales
        $modelos = DB::table('modelos')->get();
        $anos = ['2018', '2019', '2020', '2021', '2022', '2023', '2024'];

        foreach ($modelos as $modelo) {
            foreach ($anos as $ano) {
                DB::table('modelo_anos')->insert([
                    'modelo_id' => $modelo->id,
                    'ano' => $ano,
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
        Schema::dropIfExists('modelo_anos');
    }
};
