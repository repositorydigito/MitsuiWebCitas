<?php

namespace App\Services\C4C;

use Illuminate\Support\Facades\Log;

class MockCustomerService
{
    /**
     * Find a customer by DNI.
     *
     * @param string $dni
     * @return array
     */
    public function findByDNI(string $dni)
    {
        Log::info("MockCustomerService: Buscando cliente con DNI {$dni}");

        // Datos específicos para el DNI 40359482 (basado en la estructura real de la respuesta SOAP)
        if ($dni === '40359482') {
            $customer = [
                'uuid' => '00163e10-02f9-1ee6-ae94-41a2270e84e2',
                'internal_id' => '1000140',
                'external_id' => '500000080',
                'change_state_id' => '20230613225505.0611240',
                'system_administrative_data' => [
                    'creation_date_time' => '2016-12-02T16:17:12.820781Z',
                    'creation_identity_uuid' => '00163e10-02f9-1ee6-ac81-41554fd34cd6',
                    'last_change_date_time' => '2023-06-13T22:55:05.061124Z',
                    'last_change_identity_uuid' => '00163e10-02fb-1ed5-bdd5-7af3cf02f977',
                ],
                'category_code' => '1',
                'prospect_indicator' => true,
                'customer_indicator' => true,
                'life_cycle_status_code' => '2',
                'organisation' => [
                    'company_legal_form_code' => 'Z1',
                    'first_line_name' => 'ALEJANDRO TOLEDO PARRA',
                ],
                'contact_allowed_code' => '1',
                'legal_competence_indicator' => true,
                'industrial_sector_code' => 'ZM007',
                'address_information' => [
                    'uuid' => '00163e10-02f9-1ee6-ae94-41a2270f04e2',
                    'current_address_snapshot_uuid' => '00163e10-02f9-1ee6-ae95-5911f4b44d9a',
                    'address_usage' => [
                        'address_usage_code' => 'XXDEFAULT',
                    ],
                    'address' => [
                        'correspondence_language_code' => 'ES',
                        'email' => [
                            'uri' => 'alejandro.toledo@example.com',
                        ],
                        'postal_address' => [
                            'country_code' => 'PE',
                            'region_code' => '25',
                            'region_description' => 'Lima Region',
                            'city_name' => 'ATE',
                            'street_name' => 'Av. Metropolitana Mz. R Lote 11 Urb. Los Angeles',
                            'time_zone_code' => 'UTC-5',
                        ],
                        'telephone' => [
                            [
                                'formatted_number_description' => '+51 4895617',
                                'mobile_phone_number_indicator' => false,
                            ],
                            [
                                'formatted_number_description' => '+51 966755294',
                                'mobile_phone_number_indicator' => true,
                            ],
                        ],
                        'formatted_address' => [
                            'formatted_address_description' => 'ALEJANDRO TOLEDO PARRA / Av. Metropolitana Mz. R Lote 11 Urb. Los Angeles / ATE / PE',
                            'formatted_postal_address_description' => 'Av. Metropolitana Mz. R Lote 11 Urb. Los Angeles / ATE / PE',
                            'formatted_address' => [
                                'first_line_description' => 'ALEJANDRO TOLEDO PARRA',
                                'second_line_description' => 'Av. Metropolitana Mz. R Lote 11 Urb. Los Angeles',
                                'third_line_description' => 'ATE',
                                'fourth_line_description' => 'Peru',
                            ],
                        ],
                    ],
                ],
                'role' => [
                    'role_code' => 'BUP002',
                ],
                'role_description' => 'Prospect',
                'direct_responsibility' => [
                    'party_role_code' => '142',
                    'employee_id' => '3',
                    'employee_uuid' => '00163e10-02f9-1ee6-ac81-3fca2edfecbf',
                    'validity_period' => [
                        'start_date' => '0001-01-01',
                        'end_date' => '9999-12-31',
                    ],
                    'default_indicator' => true,
                ],
                'sales_arrangement' => [
                    'sales_organisation_id' => 'DM07',
                    'distribution_channel_code' => 'D4',
                    'sales_group_id' => 'D03',
                    'complete_delivery_requested_indicator' => true,
                    'currency_code' => 'PEN',
                    'customer_group_code' => 'T1',
                    'cash_discount_terms_code' => 'Z000',
                    'division_code' => 'D1',
                ],
                'zDNI' => $dni,
            ];

            // Crear la respuesta en el formato esperado
            $response = [
                'success' => true,
                'error' => null,
                'data' => [$customer],
                'count' => 1,
                'processing_conditions' => [
                    'returned_query_hits_number_value' => 1,
                    'more_hits_available_indicator' => false,
                    'last_returned_object_id' => '00163E1002F91EE6AE9441A2270E84E2',
                ],
            ];

            return $response;
        }

        // Datos de ejemplo para otros DNIs (formato simplificado pero manteniendo la estructura)
        $customer = [
            'uuid' => '00163e10-02f9-1ee7-86ae-7e8d16f8d608',
            'internal_id' => '80019',
            'external_id' => '0000080019',
            'category_code' => '1',
            'life_cycle_status_code' => '2',
            'organisation' => [
                'first_line_name' => 'JUAN PEREZ',
            ],
            'address_information' => [
                'address' => [
                    'postal_address' => [
                        'country_code' => 'PE',
                        'city_name' => 'LIMA',
                        'street_name' => 'AV. JAVIER PRADO ESTE 123',
                        'street_postal_code' => '15024',
                    ],
                    'email' => [
                        'uri' => 'juan.perez@example.com',
                    ],
                    'telephone' => [
                        [
                            'formatted_number_description' => '+51 987654321',
                            'mobile_phone_number_indicator' => true,
                        ],
                    ],
                ],
            ],
            'sales_arrangement' => [
                'sales_organisation_id' => 'DM07',
                'distribution_channel_code' => 'D4',
                'sales_group_id' => 'D03',
                'currency_code' => 'PEN',
                'customer_group_code' => 'T1',
            ],
            'zDNI' => $dni,
        ];

        return [
            'success' => true,
            'error' => null,
            'data' => [$customer],
            'count' => 1,
            'processing_conditions' => [
                'returned_query_hits_number_value' => 1,
                'more_hits_available_indicator' => false,
            ],
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
        Log::info("MockCustomerService: Buscando cliente con RUC {$ruc}");

        // Datos específicos para el RUC 20558638223 (basado en la estructura real de la respuesta SOAP)
        if ($ruc === '20558638223') {
            $customer = [
                'uuid' => '00163e10-02f9-1ee6-ae94-41a2270e84e2',
                'internal_id' => '1000140',
                'external_id' => '500000080',
                'change_state_id' => '20230613225505.0611240',
                'system_administrative_data' => [
                    'creation_date_time' => '2016-12-02T16:17:12.820781Z',
                    'creation_identity_uuid' => '00163e10-02f9-1ee6-ac81-41554fd34cd6',
                    'last_change_date_time' => '2023-06-13T22:55:05.061124Z',
                    'last_change_identity_uuid' => '00163e10-02fb-1ed5-bdd5-7af3cf02f977',
                ],
                'category_code' => '2',
                'prospect_indicator' => true,
                'life_cycle_status_code' => '2',
                'organisation' => [
                    'company_legal_form_code' => 'Z2',
                    'first_line_name' => 'ALEJANDRO TOLEDO PARRA',
                ],
                'contact_allowed_code' => '1',
                'legal_competence_indicator' => true,
                'industrial_sector_code' => 'ZM007',
                'address_information' => [
                    'uuid' => '00163e10-02f9-1ee6-ae94-41a2270f04e2',
                    'current_address_snapshot_uuid' => '00163e10-02f9-1ee6-ae95-5911f4b44d9a',
                    'address_usage' => [
                        'address_usage_code' => 'XXDEFAULT',
                    ],
                    'address' => [
                        'correspondence_language_code' => 'ES',
                        'email' => [
                            'uri' => 'ktrujillo@mitsuiautomotriz.com',
                        ],
                        'postal_address' => [
                            'country_code' => 'PE',
                            'region_code' => '25',
                            'region_description' => 'Lima Region',
                            'city_name' => 'ATE',
                            'street_name' => 'Av. Metropolitana Mz. R Lote 11 Urb. Los Angeles',
                            'time_zone_code' => 'UTC-5',
                        ],
                        'telephone' => [
                            [
                                'formatted_number_description' => '+51 4895617',
                                'mobile_phone_number_indicator' => false,
                            ],
                            [
                                'formatted_number_description' => '+51 966755294',
                                'mobile_phone_number_indicator' => true,
                            ],
                        ],
                        'formatted_address' => [
                            'formatted_address_description' => 'ALEJANDRO TOLEDO PARRA / Av. Metropolitana Mz. R Lote 11 Urb. Los Angeles / ATE / PE',
                            'formatted_postal_address_description' => 'Av. Metropolitana Mz. R Lote 11 Urb. Los Angeles / ATE / PE',
                            'formatted_address' => [
                                'first_line_description' => 'ALEJANDRO TOLEDO PARRA',
                                'second_line_description' => 'Av. Metropolitana Mz. R Lote 11 Urb. Los Angeles',
                                'third_line_description' => 'ATE',
                                'fourth_line_description' => 'Peru',
                            ],
                        ],
                    ],
                ],
                'role' => [
                    'role_code' => 'BUP002',
                ],
                'role_description' => 'Prospect',
                'direct_responsibility' => [
                    'party_role_code' => '142',
                    'employee_id' => '3',
                    'employee_uuid' => '00163e10-02f9-1ee6-ac81-3fca2edfecbf',
                    'validity_period' => [
                        'start_date' => '0001-01-01',
                        'end_date' => '9999-12-31',
                    ],
                    'default_indicator' => true,
                ],
                'sales_arrangement' => [
                    'sales_organisation_id' => 'DM07',
                    'distribution_channel_code' => 'D4',
                    'sales_group_id' => 'D03',
                    'complete_delivery_requested_indicator' => true,
                    'currency_code' => 'PEN',
                    'customer_group_code' => 'T1',
                    'cash_discount_terms_code' => 'Z000',
                    'division_code' => 'D1',
                ],
                'zRuc' => $ruc,
            ];

            // Crear la respuesta en el formato esperado
            $response = [
                'success' => true,
                'error' => null,
                'data' => [$customer],
                'count' => 1,
                'processing_conditions' => [
                    'returned_query_hits_number_value' => 1,
                    'more_hits_available_indicator' => false,
                    'last_returned_object_id' => '00163E1002F91EE6AE9441A2270E84E2',
                ],
            ];

            return $response;
        }

        // Datos específicos para el RUC 20605414410
        if ($ruc === '20605414410') {
            $customer = [
                'uuid' => '00163e10-02f9-1ee7-86ae-7e8d16f8d608',
                'internal_id' => '80020',
                'external_id' => '0000080020',
                'change_state_id' => '20230613225505.0611240',
                'system_administrative_data' => [
                    'creation_date_time' => '2016-12-02T16:17:12.820781Z',
                    'creation_identity_uuid' => '00163e10-02f9-1ee6-ac81-41554fd34cd6',
                    'last_change_date_time' => '2023-06-13T22:55:05.061124Z',
                    'last_change_identity_uuid' => '00163e10-02fb-1ed5-bdd5-7af3cf02f977',
                ],
                'category_code' => '2',
                'prospect_indicator' => true,
                'life_cycle_status_code' => '2',
                'organisation' => [
                    'company_legal_form_code' => 'Z2',
                    'first_line_name' => 'EMPRESA CON RUC 20605414410',
                ],
                'contact_allowed_code' => '1',
                'legal_competence_indicator' => true,
                'industrial_sector_code' => 'ZM007',
                'address_information' => [
                    'address' => [
                        'email' => [
                            'uri' => 'contacto@empresa20605414410.com',
                        ],
                        'postal_address' => [
                            'country_code' => 'PE',
                            'region_code' => '25',
                            'region_description' => 'Lima Region',
                            'city_name' => 'LIMA',
                            'street_name' => 'AV. JAVIER PRADO ESTE 456',
                            'time_zone_code' => 'UTC-5',
                        ],
                        'telephone' => [
                            [
                                'formatted_number_description' => '+51 1 234-5678',
                                'mobile_phone_number_indicator' => false,
                            ],
                        ],
                    ],
                ],
                'sales_arrangement' => [
                    'sales_organisation_id' => 'DM07',
                    'distribution_channel_code' => 'D4',
                    'sales_group_id' => 'D03',
                    'currency_code' => 'PEN',
                    'customer_group_code' => 'T1',
                ],
                'zRuc' => $ruc,
            ];

            return [
                'success' => true,
                'error' => null,
                'data' => [$customer],
                'count' => 1,
                'processing_conditions' => [
                    'returned_query_hits_number_value' => 1,
                    'more_hits_available_indicator' => false,
                ],
            ];
        }

        // Datos de ejemplo para otros RUCs (formato simplificado pero manteniendo la estructura)
        $customer = [
            'uuid' => '00163e10-02f9-1ee7-86ae-7e8d16f8d608',
            'internal_id' => '80019',
            'external_id' => '0000080019',
            'category_code' => '2',
            'life_cycle_status_code' => '2',
            'organisation' => [
                'first_line_name' => 'EMPRESA DE PRUEBA S.A.C.',
            ],
            'address_information' => [
                'address' => [
                    'postal_address' => [
                        'country_code' => 'PE',
                        'city_name' => 'LIMA',
                        'street_name' => 'AV. JAVIER PRADO ESTE 6042',
                        'street_postal_code' => '15024',
                    ],
                    'email' => [
                        'uri' => 'contacto@empresaprueba.com',
                    ],
                    'telephone' => [
                        [
                            'formatted_number_description' => '+51 1 345-6789',
                            'mobile_phone_number_indicator' => false,
                        ],
                    ],
                ],
            ],
            'sales_arrangement' => [
                'sales_organisation_id' => 'DM07',
                'distribution_channel_code' => 'D4',
                'sales_group_id' => 'D03',
                'currency_code' => 'PEN',
                'customer_group_code' => 'T1',
            ],
            'zRuc' => $ruc,
        ];

        return [
            'success' => true,
            'error' => null,
            'data' => [$customer],
            'count' => 1,
            'processing_conditions' => [
                'returned_query_hits_number_value' => 1,
                'more_hits_available_indicator' => false,
            ],
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
        Log::info("MockCustomerService: Buscando cliente con CE {$ce}");

        // Datos específicos para el CE 73532531 (basado en la estructura real de la respuesta SOAP)
        if ($ce === '73532531') {
            $customer = [
                'uuid' => '00163e10-02f9-1ee6-ae94-41a2270e84e2',
                'internal_id' => '1000140',
                'external_id' => '500000080',
                'change_state_id' => '20230613225505.0611240',
                'system_administrative_data' => [
                    'creation_date_time' => '2016-12-02T16:17:12.820781Z',
                    'creation_identity_uuid' => '00163e10-02f9-1ee6-ac81-41554fd34cd6',
                    'last_change_date_time' => '2023-06-13T22:55:05.061124Z',
                    'last_change_identity_uuid' => '00163e10-02fb-1ed5-bdd5-7af3cf02f977',
                ],
                'category_code' => '1',
                'prospect_indicator' => true,
                'life_cycle_status_code' => '2',
                'organisation' => [
                    'company_legal_form_code' => 'Z1',
                    'first_line_name' => 'MARIA RODRIGUEZ GONZALEZ',
                ],
                'contact_allowed_code' => '1',
                'legal_competence_indicator' => true,
                'industrial_sector_code' => 'ZM007',
                'address_information' => [
                    'uuid' => '00163e10-02f9-1ee6-ae94-41a2270f04e2',
                    'current_address_snapshot_uuid' => '00163e10-02f9-1ee6-ae95-5911f4b44d9a',
                    'address_usage' => [
                        'address_usage_code' => 'XXDEFAULT',
                    ],
                    'address' => [
                        'correspondence_language_code' => 'ES',
                        'email' => [
                            'uri' => 'maria.rodriguez@example.com',
                        ],
                        'postal_address' => [
                            'country_code' => 'PE',
                            'region_code' => '25',
                            'region_description' => 'Lima Region',
                            'city_name' => 'LIMA',
                            'street_name' => 'AV. JAVIER PRADO ESTE 456',
                            'time_zone_code' => 'UTC-5',
                        ],
                        'telephone' => [
                            [
                                'formatted_number_description' => '+51 987654322',
                                'mobile_phone_number_indicator' => true,
                            ],
                            [
                                'formatted_number_description' => '+51 1 345-6789',
                                'mobile_phone_number_indicator' => false,
                            ],
                        ],
                        'formatted_address' => [
                            'formatted_address_description' => 'MARIA RODRIGUEZ GONZALEZ / AV. JAVIER PRADO ESTE 456 / LIMA / PE',
                            'formatted_postal_address_description' => 'AV. JAVIER PRADO ESTE 456 / LIMA / PE',
                            'formatted_address' => [
                                'first_line_description' => 'MARIA RODRIGUEZ GONZALEZ',
                                'second_line_description' => 'AV. JAVIER PRADO ESTE 456',
                                'third_line_description' => 'LIMA',
                                'fourth_line_description' => 'Peru',
                            ],
                        ],
                    ],
                ],
                'role' => [
                    'role_code' => 'BUP002',
                ],
                'role_description' => 'Prospect',
                'direct_responsibility' => [
                    'party_role_code' => '142',
                    'employee_id' => '3',
                    'employee_uuid' => '00163e10-02f9-1ee6-ac81-3fca2edfecbf',
                    'validity_period' => [
                        'start_date' => '0001-01-01',
                        'end_date' => '9999-12-31',
                    ],
                    'default_indicator' => true,
                ],
                'sales_arrangement' => [
                    'sales_organisation_id' => 'DM07',
                    'distribution_channel_code' => 'D4',
                    'sales_group_id' => 'D03',
                    'complete_delivery_requested_indicator' => true,
                    'currency_code' => 'PEN',
                    'customer_group_code' => 'T1',
                    'cash_discount_terms_code' => 'Z000',
                    'division_code' => 'D1',
                ],
                'zCE' => $ce,
            ];

            // Crear la respuesta en el formato esperado
            $response = [
                'success' => true,
                'error' => null,
                'data' => [$customer],
                'count' => 1,
                'processing_conditions' => [
                    'returned_query_hits_number_value' => 1,
                    'more_hits_available_indicator' => false,
                    'last_returned_object_id' => '00163E1002F91EE6AE9441A2270E84E2',
                ],
            ];

            return $response;
        }

        // Datos de ejemplo para otros CEs (formato simplificado pero manteniendo la estructura)
        $customer = [
            'uuid' => '00163e10-02f9-1ee7-86ae-7e8d16f8d608',
            'internal_id' => '80020',
            'external_id' => '0000080020',
            'category_code' => '1',
            'life_cycle_status_code' => '2',
            'organisation' => [
                'first_line_name' => 'MARIA RODRIGUEZ',
            ],
            'address_information' => [
                'address' => [
                    'postal_address' => [
                        'country_code' => 'PE',
                        'city_name' => 'LIMA',
                        'street_name' => 'AV. JAVIER PRADO ESTE 456',
                        'street_postal_code' => '15024',
                    ],
                    'email' => [
                        'uri' => 'maria.rodriguez@example.com',
                    ],
                    'telephone' => [
                        [
                            'formatted_number_description' => '+51 987654322',
                            'mobile_phone_number_indicator' => true,
                        ],
                    ],
                ],
            ],
            'sales_arrangement' => [
                'sales_organisation_id' => 'DM07',
                'distribution_channel_code' => 'D4',
                'sales_group_id' => 'D03',
                'currency_code' => 'PEN',
                'customer_group_code' => 'T1',
            ],
            'zCE' => $ce,
        ];

        return [
            'success' => true,
            'error' => null,
            'data' => [$customer],
            'count' => 1,
            'processing_conditions' => [
                'returned_query_hits_number_value' => 1,
                'more_hits_available_indicator' => false,
            ],
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
        Log::info("MockCustomerService: Buscando cliente con Pasaporte {$passport}");

        // Datos específicos para el Pasaporte 37429823 (basado en la estructura real de la respuesta SOAP)
        if ($passport === '37429823') {
            $customer = [
                'uuid' => '00163e10-02f9-1ee6-ae94-41a2270e84e2',
                'internal_id' => '1000140',
                'external_id' => '500000080',
                'change_state_id' => '20230613225505.0611240',
                'system_administrative_data' => [
                    'creation_date_time' => '2016-12-02T16:17:12.820781Z',
                    'creation_identity_uuid' => '00163e10-02f9-1ee6-ac81-41554fd34cd6',
                    'last_change_date_time' => '2023-06-13T22:55:05.061124Z',
                    'last_change_identity_uuid' => '00163e10-02fb-1ed5-bdd5-7af3cf02f977',
                ],
                'category_code' => '1',
                'prospect_indicator' => true,
                'life_cycle_status_code' => '2',
                'organisation' => [
                    'company_legal_form_code' => 'Z1',
                    'first_line_name' => 'JOHN MICHAEL SMITH',
                ],
                'contact_allowed_code' => '1',
                'legal_competence_indicator' => true,
                'industrial_sector_code' => 'ZM007',
                'address_information' => [
                    'uuid' => '00163e10-02f9-1ee6-ae94-41a2270f04e2',
                    'current_address_snapshot_uuid' => '00163e10-02f9-1ee6-ae95-5911f4b44d9a',
                    'address_usage' => [
                        'address_usage_code' => 'XXDEFAULT',
                    ],
                    'address' => [
                        'correspondence_language_code' => 'EN',
                        'email' => [
                            'uri' => 'john.smith@example.com',
                        ],
                        'postal_address' => [
                            'country_code' => 'PE',
                            'region_code' => '25',
                            'region_description' => 'Lima Region',
                            'city_name' => 'LIMA',
                            'street_name' => 'AV. JAVIER PRADO ESTE 789',
                            'time_zone_code' => 'UTC-5',
                        ],
                        'telephone' => [
                            [
                                'formatted_number_description' => '+51 987654323',
                                'mobile_phone_number_indicator' => true,
                            ],
                            [
                                'formatted_number_description' => '+51 1 456-7890',
                                'mobile_phone_number_indicator' => false,
                            ],
                        ],
                        'formatted_address' => [
                            'formatted_address_description' => 'JOHN MICHAEL SMITH / AV. JAVIER PRADO ESTE 789 / LIMA / PE',
                            'formatted_postal_address_description' => 'AV. JAVIER PRADO ESTE 789 / LIMA / PE',
                            'formatted_address' => [
                                'first_line_description' => 'JOHN MICHAEL SMITH',
                                'second_line_description' => 'AV. JAVIER PRADO ESTE 789',
                                'third_line_description' => 'LIMA',
                                'fourth_line_description' => 'Peru',
                            ],
                        ],
                    ],
                ],
                'role' => [
                    'role_code' => 'BUP002',
                ],
                'role_description' => 'Prospect',
                'direct_responsibility' => [
                    'party_role_code' => '142',
                    'employee_id' => '3',
                    'employee_uuid' => '00163e10-02f9-1ee6-ac81-3fca2edfecbf',
                    'validity_period' => [
                        'start_date' => '0001-01-01',
                        'end_date' => '9999-12-31',
                    ],
                    'default_indicator' => true,
                ],
                'sales_arrangement' => [
                    'sales_organisation_id' => 'DM07',
                    'distribution_channel_code' => 'D4',
                    'sales_group_id' => 'D03',
                    'complete_delivery_requested_indicator' => true,
                    'currency_code' => 'PEN',
                    'customer_group_code' => 'T1',
                    'cash_discount_terms_code' => 'Z000',
                    'division_code' => 'D1',
                ],
                'zPasaporte' => $passport,
            ];

            // Crear la respuesta en el formato esperado
            $response = [
                'success' => true,
                'error' => null,
                'data' => [$customer],
                'count' => 1,
                'processing_conditions' => [
                    'returned_query_hits_number_value' => 1,
                    'more_hits_available_indicator' => false,
                    'last_returned_object_id' => '00163E1002F91EE6AE9441A2270E84E2',
                ],
            ];

            return $response;
        }

        // Datos de ejemplo para otros Pasaportes (formato simplificado pero manteniendo la estructura)
        $customer = [
            'uuid' => '00163e10-02f9-1ee7-86ae-7e8d16f8d608',
            'internal_id' => '80021',
            'external_id' => '0000080021',
            'category_code' => '1',
            'life_cycle_status_code' => '2',
            'organisation' => [
                'first_line_name' => 'JOHN SMITH',
            ],
            'address_information' => [
                'address' => [
                    'postal_address' => [
                        'country_code' => 'PE',
                        'city_name' => 'LIMA',
                        'street_name' => 'AV. JAVIER PRADO ESTE 789',
                        'street_postal_code' => '15024',
                    ],
                    'email' => [
                        'uri' => 'john.smith@example.com',
                    ],
                    'telephone' => [
                        [
                            'formatted_number_description' => '+51 987654323',
                            'mobile_phone_number_indicator' => true,
                        ],
                    ],
                ],
            ],
            'sales_arrangement' => [
                'sales_organisation_id' => 'DM07',
                'distribution_channel_code' => 'D4',
                'sales_group_id' => 'D03',
                'currency_code' => 'PEN',
                'customer_group_code' => 'T1',
            ],
            'zPasaporte' => $passport,
        ];

        return [
            'success' => true,
            'error' => null,
            'data' => [$customer],
            'count' => 1,
            'processing_conditions' => [
                'returned_query_hits_number_value' => 1,
                'more_hits_available_indicator' => false,
            ],
        ];
    }
}
