<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\C4C\CustomerService;
use Exception;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DocumentAuthService
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
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
                    return [
                        'success' => true,
                        'user' => $localUser,
                        'action' => 'request_password',
                        'message' => 'Usuario encontrado, ingrese contraseña',
                    ];
                }
            }

            // PASO 2: Usuario NO existe localmente, buscar en C4C
            return $this->handleNewUserFromC4C($documentType, $documentNumber);

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
     * Maneja la creación de usuario nuevo desde C4C
     */
    protected function handleNewUserFromC4C(string $documentType, string $documentNumber): array
    {
        try {
            // Buscar en C4C
            $c4cCustomer = $this->customerService->findByDocument($documentType, $documentNumber);

            if ($c4cCustomer) {
                // Cliente existe en C4C - crear usuario real
                $userData = $this->extractUserDataFromC4C($c4cCustomer, $documentType, $documentNumber);
                $user = $this->createUserFromC4CData($userData);

                return [
                    'success' => true,
                    'user' => $user,
                    'action' => 'create_password',
                    'message' => 'Cliente encontrado en C4C, configure su contraseña',
                    'c4c_data' => $userData,
                ];
            } else {
                // Cliente NO existe en C4C - crear usuario comodín
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
            Log::error('Error al consultar C4C', [
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
     * Extrae datos del usuario desde respuesta de C4C
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
     * Extrae teléfono de la respuesta de C4C
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
     * Crea usuario desde datos de C4C
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
            'c4c_internal_id' => '99911999', // ID del cliente comodín
            'c4c_uuid' => null,
            'is_comodin' => true,
        ]);
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
