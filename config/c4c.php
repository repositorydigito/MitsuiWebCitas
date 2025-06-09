<?php

return [
    /*
    |--------------------------------------------------------------------------
    | C4C SOAP API Configuration
    |--------------------------------------------------------------------------
    */

    'auth' => [
        'username' => env('C4C_USERNAME', 'USCP'),
        'password' => env('C4C_PASSWORD', 'Inicio01'),
    ],

    'timeout' => env('C4C_TIMEOUT', 120),

    'services' => [
        'customer' => [
            'wsdl' => env('C4C_CUSTOMER_WSDL', 'https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/querycustomerin1?sap-vhost=my317791.crm.ondemand.com'),
            'method' => 'CustomerByElementsQuery_sync',
        ],
        'appointment' => [
            'wsdl' => env('C4C_APPOINTMENT_WSDL', 'https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/manageappointmentactivityin1?sap-vhost=my317791.crm.ondemand.com'),
            'method' => 'AppointmentActivityBundleMaintainRequest_sync_V1',
        ],
        'appointment_query' => [
            'wsdl' => env('C4C_APPOINTMENT_QUERY_WSDL', 'https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/yy6saj0kgy_wscitas?sap-vhost=my317791.crm.ondemand.com'),
            'method' => 'ActivityBOVNCitasQueryByElementsSimpleByRequest_sync',
        ],
    ],

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
];
