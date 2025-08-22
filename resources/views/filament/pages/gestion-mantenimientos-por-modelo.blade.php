<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header con botón agregar y filtros -->
        <div class="flex flex-col md:flex-row justify-between md:items-center gap-4 mb-4">
            <div class="flex flex-col md:flex-row items-start md:items-center gap-4 md:space-x-4 w-full">
                <!-- Campo de búsqueda -->
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live="busqueda"
                        placeholder="Buscar por nombre, código o tipo valor trabajo"
                    />
                </x-filament::input.wrapper>

                <!-- Filtro por marca -->
                <div class="relative">
                    <select
                        wire:model.live="filtroMarca"
                        class="border border-gray-300 rounded-md pr-6 py-2 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent min-w-32"
                    >
                        <option value="">Todas las marcas</option>
                        <option value="Toyota">Toyota</option>
                        <option value="Lexus">Lexus</option>
                        <option value="Hino">Hino</option>
                    </select>
                </div>

                <!-- Filtro por tipo valor trabajo -->
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model.live="filtroModelo"
                        placeholder="Filtrar por tipo valor trabajo"
                    />
                </x-filament::input.wrapper>

                <!-- Botones de limpiar filtros -->
                @if($busqueda || $filtroMarca || $filtroModelo)
                    <x-filament::button
                        wire:click="limpiarFiltros"
                        color="gray"
                        size="sm"
                    >
                        Limpiar filtros
                    </x-filament::button>
                @endif
            </div>

            <x-filament::button
                wire:click="agregarMantenimiento"
                color="primary"
                class="flex items-center"
            >
                Agregar
            </x-filament::button>
        </div>

        <!-- Tabla -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-primary-500">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Nombre
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Código
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                            Marca
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Tipo Valor Trabajo
                        </th>
                        <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Kilómetros
                        </th>
                        <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Descripción
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        // Agrupar los mantenimientos por marca, código y kilómetros
                        $grupos = $this->mantenimientosPaginados->groupBy(['brand', 'code', 'kilometers']);
                    @endphp
                    
                    @forelse($grupos as $marca => $porCodigo)
                        @foreach($porCodigo as $codigo => $porKilometros)
                            @foreach($porKilometros as $kilometros => $mantenimientos)
                                @php
                                    $primerMantenimiento = $mantenimientos->first();
                                    $todosLosTipos = $mantenimientos->pluck('tipo_valor_trabajo')->filter()->implode(', ');
                                    $ids = $mantenimientos->pluck('id')->toArray();
                                    $isActive = $mantenimientos->contains('is_active', true);
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $primerMantenimiento['name'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $codigo }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($marca === 'Toyota') bg-red-100 text-red-800
                                            @elseif($marca === 'Lexus') bg-blue-100 text-blue-800
                                            @else bg-orange-100 text-orange-800
                                            @endif">
                                        {{ $marca }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    {{ $todosLosTipos }}
                                </td>
                                <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ number_format($kilometros, 0, ',', '.') }} km
                                </td>
                                <td class="hidden md:table-cell px-6 py-4 text-sm text-gray-500">
                                    {{ $primerMantenimiento['description'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <!-- Toggle Switch -->
                                        <button
                                            type="button"
                                            wire:click="toggleEstado({{ $primerMantenimiento['id'] }})"
                                            @class([
                                                'relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                                                'bg-primary-600' => $isActive,
                                                'bg-gray-200' => !$isActive,
                                            ])
                                            role="switch"
                                            aria-checked="{{ $isActive ? 'true' : 'false' }}"
                                        >
                                            <span
                                                aria-hidden="true"
                                                @class([
                                                    'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                                    'translate-x-5' => $isActive,
                                                    'translate-x-0' => !$isActive,
                                                ])
                                            ></span>
                                        </button>

                                        <!-- Botón Editar -->
                                        <button
                                            type="button"
                                            wire:click="editarMantenimiento({{ $primerMantenimiento['id'] }})"
                                            class="text-primary-600 hover:text-primary-900"
                                            title="Editar"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>

                                        <!-- Botón Eliminar -->
                                        <button
                                            type="button"
                                            wire:click="confirmarEliminacion({{ $primerMantenimiento['id'] }})"
                                            class="text-red-600 hover:text-red-900"
                                            title="Eliminar"
                                        >
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No se encontraron mantenimientos por modelo
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        <!-- Paginación -->
        @if($this->mantenimientosPaginados->hasPages())
            <div class="flex justify-center">
                <nav class="flex items-center gap-1 sm:gap-2">
                    <!-- Botón Anterior -->
                    @if($this->mantenimientosPaginados->onFirstPage())
                        <span class="px-2 py-2 text-gray-400 text-sm sm:px-3">
                            <span>‹</span>
                        </span>
                    @else
                        <x-filament::button
                            wire:click="gotoPage({{ $this->mantenimientosPaginados->currentPage() - 1 }})"
                            color="gray"
                            size="sm"
                            class="px-2 py-2 text-sm sm:px-3"
                        >
                            <span>‹</span>
                        </x-filament::button>
                    @endif

                    @php
                        $currentPage = $this->mantenimientosPaginados->currentPage();
                        $lastPage = $this->mantenimientosPaginados->lastPage();
                        $showPages = [];
                        
                        if ($lastPage <= 7) {
                            // Si hay 7 páginas o menos, mostrar todas
                            $showPages = range(1, $lastPage);
                        } else {
                            // Lógica para páginas con puntos suspensivos
                            if ($currentPage <= 4) {
                                // Cerca del inicio: 1 2 3 4 5 ... último
                                $showPages = array_merge(range(1, 5), ['...'], [$lastPage]);
                            } elseif ($currentPage >= $lastPage - 3) {
                                // Cerca del final: 1 ... antepenúltimo penúltimo último
                                $showPages = array_merge([1], ['...'], range($lastPage - 4, $lastPage));
                            } else {
                                // En el medio: 1 ... actual-1 actual actual+1 ... último
                                $showPages = array_merge([1], ['...'], range($currentPage - 1, $currentPage + 1), ['...'], [$lastPage]);
                            }
                        }
                    @endphp

                    @foreach($showPages as $page)
                        @if($page === '...')
                            <span class="px-2 py-2 text-gray-400 text-sm sm:px-3">...</span>
                        @elseif($page == $currentPage)
                            <span class="px-2 py-2 bg-primary-500 text-white rounded text-sm sm:px-3 min-w-[32px] text-center">{{ $page }}</span>
                        @else
                            <x-filament::button
                                wire:click="gotoPage({{ $page }})"
                                color="gray"
                                size="sm"
                                class="px-2 py-2 text-sm sm:px-3 min-w-[32px]"
                            >
                                {{ $page }}
                            </x-filament::button>
                        @endif
                    @endforeach

                    <!-- Botón Siguiente -->
                    @if($this->mantenimientosPaginados->hasMorePages())
                        <x-filament::button
                            wire:click="gotoPage({{ $this->mantenimientosPaginados->currentPage() + 1 }})"
                            color="gray"
                            size="sm"
                            class="px-2 py-2 text-sm sm:px-3"
                        >
                            <span>›</span>
                            
                        </x-filament::button>
                    @else
                        <span class="px-2 py-2 text-gray-400 text-sm sm:px-3">
                            <span>›</span>
                        </span>
                    @endif
                </nav>
            </div>
        @endif
    </div>

    <!-- Modal para agregar/editar mantenimiento -->
    @if($isFormModalOpen)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
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
                            {{ $accionFormulario === 'crear' ? 'Agregar Mantenimiento por Tipo Valor Trabajo' : 'Editar Mantenimiento por Tipo Valor Trabajo' }}
                        </h3>
                        <div class="w-full">

                            <div class="flex flex-col space-y-4">
                                <div class="flex md:flex-row flex-col justify-between gap-2">
                                    <!-- Campo Nombre -->
                                    <div class="flex flex-col w-full">
                                        <label for="name" class="text-sm font-medium text-gray-700 mb-1">Nombre</label>
                                        <input
                                            type="text"
                                            id="name"
                                            wire:model="mantenimientoEnEdicion.name"
                                            class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        >
                                        @error('mantenimientoEnEdicion.name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Campo Código -->
                                    <div class="flex flex-col w-full">
                                        <label for="code" class="text-sm font-medium text-gray-700 mb-1">Código</label>
                                        <input
                                            type="text"
                                            id="code"
                                            wire:model="mantenimientoEnEdicion.code"
                                            class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        >
                                        @error('mantenimientoEnEdicion.code') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>    
                            
                                <div class="flex md:flex-row flex-col justify-between gap-2">
                                    <!-- Campo Marca -->
                                    <div class="flex flex-col w-full">
                                        <label for="brand" class="text-sm font-medium text-gray-700 mb-1">Marca</label>
                                        <select
                                            id="brand"
                                            wire:model="mantenimientoEnEdicion.brand"
                                            class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        >
                                            <option value="Toyota">Toyota</option>
                                            <option value="Lexus">Lexus</option>
                                            <option value="Hino">Hino</option>
                                        </select>
                                        @error('mantenimientoEnEdicion.brand') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Campo Kilómetros -->
                                    <div class="flex flex-col w-full">
                                        <label for="kilometers" class="text-sm font-medium text-gray-700 mb-1">Kilómetros</label>
                                        <input
                                            type="number"
                                            id="kilometers"
                                            wire:model="mantenimientoEnEdicion.kilometers"
                                            class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        >
                                        @error('mantenimientoEnEdicion.kilometers') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                <div class="flex md:flex-row flex-col justify-between gap-2">
                                    <!-- Campo Tipo Valor Trabajo -->
                                    <div class="flex flex-col w-full">
                                        <div class="flex items-center justify-between">
                                            <label for="tipo_valor_trabajo" class="text-sm font-medium text-gray-700 mb-1">Tipos de Valor Trabajo</label>
                                            <span class="text-xs text-gray-500">Separados por comas</span>
                                        </div>
                                        <input
                                            type="text"
                                            id="tipo_valor_trabajo"
                                            wire:model="mantenimientoEnEdicion.tipo_valor_trabajo"
                                            class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                            placeholder="Ej: HILUX-2275, RAV4-1085, COROLLA-1234"
                                            title="Ingrese los tipos de valor de trabajo separados por comas"
                                        >
                                        @error('mantenimientoEnEdicion.tipo_valor_trabajo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        <p class="text-xs text-gray-500 mt-1">Ejemplo: HILUX-2275, RAV4-1085, COROLLA-1234</p>
                                    </div>
                                    <!-- Campo Activo -->
                                    <div class="flex items-center gap-2 w-full">
                                        <input
                                            type="checkbox"
                                            id="is_active"
                                            wire:model="mantenimientoEnEdicion.is_active"
                                            class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                        >
                                        <label for="is_active" class="ml-2 text-sm text-gray-700">Activo</label>
                                    </div>
                                </div>

                                

                                <!-- Campo Descripción -->
                                <div class="flex flex-col">
                                    <label for="description" class="text-sm font-medium text-gray-700 mb-1">Descripción</label>
                                    <textarea
                                        id="description"
                                        wire:model="mantenimientoEnEdicion.description"
                                        rows="3"
                                        class="border border-gray-300 rounded-md p-2 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                                        placeholder="Descripción del mantenimiento (opcional)"
                                    ></textarea>
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
                                        wire:click="guardarMantenimiento"
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