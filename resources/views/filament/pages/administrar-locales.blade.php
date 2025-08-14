<x-filament-panels::page>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-xl font-bold text-blue-900">Lista de Locales</h1>
            <button
                wire:click="abrirModal"
                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 flex items-center"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Nuevo Local
            </button>
        </div>
        <br>

        <!-- Tabla de locales -->
        <div class="overflow-x-auto rounded-lg">
            <table class="w-full bg-white border border-gray-200">
                <thead>
                    <tr class="bg-primary-500">
                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Código</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Nombre</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Marca</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Dirección</th>
                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Ubicación</th>
                        <!-- <th class="py-3 px-4 text-left text-sm font-medium text-white">Horario</th> -->
                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Estado</th>
                        <th class="py-3 px-4 text-center text-sm font-medium text-white">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($locales as $local)
                        <tr class="hover:bg-gray-50">
                            <td class="py-3 px-4 text-sm text-gray-700">{{ $local->code }}</td>
                            <td class="py-3 px-4 text-sm text-gray-700">{{ $local->name }}</td>
                            <td class="py-3 px-4 text-sm text-gray-700">
                                <span class="px-2 py-1 rounded-full text-xs font-medium
                                    {{ $local->brand === 'Toyota' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $local->brand === 'Lexus' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $local->brand === 'Hino' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ !in_array($local->brand, ['Toyota', 'Lexus', 'Hino']) ? 'bg-gray-100 text-gray-800' : '' }}
                                ">
                                    {{ $local->brand ?: '-' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-700">{{ $local->address ?: '-' }}</td>
                            <td class="py-3 px-4 text-sm text-gray-700">{{ $local->phone ?: '-' }}</td>
                            <!-- <td class="py-3 px-4 text-sm text-gray-700">{{ substr($local->opening_time, 0, 5) }} - {{ substr($local->closing_time, 0, 5) }}</td> -->
                            <td class="py-3 px-4 text-sm">
                                <span class="px-2 py-1 rounded-full text-xs {{ $local->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $local->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-sm text-gray-700">
                                <div class="flex justify-between space-x-2">
                                    <button
                                        wire:click="abrirModal({{ $local->id }})"
                                        class="text-primary-600 hover:text-primary-800"
                                        title="Editar"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </button>
                                    <button
                                        wire:click="toggleEstado({{ $local->id }})"
                                        class="{{ $local->is_active ? 'text-red-600 hover:text-red-800' : 'text-green-600 hover:text-green-800' }}"
                                        title="{{ $local->is_active ? 'Desactivar' : 'Activar' }}"
                                    >
                                        @if($local->is_active)
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
                                        wire:click="eliminarLocal({{ $local->id }})"
                                        class="text-red-600 hover:text-red-800"
                                        title="Eliminar"
                                        onclick="return confirm('¿Estás seguro de que deseas eliminar este local?')"
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
                            <td colspan="8" class="py-4 px-4 text-center text-gray-500">No hay locales registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal para crear/editar local -->
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
                        <h2 class="text-lg font-semibold text-blue-900">{{ $editMode ? 'Editar Local' : 'Nuevo Local' }}</h2>
                        <button wire:click="cerrarModal" class="text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700 mb-1">Código *</label>
                            <input
                                type="text"
                                wire:model="formData.code"
                                id="code"
                                class="w-full rounded-lg border {{ $errors['code'] ? 'border-red-500' : 'border-primary-500' }} text-gray-700 py-2 px-3"
                                placeholder="Ej: local1"
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
                                placeholder="Ej: La Molina"
                            >
                            @if($errors['name'])
                                <p class="text-primary-500 text-sm mt-1">Elige una opción disponible.</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="brand" class="block text-sm font-medium text-gray-700 mb-1">Marca *</label>
                            <select
                                wire:model="formData.brand"
                                id="brand"
                                class="w-full rounded-lg border {{ $errors['brand'] ? 'border-red-500' : 'border-primary-500' }} text-gray-700 py-2 px-3"
                            >
                                <option value="">Selecciona una marca</option>
                                <option value="Toyota">Toyota</option>
                                <option value="Lexus">Lexus</option>
                                <option value="Hino">Hino</option>
                            </select>
                            @if($errors['brand'])
                                <p class="text-primary-500 text-sm mt-1">Selecciona una marca válida.</p>
                            @endif
                        </div>
                        <div>
                            <!-- Espacio vacío para mantener el grid balanceado -->
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
                            <input
                                type="text"
                                wire:model="formData.address"
                                id="address"
                                class="w-full rounded-lg border border-primary-500 text-gray-700 py-2 px-3"
                                placeholder="Ej: Av. La Molina 123"
                            >
                        </div>
                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono</label>
                            <input
                                type="text"
                                wire:model="formData.phone"
                                id="phone"
                                class="w-full rounded-lg border border-primary-500 text-gray-700 py-2 px-3"
                                placeholder="Ej: (01) 123-4567"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="opening_time" class="block text-sm font-medium text-gray-700 mb-1">Horario de apertura</label>
                            <input
                                type="time"
                                wire:model="formData.opening_time"
                                id="opening_time"
                                class="w-full rounded-lg border border-primary-500 text-gray-700 py-2 px-3"
                            >
                        </div>
                        <div>
                            <label for="closing_time" class="block text-sm font-medium text-gray-700 mb-1">Horario de cierre</label>
                            <input
                                type="time"
                                wire:model="formData.closing_time"
                                id="closing_time"
                                class="w-full rounded-lg border border-primary-500 text-gray-700 py-2 px-3"
                            >
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="maps_url" class="block text-sm font-medium text-gray-700 mb-1">URL de Google Maps</label>
                            <input
                                type="url"
                                wire:model="formData.maps_url"
                                id="maps_url"
                                class="w-full rounded-lg border border-primary-500 text-gray-700 py-2 px-3"
                                placeholder="https://maps.google.com/..."
                            >
                        </div>
                        <div>
                            <label for="waze_url" class="block text-sm font-medium text-gray-700 mb-1">URL de Waze</label>
                            <input
                                type="url"
                                wire:model="formData.waze_url"
                                id="waze_url"
                                class="w-full rounded-lg border border-primary-500 text-gray-700 py-2 px-3"
                                placeholder="https://waze.com/..."
                            >
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                wire:model="formData.is_active"
                                class="rounded border-primary-500 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50"
                            >
                            <span class="ml-2 text-sm text-gray-700">Activo</span>
                        </label>
                    </div>

                    <div class="flex justify-center pt-4 border-t gap-4">
                        <button
                            wire:click="cerrarModal"
                            class="px-4 py-2 border border-primary-500 text-primary-500 rounded-lg hover:bg-gray-50 mr-2"
                        >
                            Cancelar
                        </button>
                        <button
                            wire:click="guardarLocal"
                            class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                        >
                            {{ $editMode ? 'Actualizar' : 'Guardar' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
