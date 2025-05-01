<x-filament-panels::page>
    <p class="text-gray-600 mb-6">Visualiza y gestiona las campañas.</p>

    {{-- Filtros y búsqueda --}}
    <div class="mb-6 flex flex-wrap items-end gap-4">
        {{-- Filtro de ciudad --}}
        <div class="w-auto">
            <label for="ciudad" class="block text-sm font-medium text-gray-700 mb-1">Elegir ciudad</label>
            <select
                id="ciudad"
                wire:model.live="ciudadSeleccionada"
                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                style="min-width: 160px;"
            >
                <option value="">Todos</option>
                @foreach ($ciudades as $ciudad)
                    <option value="{{ $ciudad }}">{{ $ciudad }}</option>
                @endforeach
            </select>
        </div>

        {{-- Filtro de estado --}}
        <div class="w-auto">
            <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Elegir estado</label>
            <select
                id="estado"
                wire:model.live="estadoSeleccionado"
                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                style="min-width: 160px;"
            >
                <option value="">Todos</option>
                @foreach ($estados as $estado)
                    <option value="{{ $estado }}">{{ $estado }}</option>
                @endforeach
            </select>
        </div>

        {{-- Filtro de fechas --}}
        <div class="w-auto">
            <label for="fechas" class="block text-sm font-medium text-gray-700 mb-1">Rango de fechas</label>
            <div class="relative">
                <input
                    type="text"
                    id="fechas"
                    wire:model="rangoFechas"
                    placeholder=""
                    class="w-auto border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 datepicker"
                    style="max-width: 220px;"
                    autocomplete="off"
                >
            </div>
        </div>

        {{-- Búsqueda --}}
        <div class="w-auto flex-grow">
            <label for="busqueda" class="block text-sm font-medium text-gray-700 mb-1">Buscar por código o nombre</label>
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
            <button
                type="button"
                wire:click="cargarCampanas"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                Cargar campañas
            </button>

            {{-- Botón para crear campaña --}}
            <a
                href="{{ \App\Filament\Pages\CrearCampana::getUrl() }}"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
            >
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
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
                                {{ $campana['local'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $campana['fecha_inicio'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $campana['fecha_fin'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $campana['estado'] === 'Activo' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $campana['estado'] }}
                                </span>
                            </td>
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                <div class="flex justify-center space-x-2 gap-4">
                                    {{-- Botón Ver detalle --}}
                                    <button
                                        wire:click="verDetalle('{{ $campana['codigo'] }}')"
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
                                        wire:click="eliminar('{{ $campana['codigo'] }}')"
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
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
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
    <div class="mt-4 flex justify-end">
        {{ $this->campanasPaginadas->links('vendor.pagination.default') }}
    </div>

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
                    altInput: false, // Desactivamos altInput para evitar problemas con Livewire
                    allowInput: true,
                    disableMobile: true,
                    onChange: function(selectedDates, dateStr, instance) {
                        // Actualizar el valor en tiempo real
                        if (selectedDates.length > 0) {
                            window.livewire.find(datepickerEl.closest('[wire\\:id]').getAttribute('wire:id'))
                                .set('rangoFechas', dateStr);
                        }
                    },
                    onClose: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length > 0) {
                            // Enviar el valor al componente Livewire y aplicar el filtro
                            window.livewire.find(datepickerEl.closest('[wire\\:id]').getAttribute('wire:id'))
                                .call('aplicarFiltroFechas');
                        }
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
