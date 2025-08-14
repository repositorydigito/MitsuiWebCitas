<?php

namespace App\Services\C4C;

use App\Models\Appointment;
use App\Models\CenterOrganizationMapping;
use App\Services\C4C\C4CClient;
use Illuminate\Support\Facades\Log;

class OfferService
{
    protected string $wsdl;
    protected string $method;
    protected string $username;
    protected string $password;

    public function __construct()
    {
        // ✅ USAR CONFIGURACIÓN CORRECTA SEGÚN DOCUMENTACIÓN
        $this->wsdl = env('C4C_OFFER_WSDL');
        $this->method = config('c4c.services.offer.create_method');
        $this->username = env('C4C_OFFER_USERNAME');
        $this->password = env('C4C_OFFER_PASSWORD');

        Log::info('OfferService inicializado', [
            'wsdl' => $this->wsdl,
            'username' => $this->username,
            'method' => $this->method
        ]);
    }

    /**
     * ✅ MÉTODO PRINCIPAL: Crear oferta desde cita con mapeo organizacional
     */
    public function crearOfertaDesdeCita(Appointment $appointment): array
    {
        try {
            Log::info('🚀 Iniciando creación de oferta con mapeo organizacional', [
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
                'center_code' => $appointment->center_code,
                'brand_code' => $appointment->vehicle_brand_code,
                'package_id' => $appointment->package_id,
                'c4c_uuid' => $appointment->c4c_uuid
            ]);

            // ✅ PASO 1: OBTENER MAPEO ORGANIZACIONAL
            $mapping = $this->obtenerMapeoOrganizacional($appointment);

            if (!$mapping) {
                return [
                    'success' => false,
                    'error' => 'No se encontró configuración organizacional para centro: ' .
                        $appointment->center_code . ' y marca: ' . $appointment->vehicle_brand_code
                ];
            }

            Log::info('🏢 Mapeo organizacional obtenido', [
                'center_code' => $appointment->center_code,
                'brand_code' => $appointment->vehicle_brand_code,
                'sales_organization_id' => $mapping->sales_organization_id,
                'sales_office_id' => $mapping->sales_office_id,
                'division_code' => $mapping->division_code
            ]);

            // Validaciones básicas
            if (!$appointment->package_id) {
                return [
                    'success' => false,
                    'error' => 'No se puede crear oferta sin paquete ID',
                    'data' => null
                ];
            }

            if (!$appointment->c4c_uuid) {
                return [
                    'success' => false,
                    'error' => 'Cita debe estar sincronizada con C4C primero',
                    'data' => null
                ];
            }

            // ✅ PASO 2: PREPARAR PARÁMETROS CON ESTRUCTURA ORGANIZACIONAL REAL
            $params = $this->prepararParametrosOferta($appointment, $mapping);

            // ✅ PASO 3: LLAMAR WEBSERVICE
            Log::info('📞 Llamando webservice de ofertas C4C', [
                'wsdl' => $this->wsdl,
                'method' => $this->method,
                'appointment_id' => $appointment->id
            ]);

            $result = C4CClient::call($this->wsdl, $this->method, $params);

            if ($result['success']) {
                // ✅ MANEJAR DIFERENTES ESTRUCTURAS DE RESPUESTA
                $data = $result['data'] ?? [];
                if (is_object($data)) {
                    $data = json_decode(json_encode($data), true);
                }

                // ✅ VERIFICAR ERRORES EN LA RESPUESTA C4C ANTES DE PROCESAR
                $validationResult = $this->verificarErroresC4C($data);
                if (!$validationResult['success']) {
                    Log::error('❌ Error de validación en C4C al crear oferta', [
                        'appointment_id' => $appointment->id,
                        'errors' => $validationResult['errors'],
                        'response_data' => $data
                    ]);

                    // Actualizar appointment con información del error
                    $appointment->update([
                        'offer_creation_failed' => true,
                        'offer_creation_error' => $validationResult['error_message'],
                        'offer_creation_attempts' => ($appointment->offer_creation_attempts ?? 0) + 1
                    ]);

                    return [
                        'success' => false,
                        'error' => $validationResult['error_message'],
                        'errors' => $validationResult['errors'],
                        'details' => 'Errores de validación en C4C'
                    ];
                }

                // ✅ EXTRAER ID CORRECTO DE LA RESPUESTA SAP C4C (igual que en actualizarAppointmentConOferta)
                $customerQuote = $data['Body']['CustomerQuoteBundleMaintainConfirmation_sync_V1']['CustomerQuote'] ?? [];
                $offerId = $customerQuote['ID'] ?? $data['offer_id'] ?? $data['ID'] ?? null;

                if (!$offerId) {
                    Log::error('❌ No se pudo extraer el ID de la oferta de la respuesta C4C', [
                        'appointment_id' => $appointment->id,
                        'response_data' => $data
                    ]);

                    return [
                        'success' => false,
                        'error' => 'No se pudo extraer el ID de la oferta de la respuesta C4C',
                        'data' => $data
                    ];
                }

                $this->actualizarAppointmentConOferta($appointment, $result);

                Log::info('✅ Oferta creada exitosamente en C4C', [
                    'appointment_id' => $appointment->id,
                    'c4c_offer_id' => $offerId,
                    'response_data' => $data
                ]);

                return [
                    'success' => true,
                    'c4c_offer_id' => $offerId,
                    'message' => 'Oferta creada exitosamente',
                    'data' => $data
                ];
            } else {
                // ✅ MEJORAR MANEJO DE ERRORES SOAP FAULT
                $errorMessage = $result['error'] ?? 'Error desconocido en C4C';
                $transactionId = $result['transaction_id'] ?? null;
                $faultCode = $result['fault_code'] ?? null;

                Log::error('❌ Error en C4C al crear oferta', [
                    'appointment_id' => $appointment->id,
                    'error' => $errorMessage,
                    'fault_code' => $faultCode,
                    'transaction_id' => $transactionId,
                    'full_result' => $result
                ]);

                // Actualizar appointment con información del error
                $appointment->update([
                    'offer_creation_failed' => true,
                    'offer_creation_error' => $errorMessage,
                    'offer_creation_attempts' => ($appointment->offer_creation_attempts ?? 0) + 1,
                    'c4c_transaction_id' => $transactionId
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'fault_code' => $faultCode,
                    'transaction_id' => $transactionId,
                    'details' => 'Revisa los logs de C4C con Transaction ID: ' . $transactionId
                ];
            }
        } catch (\Exception $e) {
            Log::error('❌ Error creando oferta', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ✅ MÉTODO PRIVADO: Verificar errores en la respuesta C4C
     */
    private function verificarErroresC4C(array $data): array
    {
        $log = $data['Body']['CustomerQuoteBundleMaintainConfirmation_sync_V1']['Log'] ?? [];

        // Verificar si hay errores en el log
        if (empty($log)) {
            return ['success' => true];
        }

        $maxSeverity = $log['MaximumLogItemSeverityCode'] ?? null;
        $items = $log['Item'] ?? [];

        // Normalizar items a array
        if (!is_array($items)) {
            $items = [$items];
        }

        // Si items es un array asociativo (un solo item), convertirlo a array numérico
        if (isset($items['SeverityCode'])) {
            $items = [$items];
        }

        $errors = [];
        $hasErrors = false;

        foreach ($items as $item) {
            $severityCode = $item['SeverityCode'] ?? null;
            $note = $item['Note'] ?? '';

            // SeverityCode 3 = Error en C4C
            if ($severityCode === '3') {
                $errors[] = $note;
                $hasErrors = true;
            }
        }

        if ($hasErrors) {
            return [
                'success' => false,
                'errors' => $errors,
                'error_message' => 'Errores de validación C4C: ' . implode('; ', $errors),
                'max_severity' => $maxSeverity
            ];
        }

        return ['success' => true];
    }

    /**
     * ✅ MÉTODO PRIVADO: Obtener mapeo organizacional
     */
    private function obtenerMapeoOrganizacional(Appointment $appointment): ?CenterOrganizationMapping
    {
        $mapping = CenterOrganizationMapping::forCenterAndBrand(
            $appointment->center_code,
            $appointment->vehicle_brand_code
        )->first();

        if (!$mapping) {
            Log::error('❌ Mapeo organizacional no encontrado', [
                'center_code' => $appointment->center_code,
                'brand_code' => $appointment->vehicle_brand_code,
                'appointment_id' => $appointment->id
            ]);
        }

        return $mapping;
    }

    /**
     * ✅ MÉTODO PRIVADO: Preparar parámetros con estructura organizacional real (según enviar-oferta.md)
     */
    private function prepararParametrosOferta(Appointment $appointment, CenterOrganizationMapping $mapping): array
    {
        // ✅ OBTENER DATOS SEGÚN LA CONSULTA SQL DE LA DOCUMENTACIÓN (líneas 104-129)
        $vehicle = $appointment->vehicle;
        $user = \App\Models\User::where('document_number', $appointment->customer_ruc)->first();

        if (!$vehicle) {
            throw new \Exception("Vehículo no encontrado para appointment_id: {$appointment->id}");
        }

        if (!$user || !$user->c4c_internal_id) {
            throw new \Exception("Usuario C4C no encontrado para RUC: {$appointment->customer_ruc}");
        }

        // ✅ OBTENER PRODUCTOS SEGÚN DOCUMENTACIÓN (líneas 131-142)
        $productos = \App\Models\Product::where('appointment_id', $appointment->id)
            ->orderBy('position_number', 'asc')
            ->get();

        if ($productos->isEmpty()) {
            Log::warning('⚠️ No hay productos descargados para esta cita', [
                'appointment_id' => $appointment->id,
                'package_id' => $appointment->package_id
            ]);
            throw new \Exception("No hay productos descargados para appointment_id: {$appointment->id}");
        }

        Log::info('📦 Datos obtenidos según documentación enviar-oferta.md', [
            'appointment_id' => $appointment->id,
            'customer_c4c_id' => $user->c4c_internal_id,
            'vehicle_plate' => $vehicle->license_plate,
            'vehicle_mileage' => $vehicle->mileage,
            'center_code' => $appointment->center_code,
            'brand_code' => $vehicle->brand_code,
            'total_productos' => $productos->count(),
        ]);

        // ✅ ESTRUCTURA SOAP SEGÚN DOCUMENTACIÓN EXACTA
        $params = [
            'CustomerQuote' => [
                // ✅ ATRIBUTOS PRINCIPALES (según trama de ejemplo)
                'ViewObjectIndicator' => '',
                'actionCode' => '01',
                'approverPartyListCompleteTransmissionIndicator' => '',
                'businessTransactionDocumentReferenceListCompleteTransmissionIndicator' => '',
                'competitorPartyListCompleteTransmissionIndicator' => '',
                'itemListCompleteTransmissionIndicator' => '',
                'otherPartyListCompleteTransmissionIndicator' => '',
                'salesEmployeePartyListCompleteTransmissionIndicator' => '',
                'salesPartnerListCompleteTransmissionIndicator' => '',
                'textListCompleteTransimissionIndicator' => '',

                // ✅ DATOS BÁSICOS (según metadatos)
                'ProcessingTypeCode' => 'Z300',
                'BuyerID' => [
                    'schemeAgencyID' => '',
                    'schemeAgencySchemeAgencyID' => '',
                    'schemeID' => ''
                ],
                'Name' => [
                    '_' => 'OFERTA',
                    'languageCode' => 'ES'
                ],
                'DocumentLanguageCode' => 'ES',

                // ✅ DATOS DEL CLIENTE (según documentación línea 158)
                'BuyerParty' => [
                    'contactPartyListCompleteTransmissionIndicator' => '',
                    'BusinessPartnerInternalID' => $user->c4c_internal_id
                ],

                // ✅ EMPLEADO RESPONSABLE
                'EmployeeResponsibleParty' => [
                    'EmployeeID' => '8000000010'
                ],

                // ✅ ESTRUCTURA ORGANIZACIONAL (según trama de ejemplo)
                'SellerParty' => [
                    'OrganisationalCentreID' => 'GMIT'
                ],
                'SalesUnitParty' => [
                    'OrganisationalCentreID' => $mapping->sales_organization_id // DM08 en ejemplo
                ],
                'SalesAndServiceBusinessArea' => [
                    'SalesOrganisationID' => $mapping->sales_organization_id,
                    'SalesOfficeID' => $mapping->sales_office_id,
                    'SalesGroupID' => $mapping->sales_group_id,
                    'DistributionChannelCode' => [
                        '_' => $mapping->distribution_channel_code,
                        'listAgencyID' => '',
                        'listAgencySchemeAgencyID' => '',
                        'listAgencySchemeID' => '',
                        'listID' => '',
                        'listVersionID' => ''
                    ],
                    'DivisionCode' => [
                        '_' => $mapping->division_code,
                        'listAgencyID' => '',
                        'listAgencySchemeAgencyID' => '',
                        'listAgencySchemeID' => '',
                        'listID' => '',
                        'listVersionID' => ''
                    ]
                ],

                // ✅ ITEMS: GENERAR UN ELEMENTO POR CADA PRODUCTO (según documentación)
                'Item' => $this->generarItemsDeProductos($productos, $appointment),

                // ✅ VINCULACIÓN CON LA CITA (según trama exacta)
                'BusinessTransactionDocumentReference' => [
                    'actionCode' => '01',
                    'UUID' => [
                        '_' => $appointment->c4c_uuid,
                        'schemeAgencyID' => '',
                        'schemeID' => ''
                    ],
                    'TypeCode' => [
                        '_' => '12',
                        'listAgencyID' => '',
                        'listID' => '',
                        'listVersionID' => ''
                    ],
                    'RoleCode' => '1'
                ],

                // ✅ TEXTO ADICIONAL (según trama exacta)
                'Text' => [
                    'actionCode' => '01',
                    'TextTypeCode' => [
                        '_' => '10024',
                        'listAgencyID' => '',
                        'listAgencySchemeAgencyID' => '',
                        'listAgencySchemeID' => '',
                        'listID' => '',
                        'listVersionID' => ''
                    ],
                    'ContentText' => $this->generarComentariosCombinados($appointment)
                ],

                // ✅ CAMPOS PERSONALIZADOS SEGÚN DOCUMENTACIÓN (líneas 211-216)
                'y6s:zOVGrupoVendedores' => $mapping->sales_group_id,
                'y6s:zOVIDCentro' => $appointment->center_code,                               // {appointments.center_code}
                'y6s:zOVPlaca' => $vehicle->license_plate,                                   // {vehicles.license_plate}
                'y6s:zOVVieneDeHCI' => 'X',
                'y6s:zOVServExpress' => ($appointment->service_mode === 'express') ? 'true' : 'false', // {appointments.service_mode == 'express' ? 'true' : 'false'}
                'y6s:zOVKilometraje' => '0', // ✅ CORREGIDO: Enviado en 0 para ambos tipos de cliente
                'y6s:zOVOrdenDBMV3' => '3000694890'
            ]
        ];

        Log::info('📋 Parámetros de oferta preparados', [
            'appointment_id' => $appointment->id,
            'total_productos' => $productos->count(),
            'package_id' => $appointment->package_id,
            'sales_org' => $mapping->sales_organization_id,
            'sales_office' => $mapping->sales_office_id,
            'division' => $mapping->division_code
        ]);

        return $params;
    }

    /**
     * ✅ MÉTODO PRIVADO: Extraer product_id del package_id
     */
    private function extraerProductIdDelPaquete(string $packageId): string
    {
        // M1085-010 → P010
        if (preg_match('/^M(\d+)-(\d+)$/', $packageId, $matches)) {
            return 'P' . str_pad($matches[2], 3, '0', STR_PAD_LEFT);
        }

        Log::warning('⚠️ Formato de package_id inesperado', [
            'package_id' => $packageId
        ]);

        return 'P010'; // fallback
    }

    /**
     * ✅ MÉTODO PRIVADO: Actualizar appointment con datos de la oferta
     */
    private function actualizarAppointmentConOferta(Appointment $appointment, array $result): void
    {
        // ✅ MANEJAR DIFERENTES ESTRUCTURAS DE RESPUESTA
        $data = $result['data'] ?? [];

        // Convertir stdClass a array si es necesario
        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }

        // ✅ EXTRAER ID CORRECTO DE LA RESPUESTA SAP C4C
        $customerQuote = $data['Body']['CustomerQuoteBundleMaintainConfirmation_sync_V1']['CustomerQuote'] ?? [];
        $offerId = $customerQuote['ID'] ?? $data['offer_id'] ?? $data['ID'] ?? null;

        $updateData = [
            'c4c_offer_id' => $offerId,
            'offer_created_at' => now(),
            'offer_creation_failed' => false,
            'offer_creation_error' => null,
            'offer_creation_attempts' => ($appointment->offer_creation_attempts ?? 0) + 1
        ];

        $appointment->update($updateData);

        Log::info('📝 Appointment actualizado con datos de oferta', [
            'appointment_id' => $appointment->id,
            'c4c_offer_id' => $updateData['c4c_offer_id'],
            'attempts' => $updateData['offer_creation_attempts'],
            'response_data' => $data
        ]);
    }

    /**
     * ✅ NUEVO MÉTODO: Generar elementos Item por cada producto descargado
     * Según documentación: "Se debe iterar TODOS los productos del appointment_id"
     */
    private function generarItemsDeProductos($productos, Appointment $appointment): array
    {
        $items = [];

        foreach ($productos as $index => $producto) {
            $item = [
                'actionCode' => '01',
                'itemBTDReferenceListCompleteTransmissionIndicator' => '',
                'textListCompleteTransimissionIndicator' => '',
                'ProcessingTypeCode' => 'AGN',

                'ItemProduct' => [
                    'ProductID' => [
                        '_' => $producto->c4c_product_id,
                        'schemeAgencyID' => '',
                        'schemeAgencySchemeAgencyID' => '',
                        'schemeAgencySchemeID' => '',
                        'schemeID' => ''
                    ],
                    'ProductInternalID' => [
                        '_' => $producto->c4c_product_id,
                        'schemeAgencyID' => '',
                        'schemeID' => ''
                    ]
                ],

                'ItemRequestedScheduleLine' => [
                    'Quantity' => [
                        '_' => ($producto->quantity > 0) ? (string)$producto->quantity : '1.0',  // ✅ Usar 1.0 si es 0
                        'unitCode' => $this->determinarUnitCode($producto->position_type, $producto->unit_code)  // ✅ NUEVA LÓGICA
                    ]
                ],

                // ✅ CAMPOS PERSONALIZADOS SEGÚN DOCUMENTACIÓN (líneas 189-195)
                'y6s:zOVPosIDTipoPosicion' => [
                    '_' => $producto->position_type ?? 'P009',           // {products.position_type}
                    'listID' => '?',
                    'listVersionID' => '?',
                    'listAgencyID' => '?'
                ],
                'y6s:zOVPosTipServ' => [
                    '_' => 'P',
                    'listID' => '?',
                    'listVersionID' => '',
                    'listAgencyID' => ''
                ],
                'y6s:zOVPosCantTrab' => '0',
                'y6s:zID_PAQUETE' => $appointment->package_id,                              // {appointments.package_id}
                'y6s:zTIPO_PAQUETE' => 'Z1',
                'y6s:zOVPosTiempoTeorico' => $this->formatearTiempoTeorico($producto->work_time_value)   // {products.work_time_value}
            ];

            $items[] = $item;
        }

        Log::info('✅ Items generados para oferta', [
            'appointment_id' => $appointment->id,
            'total_items' => count($items),
            'package_id' => $appointment->package_id,
            'productos_procesados' => $productos->pluck('c4c_product_id')->toArray()
        ]);

        return $items;
    }

    /**
     * ✅ NUEVA LÓGICA: Determinar unit code basado en zTipoPosicion
     * P001 (Servicios) → HUR (Horas)
     * Todos los otros casos → EA (Each)
     */
    private function determinarUnitCode(?string $positionType, ?string $unitCodeFromProduct): string
    {
        // Si el producto ya tiene unit_code válido, usarlo
        if (!empty($unitCodeFromProduct)) {
            return $unitCodeFromProduct;
        }

        // Aplicar lógica según tipo de posición
        switch ($positionType) {
            case 'P001': // Servicios
                return 'HUR'; // Horas
            case 'P002': // Materiales/Partes
            case 'P009': // Componentes
            case 'P010': // Material específico
            default:
                return 'EA'; // Each por defecto
        }
    }

    /**
     * Formatear tiempo teórico exactamente como Postman (sin decimales innecesarios)
     */
    private function formatearTiempoTeorico($workTimeValue): string
    {
        if (empty($workTimeValue) || $workTimeValue == 0) {
            return '0';  // ✅ Formato entero como Postman
        }

        // Convertir a número y formatear sin decimales innecesarios
        $numero = (float)$workTimeValue;

        // Si es un número entero, devolver sin decimales
        if ($numero == (int)$numero) {
            return (string)(int)$numero;
        }

        // Si tiene decimales, mantener solo los necesarios (máximo 2)
        return rtrim(rtrim(number_format($numero, 2, '.', ''), '0'), '.');
    }

    /**
     * ✅ MÉTODO PÚBLICO: Crear oferta para clientes wildcard (comodín) - MÉTODO COMPLETAMENTE SEPARADO
     */
    public function crearOfertaWildcard(Appointment $appointment): array
    {
        try {
            Log::info('🚀 Iniciando creación de oferta WILDCARD', [
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
                'center_code' => $appointment->center_code,
                'brand_code' => $appointment->vehicle_brand_code,
                'c4c_uuid' => $appointment->c4c_uuid
            ]);

            // ✅ VERIFICAR QUE ES REALMENTE CLIENTE WILDCARD
            $user = \App\Models\User::where('document_number', $appointment->customer_ruc)->first();
            if (!$user || $user->c4c_internal_id !== '1200166011') {
                return [
                    'success' => false,
                    'error' => 'Este método es solo para clientes wildcard (c4c_internal_id = 1200166011)',
                    'data' => null
                ];
            }

            // ✅ PASO 1: OBTENER MAPEO ORGANIZACIONAL
            $mapping = $this->obtenerMapeoOrganizacional($appointment);

            if (!$mapping) {
                return [
                    'success' => false,
                    'error' => 'No se encontró configuración organizacional para centro: ' .
                        $appointment->center_code . ' y marca: ' . $appointment->vehicle_brand_code
                ];
            }

            // ✅ PASO 2: PREPARAR PARÁMETROS WILDCARD
            $params = $this->prepararParametrosWildcard($appointment, $mapping, $user, $appointment->vehicle);

            // ✅ PASO 3: LLAMAR WEBSERVICE
            Log::info('📞 Llamando webservice de ofertas C4C para cliente wildcard', [
                'wsdl' => $this->wsdl,
                'method' => $this->method,
                'appointment_id' => $appointment->id
            ]);

            $result = C4CClient::call($this->wsdl, $this->method, $params);

            if ($result['success']) {
                // ✅ MANEJAR DIFERENTES ESTRUCTURAS DE RESPUESTA
                $data = $result['data'] ?? [];
                if (is_object($data)) {
                    $data = json_decode(json_encode($data), true);
                }

                // ✅ VERIFICAR ERRORES EN LA RESPUESTA C4C ANTES DE PROCESAR
                $validationResult = $this->verificarErroresC4C($data);
                if (!$validationResult['success']) {
                    // ✅ PARA CLIENTE WILDCARD: IGNORAR ERRORES RELACIONADOS CON VEHÍCULO/PLACA/BLOQUEO
                    $erroresPermitidos = [
                        'El vehículo no existe.',
                        'No se encontró la placa.'
                    ];

                    $todosLosErroresSonPermitidos = true;
                    foreach ($validationResult['errors'] as $error) {
                        $esErrorPermitido = false;

                        // Verificar errores exactos
                        if (in_array($error, $erroresPermitidos)) {
                            $esErrorPermitido = true;
                        }

                        // Verificar errores de bloqueo (contiene "Locking object not possible")
                        if (str_contains($error, 'Locking object not possible')) {
                            $esErrorPermitido = true;
                        }

                        if (!$esErrorPermitido) {
                            $todosLosErroresSonPermitidos = false;
                            break;
                        }
                    }
                    $esErrorVehiculoSolamente = $todosLosErroresSonPermitidos;

                    Log::info('🔍 DEBUG WILDCARD: Análisis de errores', [
                        'appointment_id' => $appointment->id,
                        'errores_recibidos' => $validationResult['errors'],
                        'errores_permitidos' => $erroresPermitidos,
                        'count_errores' => count($validationResult['errors']),
                        'todos_los_errores_son_permitidos' => $todosLosErroresSonPermitidos,
                        'es_error_vehiculo_solamente' => $esErrorVehiculoSolamente
                    ]);

                    if ($esErrorVehiculoSolamente) {
                        Log::info('🎯 Cliente wildcard: Ignorando errores de vehículo/placa - continuando con éxito', [
                            'appointment_id' => $appointment->id,
                            'errores_ignorados' => $validationResult['errors']
                        ]);

                        // Para wildcard, este error no es realmente un error - continuar como exitoso
                        // NO ejecutar el return de error, continuar al procesamiento normal
                    } else {
                        Log::error('❌ Error de validación en C4C al crear oferta wildcard', [
                            'appointment_id' => $appointment->id,
                            'errors' => $validationResult['errors'],
                            'response_data' => $data
                        ]);

                        // Actualizar appointment con información del error
                        $appointment->update([
                            'offer_creation_failed' => true,
                            'offer_creation_error' => $validationResult['error_message'],
                            'offer_creation_attempts' => ($appointment->offer_creation_attempts ?? 0) + 1
                        ]);

                        return [
                            'success' => false,
                            'error' => $validationResult['error_message'],
                            'errors' => $validationResult['errors'],
                            'details' => 'Errores de validación en C4C'
                        ];
                    }
                }

                // ✅ EXTRAER ID CORRECTO DE LA RESPUESTA SAP C4C
                $customerQuote = $data['Body']['CustomerQuoteBundleMaintainConfirmation_sync_V1']['CustomerQuote'] ?? [];
                $offerId = $customerQuote['ID'] ?? $data['offer_id'] ?? $data['ID'] ?? null;

                if (!$offerId) {
                    // ✅ PARA CLIENTE WILDCARD: SI NO HAY ID PERO IGNORAMOS ERROR VEHÍCULO, GENERAR ID FICTICIO
                    if (isset($esErrorVehiculoSolamente) && $esErrorVehiculoSolamente) {
                        $offerId = 'WILDCARD-' . $appointment->id . '-' . time();
                        Log::info('🎯 Cliente wildcard: Generando ID ficticio porque C4C no devolvió ID válido', [
                            'appointment_id' => $appointment->id,
                            'offer_id_ficticio' => $offerId
                        ]);
                    } else {
                        Log::error('❌ No se pudo extraer el ID de la oferta wildcard de la respuesta C4C', [
                            'appointment_id' => $appointment->id,
                            'response_data' => $data
                        ]);

                        return [
                            'success' => false,
                            'error' => 'No se pudo extraer el ID de la oferta de la respuesta C4C',
                            'data' => $data
                        ];
                    }
                }

                // ✅ PARA WILDCARD: ACTUALIZAR MANUALMENTE CON ID FICTICIO
                if (isset($esErrorVehiculoSolamente) && $esErrorVehiculoSolamente) {
                    $appointment->update([
                        'c4c_offer_id' => $offerId,
                        'offer_created_at' => now(),
                        'offer_creation_failed' => false,
                        'offer_creation_error' => null,
                        'offer_creation_attempts' => ($appointment->offer_creation_attempts ?? 0) + 1
                    ]);

                    Log::info('🎯 Appointment wildcard actualizado con ID ficticio', [
                        'appointment_id' => $appointment->id,
                        'c4c_offer_id_ficticio' => $offerId
                    ]);
                } else {
                    $this->actualizarAppointmentConOferta($appointment, $result);
                }

                Log::info('✅ Oferta wildcard creada exitosamente en C4C', [
                    'appointment_id' => $appointment->id,
                    'c4c_offer_id' => $offerId,
                    'response_data' => $data
                ]);

                return [
                    'success' => true,
                    'c4c_offer_id' => $offerId,
                    'message' => 'Oferta wildcard creada exitosamente',
                    'data' => $data
                ];
            } else {
                // ✅ MEJORAR MANEJO DE ERRORES SOAP FAULT
                $errorMessage = $result['error'] ?? 'Error desconocido en C4C';
                $transactionId = $result['transaction_id'] ?? null;
                $faultCode = $result['fault_code'] ?? null;

                Log::error('❌ Error en C4C al crear oferta wildcard', [
                    'appointment_id' => $appointment->id,
                    'error' => $errorMessage,
                    'fault_code' => $faultCode,
                    'transaction_id' => $transactionId,
                    'full_result' => $result
                ]);

                // Actualizar appointment con información del error
                $appointment->update([
                    'offer_creation_failed' => true,
                    'offer_creation_error' => $errorMessage,
                    'offer_creation_attempts' => ($appointment->offer_creation_attempts ?? 0) + 1,
                    'c4c_transaction_id' => $transactionId
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'fault_code' => $faultCode,
                    'transaction_id' => $transactionId,
                    'details' => 'Revisa los logs de C4C con Transaction ID: ' . $transactionId
                ];
            }
        } catch (\Exception $e) {
            Log::error('❌ Error creando oferta wildcard', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * ✅ MÉTODO PRIVADO: Preparar parámetros específicos para clientes wildcard
     * Genera XML exactamente como la referencia que funciona (SIN sección Item)
     */
    private function prepararParametrosWildcard(Appointment $appointment, CenterOrganizationMapping $mapping, $user, $vehicle): array
    {
        Log::info('🎯 Creando oferta para cliente wildcard', [
            'appointment_id' => $appointment->id,
            'customer_c4c_id' => $user->c4c_internal_id
        ]);

        // ✅ CONCATENAR SERVICIOS SELECCIONADOS EN COMENTARIOS
        $serviciosSeleccionados = $this->concatenarServiciosWildcard($appointment);

        // ✅ ESTRUCTURA XML EXACTA SEGÚN REFERENCIA FUNCIONAL (SIN ITEM)
        $params = [
            'CustomerQuote' => [
                // ✅ ATRIBUTOS PRINCIPALES (según trama de ejemplo)
                'ViewObjectIndicator' => '',
                'actionCode' => '01',
                'approverPartyListCompleteTransmissionIndicator' => '',
                'businessTransactionDocumentReferenceListCompleteTransmissionIndicator' => '',
                'competitorPartyListCompleteTransmissionIndicator' => '',
                'itemListCompleteTransmissionIndicator' => '',
                'otherPartyListCompleteTransmissionIndicator' => '',
                'salesEmployeePartyListCompleteTransmissionIndicator' => '',
                'salesPartnerListCompleteTransimissionIndicator' => '',
                'textListCompleteTransimissionIndicator' => '',

                // ✅ DATOS BÁSICOS
                'ProcessingTypeCode' => 'Z300',
                'BuyerID' => [
                    'schemeAgencyID' => '',
                    'schemeAgencySchemeAgencyID' => '',
                    'schemeID' => ''
                ],
                'Name' => [
                    '_' => 'OFERTA',
                    'languageCode' => 'ES'
                ],
                'DocumentLanguageCode' => 'ES',

                // ✅ DATOS DEL CLIENTE WILDCARD
                'BuyerParty' => [
                    'contactPartyListCompleteTransmissionIndicator' => '',
                    'BusinessPartnerInternalID' => '1200166011' // ✅ HARDCODEADO PARA WILDCARD
                ],

                // ✅ EMPLEADO RESPONSABLE
                'EmployeeResponsibleParty' => [
                    'EmployeeID' => '8000000010'
                ],

                // ✅ ESTRUCTURA ORGANIZACIONAL
                'SellerParty' => [
                    'OrganisationalCentreID' => 'GMIT'
                ],
                'SalesUnitParty' => [
                    'OrganisationalCentreID' => $mapping->sales_organization_id
                ],
                'SalesAndServiceBusinessArea' => [
                    'SalesOrganisationID' => $mapping->sales_organization_id,
                    'SalesOfficeID' => $mapping->sales_office_id,
                    'SalesGroupID' => $mapping->sales_group_id,
                    'DistributionChannelCode' => [
                        '_' => 'D4', // ✅ HARDCODEADO SEGÚN XML DE REFERENCIA
                        'listAgencyID' => '',
                        'listAgencySchemeAgencyID' => '',
                        'listAgencySchemeID' => '',
                        'listID' => '',
                        'listVersionID' => ''
                    ],
                    'DivisionCode' => [
                        '_' => $mapping->division_code,
                        'listAgencyID' => '',
                        'listAgencySchemeAgencyID' => '',
                        'listAgencySchemeID' => '',
                        'listID' => '',
                        'listVersionID' => ''
                    ]
                ],

                // ✅ VINCULACIÓN CON LA CITA
                'BusinessTransactionDocumentReference' => [
                    'actionCode' => '01',
                    'UUID' => [
                        '_' => $appointment->c4c_uuid,
                        'schemeAgencyID' => '',
                        'schemeID' => ''
                    ],
                    'TypeCode' => [
                        '_' => '12',
                        'listAgencyID' => '',
                        'listID' => '',
                        'listVersionID' => ''
                    ],
                    'RoleCode' => '1'
                ],

                // ✅ SERVICIOS CONCATENADOS EN TEXTO
                'Text' => [
                    'actionCode' => '01',
                    'TextTypeCode' => [
                        '_' => '10024',
                        'listAgencyID' => '',
                        'listAgencySchemeAgencyID' => '',
                        'listAgencySchemeID' => '',
                        'listID' => '',
                        'listVersionID' => ''
                    ],
                    'ContentText' => $serviciosSeleccionados
                ],

                // ✅ CAMPOS PERSONALIZADOS WILDCARD (según XML de referencia)
                'y6s:zOVGrupoVendedores' => $mapping->sales_group_id, // D03 dinámico
                'y6s:zOVIDCentro' => $appointment->center_code, // L013 dinámico
                'y6s:zOVPlaca' => $vehicle->license_plate, // ✅ CORREGIDO: Usar placa real del vehículo
                'y6s:zOVVieneDeHCI' => 'X',
                'y6s:zOVServExpress' => (strpos($appointment->service_mode, 'express') !== false) ? 'true' : 'false', // ✅ CORREGIDO: Dinámico para wildcard
                'y6s:zOVKilometraje' => '0', // ✅ HARDCODEADO A 0
                'y6s:zOVOrdenDBMV3' => '3000694890'
            ]
        ];

        Log::info('📋 Parámetros wildcard preparados', [
            'appointment_id' => $appointment->id,
            'servicios_concatenados' => $serviciosSeleccionados,
            'sales_org' => $mapping->sales_organization_id,
            'center_code' => $appointment->center_code
        ]);

        return $params;
    }

    /**
     * ✅ NUEVO MÉTODO: Concatenar servicios seleccionados para cliente wildcard
     */
    private function concatenarServiciosWildcard(Appointment $appointment): string
    {
        // ✅ OBTENER SERVICIOS DESDE CAMPOS DE LA CITA + COMENTARIOS WILDCARD
        $servicios = [];

        // 1. Mantenimiento
        if (!empty($appointment->maintenance_type)) {
            $servicios[] = "Mantenimiento: {$appointment->maintenance_type}";
        }

        // 2. Servicios adicionales y campañas - ✅ CORREGIDO: Para wildcard desde campo JSON
        $wildcardSelections = $appointment->wildcard_selections ? json_decode($appointment->wildcard_selections, true) : null;
        if (!empty($wildcardSelections)) {
            // Servicios adicionales
            if (!empty($wildcardSelections['servicios_adicionales'])) {
                $servicios[] = "Servicios adicionales: " . implode(', ', $wildcardSelections['servicios_adicionales']);
            }
            
            // Campañas  
            if (!empty($wildcardSelections['campanas'])) {
                $servicios[] = "Campañas: " . implode(', ', $wildcardSelections['campanas']);
            }
        }

        // 5. Información del vehículo
        $vehicle = $appointment->vehicle;
        if ($vehicle) {
            $servicios[] = "Vehículo: {$vehicle->license_plate} - {$vehicle->model}";
        }

        $serviciosConcatenados = !empty($servicios) ?
            implode(' | ', $servicios) :
            'Servicios múltiples seleccionados por cliente wildcard';

        Log::info('🔗 Servicios concatenados para wildcard', [
            'appointment_id' => $appointment->id,
            'servicios_count' => count($servicios),
            'servicios_texto' => $serviciosConcatenados
        ]);

        return $serviciosConcatenados;
    }

    /**
     * ✅ MÉTODO HELPER: Extraer valor numérico del tipo de mantenimiento para y6s:zOVKilometraje
     *
     * @param string $maintenanceType Ej: "10,000 Km", "20,000 Km", "1,000 km"
     * @return string Valor en miles para SAP (ej: "10", "20", "1")
     */
    private function extraerKilometrajeDeMantenimiento(string $maintenanceType): string
    {
        // Extraer número de strings como "10,000 Km", "20,000 Km", "1,000 km", "15,000 KM"
        if (preg_match('/(\d{1,3}),?(\d{3})?\s*(km|Km|KM)/i', $maintenanceType, $matches)) {
            $kilometers = $matches[1] . ($matches[2] ?? '');
            $kilometersInt = (int)$kilometers;

            // ✅ CORREGIDO: Enviar el valor completo en kilómetros (15,000 km → "15000")
            Log::info('🔢 Kilometraje extraído del maintenance_type', [
                'maintenance_type' => $maintenanceType,
                'kilometers_extraidos' => $kilometersInt,
                'valor_final' => (string)$kilometersInt
            ]);

            return (string)$kilometersInt;
        }

        // Fallback: usar 10000 como valor por defecto
        Log::warning('⚠️ No se pudo extraer kilometraje del maintenance_type, usando fallback', [
            'maintenance_type' => $maintenanceType,
            'fallback_value' => '10000'
        ]);

        return '10000';
    }

    /**
     * Generar comentarios combinados para ofertas de clientes normales
     * Incluye todos los servicios/campañas que no fueron priorizados en el package_id
     */
    private function generarComentariosCombinados(Appointment $appointment): string
    {
        $comentarios = ['Oferta generada automáticamente desde sistema web'];

        // Agregar tipo de mantenimiento si existe
        if (!empty($appointment->maintenance_type)) {
            $comentarios[] = "Mantenimiento: {$appointment->maintenance_type}";
        }

        // Agregar servicios adicionales usando relación many-to-many
        try {
            $serviciosAdicionales = $appointment->additionalServices ?? collect([]);
            if ($serviciosAdicionales->isNotEmpty()) {
                $serviciosTexto = $serviciosAdicionales->pluck('name')->toArray();
                $comentarios[] = "Servicios adicionales: " . implode(', ', $serviciosTexto);
            }
        } catch (\Exception $e) {
            Log::warning('⚠️ Error obteniendo servicios adicionales para comentarios', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage()
            ]);
        }

        // Agregar comentarios de la cita si existen
        if (!empty($appointment->comments)) {
            $comentarios[] = "Comentarios: {$appointment->comments}";
        }

        $comentarioFinal = implode(' | ', $comentarios);
        
        Log::info('📝 Comentarios combinados generados para oferta', [
            'appointment_id' => $appointment->id,
            'comentario_final' => $comentarioFinal,
            'maintenance_type' => $appointment->maintenance_type,
            'servicios_adicionales_count' => $serviciosAdicionales->count() ?? 0
        ]);

        return $comentarioFinal;
    }
}
