<?php

namespace App\Services;

use App\Services\C4C\CustomerService;
use Exception;
use Illuminate\Support\Facades\Log;
use SoapClient;

class CustomerValidationService
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Valida un cliente por documento en SAP y C4C
     * Retorna datos personales si los encuentra, o null si debe ser cliente comodín
     */
    public function validateCustomerByDocument(string $documentType, string $documentNumber): ?array
    {
        Log::info("Validando cliente con {$documentType}: {$documentNumber}");

        $sapData = null;
        $c4cData = null;

        // Consultar ambos servicios en paralelo
        try {
            $sapData = $this->validateInSAP($documentNumber);
            if ($sapData) {
                Log::info("Cliente encontrado en SAP", ['document' => $documentNumber, 'name' => $sapData['name']]);
            }
        } catch (\Exception $e) {
            Log::warning("Error consultando SAP: " . $e->getMessage());
        }

        try {
            $c4cData = $this->validateInC4C($documentType, $documentNumber);
            if ($c4cData) {
                Log::info("Cliente encontrado en C4C", ['document' => $documentNumber, 'name' => $c4cData['name'], 'c4c_id' => $c4cData['c4c_internal_id'] ?? 'NULL']);
            }
        } catch (\Exception $e) {
            Log::warning("Error consultando C4C: " . $e->getMessage());
        }

        // Priorizar C4C si tiene c4c_internal_id válido
        if ($c4cData && !empty($c4cData['c4c_internal_id'])) {
            Log::info("Usando datos de C4C (tiene c4c_internal_id)", ['document' => $documentNumber, 'c4c_id' => $c4cData['c4c_internal_id']]);
            return $c4cData;
        }

        // Si C4C no tiene c4c_internal_id pero SAP tiene datos, usar SAP
        if ($sapData) {
            Log::info("Usando datos de SAP (C4C sin c4c_internal_id)", ['document' => $documentNumber, 'name' => $sapData['name']]);
            return $sapData;
        }

        // Si C4C tiene datos pero sin c4c_internal_id, usarlo de todas formas
        if ($c4cData) {
            Log::info("Usando datos de C4C (sin c4c_internal_id)", ['document' => $documentNumber, 'name' => $c4cData['name']]);
            return $c4cData;
        }

        Log::info("Cliente no encontrado en SAP ni C4C, será cliente comodín", ['document' => $documentNumber]);
        return null;
    }

    /**
     * Valida cliente en SAP usando Z3PF_GETDATOSCLIENTE
     */
    protected function validateInSAP(string $documentNumber): ?array
    {
        try {
            $soapClient = $this->createSAPSoapClient();
            if (!$soapClient) {
                return null;
            }

            $parametros = [
                'PI_NUMDOCCLI' => $documentNumber,
            ];

            $respuesta = $soapClient->Z3PF_GETDATOSCLIENTE($parametros);

            if (isset($respuesta->PE_NOMCLI) && !empty($respuesta->PE_NOMCLI)) {
                return [
                    'source' => 'SAP',
                    'name' => $respuesta->PE_NOMCLI ?? '',
                    'email' => $respuesta->PE_CORCLI ?? null,
                    'phone' => $respuesta->PE_TELCLI ?? null,
                    'club_points' => $respuesta->PE_PUNCLU ?? null,
                    // SAP no proporciona c4c_internal_id, se asignará como comodín en el registro
                    'c4c_internal_id' => null,
                    'c4c_uuid' => null,
                ];
            }

            return null;

        } catch (Exception $e) {
            Log::warning("Error al consultar SAP para documento {$documentNumber}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Valida cliente en C4C
     */
    protected function validateInC4C(string $documentType, string $documentNumber): ?array
    {
        try {
            $c4cCustomer = $this->customerService->findByDocument($documentType, $documentNumber);

            if ($c4cCustomer) {
                return [
                    'source' => 'C4C',
                    'name' => $c4cCustomer['organisation']['first_line_name'] ?? 'Cliente C4C',
                    'email' => $c4cCustomer['address_information']['address']['email']['uri'] ?? null,
                    'phone' => $this->extractPhoneFromC4C($c4cCustomer),
                    'c4c_internal_id' => $c4cCustomer['internal_id'] ?? null,
                    'c4c_uuid' => $c4cCustomer['uuid'] ?? null,
                ];
            }

            return null;

        } catch (Exception $e) {
            Log::warning("Error al consultar C4C para documento {$documentNumber}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Crea cliente SOAP para SAP
     */
    protected function createSAPSoapClient(): ?SoapClient
    {
        try {
            $wsdlUrl = config('services.sap_3p.wsdl_url');
            $usuario = config('services.sap_3p.usuario');
            $password = config('services.sap_3p.password');

            if (empty($wsdlUrl) || empty($usuario) || empty($password)) {
                Log::warning('Configuración SAP incompleta para validación de cliente');
                return null;
            }

            // Intentar primero con WSDL local si existe
            $wsdlLocal = storage_path('wsdl/vehiculos.wsdl');
            if (file_exists($wsdlLocal)) {
                $wsdlPath = $wsdlLocal;
            } else {
                $wsdlPath = $wsdlUrl;
            }

            $opciones = [
                'trace' => true,
                'exceptions' => true,
                'cache_wsdl' => WSDL_CACHE_NONE,
                'connection_timeout' => 10,
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                    'http' => [
                        'timeout' => 10,
                    ],
                ]),
                'login' => $usuario,
                'password' => $password,
            ];

            return new SoapClient($wsdlPath, $opciones);

        } catch (Exception $e) {
            Log::warning('Error al crear cliente SOAP SAP: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extrae teléfono de la respuesta de C4C
     */
    protected function extractPhoneFromC4C(array $c4cCustomer): ?string
    {
        $phones = $c4cCustomer['address_information']['address']['telephone'] ?? [];

        if (is_array($phones) && !empty($phones)) {
            return $phones[0]['formatted_number_description'] ?? null;
        }

        return null;
    }
}