<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Pages\Auth\Register as BaseRegister;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Placeholder;
use Filament\Http\Responses\Auth\LoginResponse;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;

class Register extends BaseRegister
{
    protected static string $view = 'filament.pages.auth.corporate-register';

    protected function getLoginAction(): Action
    {
        return Action::make('login')
            ->label('Inicia sesión')
            ->url(filament()->getLoginUrl())
            ->color('gray');
    }

    protected function hasLoginAction(): bool
    {
        return true;
    }

    public function register(): ?RegistrationResponse
    {
        $user = $this->wrapInDatabaseTransaction(function () {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeRegister($data);

            $this->callHook('beforeRegister');

            $user = $this->handleRegistration($data);

            $this->form->model($user)->saveRelationships();

            $this->callHook('afterRegister');

            // Asignar rol "Usuario" automáticamente
            $user->assignRole('Usuario');

            return $user;
        });

        // Login automático tras registro
        \Auth::login($user);
        // Devolver respuesta estándar de Filament, asegurando el tipo correcto
        /** @var RegistrationResponse $response */
        $response = app(\Filament\Http\Responses\Auth\RegistrationResponse::class);
        return $response;
    }

    public function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->schema([
                        Select::make('document_type')
                            ->label('Tipo de Documento')
                            ->options([
                                'DNI' => 'DNI',
                                'RUC' => 'RUC',
                                'CE' => 'Carné de Extranjería',
                            ])
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Limpiar número cuando cambia el tipo
                                $set('document_number', '');
                            }),
                        TextInput::make('document_number')
                            ->label('Número de Documento')
                            ->required()
                            ->placeholder(function (callable $get) {
                                $type = $get('document_type');

                                return match ($type) {
                                    'DNI' => 'Ej: 12345678',
                                    'RUC' => 'Ej: 20123456789',
                                    'CE' => 'Ej: 123456789',
                                    default => 'Seleccione tipo de documento',
                                };
                            })
                            ->numeric()
                            ->rule(function (callable $get) {
                                $type = $get('document_type');
                                
                                return match ($type) {
                                    'DNI' => 'regex:/^[0-9]{8}$/',
                                    'RUC' => 'regex:/^[0-9]{11}$/',
                                    'CE' => 'regex:/^[0-9]{9}$/',
                                    default => 'numeric',
                                };
                            })
                            ->maxLength(function (callable $get) {
                                $type = $get('document_type');

                                return match ($type) {
                                    'DNI' => 8,
                                    'RUC' => 11,
                                    'CE' => 9,
                                    default => 11,
                                };
                            })
                            ->minLength(function (callable $get) {
                                $type = $get('document_type');

                                return match ($type) {
                                    'DNI' => 8,
                                    'RUC' => 11,
                                    'CE' => 9,
                                    default => 1,
                                };
                            })
                            ->validationMessages([
                                'regex' => function (callable $get) {
                                    $type = $get('document_type');
                                    
                                    return match ($type) {
                                        'DNI' => 'El DNI debe tener exactamente 8 números',
                                        'RUC' => 'El RUC debe tener exactamente 11 números',
                                        'CE' => 'El Carné de Extranjería debe tener exactamente 9 números',
                                        default => 'Formato de documento inválido',
                                    };
                                },
                                'numeric' => 'Solo se permiten números',
                                'required' => 'Este campo es obligatorio',
                            ])
                            ->reactive(),
                        TextInput::make('email')
                            ->label('Correo electrónico')
                            ->email()
                            ->required(),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->revealable()
                            ->required()
                            ->minLength(8)
                            ->rule('regex:/^(?=.*[A-Z])(?=.*\d).+$/')
                            ->validationMessages([
                                'regex' => 'La contraseña debe contener al menos una letra mayúscula y un número.',
                                'min' => 'La contraseña debe tener al menos 8 caracteres.',
                            ])
                            ->confirmed(),
                        TextInput::make('password_confirmation')
                            ->label('Confirmar contraseña')
                            ->password()
                            ->revealable()
                            ->required()
                            ->dehydrated(false),
                        
                        // Sección de requisitos de contraseña
                        Section::make()
                            ->schema([
                                Placeholder::make('password_requirements')
                                    ->label('La contraseña debe tener:')
                                    ->content(view('filament.components.password-requirements')),
                            ])
                            ->columnSpan('full'),
                        
                        // Checkboxes de términos y condiciones
                        Checkbox::make('accept_terms')
                            ->label(new \Illuminate\Support\HtmlString('He leído y acepto los <a href="' . asset('documents/terminos-y-condiciones.pdf') . '" target="_blank" style="color: #0075BF; text-decoration: underline;">términos y condiciones</a>.'))
                            ->required()
                            ->validationMessages([
                                'required' => 'Debe aceptar los términos y condiciones.',
                            ]),
                        
