<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PopUp;

class PopUpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $popups = [
            [
                'nombre' => 'Compra de paquete de mantenimientos prepagados',
                'imagen_path' => 'images/toyota-value.jpg',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51987654321&text=Hola,%20me%20interesa%20el%20paquete%20de%20mantenimientos%20prepagados',
                'activo' => true,
            ],
            [
                'nombre' => 'Compra/Venta de auto seminuevo',
                'imagen_path' => 'images/mitsui-seminuevos.jpg',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51987654321&text=Hola,%20me%20interesa%20la%20compra/venta%20de%20auto%20seminuevo',
                'activo' => true,
            ],
            [
                'nombre' => 'Renovación de auto',
                'imagen_path' => 'images/renovacion-auto.jpg',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51987654321&text=Hola,%20me%20interesa%20renovar%20mi%20auto',
                'activo' => true,
            ],
            [
                'nombre' => 'Venta de SOAT',
                'imagen_path' => 'images/soat.jpg',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51987654321&text=Hola,%20me%20interesa%20adquirir%20un%20SOAT',
                'activo' => true,
            ],
            [
                'nombre' => 'Alquiler de auto (Kinto share)',
                'imagen_path' => 'images/kinto-share.jpg',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51987654321&text=Hola,%20me%20interesa%20el%20alquiler%20de%20auto%20Kinto%20Share',
                'activo' => true,
            ],
            [
                'nombre' => 'Seguro Toyota',
                'imagen_path' => 'images/seguro-toyota.jpg',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51987654321&text=Hola,%20me%20interesa%20el%20seguro%20Toyota',
                'activo' => true,
            ],
        ];

        foreach ($popups as $popup) {
            PopUp::create($popup);
        }
    }
}
