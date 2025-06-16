<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Pages\Auth\Register as BaseRegister;

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

            return $user;
        });

        // Guardar mensaje de éxito en sesión y redirigir
        session()->flash('status', 'Registro exitoso. Ahora puedes iniciar sesión con tus credenciales.');

        $this->redirect(filament()->getLoginUrl());

        return null;
    }
}
