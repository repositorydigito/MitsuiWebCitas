<x-filament-panels::page>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-bold text-blue-900">Lista de Modelos</h1>
            <button
                wire:click="abrirModal"
                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Nuevo Modelo
            </button>
        </div>
        <br>

        <!-- Tabla de modelos -->
        <div class="overflow-x-auto rounded-lg">
            <table class="w-full bg-white border border-gray-200">
                <thead>
                    <tr class="bg-primary-500">
                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Código</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Nombre</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Marca</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Descripción</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Estado</th>
                        <th class="py-3 px-4 text-center text-sm font-medium text-white">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($modelos as $modelo)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-sm text-gray-700">{{ $modelo->code }}</td>
                            <td class="py-3 px-4 text-sm text-gray-700">{{ $modelo->name }}</td>
                            <td class="py-3 px-4 text-sm text-gray-700">{{ $modelo->brand }}</td>
                            <td class="py-3 px-4 text-sm text-gray-700">{{ $modelo->description ?: '-' }}</td>
                            <td class="py-3 px-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs {{ $modelo->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $modelo->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-700">
                                <div class="flex justify-between space-x-2">
                                    <button
                                        wire:click="abrirModalAnos({{ $modelo->id }})"
                                        class="text-blue-600 hover:text-blue-800"
                                        title="Gestionar Años"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z" />
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="abrirModal({{ $modelo->id }})"
                                        class="text-primary-600 hover:text-primary-800"
                                        title="Editar"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="toggleEstado({{ $modelo->id }})"
                                        class="{{ $modelo->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}"
                                        title="{{ $modelo->is_active ? 'Desactivar' : 'Activar' }}"
                                    >
                                        @if($modelo->is_active)
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                                            </svg>
                                        @else
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </button>
                                    <button
                                        wire:click="eliminarModelo({{ $modelo->id }})"
                                        class="text-red-600 hover:text-red-800"
                                        title="Eliminar"
                                        onclick="return confirm('¿Estás seguro de que deseas eliminar este modelo?')"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-4 px-4 text-center text-gray-500">No hay modelos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para crear/editar modelo -->
    @if($modalVisible)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{}" x-cloak>
            <!-- Overlay de fondo oscuro (clic para cerrar) -->
            <div class="fixed inset-0 bg-black/50" wire:click="cerrarModal" aria-hidden="true"></div>

            <!-- Modal centrado -->
            <div class="flex items-center justify-center min-h-screen p-4">
                <!-- Panel del modal -->
                <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4 relative z-10"
                     x-on:click.outside="$wire.cerrarModal()"
                >
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-blue-900">{{ $editMode ? 'Editar Modelo' : 'Nuevo Modelo' }}</h2>
                            <button wire:click="cerrarModal" class="text-gray-500 hover:text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="codigo" class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
                                <input
                                    type="text"
                                    wire:model="formData.code"
                                    id="codigo"
                                    class="w-full rounded-lg border {{ $errors['code'] ? 'border-red-500' : 'border-primary-500' }} text-gray-700 py-2 px-3"
                                    placeholder="Ej: COROLLA_CROSS"
                                    {{ $editMode ? 'disabled' : '' }}
                                >
                                @if($errors['code'])
                                    <p class="text-primary-500 text-sm mt-1">Elige una opción disponible.</p>
                                @endif
                            </div>
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                                <input
                                    type="text"
                                    wire:model="formData.name"
                                    id="name"
                                    class="w-full rounded-lg border {{ $errors['name'] ? 'border-red-500' : 'border-primary-500' }} text-gray-700 py-2 px-3"
                                    placeholder="Ej: COROLLA CROSS"
                                >
                                @if($errors['name'])
                                    <p class="text-primary-500 text-sm mt-1">Elige una opción disponible.</p>
                                @endif
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="marca" class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                                <select
                                    wire:model="formData.brand"
                                    id="marca"
                                    class="w-full rounded-lg border border-primary-500 text-gray-700 py-2 px-3"
                                >
                                    <option value="TOYOTA">TOYOTA</option>
                                    <option value="HINO">HINO</option>
                                    <option value="LEXUS">LEXUS</option>
                                </select>
                            </div>
                            <div>
                                <label for="activo" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                <div class="flex items-center mt-2">
                                    <label class="inline-flex items-center gap-2">
                                        <input
                                            type="checkbox"
                                            wire:model="formData.is_active"
                                            class="rounded border-primary-500 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                                        >
                                        <span class="ml-2 text-sm text-gray-700">Activo</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                            <textarea
                                wire:model="formData.description"
                                id="descripcion"
                                class="w-full rounded-lg border border-primary-500 text-gray-700 py-2 px-3"
                                rows="3"
                                placeholder="Descripción del modelo"
                            ></textarea>
                        </div>

                        <div class="flex justify-center mt-6 border-t pt-4 gap-4">
                            <button
                                wire:click="cerrarModal"
                                class="px-4 py-2 border border-primary-500 text-primary-500 rounded-lg hover:bg-gray-50 mr-2"
                            >
                                Cancelar
                            </button>
                            <button
                                wire:click="guardarModelo"
                                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                            >
                                {{ $editMode ? 'Actualizar' : 'Guardar' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal para gestionar años -->
    @if($anosModalVisible)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{}" x-cloak>
            <!-- Overlay de fondo oscuro (clic para cerrar) -->
            <div class="fixed inset-0 bg-black/50" wire:click="cerrarModalAnos" aria-hidden="true"></div>

            <!-- Modal centrado -->
            <div class="flex items-center justify-center min-h-screen p-4">
                <!-- Panel del modal -->
                <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl mx-4 relative z-10"
                     x-on:click.outside="$wire.cerrarModalAnos()"
                >
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-blue-900">Gestionar Años - {{ $currentModeloNombre }}</h2>
                            <button wire:click="cerrarModalAnos" class="text-gray-500 hover:text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <!-- Formulario para agregar nuevo año -->
                        <div class="mb-4">
                            <div class="flex items-end gap-4">
                                <div class="flex-1">
                                    <label for="nuevoAno" class="block text-sm font-medium text-gray-700 mb-1">Nuevo Año</label>
                                    <input
                                        type="text"
                                        wire:model="nuevoAno"
                                        inputmode="numeric"
                                        pattern="\d*"
                                        maxlength="4"
                                        x-on:input="$el.value = $el.value.replace(/\D/g, '').slice(0, 4)"
                                        id="nuevoAno"
                                        class="w-full rounded-lg border {{ $errors['nuevoAno'] ? 'border-red-500' : 'border-primary-500' }} text-gray-700 py-2 px-3"
                                        placeholder="Ej: 2025"
                                    >
                                    @if($errors['nuevoAno'])
                                        <p class="text-primary-500 text-sm mt-1">Ingresa un año válido (4 dígitos).</p>
                                    @endif
                                </div>
                                <button
                                    wire:click="agregarAno"
                                    class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                                >
                                    Agregar
                                </button>
                            </div>
                        </div>

                        <!-- Tabla de años -->
                        <div class="overflow-x-auto rounded-lg">
                            <table class="w-full bg-white border border-gray-200">
                                <thead>
                                    <tr class="bg-primary-500">
                                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Año</th>
                                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Estado</th>
                                        <th class="py-3 px-4 text-center text-sm font-medium text-white">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @forelse ($modeloAnosData as $modeloAno)
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-3 px-4 text-sm text-gray-700">{{ $modeloAno['year'] }}</td>
                                            <td class="py-3 px-4 text-sm">
                                                <span class="px-2 py-1 rounded-full text-xs {{ $modeloAno['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $modeloAno['is_active'] ? 'Activo' : 'Inactivo' }}
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-sm text-gray-700">
                                                <div class="flex justify-center space-x-4">
                                                    <button
                                                        wire:click="toggleEstadoAno({{ $modeloAno['id'] }})"
                                                        class="{{ $modeloAno['is_active'] ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}"
                                                        title="{{ $modeloAno['is_active'] ? 'Desactivar' : 'Activar' }}"
                                                    >
                                                        @if($modeloAno['is_active'])
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                                                            </svg>
                                                        @else
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                            </svg>
                                                        @endif
                                                    </button>
                                                    <button
                                                        wire:click="eliminarAno({{ $modeloAno['id'] }})"
                                                        class="text-red-600 hover:text-red-800"
                                                        title="Eliminar"
                                                        onclick="return confirm('¿Estás seguro de que deseas eliminar este año?')"
                                                    >
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="py-4 px-4 text-center text-gray-500">No hay años registrados para este modelo.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="flex justify-end mt-6 border-t pt-4">
                            <button
                                wire:click="cerrarModalAnos"
                                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                            >
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
