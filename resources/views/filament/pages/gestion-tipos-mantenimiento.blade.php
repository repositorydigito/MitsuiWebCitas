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
                wire:click="agregarTipo"
                color="primary"
                class="flex items-center"
            >
                Agregar
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
                            Kilómetros
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Descripción
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($this->tiposPaginados as $tipo)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $tipo['name'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $tipo['code'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ number_format($tipo['kilometers']) }} Km
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                {{ $tipo['description'] ?? 'Sin descripción' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                <div class="flex justify-center items-center gap-6">
                                    <button
                                        wire:click="editarTipo({{ $tipo['id'] }})"
                                        class="text-primary-600 hover:text-primary-900 flex"
                                        title="Editar"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Editar
                                    </button>

                                    <button
                                        wire:click="eliminarTipo({{ $tipo['id'] }})"
                                        class="text-primary-600 hover:text-primary-900 flex"
                                        title="Eliminar"
                                        onclick="return confirm('¿Estás seguro de que deseas eliminar este tipo de mantenimiento?')"
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
                                                id="toggle-{{ $tipo['id'] }}"
                                                wire:click="toggleEstado({{ $tipo['id'] }})"
                                                @if($estadoTipos[$tipo['id']]) checked @endif
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
                                No se encontraron tipos de mantenimiento
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($this->tiposPaginados->hasPages())
            <div class="flex justify-center">
                <nav class="flex items-center space-x-2">
                    @if($this->tiposPaginados->onFirstPage())
                        <span class="px-3 py-2 text-gray-400">Anterior</span>
                    @else
                        <x-filament::button
                            wire:click="gotoPage({{ $this->tiposPaginados->currentPage() - 1 }})"
                            color="gray"
                            size="sm"
                        >
                            Anterior
                        </x-filament::button>
                    @endif

                    @foreach($this->tiposPaginados->getUrlRange(1, $this->tiposPaginados->lastPage()) as $page => $url)
                        @if($page == $this->tiposPaginados->currentPage())
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

                    @if($this->tiposPaginados->hasMorePages())
                        <x-filament::button
                            wire:click="gotoPage({{ $this->tiposPaginados->currentPage() + 1 }})"
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

    <!-- Modal para agregar/editar tipo -->
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
                            {{ $accionFormulario === 'crear' ? 'Agregar Tipo de Mantenimiento' : 'Editar Tipo de Mantenimiento' }}
                        </h3>
                        <div class="w-full">

                            <div class="flex flex-col space-y-4">
                                <!-- Campo Nombre -->
                                <div class="flex flex-col">
                                    <label for="name" class="text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                    <input
                                        type="text"
                                        id="name"
                                        wire:model="tipoEnEdicion.name"
                                        class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        placeholder="Nombre del tipo (ej: 5,000 Km)"
                                    >
                                    @error('tipoEnEdicion.name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Campo Código -->
                                <div class="flex flex-col">
                                    <label for="code" class="text-sm font-medium text-gray-700 mb-1">Código</label>
                                    <input
                                        type="text"
                                        id="code"
                                        wire:model="tipoEnEdicion.code"
                                        class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        placeholder="Código único (ej: MANT_5K)"
                                    >
                                    @error('tipoEnEdicion.code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Campo Kilómetros -->
                                <div class="flex flex-col">
                                    <label for="kilometers" class="text-sm font-medium text-gray-700 mb-1">Kilómetros</label>
                                    <input
                                        type="number"
                                        id="kilometers"
                                        wire:model="tipoEnEdicion.kilometers"
                                        class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        placeholder="Kilómetros (ej: 5000)"
                                    >
                                    @error('tipoEnEdicion.kilometers') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <!-- Campo Descripción -->
                                <div class="flex flex-col">
                                    <label for="description" class="text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                    <textarea
                                        id="description"
                                        wire:model="tipoEnEdicion.description"
                                        rows="3"
                                        class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        placeholder="Descripción (opcional)"
                                    ></textarea>
                                </div>

                                <!-- Campo Activo -->
                                <div class="flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="is_active"
                                        wire:model="tipoEnEdicion.is_active"
                                        class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                    >
                                    <label for="is_active" class="ml-2 text-sm text-gray-700">Activo</label>
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
                                        wire:click="guardarTipo"
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
