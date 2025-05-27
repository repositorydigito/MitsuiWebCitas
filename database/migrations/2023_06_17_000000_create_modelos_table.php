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
        Schema::create('models', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('nombre');
            $table->string('marca')->default('TOYOTA');
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Insertar datos iniciales
        DB::table('models')->insert([
            [
                'codigo' => 'COROLLA_CROSS',
                'nombre' => 'COROLLA CROSS',
                'marca' => 'TOYOTA',
                'descripcion' => 'SUV compacto',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'HILUX',
                'nombre' => 'HILUX',
                'marca' => 'TOYOTA',
                'descripcion' => 'Pickup',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'LAND_CRUISER',
                'nombre' => 'LAND CRUISER',
                'marca' => 'TOYOTA',
                'descripcion' => 'SUV de lujo',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'RAV4',
                'nombre' => 'RAV4',
                'marca' => 'TOYOTA',
                'descripcion' => 'SUV compacto',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'YARIS',
                'nombre' => 'YARIS',
                'marca' => 'TOYOTA',
                'descripcion' => 'Hatchback',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'codigo' => 'YARIS_CROSS',
                'nombre' => 'YARIS CROSS',
                'marca' => 'TOYOTA',
                'descripcion' => 'SUV subcompacto',
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
        Schema::dropIfExists('models');
    }
};
