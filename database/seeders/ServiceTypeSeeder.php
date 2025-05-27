<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use Illuminate\Database\Seeder;

class ServiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $serviceTypes = [
            // Mantenimientos periódicos
            [
                'code' => 'MANT-10K',
                'name' => 'Mantenimiento 10,000 Km',
                'description' => 'Mantenimiento periódico para vehículos con 10,000 kilómetros recorridos',
                'category' => 'maintenance',
                'duration_minutes' => 120,
                'is_express_available' => true,
                'express_duration_minutes' => 90,
                'is_active' => true,
            ],
            [
                'code' => 'MANT-20K',
                'name' => 'Mantenimiento 20,000 Km',
                'description' => 'Mantenimiento periódico para vehículos con 20,000 kilómetros recorridos',
                'category' => 'maintenance',
                'duration_minutes' => 180,
                'is_express_available' => true,
                'express_duration_minutes' => 120,
                'is_active' => true,
            ],
            [
                'code' => 'MANT-30K',
                'name' => 'Mantenimiento 30,000 Km',
                'description' => 'Mantenimiento periódico para vehículos con 30,000 kilómetros recorridos',
                'category' => 'maintenance',
                'duration_minutes' => 240,
                'is_express_available' => false,
                'express_duration_minutes' => null,
                'is_active' => true,
            ],

            // Reparaciones
            [
                'code' => 'REP-DIAG',
                'name' => 'Diagnóstico y Reparación',
                'description' => 'Servicio de diagnóstico y reparación de averías',
                'category' => 'repair',
                'duration_minutes' => 180,
                'is_express_available' => false,
                'express_duration_minutes' => null,
                'is_active' => true,
            ],

            // Campañas y otros
            [
                'code' => 'CAMP-REV',
                'name' => 'Llamado a Revisión',
                'description' => 'Revisión del correcto funcionamiento del vehículo por campaña del fabricante',
                'category' => 'campaign',
                'duration_minutes' => 120,
                'is_express_available' => false,
                'express_duration_minutes' => null,
                'is_active' => true,
            ],
            [
                'code' => 'SERV-LAV',
                'name' => 'Lavado',
                'description' => 'Servicio de lavado completo del vehículo',
                'category' => 'other',
                'duration_minutes' => 60,
                'is_express_available' => true,
                'express_duration_minutes' => 45,
                'is_active' => true,
            ],
        ];

        foreach ($serviceTypes as $serviceType) {
            ServiceType::updateOrCreate(
                ['code' => $serviceType['code']],
                $serviceType
            );
        }

        $this->command->info('Service types seeded successfully.');
    }
}
