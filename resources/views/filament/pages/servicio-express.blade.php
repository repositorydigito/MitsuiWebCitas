<x-filament-panels::page>
<p class="text-gray-600">Registra los vehículos que son parte de servicio Express.</p>
<div class="flex items-center justify-end mb-4">
    <div class="flex space-x-4 gap-4">
        <button
            type="button"
            wire:click="descargarPlantilla"
            class="inline-flex items-center gap-2 px-2 py-2 text-sm font-medium text-primary-600 bg-white border border-primary-600 rounded-lg hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            Descargar plantilla
        </button>

        <button
            type="button"
            wire:click="registrarVehiculo"
            class="inline-flex items-center gap-2 px-2 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Registrar vehículo
        </button>
    </div>
</div>


    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-primary-600">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Código
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Tipo
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Modelo
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Marca
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Año
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Mantenimiento
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Local
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($this->vehiculosPaginados as $index => $vehiculo)
                        <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-100">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $vehiculo['code'] ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $vehiculo['type'] ?? '-' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $vehiculo['model'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $vehiculo['brand'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $vehiculo['year'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                @if(is_array($vehiculo['maintenance']))
                                    {{ implode(', ', $vehiculo['maintenance']) }}
                                @else
                                    {{ $vehiculo['maintenance'] }}
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $vehiculo['premises'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                <div class="flex justify-center items-center gap-6">
                                    <button
                                        wire:click="editar({{ $vehiculo['id'] }})"
                                        class="text-primary-600 hover:text-primary-900 flex"
                                        title="Editar"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Editar
                                    </button>

                                    <button
                                        wire:click="eliminar({{ $vehiculo['id'] }})"
                                        class="text-primary-600 hover:text-primary-900 flex"
                                        title="Eliminar"
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
                                                id="toggle-{{ $vehiculo['id'] }}"
                                                wire:click="toggleEstado({{ $vehiculo['id'] }})"
                                                @if($estadoVehiculos[$vehiculo['id']]) checked @endif
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
                            <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                <div class="flex flex-col items-center justify-center py-6">
                                    <svg class="w-8 h-8 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-gray-500 text-lg font-medium mb-2">No se encontraron vehículos</p>
                                    <p class="text-gray-400 text-sm">Registra un nuevo vehículo para comenzar</p>
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
        {{ $this->vehiculosPaginados->links('vendor.pagination.default') }}
    </div>

    <!-- Modal para registrar vehículos -->
    @if($isModalOpen)
    <div class="fixed inset-0 z-[9999] overflow-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Overlay de fondo oscuro (clic para cerrar) -->
        <div class="fixed inset-0 bg-black/50" aria-hidden="true" wire:click="cerrarModal"></div>

        <!-- Contenedor para centrar vertical y horizontalmente -->
        <div class="flex items-center justify-center px-4 py-6 text-center min-h-screen" style="align-items: flex-start; margin-top: 100px;">

            <!-- Modal -->
            <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-lg z-[9999]" @click.stop>
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
                            Registrar vehículos
                        </h3>
                        <div class="w-full">
                            <p class="text-sm text-gray-700 text-center mb-4">
                                Selecciona el archivo xlsx con el listado de modelos para realizar una carga masiva.
                            </p>

                            <div class="flex flex-col items-center space-y-6">
                                <!-- Campo para seleccionar archivo -->
                                <div class="w-full flex">
                                    <div class="flex-1 border border-gray-300 rounded-l-md p-2 bg-gray-50 text-gray-500">
                                        {{ $nombreArchivo }}
                                    </div>
                                    <label for="file-upload" class="cursor-pointer bg-primary-600 text-white px-4 py-2 rounded-r-md hover:bg-primary-700">
                                        Seleccionar archivo
                                        <input
                                            id="file-upload"
                                            type="file"
                                            wire:model.live="archivoExcel"
                                            class="hidden"
                                            accept=".xlsx,.xls"
                                        />
                                    </label>
                                </div>

                                <!-- Botón de registrar -->
                                <button
                                    type="button"
                                    wire:click="cargarArchivo"
                                    class="w-full sm:w-1/2 inline-flex justify-center items-center px-4 py-3 bg-primary-600 text-white font-medium rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                                >
                                    Registrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de Edición -->
    @if($isEditModalOpen)
    <div class="fixed inset-0 z-[9999] overflow-hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <!-- Overlay de fondo oscuro (clic para cerrar) -->
        <div class="fixed inset-0 bg-black/50" aria-hidden="true" wire:click="cerrarModalEdicion"></div>

        <!-- Contenedor para centrar vertical y horizontalmente -->
        <div class="flex items-center justify-center px-4 py-6 text-center min-h-screen" style="align-items: flex-start; margin-top: 100px;">

            <!-- Modal -->
            <div class="relative inline-block bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-7xl w-full z-[9999]"
                style="max-height: 65vh; display: flex; flex-direction: column;">
                <!-- Contenido del modal -->
                <div class="bg-white px-6 pt-6 pb-6 relative flex-1 overflow-y-auto">
                    <!-- Botón de cerrar en la esquina superior derecha -->
                    <div class="absolute top-0 right-0 pt-3 pr-3 z-[10000]">
                        <button type="button" wire:click="cerrarModalEdicion" class="bg-white rounded-md text-gray-600 hover:text-gray-900 focus:outline-none">
                            <span class="sr-only">Cerrar</span>
                            <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="flex flex-col items-center">
                        <h3 class="text-xl font-bold text-primary-600 text-center mb-4" id="modal-title">
                            Editar vehículo
                        </h3>
                        <div class="w-full">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Primera columna: todos los campos del formulario -->
                                <div class="flex flex-col space-y-4 col-span-1">
                                    <!-- Campo Código -->
                                    <div class="flex flex-col">
                                        <label for="code" class="text-sm font-medium text-gray-700 mb-1">Código</label>
                                        <input
                                            type="text"
                                            id="code"
                                            wire:model.live="vehiculoEnEdicion.code"
                                            class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                            placeholder="Ingrese el código"
                                        >
                                    </div>
                                    <!-- Campo Tipo -->
                                    <div class="flex flex-col">
                                        <label for="type" class="text-sm font-medium text-gray-700 mb-1">Tipo</label>
                                        <input
                                            type="text"
                                            id="type"
                                            wire:model.live="vehiculoEnEdicion.type"
                                            class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                            placeholder="Ingrese el tipo"
                                        >
                                    </div>
                                    <!-- Campo Modelo -->
                                    <div class="flex flex-col">
                                        <label for="model" class="text-sm font-medium text-gray-700 mb-1">Modelo</label>
                                        <input
                                            type="text"
                                            id="model"
                                            wire:model.live="vehiculoEnEdicion.model"
                                            class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                            placeholder="Ingrese el modelo"
                                        >
                                    </div>
                                    <!-- Campo Marca -->
                                    <div class="flex flex-col">
                                        <label for="brand" class="text-sm font-medium text-gray-700 mb-1">Marca</label>
                                        <input
                                            type="text"
                                            id="brand"
                                            wire:model.live="vehiculoEnEdicion.brand"
                                            class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                            placeholder="Ingrese la marca"
                                        >
                                    </div>
                                    <!-- Campo Año -->
                                    <div class="flex flex-col">
                                        <label for="year" class="text-sm font-medium text-gray-700 mb-1">Año</label>
                                        <input
                                            type="text"
                                            id="year"
                                            wire:model.live="vehiculoEnEdicion.year"
                                            class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                            placeholder="Ingrese el año"
                                        >
                                    </div>
                                    <!-- Campo Local -->
                                    <div class="flex flex-col">
                                        <label for="premises" class="text-sm font-medium text-gray-700 mb-1">Local</label>
                                        <select
                                            id="premises"
                                            wire:model.live="vehiculoEnEdicion.premises"
                                            class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        >
                                            <option value="">Seleccione un local</option>
                                            @foreach($localesDisponibles as $codigo => $nombre)
                                                <option value="{{ $nombre }}">{{ $nombre }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <!-- Tipos de Mantenimiento: abarca las otras columnas -->
                                <div class="flex flex-col col-span-1 md:col-span-2">
                                    <label class="text-sm font-medium text-gray-700 mb-1">Tipos de Mantenimiento</label>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2 max-h-64 overflow-y-auto border border-gray-300 rounded-md p-2">
                                        @foreach($tiposMantenimientoDisponibles as $id => $nombre)
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="checkbox"
                                                    id="maintenance_{{ $id }}"
                                                    value="{{ $nombre }}"
                                                    wire:model="vehiculoEnEdicion.maintenance"
                                                    class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                                >
                                                <label for="maintenance_{{ $id }}" class="ml-2 text-sm text-gray-700">
                                                    {{ $nombre }}
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Selecciona uno o más tipos de mantenimiento</p>
                                </div>
                            </div>
                            <!-- Botón de guardar -->
                            <button
                                type="button"
                                wire:click="guardarCambios"
                                class="w-full inline-flex justify-center items-center px-4 py-3 bg-primary-600 text-white font-medium rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 mt-4"
                            >
                                Guardar cambios
                            </button>
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
