<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MitsuiInitialDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar tablas existentes
        $this->command->info('Limpiando datos existentes...');
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $tables = [
            'appointment_additional_service',
            'appointments',
            'additional_services',
            'maintenance_types',
            'pop_ups',
            'vehicles_express',
            'vehicles',
            'blockades',
            'campaign_years',
            'campaign_premises',
            'campaign_models',
            'campaign_images',
            'campaigns',
            'model_years',
            'models',
            'premises'
        ];

        foreach ($tables as $table) {
            DB::table($table)->truncate();
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 1. Crear locales (premises)
        $this->command->info('Creando locales...');
        $premises = [
            [
                'code' => 'local1',
                'name' => 'Mitsui La Molina',
                'address' => 'Av. La Molina 123, La Molina, Lima',
                'location' => '(01) 123-4567',
                'is_active' => true,
                'waze_url' => 'https://waze.com/ul/hsv8ub9c8k',
                'maps_url' => 'https://maps.google.com/?q=Mitsui+La+Molina',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'local2',
                'name' => 'Mitsui San Borja',
                'address' => 'Av. San Borja Norte 456, San Borja, Lima',
                'location' => '(01) 234-5678',
                'is_active' => true,
                'waze_url' => 'https://waze.com/ul/hsv8ub9c9l',
                'maps_url' => 'https://maps.google.com/?q=Mitsui+San+Borja',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'local3',
                'name' => 'Mitsui Surco',
                'address' => 'Av. Primavera 789, Surco, Lima',
                'location' => '(01) 345-6789',
                'is_active' => true,
                'waze_url' => null,
                'maps_url' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('premises')->insert($premises);

        // 2. Crear modelos de vehículos
        $this->command->info('Creando modelos de vehículos...');
        $models = [
            [
                'code' => 'OUTLANDER',
                'name' => 'Outlander',
                'brand' => 'Mitsubishi',
                'description' => 'SUV compacta',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'LANCER',
                'name' => 'Lancer',
                'brand' => 'Mitsubishi',
                'description' => 'Sedán deportivo',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'MONTERO',
                'name' => 'Montero',
                'brand' => 'Mitsubishi',
                'description' => 'SUV grande',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('models')->insert($models);

        // 3. Crear años por modelo
        $this->command->info('Creando años por modelo...');
        $modelYears = [];
        $modelIds = DB::table('models')->pluck('id', 'code');

        foreach ($modelIds as $code => $modelId) {
            for ($year = 2018; $year <= 2024; $year++) {
                $modelYears[] = [
                    'model_id' => $modelId,
                    'year' => $year,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('model_years')->insert($modelYears);

        // 4. Crear tipos de mantenimiento
        $this->command->info('Creando tipos de mantenimiento...');
        $maintenanceTypes = [
            [
                'name' => '5,000 Km',
                'code' => 'MANT_5K',
                'description' => 'Mantenimiento básico cada 5,000 kilómetros',
                'kilometers' => 5000,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '10,000 Km',
                'code' => 'MANT_10K',
                'description' => 'Mantenimiento intermedio cada 10,000 kilómetros',
                'kilometers' => 10000,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => '20,000 Km',
                'code' => 'MANT_20K',
                'description' => 'Mantenimiento mayor cada 20,000 kilómetros',
                'kilometers' => 20000,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('maintenance_types')->insert($maintenanceTypes);

        // 5. Crear servicios adicionales
        $this->command->info('Creando servicios adicionales...');
        $additionalServices = [
            [
                'name' => 'Lavado Completo',
                'code' => 'LAVADO_COMP',
                'description' => 'Lavado exterior e interior del vehículo',
                'price' => 25.00,
                'duration_minutes' => 45,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Encerado',
                'code' => 'ENCERADO',
                'description' => 'Aplicación de cera protectora',
                'price' => 35.00,
                'duration_minutes' => 60,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Revisión de Frenos',
                'code' => 'REV_FRENOS',
                'description' => 'Inspección completa del sistema de frenos',
                'price' => 50.00,
                'duration_minutes' => 90,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('additional_services')->insert($additionalServices);

        // 6. Crear vehículos de ejemplo
        $this->command->info('Creando vehículos de ejemplo...');
        $vehicles = [
            [
                'vehicle_id' => 'VEH001',
                'license_plate' => 'ABC-123',
                'model' => 'Outlander',
                'year' => '2022',
                'brand_name' => 'Mitsubishi',
                'brand_code' => 'MIT',
                'customer_name' => 'Juan Pérez',
                'customer_phone' => '999123456',
                'customer_email' => 'juan.perez@email.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'vehicle_id' => 'VEH002',
                'license_plate' => 'DEF-456',
                'model' => 'Lancer',
                'year' => '2021',
                'brand_name' => 'Mitsubishi',
                'brand_code' => 'MIT',
                'customer_name' => 'María García',
                'customer_phone' => '999654321',
                'customer_email' => 'maria.garcia@email.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('vehicles')->insert($vehicles);

        // 7. Crear servicios express
        $this->command->info('Creando servicios express...');
        $vehiclesExpress = [
            [
                'model' => 'Outlander',
                'brand' => 'Mitsubishi',
                'year' => 2022,
                'premises' => 'local1',
                'maintenance' => json_encode(['5,000 Km', '10,000 Km']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'model' => 'Lancer',
                'brand' => 'Mitsubishi',
                'year' => 2021,
                'premises' => 'local1',
                'maintenance' => json_encode(['5,000 Km', '20,000 Km']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'model' => 'Montero',
                'brand' => 'Mitsubishi',
                'year' => 2023,
                'premises' => 'local2',
                'maintenance' => json_encode(['10,000 Km', '20,000 Km']),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('vehicles_express')->insert($vehiclesExpress);

        // 8. Crear campañas de ejemplo
        $this->command->info('Creando campañas de ejemplo...');
        $campaigns = [
            [
                'title' => 'Campaña de Verano 2024',
                'description' => 'Descuentos especiales en mantenimiento durante el verano',
                'city' => 'Lima',
                'status' => 'active',
                'start_date' => Carbon::now()->subDays(30),
                'end_date' => Carbon::now()->addDays(60),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Promoción Outlander',
                'description' => 'Mantenimiento gratuito para Outlander 2022-2024',
                'city' => 'Lima',
                'status' => 'active',
                'start_date' => Carbon::now()->subDays(15),
                'end_date' => Carbon::now()->addDays(45),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('campaigns')->insert($campaigns);

        // 9. Relacionar campañas con modelos, locales y años
        $this->command->info('Creando relaciones de campañas...');
        $campaignIds = DB::table('campaigns')->pluck('id');
        $modelIds = DB::table('models')->pluck('id');

        // Relacionar campañas con modelos
        foreach ($campaignIds as $campaignId) {
            foreach ($modelIds as $modelId) {
                DB::table('campaign_models')->insert([
                    'campaign_id' => $campaignId,
                    'model_id' => $modelId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Relacionar campañas con locales
        foreach ($campaignIds as $campaignId) {
            foreach (['local1', 'local2'] as $premiseCode) {
                DB::table('campaign_premises')->insert([
                    'campaign_id' => $campaignId,
                    'premise_code' => $premiseCode,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Relacionar campañas con años
        foreach ($campaignIds as $campaignId) {
            foreach ([2022, 2023, 2024] as $year) {
                DB::table('campaign_years')->insert([
                    'campaign_id' => $campaignId,
                    'year' => $year,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 10. Crear pop-up de ejemplo
        $this->command->info('Creando pop-up de ejemplo...');
        DB::table('pop_ups')->insert([
            'name' => 'Promoción Especial',
            'image_path' => 'images/popup-promo.jpg',
            'sizes' => '400x300',
            'format' => 'jpg',
            'url_wp' => 'https://wa.me/51999123456',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->command->info('¡Datos iniciales creados exitosamente!');
        $this->command->info('');
        $this->command->info('Datos creados:');
        $this->command->info('- 3 locales (La Molina, San Borja, Surco)');
        $this->command->info('- 3 modelos de vehículos (Outlander, Lancer, Montero)');
        $this->command->info('- Años 2018-2024 para cada modelo');
        $this->command->info('- 3 tipos de mantenimiento');
        $this->command->info('- 3 servicios adicionales');
        $this->command->info('- 2 vehículos de ejemplo');
        $this->command->info('- 3 servicios express');
        $this->command->info('- 2 campañas activas');
        $this->command->info('- 1 pop-up promocional');
    }
}
