<?php

namespace App\Filament\Pages;

use App\Models\PopUp;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\WithFileUploads;

class GestionPopUp extends Page
{
    use WithFileUploads, HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Gestión Pop up';
    
    protected static ?string $navigationGroup = '📢 Marketing';
    
    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Gestión Pop up';

    protected static string $view = 'filament.pages.gestion-pop-up';

    // Propiedades para la tabla
    public Collection $popups;

    public int $perPage = 5;

    public int $currentPage = 1;

    public int $page = 1;

    // Propiedad para búsqueda
    public string $busqueda = '';

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

    public $imagen; // Propiedad para la imagen subida

    public $imagenPreview = null; // Para mostrar la vista previa de la imagen

    public function mount(): void
    {
        $this->currentPage = request()->query('page', 1);
        $this->cargarPopups();
    }

    public function cargarPopups(): void
    {
        try {
            // Cargar los popups desde la base de datos
            $popupsDB = PopUp::all();

            // Transformar los datos para que coincidan con el formato esperado
            $this->popups = collect();

            foreach ($popupsDB as $popup) {
                $imagenUrl = $popup->image_path;

                // Si la imagen es una ruta relativa, convertirla a URL completa
                if (! filter_var($imagenUrl, FILTER_VALIDATE_URL)) {
                    $imagenUrl = asset('storage/'.$imagenUrl);
                }

                $this->popups->push([
                    'id' => $popup->id,
                    'image' => $imagenUrl,
                    'name' => $popup->name,
                    'sizes' => $popup->sizes,
                    'format' => $popup->format,
                    'url_wp' => $popup->url_wp,
                    'is_active' => $popup->is_active,
                ]);
            }

            // Inicializar el estado de los popups
            foreach ($this->popups as $popup) {
                $this->estadoPopups[$popup['id']] = $popup['is_active'];
            }
        } catch (\Exception $e) {
            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al cargar popups')
                ->body('Ha ocurrido un error al cargar los popups: '.$e->getMessage())
                ->danger()
                ->send();
            // Inicializar con una colección vacía
            $this->popups = collect();
        }
    }

