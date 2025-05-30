<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

class VehiculoSoapService
{
    /**
     * URL del servicio SOAP
     *
     * @var string
     */
    protected $wsdlUrl;

    /**
     * Servicio de verificación de salud
     */
    protected VehiculoWebServiceHealthCheck $healthCheck;

    /**
     * Servicio de datos mock
     */
    protected MockVehiculoService $mockService;

    /**
     * Constructor del servicio
     *
     * @param  string|null  $wsdlUrl  - URL del WSDL (opcional, se usa configuración si no se proporciona)
     */
    public function __construct(
        ?string $wsdlUrl = null,
        ?VehiculoWebServiceHealthCheck $healthCheck = null,
        ?MockVehiculoService $mockService = null
    ) {
        // Estrategia de fallback para WSDL basada en configuración:
        $localWsdl = storage_path('wsdl/vehiculos.wsdl');
        $remoteWsdl = $wsdlUrl ?? config('services.sap_3p.wsdl_url');
        $preferLocal = config('vehiculos_webservice.prefer_local_wsdl', true);

        if ($preferLocal && file_exists($localWsdl)) {
            // Preferir WSDL local si está habilitado y existe
            $this->wsdlUrl = $localWsdl;
            Log::info('[VehiculoSoapService] Usando WSDL local (preferencia configurada):', ['path' => $localWsdl]);
        } elseif (!$preferLocal) {
            // Usar directamente el remoto si no se prefiere el local
            $this->wsdlUrl = $remoteWsdl;
            Log::info('[VehiculoSoapService] Usando WSDL remoto (configuración prefer_local_wsdl=false):', ['url' => $remoteWsdl]);
        } elseif (file_exists($localWsdl)) {
            // Fallback al local si existe
            $this->wsdlUrl = $localWsdl;
            Log::info('[VehiculoSoapService] Usando WSDL local como fallback:', ['path' => $localWsdl]);
        } else {
            // Último recurso: remoto
            $this->wsdlUrl = $remoteWsdl;
            Log::info('[VehiculoSoapService] WSDL local no encontrado, usando URL remota:', ['url' => $remoteWsdl]);
        }

        // Inicializar servicios de soporte
        $this->healthCheck = $healthCheck ?? app(VehiculoWebServiceHealthCheck::class);
        $this->mockService = $mockService ?? app(MockVehiculoService::class);
    }

