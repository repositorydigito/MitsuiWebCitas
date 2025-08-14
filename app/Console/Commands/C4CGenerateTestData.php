<?php

namespace App\Console\Commands;

use Faker\Factory as Faker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class C4CGenerateTestData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:generate-test-data
                            {count=10 : Número de clientes a generar}
                            {--output=storage/c4c/test-data.json : Ruta del archivo de salida}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generar datos de prueba para C4C';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = (int) $this->argument('count');
        $outputPath = $this->option('output');

        // Crear el directorio si no existe
        $directory = dirname($outputPath);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $this->info("Generando {$count} clientes de prueba...");

        // Crear instancia de Faker
        $faker = Faker::create('es_PE');

        // Generar datos de prueba
        $customers = [];

        for ($i = 0; $i < $count; $i++) {
            // Determinar tipo de cliente (persona o empresa)
            $isCompany = $faker->boolean(30); // 30% de probabilidad de ser empresa

            // Generar datos básicos
            $uuid = $faker->uuid;
            $internalId = $faker->numberBetween(80000, 90000);
            $externalId = str_pad($internalId, 10, '0', STR_PAD_LEFT);

            // Generar datos específicos según el tipo de cliente
            if ($isCompany) {
                // Empresa
                $name = $faker->company;
                $categoryCode = '2';
                $documentType = 'RUC';
                $documentNumber = '20'.$faker->numerify('#########');
            } else {
                // Persona
                $name = $faker->name;
                $categoryCode = '1';

                // Determinar tipo de documento
                $documentTypes = ['DNI', 'CE', 'PASSPORT'];
                $documentType = $faker->randomElement($documentTypes);

                switch ($documentType) {
                    case 'DNI':
                        $documentNumber = $faker->numerify('########');
                        break;
                    case 'CE':
                        $documentNumber = $faker->numerify('#######');
                        break;
                    case 'PASSPORT':
                        $documentNumber = $faker->bothify('??######');
                        break;
                }
            }

            // Crear estructura de cliente
            $customer = [
                'uuid' => $uuid,
                'internal_id' => (string) $internalId,
                'external_id' => $externalId,
                'change_state_id' => date('Ymd').$faker->numerify('.######'),
                'system_administrative_data' => [
                    'creation_date_time' => $faker->dateTimeThisYear->format('Y-m-d\TH:i:s.u\Z'),
                    'creation_identity_uuid' => $faker->uuid,
                    'last_change_date_time' => $faker->dateTimeThisMonth->format('Y-m-d\TH:i:s.u\Z'),
                    'last_change_identity_uuid' => $faker->uuid,
                ],
                'category_code' => $categoryCode,
                'prospect_indicator' => $faker->boolean(80),
                'life_cycle_status_code' => $faker->randomElement(['1', '2', '3']),
                'organisation' => [
                    'company_legal_form_code' => $isCompany ? 'Z2' : 'Z1',
                    'first_line_name' => $name,
                ],
                'contact_allowed_code' => '1',
                'legal_competence_indicator' => true,
                'industrial_sector_code' => $faker->randomElement(['ZM001', 'ZM002', 'ZM003', 'ZM007']),
                'address_information' => [
                    'uuid' => $faker->uuid,
                    'current_address_snapshot_uuid' => $faker->uuid,
                    'address_usage' => [
                        'address_usage_code' => 'XXDEFAULT',
                    ],
                    'address' => [
                        'correspondence_language_code' => 'ES',
                        'email' => [
                            'uri' => $faker->email,
                        ],
                        'postal_address' => [
                            'country_code' => 'PE',
                            'region_code' => '25',
                            'region_description' => 'Lima Region',
                            'city_name' => $faker->city,
                            'street_name' => $faker->streetAddress,
                            'time_zone_code' => 'UTC-5',
                        ],
                        'telephone' => [
                            [
                                'formatted_number_description' => '+51 '.$faker->numerify('#######'),
                                'mobile_phone_number_indicator' => true,
                            ],
                            [
                                'formatted_number_description' => '+51 1 '.$faker->numerify('###-####'),
                                'mobile_phone_number_indicator' => false,
                            ],
                        ],
                        'formatted_address' => [
                            'formatted_address_description' => $name.' / '.$faker->streetAddress.' / '.$faker->city.' / PE',
                            'formatted_postal_address_description' => $faker->streetAddress.' / '.$faker->city.' / PE',
                            'formatted_address' => [
                                'first_line_description' => $name,
                                'second_line_description' => $faker->streetAddress,
                                'third_line_description' => $faker->city,
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
                    'employee_id' => $faker->numberBetween(1, 10),
                    'employee_uuid' => $faker->uuid,
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
            ];

            // Agregar documento según el tipo
            switch ($documentType) {
                case 'DNI':
                    $customer['zDNI'] = $documentNumber;
                    break;
                case 'RUC':
                    $customer['zRuc'] = $documentNumber;
                    break;
                case 'CE':
                    $customer['zCE'] = $documentNumber;
                    break;
                case 'PASSPORT':
                    $customer['zPasaporte'] = $documentNumber;
                    break;
            }

            $customers[] = $customer;

            $index = $i + 1;
            $this->info("Cliente {$index} generado: {$name} ({$documentType}: {$documentNumber})");
        }

        // Crear estructura de respuesta
        $response = [
            'success' => true,
            'error' => null,
            'data' => $customers,
            'count' => count($customers),
            'processing_conditions' => [
                'returned_query_hits_number_value' => count($customers),
                'more_hits_available_indicator' => false,
                'last_returned_object_id' => $customers[count($customers) - 1]['uuid'],
            ],
        ];

        // Guardar datos en archivo JSON
        File::put($outputPath, json_encode($response, JSON_PRETTY_PRINT));

        $this->info("Datos de prueba generados y guardados en {$outputPath}");

        return 0;
    }
}
