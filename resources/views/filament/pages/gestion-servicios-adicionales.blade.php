<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header con botón agregar y búsqueda -->
        <div class="flex justify-between items-center mb-4">
            <div class="flex items-center gap-4 space-x-4">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live="busqueda"
                        placeholder="Buscar..."
                        class="w-64"
                    />
                </x-filament::input.wrapper>

                @if($busqueda)
                    <x-filament::button
                        wire:click="limpiarBusqueda"
                        color="gray"
                        size="sm"
                    >
                        Limpiar
                    </x-filament::button>
                @endif
            </div>

            <x-filament::button
                wire:click="agregarServicio"
                color="primary"
            >
                Agregar Servicio
            </x-filament::button>
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-primary-500">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Nombre
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Código
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Precio
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Duración
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->serviciosPaginados as $servicio)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $servicio['name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $servicio['code'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                @if($servicio['price'])
                                    S/ {{ number_format($servicio['price'], 2) }}
                                @else
                                    Sin precio
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $servicio['duration_minutes'] }} min
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                <div class="flex justify-center items-center gap-6">
                                    <button
                                        wire:click="editarServicio({{ $servicio['id'] }})"
                                        class="text-primary-600 hover:text-primary-900 flex"
                                        title="Editar"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Editar
                                    </button>

                                    <button
                                        wire:click="eliminarServicio({{ $servicio['id'] }})"
                                        class="text-primary-600 hover:text-primary-900 flex"
                                        title="Eliminar"
                                        onclick="return confirm('¿Estás seguro de que deseas eliminar este servicio adicional?')"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Eliminar
                                    </button>

                                    <div class="inline-flex items-center">
                                        <label class="relative inline-block">
                                            <input
                                                type="checkbox"
                                                id="toggle-{{ $servicio['id'] }}"
                                                wire:click="toggleEstado({{ $servicio['id'] }})"
                                                @if($estadoServicios[$servicio['id']]) checked @endif
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
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                No se encontraron servicios adicionales
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($this->serviciosPaginados->hasPages())
            <div class="flex justify-center">
                <nav class="flex items-center space-x-2">
                    @if($this->serviciosPaginados->onFirstPage())
                        <span class="px-3 py-2 text-gray-400">Anterior</span>
                    @else
                        <x-filament::button
                            wire:click="gotoPage({{ $this->serviciosPaginados->currentPage() - 1 }})"
                            color="gray"
                            size="sm"
                        >
                            Anterior
                        </x-filament::button>
                    @endif

                    @foreach($this->serviciosPaginados->getUrlRange(1, $this->serviciosPaginados->lastPage()) as $page => $url)
                        @if($page == $this->serviciosPaginados->currentPage())
                            <span class="px-3 py-2 bg-blue-500 text-white rounded">{{ $page }}</span>
                        @else
                            <x-filament::button
                                wire:click="gotoPage({{ $page }})"
                                color="gray"
                                size="sm"
                            >
                                {{ $page }}
                            </x-filament::button>
                        @endif
                    @endforeach

                    @if($this->serviciosPaginados->hasMorePages())
                        <x-filament::button
                            wire:click="gotoPage({{ $this->serviciosPaginados->currentPage() + 1 }})"
                            color="gray"
                            size="sm"
                        >
                            Siguiente
                        </x-filament::button>
                    @else
                        <span class="px-3 py-2 text-gray-400">Siguiente</span>
                    @endif
                </nav>
            </div>
        @endif
    </div>

    <!-- Modal para agregar/editar servicio -->
    @if($isFormModalOpen)
    <div class="fixed inset-0 z-[9999] overflow-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Overlay de fondo oscuro (clic para cerrar) -->
        <div class="fixed inset-0 bg-black/50" aria-hidden="true" wire:click="cerrarFormModal"></div>

        <!-- Contenedor para centrar vertical y horizontalmente -->
        <div class="flex items-center justify-center min-h-screen px-4 py-6 text-center">

            <!-- Modal -->
            <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-2xl w-full z-[9999]" @click.stop>
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

                    <div class="flex flex-col items-center">
                        <h3 class="text-xl font-bold text-primary-600 text-center mb-4" id="modal-title">
                            {{ $accionFormulario === 'crear' ? 'Agregar Servicio Adicional' : 'Editar Servicio Adicional' }}
                        </h3>
                        <div class="w-full">

                            <div class="flex flex-col space-y-4">
                                <!-- Campo Nombre -->
                                <div class="flex flex-col">
                                    <label for="service_name" class="text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                    <input
                                        type="text"
                                        id="service_name"
                                        wire:model="servicioEnEdicion.name"
                                        class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        placeholder="Nombre del servicio"
                                    >
                                    @error('servicioEnEdicion.name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Campo Código -->
                                <div class="flex flex-col">
                                    <label for="service_code" class="text-sm font-medium text-gray-700 mb-1">Código</label>
                                    <input
                                        type="text"
                                        id="service_code"
                                        wire:model="servicioEnEdicion.code"
                                        class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        placeholder="Código único"
                                    >
                                    @error('servicioEnEdicion.code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Campos Precio y Duración -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex flex-col">
                                        <label for="service_price" class="text-sm font-medium text-gray-700 mb-1">Precio</label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            id="service_price"
                                            wire:model="servicioEnEdicion.price"
                                            class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                            placeholder="Precio (opcional)"
                                        >
                                        @error('servicioEnEdicion.price') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="flex flex-col">
                                        <label for="service_duration" class="text-sm font-medium text-gray-700 mb-1">Duración (min)</label>
                                        <input
                                            type="number"
                                            id="service_duration"
                                            wire:model="servicioEnEdicion.duration_minutes"
                                            class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                            placeholder="Duración en minutos"
                                        >
                                        @error('servicioEnEdicion.duration_minutes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <!-- Campo Descripción -->
                                <div class="flex flex-col">
                                    <label for="service_description" class="text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                    <textarea
                                        id="service_description"
                                        wire:model="servicioEnEdicion.description"
                                        rows="3"
                                        class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        placeholder="Descripción (opcional)"
                                    ></textarea>
                                </div>

                                <!-- Campo Activo -->
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="service_active"
                                        wire:model="servicioEnEdicion.is_active"
                                        class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                    >
                                    <label for="service_active" class="ml-2 text-sm text-gray-700">Activo</label>
                                </div>

                                <!-- Botones -->
                                <div class="flex justify-end space-x-2 mt-6 gap-4">
                                    <button
                                        type="button"
                                        wire:click="cerrarFormModal"
                                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                                    >
                                        Cancelar
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="guardarServicio"
                                        class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    >
                                        {{ $accionFormulario === 'crear' ? 'Crear' : 'Actualizar' }}
                                    </button>
                                </div>
                            </div>
                        </div>
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
