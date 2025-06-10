<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MiCuenta extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Mi cuenta';
    
    protected static ?string $navigationGroup = '👥 Administración';
    
    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Mi cuenta';

    protected static string $view = 'filament.pages.mi-cuenta';

    protected function getHeaderActions(): array
    {
        return [];
    }

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    protected function getFooterWidgets(): array
    {
        return [];
    }

    // Variables para el modo de edición
    public int $pasoActual = 1;

    public bool $modoEdicion = false;

    // Datos temporales para la edición
    public array $datosEdicion = [];

    // Tipos de documento disponibles
    public array $tiposDocumento = [
        'DNI' => 'DNI',
        'RUC' => 'RUC',
        'CE' => 'Carnet de Extranjería',
        'PASAPORTE' => 'Pasaporte',
    ];

    public function mount(): void
    {
        // Inicializar los datos de edición con los datos del usuario autenticado
        $this->datosEdicion = $this->getUserData();
    }

    // Método para obtener datos del usuario autenticado
    public function getUserData(): array
    {
        $user = Auth::user();
        
        return [
            'nombres' => $this->splitName($user->name)['nombres'],
            'apellidos' => $this->splitName($user->name)['apellidos'],
            'celular' => $user->phone ?? '',
            'correo' => $user->email ?? '',
            'tipo_documento' => $user->document_type ?? '',
            'numero_documento' => $user->document_number ?? '',
        ];
    }

    // Método auxiliar para dividir el nombre completo
    private function splitName(?string $fullName): array
    {
        if (!$fullName) {
            return ['nombres' => '', 'apellidos' => ''];
        }

        $parts = explode(' ', trim($fullName));
        
        // Si solo hay una palabra, va a nombres
        if (count($parts) === 1) {
            return ['nombres' => $parts[0], 'apellidos' => ''];
        }
        
        // Si hay dos o más palabras, la primera va a nombres, el resto a apellidos
        $nombres = $parts[0];
        $apellidos = implode(' ', array_slice($parts, 1));
        
        return ['nombres' => $nombres, 'apellidos' => $apellidos];
    }

    // Método para iniciar la edición
    public function iniciarEdicion(): void
    {
        $this->modoEdicion = true;
        $this->pasoActual = 1;
        $this->datosEdicion = $this->getUserData();
    }

    // Método para cancelar la edición
    public function cancelarEdicion(): void
    {
        $this->modoEdicion = false;
        $this->pasoActual = 1;
        $this->datosEdicion = $this->getUserData();
    }

    // Método para avanzar al siguiente paso
    public function siguientePaso(): void
    {
        // Validar los datos según el paso actual
        if ($this->pasoActual == 1) {
            $this->validate([
                'datosEdicion.nombres' => 'required|string|max:100',
                'datosEdicion.apellidos' => 'required|string|max:100',
                'datosEdicion.correo' => 'required|email|max:100',
                'datosEdicion.celular' => 'required|string|max:15',
                'datosEdicion.tipo_documento' => 'required|string|in:DNI,RUC,CE,PASAPORTE',
                'datosEdicion.numero_documento' => 'required|string|max:20',
            ]);
        }

        // Avanzar al siguiente paso
        $this->pasoActual++;
    }

    // Método para volver al paso anterior
    public function pasoAnterior(): void
    {
        if ($this->pasoActual > 1) {
            $this->pasoActual--;
        }
    }

    // Método para guardar los cambios
    public function guardarCambios(): void
    {
        $user = Auth::user();
        
        // Combinar nombres y apellidos para el campo name
        $fullName = trim($this->datosEdicion['nombres'] . ' ' . $this->datosEdicion['apellidos']);
        
        // Actualizar el usuario en la base de datos
        $user->update([
            'name' => $fullName,
            'email' => $this->datosEdicion['correo'],
            'phone' => $this->datosEdicion['celular'],
            'document_type' => $this->datosEdicion['tipo_documento'],
            'document_number' => $this->datosEdicion['numero_documento'],
        ]);

        // Registrar la acción en los logs
        Log::info('Usuario actualizó sus datos personales', [
            'user_id' => $user->id,
            'datos' => $this->datosEdicion,
        ]);

        // Mostrar notificación de éxito
        \Filament\Notifications\Notification::make()
            ->title('Datos actualizados')
            ->body('Tus datos personales han sido actualizados correctamente.')
            ->success()
            ->send();

        // Avanzar al paso de confirmación
        $this->pasoActual = 3;
    }

    // Método para cerrar y redirigir a Cita de servicio
    public function cerrarYRedirigir()
    {
        // Volver al modo de visualización
        $this->modoEdicion = false;
        $this->pasoActual = 1;

        // Redirigir a la página de Cita de servicio
        $this->redirect('/admin/vehiculos');
    }
}