    /**
     * Crear cliente SOAP con estrategia de fallback
     */
    protected function crearClienteSoap(): ?SoapClient
    {
        if (empty($this->wsdlUrl)) {
            Log::error('[VehiculoSoapService] No se puede crear cliente SOAP: URL/ruta WSDL no válida.');
            return null;
        }

        // Configurar opciones del cliente SOAP usando las nuevas variables
        $opciones = [
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'connection_timeout' => config('vehiculos_webservice.timeout', 30),
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
                'http' => [
                    'timeout' => config('vehiculos_webservice.timeout', 30),
                ],
            ]),
            'login' => config('services.sap_3p.usuario'),
            'password' => config('services.sap_3p.password'),
        ];

        $isLocalWsdl = file_exists($this->wsdlUrl);
        $wsdlType = $isLocalWsdl ? 'local' : 'remoto';

        Log::debug("[VehiculoSoapService] Creando cliente SOAP con WSDL {$wsdlType}:", [
            'wsdl' => $this->wsdlUrl,
            'usuario' => config('services.sap_3p.usuario'),
            'timeout' => config('vehiculos_webservice.timeout', 30),
        ]);

        try {
            // Crear el cliente SOAP
            $cliente = new SoapClient($this->wsdlUrl, $opciones);
            Log::info("[VehiculoSoapService] Cliente SOAP creado exitosamente usando WSDL {$wsdlType}.");

            return $cliente;
        } catch (SoapFault $e) {
            Log::error("[VehiculoSoapService] SoapFault al crear cliente SOAP con WSDL {$wsdlType}:", [
                'wsdl' => $this->wsdlUrl,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            // Si falló el WSDL local y está habilitado el fallback, intentar con el remoto
            if ($isLocalWsdl && config('vehiculos_webservice.prefer_local_wsdl', true)) {
                return $this->intentarWsdlRemoto($opciones);
            }

            // Marcar el servicio como no disponible
            $this->healthCheck->isAvailable(true);
            return null;
        } catch (\Exception $e) {
            Log::error("[VehiculoSoapService] Exception general al crear cliente SOAP con WSDL {$wsdlType}:", [
                'wsdl' => $this->wsdlUrl,
                'message' => $e->getMessage(),
            ]);

            // Si falló el WSDL local y está habilitado el fallback, intentar con el remoto
            if ($isLocalWsdl && config('vehiculos_webservice.prefer_local_wsdl', true)) {
                return $this->intentarWsdlRemoto($opciones);
            }

            return null;
        }
    }

    /**
     * Intentar crear cliente SOAP con WSDL remoto como fallback
     */
    protected function intentarWsdlRemoto(array $opciones): ?SoapClient
    {
        $remoteWsdl = config('services.sap_3p.wsdl_url');

        if (empty($remoteWsdl)) {
            Log::error('[VehiculoSoapService] No hay URL remota configurada para fallback.');
            return null;
        }

        Log::warning('[VehiculoSoapService] Intentando fallback con WSDL remoto:', ['url' => $remoteWsdl]);

        try {
            $cliente = new SoapClient($remoteWsdl, $opciones);
            Log::info('[VehiculoSoapService] Cliente SOAP creado exitosamente usando WSDL remoto como fallback.');

            // Actualizar la URL para futuras llamadas en esta sesión
            $this->wsdlUrl = $remoteWsdl;

            return $cliente;
        } catch (SoapFault $e) {
            Log::error('[VehiculoSoapService] SoapFault también en WSDL remoto:', [
                'url' => $remoteWsdl,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            // Marcar el servicio como no disponible
            $this->healthCheck->isAvailable(true);
            return null;
        } catch (\Exception $e) {
            Log::error('[VehiculoSoapService] Exception también en WSDL remoto:', [
                'url' => $remoteWsdl,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Consulta los vehículos asociados a un cliente para múltiples marcas
     *
     * @param  array  $marcas  Array de códigos de marca
     */
    public function getVehiculosCliente(string $documentoCliente, array $marcas): Collection
    {
        // Aumentar temporalmente el tiempo máximo de ejecución para esta operación
        ini_set('max_execution_time', 120); // 120 segundos

        // Verificar si debemos usar datos mock (por configuración o por estado de servicio)
        if (! config('vehiculos_webservice.enabled', true) || ! $this->healthCheck->isAvailable()) {
            Log::warning('[VehiculoSoapService] Usando datos simulados para getVehiculosCliente.');

            return $this->mockService->getVehiculosCliente($documentoCliente, $marcas);
        }

        $cliente = $this->crearClienteSoap();
        if (! $cliente) {
            Log::error('[VehiculoSoapService] Abortando getVehiculosCliente: No se pudo crear el cliente SOAP.');

            // Activar modo fallback si no se pudo crear el cliente
            return $this->mockService->getVehiculosCliente($documentoCliente, $marcas);
        }

        $todosLosVehiculos = collect();
        $lastRequest = null;
        $lastResponse = null;

        foreach ($marcas as $marca) {
            try {
                Log::info("[VehiculoSoapService] Consultando vehículos para cliente {$documentoCliente} y marca {$marca}");

                $parametros = [
                    'PI_NUMDOCCLI' => $documentoCliente,
                    'PI_MARCA' => $marca,
                ];

                Log::debug('[VehiculoSoapService] Parámetros para Z3PF_GETLISTAVEHICULOS:', $parametros);

                $respuesta = $cliente->Z3PF_GETLISTAVEHICULOS($parametros);

                $lastRequest = $cliente->__getLastRequest();
                $lastResponse = $cliente->__getLastResponse();

                Log::debug('[VehiculoSoapService] RAW SOAP Request (Marca: '.$marca.'):', ['xml' => $lastRequest]);
                Log::debug('[VehiculoSoapService] RAW SOAP Response (Marca: '.$marca.'):', ['xml' => $lastResponse]);

                Log::info("[VehiculoSoapService] Respuesta SOAP recibida para marca {$marca}. Procesando...");

                $vehiculosMarca = $this->procesarRespuestaSoap($respuesta);

                // *** Añadir la marca a cada vehículo procesado ***
                $vehiculosConMarca = $vehiculosMarca->map(function ($vehiculo) use ($marca) {
                    $vehiculo['marca_codigo'] = $marca; // Añadir el código de la marca

                    // Podríamos añadir el nombre si lo tuviéramos mapeado aquí, pero lo haremos en la página
                    return $vehiculo;
                });

                // Usar la colección con la marca añadida
                $todosLosVehiculos = $todosLosVehiculos->merge($vehiculosConMarca);

                // Log actualizado para mostrar la cuenta de la colección correcta
                Log::info("[VehiculoSoapService] Se procesaron {$vehiculosConMarca->count()} vehículos para la marca {$marca}");

            } catch (SoapFault $e) {
                $lastRequest = $cliente->__getLastRequest();
                $lastResponse = $cliente->__getLastResponse();
                Log::error("[VehiculoSoapService] SoapFault en llamada Z3PF_GETLISTAVEHICULOS (Marca: {$marca}): ", [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'request' => $lastRequest ?? 'N/A',
                    'response' => $lastResponse ?? 'N/A',
                ]);
            } catch (\Exception $e) {
                Log::error("[VehiculoSoapService] Exception general al obtener vehículos (Marca: {$marca}): ", [
                    'message' => $e->getMessage(),
                ]);
            }
        }

        Log::info("[VehiculoSoapService] Total final de vehículos encontrados para cliente {$documentoCliente}: {$todosLosVehiculos->count()}");

        return $todosLosVehiculos;
    }

    /**
     * Procesa la respuesta del servicio SOAP y devuelve una colección de vehículos
     *
     * @param  object  $soapResponse  Respuesta directa del cliente SOAP
     */
    protected function procesarRespuestaSoap(object $soapResponse): Collection
    {
        $items = collect();
        Log::debug('[VehiculoSoapService] Iniciando procesarRespuestaSoap. Respuesta recibida (JSON):', ['responseJson' => json_encode($soapResponse)]);

        try {
            $listaVeh = $soapResponse->TT_LISVEH ?? null;

            if (! $listaVeh) {
                Log::warning('[VehiculoSoapService] No se encontró la propiedad \'TT_LISVEH\' en la respuesta SOAP.');

                return $items;
            }

            if (! isset($listaVeh->item)) {
                Log::info('[VehiculoSoapService] La propiedad \'TT_LISVEH\' existe, pero no contiene \'item\'. Asumiendo 0 vehículos.', ['TT_LISVEH_Type' => gettype($listaVeh)]);

                return $items;
            }

            $vehiculos = is_array($listaVeh->item) ? $listaVeh->item : [$listaVeh->item];
            $count = count($vehiculos);
            Log::info("[VehiculoSoapService] Procesando respuesta SOAP. Número de items encontrados en TT_LISVEH->item: {$count}");

            foreach ($vehiculos as $index => $vehiculo) {
                Log::debug("[VehiculoSoapService] Procesando item #{$index}:", ['itemData' => json_encode($vehiculo)]);
                if (is_object($vehiculo) || is_array($vehiculo)) {
                    $vehiculo = (object) $vehiculo;
                    $data = [
                        'vhclie' => (string) ($vehiculo->VHCLE ?? $vehiculo->VHCLIE ?? ''),
                        'numpla' => (string) ($vehiculo->NUMPLA ?? ''),
                        'aniomod' => (string) ($vehiculo->ANIOMOD ?? ''),
                        'modver' => (string) ($vehiculo->MODVER ?? ''),
                    ];
                    $items->push($data);
                    Log::debug("[VehiculoSoapService] Item #{$index} procesado y añadido:", $data);
                } else {
                    Log::warning("[VehiculoSoapService] Item de vehículo en índice {$index} no es objeto ni array:", ['itemType' => gettype($vehiculo), 'itemValue' => $vehiculo]);
                }
            }

            Log::info('[VehiculoSoapService] Finalizado procesarRespuestaSoap exitosamente.');

            return $items;

        } catch (\Exception $e) {
            Log::error('[VehiculoSoapService] Exception al procesar respuesta SOAP de vehículos:', [
                'message' => $e->getMessage(),
                'responseFragment' => json_encode($soapResponse),
            ]);

            return collect();
        }
    }

    /**
     * Obtener datos de ejemplo para desarrollo/demos
     *
     * @param  int  $count  Número de vehículos por marca
     */
    public function getDatosEjemplo(int $count = 3): Collection
    {
        Log::info('[VehiculoSoapService] Generando datos de ejemplo para demo/desarrollo');

        return $this->mockService->getDatosEjemplo($count);
    }

    /**
     * Verificar estado de disponibilidad del servicio
     *
     * @param  bool  $forceCheck  Forzar verificación sin usar caché
     */
    public function verificarDisponibilidad(bool $forceCheck = false): bool
    {
        return $this->healthCheck->isAvailable($forceCheck);
    }

    /**
     * Resetear estado del servicio de salud
     */
    public function resetearEstadoServicio(): void
    {
        $this->healthCheck->reset();
    }
}
