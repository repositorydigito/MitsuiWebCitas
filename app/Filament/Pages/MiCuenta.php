<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class MiCuenta extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Mi cuenta';

    protected static ?string $title = 'Mi cuenta';

    protected static ?int $navigationSort = 90;

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

    // Datos del usuario (simulados por ahora)
    public array $usuario = [
        'nombres' => 'Francisco Luis',
        'apellidos' => 'Salas Ruiz',
        'celular' => '994025223',
        'correo' => 'fsalas@mitsuiautomotriz.com',
        'tipo_documento' => 'DNI',
        'numero_documento' => '44885533',
    ];

    // Variables para el modo de edición
    public int $pasoActual = 1;
    public bool $modoEdicion = false;

    // Datos temporales para la edición
    public array $datosEdicion = [];

    // Tipos de documento disponibles
    public array $tiposDocumento = [
        'DNI' => 'DNI',
        'CE' => 'Carnet de Extranjería',
        'PAS' => 'Pasaporte',
    ];

    public function mount(): void
    {
        // Inicializar los datos de edición con los datos actuales del usuario
        $this->datosEdicion = $this->usuario;
    }

    // Método para iniciar la edición
    public function iniciarEdicion(): void
    {
        $this->modoEdicion = true;
        $this->pasoActual = 1;
        $this->datosEdicion = $this->usuario;
    }

    // Método para cancelar la edición
    public function cancelarEdicion(): void
    {
        $this->modoEdicion = false;
        $this->pasoActual = 1;
        $this->datosEdicion = $this->usuario;
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
                'datosEdicion.tipo_documento' => 'required|string|in:DNI,CE,PAS',
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
        // Aquí se guardarían los cambios en la base de datos
        // Por ahora, solo actualizamos los datos del usuario en memoria
        $this->usuario = $this->datosEdicion;

        // Registrar la acción en los logs
        Log::info('Usuario actualizó sus datos personales', [
            'usuario' => $this->usuario['correo'],
            'datos' => $this->usuario,
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
        // En un entorno real, esto redireccionaría a la página de citas
        return redirect()->to('/admin/vehiculos');
    }
}
