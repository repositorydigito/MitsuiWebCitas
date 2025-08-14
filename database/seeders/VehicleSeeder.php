<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creando vehículos desde datos mock...');

        // Obtener los datos mock de la configuración
        $mockData = config('vehiculos_webservice.mock_data', []);
        
        if (empty($mockData)) {
            $this->command->warn('No hay datos mock configurados en vehiculos_webservice.php');
            return;
        }

        // Buscar un usuario por defecto para asignar los vehículos
        $defaultUser = User::first();
        if (!$defaultUser) {
            $this->command->error('No hay usuarios en la base de datos. Ejecuta primero el seeder de usuarios.');
            return;
        }

        $totalCreated = 0;

        foreach ($mockData as $brandCode => $vehicles) {
            $this->command->info("Procesando marca: {$brandCode}");
            
            foreach ($vehicles as $vehicleData) {
                try {
                    // Verificar si el vehículo ya existe
                    $existingVehicle = Vehicle::where('license_plate', $vehicleData['numpla'])
                        ->orWhere('vehicle_id', $vehicleData['vhclie'])
                        ->first();

                    if ($existingVehicle) {
                        $this->command->info("Vehículo ya existe: {$vehicleData['numpla']}");
                        continue;
                    }

                    // Determinar el nombre de la marca
                    $brandName = match ($brandCode) {
                        'Z01' => 'TOYOTA',
                        'Z02' => 'LEXUS',
                        'Z03' => 'HINO',
                        default => 'TOYOTA',
                    };

                    // Crear el vehículo
                    $vehicle = Vehicle::create([
                        'vehicle_id' => $vehicleData['vhclie'],
                        'license_plate' => $vehicleData['numpla'],
                        'model' => $vehicleData['modver'],
                        'year' => $vehicleData['aniomod'],
                        'brand_code' => $brandCode,
                        'brand_name' => $brandName,
                        'user_id' => $defaultUser->id,
                        'status' => 'active',
                        // Campos adicionales con valores por defecto
                        'color' => 'No especificado',
                        'mileage' => rand(10000, 100000),
                        'last_service_date' => now()->subMonths(rand(1, 6)),
                        'last_service_mileage' => rand(5000, 50000),
                        'next_service_date' => now()->addMonths(rand(3, 12)),
                        'next_service_mileage' => rand(60000, 120000),
                        'has_prepaid_maintenance' => rand(0, 1) == 1,
                        'prepaid_maintenance_expiry' => rand(0, 1) == 1 ? now()->addYears(rand(1, 3)) : null,
                    ]);

                    $totalCreated++;
                    $this->command->info("✅ Vehículo creado: {$vehicle->license_plate} - {$vehicle->model}");

                } catch (\Exception $e) {
                    $this->command->error("❌ Error creando vehículo {$vehicleData['numpla']}: " . $e->getMessage());
                    Log::error('Error en VehicleSeeder', [
                        'vehicle_data' => $vehicleData,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        $this->command->info("✅ Proceso completado. Total de vehículos creados: {$totalCreated}");
    }
}
