<?php

namespace Database\Seeders;

use App\Models\AdditionalService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdditionalServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $additionalServices = [
            [
                'code' => 'REST-FAROS',
                'name' => 'Restauración de faros',
                'description' => 'Servicio de restauración y pulido de faros para mejorar la visibilidad',
                'price' => 150.00,
                'duration_minutes' => 60,
                'image_url' => '/images/services/restauracion-faros.jpg',
                'is_active' => true,
            ],
            [
                'code' => 'REST-RINES',
                'name' => 'Restauración de rines',
                'description' => 'Servicio de restauración y pulido de rines para mejorar la apariencia',
                'price' => 200.00,
                'duration_minutes' => 90,
                'image_url' => '/images/services/restauracion-rines.jpg',
                'is_active' => true,
            ],
            [
                'code' => 'REST-FOCOS',
                'name' => 'Restauración de focos',
                'description' => 'Servicio de restauración y limpieza de focos para mejorar la iluminación',
                'price' => 120.00,
                'duration_minutes' => 45,
                'image_url' => '/images/services/restauracion-focos.jpg',
                'is_active' => true,
            ],
            [
                'code' => 'ALIN-RUEDAS',
                'name' => 'Alineación de ruedas',
                'description' => 'Servicio de alineación de ruedas para mejorar el manejo y reducir el desgaste de neumáticos',
                'price' => 180.00,
                'duration_minutes' => 60,
                'image_url' => '/images/services/alineacion-ruedas.jpg',
                'is_active' => true,
            ],
            [
                'code' => 'BALAN-RUEDAS',
                'name' => 'Balanceo de ruedas',
                'description' => 'Servicio de balanceo de ruedas para reducir vibraciones y mejorar el confort',
                'price' => 150.00,
                'duration_minutes' => 45,
                'image_url' => '/images/services/balanceo-ruedas.jpg',
                'is_active' => true,
            ],
            [
                'code' => 'LIMP-INYECT',
                'name' => 'Limpieza de inyectores',
                'description' => 'Servicio de limpieza de inyectores para mejorar el rendimiento del motor',
                'price' => 220.00,
                'duration_minutes' => 90,
                'image_url' => '/images/services/limpieza-inyectores.jpg',
                'is_active' => true,
            ],
        ];

        foreach ($additionalServices as $additionalService) {
            AdditionalService::updateOrCreate(
                ['code' => $additionalService['code']],
                $additionalService
            );
        }

        $this->command->info('Additional services seeded successfully.');
    }
}
