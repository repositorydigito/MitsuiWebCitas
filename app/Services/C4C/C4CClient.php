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
            // ✅ CREDENCIALES ESPECÍFICAS SEGÚN EL SERVICIO
            $username = config('c4c.username');
            $password = config('c4c.password');

            // Para servicios de ofertas, usar credenciales específicas
            if (strpos($wsdl, 'customerquoteprocessingmanagec') !== false) {
                $username = env('C4C_OFFER_USERNAME', '_USER_INT');
                $password = env('C4C_OFFER_PASSWORD', '/sap/ap/ui/cloginA!"2');
            }

            // Configurar opciones del cliente SOAP
            $options = [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => config('c4c.timeout', 120),
                'login' => $username,
                'password' => $password,
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                    'http' => [
                        'header' => [
                            'Authorization: Basic ' . base64_encode($username . ':' . $password),
                            'Content-Type: text/xml; charset=utf-8',
                        ],
                        'timeout' => config('c4c.timeout', 120),
                    ],
                ]),
            ];

            // Intentar crear el cliente SOAP
            Log::debug('Intentando crear cliente SOAP con WSDL: ' . $wsdl);

            // Verificar si podemos acceder al WSDL
            $wsdlContent = @file_get_contents($wsdl, false, stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
                'http' => [
                    'header' => [
                        'Authorization: Basic ' . base64_encode($username . ':' . $password),
                    ],
                    'timeout' => config('c4c.timeout', 120),
                ],
            ]));

            if ($wsdlContent === false) {
                Log::error('No se pudo acceder al WSDL remoto: ' . $wsdl);
                Log::error('Error: ' . error_get_last()['message']);

                // FORZAR conexión remota como Python - NO usar WSDL local
                Log::warning('Intentando conexión directa al servicio remoto sin validar WSDL...');

                // Intentar crear cliente SOAP directamente con la URL remota
                try {
                    return new SoapClient($wsdl, $options);
                } catch (\Exception $e) {
                    Log::error('Error al conectar directamente al servicio remoto: ' . $e->getMessage());

                    return null;
                }
            }

            return new SoapClient($wsdl, $options);
        } catch (SoapFault $e) {
            Log::error('C4C SOAP Client Error: ' . $e->getMessage(), [
                'wsdl' => $wsdl,
                'code' => $e->getCode(),
            ]);

            // NO usar WSDL local - forzar conexión remota como Python
            Log::error('Fallo al conectar al servicio remoto C4C - NO se usará WSDL local');

            return null;
        } catch (\Exception $e) {
            Log::error('Error general al crear cliente SOAP: ' . $e->getMessage(), [
                'wsdl' => $wsdl,
                'code' => $e->getCode(),
            ]);

            return null;
        }
    }

    /**
     * Execute a SOAP call using HTTP requests like Python.
     *
     * @return array
     */
    public static function call(string $wsdl, string $method, array $params)
    {
        // ✅ PARA OFERTAS: Ir directo a HTTP request (no usar SoapClient)
        if (strpos($wsdl, 'customerquoteprocessingmanagec') !== false) {
            Log::info('🎯 Servicio de ofertas detectado - usando HTTP directo (sin WSDL)');
            return self::makeHttpSoapRequest($wsdl, $method, $params);
        }

        // Para otros servicios: Intentar primero con SoapClient tradicional
        $client = self::create($wsdl);

        if ($client) {
            try {
                Log::debug('C4C SOAP Request (SoapClient)', [
                    'method' => $method,
                    'params' => $params,
                ]);

                $result = $client->__soapCall($method, [$params]);

                Log::debug('C4C SOAP Response (SoapClient)', [
                    'result' => json_decode(json_encode($result), true),
                ]);

                return [
                    'success' => true,
                    'error' => null,
                    'data' => $result,
                ];
            } catch (SoapFault $e) {
                Log::error('C4C SOAP Call Error (SoapClient): ' . $e->getMessage());
                // Continuar con HTTP request como fallback
            }
        }

        // Fallback: HTTP request directo como Python
        Log::info('Intentando HTTP request directo como Python...');

        return self::makeHttpSoapRequest($wsdl, $method, $params);
    }

    /**
     * Make HTTP SOAP request like Python does.
     *
     * @return array
     */
    private static function makeHttpSoapRequest(string $wsdl, string $method, array $params)
    {
        try {
            // Usar la URL completa como Python (incluyendo sap-vhost)
            $url = $wsdl;

            // Construir el cuerpo SOAP basado en los parámetros
            $soapBody = self::buildSoapBody($method, $params);

            // ✅ CREDENCIALES ESPECÍFICAS SEGÚN EL SERVICIO
            $username = config('c4c.username');
            $password = config('c4c.password');

            // Para servicios de ofertas, usar credenciales específicas
            if (strpos($wsdl, 'customerquoteprocessingmanagec') !== false) {
                $username = env('C4C_OFFER_USERNAME', '_USER_INT');
                $password = env('C4C_OFFER_PASSWORD', '/sap/ap/ui/cloginA!"2');
            }

            // Headers como Python
            $headers = [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: ""',
                'Authorization: Basic ' . base64_encode($username . ':' . $password),
                'User-Agent: Laravel-C4C-Client/1.0',
            ];

            Log::info('Enviando HTTP SOAP request como Python', [
                'url' => $url,
                'headers' => $headers,
                'body_preview' => substr($soapBody, 0, 200) . '...',
            ]);

            // ✅ Log completo del SOAP body para debugging específicos
            if (strpos($wsdl, 'customerquoteprocessingmanagec') !== false) {
                Log::info('🔍 [C4CClient] SOAP Body completo para ofertas', [
                    'soap_body' => $soapBody
                ]);
            }

            // ✅ NUEVO: Log completo para eliminación de citas (CUALQUIER actionCode)
            if (strpos($wsdl, 'manageappointmentactivityin1') !== false) {
                Log::info('🔍 [C4CClient] SOAP Body completo para CITAS', [
                    'soap_body_complete' => $soapBody,
                    'contains_action_01' => strpos($soapBody, 'actionCode="01"') !== false,
                    'contains_action_04' => strpos($soapBody, 'actionCode="04"') !== false,
                    'contains_action_05' => strpos($soapBody, 'actionCode="05"') !== false,
                    'contains_lifecycle_1' => strpos($soapBody, 'LifeCycleStatusCode>1<') !== false,
                    'contains_lifecycle_2' => strpos($soapBody, 'LifeCycleStatusCode>2<') !== false,
                    'contains_lifecycle_4' => strpos($soapBody, 'LifeCycleStatusCode>4<') !== false,
                    'contains_estado_6' => strpos($soapBody, 'zEstadoCita>6<') !== false,
                ]);
            }

            // Configurar timeout específico para citas (más tiempo)
            $timeout = config('c4c.timeout', 120); // Default aumentado
            if (
                strpos($wsdl, 'manageappointmentactivityin1') !== false ||
                strpos($wsdl, 'yy6saj0kgy_wscitas') !== false
            ) {
                $timeout = 180; // 3 minutos para operaciones de citas
                Log::info("⏱️ Usando timeout extendido para operaciones de citas: {$timeout}s");
            }

            // Agregar declaración XML solo para HTTP (Postman la necesita para parsing)
            $soapBodyWithDeclaration = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $soapBody;

            // Hacer request HTTP con cURL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $soapBodyWithDeclaration,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => 30, // Más tiempo para conectar
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_FOLLOWLOCATION => true,
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('cURL Error: ' . $error);

                return [
                    'success' => false,
                    'error' => 'cURL Error: ' . $error,
                    'data' => null,
                ];
            }

            Log::info('📥 [C4CClient] HTTP SOAP Response recibida', [
                'http_code' => $httpCode,
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 500) . '...',
            ]);

            // ✅ NUEVO: Log completo de respuesta para eliminación de citas
            if (
                strpos($wsdl, 'manageappointmentactivityin1') !== false &&
                strpos($soapBodyWithDeclaration, 'actionCode="05"') !== false
            ) {
                Log::info('🔍 [C4CClient] Respuesta COMPLETA para ELIMINACIÓN DE CITA', [
                    'http_code' => $httpCode,
                    'response_complete' => $response,
                    'is_success_response' => $httpCode === 200,
                ]);
            }

            if ($httpCode === 200) {
                // Parsear respuesta XML como lo haría SoapClient
                $parsedResponse = self::parseXmlResponse($response);

                // ✅ NUEVO: Log del parsing para eliminación
                if (
                    strpos($wsdl, 'manageappointmentactivityin1') !== false &&
                    strpos($soapBodyWithDeclaration, 'actionCode="05"') !== false
                ) {
                    Log::info('🔍 [C4CClient] Resultado del parsing para ELIMINACIÓN', [
                        'parsed_response_type' => gettype($parsedResponse),
                        'parsed_response_keys' => is_object($parsedResponse) ? array_keys((array)$parsedResponse) : 'not_object',
                        'parsed_response' => json_encode($parsedResponse, JSON_PRETTY_PRINT),
                    ]);
                }

                // ✅ EXTRAER UUID PARA CITAS (SÍNCRONO)
                $result = [
                    'success' => true,
                    'error' => null,
                    'data' => $parsedResponse,
                ];

                // Si es una cita, extraer UUID
                if (strpos($wsdl, 'manageappointmentactivityin1') !== false) {
                    $result['uuid'] = self::extractUuidFromAppointmentResponse($parsedResponse);
                    $result['appointment_id'] = self::extractAppointmentIdFromResponse($parsedResponse);
                }

                return $result;
            } elseif ($httpCode === 500 && strpos($response, 'soap-env:Fault') !== false) {
                // ✅ MANEJAR SOAP FAULT ESPECÍFICAMENTE
                Log::info('📋 Respuesta SOAP Fault recibida - procesando detalles');

                $parsedResponse = self::parseXmlResponse($response);
                $faultDetails = self::extractSoapFaultDetails($response);

                return [
                    'success' => false,
                    'error' => 'SOAP Fault: ' . $faultDetails['faultstring'],
                    'fault_code' => $faultDetails['faultcode'],
                    'transaction_id' => $faultDetails['transaction_id'],
                    'data' => $parsedResponse,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP Error: ' . $httpCode,
                    'data' => null,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Error en HTTP SOAP request: ' . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null,
            ];
        }
    }

    /**
     * Build SOAP body XML like Python does.
     *
     * @return string
     */
    private static function buildSoapBody(string $method, array $params)
    {
        // Gestión de citas - Crear/Actualizar/Eliminar (método original)
        if ($method === 'AppointmentActivityBundleMaintainRequest_sync_V1') {
            return self::buildAppointmentManagementSoapBody($params);
        }

        // Gestión de citas - Crear (método simplificado como el ejemplo)
        if ($method === 'AppointmentCreateRequest_sync') {
            return self::buildAppointmentCreateSoapBody($params);
        }

        // Consulta de citas pendientes
        if ($method === 'ActivityBOVNCitasQueryByElementsSimpleByRequest_sync') {
            return self::buildAppointmentQuerySoapBody($params);
        }

        // ✅ NUEVO: Creación de ofertas (SIMPLIFICADO como Postman)
        if ($method === 'CustomerQuoteBundleMaintainRequest_sync_V1') {
            return self::buildOfferCreateSoapBodySimple($params);
        }

        // Consulta de clientes
        if ($method === 'CustomerByElementsQuery_sync') {
            // Extraer parámetros para consulta de cliente
            $customerSelection = $params['CustomerSelectionByElements'] ?? [];
            $processingConditions = $params['ProcessingConditions'] ?? [];

            // Determinar el tipo de búsqueda
            $searchField = '';
            $searchValue = '';

            if (isset($customerSelection['y6s:zDNI_EA8AE8AUBVHCSXVYS0FJ1R3ON'])) {
                $searchField = 'y6s:zDNI_EA8AE8AUBVHCSXVYS0FJ1R3ON';
                $searchValue = $customerSelection[$searchField]['SelectionByText']['LowerBoundaryName'] ?? '';
            } elseif (isset($customerSelection['y6s:zRuc_EA8AE8AUBVHCSXVYS0FJ1R3ON'])) {
                $searchField = 'y6s:zRuc_EA8AE8AUBVHCSXVYS0FJ1R3ON';
                $searchValue = $customerSelection[$searchField]['SelectionByText']['LowerBoundaryName'] ?? '';
            }

            $maxResults = $processingConditions['QueryHitsMaximumNumberValue'] ?? 20;

            return '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope
    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:glob="http://sap.com/xi/SAPGlobal20/Global"
    xmlns:y6s="http://0002961282-one-off.sap.com/Y6SAJ0KGY_">
    <soapenv:Header/>
    <soapenv:Body>
        <glob:CustomerByElementsQuery_sync>
            <CustomerSelectionByElements>
                <' . $searchField . '>
                    <SelectionByText>
                        <InclusionExclusionCode>I</InclusionExclusionCode>
                        <IntervalBoundaryTypeCode>1</IntervalBoundaryTypeCode>
                        <LowerBoundaryName>' . htmlspecialchars($searchValue) . '</LowerBoundaryName>
                        <UpperBoundaryName/>
                    </SelectionByText>
                </' . $searchField . '>
            </CustomerSelectionByElements>
            <ProcessingConditions>
                <QueryHitsMaximumNumberValue>' . $maxResults . '</QueryHitsMaximumNumberValue>
                <QueryHitsUnlimitedIndicator>false</QueryHitsUnlimitedIndicator>
            </ProcessingConditions>
        </glob:CustomerByElementsQuery_sync>
    </soapenv:Body>
</soapenv:Envelope>';
        }

        // Para otros métodos, devolver un SOAP básico
        return '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/">
    <soapenv:Header/>
    <soapenv:Body>
        <' . $method . '/>
    </soapenv:Body>
</soapenv:Envelope>';
    }

    /**
     * ✅ NUEVO: Extract SOAP Fault details from response
     */
    private static function extractSoapFaultDetails(string $xmlResponse): array
    {
        $details = [
            'faultcode' => 'Unknown',
            'faultstring' => 'Unknown error',
            'transaction_id' => null,
        ];

        try {
            // Extraer faultcode
            if (preg_match('/<faultcode>([^<]+)<\/faultcode>/', $xmlResponse, $matches)) {
                $details['faultcode'] = $matches[1];
            }

            // Extraer faultstring
            if (preg_match('/<faultstring[^>]*>([^<]+)<\/faultstring>/', $xmlResponse, $matches)) {
                $details['faultstring'] = $matches[1];
            }

            // Extraer Transaction ID del faultstring
            if (preg_match('/Transaction ID ([A-F0-9]+)/', $details['faultstring'], $matches)) {
                $details['transaction_id'] = $matches[1];
            }

            Log::info('📋 SOAP Fault details extraídos', $details);
        } catch (\Exception $e) {
            Log::error('Error extrayendo detalles de SOAP Fault: ' . $e->getMessage());
        }

        return $details;
    }

    /**
     * Parse XML response like SoapClient would.
     *
     * @return object
     */
    private static function parseXmlResponse(string $xmlResponse)
    {
        try {
            Log::info('Parseando respuesta XML...', [
                'xml_length' => strlen($xmlResponse),
                'xml_preview' => substr($xmlResponse, 0, 300) . '...',
            ]);

            Log::info('🔍 [DEBUG] XML CRUDO COMPLETO:', [
                'xml_completo' => $xmlResponse
            ]);

            // Limpiar namespaces para simplificar el parsing
            $cleanXml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $xmlResponse);
            // Cambiar regex para NO eliminar los : de las horas (ej: 08:00:00)
            $cleanXml = preg_replace('/<([a-zA-Z0-9\-]*):/', '<', $cleanXml);
            $cleanXml = preg_replace('/([a-zA-Z0-9\-]*):([a-zA-Z])/', '$2', $cleanXml);

            $xml = simplexml_load_string($cleanXml);

            if ($xml === false) {
                Log::error('Error al parsear XML response');

                return (object) [];
            }

            // Convertir a objeto como lo haría SoapClient
            $result = json_decode(json_encode($xml), false);

            Log::info('XML parseado exitosamente', [
                'result_preview' => json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ]);

            // Verificar si hay datos de Customer en la estructura correcta
            if (isset($result->Body->CustomerByElementsResponse_sync->Customer)) {
                Log::info('✅ Customer encontrado en respuesta XML');
            } else {
                Log::warning('❌ No se encontró Customer en la estructura esperada');
                Log::info('Estructura disponible:', [
                    'keys' => array_keys((array) $result),
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Error al parsear respuesta XML: ' . $e->getMessage());

            return (object) [];
        }
    }

    /**
     * Build SOAP body for appointment management operations.
     */
    private static function buildAppointmentManagementSoapBody(array $params): string
    {
        $appointment = $params['AppointmentActivity'] ?? [];
        $actionCode = $appointment['@actionCode'] ?? $appointment['actionCode'] ?? '01';

        // 🔍 LOG DETALLADO para debugging
        Log::info('🔍 [buildAppointmentManagementSoapBody] INICIO CONSTRUCCIÓN XML', [
            'params_received' => $params,
            'appointment_array' => $appointment,
            'actionCode_found' => $actionCode,
            'has_at_actionCode' => isset($appointment['@actionCode']),
            'at_actionCode_value' => $appointment['@actionCode'] ?? 'NO_ENCONTRADO',
            'has_normal_actionCode' => isset($appointment['actionCode']),
            'normal_actionCode_value' => $appointment['actionCode'] ?? 'NO_ENCONTRADO',
        ]);

        // 🔍 DEBUG CRÍTICO: Si actionCode es 04, NO debería incluir placa
        if ($actionCode === '04') {
            Log::info('🔍 [buildAppointmentManagementSoapBody] CANCELACIÓN DETECTADA - NO INCLUIR PLACA', [
                'actionCode' => $actionCode,
                'should_include_plate' => false,
                'will_include_minimal_fields' => true,
            ]);
        }

        // Para UPDATE y DELETE, necesitamos UUID
        $uuid = $appointment['UUID'] ?? '';

        // Extraer datos básicos
        $businessPartnerId = $appointment['MainActivityParty']['BusinessPartnerInternalID'] ?? '1270000347';
        $employeeId = $appointment['AttendeeParty']['EmployeeID'] ?? '7000002';
        $startDateTime = $appointment['StartDateTime']['_'] ?? $appointment['StartDateTime'] ?? '2024-10-08T13:30:00Z';
        $endDateTime = $appointment['EndDateTime']['_'] ?? $appointment['EndDateTime'] ?? '2024-10-08T13:44:00Z';
        $observation = $appointment['Text']['ContentText'] ?? 'Cita de prueba automatizada';

        // Extraer campos personalizados
        $clientName = $appointment['y6s:zClienteComodin'] ?? 'Cliente de Prueba';
        $exitDate = $appointment['y6s:zFechaHoraProbSalida'] ?? '2024-10-08';
        $exitTime = $appointment['y6s:zHoraProbSalida'] ?? '13:40:00';
        $centerId = $appointment['y6s:zIDCentro'] ?? 'M013';
        $licensePlate = $appointment['y6s:zPlaca'] ?? 'NO-PLACA';
        $appointmentStatus = $appointment['y6s:zEstadoCita'] ?? '1';
        $isExpress = $appointment['y6s:zExpress'] ?? 'false';

        // 🔍 DEBUG: Log para ver qué placa está llegando
        Log::info('🔍 [C4CClient] Placa extraída del appointment array', [
            'license_plate_found' => $licensePlate,
            'has_zPlaca_key' => isset($appointment['y6s:zPlaca']),
            'zPlaca_value' => $appointment['y6s:zPlaca'] ?? 'NO_ENCONTRADO',
            'appointment_keys' => array_keys($appointment),
        ]);

        // Solo los campos que Python realmente usa (no más campos adicionales)

        // Construir XML dinámicamente según el actionCode
        $xmlBody = '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope
    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:glob="http://sap.com/xi/SAPGlobal20/Global"
    xmlns:y6s="http://0002961282-one-off.sap.com/Y6SAJ0KGY_"
    xmlns:a25="http://sap.com/xi/AP/CustomerExtension/BYD/A252F">
    <soapenv:Header/>
    <soapenv:Body>
        <glob:AppointmentActivityBundleMaintainRequest_sync_V1>
            <AppointmentActivity actionCode="' . htmlspecialchars($actionCode) . '">';

        // Para UPDATE y DELETE, incluir UUID
        if (! empty($uuid) && ($actionCode === '04' || $actionCode === '05')) {
            $xmlBody .= '
                <UUID>' . htmlspecialchars($uuid) . '</UUID>';
        }

        // Campos básicos (siempre incluidos para CREATE, opcionales para UPDATE/DELETE)
        if ($actionCode === '01') {
            // CREATE: incluir todos los campos requeridos
            $xmlBody .= '
                <DocumentTypeCode>0001</DocumentTypeCode>
                <LifeCycleStatusCode>1</LifeCycleStatusCode>
                <MainActivityParty>
                    <BusinessPartnerInternalID>' . htmlspecialchars($businessPartnerId) . '</BusinessPartnerInternalID>
                </MainActivityParty>
                <AttendeeParty>
                    <EmployeeID>' . htmlspecialchars($employeeId) . '</EmployeeID>
                </AttendeeParty>
                <StartDateTime timeZoneCode="UTC-5">' . htmlspecialchars($startDateTime) . '</StartDateTime>
                <EndDateTime timeZoneCode="UTC-5">' . htmlspecialchars($endDateTime) . '</EndDateTime>
                <Text actionCode="' . htmlspecialchars($actionCode) . '">
                    <TextTypeCode>10002</TextTypeCode>
                    <ContentText>' . htmlspecialchars($observation) . '</ContentText>
                </Text>';
        } elseif ($actionCode === '04' && ! empty($businessPartnerId)) {
            // UPDATE con campos completos: incluir Text solo si se proporciona
            $xmlBody .= '
                <DocumentTypeCode>0001</DocumentTypeCode>
                <LifeCycleStatusCode>2</LifeCycleStatusCode>
                <MainActivityParty>
                    <BusinessPartnerInternalID>' . htmlspecialchars($businessPartnerId) . '</BusinessPartnerInternalID>
                </MainActivityParty>
                <AttendeeParty>
                    <EmployeeID>' . htmlspecialchars($employeeId) . '</EmployeeID>
                </AttendeeParty>
                <StartDateTime timeZoneCode="UTC-5">' . htmlspecialchars($startDateTime) . '</StartDateTime>
                <EndDateTime timeZoneCode="UTC-5">' . htmlspecialchars($endDateTime) . '</EndDateTime>';

            // Solo incluir Text si se proporciona explícitamente
            if (isset($appointment['Text']['ContentText'])) {
                $xmlBody .= '
                <Text actionCode="' . htmlspecialchars($actionCode) . '">
                    <TextTypeCode>10002</TextTypeCode>
                    <ContentText>' . htmlspecialchars($observation) . '</ContentText>
                </Text>';
            }
        } elseif ($actionCode === '04') {
            // UPDATE simple (solo estado): campos mínimos sin Text
            $xmlBody .= '
                <LifeCycleStatusCode>2</LifeCycleStatusCode>';
        }

        // Campos personalizados (solo para CREATE)
        if ($actionCode === '01') {
            $xmlBody .= '
                <y6s:zClienteComodin>' . htmlspecialchars($clientName) . '</y6s:zClienteComodin>
                <y6s:zFechaHoraProbSalida>' . htmlspecialchars($exitDate) . '</y6s:zFechaHoraProbSalida>
                <y6s:zHoraProbSalida>' . htmlspecialchars($exitTime) . '</y6s:zHoraProbSalida>
                <y6s:zIDCentro>' . htmlspecialchars($centerId) . '</y6s:zIDCentro>
                <y6s:zPlaca>' . htmlspecialchars($licensePlate) . '</y6s:zPlaca>
                <y6s:zEstadoCita>' . htmlspecialchars($appointmentStatus) . '</y6s:zEstadoCita>
                <y6s:zVieneHCP>X</y6s:zVieneHCP>
                <y6s:zExpress>' . htmlspecialchars($isExpress) . '</y6s:zExpress>';
        }

        // Para UPDATE/CANCEL (04): solo campos mínimos - NO cambiar placa
        if ($actionCode === '04') {
            $xmlBody .= '
                <y6s:zEstadoCita>' . htmlspecialchars($appointmentStatus) . '</y6s:zEstadoCita>
                <y6s:zVieneHCP>X</y6s:zVieneHCP>';
        }

        // Para DELETE, solo campos mínimos
        if ($actionCode === '05') {
            $xmlBody .= '
                <LifeCycleStatusCode>4</LifeCycleStatusCode>
                <y6s:zEstadoCita>6</y6s:zEstadoCita>
                <y6s:zVieneHCP>X</y6s:zVieneHCP>';
        }

        $xmlBody .= '
            </AppointmentActivity>
        </glob:AppointmentActivityBundleMaintainRequest_sync_V1>
    </soapenv:Body>
</soapenv:Envelope>';

        // 🔍 LOG DEL XML FINAL GENERADO
        Log::info('🔍 [buildAppointmentManagementSoapBody] XML FINAL GENERADO', [
            'actionCode_used' => $actionCode,
            'xml_complete' => $xmlBody,
            'xml_length' => strlen($xmlBody),
        ]);

        return $xmlBody;
    }

    /**
     * Build SOAP body for appointment query operations.
     */
    private static function buildAppointmentQuerySoapBody(array $params): string
    {
        $selection = $params['ActivitySimpleSelectionBy'] ?? [];
        $processing = $params['ProcessingConditions'] ?? [];

        // Extraer parámetros de selección
        $typeCode = $selection['SelectionByTypeCode']['LowerBoundaryTypeCode'] ?? '12';
        $partyId = $selection['SelectionByPartyID']['LowerBoundaryPartyID'] ?? '';
        $lowerStatus = $selection['SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5']['LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5'] ?? '1';
        $upperStatus = $selection['SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5']['UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5'] ?? '2';

        // Parámetros de procesamiento
        $maxResults = $processing['QueryHitsMaximumNumberValue'] ?? 10000;

        return '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope
    xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
    xmlns:glob="http://sap.com/xi/SAPGlobal20/Global">
    <soapenv:Header/>
    <soapenv:Body>
        <glob:ActivityBOVNCitasQueryByElementsSimpleByRequest_sync>
            <ActivitySimpleSelectionBy>
                <SelectionByTypeCode>
                    <InclusionExclusionCode>I</InclusionExclusionCode>
                    <IntervalBoundaryTypeCode>1</IntervalBoundaryTypeCode>
                    <LowerBoundaryTypeCode>' . htmlspecialchars($typeCode) . '</LowerBoundaryTypeCode>
                </SelectionByTypeCode>
                <SelectionByPartyID>
                    <InclusionExclusionCode>I</InclusionExclusionCode>
                    <IntervalBoundaryTypeCode>1</IntervalBoundaryTypeCode>
                    <LowerBoundaryPartyID>' . htmlspecialchars($partyId) . '</LowerBoundaryPartyID>
                    <UpperBoundaryPartyID/>
                </SelectionByPartyID>
                <SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5>
                    <InclusionExclusionCode>I</InclusionExclusionCode>
                    <IntervalBoundaryTypeCode>3</IntervalBoundaryTypeCode>
                    <LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5>' . htmlspecialchars($lowerStatus) . '</LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5>
                    <UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5>' . htmlspecialchars($upperStatus) . '</UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5>
                </SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5>
            </ActivitySimpleSelectionBy>
            <ProcessingConditions>
                <QueryHitsMaximumNumberValue>' . htmlspecialchars($maxResults) . '</QueryHitsMaximumNumberValue>
                <QueryHitsUnlimitedIndicator/>
                <LastReturnedObjectID/>
            </ProcessingConditions>
        </glob:ActivityBOVNCitasQueryByElementsSimpleByRequest_sync>
    </soapenv:Body>
</soapenv:Envelope>';
    }

    /**
     * Build SOAP body for appointment creation (simplified method like the example).
     */
    private static function buildAppointmentCreateSoapBody(array $params): string
    {
        $appointment = $params['Appointment'] ?? [];

        // Extraer datos básicos
        $businessPartnerId = $appointment['BusinessPartnerInternalID'] ?? '1270002726';
        $employeeId = $appointment['EmployeeID'] ?? '1740';
        $startDateTime = $appointment['StartDateTime'] ?? '2025-05-30T14:00:00Z';
        $endDateTime = $appointment['EndDateTime'] ?? '2025-05-30T15:00:00Z';
        $text = $appointment['Text'] ?? 'Nueva cita creada desde Laravel';

        // Campos personalizados
        $licensePlate = $appointment['zPlaca'] ?? 'APP-001';
        $centerId = $appointment['zIDCentro'] ?? 'M013';
        $appointmentStatus = $appointment['zEstadoCita'] ?? '1';
        $exitDate = $appointment['zFechaHoraProbSalida'] ?? '2025-05-30';
        $exitTime = $appointment['zHoraProbSalida'] ?? '15:00:00';
        $driverName = $appointment['zNombresConductor'] ?? 'ALEX TOLEDO';
        $clientPhone = $appointment['zTelefonoCliente'] ?? '+51 994151561';
        $vin = $appointment['zVIN'] ?? 'VINAPP01234567891';
        $vehicleModel = $appointment['zModeloVeh'] ?? '0720';
        $vehicleDescription = $appointment['zDesModeloVeh'] ?? 'YARIS XLI 1.3 GSL';
        $mileage = $appointment['zKilometrajeVeh'] ?? '30252.00';
        $vehicleYear = $appointment['zAnnioVeh'] ?? '2018';
        $vehicleColor = $appointment['zColorVeh'] ?? 'YARIS_070';
        $requestTaxi = $appointment['zSolicitarTaxi'] ?? '1';

        return '<?xml version="1.0" encoding="UTF-8"?>
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:glob="http://sap.com/xi/SAPGlobal20/Global">
   <soapenv:Header/>
   <soapenv:Body>
      <glob:AppointmentCreateRequest_sync>
         <Appointment>
            <DocumentTypeCode>0001</DocumentTypeCode>
            <LifeCycleStatusCode>1</LifeCycleStatusCode>
            <MainActivityParty>
               <BusinessPartnerInternalID>' . htmlspecialchars($businessPartnerId) . '</BusinessPartnerInternalID>
            </MainActivityParty>
            <AttendeeParty>
               <EmployeeID>' . htmlspecialchars($employeeId) . '</EmployeeID>
            </AttendeeParty>
            <StartDateTime>' . htmlspecialchars($startDateTime) . '</StartDateTime>
            <EndDateTime>' . htmlspecialchars($endDateTime) . '</EndDateTime>
            <Text>' . htmlspecialchars($text) . '</Text>
            <zPlaca>' . htmlspecialchars($licensePlate) . '</zPlaca>
            <zIDCentro>' . htmlspecialchars($centerId) . '</zIDCentro>
            <zEstadoCita>' . htmlspecialchars($appointmentStatus) . '</zEstadoCita>
            <zFechaHoraProbSalida>' . htmlspecialchars($exitDate) . '</zFechaHoraProbSalida>
            <zHoraProbSalida>' . htmlspecialchars($exitTime) . '</zHoraProbSalida>
            <zNombresConductor>' . htmlspecialchars($driverName) . '</zNombresConductor>
            <zTelefonoCliente>' . htmlspecialchars($clientPhone) . '</zTelefonoCliente>
            <zVIN>' . htmlspecialchars($vin) . '</zVIN>
            <zModeloVeh>' . htmlspecialchars($vehicleModel) . '</zModeloVeh>
            <zDesModeloVeh>' . htmlspecialchars($vehicleDescription) . '</zDesModeloVeh>
            <zKilometrajeVeh>' . htmlspecialchars($mileage) . '</zKilometrajeVeh>
            <zAnnioVeh>' . htmlspecialchars($vehicleYear) . '</zAnnioVeh>
            <zColorVeh>' . htmlspecialchars($vehicleColor) . '</zColorVeh>
            <zSolicitarTaxi>' . htmlspecialchars($requestTaxi) . '</zSolicitarTaxi>
         </Appointment>
      </glob:AppointmentCreateRequest_sync>
   </soapenv:Body>
</soapenv:Envelope>';
    }

    /**
     * ✅ NUEVO: Build SOAP body for offer creation
     */
    private static function buildOfferCreateSoapBody(array $params): string
    {
        $customerQuote = $params['CustomerQuote'] ?? [];

        // Extraer datos principales
        $actionCode = $customerQuote['actionCode'] ?? '01';
        $processingTypeCode = $customerQuote['ProcessingTypeCode'] ?? 'Z300';
        $name = $customerQuote['Name']['_'] ?? 'OFERTA';
        $nameLanguage = $customerQuote['Name']['languageCode'] ?? 'ES';
        $documentLanguageCode = $customerQuote['DocumentLanguageCode'] ?? 'ES';

        // Datos del cliente
        $buyerParty = $customerQuote['BuyerParty'] ?? [];
        $businessPartnerInternalID = $buyerParty['BusinessPartnerInternalID'] ?? '1200191766';

        // Empleado responsable
        $employeeResponsibleParty = $customerQuote['EmployeeResponsibleParty'] ?? [];
        $employeeID = $employeeResponsibleParty['EmployeeID'] ?? '8000000010';

        // Estructura organizacional
        $sellerParty = $customerQuote['SellerParty'] ?? [];
        $sellerOrgCentreID = $sellerParty['OrganisationalCentreID'] ?? 'GMIT';

        $salesUnitParty = $customerQuote['SalesUnitParty'] ?? [];
        $salesUnitOrgCentreID = $salesUnitParty['OrganisationalCentreID'] ?? 'DM08';

        $salesAndServiceBusinessArea = $customerQuote['SalesAndServiceBusinessArea'] ?? [];
        $salesOrganisationID = $salesAndServiceBusinessArea['SalesOrganisationID'] ?? 'DM08';
        $salesOfficeID = $salesAndServiceBusinessArea['SalesOfficeID'] ?? 'OVDL01';
        $salesGroupID = $salesAndServiceBusinessArea['SalesGroupID'] ?? 'D03';
        $distributionChannelCode = $salesAndServiceBusinessArea['DistributionChannelCode']['_'] ?? 'D4';
        $divisionCode = $salesAndServiceBusinessArea['DivisionCode']['_'] ?? 'D2';

        // Item del producto
        $item = $customerQuote['Item'] ?? [];
        $itemActionCode = $item['actionCode'] ?? '01';
        $itemProcessingTypeCode = $item['ProcessingTypeCode'] ?? 'AGN';

        $itemProduct = $item['ItemProduct'] ?? [];
        $productID = $itemProduct['ProductID']['_'] ?? 'P010';
        $productInternalID = $itemProduct['ProductInternalID']['_'] ?? 'P010';

        $itemRequestedScheduleLine = $item['ItemRequestedScheduleLine'] ?? [];
        $quantity = $itemRequestedScheduleLine['Quantity']['_'] ?? '1.0';
        $unitCode = $itemRequestedScheduleLine['Quantity']['unitCode'] ?? 'EA';

        // Campos personalizados del item
        $zOVPosIDTipoPosicion = $item['y6s:zOVPosIDTipoPosicion']['_'] ?? 'P009';
        $zOVPosTipServ = $item['y6s:zOVPosTipServ']['_'] ?? 'P';
        $zOVPosCantTrab = $item['y6s:zOVPosCantTrab'] ?? '0';
        $zID_PAQUETE = $item['y6s:zID_PAQUETE'] ?? 'M1085-010';
        $zTIPO_PAQUETE = $item['y6s:zTIPO_PAQUETE'] ?? 'Z1';
        $zOVPosTiempoTeorico = $item['y6s:zOVPosTiempoTeorico'] ?? '0';

        // Referencia de documento de transacción de negocio
        $businessTransactionDocumentReference = $customerQuote['BusinessTransactionDocumentReference'] ?? [];
        $btdrActionCode = $businessTransactionDocumentReference['actionCode'] ?? '01';
        $uuid = $businessTransactionDocumentReference['UUID']['_'] ?? '';
        $typeCode = $businessTransactionDocumentReference['TypeCode']['_'] ?? '12';
        $roleCode = $businessTransactionDocumentReference['RoleCode'] ?? '1';

        // Texto
        $text = $customerQuote['Text'] ?? [];
        $textActionCode = $text['actionCode'] ?? '01';
        $textTypeCode = $text['TextTypeCode']['_'] ?? '10024';
        $contentText = $text['ContentText'] ?? 'Oferta generada automáticamente';

        // Campos personalizados principales
        $zOVGrupoVendedores = $customerQuote['y6s:zOVGrupoVendedores'] ?? 'D03';
        $zOVIDCentro = $customerQuote['y6s:zOVIDCentro'] ?? 'L013';
        $zOVPlaca = $customerQuote['y6s:zOVPlaca'] ?? '';
        $zOVVieneDeHCI = $customerQuote['y6s:zOVVieneDeHCI'] ?? 'X';
        $zOVServExpress = $customerQuote['y6s:zOVServExpress'] ?? 'false';
        $zOVKilometraje = $customerQuote['y6s:zOVKilometraje'] ?? '10';
        $zOVOrdenDBMV3 = $customerQuote['y6s:zOVOrdenDBMV3'] ?? '3000694890';

        return '<?xml version="1.0" encoding="UTF-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"
 xmlns:glob="http://sap.com/xi/SAPGlobal20/Global" xmlns:y6s="http://0002961282-one-off.sap.com/Y6SAJ0KGY_" xmlns:a25="http://sap.com/xi/AP/CustomerExtension/BYD/A252F">
    <soap:Header/>
    <soap:Body>
        <glob:CustomerQuoteBundleMaintainRequest_sync_V1>
            <CustomerQuote ViewObjectIndicator="" actionCode="' . htmlspecialchars($actionCode) . '" approverPartyListCompleteTransmissionIndicator="" businessTransactionDocumentReferenceListCompleteTransmissionIndicator="" competitorPartyListCompleteTransmissionIndicator="" itemListCompleteTransmissionIndicator="" otherPartyListCompleteTransmissionIndicator="" salesEmployeePartyListCompleteTransmissionIndicator="" salesPartnerListCompleteTransmissionIndicator="" textListCompleteTransimissionIndicator="">
                <ProcessingTypeCode>' . htmlspecialchars($processingTypeCode) . '</ProcessingTypeCode>
                <BuyerID schemeAgencyID="" schemeAgencySchemeAgencyID="" schemeID=""/>
                <Name languageCode="' . htmlspecialchars($nameLanguage) . '">' . htmlspecialchars($name) . '</Name>
                <DocumentLanguageCode>' . htmlspecialchars($documentLanguageCode) . '</DocumentLanguageCode>
                <BuyerParty contactPartyListCompleteTransmissionIndicator="">
                    <BusinessPartnerInternalID>' . htmlspecialchars($businessPartnerInternalID) . '</BusinessPartnerInternalID>
                </BuyerParty>
                <EmployeeResponsibleParty>
                    <EmployeeID>' . htmlspecialchars($employeeID) . '</EmployeeID>
                </EmployeeResponsibleParty>
                <SellerParty>
                    <OrganisationalCentreID>' . htmlspecialchars($sellerOrgCentreID) . '</OrganisationalCentreID>
                </SellerParty>
                <SalesUnitParty>
                    <OrganisationalCentreID>' . htmlspecialchars($salesUnitOrgCentreID) . '</OrganisationalCentreID>
                </SalesUnitParty>
                <SalesAndServiceBusinessArea>
                    <SalesOrganisationID>' . htmlspecialchars($salesOrganisationID) . '</SalesOrganisationID>
                    <SalesOfficeID>' . htmlspecialchars($salesOfficeID) . '</SalesOfficeID>
                    <SalesGroupID>' . htmlspecialchars($salesGroupID) . '</SalesGroupID>
                    <DistributionChannelCode listAgencyID="" listAgencySchemeAgencyID="" listAgencySchemeID="" listID="" listVersionID="">' . htmlspecialchars($distributionChannelCode) . '</DistributionChannelCode>
                    <DivisionCode listAgencyID="" listAgencySchemeAgencyID="" listAgencySchemeID="" listID="" listVersionID="">' . htmlspecialchars($divisionCode) . '</DivisionCode>
                </SalesAndServiceBusinessArea>
                <Item actionCode="' . htmlspecialchars($itemActionCode) . '" itemBTDReferenceListCompleteTransmissionIndicator="" textListCompleteTransimissionIndicator="">
                    <ProcessingTypeCode>' . htmlspecialchars($itemProcessingTypeCode) . '</ProcessingTypeCode>
                    <ItemProduct>
                        <ProductID schemeAgencyID="" schemeAgencySchemeAgencyID="" schemeAgencySchemeID="" schemeID="">' . htmlspecialchars($productID) . '</ProductID>
                        <ProductInternalID schemeAgencyID="" schemeID="">' . htmlspecialchars($productInternalID) . '</ProductInternalID>
                    </ItemProduct>
                    <ItemRequestedScheduleLine>
                        <Quantity unitCode="' . htmlspecialchars($unitCode) . '">' . htmlspecialchars($quantity) . '</Quantity>
                    </ItemRequestedScheduleLine>
                    <y6s:zOVPosIDTipoPosicion listID="?" listVersionID="?" listAgencyID="?">' . htmlspecialchars($zOVPosIDTipoPosicion) . '</y6s:zOVPosIDTipoPosicion>
                    <y6s:zOVPosTipServ listID="?" listVersionID="" listAgencyID="">' . htmlspecialchars($zOVPosTipServ) . '</y6s:zOVPosTipServ>
                    <y6s:zOVPosCantTrab>' . htmlspecialchars($zOVPosCantTrab) . '</y6s:zOVPosCantTrab>
                    <y6s:zID_PAQUETE>' . htmlspecialchars($zID_PAQUETE) . '</y6s:zID_PAQUETE>
                    <y6s:zTIPO_PAQUETE>' . htmlspecialchars($zTIPO_PAQUETE) . '</y6s:zTIPO_PAQUETE>
                    <y6s:zOVPosTiempoTeorico>' . htmlspecialchars($zOVPosTiempoTeorico) . '</y6s:zOVPosTiempoTeorico>
                </Item>
                <BusinessTransactionDocumentReference actionCode="' . htmlspecialchars($btdrActionCode) . '">
                    <UUID schemeAgencyID="" schemeID="">' . htmlspecialchars($uuid) . '</UUID>
                    <TypeCode listAgencyID="" listID="" listVersionID="">' . htmlspecialchars($typeCode) . '</TypeCode>
                    <RoleCode>' . htmlspecialchars($roleCode) . '</RoleCode>
                </BusinessTransactionDocumentReference>
                <Text actionCode="' . htmlspecialchars($textActionCode) . '">
                    <TextTypeCode listAgencyID="" listAgencySchemeAgencyID="" listAgencySchemeID="" listID="" listVersionID="">' . htmlspecialchars($textTypeCode) . '</TextTypeCode>
                    <ContentText>' . htmlspecialchars($contentText) . '</ContentText>
                </Text>
                <y6s:zOVGrupoVendedores>' . htmlspecialchars($zOVGrupoVendedores) . '</y6s:zOVGrupoVendedores>
                <y6s:zOVIDCentro>' . htmlspecialchars($zOVIDCentro) . '</y6s:zOVIDCentro>
                <y6s:zOVPlaca>' . htmlspecialchars($zOVPlaca) . '</y6s:zOVPlaca>
                <y6s:zOVVieneDeHCI>' . htmlspecialchars($zOVVieneDeHCI) . '</y6s:zOVVieneDeHCI>
                <y6s:zOVServExpress>' . htmlspecialchars($zOVServExpress) . '</y6s:zOVServExpress>
                <y6s:zOVKilometraje>' . htmlspecialchars($zOVKilometraje) . '</y6s:zOVKilometraje>
                <y6s:zOVOrdenDBMV3>' . htmlspecialchars($zOVOrdenDBMV3) . '</y6s:zOVOrdenDBMV3>
            </CustomerQuote>
        </glob:CustomerQuoteBundleMaintainRequest_sync_V1>
    </soap:Body>
</soap:Envelope>';
    }

    /**
     * ✅ NUEVO: Build SOAP body for offer creation (SIMPLIFICADO como Postman)
     */
    private static function buildOfferCreateSoapBodySimple(array $params): string
    {
        $customerQuote = $params['CustomerQuote'] ?? [];

        // ✅ EXTRAER TODOS LOS DATOS NECESARIOS DEL ARRAY DE PARÁMETROS
        $businessPartnerID = $customerQuote['BuyerParty']['BusinessPartnerInternalID'] ?? '1200191766';
        $employeeID = $customerQuote['EmployeeResponsibleParty']['EmployeeID'] ?? '8000000010';

        // Datos organizacionales
        $salesOrgID = $customerQuote['SalesAndServiceBusinessArea']['SalesOrganisationID'] ?? 'DM07';
        $salesOfficeID = $customerQuote['SalesAndServiceBusinessArea']['SalesOfficeID'] ?? 'OVDM01';
        $salesGroupID = $customerQuote['SalesAndServiceBusinessArea']['SalesGroupID'] ?? 'D03';
        $distributionChannel = $customerQuote['SalesAndServiceBusinessArea']['DistributionChannelCode']['_'] ?? 'D4';
        $divisionCode = $customerQuote['SalesAndServiceBusinessArea']['DivisionCode']['_'] ?? 'D2';

        // Datos de la cita
        $uuid = $customerQuote['BusinessTransactionDocumentReference']['UUID']['_'] ?? '';
        $comments = $customerQuote['Text']['ContentText'] ?? '';
        $centerCode = $customerQuote['y6s:zOVIDCentro'] ?? 'M013';
        $placa = $customerQuote['y6s:zOVPlaca'] ?? '';
        $kilometraje = $customerQuote['y6s:zOVKilometraje'] ?? '0';
        $serviceExpress = $customerQuote['y6s:zOVServExpress'] ?? 'false';

        // ✅ GENERAR ITEMS DE PRODUCTOS (TODOS LOS PRODUCTOS)
        $items = $customerQuote['Item'] ?? [];
        $itemsXml = '';

        if (is_array($items)) {
            foreach ($items as $item) {
                $productID = $item['ItemProduct']['ProductID']['_'] ?? '';
                $rawQuantity = $item['ItemRequestedScheduleLine']['Quantity']['_'] ?? '1.0';
                $rawUnitCode = $item['ItemRequestedScheduleLine']['Quantity']['unitCode'] ?? 'EA';

                // ✅ USAR VALORES EXACTOS SIN REDONDEAR
                // Si quantity es 0 o vacío, usar 1.0 por defecto
                $quantity = (empty($rawQuantity) || floatval($rawQuantity) <= 0) ? '1.0' : (string)floatval($rawQuantity);

                // Si unitCode está vacío, usar EA por defecto
                $unitCode = empty($rawUnitCode) ? 'EA' : $rawUnitCode;
                $positionType = $item['y6s:zOVPosIDTipoPosicion']['_'] ?? 'P009';
                $packageId = $item['y6s:zID_PAQUETE'] ?? '';
                $workTime = $item['y6s:zOVPosTiempoTeorico'] ?? '0';

                $itemsXml .= '
                <Item actionCode="01" itemBTDReferenceListCompleteTransmissionIndicator="" textListCompleteTransimissionIndicator="">
                    <ProcessingTypeCode>AGN</ProcessingTypeCode>
                    <ItemProduct>
                        <ProductID schemeAgencyID="" schemeAgencySchemeAgencyID="" schemeAgencySchemeID="" schemeID="">' . htmlspecialchars($productID) . '</ProductID>
                        <ProductInternalID schemeAgencyID="" schemeID="">' . htmlspecialchars($productID) . '</ProductInternalID>
                    </ItemProduct>
                    <ItemRequestedScheduleLine>
                        <Quantity unitCode="' . htmlspecialchars($unitCode) . '">' . htmlspecialchars($quantity) . '</Quantity>
                    </ItemRequestedScheduleLine>
                    <y6s:zOVPosIDTipoPosicion listID="?" listVersionID="?" listAgencyID="?">' . htmlspecialchars($positionType) . '</y6s:zOVPosIDTipoPosicion>
                    <y6s:zOVPosTipServ listID="?" listVersionID="" listAgencyID="">P</y6s:zOVPosTipServ>
                    <y6s:zOVPosCantTrab>0</y6s:zOVPosCantTrab>';

                // ✅ SOLO INCLUIR CAMPOS DE PAQUETE SI TIENE PACKAGE_ID (OMITIR PARA WILDCARD)
                if (!empty($packageId)) {
                    $itemsXml .= '
                    <y6s:zID_PAQUETE>' . htmlspecialchars($packageId) . '</y6s:zID_PAQUETE>
                    <y6s:zTIPO_PAQUETE>Z1</y6s:zTIPO_PAQUETE>';
                }

                $itemsXml .= '
                    <y6s:zOVPosTiempoTeorico>' . htmlspecialchars($workTime) . '</y6s:zOVPosTiempoTeorico>
                </Item>';
            }
        }

        // ✅ ESTRUCTURA COMPLETA SIN DECLARACIÓN XML (como Postman)
        return '<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/" xmlns:glob="http://sap.com/xi/SAPGlobal20/Global" xmlns:y6s="http://0002961282-one-off.sap.com/Y6SAJ0KGY_" xmlns:a25="http://sap.com/xi/AP/CustomerExtension/BYD/A252F">
    <soap:Header/>
    <soap:Body>
        <glob:CustomerQuoteBundleMaintainRequest_sync_V1>
            <CustomerQuote ViewObjectIndicator="" actionCode="01" approverPartyListCompleteTransmissionIndicator="" businessTransactionDocumentReferenceListCompleteTransmissionIndicator="" competitorPartyListCompleteTransmissionIndicator="" itemListCompleteTransmissionIndicator="" otherPartyListCompleteTransmissionIndicator="" salesEmployeePartyListCompleteTransmissionIndicator="" salesPartnerListCompleteTransmissionIndicator="" textListCompleteTransimissionIndicator="">
                <ProcessingTypeCode>Z300</ProcessingTypeCode>
                <BuyerID schemeAgencyID="" schemeAgencySchemeAgencyID="" schemeID=""/>
                <Name languageCode="ES">OFERTA</Name>
                <DocumentLanguageCode>ES</DocumentLanguageCode>
                <BuyerParty contactPartyListCompleteTransmissionIndicator="">
                    <BusinessPartnerInternalID>' . htmlspecialchars($businessPartnerID) . '</BusinessPartnerInternalID>
                </BuyerParty>
                <EmployeeResponsibleParty>
                    <EmployeeID>' . htmlspecialchars($employeeID) . '</EmployeeID>
                </EmployeeResponsibleParty>
                <SellerParty>
                    <OrganisationalCentreID>GMIT</OrganisationalCentreID>
                </SellerParty>
                <SalesUnitParty>
                    <OrganisationalCentreID>' . htmlspecialchars($salesOrgID) . '</OrganisationalCentreID>
                </SalesUnitParty>
                <SalesAndServiceBusinessArea>
                    <SalesOrganisationID>' . htmlspecialchars($salesOrgID) . '</SalesOrganisationID>
                    <SalesOfficeID>' . htmlspecialchars($salesOfficeID) . '</SalesOfficeID>
                    <SalesGroupID>' . htmlspecialchars($salesGroupID) . '</SalesGroupID>
                    <DistributionChannelCode listAgencyID="" listAgencySchemeAgencyID="" listAgencySchemeID="" listID="" listVersionID="">' . htmlspecialchars($distributionChannel) . '</DistributionChannelCode>
                    <DivisionCode listAgencyID="" listAgencySchemeAgencyID="" listAgencySchemeID="" listID="" listVersionID="">' . htmlspecialchars($divisionCode) . '</DivisionCode>
                </SalesAndServiceBusinessArea>' . $itemsXml . '
                <BusinessTransactionDocumentReference actionCode="01">
                    <UUID schemeAgencyID="" schemeID="">' . htmlspecialchars($uuid) . '</UUID>
                    <TypeCode listAgencyID="" listID="" listVersionID="">12</TypeCode>
                    <RoleCode>1</RoleCode>
                </BusinessTransactionDocumentReference>
                <Text actionCode="01">
                    <TextTypeCode listAgencyID="" listAgencySchemeAgencyID="" listAgencySchemeID="" listID="" listVersionID="">10024</TextTypeCode>
                    <ContentText>' . htmlspecialchars($comments) . '</ContentText>
                </Text>
                <y6s:zOVGrupoVendedores>' . htmlspecialchars($salesGroupID) . '</y6s:zOVGrupoVendedores>
                <y6s:zOVIDCentro>' . htmlspecialchars($centerCode) . '</y6s:zOVIDCentro>
                <y6s:zOVPlaca>' . htmlspecialchars($placa) . '</y6s:zOVPlaca>
                <y6s:zOVVieneDeHCI>X</y6s:zOVVieneDeHCI>
                <y6s:zOVServExpress>' . htmlspecialchars($serviceExpress) . '</y6s:zOVServExpress>
                <y6s:zOVKilometraje>' . htmlspecialchars($kilometraje) . '</y6s:zOVKilometraje>
                <y6s:zOVOrdenDBMV3>3000694890</y6s:zOVOrdenDBMV3>
            </CustomerQuote>
        </glob:CustomerQuoteBundleMaintainRequest_sync_V1>
    </soap:Body>
</soap:Envelope>';
    }

    /**
     * ✅ EXTRAER UUID de respuesta de cita
     */
    private static function extractUuidFromAppointmentResponse($response): ?string
    {
        try {
            // Buscar en diferentes estructuras posibles
            if (isset($response->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1->AppointmentActivity->UUID)) {
                return (string) $response->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1->AppointmentActivity->UUID;
            }

            if (isset($response->Body->AppointmentActivity->UUID)) {
                return (string) $response->Body->AppointmentActivity->UUID;
            }

            // Buscar UUID en cualquier parte de la respuesta
            $responseArray = json_decode(json_encode($response), true);
            return self::findUuidInArray($responseArray);
        } catch (\Exception $e) {
            Log::warning('Error extrayendo UUID de respuesta de cita', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ✅ EXTRAER ID de respuesta de cita
     */
    private static function extractAppointmentIdFromResponse($response): ?string
    {
        try {
            if (isset($response->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1->AppointmentActivity->ID)) {
                return (string) $response->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1->AppointmentActivity->ID;
            }

            if (isset($response->Body->AppointmentActivity->ID)) {
                return (string) $response->Body->AppointmentActivity->ID;
            }

            return null;
        } catch (\Exception $e) {
            Log::warning('Error extrayendo ID de respuesta de cita', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * ✅ BUSCAR UUID en array recursivamente
     */
    private static function findUuidInArray(array $data): ?string
    {
        foreach ($data as $key => $value) {
            if (is_string($value) && preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $value)) {
                return $value;
            }
            if (is_array($value)) {
                $found = self::findUuidInArray($value);
                if ($found) return $found;
            }
        }
        return null;
    }
}
