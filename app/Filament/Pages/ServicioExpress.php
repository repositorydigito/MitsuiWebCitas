<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ServicioExpress extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Servicio Express';

    protected static ?string $title = 'Gestión servicio express';

    protected static string $view = 'filament.pages.servicio-express';

    // Propiedades para la tabla
    public Collection $vehiculos;
    public int $perPage = 5;
    public int $currentPage = 1;
    public int $page = 1;

    // Estado de los vehículos
    public array $estadoVehiculos = [];

    // Propiedades para el modal
    public bool $isModalOpen = false;
    public $archivoExcel = null;
    public string $nombreArchivo = 'Sin selección';

    public function mount(): void
    {
        $this->currentPage = request()->query('page', 1);
        $this->cargarVehiculos();
    }

    public function cargarVehiculos(): void
    {
        // Simulamos datos de vehículos para el ejemplo
        $this->vehiculos = collect([
            [
                'id' => 1,
                'modelo' => 'Yaris',
                'marca' => 'Toyota',
                'ano' => '2022',
                'mantenimiento' => '20,000 Km',
                'local' => 'La Molina',
                'activo' => true,
            ],
            [
                'id' => 2,
                'modelo' => 'Yaris',
                'marca' => 'Toyota',
                'ano' => '2022',
                'mantenimiento' => '20,000 Km',
                'local' => 'La Molina',
                'activo' => true,
            ],
            [
                'id' => 3,
                'modelo' => 'Yaris',
                'marca' => 'Toyota',
                'ano' => '2022',
                'mantenimiento' => '20,000 Km',
                'local' => 'La Molina',
                'activo' => true,
            ],
            [
                'id' => 4,
                'modelo' => 'Yaris',
                'marca' => 'Toyota',
                'ano' => '2022',
                'mantenimiento' => '20,000 Km',
                'local' => 'La Molina',
                'activo' => true,
            ],
            [
                'id' => 5,
                'modelo' => 'Yaris',
                'marca' => 'Toyota',
                'ano' => '2022',
                'mantenimiento' => '20,000 Km',
                'local' => 'La Molina',
                'activo' => true,
            ],
            [
                'id' => 6,
                'modelo' => 'Yaris',
                'marca' => 'Toyota',
                'ano' => '2022',
                'mantenimiento' => '20,000 Km',
                'local' => 'La Molina',
                'activo' => true,
            ],
            [
                'id' => 7,
                'modelo' => 'Yaris',
                'marca' => 'Toyota',
                'ano' => '2022',
                'mantenimiento' => '20,000 Km',
                'local' => 'La Molina',
                'activo' => true,
            ],
            [
                'id' => 8,
                'modelo' => 'Yaris',
                'marca' => 'Toyota',
                'ano' => '2022',
                'mantenimiento' => '20,000 Km',
                'local' => 'La Molina',
                'activo' => true,
            ],
            [
                'id' => 9,
                'modelo' => 'Yaris',
                'marca' => 'Toyota',
                'ano' => '2022',
                'mantenimiento' => '20,000 Km',
                'local' => 'La Molina',
                'activo' => true,
            ],
            [
                'id' => 10,
                'modelo' => 'Yaris',
                'marca' => 'Toyota',
                'ano' => '2022',
                'mantenimiento' => '20,000 Km',
                'local' => 'La Molina',
                'activo' => true,
            ],
            [
                'id' => 11,
                'modelo' => 'Yaris',
                'marca' => 'Toyota',
                'ano' => '2022',
                'mantenimiento' => '20,000 Km',
                'local' => 'La Molina',
                'activo' => true,
            ],
            [
                'id' => 12,
                'modelo' => 'Yaris',
                'marca' => 'Toyota',
                'ano' => '2022',
                'mantenimiento' => '20,000 Km',
                'local' => 'La Molina',
                'activo' => true,
            ],
        ]);

        // Inicializar el estado de los vehículos
        foreach ($this->vehiculos as $vehiculo) {
            $this->estadoVehiculos[$vehiculo['id']] = $vehiculo['activo'];
        }
    }

    public function getVehiculosPaginadosProperty(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $this->vehiculos->forPage($this->currentPage, $this->perPage),
            $this->vehiculos->count(),
            $this->perPage,
            $this->currentPage,
            [
                'path' => Paginator::resolveCurrentPath(),
                'pageName' => 'page',
            ]
        );
    }

    public function toggleEstado(int $id): void
    {
        // Invertir el estado actual
        $this->estadoVehiculos[$id] = !$this->estadoVehiculos[$id];

        // Actualizar el estado en la colección de vehículos
        $this->vehiculos = $this->vehiculos->map(function ($vehiculo) use ($id) {
            if ($vehiculo['id'] === $id) {
                $vehiculo['activo'] = $this->estadoVehiculos[$id];
            }
            return $vehiculo;
        });

        // Aquí iría la lógica para actualizar el estado en la base de datos

        // Mostrar notificación con el estado actual
        $estado = $this->estadoVehiculos[$id] ? 'activado' : 'desactivado';
        \Filament\Notifications\Notification::make()
            ->title('Estado actualizado')
            ->body("El vehículo ha sido {$estado}")
            ->success()
            ->send();
    }

    public function editar(int $id): void
    {
        // Implementar la lógica para editar
        \Filament\Notifications\Notification::make()
            ->title('Editar vehículo')
            ->body("Editar el vehículo con ID: {$id}")
            ->success()
            ->send();
    }

    public function eliminar(int $id): void
    {
        // Implementar la lógica para eliminar
        \Filament\Notifications\Notification::make()
            ->title('Eliminar vehículo')
            ->body("Eliminar el vehículo con ID: {$id}")
            ->success()
            ->send();
    }

    public function registrarVehiculo(): void
    {
        // Abrir el modal
        $this->isModalOpen = true;
    }

    public function cerrarModal(): void
    {
        // Cerrar el modal y resetear valores
        $this->isModalOpen = false;
        $this->archivoExcel = null;
        $this->nombreArchivo = 'Sin selección';
    }

    public function seleccionarArchivo($archivo): void
    {
        if ($archivo) {
            $this->archivoExcel = $archivo;
            $this->nombreArchivo = $archivo->getClientOriginalName();
        }
    }

    public function cargarArchivo(): void
    {
        if (!$this->archivoExcel) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body("Debes seleccionar un archivo Excel")
                ->danger()
                ->send();
            return;
        }

        // Aquí iría la lógica para procesar el archivo Excel
        // Por ejemplo, leer el archivo y cargar los vehículos

        \Filament\Notifications\Notification::make()
            ->title('Archivo cargado')
            ->body("El archivo {$this->nombreArchivo} ha sido cargado correctamente")
            ->success()
            ->send();

        $this->cerrarModal();
    }
}
