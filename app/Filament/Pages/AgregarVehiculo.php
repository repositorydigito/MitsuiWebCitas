<?php

namespace App\Filament\Pages;

use App\Models\Vehicle;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AgregarVehiculo extends Page
{
    use HasPageShield;
    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static ?string $navigationLabel = 'Agregar Vehículo';
    
    protected static ?string $navigationGroup = '🚗 Vehículos';
    
    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Agregar vehículo';

    protected static string $view = 'filament.pages.agregar-vehiculo';

    // Ocultar de la navegación principal ya que se accederá desde la página de vehículos
    protected static bool $shouldRegisterNavigation = false;

    // Datos del formulario
    public string $placa = '';

    public string $modelo = '';

    public string $anio = '';

    public string $kilometraje = '';

    public string $color = '';

    public string $marca = 'Z01'; // Por defecto TOYOTA

    // Paso actual
    public int $pasoActual = 1;

    public int $totalPasos = 3;

    public function mount(): void
    {
        Log::info('[AgregarVehiculo] Inicializando página de agregar vehículo');
    }

    // Método para avanzar al siguiente paso (botón "Continuar")
    public function continuar(): void
    {
        if ($this->pasoActual == 1) {
            // Validación básica para el paso 1
            if (empty($this->placa) || empty($this->modelo) || empty($this->anio) || empty($this->marca)) {
                \Filament\Notifications\Notification::make()
                    ->title('Campos Incompletos')
                    ->body('Por favor complete todos los campos obligatorios.')
                    ->warning()
                    ->send();

                return;
            }

            // Validar formato de placa (ejemplo: ABC-123)
            if (! preg_match('/^[A-Z0-9]{3,4}-[0-9]{3,4}$/i', $this->placa)) {
                \Filament\Notifications\Notification::make()
                    ->title('Formato Incorrecto')
                    ->body('La placa debe tener un formato válido (ejemplo: ABC-123).')
                    ->warning()
                    ->send();

                return;
            }

            // Validar año (debe ser un número entre 1900 y el año actual + 1)
            $currentYear = (int) date('Y');
            if (! is_numeric($this->anio) || (int) $this->anio < 1900 || (int) $this->anio > ($currentYear + 1)) {
                \Filament\Notifications\Notification::make()
                    ->title('Año Inválido')
                    ->body("El año debe ser un número entre 1900 y {$currentYear}.")
                    ->warning()
                    ->send();

                return;
            }

            // Si pasa todas las validaciones, avanzar al siguiente paso
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
        try {
            // Validar datos básicos
            if (empty($this->placa) || empty($this->modelo) || empty($this->anio) || empty($this->marca)) {
                \Filament\Notifications\Notification::make()
                    ->title('Error al Guardar')
                    ->body('Por favor complete todos los campos obligatorios.')
                    ->danger()
                    ->send();

                return;
            }

            // Determinar el nombre de la marca basado en el código
            $brandName = match ($this->marca) {
                'Z01' => 'TOYOTA',
                'Z02' => 'LEXUS',
                'Z03' => 'HINO',
                default => 'TOYOTA',
            };

            // Generar datos aleatorios para simular datos reales
            $randomData = [
                'color' => $this->color ?: $this->getRandomColor(),
                'mileage' => ! empty($this->kilometraje) ? (int) $this->kilometraje : rand(1000, 50000),
                'last_service_date' => now()->subMonths(rand(1, 6)),
                'last_service_mileage' => rand(1000, 30000),
                'next_service_date' => now()->addMonths(rand(1, 6)),
                'next_service_mileage' => rand(5000, 60000),
                'has_prepaid_maintenance' => (bool) rand(0, 1),
                'prepaid_maintenance_expiry' => now()->addYears(rand(1, 3)),
            ];

            // Crear el vehículo en la base de datos
            $vehicle = new Vehicle;
            $vehicle->vehicle_id = 'VH'.Str::random(8);
            $vehicle->license_plate = strtoupper($this->placa);
            $vehicle->model = $this->modelo;
            $vehicle->year = $this->anio;
            $vehicle->brand_code = $this->marca;
            $vehicle->brand_name = $brandName;
            $vehicle->color = $randomData['color'];
            $vehicle->mileage = $randomData['mileage'];
            $vehicle->last_service_date = $randomData['last_service_date'];
            $vehicle->last_service_mileage = $randomData['last_service_mileage'];
            $vehicle->next_service_date = $randomData['next_service_date'];
            $vehicle->next_service_mileage = $randomData['next_service_mileage'];
            $vehicle->has_prepaid_maintenance = $randomData['has_prepaid_maintenance'];
            $vehicle->prepaid_maintenance_expiry = $randomData['prepaid_maintenance_expiry'];
            $vehicle->status = 'active';
            $vehicle->save();

            Log::info("[AgregarVehiculo] Vehículo guardado exitosamente: {$vehicle->license_plate}");

            // Mostramos una notificación de éxito
            \Filament\Notifications\Notification::make()
                ->title('Vehículo Agregado')
                ->body('El vehículo ha sido agregado exitosamente.')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Log::error('[AgregarVehiculo] Error al guardar vehículo: '.$e->getMessage());

            \Filament\Notifications\Notification::make()
                ->title('Error al Guardar')
                ->body('Ocurrió un error al guardar el vehículo: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    /**
     * Obtener un color aleatorio.
     */
    private function getRandomColor(): string
    {
        $colors = [
            'Blanco', 'Negro', 'Gris', 'Plata', 'Rojo', 'Azul', 'Verde',
            'Amarillo', 'Naranja', 'Marrón', 'Beige', 'Dorado', 'Bronce',
        ];

        return $colors[array_rand($colors)];
    }

    // Método para volver a la página de vehículos
    public function volverAVehiculos(): void
    {
        $this->redirect(Vehiculos::getUrl());
    }
}
