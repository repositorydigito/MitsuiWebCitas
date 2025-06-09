<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;
use Filament\Actions\Action;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.corporate-login';

    protected function getRegisterAction(): Action
    {
        return Action::make('register')
            ->label('Registrarse')
            ->url(filament()->getRegistrationUrl())
            ->color('gray');
    }

    protected function hasRegisterAction(): bool
    {
        return filament()->hasRegistration();
    }
}