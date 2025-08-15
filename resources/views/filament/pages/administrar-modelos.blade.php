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

        <!-- Filtros -->
        <div class="bg-gray-50 rounded-lg p-4 mb-4">
            <div class="flex flex-col sm:flex-row gap-4">
                <!-- Buscador por nombre -->
                <div class="flex-1">
                    <label for="search-name" class="block text-sm font-medium text-gray-700 mb-2">
                        Buscar por nombre de modelo
                    </label>
                    <div class="relative">
                        <input
                            type="text"
                            id="search-name"
                            wire:model.live="filtroNombre"
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                        >
                    </div>
                </div>

                <!-- Filtro por marca -->
                <div class="sm:w-64">
                    <label for="filter-brand" class="block text-sm font-medium text-gray-700 mb-2">
                        Filtrar por marca
                    </label>
                    <select
                        id="filter-brand"
                        wire:model.live="filtroMarca"
                        class="w-full px-8 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                    >
                        <option value="">Todas las marcas</option>
                        <option value="TOYOTA">TOYOTA</option>
                        <option value="LEXUS">LEXUS</option>
                        <option value="HINO">HINO</option>
                    </select>
                </div>

                <!-- Botón para limpiar filtros -->
                <div class="sm:w-auto flex items-end">
                    <button
                        type="button"
                        wire:click="limpiarFiltros"
                        class="px-4 py-2 text-sm font-medium text-gray-600 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500"
                    >
                        Limpiar filtros
                    </button>
                </div>
            </div>
        </div>

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
                        <th class="py-3 px-4 text-left text-sm font-medium text-white">Imagen</th>
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
                                @if($modelo->image)
                                    <img src="{{ asset('storage/' . $modelo->image) }}" alt="Imagen" class="h-auto w-16 object-contain rounded border" />
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
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
                            <td colspan="7" class="py-4 px-4 text-center text-gray-500">No hay modelos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <!-
