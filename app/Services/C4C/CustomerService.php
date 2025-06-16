<?php

namespace App\Services\C4C;

use Illuminate\Support\Facades\Log;

class CustomerService
{
    /**
     * WSDL URL for customer service.
     *
     * @var string
     */
    protected $wsdl;

    /**
     * SOAP method for customer queries.
     *
     * @var string
     */
    protected $method;

    /**
     * Create a new CustomerService instance.
     */
    public function __construct()
    {
        // Intentar usar el WSDL local primero
        $localWsdl = storage_path('wsdl/querycustomerin.wsdl');
        if (file_exists($localWsdl)) {
            $this->wsdl = $localWsdl;
            Log::info('CustomerService usando WSDL local: '.$localWsdl);
        } else {
            $this->wsdl = config('c4c.services.customer.wsdl');
            Log::info('CustomerService usando WSDL remoto: '.$this->wsdl);
        }

        $this->method = config('c4c.services.customer.method');
    }

    /**
     * Find a customer by document type and number.
     */
    public function findByDocument(string $documentType, string $documentNumber): ?array
    {
        Log::info("Buscando cliente con {$documentType}: {$documentNumber}");

        $result = match ($documentType) {
            'DNI' => $this->findByDNI($documentNumber),
            'RUC' => $this->findByRUC($documentNumber),
            'CE' => $this->findByCE($documentNumber),
            'PASAPORTE' => $this->findByPassport($documentNumber),
            default => [
                'success' => false,
                'error' => 'Tipo de documento no válido',
                'data' => null,
            ],
        };

        // Si encontró datos, devolver el primer cliente
        if ($result['success'] && ! empty($result['data'])) {
            return $result['data'][0]; // Retornar primer cliente encontrado
        }

        return null; // No encontrado
    }

