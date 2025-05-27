<?php

namespace App\Console\Commands;

use App\Services\C4C\CustomerService;
use App\Services\C4C\MockCustomerService;
use Illuminate\Console\Command;

class C4CTestAllDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:test-all-documents
                            {--real : Forzar el uso del servicio real en lugar del mock}
                            {--mock : Forzar el uso del servicio mock en lugar del real}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar todos los tipos de documentos en C4C';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $forceReal = $this->option('real');
        $forceMock = $this->option('mock');

        // Documentos de prueba
        $documents = [
            'DNI' => '40359482',
            'RUC' => '20558638223',
            'CE' => '73532531',
            'PASSPORT' => '37429823',
        ];

        // Determinar qué servicio usar
        if ($forceMock) {
            $this->info('Usando servicio mock (forzado por opción --mock)...');
            $service = new MockCustomerService;
        } elseif ($forceReal) {
            $this->info('Usando servicio real (forzado por opción --real)...');
            $service = app(CustomerService::class);
        } else {
            $this->info('Usando servicio real por defecto...');
            $service = app(CustomerService::class);
        }

        // Probar cada tipo de documento
        foreach ($documents as $type => $number) {
            $this->info("\n=== Probando $type: $number ===");

            try {
                $result = null;

                switch ($type) {
                    case 'DNI':
                        $result = $service->findByDNI($number);
                        break;
                    case 'RUC':
                        $result = $service->findByRUC($number);
                        break;
                    case 'CE':
                        $result = $service->findByCE($number);
                        break;
                    case 'PASSPORT':
                        $result = $service->findByPassport($number);
                        break;
                }

                if ($result['success'] && ! empty($result['data'])) {
                    $this->info('¡Cliente encontrado!');

                    foreach ($result['data'] as $index => $customer) {
                        $this->info("\n--- Cliente ".($index + 1).' ---');

                        // Información básica
                        $this->info('<fg=green;options=bold>Información Básica:</>');
                        $this->info('UUID: '.($customer['uuid'] ?? $customer['uuid'] ?? 'N/A'));
                        $this->info('ID Interno: '.($customer['internal_id'] ?? 'N/A'));
                        $this->info('ID Externo: '.($customer['external_id'] ?? 'N/A'));

                        // Nombre de la organización
                        if (isset($customer['organisation']) && isset($customer['organisation']['first_line_name'])) {
                            $this->info('Nombre: '.$customer['organisation']['first_line_name']);
                        } elseif (isset($customer['name'])) {
                            $this->info('Nombre: '.$customer['name']);
                        }

                        $this->info('Categoría: '.($customer['category_code'] ?? 'N/A'));
                        $this->info('Estado: '.($customer['life_cycle_status_code'] ?? $customer['life_cycle_status'] ?? 'N/A'));

                        // Información de identificación
                        $this->info("\n<fg=green;options=bold>Documentos de Identidad:</>");

                        // Buscar en diferentes lugares donde podría estar la información de identificación
                        $identifications = [];

                        // Buscar en el campo zDNI, zRuc, zCE, zPasaporte
                        if (isset($customer['zDNI'])) {
                            $identifications['DNI'] = $customer['zDNI'];
                        }
                        if (isset($customer['zRuc'])) {
                            $identifications['RUC'] = $customer['zRuc'];
                        }
                        if (isset($customer['zCE'])) {
                            $identifications['CE'] = $customer['zCE'];
                        }
                        if (isset($customer['zPasaporte'])) {
                            $identifications['PASSPORT'] = $customer['zPasaporte'];
                        }

                        // Buscar en el campo identification
                        if (isset($customer['identification'])) {
                            foreach ($customer['identification'] as $docType => $docValue) {
                                $identifications[strtoupper($docType)] = $docValue;
                            }
                        }

                        if (! empty($identifications)) {
                            foreach ($identifications as $docType => $docValue) {
                                $this->info($docType.': '.$docValue);
                            }
                        } else {
                            $this->info('No se encontraron documentos de identidad');
                        }

                        // Información de contacto
                        $this->info("\n<fg=green;options=bold>Información de Contacto:</>");

                        // Buscar email
                        $email = null;
                        if (isset($customer['contact']) && isset($customer['contact']['email'])) {
                            $email = $customer['contact']['email'];
                        } elseif (isset($customer['address_information']) && isset($customer['address_information']['address']) &&
                                isset($customer['address_information']['address']['email']) &&
                                isset($customer['address_information']['address']['email']['uri'])) {
                            $email = $customer['address_information']['address']['email']['uri'];
                        }

                        if ($email) {
                            $this->info('Email: '.$email);
                        } else {
                            $this->info('Email: N/A');
                        }

                        // Buscar teléfonos
                        $phones = [];
                        if (isset($customer['contact']) && isset($customer['contact']['phones'])) {
                            $phones = $customer['contact']['phones'];
                        } elseif (isset($customer['address_information']) && isset($customer['address_information']['address']) &&
                                isset($customer['address_information']['address']['telephone'])) {
                            $phones = $customer['address_information']['address']['telephone'];
                        }

                        if (! empty($phones)) {
                            $this->info('Teléfonos:');
                            foreach ($phones as $phone) {
                                $number = isset($phone['number']) ? $phone['number'] :
                                        (isset($phone['formatted_number_description']) ? $phone['formatted_number_description'] : 'N/A');

                                $isMobile = isset($phone['is_mobile']) ? $phone['is_mobile'] :
                                        (isset($phone['mobile_phone_number_indicator']) ? $phone['mobile_phone_number_indicator'] : false);

                                $type = $isMobile ? 'Móvil' : 'Fijo';
                                $this->info('  - '.$number.' ('.$type.')');
                            }
                        } else {
                            $this->info('Teléfonos: N/A');
                        }

                        // Información de dirección
                        $this->info("\n<fg=green;options=bold>Dirección:</>");

                        // Buscar dirección formateada
                        $formattedAddress = null;
                        if (isset($customer['address_information']) && isset($customer['address_information']['address']) &&
                                isset($customer['address_information']['address']['formatted_address']) &&
                                isset($customer['address_information']['address']['formatted_address']['formatted_address_description'])) {
                            $formattedAddress = $customer['address_information']['address']['formatted_address']['formatted_address_description'];
                        }

                        if ($formattedAddress) {
                            $this->info('Dirección completa: '.$formattedAddress);
                        } else {
                            // Buscar componentes de dirección
                            $street = null;
                            $city = null;
                            $region = null;
                            $country = null;

                            if (isset($customer['address']) && isset($customer['address']['street'])) {
                                $street = $customer['address']['street'];
                                $city = $customer['address']['city'] ?? 'N/A';
                                $region = $customer['address']['region_description'] ?? 'N/A';
                                $country = $customer['address']['country'] ?? 'N/A';
                            } elseif (isset($customer['address_information']) && isset($customer['address_information']['address']) &&
                                    isset($customer['address_information']['address']['postal_address'])) {
                                $postalAddress = $customer['address_information']['address']['postal_address'];
                                $street = $postalAddress['street_name'] ?? 'N/A';
                                $city = $postalAddress['city_name'] ?? 'N/A';
                                $region = $postalAddress['region_description'] ?? 'N/A';
                                $country = $postalAddress['country_code'] ?? 'N/A';
                            }

                            $this->info('Calle: '.($street ?? 'N/A'));
                            $this->info('Ciudad: '.($city ?? 'N/A'));
                            $this->info('Región: '.($region ?? 'N/A'));
                            $this->info('País: '.($country ?? 'N/A'));
                        }

                        // Información de ventas
                        $this->info("\n<fg=green;options=bold>Información de Ventas:</>");

                        // Buscar información de ventas
                        $salesInfo = null;
                        if (isset($customer['sales'])) {
                            $salesInfo = $customer['sales'];
                        } elseif (isset($customer['sales_arrangement'])) {
                            $salesInfo = $customer['sales_arrangement'];
                        }

                        if ($salesInfo) {
                            $this->info('Organización: '.($salesInfo['organisation_id'] ?? $salesInfo['sales_organisation_id'] ?? 'N/A'));
                            $this->info('Canal de distribución: '.($salesInfo['distribution_channel'] ?? $salesInfo['distribution_channel_code'] ?? 'N/A'));
                            $this->info('Grupo de ventas: '.($salesInfo['group_id'] ?? $salesInfo['sales_group_id'] ?? 'N/A'));
                            $this->info('Moneda: '.($salesInfo['currency'] ?? $salesInfo['currency_code'] ?? 'N/A'));
                            $this->info('Grupo de cliente: '.($salesInfo['customer_group'] ?? $salesInfo['customer_group_code'] ?? 'N/A'));
                        } else {
                            $this->info('No se encontró información de ventas');
                        }
                    }

                    $this->info("\n<fg=yellow;options=bold>Total de clientes encontrados: ".count($result['data']).'</>');
                } else {
                    $this->error('Cliente no encontrado o error: '.($result['error'] ?? 'Error desconocido'));
                }
            } catch (\Exception $e) {
                $this->error('Error al buscar cliente: '.$e->getMessage());

                // Si se forzó el uso del servicio real, no intentar con el mock
                if ($forceReal) {
                    continue;
                }

                // Intentar con el mock como fallback
                $this->warn('Intentando con servicio mock...');
                $mockService = new MockCustomerService;

                try {
                    $result = null;

                    switch ($type) {
                        case 'DNI':
                            $result = $mockService->findByDNI($number);
                            break;
                        case 'RUC':
                            $result = $mockService->findByRUC($number);
                            break;
                        case 'CE':
                            $result = $mockService->findByCE($number);
                            break;
                        case 'PASSPORT':
                            $result = $mockService->findByPassport($number);
                            break;
                    }

                    if ($result['success'] && ! empty($result['data'])) {
                        $this->info('¡Cliente encontrado en mock!');
                        $this->info('Nombre: '.($result['data'][0]['organisation']['first_line_name'] ?? $result['data'][0]['name'] ?? 'N/A'));
                    } else {
                        $this->error('Cliente no encontrado en mock: '.($result['error'] ?? 'Error desconocido'));
                    }
                } catch (\Exception $e) {
                    $this->error('Error al buscar cliente en mock: '.$e->getMessage());
                }
            }
        }

        return 0;
    }
}
