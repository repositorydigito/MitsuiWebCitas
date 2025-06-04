<x-filament-panels::page>
<div class="flex flex-col md:flex-row justify-between items-center mb-4 gap-4">
    <p class="text-gray-600">Gestiona las imágenes de la plataforma.</p>

    <div class="flex flex-col md:flex-row items-center gap-4">
        <!-- Búsqueda por nombre -->
        <div class="relative w-full md:w-64">
            <input
                type="text"
                wire:model.live.debounce.300ms="busqueda"
                placeholder="Buscar por nombre..."
                class="w-full px-4 border border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
            >

            <!-- Botón para limpiar búsqueda -->
            @if(!empty($busqueda))
                <button
                    wire:click="limpiarBusqueda"
                    class="absolute inset-y-0 right-0 flex items-center text-gray-500 hover:text-gray-700"
                    title="Limpiar búsqueda"
                >
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            @endif
        </div>

        <!-- Botón Agregar opción -->
        <button
            type="button"
            wire:click="agregarOpcion"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 whitespace-nowrap"
        >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Agregar opción
        </button>
    </div>
</div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-primary-600">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Imagen
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Nombre
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Medidas
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Formato
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                            URL WP
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($this->popupsPaginados as $index => $popup)
                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <img src="{{ $popup['image'] }}" alt="{{ $popup['name'] }}" class="h-10 w-10 object-cover rounded">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $popup['name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $popup['sizes'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $popup['format'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 truncate max-w-xs">
                                {{ $popup['url_wp'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center" onclick="event.stopPropagation();">
                                <div class="flex justify-center items-center gap-6">
                                    <button
                                        wire:click="verImagen({{ $popup['id'] }})"
                                        class="text-primary-600 hover:text-primary-900 flex items-center"
                                        title="Ver imagen"
                                    >
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        Ver
                                    </button>

                                    <button
                                        wire:click="editarPopup({{ $popup['id'] }})"
                                        class="text-blue-600 hover:text-blue-900 flex items-center"
                                        title="Editar popup"
                                    >
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Editar
                                    </button>

                                    <div class="inline-flex items-center">
                                        <label class="relative inline-block">
                                            <input
                                                type="checkbox"
                                                id="toggle-{{ $popup['id'] }}"
                                                wire:click="toggleEstado({{ $popup['id'] }})"
                                                @if($estadoPopups[$popup['id']]) checked @endif
                                                class="toggle-checkbox"
                                            />
                                            <span class="toggle-label"></span>
                                        </label>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                <div class="flex flex-col items-center justify-center py-6">
                                    <svg class="w-8 h-8 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-gray-500 text-lg font-medium mb-2">
                                        @if(!empty($busqueda))
                                            No se encontraron resultados para "{{ $busqueda }}"
                                        @else
                                            No se encontraron pop ups
                                        @endif
                                    </p>
                                    <p class="text-gray-400 text-sm">
                                        @if(!empty($busqueda))
                                            <button wire:click="limpiarBusqueda" class="text-primary-600 hover:text-primary-800 underline">
                                                Limpiar búsqueda
                                            </button>
                                        @else
                                            Agrega una nueva opción para comenzar
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    <div class="mt-4 flex justify-end">
        {{ $this->popupsPaginados->links('vendor.pagination.default') }}
    </div>

    <!-- Modal para ver imagen -->
    @if($isModalOpen)
    <div class="fixed inset-0 z-[9999] overflow-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Overlay de fondo oscuro (clic para cerrar) -->
        <div class="fixed inset-0 bg-black/50" aria-hidden="true" wire:click="cerrarModal"></div>

        <!-- Contenedor para centrar vertical y horizontalmente -->
        <div class="flex items-center justify-center min-h-screen px-4 py-6 text-center">
            <!-- Modal -->
            <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-lg w-full z-[9999]" @click.stop>
                <!-- Contenido del modal -->
                <div class="bg-white px-6 pt-6 pb-6 relative">
                    <!-- Botón de cerrar en la esquina superior derecha -->
                    <div class="absolute top-0 right-0 pt-3 pr-3 z-[10000]">
                        <button type="button" wire:click="cerrarModal" class="bg-white rounded-md text-gray-600 hover:text-gray-900 focus:outline-none">
                            <span class="sr-only">Cerrar</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex flex-col items-center">
                        <h3 class="text-xl font-bold text-primary-600 text-center mb-4" id="modal-title">
                            {{ $imagenNombre }}
                        </h3>
                        <div class="w-full flex justify-center">
                            <img src="{{ $imagenUrl }}" alt="{{ $imagenNombre }}" class="max-w-full h-auto">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal para agregar/editar popup -->
    @if($isFormModalOpen)
    <div class="fixed inset-0 z-[9999] overflow-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Overlay de fondo oscuro (clic para cerrar) -->
        <div class="fixed inset-0 bg-black/50" aria-hidden="true" wire:click="cerrarFormModal"></div>

        <!-- Contenedor para centrar vertical y horizontalmente -->
        <div class="flex items-center justify-center min-h-screen px-4 py-6 text-center">
            <!-- Modal -->
            <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-lg w-full z-[9999]" @click.stop>
                <!-- Contenido del modal -->
                <div class="bg-white px-6 pt-6 pb-6 relative">
                    <!-- Botón de cerrar en la esquina superior derecha -->
                    <div class="absolute top-0 right-0 pt-3 pr-3 z-[10000]">
                        <button type="button" wire:click="cerrarFormModal" class="bg-white rounded-md text-gray-600 hover:text-gray-900 focus:outline-none">
                            <span class="sr-only">Cerrar</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex flex-col">
                        <h3 class="text-xl font-bold text-primary-600 text-center mb-4" id="modal-title">
                            {{ $accionFormulario === 'crear' ? 'Agregar nueva opción' : 'Editar opción: ' . ($popupEnEdicion['name'] ?? '') }}
                        </h3>

                        <form wire:submit.prevent="guardarPopup" class="space-y-4">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                                <input type="text" id="name" wire:model.defer="popupEnEdicion.name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>

                            <div>
                                <label for="imagen" class="block text-sm font-medium text-gray-700">Imagen</label>
                                <div class="mt-1 flex flex-col space-y-2">
                                    <div class="flex items-center">
                                        <input type="file" wire:model="imagen" id="imagen" class="sr-only" accept="image/*">
                                        <label for="imagen" class="cursor-pointer bg-white py-2 px-3 border border-gray-300 rounded-md shadow-sm text-sm leading-4 font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                            Seleccionar archivo
                                        </label>
                                        <span class="px-4 text-sm text-gray-500">JPG, PNG, GIF hasta 10MB</span>
                                    </div>

                                    <!-- Vista previa de la imagen -->
                                    @if($imagenPreview)
                                        <div class="mt-2">
                                            <img src="{{ $imagenPreview }}" alt="Vista previa" class="h-32 w-auto object-contain border border-gray-300 rounded-md">
                                            <div class="mt-1 text-xs text-gray-500">
                                                <span>{{ $popupEnEdicion['sizes'] ?? '' }}</span>
                                                <span class="ml-2">{{ $popupEnEdicion['format'] ?? '' }}</span>
                                            </div>
                                        </div>
                                    @endif

                                    <!-- Mensaje de carga -->
                                    <div wire:loading wire:target="imagen" class="text-sm text-primary-600">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-primary-600 inline" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Procesando imagen...
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label for="url_wp" class="block text-sm font-medium text-gray-700">URL WhatsApp</label>
                                <input type="text" id="url_wp" wire:model.defer="popupEnEdicion.url_wp" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            </div>

                            <div class="flex items-center">
                                <input id="is_active" type="checkbox" wire:model.defer="popupEnEdicion.is_active" class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                <label for="is_active" class="px-2 block text-sm text-gray-900">Activo</label>
                            </div>

                            <div class="flex justify-center pt-4 gap-4">
                                <button type="button" wire:click="cerrarFormModal" class="mr-3 bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    Cancelar
                                </button>
                                <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                    Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <style>
        /* Estilos para el toggle switch */
        .toggle-checkbox {
            position: absolute;
            opacity: 0;
            height: 0;
            width: 0;
        }

        .toggle-label {
            position: relative;
            display: block;
            height: 24px;
            width: 48px;
            background-color: #ccc;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .toggle-label:after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 20px;
            height: 20px;
            background-color: white;
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
        }

        .toggle-checkbox:checked + .toggle-label {
            background-color: #3b82f6;
        }

        .toggle-checkbox:checked + .toggle-label:after {
            left: calc(100% - 22px);
        }

        .toggle-checkbox:focus + .toggle-label {
            box-shadow: 0 0 1px #3b82f6;
        }
    </style>
</x-filament-panels::page>