    /**
     * Find a customer by DNI.
     *
     * @return array
     */
    public function findByDNI(string $dni)
    {
        Log::info("Buscando cliente con DNI: {$dni}");

        $params = [
            'CustomerSelectionByElements' => [
                'y6s:zDNI_EA8AE8AUBVHCSXVYS0FJ1R3ON' => [
                    'SelectionByText' => [
                        'InclusionExclusionCode' => 'I',
                        'IntervalBoundaryTypeCode' => '1',
                        'LowerBoundaryName' => $dni,
                        'UpperBoundaryName' => '',
                    ],
                ],
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => 20,  // Usar 20 como en Python para obtener más resultados
                'QueryHitsUnlimitedIndicator' => false,
            ],
        ];

        // Log de parámetros enviados
        Log::info("Enviando parámetros SOAP para DNI: {$dni}", [
            'params' => $params,
            'wsdl' => $this->wsdl,
            'method' => $this->method,
        ]);

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        // Log de respuesta recibida
        Log::info("Respuesta SOAP recibida para DNI: {$dni}", [
            'success' => $result['success'],
            'error' => $result['error'] ?? null,
            'has_customer_data' => isset($result['data']->Customer),
            'raw_response_preview' => isset($result['data']) ? substr(json_encode($result['data']), 0, 500) : null,
        ]);

        // Verificar la estructura correcta de la respuesta como viene del HTTP request
        $hasCustomerData = false;
        $customerData = null;

        if ($result['success'] && $result['data']) {
            // Estructura del HTTP request: Body->CustomerByElementsResponse_sync->Customer
            if (isset($result['data']->Body->CustomerByElementsResponse_sync->Customer)) {
                $hasCustomerData = true;
                $customerData = $result['data']->Body->CustomerByElementsResponse_sync;
                Log::info('✅ Estructura HTTP: Customer encontrado en Body->CustomerByElementsResponse_sync->Customer');
            }
            // Estructura del SoapClient tradicional: Customer directo
            elseif (isset($result['data']->Customer)) {
                $hasCustomerData = true;
                $customerData = $result['data'];
                Log::info('✅ Estructura SoapClient: Customer encontrado directamente');
            } else {
                Log::warning('❌ No se encontró Customer en ninguna estructura conocida');
                Log::info('Estructura disponible:', [
                    'data_keys' => array_keys((array) $result['data']),
                ]);
            }
        }

        if ($hasCustomerData && $customerData) {
            $formattedResult = $this->formatCustomerData($customerData);

            // Implementar la MISMA lógica que Python: success + 'Customer' en respuesta
            if ($formattedResult['success'] && ! empty($formattedResult['data'])) {

                // Python considera exitoso si hay clientes en la respuesta, sin filtrar por DNI específico
                $customer = $formattedResult['data'][0]; // Tomar el primer cliente como Python
                $customerName = $customer['organisation']['first_line_name'] ?? 'N/A';

                Log::info("✅ Cliente encontrado por DNI: {$dni} (lógica Python)", [
                    'customer_name' => $customerName,
                    'internal_id' => $customer['internal_id'] ?? null,
                    'external_id' => $customer['external_id'] ?? null,
                    'total_customers_returned' => count($formattedResult['data']),
                    'behavior' => 'same_as_python_examples',
                ]);

                return $formattedResult; // Devolver todos los clientes como Python
            }

            return $formattedResult;
        }

        Log::warning("No se encontró ningún cliente con DNI: {$dni}");

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Customer not found',
            'data' => null,
        ];
    }

    /**
     * Find a customer by RUC.
     *
     * @return array
     */
    public function findByRUC(string $ruc)
    {
        Log::info("Buscando cliente con RUC: {$ruc}");

        $params = [
            'CustomerSelectionByElements' => [
                'y6s:zRuc_EA8AE8AUBVHCSXVYS0FJ1R3ON' => [
                    'SelectionByText' => [
                        'InclusionExclusionCode' => 'I',
                        'IntervalBoundaryTypeCode' => '1',
                        'LowerBoundaryName' => $ruc,
                        'UpperBoundaryName' => '',
                    ],
                ],
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => 20,
                'QueryHitsUnlimitedIndicator' => false,
            ],
        ];

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        // Verificar la estructura correcta de la respuesta como viene del HTTP request
        $hasCustomerData = false;
        $customerData = null;

        if ($result['success'] && $result['data']) {
            // Estructura del HTTP request: Body->CustomerByElementsResponse_sync->Customer
            if (isset($result['data']->Body->CustomerByElementsResponse_sync->Customer)) {
                $hasCustomerData = true;
                $customerData = $result['data']->Body->CustomerByElementsResponse_sync;
                Log::info('✅ Estructura HTTP: Customer encontrado en Body->CustomerByElementsResponse_sync->Customer para RUC');
            }
            // Estructura del SoapClient tradicional: Customer directo
            elseif (isset($result['data']->Customer)) {
                $hasCustomerData = true;
                $customerData = $result['data'];
                Log::info('✅ Estructura SoapClient: Customer encontrado directamente para RUC');
            } else {
                Log::warning('❌ No se encontró Customer en ninguna estructura conocida para RUC');
            }
        }

        if ($hasCustomerData && $customerData) {
            $formattedResult = $this->formatCustomerData($customerData);

            // Implementar la MISMA lógica que Python: success + 'Customer' en respuesta
            if ($formattedResult['success'] && ! empty($formattedResult['data'])) {

                // Python considera exitoso si hay clientes en la respuesta, sin filtrar por RUC específico
                $customer = $formattedResult['data'][0]; // Tomar el primer cliente como Python
                $customerName = $customer['organisation']['first_line_name'] ?? 'N/A';

                Log::info("✅ Cliente encontrado por RUC: {$ruc} (lógica Python)", [
                    'customer_name' => $customerName,
                    'internal_id' => $customer['internal_id'] ?? null,
                    'external_id' => $customer['external_id'] ?? null,
                    'total_customers_returned' => count($formattedResult['data']),
                    'behavior' => 'same_as_python_examples',
                ]);

                return $formattedResult; // Devolver todos los clientes como Python
            }

            return $formattedResult;
        }

        Log::warning("No se encontró ningún cliente con RUC: {$ruc}");

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Customer not found',
            'data' => null,
        ];
    }

    /**
     * Find a customer by Carnet de Extranjería.
     *
     * @return array
     */
    public function findByCE(string $ce)
    {
        Log::info("Buscando cliente con CE: {$ce}");

        $params = [
            'CustomerSelectionByElements' => [
                'y6s:zCE_EA8AE8AUBVHCSXVYS0FJ1R3ON' => [
                    'SelectionByText' => [
                        'InclusionExclusionCode' => 'I',
                        'IntervalBoundaryTypeCode' => '1',
                        'LowerBoundaryName' => $ce,
                        'UpperBoundaryName' => '',
                    ],
                ],
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => 20,
                'QueryHitsUnlimitedIndicator' => false,
            ],
        ];

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        // Verificar la estructura correcta de la respuesta como viene del HTTP request
        $hasCustomerData = false;
        $customerData = null;

        if ($result['success'] && $result['data']) {
            // Estructura del HTTP request: Body->CustomerByElementsResponse_sync->Customer
            if (isset($result['data']->Body->CustomerByElementsResponse_sync->Customer)) {
                $hasCustomerData = true;
                $customerData = $result['data']->Body->CustomerByElementsResponse_sync;
                Log::info('✅ Estructura HTTP: Customer encontrado en Body->CustomerByElementsResponse_sync->Customer para CE');
            }
            // Estructura del SoapClient tradicional: Customer directo
            elseif (isset($result['data']->Customer)) {
                $hasCustomerData = true;
                $customerData = $result['data'];
                Log::info('✅ Estructura SoapClient: Customer encontrado directamente para CE');
            } else {
                Log::warning('❌ No se encontró Customer en ninguna estructura conocida para CE');
            }
        }

        if ($hasCustomerData && $customerData) {
            $formattedResult = $this->formatCustomerData($customerData);

            // Implementar la MISMA lógica que Python: success + 'Customer' en respuesta
            if ($formattedResult['success'] && ! empty($formattedResult['data'])) {

                // Python considera exitoso si hay clientes en la respuesta, sin filtrar por CE específico
                $customer = $formattedResult['data'][0]; // Tomar el primer cliente como Python
                $customerName = $customer['organisation']['first_line_name'] ?? 'N/A';

                Log::info("✅ Cliente encontrado por CE: {$ce} (lógica Python)", [
                    'customer_name' => $customerName,
                    'internal_id' => $customer['internal_id'] ?? null,
                    'external_id' => $customer['external_id'] ?? null,
                    'total_customers_returned' => count($formattedResult['data']),
                    'behavior' => 'same_as_python_examples',
                ]);

                return $formattedResult; // Devolver todos los clientes como Python
            }

            return $formattedResult;
        }

        Log::warning("No se encontró ningún cliente con CE: {$ce}");

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Customer not found',
            'data' => null,
        ];
    }

    /**
     * Find a customer by Passport.
     *
     * @return array
     */
    public function findByPassport(string $passport)
    {
        Log::info("Buscando cliente con Passport: {$passport}");

        $params = [
            'CustomerSelectionByElements' => [
                'y6s:zPasaporte_EA8AE8AUBVHCSXVYS0FJ1R3ON' => [
                    'SelectionByText' => [
                        'InclusionExclusionCode' => 'I',
                        'IntervalBoundaryTypeCode' => '1',
                        'LowerBoundaryName' => $passport,
                        'UpperBoundaryName' => '',
                    ],
                ],
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => 20,
                'QueryHitsUnlimitedIndicator' => false,
            ],
        ];

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        // Verificar la estructura correcta de la respuesta como viene del HTTP request
        $hasCustomerData = false;
        $customerData = null;

        if ($result['success'] && $result['data']) {
            // Estructura del HTTP request: Body->CustomerByElementsResponse_sync->Customer
            if (isset($result['data']->Body->CustomerByElementsResponse_sync->Customer)) {
                $hasCustomerData = true;
                $customerData = $result['data']->Body->CustomerByElementsResponse_sync;
                Log::info('✅ Estructura HTTP: Customer encontrado en Body->CustomerByElementsResponse_sync->Customer para Passport');
            }
            // Estructura del SoapClient tradicional: Customer directo
            elseif (isset($result['data']->Customer)) {
                $hasCustomerData = true;
                $customerData = $result['data'];
                Log::info('✅ Estructura SoapClient: Customer encontrado directamente para Passport');
            } else {
                Log::warning('❌ No se encontró Customer en ninguna estructura conocida para Passport');
            }
        }

        if ($hasCustomerData && $customerData) {
            $formattedResult = $this->formatCustomerData($customerData);

            // Implementar la MISMA lógica que Python: success + 'Customer' en respuesta
            if ($formattedResult['success'] && ! empty($formattedResult['data'])) {

                // Python considera exitoso si hay clientes en la respuesta, sin filtrar por Passport específico
                $customer = $formattedResult['data'][0]; // Tomar el primer cliente como Python
                $customerName = $customer['organisation']['first_line_name'] ?? 'N/A';

                Log::info("✅ Cliente encontrado por Passport: {$passport} (lógica Python)", [
                    'customer_name' => $customerName,
                    'internal_id' => $customer['internal_id'] ?? null,
                    'external_id' => $customer['external_id'] ?? null,
                    'total_customers_returned' => count($formattedResult['data']),
                    'behavior' => 'same_as_python_examples',
                ]);

                return $formattedResult; // Devolver todos los clientes como Python
            }

            return $formattedResult;
        }

        Log::warning("No se encontró ningún cliente con Passport: {$passport}");

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Customer not found',
            'data' => null,
        ];
    }

    /**
     * Find customer with fallback (DNI -> RUC).
     *
     * @return array
     */
    public function findWithFallback(string $dni, ?string $ruc = null)
    {
        Log::info("Buscando cliente con fallback - DNI: {$dni}, RUC: {$ruc}");

        // Paso 1: Buscar por DNI
        $result = $this->findByDNI($dni);

        if ($result['success'] && ! empty($result['data'])) {
            Log::info("Cliente encontrado por DNI: {$dni}");

            return array_merge($result, [
                'search_type' => 'DNI',
                'document_used' => $dni,
                'fallback_used' => false,
            ]);
        }

        // Paso 2: Si se proporciona RUC, buscar por RUC
        if ($ruc) {
            Log::info("Cliente no encontrado por DNI, intentando con RUC: {$ruc}");
            $result = $this->findByRUC($ruc);

            if ($result['success'] && ! empty($result['data'])) {
                Log::info("Cliente encontrado por RUC: {$ruc}");

                return array_merge($result, [
                    'search_type' => 'RUC',
                    'document_used' => $ruc,
                    'fallback_used' => true,
                ]);
            }
        }

        Log::warning("Cliente no encontrado con ningún documento - DNI: {$dni}, RUC: {$ruc}");

        return [
            'success' => false,
            'error' => 'Customer not found with any provided document',
            'data' => null,
            'search_type' => null,
            'document_used' => null,
            'fallback_used' => true,
        ];
    }

    /**
     * Find customers with multiple documents.
     *
     * @return array
     */
    public function findMultiple(array $documents)
    {
        Log::info('Búsqueda múltiple con documentos: '.implode(', ', $documents));

        foreach ($documents as $index => $document) {
            $document = trim($document);

            // Determinar tipo por longitud (simplificado)
            if (strlen($document) == 8) {
                $searchType = 'DNI';
                $result = $this->findByDNI($document);
            } elseif (strlen($document) == 11) {
                $searchType = 'RUC';
                $result = $this->findByRUC($document);
            } else {
                Log::warning("Documento {$document} no tiene formato reconocido");

                continue;
            }

            if ($result['success'] && ! empty($result['data'])) {
                Log::info("Cliente encontrado con {$searchType}: {$document}");

                return array_merge($result, [
                    'search_type' => $searchType,
                    'document_used' => $document,
                    'attempt_number' => $index + 1,
                    'total_attempts' => count($documents),
                    'documents_tried' => array_slice($documents, 0, $index + 1),
                ]);
            } else {
                Log::info("No encontrado con {$searchType}: {$document}");
            }
        }

        Log::warning('Cliente no encontrado con ningún documento de: '.implode(', ', $documents));

        return [
            'success' => false,
            'error' => 'Customer not found with any provided document',
            'data' => null,
            'search_type' => null,
            'document_used' => null,
            'documents_tried' => $documents,
            'total_attempts' => count($documents),
        ];
    }

    /**
     * Format customer data from SOAP response.
     *
     * @param  object  $response
     * @return array
     */
    protected function formatCustomerData($response)
    {
        // Verificar si hay clientes en la respuesta
        if (! isset($response->Customer) && isset($response->ProcessingConditions)) {
            // No hay clientes, pero hay información de procesamiento
            return [
                'success' => false,
                'error' => 'Customer not found',
                'data' => null,
                'count' => 0,
                'processing_conditions' => [
                    'returned_query_hits_number_value' => $response->ProcessingConditions->ReturnedQueryHitsNumberValue ?? 0,
                    'more_hits_available_indicator' => $response->ProcessingConditions->MoreHitsAvailableIndicator ?? false,
                ],
            ];
        }

        // Si no hay información de clientes ni de procesamiento, es un error
        if (! isset($response->Customer) && ! isset($response->ProcessingConditions)) {
            return [
                'success' => false,
                'error' => 'Invalid response format',
                'data' => null,
                'count' => 0,
            ];
        }

        $customers = [];
        $customerData = $response->Customer;

        // If only one customer is returned, convert to array
        if (! is_array($customerData)) {
            $customerData = [$customerData];
        }

        foreach ($customerData as $customer) {
            // Datos básicos
            $formattedCustomer = [
                'uuid' => $customer->UUID ?? null,
                'internal_id' => $customer->InternalID ?? null,
                'external_id' => $customer->ExternalID ?? null,
                'change_state_id' => $customer->ChangeStateID ?? null,
            ];

            // Datos administrativos del sistema
            if (isset($customer->SystemAdministrativeData)) {
                $formattedCustomer['system_administrative_data'] = [
                    'creation_date_time' => $customer->SystemAdministrativeData->CreationDateTime ?? null,
                    'creation_identity_uuid' => $customer->SystemAdministrativeData->CreationIdentityUUID ?? null,
                    'last_change_date_time' => $customer->SystemAdministrativeData->LastChangeDateTime ?? null,
                    'last_change_identity_uuid' => $customer->SystemAdministrativeData->LastChangeIdentityUUID ?? null,
                ];
            }

            // Datos de categoría y estado
            $formattedCustomer['category_code'] = $customer->CategoryCode ?? null;
            $formattedCustomer['prospect_indicator'] = isset($customer->ProspectIndicator) && $customer->ProspectIndicator === 'true';
            $formattedCustomer['customer_indicator'] = isset($customer->CustomerIndicator) && $customer->CustomerIndicator === 'true';
            $formattedCustomer['life_cycle_status_code'] = $customer->LifeCycleStatusCode ?? null;

            // Datos de organización
            if (isset($customer->Organisation)) {
                $formattedCustomer['organisation'] = [
                    'company_legal_form_code' => $customer->Organisation->CompanyLegalFormCode ?? null,
                    'first_line_name' => $customer->Organisation->FirstLineName ?? null,
                    'last_line_name' => $customer->Organisation->LastLineName ?? null,
                ];
            }

            // Datos de contacto y permisos
            $formattedCustomer['contact_allowed_code'] = $customer->ContactAllowedCode ?? null;
            $formattedCustomer['legal_competence_indicator'] = isset($customer->LegalCompetenceIndicator) && $customer->LegalCompetenceIndicator === 'true';
            $formattedCustomer['industrial_sector_code'] = $customer->IndustrialSectorCode ?? null;

            // Datos de dirección
            if (isset($customer->AddressInformation)) {
                $addressInfo = [
                    'uuid' => $customer->AddressInformation->UUID ?? null,
                    'current_address_snapshot_uuid' => $customer->AddressInformation->CurrentAddressSnapshotUUID ?? null,
                ];

                // Uso de dirección
                if (isset($customer->AddressInformation->AddressUsage)) {
                    $addressInfo['address_usage'] = [
                        'address_usage_code' => $customer->AddressInformation->AddressUsage->AddressUsageCode ?? null,
                    ];
                }

                // Dirección detallada
                if (isset($customer->AddressInformation->Address)) {
                    $address = $customer->AddressInformation->Address;
                    $addressDetails = [
                        'correspondence_language_code' => $address->CorrespondenceLanguageCode ?? null,
                    ];

                    // Email
                    if (isset($address->Email) && isset($address->Email->URI)) {
                        $addressDetails['email'] = [
                            'uri' => $address->Email->URI,
                        ];
                    }

                    // Dirección postal
                    if (isset($address->PostalAddress)) {
                        $addressDetails['postal_address'] = [
                            'country_code' => $address->PostalAddress->CountryCode ?? null,
                            'region_code' => $address->PostalAddress->RegionCode ?? null,
                            'region_description' => $address->PostalAddress->RegionDescription ?? null,
                            'city_name' => $address->PostalAddress->CityName ?? null,
                            'street_name' => $address->PostalAddress->StreetName ?? null,
                            'street_postal_code' => $address->PostalAddress->StreetPostalCode ?? null,
                            'time_zone_code' => $address->PostalAddress->TimeZoneCode ?? null,
                        ];
                    }

                    // Teléfonos
                    if (isset($address->Telephone)) {
                        $phones = is_array($address->Telephone) ? $address->Telephone : [$address->Telephone];
                        $addressDetails['telephone'] = [];

                        foreach ($phones as $phone) {
                            $addressDetails['telephone'][] = [
                                'formatted_number_description' => $phone->FormattedNumberDescription ?? null,
                                'mobile_phone_number_indicator' => isset($phone->MobilePhoneNumberIndicator) && $phone->MobilePhoneNumberIndicator === 'true',
                            ];
                        }
                    }

                    // Dirección formateada
                    if (isset($address->FormattedAddress)) {
                        $addressDetails['formatted_address'] = [
                            'formatted_address_description' => $address->FormattedAddress->FormattedAddressDescription ?? null,
                            'formatted_postal_address_description' => $address->FormattedAddress->FormattedPostalAddressDescription ?? null,
                        ];

                        // Dirección formateada en líneas
                        if (isset($address->FormattedAddress->FormattedAddress)) {
                            $addressDetails['formatted_address']['formatted_address'] = [
                                'first_line_description' => $address->FormattedAddress->FormattedAddress->FirstLineDescription ?? null,
                                'second_line_description' => $address->FormattedAddress->FormattedAddress->SecondLineDescription ?? null,
                                'third_line_description' => $address->FormattedAddress->FormattedAddress->ThirdLineDescription ?? null,
                                'fourth_line_description' => $address->FormattedAddress->FormattedAddress->FourthLineDescription ?? null,
                            ];
                        }
                    }

                    $addressInfo['address'] = $addressDetails;
                }

                $formattedCustomer['address_information'] = $addressInfo;
            }

            // Datos de rol
            if (isset($customer->Role)) {
                $formattedCustomer['role'] = [
                    'role_code' => $customer->Role->RoleCode ?? null,
                ];
            }
            $formattedCustomer['role_description'] = $customer->RoleDescription ?? null;

            // Datos de responsabilidad directa
            if (isset($customer->DirectResponsibility)) {
                $formattedCustomer['direct_responsibility'] = [
                    'party_role_code' => $customer->DirectResponsibility->PartyRoleCode ?? null,
                    'employee_id' => $customer->DirectResponsibility->EmployeeID ?? null,
                    'employee_uuid' => $customer->DirectResponsibility->EmployeeUUID ?? null,
                ];

                // Período de validez
                if (isset($customer->DirectResponsibility->ValidityPeriod)) {
                    $formattedCustomer['direct_responsibility']['validity_period'] = [
                        'start_date' => $customer->DirectResponsibility->ValidityPeriod->StartDate ?? null,
                        'end_date' => $customer->DirectResponsibility->ValidityPeriod->EndDate ?? null,
                    ];
                }

                $formattedCustomer['direct_responsibility']['default_indicator'] =
                    isset($customer->DirectResponsibility->DefaultIndicator) &&
                    $customer->DirectResponsibility->DefaultIndicator === 'true';
            }

            // Datos de acuerdo de ventas
            if (isset($customer->SalesArrangement)) {
                $formattedCustomer['sales_arrangement'] = [
                    'sales_organisation_id' => $customer->SalesArrangement->SalesOrganisationID ?? null,
                    'distribution_channel_code' => $customer->SalesArrangement->DistributionChannelCode ?? null,
                    'sales_group_id' => $customer->SalesArrangement->SalesGroupID ?? null,
                    'complete_delivery_requested_indicator' => isset($customer->SalesArrangement->CompleteDeliveryRequestedIndicator) &&
                        $customer->SalesArrangement->CompleteDeliveryRequestedIndicator === 'true',
                    'currency_code' => $customer->SalesArrangement->CurrencyCode ?? null,
                    'customer_group_code' => $customer->SalesArrangement->CustomerGroupCode ?? null,
                    'cash_discount_terms_code' => $customer->SalesArrangement->CashDiscountTermsCode ?? null,
                    'division_code' => $customer->SalesArrangement->DivisionCode ?? null,
                ];
            }

            // Datos de identificación
            // Buscar propiedades con prefijo 'n1:z' que podrían contener documentos de identidad
            foreach ($customer as $key => $value) {
                if (strpos($key, 'n1:z') === 0) {
                    $docType = str_replace('n1:z', '', $key);
                    $formattedCustomer[$key] = $value;
                }
            }

            // Agregar campos específicos de identificación que sabemos que existen
            if (isset($customer->zDNI)) {
                $formattedCustomer['zDNI'] = $customer->zDNI;
                Log::info('✅ zDNI encontrado en customer', ['zDNI' => $customer->zDNI]);
            }
            if (isset($customer->zRuc)) {
                $formattedCustomer['zRuc'] = $customer->zRuc;
            }
            if (isset($customer->zCE)) {
                $formattedCustomer['zCE'] = $customer->zCE;
            }
            if (isset($customer->zPasaporte)) {
                $formattedCustomer['zPasaporte'] = $customer->zPasaporte;
            }

            // Debug: mostrar todas las propiedades del customer
            Log::info('Propiedades del customer:', [
                'customer_keys' => array_keys((array) $customer),
                'customer_preview' => json_encode($customer, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ]);

            // Agregar el cliente formateado a la lista
            $customers[] = $formattedCustomer;
        }

        // Crear la respuesta
        $result = [
            'success' => true,
            'error' => null,
            'data' => $customers,
            'count' => count($customers),
        ];

        // Agregar información de procesamiento si está disponible
        if (isset($response->ProcessingConditions)) {
            $result['processing_conditions'] = [
                'returned_query_hits_number_value' => $response->ProcessingConditions->ReturnedQueryHitsNumberValue ?? 0,
                'more_hits_available_indicator' => $response->ProcessingConditions->MoreHitsAvailableIndicator ?? false,
                'last_returned_object_id' => $response->ProcessingConditions->LastReturnedObjectID ?? null,
            ];
        }

        return $result;
    }
}