                        Checkbox::make('accept_privacy')
                            ->label(new \Illuminate\Support\HtmlString('He leído y acepto la <a href="https://www.mitsuiautomotriz.com/politica-de-privacidad" target="_blank" style="color: #0075BF; text-decoration: underline;">política de privacidad de datos</a>.'))
                            ->required()
                            ->validationMessages([
                                'required' => 'Debe aceptar la política de privacidad.',
                            ]),
                        
                        Checkbox::make('accept_promotions')
                            ->label('Acepto recibir promociones de Mitsui Automotriz o empresas relacionadas.')
                            ->default(false),
                    ])
                    ->statePath('data'),
            ),
        ];
    }

    public function mutateFormDataBeforeRegister(array $data): array
    {
        // Validar documento duplicado
        if (!empty($data['document_type']) && !empty($data['document_number'])) {
            $existingUser = \App\Models\User::where('document_type', $data['document_type'])
                ->where('document_number', $data['document_number'])
                ->first();
            
            if ($existingUser) {
                // Lanzar excepción de validación
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'data.document_number' => 'Ya existe un usuario registrado con este número de documento.'
                ]);
            }
        }
        
        // Remover campos de checkboxes que no van a la base de datos
        unset($data['accept_terms'], $data['accept_privacy'], $data['accept_promotions']);

        // Validar en SAP/C4C para obtener datos reales
        if (!empty($data['document_type']) && !empty($data['document_number'])) {
            try {
                $customerValidationService = app(\App\Services\CustomerValidationService::class);
                $customerData = $customerValidationService->validateCustomerByDocument(
                    $data['document_type'], 
                    $data['document_number']
                );

                if ($customerData) {
                    // Cliente encontrado en SAP/C4C - usar datos reales
                    $data['name'] = $customerData['name'];
                    $data['phone'] = $customerData['phone'];
                    
                    // Si tiene c4c_internal_id real, usarlo; sino, asignar como comodín
                    if (!empty($customerData['c4c_internal_id'])) {
                        $data['c4c_internal_id'] = $customerData['c4c_internal_id'];
                        $data['c4c_uuid'] = $customerData['c4c_uuid'] ?? null;
                        $data['is_comodin'] = false;
                        
                        \Illuminate\Support\Facades\Log::info("Usuario registrado con datos reales de {$customerData['source']}", [
                            'document' => $data['document_number'],
                            'name' => $customerData['name'],
                            'source' => $customerData['source'],
                            'c4c_internal_id' => $customerData['c4c_internal_id']
                        ]);
                    } else {
                        // Cliente encontrado pero sin c4c_internal_id - asignar como comodín
                        $data['c4c_internal_id'] = '1200166011';
                        $data['c4c_uuid'] = null;
                        $data['is_comodin'] = true;
                        
                        \Illuminate\Support\Facades\Log::info("Usuario registrado como comodín (encontrado en {$customerData['source']} pero sin c4c_internal_id)", [
                            'document' => $data['document_number'],
                            'name' => $customerData['name'],
                            'source' => $customerData['source']
                        ]);
                    }
                } else {
                    // Cliente no encontrado - crear como comodín
                    $data['name'] = '';
                    $data['phone'] = null;
                    $data['c4c_internal_id'] = '1200166011'; // ID del cliente comodín
                    $data['c4c_uuid'] = null;
                    $data['is_comodin'] = true;

                    \Illuminate\Support\Facades\Log::info("Usuario registrado como cliente comodín", [
                        'document' => $data['document_number']
                    ]);
                }
            } catch (\Exception $e) {
                // En caso de error, crear como comodín
                $data['name'] = 'CLIENTE COMODIN';
                $data['phone'] = null;
                $data['c4c_internal_id'] = '1200166011';
                $data['c4c_uuid'] = null;
                $data['is_comodin'] = true;

                \Illuminate\Support\Facades\Log::warning("Error al validar cliente, creado como comodín", [
                    'document' => $data['document_number'],
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            // Datos incompletos - crear como comodín
            $data['name'] = 'CLIENTE COMODIN';
            $data['phone'] = null;
            $data['c4c_internal_id'] = '1200166011';
            $data['c4c_uuid'] = null;
            $data['is_comodin'] = true;
        }

        // Hashear la contraseña
        if (!empty($data['password'])) {
            $data['password'] = \Hash::make($data['password']);
        }

        return $data;
    }
}
