<?php

namespace Database\Seeders;

use App\Models\ServiceCenter;
use Illuminate\Database\Seeder;

class ServiceCenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $serviceCenters = [
            [
                'code' => 'M001',
                'name' => 'Mitsui La Molina',
                'address' => 'Av. Javier Prado Este 6042, La Molina 15024',
                'city' => 'Lima',
                'phone' => '(01) 625-3000',
                'email' => 'servicios@mitsui.com.pe',
                'maps_url' => 'https://maps.app.goo.gl/example1',
                'waze_url' => 'https://waze.com/ul/example1',
                'is_active' => true,
            ],
            [
                'code' => 'M002',
                'name' => 'Mitsui Miraflores',
                'address' => 'Av. Comandante Espinar 428, Miraflores 15074',
                'city' => 'Lima',
                'phone' => '(01) 625-3001',
                'email' => 'servicios.miraflores@mitsui.com.pe',
                'maps_url' => 'https://maps.app.goo.gl/example2',
                'waze_url' => 'https://waze.com/ul/example2',
                'is_active' => true,
            ],
            [
                'code' => 'M003',
                'name' => 'Mitsui Canadá',
                'address' => 'Av. Canadá 120, La Victoria 15034',
                'city' => 'Lima',
                'phone' => '(01) 625-3002',
                'email' => 'servicios.canada@mitsui.com.pe',
                'maps_url' => 'https://maps.app.goo.gl/example3',
                'waze_url' => 'https://waze.com/ul/example3',
                'is_active' => true,
            ],
            [
                'code' => 'M004',
                'name' => 'Mitsui Arequipa',
                'address' => 'Av. Villa Hermosa 1151 Cerro Colorado - Arequipa',
                'city' => 'Arequipa',
                'phone' => '(054) 625-3003',
                'email' => 'servicios.arequipa@mitsui.com.pe',
                'maps_url' => 'https://maps.app.goo.gl/example4',
                'waze_url' => 'https://waze.com/ul/example4',
                'is_active' => true,
            ],
        ];

        foreach ($serviceCenters as $serviceCenter) {
            ServiceCenter::updateOrCreate(
                ['code' => $serviceCenter['code']],
                $serviceCenter
            );
        }

        $this->command->info('Service centers seeded successfully.');
    }
}
