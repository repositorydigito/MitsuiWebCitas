<?php

return [
    /*
    |--------------------------------------------------------------------------
    | C4C SOAP API Configuration
    |--------------------------------------------------------------------------
    */

    'username' => env('C4C_USERNAME', 'USCP'),
    'password' => env('C4C_PASSWORD', 'Inicio01'),
    'timeout' => env('C4C_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | C4C OData API Configuration (Availability Service)
    |--------------------------------------------------------------------------
    */

    'availability' => [
        'base_url' => env('C4C_AVAILABILITY_BASE_URL', 'https://my330968.crm.ondemand.com/sap/c4c/odata/cust/v1/cita_x_centro'),
        'timeout' => env('C4C_AVAILABILITY_TIMEOUT', 120),
        'cache_ttl' => env('C4C_AVAILABILITY_CACHE_TTL', 300), // 5 minutos
    ],

    /*
    |--------------------------------------------------------------------------
    | C4C SOAP Services Configuration
    |--------------------------------------------------------------------------
    */

    'services' => [
        'customer' => [
            'wsdl' => env('C4C_CUSTOMER_WSDL', 'https://my330968.crm.ondemand.com/sap/bc/srt/scs/sap/querycustomerin1?sap-vhost=my330968.crm.ondemand.com'),
            'method' => 'CustomerByElementsQuery_sync',
        ],
        'appointment' => [
            'create_wsdl' => env('C4C_APPOINTMENT_WSDL', 'https://my330968.crm.ondemand.com/sap/bc/srt/scs/sap/manageappointmentactivityin1?sap-vhost=my330968.crm.ondemand.com'),
            'create_method' => 'AppointmentActivityBundleMaintainRequest_sync_V1',
            'query_wsdl' => env('C4C_APPOINTMENT_QUERY_WSDL', 'https://my330968.crm.ondemand.com/sap/bc/srt/scs/sap/yy6saj0kgy_wscitas?sap-vhost=my330968.crm.ondemand.com'),
            'query_method' => 'ActivityBOVNCitasQueryByElementsSimpleByRequest_sync',
        ],
        // ✅ NUEVA CONFIGURACIÓN: Servicios de ofertas
        'offer' => [
            'create_wsdl' => env('C4C_OFFER_WSDL', 'https://my330968.crm.ondemand.com/sap/bc/srt/scs/sap/customerquoteprocessingmanagec'),
            'create_method' => env('C4C_OFFER_METHOD', 'CustomerQuoteBundleMaintainRequest_sync_V1'),
            'query_wsdl' => env('C4C_OFFER_QUERY_WSDL', 'https://my330968.crm.ondemand.com/sap/bc/srt/scs/sap/yy6soffersquery'),
            'query_method' => env('C4C_OFFER_QUERY_METHOD', 'CustomerQuoteByElementsQueryRequest_sync'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | C4C Status Codes
    |--------------------------------------------------------------------------
    */

    'status_codes' => [
        'appointment' => [
            'generated' => '1',
            'confirmed' => '2',
            'attended' => '3',
            'deferred' => '4',
            'cancelled' => '5',
            'deleted' => '6',
        ],
        'lifecycle' => [
            'open' => '1',
            'in_process' => '2',
            'completed' => '3',
            'cancelled' => '4',
        ],
        'action' => [
            'create' => '01',
            'update' => '04',
            'delete' => '06',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Center Code Mapping (Local to C4C)
    |--------------------------------------------------------------------------
    */

    'center_mapping' => [
        'M013' => 'M013', // Molina - usar código original
        'M023' => 'M023', // Canada - usar código original
        'M303' => 'M303', // Miraflores - usar código original
        'M313' => 'M313', // Arequipa - usar código original
        'M033' => 'M033', // Hino - usar código original
        'L013' => 'L013', // Lexus - mantener L013
    ],

    // ✅ NUEVA CONFIGURACIÓN: Mapeo de marcas
    'brand_mapping' => [
        'Z01' => [
            'code' => 'Z01',
            'name' => 'TOYOTA',
            'display_name' => 'Toyota'
        ],
        'Z02' => [
            'code' => 'Z02',
            'name' => 'LEXUS',
            'display_name' => 'Lexus'
        ],
        'Z03' => [
            'code' => 'Z03',
            'name' => 'HINO',
            'display_name' => 'Hino'
        ],
    ],

    // ✅ NUEVA CONFIGURACIÓN: Valores por defecto organizacionales
    'defaults' => [
        'employee_id' => env('C4C_DEFAULT_EMPLOYEE_ID', '8000000010'),
        'customer_id' => env('C4C_DEFAULT_CUSTOMER_ID', '1270002726'),
        'processing_type_code' => env('C4C_DEFAULT_PROCESSING_TYPE', 'Z300'),
        'document_language' => env('C4C_DEFAULT_LANGUAGE', 'ES'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'enabled' => env('C4C_SYNC_ENABLED', true),
        'auto_sync' => env('C4C_AUTO_SYNC', true),
        'queue' => env('C4C_SYNC_QUEUE', 'c4c-sync'),
        'retry_attempts' => env('C4C_SYNC_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('C4C_SYNC_RETRY_DELAY', 60), // segundos
    ],
];

