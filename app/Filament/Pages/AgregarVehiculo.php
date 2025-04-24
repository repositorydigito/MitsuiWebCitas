<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;

class AgregarVehiculo extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationLabel = 'Agregar Vehículo';

    protected static ?string $title = 'Agregar vehículo';

    protected static string $view = 'filament.pages.agregar-vehiculo';

    // Ocultar de la navegación principal ya que se accederá desde la página de vehículos
    protected static bool $shouldRegisterNavigation = false;

    // Datos del formulario
    public string $placa = '';
    public string $modelo = '';
    public string $anio = '';
    public string $kilometraje = '';

    // Paso actual
    public int $pasoActual = 1;
    public int $totalPasos = 3;

    public function mount(): void
    {
        Log::info("[AgregarVehiculo] Inicializando página de agregar vehículo");
    }

    // Método para avanzar al siguiente paso (botón "Continuar")
    public function continuar(): void
    {
        if ($this->pasoActual == 1) {
            // Validación básica para el paso 1 (puedes agregar validaciones aquí)
            $this->pasoActual++;
        }
    }

    // Método para confirmar y avanzar al paso 3 (botón "Confirmar")
    public function confirmar(): void
    {
        if ($this->pasoActual == 2) {
            // Guardar el vehículo
            $this->guardarVehiculo();
            // Avanzar al paso 3
            $this->pasoActual++;
        }
    }

    // Método para volver al paso anterior
    public function volver(): void
    {
        if ($this->pasoActual > 1) {
            $this->pasoActual--;
        } else {
            // Si estamos en el primer paso, volver a la página de vehículos
            $this->volverAVehiculos();
        }
    }

    // Método para guardar el vehículo
    protected function guardarVehiculo(): void
    {
        // Aquí iría la lógica para guardar el vehículo en la base de datos
        // Por ahora solo simulamos el guardado

        // Mostramos una notificación de éxito
        \Filament\Notifications\Notification::make()
            ->title('Vehículo Agregado')
            ->body('El vehículo ha sido agregado exitosamente.')
            ->success()
            ->send();
    }

    // Método para volver a la página de vehículos
    public function volverAVehiculos(): void
    {
        $this->redirect(Vehiculos::getUrl());
    }
}
