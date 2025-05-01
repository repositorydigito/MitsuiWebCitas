<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GestionPopUp extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Gestión Pop up';

    protected static ?string $title = 'Gestión Pop up';

    protected static string $view = 'filament.pages.gestion-pop-up';
    
    // Propiedades para la tabla
    public Collection $popups;
    public int $perPage = 5;
    public int $currentPage = 1;
    public int $page = 1;
    
    // Estado de los popups
    public array $estadoPopups = [];
    
    // Modal para ver imagen
    public bool $isModalOpen = false;
    public string $imagenUrl = '';
    public string $imagenNombre = '';
    
    // Modal para agregar/editar popup
    public bool $isFormModalOpen = false;
    public ?array $popupEnEdicion = null;
    public string $accionFormulario = 'crear';
    
    public function mount(): void
    {
        $this->currentPage = request()->query('page', 1);
        $this->cargarPopups();
    }
    
    public function cargarPopups(): void
    {
        // Simulamos datos de popups para el ejemplo
        $this->popups = collect([
            [
                'id' => 1,
                'imagen' => 'https://via.placeholder.com/80x56/ff0000/ffffff?text=SOAT',
                'nombre' => 'Venta de SOAT',
                'medidas' => '80 x 56 px',
                'formato' => 'JPG',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51999999999&text=Hola,%20quiero%20comprar%20un%20SOAT',
                'activo' => true,
            ],
            [
                'id' => 2,
                'imagen' => 'https://via.placeholder.com/80x56/ff0000/ffffff?text=SOAT',
                'nombre' => 'Venta de SOAT',
                'medidas' => '80 x 56 px',
                'formato' => 'JPG',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51999999999&text=Hola,%20quiero%20comprar%20un%20SOAT',
                'activo' => true,
            ],
            [
                'id' => 3,
                'imagen' => 'https://via.placeholder.com/80x56/ff0000/ffffff?text=SOAT',
                'nombre' => 'Venta de SOAT',
                'medidas' => '80 x 56 px',
                'formato' => 'JPG',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51999999999&text=Hola,%20quiero%20comprar%20un%20SOAT',
                'activo' => true,
            ],
            [
                'id' => 4,
                'imagen' => 'https://via.placeholder.com/80x56/ff0000/ffffff?text=SOAT',
                'nombre' => 'Venta de SOAT',
                'medidas' => '80 x 56 px',
                'formato' => 'JPG',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51999999999&text=Hola,%20quiero%20comprar%20un%20SOAT',
                'activo' => true,
            ],
            [
                'id' => 5,
                'imagen' => 'https://via.placeholder.com/80x56/ff0000/ffffff?text=SOAT',
                'nombre' => 'Venta de SOAT',
                'medidas' => '80 x 56 px',
                'formato' => 'JPG',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51999999999&text=Hola,%20quiero%20comprar%20un%20SOAT',
                'activo' => true,
            ],
            [
                'id' => 6,
                'imagen' => 'https://via.placeholder.com/80x56/ff0000/ffffff?text=SOAT',
                'nombre' => 'Venta de SOAT',
                'medidas' => '80 x 56 px',
                'formato' => 'JPG',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51999999999&text=Hola,%20quiero%20comprar%20un%20SOAT',
                'activo' => true,
            ],
            [
                'id' => 7,
                'imagen' => 'https://via.placeholder.com/80x56/ff0000/ffffff?text=SOAT',
                'nombre' => 'Venta de SOAT',
                'medidas' => '80 x 56 px',
                'formato' => 'JPG',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51999999999&text=Hola,%20quiero%20comprar%20un%20SOAT',
                'activo' => true,
            ],
            [
                'id' => 8,
                'imagen' => 'https://via.placeholder.com/80x56/ff0000/ffffff?text=SOAT',
                'nombre' => 'Venta de SOAT',
                'medidas' => '80 x 56 px',
                'formato' => 'JPG',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51999999999&text=Hola,%20quiero%20comprar%20un%20SOAT',
                'activo' => true,
            ],
            [
                'id' => 9,
                'imagen' => 'https://via.placeholder.com/80x56/ff0000/ffffff?text=SOAT',
                'nombre' => 'Venta de SOAT',
                'medidas' => '80 x 56 px',
                'formato' => 'JPG',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51999999999&text=Hola,%20quiero%20comprar%20un%20SOAT',
                'activo' => true,
            ],
            [
                'id' => 10,
                'imagen' => 'https://via.placeholder.com/80x56/ff0000/ffffff?text=SOAT',
                'nombre' => 'Venta de SOAT',
                'medidas' => '80 x 56 px',
                'formato' => 'JPG',
                'url_wp' => 'https://api.whatsapp.com/send?phone=51999999999&text=Hola,%20quiero%20comprar%20un%20SOAT',
                'activo' => true,
            ],
        ]);
        
        // Inicializar el estado de los popups
        foreach ($this->popups as $popup) {
            $this->estadoPopups[$popup['id']] = $popup['activo'];
        }
    }
    
    public function getPopupsPaginadosProperty(): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            $this->popups->forPage($this->currentPage, $this->perPage),
            $this->popups->count(),
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
        $this->estadoPopups[$id] = !$this->estadoPopups[$id];
        
        // Actualizar el estado en la colección de popups
        $this->popups = $this->popups->map(function ($popup) use ($id) {
            if ($popup['id'] === $id) {
                $popup['activo'] = $this->estadoPopups[$id];
            }
            return $popup;
        });
        
        // Aquí iría la lógica para actualizar el estado en la base de datos
        
        // Mostrar notificación con el estado actual
        $estado = $this->estadoPopups[$id] ? 'activado' : 'desactivado';
        \Filament\Notifications\Notification::make()
            ->title('Estado actualizado')
            ->body("El popup ha sido {$estado}")
            ->success()
            ->send();
    }
    
    public function verImagen(int $id): void
    {
        $popup = $this->popups->firstWhere('id', $id);
        if ($popup) {
            $this->imagenUrl = $popup['imagen'];
            $this->imagenNombre = $popup['nombre'];
            $this->isModalOpen = true;
        }
    }
    
    public function cerrarModal(): void
    {
        $this->isModalOpen = false;
    }
    
    public function agregarOpcion(): void
    {
        $this->accionFormulario = 'crear';
        $this->popupEnEdicion = [
            'id' => null,
            'imagen' => '',
            'nombre' => '',
            'medidas' => '80 x 56 px',
            'formato' => 'JPG',
            'url_wp' => '',
            'activo' => true,
        ];
        $this->isFormModalOpen = true;
    }
    
    public function guardarPopup(): void
    {
        // Aquí iría la lógica para guardar el popup en la base de datos
        
        \Filament\Notifications\Notification::make()
            ->title('Popup guardado')
            ->body("El popup ha sido guardado correctamente")
            ->success()
            ->send();
            
        $this->isFormModalOpen = false;
    }
    
    public function cerrarFormModal(): void
    {
        $this->isFormModalOpen = false;
    }
}
