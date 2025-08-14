<x-filament-panels::page>
    <p class="text-gray-600 mb-4">Visualiza y gestiona las campañas.</p>

    {{-- Filtros y búsqueda --}}
    <div class="mb-4 flex flex-wrap items-end gap-4">
        {{-- Filtro de ciudad --}}
        <div class="w-auto">
            <label for="ciudad" class="block text-sm font-medium text-gray-700 mb-2">Elegir ciudad</label>
            <select
                id="ciudad"
                wire:model.live="ciudadSeleccionada"
                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                style="max-width: 150px;"
            >
                <option value="">Todos</option>
                @foreach ($ciudades as $ciudad)
                    <option value="{{ $ciudad }}">{{ $ciudad }}</option>
                @endforeach
            </select>
        </div>

        {{-- Filtro de estado --}}
        <div class="w-auto">
            <label for="estado" class="block text-sm font-medium text-gray-700 mb-2">Elegir estado</label>
            <select
                id="estado"
                wire:model.live="estadoSeleccionado"
                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"

            >
                <option value="">Todos</option>
                @foreach ($estados as $estado)
                    <option value="{{ $estado }}">{{ $estado }}</option>
                @endforeach
            </select>
        </div>

        {{-- Filtro de marca --}}
        <div class="w-auto">
            <label for="marca" class="block text-sm font-medium text-gray-700 mb-2">Elegir marca</label>
            <select
                id="marca"
                wire:model.live="filtroMarca"
                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                style="padding-right: 40px"
            >
                <option value="">Todas las marcas</option>
                <option value="Toyota">Toyota</option>
                <option value="Lexus">Lexus</option>
                <option value="Hino">Hino</option>
            </select>
        </div>

        {{-- Filtro de fechas --}}
        <div class="w-auto">
            <label for="fechas" class="block text-sm font-medium text-gray-700 mb-2">Rango de fechas</label>
            <div class="flex items-center gap-1">
                <div class="relative">
                    <input
                        type="text"
                        id="fechas"
                        wire:model="rangoFechas"
                        class="w-auto border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 datepicker"
                        style="max-width: 200px;"
                        autocomplete="off"
                        readonly
                    >
                </div>

                {{-- Botón para aplicar el filtro --}}
                <button
                    type="button"
                    wire:click="aplicarFiltroFechas"
                    class="p-2 text-white bg-primary-600 hover:bg-primary-700 rounded-lg focus:outline-none"
                    title="Aplicar filtro de fechas"
                >
                    <svg class="w-4 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </button>

                {{-- Botón para limpiar el filtro --}}
                @if(!empty($rangoFechas))
                    <button
                        type="button"
                        wire:click="limpiarFiltroFechas"
                        class="p-2 text-gray-500 hover:text-primary-600 focus:outline-none"
                        title="Limpiar filtro de fechas"
                    >
                        <svg class="w-4 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        {{-- Búsqueda --}}
        <div class="w-auto flex-grow">
            <label for="busqueda" class="block text-sm font-medium text-gray-700 mb-2">Buscar por código o nombre</label>
            <div class="relative">
                <input
                    type="text"
                    id="busqueda"
                    wire:model.live.debounce.300ms="busqueda"
                    class="w-auto pl-3 pr-10 py-2 border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                >
            </div>
        </div>

        {{-- Botones de acción --}}
        <div class="flex space-x-2 gap-4">
            {{-- Botón para cargar campañas --}}
            <a
                href="{{ \App\Filament\Pages\CargaCampanasPage::getUrl() }}"
                class="inline-flex items-center gap-2 px-2 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                Cargar campañas
            </a>

            {{-- Botón para crear campaña --}}
            <a
                href="{{ \App\Filament\Pages\CrearCampana::getUrl() }}"
                class="inline-flex items-center gap-2 px-2 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Crear una campaña
            </a>
        </div>
    </div>



    {{-- Tabla de campañas --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-primary-600">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Código campaña
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Campaña
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Marca
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Local
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Fecha de inicio
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Fecha de fin
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                            Estado
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($this->campanasPaginadas as $campana)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                {{ $campana['codigo'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $campana['nombre'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                    @if($campana['brand'] === 'Toyota') bg-red-100 text-red-800
                                    @elseif($campana['brand'] === 'Lexus') bg-blue-100 text-blue-800
                                    @else bg-orange-100 text-orange-800
                                    @endif">
                                    {{ $campana['brand'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @if(is_array($campana['locales']) && count($campana['locales']) > 0)
                                    {{ implode(', ', $campana['locales']) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $campana['fecha_inicio'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $campana['fecha_fin'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $campana['estado'] === 'Activo' ? 'bg-primary-500 text-white' : 'bg-red-100 text-red-800' }}">
                                    {{ $campana['estado'] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                <div class="flex justify-center space-x-2 gap-4">
                                    {{-- Botón Ver detalle --}}
                                    <button
                                        x-data="{}"
                                        x-on:click="$wire.verDetalleJS('{{ $campana['codigo'] }}')"
                                        class="text-primary-600 hover:text-primary-900"
                                        title="Ver detalle"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>

                                    {{-- Botón Editar --}}
                                    <button
                                        wire:click="editar('{{ $campana['codigo'] }}')"
                                        class="text-primary-600 hover:text-primary-900"
                                        title="Editar"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>

                                    {{-- Botón Eliminar --}}
                                    <button
                                        x-data="{}"
                                        x-on:click="if (confirm('¿Estás seguro de que deseas eliminar esta campaña? Esta acción no se puede deshacer.')) { $wire.eliminar('{{ $campana['codigo'] }}') }"
                                        class="text-red-600 hover:text-red-900"
                                        title="Eliminar"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
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
                                    <p class="text-gray-500 text-lg font-medium mb-2">No se encontraron campañas</p>
                                    <p class="text-gray-400 text-sm">Intenta con otros filtros o crea una nueva campaña</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Paginación --}}
    @if($this->campanasPaginadas->hasPages())
        <div class="mt-4 flex justify-center">
            <nav class="flex items-center gap-1 sm:gap-2">
                <!-- Botón Anterior -->
                @if($this->campanasPaginadas->onFirstPage())
                    <span class="px-2 py-2 text-gray-400 text-sm sm:px-3">
                        <span class="inline sm:hidden">‹</span>
                        <span class="hidden sm:inline">Anterior</span>
                    </span>
                @else
                    <button
                        wire:click="gotoPage({{ $this->campanasPaginadas->currentPage() - 1 }})"
                        class="px-2 py-2 text-sm sm:px-3 text-gray-600 hover:text-primary-600 border border-gray-300 rounded hover:bg-gray-50"
                    >
                        <span class="inline sm:hidden">‹</span>
                        <span class="hidden sm:inline">Anterior</span>
                    </button>
                @endif

                @php
                    $currentPage = $this->campanasPaginadas->currentPage();
                    $lastPage = $this->campanasPaginadas->lastPage();
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
                        <button
                            wire:click="gotoPage({{ $page }})"
                            class="px-2 py-2 text-sm sm:px-3 min-w-[32px] text-gray-600 hover:text-primary-600 border border-gray-300 rounded hover:bg-gray-50"
                        >
                            {{ $page }}
                        </button>
                    @endif
                @endforeach

                <!-- Botón Siguiente -->
                @if($this->campanasPaginadas->hasMorePages())
                    <button
                        wire:click="gotoPage({{ $this->campanasPaginadas->currentPage() + 1 }})"
                        class="px-2 py-2 text-sm sm:px-3 text-gray-600 hover:text-primary-600 border border-gray-300 rounded hover:bg-gray-50"
                    >
                        <span class="inline sm:hidden">›</span>
                        <span class="hidden sm:inline">Siguiente</span>
                    </button>
                @else
                    <span class="px-2 py-2 text-gray-400 text-sm sm:px-3">
                        <span class="inline sm:hidden">›</span>
                        <span class="hidden sm:inline">Siguiente</span>
                    </span>
                @endif
            </nav>
        </div>
    @endif

    {{-- Modal para ver detalle de campaña --}}
    @if($modalDetalleVisible)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{}" x-cloak>
            <!-- Overlay de fondo oscuro (clic para cerrar) -->
            <div class="fixed inset-0 bg-black/50" wire:click="cerrarModalDetalle" aria-hidden="true"></div>

            <!-- Modal centrado -->
            <div class="flex items-center justify-center min-h-screen p-4">
                <!-- Panel del modal -->
                <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl mx-4 relative z-10">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-blue-900">Detalle de Campaña</h2>
                            <button wire:click="cerrarModalDetalle" class="text-gray-500 hover:text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
                                <div>
                                    <h3 class="text-base font-semibold leading-7 text-gray-900">Información General</h3>
                                    <div class="mt-2 border-t border-gray-100">
                                        <dl class="divide-y divide-gray-100">
                                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                                <dt class="text-sm font-medium leading-6 text-gray-900">Código</dt>
                                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $campanaDetalle['codigo'] ?? '' }}</dd>
                                            </div>
                                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                                <dt class="text-sm font-medium leading-6 text-gray-900">Título</dt>
                                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $campanaDetalle['nombre'] ?? '' }}</dd>
                                            </div>
                                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                                <dt class="text-sm font-medium leading-6 text-gray-900">Estado</dt>
                                                <dd class="mt-1 text-sm leading-6 sm:col-span-2 sm:mt-0">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ ($campanaDetalle['estado'] ?? '') === 'Activo' ? 'bg-primary-500 text-white' : 'bg-red-100 text-red-800' }}">
                                                        {{ $campanaDetalle['estado'] ?? '' }}
                                                    </span>
                                                </dd>
                                            </div>
                                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                                <dt class="text-sm font-medium leading-6 text-gray-900">Fecha Inicio</dt>
                                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $campanaDetalle['fecha_inicio'] ?? '' }}</dd>
                                            </div>
                                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                                <dt class="text-sm font-medium leading-6 text-gray-900">Fecha Fin</dt>
                                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">{{ $campanaDetalle['fecha_fin'] ?? '' }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>

                                <div>
                                    @if(!empty($campanaDetalle['imagen']))
                                        <div class="mb-4">
                                            <h3 class="text-base font-semibold leading-7 text-gray-900">Imagen</h3>
                                            <div class="mt-2 flex justify-center">
                                                <img src="{{ $campanaDetalle['imagen'] }}" alt="Imagen de campaña" class="max-w-full h-auto rounded-lg" style="max-height: 150px;">
                                            </div>
                                        </div>
                                    @endif

                                    <h3 class="text-base font-semibold leading-7 text-gray-900">Segmentación</h3>
                                    <div class="mt-2 border-t border-gray-100">
                                        <dl class="divide-y divide-gray-100">
                                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                                <dt class="text-sm font-medium leading-6 text-gray-900">Locales</dt>
                                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                                                    {{ !empty($campanaDetalle['locales']) ? implode(', ', $campanaDetalle['locales']) : 'No especificado' }}
                                                </dd>
                                            </div>
                                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                                <dt class="text-sm font-medium leading-6 text-gray-900">Modelos</dt>
                                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                                                    {{ !empty($campanaDetalle['modelos']) ? implode(', ', $campanaDetalle['modelos']) : 'No especificado' }}
                                                </dd>
                                            </div>
                                            <div class="px-4 py-3 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-0">
                                                <dt class="text-sm font-medium leading-6 text-gray-900">Años</dt>
                                                <dd class="mt-1 text-sm leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                                                    {{ !empty($campanaDetalle['anos']) ? implode(', ', $campanaDetalle['anos']) : 'No especificado' }}
                                                </dd>
                                            </div>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end mt-6 border-t pt-4">
                            <button
                                wire:click="cerrarModalDetalle"
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

    {{-- Scripts para el datepicker --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

    <script>
        // Inicializar el datepicker cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function () {
            initDatepicker();
        });

        // Reinicializar el datepicker cuando Livewire actualice el DOM
        document.addEventListener('livewire:load', function () {
            Livewire.hook('message.processed', (message, component) => {
                initDatepicker();
            });

            // Escuchar el evento mostrarNotificacion
            Livewire.on('mostrarNotificacion', (data) => {
                console.log('Mostrando notificación:', data);

                // Usar la API de notificaciones de Filament
                if (window.FilamentNotifications) {
                    const notification = {
                        title: data.titulo,
                        body: data.mensaje,
                    };

                    switch (data.tipo) {
                        case 'success':
                            window.FilamentNotifications.success(notification);
                            break;
                        case 'error':
                            window.FilamentNotifications.error(notification);
                            break;
                        case 'warning':
                            window.FilamentNotifications.warning(notification);
                            break;
                        default:
                            window.FilamentNotifications.info(notification);
                    }
                }
            });

            // Escuchar el evento refresh para actualizar la página
            Livewire.on('refresh', () => {
                console.log('Refrescando componente...');
                Livewire.emit('$refresh');
            });
        });

        // Inicializar el datepicker cuando Alpine.js inicialice el componente
        document.addEventListener('alpine:initialized', function () {
            initDatepicker();
        });

        let flatpickrInstance = null;

        function initDatepicker() {
            const datepickerEl = document.querySelector('.datepicker');

            // Destruir la instancia anterior si existe
            if (flatpickrInstance !== null) {
                flatpickrInstance.destroy();
            }

            if (datepickerEl) {
                flatpickrInstance = flatpickr(datepickerEl, {
                    mode: "range",
                    dateFormat: "d/m/Y",
                    locale: "es",
                    rangeSeparator: " - ",
                    altInput: false,
                    allowInput: false,
                    disableMobile: true,
                    showMonths: 2,
                    maxDate: new Date().fp_incr(365),
                    minDate: new Date().fp_incr(-365),
                    onChange: function(selectedDates, dateStr, instance) {
                        console.log('Fechas seleccionadas:', dateStr);
                    },
                    onClose: function(selectedDates, dateStr, instance) {
                        console.log('Datepicker cerrado con valor:', dateStr);

                        // Obtener el ID del componente Livewire
                        const componentId = datepickerEl.closest('[wire\\:id]').getAttribute('wire:id');

                        // Actualizar el valor en el componente Livewire
                        window.livewire.find(componentId).set('rangoFechas', dateStr);

                        // Aplicar el filtro de fechas
                        window.livewire.find(componentId).call('aplicarFiltroFechasDirecto', dateStr);
                    }
                });

                // Si ya hay un valor en el modelo, establecerlo en el datepicker
                const initialValue = datepickerEl.value;
                if (initialValue) {
                    const dates = initialValue.split(' - ').map(date => date.trim());
                    if (dates.length > 0) {
                        const parsedDates = dates.map(date => {
                            const parts = date.split('/');
                            return new Date(parts[2], parts[1] - 1, parts[0]);
                        });
                        flatpickrInstance.setDate(parsedDates);
                    }
                }
            }
        }
    </script>
</x-filament-panels::page>
