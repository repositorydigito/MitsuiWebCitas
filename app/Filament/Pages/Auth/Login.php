<?php

namespace App\Filament\Pages\Auth;

use App\Services\Auth\DocumentAuthService;
use Filament\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.corporate-login';

    public bool $showPasswordField = true;
    public bool $userExists = false;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->getDocumentTypeFormComponent(),
                $this->getDocumentNumberFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }

    protected function getDocumentTypeFormComponent(): Component
    {
        return Select::make('document_type')
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
            });
    }

    protected function getDocumentNumberFormComponent(): Component
    {
        return TextInput::make('document_number')
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
            ->reactive()
            ->afterStateUpdated(function ($state, callable $set, $livewire) {
                // Resetear estado cuando cambia el número (ya no ocultamos el password)
                $livewire->userExists = false;
            });
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Contraseña')
            ->password()
            ->revealable()
            ->placeholder('Ingrese su contraseña')
            ->visible(fn () => $this->showPasswordField)
            ->required(fn () => $this->showPasswordField);
    }

    // Método checkUser eliminado porque ahora la validación es directa

    protected function getCredentialsFromFormData(array $data): array
    {
        // Validación directa con contraseña siempre presente
        if (empty($data['document_type']) || empty($data['document_number'])) {
            throw ValidationException::withMessages([
                'data.document_number' => 'Por favor complete los datos del documento',
            ]);
        }

        // Usar lógica personalizada en lugar de email/password
        $authService = app(DocumentAuthService::class);
        $result = $authService->authenticateByDocument(
            $data['document_type'],
            $data['document_number'],
            $data['password'] ?? null
        );

        if (! $result['success']) {
            throw ValidationException::withMessages([
                'data.password' => $result['message'],
            ]);
        }

        switch ($result['action']) {
            case 'login':
                // Retornar credenciales para el proceso normal de Filament
                return [
                    'document_type' => $data['document_type'],
                    'document_number' => $data['document_number'],
                    'password' => $data['password'],
                ];

            case 'request_password':
                throw ValidationException::withMessages([
                    'data.password' => 'Por favor, ingrese su contraseña.',
                ]);

            case 'create_password':
                // Solo redirigir si el usuario NO tiene contraseña válida
                if (empty($result['user']->password)) {
                    session(['pending_user_id' => $result['user']->id]);
                    $this->redirect('/auth/create-password');
                    return [];
                }
                // Si tiene contraseña, permitir login normal
                return [
                    'document_type' => $data['document_type'],
                    'document_number' => $data['document_number'],
                    'password' => $data['password'],
                ];

            default:
                throw ValidationException::withMessages([
                    'data.document_number' => $result['message'],
                ]);
        }
    }

    public function getLoginAction(): Action
    {
        return Action::make('login')
            ->label('Inicia sesión')
            ->url(filament()->getLoginUrl())
            ->color('gray');
    }

    protected function getForgotPasswordAction(): Action
    {
        return Action::make('forgotPassword')
            ->label('¿Olvidaste tu contraseña?')
            ->url(route('password.request'))
            ->color('primary')
            ->outlined();
    }

    protected function getFormActions(): array
    {
        // Mostrar el botón de "Iniciar Sesión" y "¿Olvidaste tu contraseña?"
        return [
            $this->getAuthenticateFormAction(),
            $this->getForgotPasswordAction(),
        ];
    }
}
