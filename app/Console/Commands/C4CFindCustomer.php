<?php

namespace App\Console\Commands;

use App\Services\C4C\CustomerService;
use Illuminate\Console\Command;

class C4CFindCustomer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:find-customer
                            {type : Tipo de documento (DNI, RUC, CE, PASSPORT)}
                            {number : Número de documento}
                            {--real : Forzar el uso del servicio real en lugar del mock}
                            {--mock : Forzar el uso del servicio mock en lugar del real}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buscar un cliente en C4C por tipo y número de documento';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $type = strtoupper($this->argument('type'));
        $number = $this->argument('number');
        $forceReal = $this->option('real');
        $forceMock = $this->option('mock');

        $this->info("Buscando cliente con $type: $number");

        // Determinar qué servicio usar
        if ($forceMock) {
            // Si se fuerza el uso del mock, usar el mock directamente
            $this->info("Usando servicio mock (forzado por opción --mock)...");
            $mockService = new \App\Services\C4C\MockCustomerService();

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
                default:
                    $this->error("Tipo de documento no válido. Use DNI, RUC, CE o PASSPORT.");
                    return 1;
            }
        } elseif ($forceReal || ($type === 'RUC' && $number === '20605414410' && $forceReal)) {
            // Si se fuerza el uso del servicio real, o si es el RUC específico y se fuerza el real
            try {
                $customerService = app(CustomerService::class);
                $this->info("Usando servicio real (forzado por opción --real)...");

                switch ($type) {
                    case 'DNI':
                        $result = $customerService->findByDNI($number);
                        break;
                    case 'RUC':
                        $result = $customerService->findByRUC($number);
                        break;
                    case 'CE':
                        $result = $customerService->findByCE($number);
                        break;
                    case 'PASSPORT':
                        $result = $customerService->findByPassport($number);
                        break;
                    default:
                        $this->error("Tipo de documento no válido. Use DNI, RUC, CE o PASSPORT.");
                        return 1;
                }

                // Si el servicio real falló y no se forzó el uso del real, usar el mock
                if (!$result['success'] && !$forceReal) {
                    $this->warn("El servicio real falló. Usando servicio mock...");
                    $mockService = new \App\Services\C4C\MockCustomerService();

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
                }
            } catch (\Exception $e) {
                if ($forceReal) {
                    // Si se forzó el uso del real, mostrar el error y terminar
                    $this->error("Error al usar el servicio real: " . $e->getMessage());
                    return 1;
                } else {
                    // Si no se forzó, usar el mock como fallback
                    $this->error("Error al usar el servicio real: " . $e->getMessage());
                    $this->warn("Usando servicio mock como fallback...");

                    $mockService = new \App\Services\C4C\MockCustomerService();

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
                        default:
                            $this->error("Tipo de documento no válido. Use DNI, RUC, CE o PASSPORT.");
                            return 1;
                    }
                }
            }
        } else {
            // Comportamiento normal (sin forzar)
            // Para el RUC específico 20605414410, usar directamente el mock
            if ($type === 'RUC' && $number === '20605414410') {
                $this->info("Usando servicio mock para RUC específico...");
                $mockService = new \App\Services\C4C\MockCustomerService();
                $result = $mockService->findByRUC($number);
            } else {
                // Para otros casos, intentar usar el servicio real primero
                try {
                    $customerService = app(CustomerService::class);
                    $this->info("Usando servicio real...");

                    switch ($type) {
                        case 'DNI':
                            $result = $customerService->findByDNI($number);
                            break;
                        case 'RUC':
                            $result = $customerService->findByRUC($number);
                            break;
                        case 'CE':
                            $result = $customerService->findByCE($number);
                            break;
                        case 'PASSPORT':
                            $result = $customerService->findByPassport($number);
                            break;
                        default:
                            $this->error("Tipo de documento no válido. Use DNI, RUC, CE o PASSPORT.");
                            return 1;
                    }

                    // Si el servicio real falló, usar el mock
                    if (!$result['success']) {
                        $this->warn("El servicio real falló. Usando servicio mock...");
                        $mockService = new \App\Services\C4C\MockCustomerService();

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
                    }
                } catch (\Exception $e) {
                    $this->error("Error al usar el servicio real: " . $e->getMessage());
                    $this->warn("Usando servicio mock...");

                    $mockService = new \App\Services\C4C\MockCustomerService();

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
                        default:
                            $this->error("Tipo de documento no válido. Use DNI, RUC, CE o PASSPORT.");
                            return 1;
                    }
                }
            }
        }

        if ($result['success'] && !empty($result['data'])) {
            $this->info("¡Cliente encontrado!");

            foreach ($result['data'] as $index => $customer) {
                $this->info("\n--- Cliente " . ($index + 1) . " ---");

                // Información básica
                $this->info("<fg=green;options=bold>Información Básica:</>");
                $this->info("UUID: " . ($customer['uuid'] ?? 'N/A'));
                $this->info("ID Interno: " . ($customer['internal_id'] ?? 'N/A'));
                $this->info("ID Externo: " . ($customer['external_id'] ?? 'N/A'));
                $this->info("Nombre: " . ($customer['name'] ?? 'N/A'));
                $this->info("Categoría: " . ($customer['category_code'] ?? 'N/A'));
                $this->info("Estado: " . ($customer['life_cycle_status'] ?? 'N/A'));

                // Información de identificación
                if (isset($customer['identification']) && !empty($customer['identification'])) {
                    $this->info("\n<fg=green;options=bold>Documentos de Identidad:</>");
                    foreach ($customer['identification'] as $docType => $docValue) {
                        $this->info(strtoupper($docType) . ": " . $docValue);
                    }
                }

                // Información de contacto
                if (isset($customer['contact']) && !empty($customer['contact'])) {
                    $this->info("\n<fg=green;options=bold>Información de Contacto:</>");

                    if (isset($customer['contact']['email'])) {
                        $this->info("Email: " . $customer['contact']['email']);
                    }

                    if (isset($customer['contact']['phones']) && !empty($customer['contact']['phones'])) {
                        $this->info("Teléfonos:");
                        foreach ($customer['contact']['phones'] as $phone) {
                            $type = isset($phone['is_mobile']) && $phone['is_mobile'] ? 'Móvil' : 'Fijo';
                            $this->info("  - " . $phone['number'] . " (" . $type . ")");
                        }
                    }
                }

                // Información de dirección
                if (isset($customer['address']) && !empty($customer['address'])) {
                    $this->info("\n<fg=green;options=bold>Dirección:</>");

                    if (isset($customer['address']['formatted'])) {
                        $this->info("Dirección completa: " . $customer['address']['formatted']);
                    } else {
                        $street = $customer['address']['street'] ?? '';
                        $city = $customer['address']['city'] ?? '';
                        $region = $customer['address']['region_description'] ?? '';
                        $country = $customer['address']['country'] ?? '';

                        $this->info("Calle: " . $street);
                        $this->info("Ciudad: " . $city);
                        $this->info("Región: " . $region);
                        $this->info("País: " . $country);
                    }
                }

                // Información de ventas
                if (isset($customer['sales']) && !empty($customer['sales'])) {
                    $this->info("\n<fg=green;options=bold>Información de Ventas:</>");
                    $this->info("Organización: " . ($customer['sales']['organisation_id'] ?? 'N/A'));
                    $this->info("Canal de distribución: " . ($customer['sales']['distribution_channel'] ?? 'N/A'));
                    $this->info("Grupo de ventas: " . ($customer['sales']['group_id'] ?? 'N/A'));
                    $this->info("Moneda: " . ($customer['sales']['currency'] ?? 'N/A'));
                    $this->info("Grupo de cliente: " . ($customer['sales']['customer_group'] ?? 'N/A'));
                }
            }

            $this->info("\n<fg=yellow;options=bold>Total de clientes encontrados: " . count($result['data']) . "</>");
        } else {
            $this->error("Cliente no encontrado o error: " . ($result['error'] ?? 'Error desconocido'));
        }

        return 0;
    }
}
