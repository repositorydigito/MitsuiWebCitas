<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Servicios SOAP
    |--------------------------------------------------------------------------
    |
    | Configuraciones para los servicios SOAP de vehículos y citas.
    | Incluye URLs de los WSDL y credenciales de autenticación.
    |
    */

    'vehiculos' => [
        'wsdl_url' => env('VEHICULOS_WSDL_URL', ''),
        'usuario' => env('VEHICULOS_USUARIO'),
        'password' => env('VEHICULOS_PASSWORD'),
    ],

    'citas' => [
        'wsdl_url' => env('CITAS_WSDL_URL', 'http://mitdesqafo.mitsuiautomotriz.com:8002/sap/bc/srt/wsdl/flv_10002A111AD1/bndg_url/sap/bc/srt/rfc/sap/zws_ges_cit/400/zws_gescit/zws_gescit?sap-client=400'),
        'usuario' => env('CITAS_USUARIO', 'USR_TRK'),
        'password' => env('CITAS_PASSWORD', 'Srv0103$MrvTk%'),
    ],

];
