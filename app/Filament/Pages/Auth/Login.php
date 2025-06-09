<?php

namespace App\Filament\Pages\Auth;

use App\Services\Auth\DocumentAuthService;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Support\RawJs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.corporate-login';

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
                'PASAPORTE' => 'Pasaporte',
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
                return match($type) {
                    'DNI' => 'Ej: 12345678',
                    'RUC' => 'Ej: 20123456789',
                    'CE' => 'Ej: 123456789',
                    'PASAPORTE' => 'Ej: A1234567',
                    default => 'Seleccione tipo de documento',
                };
            })
            ->numeric(function (callable $get) {
                $type = $get('document_type');
                return in_array($type, ['DNI', 'RUC', 'CE']);
            })
            ->maxLength(function (callable $get) {
                $type = $get('document_type');
                return match($type) {
                    'DNI' => 8,
                    'RUC' => 11,
                    'CE' => 12,
                    'PASAPORTE' => 20,
                    default => 20,
                };
            })
            ->minLength(function (callable $get) {
                $type = $get('document_type');
                return match($type) {
                    'DNI' => 8,
                    'RUC' => 11,
                    'CE' => 8,
                    'PASAPORTE' => 6,
                    default => 1,
                };
            });
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Contraseña')
            ->hint('Si es su primer acceso, se le solicitará crear una contraseña')
            ->password()
            ->revealable()
            ->placeholder('Ingrese su contraseña');
    }

    protected function getCredentialsFromFormData(array $data): array
    {
        // Usar lógica personalizada en lugar de email/password
        $authService = app(DocumentAuthService::class);
        $result = $authService->authenticateByDocument(
            $data['document_type'],
            $data['document_number'],
            $data['password'] ?? null
        );

        if (!$result['success']) {
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
                // Redirigir a página de creación de contraseña
                session(['pending_user_id' => $result['user']->id]);
                $this->redirect('/auth/create-password');
                return [];

            default:
                throw ValidationException::withMessages([
                    'data.document_number' => $result['message'],
                ]);
        }
    }
} 