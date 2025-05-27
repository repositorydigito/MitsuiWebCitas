<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener los datos mock de la configuración
        $mockData = config('vehiculos_webservice.mock_data');

        if (empty($mockData)) {
            $this->command->error('No se encontraron datos mock en la configuración.');

            return;
        }

        $totalVehicles = 0;

        // Procesar cada marca
        foreach ($mockData as $brandCode => $vehicles) {
            // Determinar el nombre de la marca
            $brandName = match ($brandCode) {
                'Z01' => 'TOYOTA',
                'Z02' => 'LEXUS',
                'Z03' => 'HINO',
                default => 'DESCONOCIDO',
            };

            $this->command->info("Procesando vehículos de la marca: {$brandName}");

            // Procesar cada vehículo de la marca
            foreach ($vehicles as $vehicleData) {
                // Verificar si el vehículo ya existe (por placa)
                $existingVehicle = Vehicle::where('license_plate', $vehicleData['numpla'])->first();

                if ($existingVehicle) {
                    $this->command->line("  - Vehículo con placa {$vehicleData['numpla']} ya existe, actualizando...");

                    // Actualizar el vehículo existente
                    $existingVehicle->update([
                        'vehicle_id' => $vehicleData['vhclie'],
                        'model' => $vehicleData['modver'],
                        'year' => $vehicleData['aniomod'],
                        'brand_code' => $vehicleData['marca_codigo'],
                        'brand_name' => $brandName,
                        // Agregar campos adicionales con valores aleatorios
                        'color' => $this->getRandomColor(),
                        'mileage' => rand(1000, 50000),
                        'last_service_date' => now()->subMonths(rand(1, 6)),
                        'last_service_mileage' => rand(1000, 30000),
                        'next_service_date' => now()->addMonths(rand(1, 6)),
                        'next_service_mileage' => rand(5000, 60000),
                        'has_prepaid_maintenance' => (bool) rand(0, 1),
                        'prepaid_maintenance_expiry' => now()->addYears(rand(1, 3)),
                        'status' => 'active',
                    ]);
                } else {
                    $this->command->line("  + Creando vehículo con placa {$vehicleData['numpla']}...");

                    // Crear un nuevo vehículo
                    Vehicle::create([
                        'vehicle_id' => $vehicleData['vhclie'],
                        'license_plate' => $vehicleData['numpla'],
                        'model' => $vehicleData['modver'],
                        'year' => $vehicleData['aniomod'],
                        'brand_code' => $vehicleData['marca_codigo'],
                        'brand_name' => $brandName,
                        // Agregar campos adicionales con valores aleatorios
                        'color' => $this->getRandomColor(),
                        'mileage' => rand(1000, 50000),
                        'last_service_date' => now()->subMonths(rand(1, 6)),
                        'last_service_mileage' => rand(1000, 30000),
                        'next_service_date' => now()->addMonths(rand(1, 6)),
                        'next_service_mileage' => rand(5000, 60000),
                        'has_prepaid_maintenance' => (bool) rand(0, 1),
                        'prepaid_maintenance_expiry' => now()->addYears(rand(1, 3)),
                        'status' => 'active',
                    ]);

                    $totalVehicles++;
                }
            }
        }

        $this->command->info("Se han creado/actualizado {$totalVehicles} vehículos de ejemplo.");
    }

    /**
     * Obtener un color aleatorio.
     */
    private function getRandomColor(): string
    {
        $colors = [
            'Blanco', 'Negro', 'Gris', 'Plata', 'Rojo', 'Azul', 'Verde',
            'Amarillo', 'Naranja', 'Marrón', 'Beige', 'Dorado', 'Bronce',
        ];

        return $colors[array_rand($colors)];
    }
}
