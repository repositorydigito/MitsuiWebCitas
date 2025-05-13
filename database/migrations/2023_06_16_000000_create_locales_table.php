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
        Schema::create('locales', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->time('horario_apertura')->default('08:00');
            $table->time('horario_cierre')->default('18:00');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar datos iniciales
        DB::table('locales')->insert([
            [
                'codigo' => 'local1',
                'nombre' => 'La Molina',
                'direccion' => 'Av. La Molina 123',
                'telefono' => '(01) 123-4567',
                'horario_apertura' => '08:00',
                'horario_cierre' => '18:00',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'local2',
                'nombre' => 'San Miguel',
                'direccion' => 'Av. La Marina 456',
                'telefono' => '(01) 987-6543',
                'horario_apertura' => '08:00',
                'horario_cierre' => '18:00',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('locales');
    }
};
