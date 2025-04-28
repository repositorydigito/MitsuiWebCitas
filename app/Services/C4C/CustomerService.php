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
            Log::info('CustomerService usando WSDL local: ' . $localWsdl);
        } else {
            $this->wsdl = config('c4c.services.customer.wsdl');
            Log::info('CustomerService usando WSDL remoto: ' . $this->wsdl);
        }

        $this->method = config('c4c.services.customer.method');
    }

    /**
     * Find a customer by DNI.
     *
     * @param string $dni
     * @return array
     */
    public function findByDNI(string $dni)
    {
        $params = [
            'CustomerSelectionByElements' => [
                'y6s:zDNI_EA8AE8AUBVHCSXVYS0FJ1R3ON' => [
                    'SelectionByText' => [
                        'InclusionExclusionCode' => 'I',
                        'IntervalBoundaryTypeCode' => '1',
                        'LowerBoundaryName' => $dni,
                        'UpperBoundaryName' => '',
                    ]
                ]
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => 20,
                'QueryHitsUnlimitedIndicator' => false,
            ],
        ];

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        if ($result['success'] && isset($result['data']->Customer)) {
            return $this->formatCustomerData($result['data']);
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Customer not found',
            'data' => null
        ];
    }

    /**
     * Find a customer by RUC.
     *
     * @param string $ruc
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
                    ]
                ]
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => 20,
                'QueryHitsUnlimitedIndicator' => false,
            ],
        ];

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        if ($result['success'] && isset($result['data']->Customer)) {
            $formattedResult = $this->formatCustomerData($result['data']);

            // Si tenemos resultados, intentamos filtrar para encontrar coincidencias exactas
            if ($formattedResult['success'] && !empty($formattedResult['data'])) {
                // Buscar coincidencias exactas por RUC
                $exactMatches = [];

                foreach ($formattedResult['data'] as $customer) {
                    // Verificar si el cliente tiene el RUC exacto
                    // El RUC podría estar en diferentes campos según la estructura de datos
                    $customerRuc = null;

                    // Verificar en campos comunes donde podría estar el RUC
                    if (isset($customer['external_id']) && $customer['external_id'] === $ruc) {
                        $exactMatches[] = $customer;
                    }
                    // También podría estar en un campo personalizado como zRuc
                    elseif (isset($customer['zRuc']) && $customer['zRuc'] === $ruc) {
                        $exactMatches[] = $customer;
                    }
                    // O podría estar en un campo de identificación general
                    elseif (isset($customer['identification']) && isset($customer['identification']['ruc']) &&
                            $customer['identification']['ruc'] === $ruc) {
                        $exactMatches[] = $customer;
                    }
                }

                // Si encontramos coincidencias exactas, actualizamos el resultado
                if (!empty($exactMatches)) {
                    Log::info("Se encontraron " . count($exactMatches) . " coincidencias exactas para el RUC: {$ruc}");
                    $formattedResult['data'] = $exactMatches;
                    $formattedResult['count'] = count($exactMatches);
                } else {
                    Log::info("No se encontraron coincidencias exactas para el RUC: {$ruc}. Devolviendo todos los resultados.");
                }
            }

            return $formattedResult;
        }

        Log::warning("No se encontró ningún cliente con RUC: {$ruc}");
        return [
            'success' => false,
            'error' => $result['error'] ?? 'Customer not found',
            'data' => null
        ];
    }

    /**
     * Find a customer by Carnet de Extranjería.
     *
     * @param string $ce
     * @return array
     */
    public function findByCE(string $ce)
    {
        $params = [
            'CustomerSelectionByElements' => [
                'y6s:zCE_EA8AE8AUBVHCSXVYS0FJ1R3ON' => [
                    'SelectionByText' => [
                        'InclusionExclusionCode' => 'I',
                        'IntervalBoundaryTypeCode' => '1',
                        'LowerBoundaryName' => $ce,
                        'UpperBoundaryName' => '',
                    ]
                ]
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => 20,
                'QueryHitsUnlimitedIndicator' => false,
            ],
        ];

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        if ($result['success'] && isset($result['data']->Customer)) {
            return $this->formatCustomerData($result['data']);
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Customer not found',
            'data' => null
        ];
    }

    /**
     * Find a customer by Passport.
     *
     * @param string $passport
     * @return array
     */
    public function findByPassport(string $passport)
    {
        $params = [
            'CustomerSelectionByElements' => [
                'y6s:zPasaporte_EA8AE8AUBVHCSXVYS0FJ1R3ON' => [
                    'SelectionByText' => [
                        'InclusionExclusionCode' => 'I',
                        'IntervalBoundaryTypeCode' => '1',
                        'LowerBoundaryName' => $passport,
                        'UpperBoundaryName' => '',
                    ]
                ]
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => 20,
                'QueryHitsUnlimitedIndicator' => false,
            ],
        ];

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        if ($result['success'] && isset($result['data']->Customer)) {
            return $this->formatCustomerData($result['data']);
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Customer not found',
            'data' => null
        ];
    }

    /**
     * Format customer data from SOAP response.
     *
     * @param object $response
     * @return array
     */
    protected function formatCustomerData($response)
    {
        // Verificar si hay clientes en la respuesta
        if (!isset($response->Customer) && isset($response->ProcessingConditions)) {
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
        if (!isset($response->Customer) && !isset($response->ProcessingConditions)) {
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
        if (!is_array($customerData)) {
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
                    'complete_delivery_requested_indicator' =>
                        isset($customer->SalesArrangement->CompleteDeliveryRequestedIndicator) &&
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
