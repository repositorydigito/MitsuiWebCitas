<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class MockVehiculoService
{
    /**
     * Obtiene datos de vehículos simulados para un cliente y marcas específicas
     */
    public function getVehiculosCliente(string $documentoCliente, array $marcas): Collection
    {
        Log::info("[MockVehiculoService] Generando datos de vehículos simulados para cliente {$documentoCliente}");

        $result = collect();

        foreach ($marcas as $marca) {
            // Obtener datos mock para esta marca desde la configuración
            $vehiculosMarca = collect(config("vehiculos_webservice.mock_data.{$marca}", []));

            // Si no hay datos para esta marca, continuar con la siguiente
            if ($vehiculosMarca->isEmpty()) {
                Log::warning("[MockVehiculoService] No hay datos de prueba configurados para la marca {$marca}");

                continue;
            }

            Log::info("[MockVehiculoService] Añadiendo {$vehiculosMarca->count()} vehículos simulados para marca {$marca}");

            // Añadir todos los vehículos de esta marca a la colección de resultados
            $result = $result->merge($vehiculosMarca);
        }

        // Ajustes específicos según documentoCliente (para datos más personalizados)
        // Ejemplo: Variar algún dato según el documento del cliente para simular diferencias
        $result = $result->map(function ($vehiculo) use ($documentoCliente) {
            // Añadir un sufijo al vhclie basado en los últimos 4 dígitos del documento
            $suffix = substr($documentoCliente, -4);
            $vehiculo['vhclie'] = str_replace('AA', $suffix, $vehiculo['vhclie']);

            // **NUEVO: Agregar campo fuente_datos para compatibilidad con persistencia**
            $vehiculo['fuente_datos'] = 'Mock_Data';

            return $vehiculo;
        });

        Log::info("[MockVehiculoService] Total de vehículos simulados generados: {$result->count()}");

        return $result;
    }

    /**
     * Generar datos de ejemplo aleatorios (para uso específico en pruebas o desarrollo)
     *
     * @param  int  $count  Número de vehículos a generar
     * @param  string  $marca  Código de marca por defecto
     */
    public function getDatosEjemplo(int $count = 5, string $marca = 'Z01'): Collection
    {
        $modelos = [
            'Z01' => ['COROLLA', 'CAMRY', 'RAV4', 'HILUX', 'YARIS'],
            'Z02' => ['RX', 'NX', 'UX', 'ES', 'LS'],
            'Z03' => ['300', '500', '700', 'DUTRO', 'RANGER'],
        ];

        $versiones = [
            'Z01' => ['XLE', 'XLI', 'LIMITED', 'SPORT', 'BASE'],
            'Z02' => ['F SPORT', 'LUXURY', 'EXECUTIVE', 'PREMIUM', 'HYBRID'],
            'Z03' => ['SERIES', 'CITY', 'TRUCK', 'CARGO', 'EXECUTIVE'],
        ];

        $result = collect();

        for ($i = 0; $i < $count; $i++) {
            $modelo = $modelos[$marca][array_rand($modelos[$marca])];
            $version = $versiones[$marca][array_rand($versiones[$marca])];

            $anio = (string) rand(2018, 2024);
            $placa = chr(65 + rand(0, 25)).chr(65 + rand(0, 25)).chr(65 + rand(0, 25)).'-'.rand(100, 999);

            $result->push([
                'vhclie' => $marca.substr(md5(rand()), 0, 8),
                'numpla' => $placa,
                'aniomod' => $anio,
                'modver' => "{$modelo} {$version}",
                'marca_codigo' => $marca,
                'fuente_datos' => 'Mock_Data_Ejemplo',
            ]);
        }

        return $result;
    }
}
