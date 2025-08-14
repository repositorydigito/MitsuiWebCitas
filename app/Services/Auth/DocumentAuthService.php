<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\CustomerValidationService;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DocumentAuthService
{
    protected $customerValidationService;

    public function __construct(CustomerValidationService $customerValidationService)
    {
        $this->customerValidationService = $customerValidationService;
    }

    /**
     * Autentica usuario por documento siguiendo el flujo diseñado
     */
    public function authenticateByDocument(
        string $documentType,
        string $documentNumber,
        ?string $password = null
    ): array {
        try {
            // PASO 1: Buscar en BD Local
            $localUser = User::byDocument($documentType, $documentNumber)->first();

            if ($localUser) {
                // Usuario existe localmente
                if ($password && Hash::check($password, $localUser->password)) {
                    return [
                        'success' => true,
                        'user' => $localUser,
                        'action' => 'login',
                        'message' => 'Login exitoso',
                    ];
                } elseif ($password) {
                    return [
                        'success' => false,
                        'action' => 'error',
                        'message' => 'Contraseña incorrecta',
                    ];
                } else {
                    // Si es cliente comodín, intentar actualizar con datos de SAP/C4C
                    if ($localUser->is_comodin) {
                        $updatedUser = $this->tryUpdateComodinWithRealData($localUser, $documentType, $documentNumber);
                        if ($updatedUser) {
                            return [
                                'success' => true,
                                'user' => $updatedUser,
                                'action' => 'request_password',
                                'message' => 'Usuario actualizado con datos reales, ingrese contraseña',
                                'updated_from_comodin' => true,
                            ];
                        }
                    }
                    
                    return [
                        'success' => true,
                        'user' => $localUser,
                        'action' => 'request_password',
                        'message' => 'Usuario encontrado, ingrese contraseña',
                    ];
                }
            }

            // PASO 2: Usuario NO existe localmente - NO crear, solo informar
            return [
                'success' => false,
                'action' => 'error',
                'message' => 'Usuario no encontrado. Por favor, regístrese primero.',
            ];

        } catch (Exception $e) {
            Log::error('Error en authenticateByDocument', [
                'document_type' => $documentType,
                'document_number' => $documentNumber,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'action' => 'error',
                'message' => 'Error interno del sistema',
            ];
        }
    }

    /**
     * Maneja la creación de usuario nuevo validando en SAP/C4C
     */
    protected function handleNewUserFromSAPOrC4C(string $documentType, string $documentNumber): array
    {
        try {
            // Validar en SAP/C4C usando el nuevo servicio
            $customerData = $this->customerValidationService->validateCustomerByDocument($documentType, $documentNumber);

            if ($customerData) {
                // Cliente encontrado en SAP o C4C - crear usuario con datos reales
                $userData = $this->extractUserDataFromValidation($customerData, $documentType, $documentNumber);
                $user = $this->createUserFromValidatedData($userData);

                return [
                    'success' => true,
                    'user' => $user,
                    'action' => 'create_password',
                    'message' => "Cliente encontrado en {$customerData['source']}, configure su contraseña",
                    'customer_data' => $customerData,
                ];
            } else {
                // Cliente NO encontrado - crear usuario comodín
                $user = $this->createComodinUser($documentType, $documentNumber);

                return [
                    'success' => true,
                    'user' => $user,
                    'action' => 'create_password',
                    'message' => 'Registrando nuevo cliente, configure su contraseña',
                    'is_comodin' => true,
                ];
            }

        } catch (Exception $e) {
            Log::error('Error al validar cliente en SAP/C4C', [
                'document_type' => $documentType,
                'document_number' => $documentNumber,
                'error' => $e->getMessage(),
            ]);

            // En caso de error, crear usuario comodín como fallback
            $user = $this->createComodinUser($documentType, $documentNumber);

            return [
                'success' => true,
                'user' => $user,
                'action' => 'create_password',
                'message' => 'Error de conectividad, registrando como cliente temporal',
                'is_comodin' => true,
            ];
        }
    }

    /**
     * Extrae datos del usuario desde respuesta de validación (SAP o C4C)
     */
    protected function extractUserDataFromValidation(array $customerData, string $documentType, string $documentNumber): array
    {
        return [
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'name' => $customerData['name'] ?? 'Cliente',
            'email' => $customerData['email'] ?? null,
            'phone' => $customerData['phone'] ?? null,
            'c4c_internal_id' => $customerData['c4c_internal_id'] ?? null,
            'c4c_uuid' => $customerData['c4c_uuid'] ?? null,
            'is_comodin' => false,
        ];
    }

    /**
     * Crea usuario desde datos validados (SAP o C4C)
     */
    protected function createUserFromValidatedData(array $userData): User
    {
        // Agregar contraseña temporal
        $userData['password'] = Hash::make('temp_'.uniqid());

        return User::create($userData);
    }

    /**
     * Extrae datos del usuario desde respuesta de C4C (método legacy)
     */
    protected function extractUserDataFromC4C(array $c4cCustomer, string $documentType, string $documentNumber): array
    {
        return [
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'name' => $c4cCustomer['organisation']['first_line_name'] ?? 'Cliente C4C',
            'email' => $c4cCustomer['address_information']['address']['email']['uri'] ?? null,
            'phone' => $this->extractPhoneFromC4C($c4cCustomer),
            'c4c_internal_id' => $c4cCustomer['internal_id'] ?? null,
            'c4c_uuid' => $c4cCustomer['uuid'] ?? null,
            'is_comodin' => false,
        ];
    }

    /**
     * Extrae teléfono de la respuesta de C4C (método legacy)
     */
    protected function extractPhoneFromC4C(array $c4cCustomer): ?string
    {
        $phones = $c4cCustomer['address_information']['address']['telephone'] ?? [];

        if (is_array($phones) && ! empty($phones)) {
            return $phones[0]['number'] ?? null;
        }

        return null;
    }

    /**
     * Crea usuario desde datos de C4C (método legacy)
     */
    protected function createUserFromC4CData(array $userData): User
    {
        // Agregar contraseña temporal
        $userData['password'] = Hash::make('temp_'.uniqid());

        return User::create($userData);
    }

    /**
     * Crea usuario comodín
     */
    protected function createComodinUser(string $documentType, string $documentNumber): User
    {
        return User::create([
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'name' => 'CLIENTE COMODIN',
            'email' => null,
            'phone' => null,
            'password' => Hash::make('temp_'.uniqid()),
            'c4c_internal_id' => '1200166011', // ID del cliente comodín
            'c4c_uuid' => null,
            'is_comodin' => true,
        ]);
    }

    /**
     * Intenta actualizar un cliente comodín con datos reales de SAP/C4C
     */
    protected function tryUpdateComodinWithRealData(User $comodinUser, string $documentType, string $documentNumber): ?User
    {
        try {
            Log::info("Intentando actualizar cliente comodín con datos reales", [
                'user_id' => $comodinUser->id,
                'document' => $documentNumber
            ]);

            // Validar en SAP/C4C
            $customerData = $this->customerValidationService->validateCustomerByDocument($documentType, $documentNumber);

            if ($customerData) {
                // Actualizar usuario con datos reales
                $comodinUser->update([
                    'name' => $customerData['name'],
                    'email' => $customerData['email'],
                    'phone' => $customerData['phone'],
                    'c4c_internal_id' => $customerData['c4c_internal_id'] ?? $comodinUser->c4c_internal_id,
                    'c4c_uuid' => $customerData['c4c_uuid'] ?? $comodinUser->c4c_uuid,
                    'is_comodin' => false, // Ya no es comodín
                ]);

                Log::info("Cliente comodín actualizado exitosamente", [
                    'user_id' => $comodinUser->id,
                    'source' => $customerData['source'],
                    'name' => $customerData['name']
                ]);

                return $comodinUser->fresh(); // Recargar desde BD
            }

            Log::info("No se encontraron datos reales para el cliente comodín", [
                'user_id' => $comodinUser->id,
                'document' => $documentNumber
            ]);

            return null;

        } catch (Exception $e) {
            Log::error('Error al actualizar cliente comodín', [
                'user_id' => $comodinUser->id,
                'document' => $documentNumber,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Establece contraseña para usuario existente
     */
    public function setUserPassword(User $user, string $password): bool
    {
        try {
            $user->update([
                'password' => Hash::make($password),
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Error al establecer contraseña', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
