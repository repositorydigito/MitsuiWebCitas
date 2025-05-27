<?php

namespace App\Filament\Pages;

use App\Models\PopUp;
use Filament\Pages\Page;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Livewire\WithFileUploads;

class GestionPopUp extends Page
{
    use WithFileUploads;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Gestión Pop up';

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
                $imagenUrl = $popup->imagen_path;

                // Si la imagen es una ruta relativa, convertirla a URL completa
                if (! filter_var($imagenUrl, FILTER_VALIDATE_URL)) {
                    $imagenUrl = asset('storage/'.$imagenUrl);
                }

                $this->popups->push([
                    'id' => $popup->id,
                    'imagen' => $imagenUrl,
                    'nombre' => $popup->nombre,
                    'medidas' => $popup->medidas,
                    'formato' => $popup->formato,
                    'url_wp' => $popup->url_wp,
                    'activo' => $popup->activo,
                ]);
            }

            // Inicializar el estado de los popups
            foreach ($this->popups as $popup) {
                $this->estadoPopups[$popup['id']] = $popup['activo'];
            }

            Log::info('[GestionPopUp] Se cargaron '.$this->popups->count().' popups desde la base de datos');
        } catch (\Exception $e) {
            Log::error('[GestionPopUp] Error al cargar popups: '.$e->getMessage());

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
                return str_contains(strtolower($popup['nombre']), $terminoBusqueda);
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
                    $popup['activo'] = $this->estadoPopups[$id];
                }

                return $popup;
            });

            // Actualizar el estado en la base de datos
            $popup = PopUp::findOrFail($id);
            $popup->activo = $this->estadoPopups[$id];
            $popup->save();

            // Mostrar notificación con el estado actual
            $estado = $this->estadoPopups[$id] ? 'activado' : 'desactivado';
            \Filament\Notifications\Notification::make()
                ->title('Estado actualizado')
                ->body("El popup ha sido {$estado}")
                ->success()
                ->send();

            Log::info("[GestionPopUp] Se actualizó el estado del popup {$id} a {$estado}");
        } catch (\Exception $e) {
            Log::error("[GestionPopUp] Error al actualizar estado del popup {$id}: ".$e->getMessage());

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
        $this->reset(['imagen', 'imagenPreview']);
        $this->isFormModalOpen = true;
    }

    /**
     * Método para editar un popup existente
     */
    public function editarPopup(int $id): void
    {
        try {
            // Buscar el popup en la colección
            $popup = $this->popups->firstWhere('id', $id);

            if ($popup) {
                $this->accionFormulario = 'editar';
                $this->popupEnEdicion = $popup;
                $this->imagenPreview = $popup['imagen'];
                $this->isFormModalOpen = true;

                Log::info("[GestionPopUp] Editando popup {$id} - {$popup['nombre']}");
            } else {
                throw new \Exception("No se encontró el popup con ID {$id}");
            }
        } catch (\Exception $e) {
            Log::error("[GestionPopUp] Error al editar popup {$id}: ".$e->getMessage());

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
                'popupEnEdicion.nombre' => 'required|string|max:255',
                'imagen' => 'nullable|image|max:10240', // 10MB máximo
                'popupEnEdicion.url_wp' => 'nullable|string|max:255',
            ], [
                'popupEnEdicion.nombre.required' => 'El nombre es obligatorio',
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

            $popup->nombre = $this->popupEnEdicion['nombre'];
            $popup->url_wp = $this->popupEnEdicion['url_wp'] ?? null;
            $popup->activo = $this->popupEnEdicion['activo'] ?? true;

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
                $popup->medidas = "{$width} x {$height} px";
                $popup->formato = strtoupper($formato);

                // Generar nombre único para la imagen
                $nombreArchivo = 'popup_'.time().'.'.$formato;
                $rutaImagen = 'popups/'.$nombreArchivo;

                // Guardar la imagen en el almacenamiento
                $img->save(storage_path('app/public/'.$rutaImagen));

                // Guardar la ruta en la base de datos
                $popup->imagen_path = $rutaImagen;
            } elseif ($this->accionFormulario === 'editar') {
                // Si estamos editando y no se ha subido una nueva imagen, mantener las dimensiones y formato existentes
                if (isset($this->popupEnEdicion['medidas'])) {
                    $popup->medidas = $this->popupEnEdicion['medidas'];
                }

                if (isset($this->popupEnEdicion['formato'])) {
                    $popup->formato = $this->popupEnEdicion['formato'];
                }

                // No modificar la ruta de la imagen
                // $popup->imagen_path se mantiene sin cambios
            } else {
                // Si es una creación nueva y no se ha subido imagen, usar valores por defecto
                $popup->medidas = '80 x 56 px';
                $popup->formato = 'JPG';
                $popup->imagen_path = 'popups/default.jpg'; // Imagen por defecto
            }

            // Guardar el popup
            $popup->save();

            // Mostrar notificación de éxito
            \Filament\Notifications\Notification::make()
                ->title('Popup guardado')
                ->body('El popup ha sido guardado correctamente')
                ->success()
                ->send();

            Log::info("[GestionPopUp] Se guardó el popup {$popup->id} - {$popup->nombre}");

            // Cerrar el modal y recargar los popups
            $this->isFormModalOpen = false;
            $this->reset(['imagen', 'imagenPreview']);
            $this->cargarPopups();
        } catch (\Exception $e) {
            Log::error('[GestionPopUp] Error al guardar popup: '.$e->getMessage());

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

    /**
     * Se ejecuta cuando se sube una imagen
     */
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
            $this->popupEnEdicion['medidas'] = "{$width} x {$height} px";
            $this->popupEnEdicion['formato'] = strtoupper($formato);

            Log::info("[GestionPopUp] Imagen subida: {$width}x{$height} {$formato}");
        } catch (\Exception $e) {
            Log::error('[GestionPopUp] Error al procesar imagen: '.$e->getMessage());

            // Mostrar notificación de error
            \Filament\Notifications\Notification::make()
                ->title('Error al procesar imagen')
                ->body('Ha ocurrido un error al procesar la imagen: '.$e->getMessage())
                ->danger()
                ->send();

            // Limpiar la imagen
            $this->reset(['imagen', 'imagenPreview']);
        }
    }

    /**
     * Limpia el filtro de búsqueda
     */
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

    /**
     * Se ejecuta cuando cambia el valor de búsqueda
     */
    public function updatedBusqueda(): void
    {
        $this->currentPage = 1;
    }
}
