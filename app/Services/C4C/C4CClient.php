<?php

namespace App\Services\C4C;

use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

class C4CClient
{
    /**
     * Create a new SOAP client instance.
     *
     * @return SoapClient|null
     */
    public static function create(string $wsdl)
    {
        try {
            // Configurar opciones del cliente SOAP
            $options = [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => config('c4c.timeout', 30),
                'login' => config('c4c.auth.username'),
                'password' => config('c4c.auth.password'),
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                    'http' => [
                        'header' => [
                            'Authorization: Basic '.base64_encode(config('c4c.auth.username').':'.config('c4c.auth.password')),
                            'Content-Type: text/xml; charset=utf-8',
                        ],
                        'timeout' => config('c4c.timeout', 30),
                    ],
                ]),
            ];

            // Intentar crear el cliente SOAP
            Log::debug('Intentando crear cliente SOAP con WSDL: '.$wsdl);

            // Verificar si podemos acceder al WSDL
            $wsdlContent = @file_get_contents($wsdl, false, stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
                'http' => [
                    'header' => [
                        'Authorization: Basic '.base64_encode(config('c4c.auth.username').':'.config('c4c.auth.password')),
                    ],
                    'timeout' => config('c4c.timeout', 30),
                ],
            ]));

            if ($wsdlContent === false) {
                Log::error('No se pudo acceder al WSDL: '.$wsdl);
                Log::error('Error: '.error_get_last()['message']);

                // Intentar usar un WSDL local si está disponible
                $localWsdlPath = storage_path('wsdl/querycustomerin.wsdl');
                if (file_exists($localWsdlPath)) {
                    Log::info('Usando WSDL local: '.$localWsdlPath);

                    return new SoapClient($localWsdlPath, $options);
                }

                return null;
            }

            return new SoapClient($wsdl, $options);
        } catch (SoapFault $e) {
            Log::error('C4C SOAP Client Error: '.$e->getMessage(), [
                'wsdl' => $wsdl,
                'code' => $e->getCode(),
            ]);

            // Intentar usar un WSDL local si está disponible
            try {
                $localWsdlPath = storage_path('wsdl/querycustomerin.wsdl');
                if (file_exists($localWsdlPath)) {
                    Log::info('Usando WSDL local después de error: '.$localWsdlPath);

                    return new SoapClient($localWsdlPath, $options);
                }
            } catch (\Exception $ex) {
                Log::error('Error al usar WSDL local: '.$ex->getMessage());
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Error general al crear cliente SOAP: '.$e->getMessage(), [
                'wsdl' => $wsdl,
                'code' => $e->getCode(),
            ]);

            return null;
        }
    }

    /**
     * Execute a SOAP call and handle errors.
     *
     * @return array
     */
    public static function call(string $wsdl, string $method, array $params)
    {
        $client = self::create($wsdl);

        if (! $client) {
            return [
                'success' => false,
                'error' => 'Failed to create SOAP client',
                'data' => null,
            ];
        }

        try {
            Log::debug('C4C SOAP Request', [
                'method' => $method,
                'params' => $params,
            ]);

            $result = $client->__soapCall($method, [$params]);

            Log::debug('C4C SOAP Response', [
                'result' => json_decode(json_encode($result), true),
            ]);

            return [
                'success' => true,
                'error' => null,
                'data' => $result,
            ];
        } catch (SoapFault $e) {
            Log::error('C4C SOAP Call Error: '.$e->getMessage(), [
                'method' => $method,
                'code' => $e->getCode(),
                'request' => $client->__getLastRequest(),
                'response' => $client->__getLastResponse(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null,
            ];
        }
    }
}
