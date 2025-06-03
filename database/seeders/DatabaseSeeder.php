<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario administrador por defecto
        User::factory()->create([
            'name' => 'Administrador Mitsui',
            'email' => 'admin@mitsui.com',
        ]);

        // Ejecutar seeder de datos iniciales de Mitsui
        $this->call([
            MitsuiInitialDataSeeder::class,
        ]);
    }
}
