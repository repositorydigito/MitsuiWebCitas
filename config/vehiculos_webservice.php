<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuración del WebService SOAP para Vehículos
    |--------------------------------------------------------------------------
    |
    | Configuración relacionada con la conexión al WebService SOAP de vehículos,
    | incluyendo opciones de fallback y timeouts.
    |
    */

    // Habilitar/deshabilitar WebService
    'enabled' => env('VEHICULOS_WEBSERVICE_ENABLED', true),

    // Timeout en segundos para las peticiones SOAP
    'timeout' => env('VEHICULOS_WEBSERVICE_TIMEOUT', 5),

    // Número de intentos fallidos antes de activar modo fallback
    'retry_attempts' => env('VEHICULOS_WEBSERVICE_RETRY_ATTEMPTS', 2),

    // Intervalo de verificación de disponibilidad en segundos (5 minutos por defecto)
    'health_check_interval' => env('VEHICULOS_WEBSERVICE_HEALTH_CHECK_INTERVAL', 300),

    // Datos de prueba para cada marca
    'mock_data' => [
        'Z01' => [ // TOYOTA
            [
                'vhclie' => 'TOYOAA123456',
                'numpla' => 'ABC-123',
                'aniomod' => '2023',
                'modver' => 'COROLLA XLE CVT',
                'marca_codigo' => 'Z01',
            ],
            [
                'vhclie' => 'TOYOBB789012',
                'numpla' => 'DEF-456',
                'aniomod' => '2022',
                'modver' => 'RAV4 LIMITED',
                'marca_codigo' => 'Z01',
            ],
            [
                'vhclie' => 'TOYOCC345678',
                'numpla' => 'GHI-789',
                'aniomod' => '2024',
                'modver' => 'CAMRY XSE',
                'marca_codigo' => 'Z01',
            ],
        ],
        'Z02' => [ // LEXUS
            [
                'vhclie' => 'LEXUSAA12345',
                'numpla' => 'JKL-012',
                'aniomod' => '2023',
                'modver' => 'RX 350 F SPORT',
                'marca_codigo' => 'Z02',
            ],
            [
                'vhclie' => 'LEXUSBB67890',
                'numpla' => 'MNO-345',
                'aniomod' => '2024',
                'modver' => 'NX 300h LUXURY',
                'marca_codigo' => 'Z02',
            ],
        ],
        'Z03' => [ // HINO
            [
                'vhclie' => 'HINOAA12345',
                'numpla' => 'PQR-678',
                'aniomod' => '2022',
                'modver' => 'HINO 300 SERIES',
                'marca_codigo' => 'Z03',
            ],
            [
                'vhclie' => 'HINOBB67890',
                'numpla' => 'STU-901',
                'aniomod' => '2021',
                'modver' => 'HINO 500 SERIES',
                'marca_codigo' => 'Z03',
            ],
        ],
    ],
];
