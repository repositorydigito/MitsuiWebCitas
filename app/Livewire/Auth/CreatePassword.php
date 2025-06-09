<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\Auth\DocumentAuthService;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CreatePassword extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];
    public ?User $user = null;

    public function mount()
    {
        $userId = session('pending_user_id');
        
        if (!$userId) {
            $this->redirect('/admin/login');
            return;
        }

        $this->user = User::find($userId);
        
        if (!$this->user) {
            session()->forget('pending_user_id');
            $this->redirect('/admin/login');
            return;
        }

        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('password')
                    ->label('Nueva Contraseña')
                    ->password()
                    ->revealable()
                    ->required()
                    ->minLength(8)
                    ->placeholder('Mínimo 8 caracteres'),
                
                TextInput::make('password_confirmation')
                    ->label('Confirmar Contraseña')
                    ->password()
                    ->revealable()
                    ->required()
                    ->same('password')
                    ->placeholder('Repita la contraseña'),
            ])
            ->statePath('data');
    }

    public function create()
    {
        $data = $this->form->getState();
        
        $authService = app(DocumentAuthService::class);
        $success = $authService->setUserPassword($this->user, $data['password']);
        
        if ($success) {
            session()->forget('pending_user_id');
            Auth::login($this->user);
            
            Notification::make()
                ->title('Contraseña establecida exitosamente')
                ->success()
                ->send();
                
            $this->redirect('/admin');
        } else {
            Notification::make()
                ->title('Error al establecer contraseña')
                ->danger()
                ->send();
        }
    }

    public function render()
    {
        return view('livewire.auth.create-password');
    }
}