    public function getPopupsPaginadosProperty(): LengthAwarePaginator
    {
        // Filtrar por nombre si hay una búsqueda
        $popupsFiltrados = $this->popups;
        if (! empty($this->busqueda)) {
            $terminoBusqueda = strtolower($this->busqueda);
            $popupsFiltrados = $this->popups->filter(function ($popup) use ($terminoBusqueda) {
                return str_contains(strtolower($popup['name']), $terminoBusqueda);
            });
        }
        // Resetear la página si cambia el filtro
        if ($popupsFiltrados->count() > 0 && $this->currentPage > ceil($popupsFiltrados->count() / $this->perPage)) {
            $this->currentPage = 1;
        }
        return new LengthAwarePaginator(
            $popupsFiltrados->forPage($this->currentPage, $this->perPage),
            $popupsFiltrados->count(),
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
        try {
            // Actualizar el estado en la memoria
            $this->estadoPopups[$id] = ! $this->estadoPopups[$id];
            // Actualizar el estado en la colección de popups
            $this->popups = $this->popups->map(function ($popup) use ($id) {
                if ($popup['id'] === $id) {
                    $popup['is_active'] = $this->estadoPopups[$id];
                }
                return $popup;
            });

            // Actualizar el estado en la base de datos
            $popup = PopUp::findOrFail($id);
            $popup->is_active = $this->estadoPopups[$id];
            $popup->save();

            // Mostrar notificación con el estado actual
            $estado = $this->estadoPopups[$id] ? 'activado' : 'desactivado';
            \Filament\Notifications\Notification::make()
                ->title('Estado actualizado')
                ->body("El popup ha sido {$estado}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al actualizar estado')
                ->body('Ha ocurrido un error al actualizar el estado del popup: '.$e->getMessage())
                ->danger()
                ->send();
            // Revertir el cambio en la memoria
            $this->estadoPopups[$id] = ! $this->estadoPopups[$id];
            // Recargar los popups para asegurar consistencia
            $this->cargarPopups();
        }
    }

    public function verImagen(int $id): void
    {
        $popup = $this->popups->firstWhere('id', $id);
        if ($popup) {
            $this->imagenUrl = $popup['image'];
            $this->imagenNombre = $popup['name'];
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
            'name' => '',
            'image_path' => '',
            'sizes' => '80 x 56 px',
            'format' => 'JPG',
            'url_wp' => '',
            'is_active' => true,
        ];
        $this->reset(['imagen', 'imagenPreview']);
        $this->isFormModalOpen = true;
    }

    public function editarPopup(int $id): void
    {
        try {
            // Buscar el popup en la colección
            $popup = $this->popups->firstWhere('id', $id);

            if ($popup) {
                $this->accionFormulario = 'editar';
                $this->popupEnEdicion = $popup;
                $this->imagenPreview = $popup['image'];
                $this->isFormModalOpen = true;
            } else {
                throw new \Exception("No se encontró el popup con ID {$id}");
            }
        } catch (\Exception $e) {
            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al editar popup')
                ->body('Ha ocurrido un error al editar el popup: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function guardarPopup(): void
    {
        try {
            // Validar los datos
            $this->validate([
                'popupEnEdicion.name' => 'required|string|max:255',
                'imagen' => 'nullable|image|max:10240', // 10MB máximo
                'popupEnEdicion.url_wp' => 'nullable|string|max:255',
            ], [
                'popupEnEdicion.name.required' => 'El nombre es obligatorio',
                'imagen.image' => 'El archivo debe ser una imagen',
                'imagen.max' => 'La imagen no debe superar los 10MB',
            ]);

            // Crear o actualizar el popup
            $popup = null;

            if ($this->accionFormulario === 'editar' && ! empty($this->popupEnEdicion['id'])) {
                $popup = PopUp::findOrFail($this->popupEnEdicion['id']);
            } else {
                $popup = new PopUp;
            }

            $popup->name = $this->popupEnEdicion['name'];
            $popup->url_wp = $this->popupEnEdicion['url_wp'] ?? null;
            $popup->is_active = $this->popupEnEdicion['is_active'] ?? true;

            // Procesar la imagen si se ha subido una nueva
            if ($this->imagen) {
                // Crear directorio si no existe
                if (! Storage::disk('public')->exists('popups')) {
                    Storage::disk('public')->makeDirectory('popups');
                }

                // Procesar la imagen con Intervention Image
                $manager = new ImageManager(new Driver);
                $img = $manager->read($this->imagen->getRealPath());

                // Obtener dimensiones y formato
                $width = $img->width();
                $height = $img->height();
                $formato = $this->imagen->getClientOriginalExtension();

                // Guardar las dimensiones y formato
                $popup->sizes = "{$width} x {$height} px";
                $popup->format = strtoupper($formato);

                // Generar nombre único para la imagen
                $nombreArchivo = 'popup_'.time().'.'.$formato;
                $rutaImagen = 'popups/'.$nombreArchivo;

                // Guardar la imagen en el almacenamiento
                $img->save(storage_path('app/public/'.$rutaImagen));

                // Guardar la ruta en la base de datos
                $popup->image_path = $rutaImagen;
            } elseif ($this->accionFormulario === 'editar') {
                // Si estamos editando y no se ha subido una nueva imagen, mantener las dimensiones y formato existentes
                if (isset($this->popupEnEdicion['sizes'])) {
                    $popup->sizes = $this->popupEnEdicion['sizes'];
                }

                if (isset($this->popupEnEdicion['format'])) {
                    $popup->format = $this->popupEnEdicion['format'];
                }
            } else {
                // Si es una creación nueva y no se ha subido imagen, usar valores por defecto
                $popup->sizes = '80 x 56 px';
                $popup->format = 'JPG';
                $popup->image_path = 'popups/default.jpg'; // Imagen por defecto
            }

            // Guardar el popup
            $popup->save();

            // Mostrar notificación de éxito
            \Filament\Notifications\Notification::make()
                ->title('Popup guardado')
                ->body('El popup ha sido guardado correctamente')
                ->success()
                ->send();
            // Cerrar el modal y recargar los popups
            $this->isFormModalOpen = false;
            $this->reset(['imagen', 'imagenPreview']);
            $this->cargarPopups();
        } catch (\Exception $e) {
            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al guardar popup')
                ->body('Ha ocurrido un error al guardar el popup: '.$e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function cerrarFormModal(): void
    {
        $this->isFormModalOpen = false;
        $this->reset(['imagen', 'imagenPreview']);
    }

    public function updatedImagen(): void
    {
        try {
            // Validar la imagen
            $this->validate([
                'imagen' => 'image|max:10240', // 10MB máximo
            ], [
                'imagen.image' => 'El archivo debe ser una imagen',
                'imagen.max' => 'La imagen no debe superar los 10MB',
            ]);

            // Generar URL temporal para la vista previa
            $this->imagenPreview = $this->imagen->temporaryUrl();

            // Obtener información de la imagen
            $manager = new ImageManager(new Driver);
            $img = $manager->read($this->imagen->getRealPath());
            $width = $img->width();
            $height = $img->height();
            $formato = $this->imagen->getClientOriginalExtension();

            // Actualizar los campos en el formulario
            $this->popupEnEdicion['sizes'] = "{$width} x {$height} px";
            $this->popupEnEdicion['format'] = strtoupper($formato);
        } catch (\Exception $e) {
            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al procesar imagen')
                ->body('Ha ocurrido un error al procesar la imagen: '.$e->getMessage())
                ->danger()
                ->send();
            $this->reset(['imagen', 'imagenPreview']);
        }
    }

    public function limpiarBusqueda(): void
    {
        $this->busqueda = '';
        $this->currentPage = 1;
        // Mostrar notificación
        \Filament\Notifications\Notification::make()
            ->title('Búsqueda limpiada')
            ->body('Se han restablecido los filtros de búsqueda')
            ->success()
            ->send();
    }

    public function eliminarPopup(int $id): void
    {
        try {
            // Buscar el popup en la base de datos
            $popup = PopUp::findOrFail($id);

            // Eliminar la imagen del almacenamiento si existe
            if (!empty($popup->image_path)) {
                try {
                    // Intentar eliminar el archivo físico
                    $rutaImagen = str_replace('storage/', '', $popup->image_path);
                    if (Storage::disk('public')->exists($rutaImagen)) {
                        Storage::disk('public')->delete($rutaImagen);
                    }
                } catch (\Exception $e) {
                    // Log del error pero continuar con la eliminación del registro
                    \Illuminate\Support\Facades\Log::warning("Error al eliminar imagen del popup {$id}: " . $e->getMessage());
                }
            }

            // Eliminar el popup de la base de datos
            $popup->delete();

            // Mostrar notificación de éxito
            \Filament\Notifications\Notification::make()
                ->title('Popup eliminado')
                ->body('El popup ha sido eliminado correctamente')
                ->success()
                ->send();

            // Recargar los popups
            $this->cargarPopups();

        } catch (\Exception $e) {
            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al eliminar popup')
                ->body('Ha ocurrido un error al eliminar el popup: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function updatedBusqueda(): void
    {
        $this->currentPage = 1;
    }

    public function gotoPage(int $page): void
    {
        $this->currentPage = $page;
    }
}
