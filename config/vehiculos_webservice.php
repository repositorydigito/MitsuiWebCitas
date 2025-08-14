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

    // Habilitar/deshabilitar WebService completamente
    'enabled' => env('VEHICULOS_WEBSERVICE_ENABLED', true),

    // Preferir WSDL local sobre remoto
    'prefer_local_wsdl' => env('VEHICULOS_PREFER_LOCAL_WSDL', true),

    // Timeout en segundos para las peticiones SOAP
    'timeout' => env('VEHICULOS_WEBSERVICE_TIMEOUT', 30),

    // Número de intentos fallidos antes de activar modo fallback
    'retry_attempts' => env('VEHICULOS_WEBSERVICE_RETRY_ATTEMPTS', 2),

    // Intervalo de verificación de disponibilidad en segundos (5 minutos por defecto)
    'health_check_interval' => env('VEHICULOS_WEBSERVICE_HEALTH_CHECK_INTERVAL', 300),

    // Datos de prueba para cada marca
    'mock_data' => [
        'Z01' => [ // TOYOTA
            [
                'vhclie' => 'TOYOWEB123456',
                'numpla' => 'B3Y-467',
                'aniomod' => '2023',
                'modver' => 'COROLLA XLE CVT',
                'marca_codigo' => 'Z01',
            ],
            [
                'vhclie' => 'TOYOWEB789012',
                'numpla' => 'WEB-001',
                'aniomod' => '2022',
                'modver' => 'RAV4 LIMITED',
                'marca_codigo' => 'Z01',
            ],
            [
                'vhclie' => 'TOYOWEB345678',
                'numpla' => 'WEB-002',
                'aniomod' => '2024',
                'modver' => 'CAMRY XSE',
                'marca_codigo' => 'Z01',
            ],
            [
                'vhclie' => 'TOYOWEB456789',
                'numpla' => 'WEB-003',
                'aniomod' => '2023',
                'modver' => 'YARIS XLI',
                'marca_codigo' => 'Z01',
            ],
        ],
        'Z02' => [ // LEXUS
            [
                'vhclie' => 'LEXUSWEB12345',
                'numpla' => 'B3Y-467',
                'aniomod' => '2023',
                'modver' => 'RX 350 F SPORT',
                'marca_codigo' => 'Z02',
            ],
        ],
        'Z03' => [ // HINO
            [
                'vhclie' => 'HINOWEB12345',
                'numpla' => 'B3Y-467',
                'aniomod' => '2022',
                'modver' => 'HINO 300 SERIES',
                'marca_codigo' => 'Z03',
            ],
        ],
    ],
];
