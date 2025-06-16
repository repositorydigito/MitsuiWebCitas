<?php

namespace App\Services;

use App\Models\User;
use App\Models\Vehicle;
use App\Services\C4C\AppointmentQueryService;
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
     * Servicio de consulta de citas C4C
     */
    protected AppointmentQueryService $appointmentQueryService;

    /**
     * Constructor del servicio
     *
     * @param  string|null  $wsdlUrl  - URL del WSDL (opcional, se usa configuración si no se proporciona)
     */
    public function __construct(
        ?string $wsdlUrl = null,
        ?VehiculoWebServiceHealthCheck $healthCheck = null,
        ?MockVehiculoService $mockService = null,
        ?AppointmentQueryService $appointmentQueryService = null
    ) {
        // Estrategia de fallback para WSDL basada en configuración:
        $localWsdl = storage_path('wsdl/vehiculos.wsdl');
        $remoteWsdl = $wsdlUrl ?? config('services.sap_3p.wsdl_url');
        $preferLocal = config('vehiculos_webservice.prefer_local_wsdl', true);

        if ($preferLocal && file_exists($localWsdl)) {
            // Preferir WSDL local si está habilitado y existe
            $this->wsdlUrl = $localWsdl;
            Log::info('[VehiculoSoapService] Usando WSDL local (preferencia configurada):', ['path' => $localWsdl]);
        } elseif (! $preferLocal) {
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
        $this->appointmentQueryService = $appointmentQueryService ?? app(AppointmentQueryService::class);
    }

    /**
     * Crear cliente SOAP con timeout personalizado
     */
    protected function crearClienteSoapConTimeout(int $timeoutSegundos): ?SoapClient
    {
        if (empty($this->wsdlUrl)) {
            Log::error('[VehiculoSoapService] No se puede crear cliente SOAP: URL/ruta WSDL no válida.');

            return null;
        }

        // Configurar opciones del cliente SOAP con timeout personalizado MÁS AGRESIVO
        $opciones = [
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE,
            'connection_timeout' => $timeoutSegundos,
            'default_socket_timeout' => $timeoutSegundos,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
                'http' => [
                    'timeout' => $timeoutSegundos,
                    'method' => 'POST',
                    'ignore_errors' => true,
                ],
                'socket' => [
                    'timeout' => $timeoutSegundos,
                ],
            ]),
            'login' => config('services.sap_3p.usuario'),
            'password' => config('services.sap_3p.password'),
        ];

        $isLocalWsdl = file_exists($this->wsdlUrl);
        $wsdlType = $isLocalWsdl ? 'local' : 'remoto';

        Log::debug("[VehiculoSoapService] Creando cliente SOAP con WSDL {$wsdlType} y timeout {$timeoutSegundos}s:", [
            'wsdl' => $this->wsdlUrl,
            'usuario' => config('services.sap_3p.usuario'),
            'timeout' => $timeoutSegundos,
        ]);

        try {
            // Crear el cliente SOAP
            $cliente = new SoapClient($this->wsdlUrl, $opciones);
            Log::info("[VehiculoSoapService] Cliente SOAP creado exitosamente usando WSDL {$wsdlType} con timeout {$timeoutSegundos}s.");

            return $cliente;
        } catch (SoapFault $e) {
            Log::error("[VehiculoSoapService] SoapFault al crear cliente SOAP con WSDL {$wsdlType} (timeout {$timeoutSegundos}s):", [
                'wsdl' => $this->wsdlUrl,
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("[VehiculoSoapService] Exception general al crear cliente SOAP con WSDL {$wsdlType} (timeout {$timeoutSegundos}s):", [
                'wsdl' => $this->wsdlUrl,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
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
     * Consulta los vehículos asociados a un cliente con flujo escalonado de 3 niveles
     *
     * FLUJO ESCALONADO:
     * 1. SAP Z3PF_GETLISTAVEHICULOS (primario)
     * 2. C4C WSCitas - Citas Pendientes (intermedio)
     * 3. Base de datos local (último recurso)
     *
     * @param  array  $marcas  Array de códigos de marca
     */
    public function getVehiculosCliente(string $documentoCliente, array $marcas): Collection
    {
        Log::info("[VehiculoSoapService] INICIANDO FLUJO ESCALONADO - Cliente: {$documentoCliente}");

        // NIVEL 1: SERVICIO SAP (PRIMARIO) - TEMPORALMENTE DESHABILITADO POR TIMEOUT
        $sapEnabled = config('vehiculos_webservice.enabled', true) && config('SAP_ENABLED', false);
        if ($sapEnabled) {
            Log::info('[VehiculoSoapService] NIVEL 1: Intentando obtener vehículos desde SAP Z3PF_GETLISTAVEHICULOS');

            $vehiculosSAP = $this->getVehiculosDesdeSAP($documentoCliente, $marcas);
            if (! $vehiculosSAP->isEmpty()) {
                Log::info('[VehiculoSoapService] NIVEL 1: Vehículos obtenidos exitosamente desde SAP Z3PF_GETLISTAVEHICULOS');

                // **NUEVA FUNCIONALIDAD: Persistir vehículos en BD**
                $this->persistirVehiculosEnBD($vehiculosSAP, $documentoCliente);

                return $vehiculosSAP;
            }
        } else {
            Log::warning('[VehiculoSoapService] NIVEL 1: SAP deshabilitado debido a problemas de timeout. Saltando a NIVEL 2 (BD Local).');
        }

        // NIVEL 2: C4C WSCitas - Citas Pendientes (INTERMEDIO) - **DESACTIVADO**
        // if (config('vehiculos_webservice.enabled', true)) {
        //     Log::info('[VehiculoSoapService] NIVEL 2: Intentando obtener vehículos desde C4C WSCitas - Citas Pendientes');
        //
        //     $vehiculosC4C = $this->getVehiculosDesdeC4C($documentoCliente, $marcas);
        //     if (! $vehiculosC4C->isEmpty()) {
        //         Log::info('[VehiculoSoapService] NIVEL 2: Vehículos obtenidos exitosamente desde C4C WSCitas - Citas Pendientes');
        //
        //         // **NUEVA FUNCIONALIDAD: Persistir vehículos en BD**
        //         $this->persistirVehiculosEnBD($vehiculosC4C, $documentoCliente);
        //
        //         return $vehiculosC4C;
        //     }
        // }

        Log::info('[VehiculoSoapService] NIVEL 2: C4C WSCitas desactivado. Saltando directamente a Base de Datos Local.');

        // NIVEL 2: Base de datos local (FALLBACK DIRECTO DESDE SAP)
        if (config('vehiculos_webservice.enabled', true)) {
            Log::info('[VehiculoSoapService] NIVEL 2: Intentando obtener vehículos desde base de datos local');

            $vehiculosLocal = $this->getVehiculosLocal($documentoCliente, $marcas);
            if (! $vehiculosLocal->isEmpty()) {
                Log::info('[VehiculoSoapService] NIVEL 2: Vehículos obtenidos exitosamente desde base de datos local');

                return $vehiculosLocal;
            }
        }

        // Si no se obtuvieron vehículos en ningún nivel, usar datos simulados
        Log::warning('[VehiculoSoapService] No se encontraron vehículos en los niveles 1 y 2 (SAP y BD Local). Usando datos simulados.');
        $vehiculosMock = $this->mockService->getVehiculosCliente($documentoCliente, $marcas);

        // **NUEVA FUNCIONALIDAD: Persistir datos mock también para testing**
        if (! $vehiculosMock->isEmpty()) {
            $this->persistirVehiculosEnBD($vehiculosMock, $documentoCliente);
        }

        return $vehiculosMock;
    }

    /**
     * Consulta los vehículos asociados a un cliente para múltiples marcas
     *
     * @param  array  $marcas  Array de códigos de marca
     */
    public function getVehiculosDesdeSAP(string $documentoCliente, array $marcas): Collection
    {
        // Usar cliente SOAP con timeout reducido para SAP
        $cliente = $this->crearClienteSoapConTimeout(8); // 8 segundos timeout para SAP
        if (! $cliente) {
            Log::error('[VehiculoSoapService] Abortando getVehiculosDesdeSAP: No se pudo crear el cliente SOAP.');

            return collect();
        }

        $todosLosVehiculos = collect();
        $lastRequest = null;
        $lastResponse = null;
        $erroresConsecutivos = 0;

        foreach ($marcas as $marca) {
            try {
                Log::info("[VehiculoSoapService] Consultando vehículos para cliente {$documentoCliente} y marca {$marca}");

                $parametros = [
                    'PI_NUMDOCCLI' => $documentoCliente,
                    'PI_MARCA' => $marca,
                ];

                Log::debug('[VehiculoSoapService] Parámetros para Z3PF_GETLISTAVEHICULOS:', $parametros);

                // Control de tiempo de inicio para timeout manual MUY ESTRICTO
                $inicioTiempo = microtime(true);

                // Si ya han pasado más de 15 segundos desde el inicio del flujo total, abortar
                static $inicioFlujoTotal = null;
                if ($inicioFlujoTotal === null) {
                    $inicioFlujoTotal = microtime(true);
                }

                $tiempoTotalTranscurrido = microtime(true) - $inicioFlujoTotal;
                if ($tiempoTotalTranscurrido > 15) { // 15 segundos máximo para todo el flujo SAP
                    Log::warning('[VehiculoSoapService] Timeout total del flujo SAP detectado: '.round($tiempoTotalTranscurrido, 2)."s. Abortando marca {$marca}.");
                    break; // Abortar completamente SAP
                }

                try {
                    // TIMEOUT EXTREMO: Si ya tardó más de 5 segundos desde el inicio, abortar inmediatamente
                    if ($tiempoTotalTranscurrido > 5) {
                        throw new \Exception("Timeout preventivo: flujo SAP ya lleva {$tiempoTotalTranscurrido}s");
                    }

                    // Implementar timeout usando proceso asíncrono con tiempo límite REAL
                    $timeoutReal = 8; // 8 segundos máximo REAL
                    $timeStart = microtime(true);

                    // Capturar cualquier excepción y convertirla en timeout si tarda más de 8s
                    $pid = null;
                    $descriptorspec = [
                        0 => ['pipe', 'r'],  // stdin
                        1 => ['pipe', 'w'],  // stdout
                        2 => ['pipe', 'w'],   // stderr
                    ];

                    // Llamada SOAP con control de tiempo manual
                    $respuesta = $cliente->Z3PF_GETLISTAVEHICULOS($parametros);

                    $timeElapsed = microtime(true) - $timeStart;
                    if ($timeElapsed > $timeoutReal) {
                        throw new \Exception("Timeout manual: llamada tardó {$timeElapsed}s (límite {$timeoutReal}s)");
                    }

                    $erroresConsecutivos = 0; // Reset contador de errores si la llamada es exitosa
                } catch (SoapFault $e) {
                    $erroresConsecutivos++;
                    $tiempoTranscurrido = microtime(true) - $inicioTiempo;
                    Log::error("[VehiculoSoapService] SoapFault en llamada Z3PF_GETLISTAVEHICULOS (Marca: {$marca}): ", [
                        'message' => $e->getMessage(),
                        'code' => $e->getCode(),
                        'tiempo_transcurrido' => round($tiempoTranscurrido, 2).'s',
                        'errores_consecutivos' => $erroresConsecutivos,
                        'request' => $cliente->__getLastRequest() ?? 'N/A',
                        'response' => $cliente->__getLastResponse() ?? 'N/A',
                    ]);

                    // Si hay 2 errores consecutivos, abortar SAP inmediatamente
                    if ($erroresConsecutivos >= 2) {
                        Log::warning("[VehiculoSoapService] Abortando SAP debido a {$erroresConsecutivos} errores consecutivos. Pasando a NIVEL 2 (BD Local).");
                        break; // Salir del foreach para pasar al siguiente nivel
                    }

                    continue; // Saltar a la siguiente marca sin procesar
                }

                // Verificar si excedió el timeout manual
                $tiempoTranscurrido = microtime(true) - $inicioTiempo;
                if ($tiempoTranscurrido > 8) { // 8 segundos para dar margen
                    Log::warning("[VehiculoSoapService] Timeout manual detectado para marca {$marca}: ".round($tiempoTranscurrido, 2).'s');

                    continue; // Saltar a la siguiente marca
                }

                $lastRequest = $cliente->__getLastRequest();
                $lastResponse = $cliente->__getLastResponse();

                Log::debug('[VehiculoSoapService] RAW SOAP Request (Marca: '.$marca.'):', ['xml' => $lastRequest]);
                Log::debug('[VehiculoSoapService] RAW SOAP Response (Marca: '.$marca.'):', ['xml' => $lastResponse]);

                Log::info("[VehiculoSoapService] Respuesta SOAP recibida para marca {$marca}. Procesando...");

                $vehiculosMarca = $this->procesarRespuestaSoap($respuesta);

                // *** Añadir la marca y fuente de datos a cada vehículo procesado ***
                $vehiculosConMarca = $vehiculosMarca->map(function ($vehiculo) use ($marca) {
                    $vehiculo['marca_codigo'] = $marca; // Añadir el código de la marca
                    $vehiculo['fuente_datos'] = 'SAP_Z3PF'; // **NUEVO: Marcar fuente de datos para persistencia**

                    return $vehiculo;
                });

                // Usar la colección con la marca añadida
                $todosLosVehiculos = $todosLosVehiculos->merge($vehiculosConMarca);

                // Log actualizado para mostrar la cuenta de la colección correcta
                Log::info("[VehiculoSoapService] Se procesaron {$vehiculosConMarca->count()} vehículos para la marca {$marca}");
            } catch (\Exception $e) {
                $erroresConsecutivos++;
                Log::error("[VehiculoSoapService] Exception general al obtener vehículos (Marca: {$marca}): ", [
                    'message' => $e->getMessage(),
                ]);

                // Si hay 2 errores consecutivos, abortar SAP inmediatamente
                if ($erroresConsecutivos >= 2) {
                    Log::warning("[VehiculoSoapService] Abortando SAP debido a {$erroresConsecutivos} errores consecutivos. Pasando a NIVEL 2 (BD Local).");
                    break; // Salir del foreach para pasar al siguiente nivel
                }
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

    /**
     * Obtener vehículos desde C4C WSCitas - Citas Pendientes (NIVEL 2)
     */
    protected function getVehiculosDesdeC4C(string $documentoCliente, array $marcas): Collection
    {
        try {
            Log::info("[VehiculoSoapService] Buscando c4c_internal_id para cliente: {$documentoCliente}");

            // Buscar el c4c_internal_id del usuario en la base de datos
            $user = User::where('document_number', $documentoCliente)->first();

            if (! $user || ! $user->c4c_internal_id) {
                Log::warning("[VehiculoSoapService] No se encontró c4c_internal_id para cliente: {$documentoCliente}");

                return collect();
            }

            $c4cInternalId = $user->c4c_internal_id;
            Log::info("[VehiculoSoapService] C4C Internal ID encontrado: {$c4cInternalId}");

            // Consultar citas pendientes en C4C
            $appointmentResult = $this->appointmentQueryService->getPendingAppointments($c4cInternalId);

            if (! $appointmentResult['success'] || ! isset($appointmentResult['data'])) {
                Log::warning("[VehiculoSoapService] No se pudieron obtener citas pendientes de C4C para cliente: {$c4cInternalId}");

                return collect();
            }

            $appointments = $appointmentResult['data'];
            Log::info('[VehiculoSoapService] Encontradas '.count($appointments).' citas pendientes en C4C');

            // Extraer vehículos únicos de las citas
            $vehiculosEncontrados = collect();
            $placasProcessadas = [];

            foreach ($appointments as $appointment) {
                // Usar los campos correctos de C4C según la estructura real
                $placa = $appointment['vehicle']['plate'] ?? null;

                if ($placa && ! in_array($placa, $placasProcessadas)) {
                    $placasProcessadas[] = $placa;

                    // Crear estructura de vehículo compatible usando datos reales de C4C
                    $vehiculo = [
                        'vhclie' => $appointment['vehicle']['vin'] ?? $appointment['vehicle']['vin_tmp'] ?? $placa, // **CORREGIDO: Usar VIN real del vehículo**
                        'numpla' => $placa,
                        'modver' => $appointment['vehicle']['model_description'] ?? 'Modelo desde C4C',
                        'aniomod' => $appointment['vehicle']['year'] ?? date('Y'),
                        'marca_codigo' => $this->determinarMarcaPorPlaca($placa, $marcas),
                        'marca_nombre' => $this->mapearCodigoMarca($this->determinarMarcaPorPlaca($placa, $marcas)),
                        'kilometraje' => $appointment['vehicle']['mileage'] ?? 0,
                        'color' => $appointment['vehicle']['color'] ?? 'No especificado',
                        'vin' => $appointment['vehicle']['vin'] ?? '',
                        'motor' => $appointment['vehicle']['motor'] ?? '',
                        'ultimo_servicio_fecha' => null,
                        'ultimo_servicio_km' => 0,
                        'proximo_servicio_fecha' => $appointment['dates']['scheduled_start_date'] ?? null,
                        'proximo_servicio_km' => 0,
                        'mantenimiento_prepagado' => false,
                        'mantenimiento_prepagado_vencimiento' => null,
                        'imagen_url' => null,
                        'fuente_datos' => 'C4C_WSCitas', // Marcar la fuente
                    ];

                    $vehiculosEncontrados->push($vehiculo);
                }
            }

            Log::info('[VehiculoSoapService] Extraídos '.$vehiculosEncontrados->count().' vehículos únicos desde C4C');

            return $vehiculosEncontrados;

        } catch (\Exception $e) {
            Log::error('[VehiculoSoapService] Error al obtener vehículos desde C4C: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * Obtener vehículos desde base de datos local (NIVEL 3)
     */
    protected function getVehiculosLocal(string $documentoCliente, array $marcas): Collection
    {
        try {
            Log::info("[VehiculoSoapService] Buscando vehículos en base de datos local para cliente: {$documentoCliente}");

            // Buscar usuario por documento
            $user = User::where('document_number', $documentoCliente)->first();

            if (! $user) {
                Log::warning("[VehiculoSoapService] Usuario no encontrado en base de datos local: {$documentoCliente}");

                return collect();
            }

            // Buscar vehículos del usuario que estén activos
            $vehiculosDB = Vehicle::where('user_id', $user->id)
                ->where('status', 'active')
                ->whereIn('brand_code', $marcas)
                ->get();

            if ($vehiculosDB->isEmpty()) {
                Log::info("[VehiculoSoapService] No se encontraron vehículos activos en base de datos local para usuario: {$user->id}");

                return collect();
            }

            Log::info('[VehiculoSoapService] Encontrados '.$vehiculosDB->count().' vehículos en base de datos local');

            // Convertir modelos a formato compatible
            $vehiculosCompatibles = $vehiculosDB->map(function ($vehicle) {
                return [
                    'vhclie' => $vehicle->vehicle_id,
                    'numpla' => $vehicle->license_plate,
                    'modver' => $vehicle->model,
                    'aniomod' => $vehicle->year,
                    'marca_codigo' => $vehicle->brand_code,
                    'marca_nombre' => $vehicle->brand_name,
                    'kilometraje' => $vehicle->mileage,
                    'color' => $vehicle->color,
                    'vin' => $vehicle->vin,
                    'motor' => $vehicle->engine_number,
                    'ultimo_servicio_fecha' => $vehicle->last_service_date?->format('Y-m-d'),
                    'ultimo_servicio_km' => $vehicle->last_service_mileage,
                    'proximo_servicio_fecha' => $vehicle->next_service_date?->format('Y-m-d'),
                    'proximo_servicio_km' => $vehicle->next_service_mileage,
                    'mantenimiento_prepagado' => $vehicle->has_prepaid_maintenance,
                    'mantenimiento_prepagado_vencimiento' => $vehicle->prepaid_maintenance_expiry?->format('Y-m-d'),
                    'imagen_url' => $vehicle->image_url,
                    'fuente_datos' => 'BaseDatos_Local', // Marcar la fuente
                ];
            });

            return $vehiculosCompatibles;

        } catch (\Exception $e) {
            Log::error('[VehiculoSoapService] Error al obtener vehículos desde base de datos local: '.$e->getMessage());

            return collect();
        }
    }

    /**
     * Determinar marca por placa (lógica simplificada)
     */
    protected function determinarMarcaPorPlaca(string $placa, array $marcas): string
    {
        // Por defecto, asignar TOYOTA (Z01)
        // En un sistema real, aquí iría la lógica para determinar la marca
        return $marcas[0] ?? 'Z01';
    }

    /**
     * Mapear código de marca a nombre
     */
    protected function mapearCodigoMarca(string $codigo): string
    {
        $mapaMarcas = [
            'Z01' => 'TOYOTA',
            'Z02' => 'LEXUS',
            'Z03' => 'HINO',
        ];

        return $mapaMarcas[$codigo] ?? 'TOYOTA';
    }

    /**
     * **NUEVA FUNCIONALIDAD:** Persistir vehículos obtenidos de webservices en la tabla vehicles
     * Esto permite mantener la referencia entre el vehículo y su ID para envío posterior a C4C
     *
     * @param  Collection  $vehiculos  Vehículos obtenidos de webservices
     * @param  string  $documentoCliente  Documento del cliente propietario
     */
    protected function persistirVehiculosEnBD(Collection $vehiculos, string $documentoCliente): void
    {
        try {
            Log::info("[VehiculoSoapService] PERSISTENCIA: Iniciando persistencia de {$vehiculos->count()} vehículos para cliente: {$documentoCliente}");

            // Buscar el usuario propietario
            $user = User::where('document_number', $documentoCliente)->first();

            if (! $user) {
                Log::warning("[VehiculoSoapService] PERSISTENCIA: Usuario no encontrado para documento {$documentoCliente}. Creando como comodín.");
                // Buscar o crear usuario comodín
                $user = User::where('is_comodin', true)->first();
                if (! $user) {
                    Log::error('[VehiculoSoapService] PERSISTENCIA: No se encontró usuario comodín. Abortando persistencia.');

                    return;
                }
            }

            $vehiculosGuardados = 0;
            $vehiculosActualizados = 0;

            foreach ($vehiculos as $vehiculoData) {
                try {
                    // Extraer datos del vehículo
                    $vehicleId = $vehiculoData['vhclie'] ?? '';
                    $licensePlate = $vehiculoData['numpla'] ?? '';
                    $model = $vehiculoData['modver'] ?? '';
                    $year = $vehiculoData['aniomod'] ?? '';
                    $brandCode = $vehiculoData['marca_codigo'] ?? 'Z01';
                    $brandName = $this->mapearCodigoMarca($brandCode);
                    $fuente = $vehiculoData['fuente_datos'] ?? 'webservice';

                    // Validar que tenemos datos mínimos
                    if (empty($vehicleId) || empty($licensePlate)) {
                        Log::warning('[VehiculoSoapService] PERSISTENCIA: Vehículo con datos insuficientes, saltando: '.json_encode($vehiculoData));

                        continue;
                    }

                    // Buscar si el vehículo ya existe (por vehicle_id o license_plate)
                    $existingVehicle = Vehicle::where('vehicle_id', $vehicleId)
                        ->orWhere('license_plate', $licensePlate)
                        ->first();

                    if ($existingVehicle) {
                        // Actualizar vehículo existente
                        $existingVehicle->update([
                            'model' => $model,
                            'year' => $year,
                            'brand_code' => $brandCode,
                            'brand_name' => $brandName,
                            'user_id' => $user->id,
                            'status' => 'active',
                            // Campos adicionales desde webservice si están disponibles
                            'color' => $vehiculoData['color'] ?? null,
                            'vin' => $vehiculoData['vin'] ?? null,
                            'engine_number' => $vehiculoData['motor'] ?? null,
                            'mileage' => $vehiculoData['kilometraje'] ?? null,
                            'last_service_date' => isset($vehiculoData['ultimo_servicio_fecha']) ?
                                (\Carbon\Carbon::createFromFormat('Y-m-d', $vehiculoData['ultimo_servicio_fecha'])->format('Y-m-d') ?? null) : null,
                            'last_service_mileage' => $vehiculoData['ultimo_servicio_km'] ?? null,
                            'next_service_date' => isset($vehiculoData['proximo_servicio_fecha']) ?
                                (\Carbon\Carbon::createFromFormat('Y-m-d', $vehiculoData['proximo_servicio_fecha'])->format('Y-m-d') ?? null) : null,
                            'next_service_mileage' => $vehiculoData['proximo_servicio_km'] ?? null,
                            'has_prepaid_maintenance' => $vehiculoData['mantenimiento_prepagado'] ?? false,
                            'prepaid_maintenance_expiry' => isset($vehiculoData['mantenimiento_prepagado_vencimiento']) ?
                                (\Carbon\Carbon::createFromFormat('Y-m-d', $vehiculoData['mantenimiento_prepagado_vencimiento'])->format('Y-m-d') ?? null) : null,
                            'image_url' => $vehiculoData['imagen_url'] ?? null,
                        ]);

                        $vehiculosActualizados++;
                        Log::debug("[VehiculoSoapService] PERSISTENCIA: Vehículo actualizado - ID: {$vehicleId}, Placa: {$licensePlate}");

                    } else {
                        // Crear nuevo vehículo
                        Vehicle::create([
                            'vehicle_id' => $vehicleId,
                            'license_plate' => $licensePlate,
                            'model' => $model,
                            'year' => $year,
                            'brand_code' => $brandCode,
                            'brand_name' => $brandName,
                            'user_id' => $user->id,
                            'status' => 'active',
                            // Campos adicionales desde webservice si están disponibles
                            'color' => $vehiculoData['color'] ?? null,
                            'vin' => $vehiculoData['vin'] ?? null,
                            'engine_number' => $vehiculoData['motor'] ?? null,
                            'mileage' => $vehiculoData['kilometraje'] ?? null,
                            'last_service_date' => isset($vehiculoData['ultimo_servicio_fecha']) ?
                                (\Carbon\Carbon::createFromFormat('Y-m-d', $vehiculoData['ultimo_servicio_fecha'])->format('Y-m-d') ?? null) : null,
                            'last_service_mileage' => $vehiculoData['ultimo_servicio_km'] ?? null,
                            'next_service_date' => isset($vehiculoData['proximo_servicio_fecha']) ?
                                (\Carbon\Carbon::createFromFormat('Y-m-d', $vehiculoData['proximo_servicio_fecha'])->format('Y-m-d') ?? null) : null,
                            'next_service_mileage' => $vehiculoData['proximo_servicio_km'] ?? null,
                            'has_prepaid_maintenance' => $vehiculoData['mantenimiento_prepagado'] ?? false,
                            'prepaid_maintenance_expiry' => isset($vehiculoData['mantenimiento_prepagado_vencimiento']) ?
                                (\Carbon\Carbon::createFromFormat('Y-m-d', $vehiculoData['mantenimiento_prepagado_vencimiento'])->format('Y-m-d') ?? null) : null,
                            'image_url' => $vehiculoData['imagen_url'] ?? null,
                        ]);

                        $vehiculosGuardados++;
                        Log::debug("[VehiculoSoapService] PERSISTENCIA: Nuevo vehículo creado - ID: {$vehicleId}, Placa: {$licensePlate}");
                    }

                } catch (\Exception $e) {
                    Log::error('[VehiculoSoapService] PERSISTENCIA: Error al procesar vehículo individual: '.$e->getMessage(), [
                        'vehiculo_data' => $vehiculoData,
                    ]);

                    continue;
                }
            }

            Log::info("[VehiculoSoapService] PERSISTENCIA: Completada exitosamente. Nuevos: {$vehiculosGuardados}, Actualizados: {$vehiculosActualizados}");

        } catch (\Exception $e) {
            Log::error('[VehiculoSoapService] PERSISTENCIA: Error crítico durante persistencia: '.$e->getMessage());
        }
    }
}
