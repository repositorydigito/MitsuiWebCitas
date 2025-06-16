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
                'connection_timeout' => config('c4c.timeout', 120),
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
                        'timeout' => config('c4c.timeout', 120),
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
                    'timeout' => config('c4c.timeout', 120),
                ],
            ]));

            if ($wsdlContent === false) {
                Log::error('No se pudo acceder al WSDL remoto: '.$wsdl);
                Log::error('Error: '.error_get_last()['message']);

                // FORZAR conexión remota como Python - NO usar WSDL local
                Log::warning('Intentando conexión directa al servicio remoto sin validar WSDL...');

                // Intentar crear cliente SOAP directamente con la URL remota
                try {
                    return new SoapClient($wsdl, $options);
                } catch (\Exception $e) {
                    Log::error('Error al conectar directamente al servicio remoto: '.$e->getMessage());

                    return null;
                }
            }

            return new SoapClient($wsdl, $options);
        } catch (SoapFault $e) {
            Log::error('C4C SOAP Client Error: '.$e->getMessage(), [
                'wsdl' => $wsdl,
                'code' => $e->getCode(),
            ]);

            // NO usar WSDL local - forzar conexión remota como Python
            Log::error('Fallo al conectar al servicio remoto C4C - NO se usará WSDL local');

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
     * Execute a SOAP call using HTTP requests like Python.
     *
     * @return array
     */
    public static function call(string $wsdl, string $method, array $params)
    {
        // Intentar primero con SoapClient tradicional
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
                Log::error('C4C SOAP Call Error (SoapClient): '.$e->getMessage());
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

            // Headers como Python
            $headers = [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: ""',
                'Authorization: Basic '.base64_encode(config('c4c.auth.username').':'.config('c4c.auth.password')),
                'User-Agent: Laravel-C4C-Client/1.0',
            ];

            Log::info('Enviando HTTP SOAP request como Python', [
                'url' => $url,
                'headers' => $headers,
                'body_preview' => substr($soapBody, 0, 200).'...',
            ]);

            // Configurar timeout específico para citas (más tiempo)
            $timeout = config('c4c.timeout', 120); // Default aumentado
            if (strpos($wsdl, 'manageappointmentactivityin1') !== false ||
                strpos($wsdl, 'yy6saj0kgy_wscitas') !== false) {
                $timeout = 180; // 3 minutos para operaciones de citas
                Log::info("⏱️ Usando timeout extendido para operaciones de citas: {$timeout}s");
            }

            // Hacer request HTTP con cURL
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $soapBody,
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
                Log::error('cURL Error: '.$error);

                return [
                    'success' => false,
                    'error' => 'cURL Error: '.$error,
                    'data' => null,
                ];
            }

            Log::info('HTTP SOAP Response recibida', [
                'http_code' => $httpCode,
                'response_length' => strlen($response),
                'response_preview' => substr($response, 0, 500).'...',
            ]);

            if ($httpCode === 200) {
                // Parsear respuesta XML como lo haría SoapClient
                $parsedResponse = self::parseXmlResponse($response);

                return [
                    'success' => true,
                    'error' => null,
                    'data' => $parsedResponse,
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'HTTP Error: '.$httpCode,
                    'data' => null,
                ];
            }

        } catch (\Exception $e) {
            Log::error('Error en HTTP SOAP request: '.$e->getMessage());

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
                <'.$searchField.'>
                    <SelectionByText>
                        <InclusionExclusionCode>I</InclusionExclusionCode>
                        <IntervalBoundaryTypeCode>1</IntervalBoundaryTypeCode>
                        <LowerBoundaryName>'.htmlspecialchars($searchValue).'</LowerBoundaryName>
                        <UpperBoundaryName/>
                    </SelectionByText>
                </'.$searchField.'>
            </CustomerSelectionByElements>
            <ProcessingConditions>
                <QueryHitsMaximumNumberValue>'.$maxResults.'</QueryHitsMaximumNumberValue>
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
        <'.$method.'/>
    </soapenv:Body>
</soapenv:Envelope>';
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
                'xml_preview' => substr($xmlResponse, 0, 300).'...',
            ]);

            // Limpiar namespaces para simplificar el parsing
            $cleanXml = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $xmlResponse);
            $cleanXml = preg_replace('/[a-zA-Z0-9\-]*:/', '', $cleanXml);

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
            Log::error('Error al parsear respuesta XML: '.$e->getMessage());

            return (object) [];
        }
    }

    /**
     * Build SOAP body for appointment management operations.
     */
    private static function buildAppointmentManagementSoapBody(array $params): string
    {
        $appointment = $params['AppointmentActivity'] ?? [];
        $actionCode = $appointment['actionCode'] ?? '01';

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
        $licensePlate = $appointment['y6s:zPlaca'] ?? 'TEST-123';
        $appointmentStatus = $appointment['y6s:zEstadoCita'] ?? '1';
        $isExpress = $appointment['y6s:zExpress'] ?? 'false';

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
            <AppointmentActivity actionCode="'.htmlspecialchars($actionCode).'">';

        // Para UPDATE y DELETE, incluir UUID
        if (! empty($uuid) && ($actionCode === '04' || $actionCode === '06')) {
            $xmlBody .= '
                <UUID>'.htmlspecialchars($uuid).'</UUID>';
        }

        // Campos básicos (siempre incluidos para CREATE, opcionales para UPDATE/DELETE)
        if ($actionCode === '01' || ($actionCode === '04' && ! empty($businessPartnerId))) {
            $xmlBody .= '
                <DocumentTypeCode>0001</DocumentTypeCode>
                <LifeCycleStatusCode>1</LifeCycleStatusCode>
                <MainActivityParty>
                    <BusinessPartnerInternalID>'.htmlspecialchars($businessPartnerId).'</BusinessPartnerInternalID>
                </MainActivityParty>
                <AttendeeParty>
                    <EmployeeID>'.htmlspecialchars($employeeId).'</EmployeeID>
                </AttendeeParty>
                <StartDateTime timeZoneCode="UTC-5">'.htmlspecialchars($startDateTime).'</StartDateTime>
                <EndDateTime timeZoneCode="UTC-5">'.htmlspecialchars($endDateTime).'</EndDateTime>
                <Text actionCode="'.htmlspecialchars($actionCode).'">
                    <TextTypeCode>10002</TextTypeCode>
                    <ContentText>'.htmlspecialchars($observation).'</ContentText>
                </Text>';
        }

        // Campos personalizados (para CREATE y UPDATE)
        if ($actionCode === '01' || $actionCode === '04') {
            $xmlBody .= '
                <y6s:zClienteComodin>'.htmlspecialchars($clientName).'</y6s:zClienteComodin>
                <y6s:zFechaHoraProbSalida>'.htmlspecialchars($exitDate).'</y6s:zFechaHoraProbSalida>
                <y6s:zHoraProbSalida>'.htmlspecialchars($exitTime).'</y6s:zHoraProbSalida>
                <y6s:zIDCentro>'.htmlspecialchars($centerId).'</y6s:zIDCentro>
                <y6s:zPlaca>'.htmlspecialchars($licensePlate).'</y6s:zPlaca>
                <y6s:zEstadoCita>'.htmlspecialchars($appointmentStatus).'</y6s:zEstadoCita>
                <y6s:zVieneHCP>X</y6s:zVieneHCP>
                <y6s:zExpress>'.htmlspecialchars($isExpress).'</y6s:zExpress>';
        }

        // Para DELETE, solo campos mínimos
        if ($actionCode === '06') {
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
                    <LowerBoundaryTypeCode>'.htmlspecialchars($typeCode).'</LowerBoundaryTypeCode>
                </SelectionByTypeCode>
                <SelectionByPartyID>
                    <InclusionExclusionCode>I</InclusionExclusionCode>
                    <IntervalBoundaryTypeCode>1</IntervalBoundaryTypeCode>
                    <LowerBoundaryPartyID>'.htmlspecialchars($partyId).'</LowerBoundaryPartyID>
                    <UpperBoundaryPartyID/>
                </SelectionByPartyID>
                <SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5>
                    <InclusionExclusionCode>I</InclusionExclusionCode>
                    <IntervalBoundaryTypeCode>3</IntervalBoundaryTypeCode>
                    <LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5>'.htmlspecialchars($lowerStatus).'</LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5>
                    <UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5>'.htmlspecialchars($upperStatus).'</UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5>
                </SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5>
            </ActivitySimpleSelectionBy>
            <ProcessingConditions>
                <QueryHitsMaximumNumberValue>'.htmlspecialchars($maxResults).'</QueryHitsMaximumNumberValue>
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
               <BusinessPartnerInternalID>'.htmlspecialchars($businessPartnerId).'</BusinessPartnerInternalID>
            </MainActivityParty>
            <AttendeeParty>
               <EmployeeID>'.htmlspecialchars($employeeId).'</EmployeeID>
            </AttendeeParty>
            <StartDateTime>'.htmlspecialchars($startDateTime).'</StartDateTime>
            <EndDateTime>'.htmlspecialchars($endDateTime).'</EndDateTime>
            <Text>'.htmlspecialchars($text).'</Text>
            <zPlaca>'.htmlspecialchars($licensePlate).'</zPlaca>
            <zIDCentro>'.htmlspecialchars($centerId).'</zIDCentro>
            <zEstadoCita>'.htmlspecialchars($appointmentStatus).'</zEstadoCita>
            <zFechaHoraProbSalida>'.htmlspecialchars($exitDate).'</zFechaHoraProbSalida>
            <zHoraProbSalida>'.htmlspecialchars($exitTime).'</zHoraProbSalida>
            <zNombresConductor>'.htmlspecialchars($driverName).'</zNombresConductor>
            <zTelefonoCliente>'.htmlspecialchars($clientPhone).'</zTelefonoCliente>
            <zVIN>'.htmlspecialchars($vin).'</zVIN>
            <zModeloVeh>'.htmlspecialchars($vehicleModel).'</zModeloVeh>
            <zDesModeloVeh>'.htmlspecialchars($vehicleDescription).'</zDesModeloVeh>
            <zKilometrajeVeh>'.htmlspecialchars($mileage).'</zKilometrajeVeh>
            <zAnnioVeh>'.htmlspecialchars($vehicleYear).'</zAnnioVeh>
            <zColorVeh>'.htmlspecialchars($vehicleColor).'</zColorVeh>
            <zSolicitarTaxi>'.htmlspecialchars($requestTaxi).'</zSolicitarTaxi>
         </Appointment>
      </glob:AppointmentCreateRequest_sync>
   </soapenv:Body>
</soapenv:Envelope>';
    }
}