- Modal para crear/editar modelo -->
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
                                    class="w-full rounded-lg border {{ $erroresCampos['code'] ? 'border-red-500' : 'border-primary-500' }} text-gray-700 py-2 px-3"
                                    placeholder="Ej: COROLLA_CROSS"
                                    {{ $editMode ? 'disabled' : '' }}
                                >
                                @if($erroresCampos['code'])
                                    <p class="text-primary-500 text-sm mt-1">Elige una opción disponible.</p>
                                @endif
                            </div>
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre *</label>
                                <input
                                    type="text"
                                    wire:model="formData.name"
                                    id="name"
                                    class="w-full rounded-lg border {{ $erroresCampos['name'] ? 'border-red-500' : 'border-primary-500' }} text-gray-700 py-2 px-3"
                                    placeholder="Ej: COROLLA CROSS"
                                >
                                @if($erroresCampos['name'])
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

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="image" class="block text-sm font-medium text-gray-700 mb-1">Imagen (máx. 10MB)</label>
                                <div class="flex gap-2">
                                    <input
                                        type="file"
                                        wire:model="formData.image"
                                        id="image"
                                        accept="image/*"
                                        class="flex-1 rounded-lg border border-primary-500 text-gray-700 py-2 px-3"
                                        onchange="validateImageSize(this)"
                                    />
                                    @if($currentImage || $formData['image'])
                                        <button
                                            type="button"
                                            wire:click="limpiarImagen"
                                            class="px-3 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 text-sm"
                                            title="Limpiar imagen"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1-1H9a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                                
                                @error('formData.image')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror

                                <!-- Alerta de tamaño de imagen -->
                                <div id="image-size-alert" class="hidden mt-2 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                                    <div class="flex items-center">
                                        <span id="image-size-message">La imagen seleccionada excede el límite de 10MB. Por favor, selecciona una imagen más pequeña.</span>
                                    </div>
                                </div>

                                {{-- Vista previa de imagen --}}
                                <div id="image-preview-container" class="mt-2">
                                    @if($formData['image'] && !is_string($formData['image']))
                                        <div class="relative">
                                            <img id="current-image" src="{{ $formData['image']->temporaryUrl() }}" alt="Vista previa" class="h-16 object-contain rounded border" />
                                            <span class="absolute -top-2 -right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">Nueva</span>
                                        </div>
                                    @elseif(!empty($formData['image']) && is_string($formData['image']))
                                        <div class="relative">
                                            <img id="current-image" src="{{ asset('storage/' . $formData['image']) }}" alt="Imagen actual" class="h-16 object-contain rounded border" />
                                            <span class="absolute -top-2 -right-2 bg-blue-500 text-white text-xs px-2 py-1 rounded-full">Actual</span>
                                        </div>
                                    @elseif(!empty($currentImage))
                                        <div class="relative">
                                            <img id="current-image" src="{{ asset('storage/' . $currentImage) }}" alt="Imagen actual" class="h-16 object-contain rounded border" />
                                            <span class="absolute -top-2 -right-2 bg-blue-500 text-white text-xs px-2 py-1 rounded-full">Actual</span>
                                        </div>
                                    @endif
                                    <!-- Vista previa temporal para nuevas imágenes -->
                                    <div class="relative">
                                        <img id="temp-image-preview" class="h-16 object-contain rounded border hidden" alt="Vista previa temporal" />
                                        <span id="temp-image-label" class="absolute -top-2 -right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full hidden">Nueva</span>
                                    </div>
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
                                        class="w-full rounded-lg border {{ $erroresCampos['nuevoAno'] ? 'border-red-500' : 'border-primary-500' }} text-gray-700 py-2 px-3"
                                        placeholder="Ej: 2025"
                                    >
                                    @if($erroresCampos['nuevoAno'])
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

    <script>
        function validateImageSize(input) {
            const maxSize = 10 * 1024 * 1024; // 10MB en bytes
            const alertDiv = document.getElementById('image-size-alert');
            const messageSpan = document.getElementById('image-size-message');
            const currentImage = document.getElementById('current-image');
            const tempPreview = document.getElementById('temp-image-preview');
            const tempLabel = document.getElementById('temp-image-label');
            
            if (input.files && input.files[0]) {
                const file = input.files[0];
                const fileSize = file.size;
                const fileSizeMB = (fileSize / (1024 * 1024)).toFixed(2);
                
                console.log('Archivo seleccionado:', {
                    name: file.name,
                    size: fileSize,
                    sizeMB: fileSizeMB,
                    type: file.type
                });
                
                if (fileSize > maxSize) {
                    // Mostrar alerta
                    messageSpan.textContent = `La imagen seleccionada (${fileSizeMB}MB) excede el límite de 10MB. Por favor, selecciona una imagen más pequeña.`;
                    alertDiv.classList.remove('hidden');
                    
                    // Limpiar el input
                    input.value = '';
                    
                    // Restaurar imagen anterior si existe
                    if (tempPreview) {
                        tempPreview.classList.add('hidden');
                        tempLabel.classList.add('hidden');
                    }
                    if (currentImage) {
                        currentImage.classList.remove('hidden');
                    }
                    
                    // Ocultar la alerta después de 5 segundos
                    setTimeout(() => {
                        alertDiv.classList.add('hidden');
                    }, 5000);
                } else {
                    // Ocultar alerta si el archivo es válido
                    alertDiv.classList.add('hidden');
                    
                    // Mostrar vista previa inmediata de la nueva imagen
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (tempPreview) {
                            tempPreview.src = e.target.result;
                            tempPreview.classList.remove('hidden');
                            tempLabel.classList.remove('hidden');
                        }
                        
                        // Ocultar imagen actual si existe
                        if (currentImage) {
                            currentImage.classList.add('hidden');
                        }
                    };
                    reader.readAsDataURL(file);
                }
            } else {
                // Si no hay archivo seleccionado, restaurar imagen original
                if (tempPreview) {
                    tempPreview.classList.add('hidden');
                    tempLabel.classList.add('hidden');
                }
                if (currentImage) {
                    currentImage.classList.remove('hidden');
                }
                alertDiv.classList.add('hidden');
            }
        }

        // Función para resetear la vista previa cuando se abre el modal o se limpia la imagen
        document.addEventListener('livewire:init', () => {
            Livewire.on('modal-opened', () => {
                resetImagePreview();
            });
            
            Livewire.on('image-cleared', () => {
                resetImagePreview();
                // También limpiar el input file
                const imageInput = document.getElementById('image');
                if (imageInput) {
                    imageInput.value = '';
                }
            });
        });

        function resetImagePreview() {
            const tempPreview = document.getElementById('temp-image-preview');
            const tempLabel = document.getElementById('temp-image-label');
            const currentImage = document.getElementById('current-image');
            const alertDiv = document.getElementById('image-size-alert');
            
            if (tempPreview) {
                tempPreview.classList.add('hidden');
                tempPreview.src = '';
            }
            if (tempLabel) {
                tempLabel.classList.add('hidden');
            }
            if (currentImage) {
                currentImage.classList.remove('hidden');
            }
            if (alertDiv) {
                alertDiv.classList.add('hidden');
            }
        }
    </script>
</x-filament-panels::page>