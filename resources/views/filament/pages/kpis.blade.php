<x-filament-panels::page>
    {{-- Filtros y búsqueda --}}
    <div class="mb-4 rounded-lg">
        <div class="flex flex-wrap items-end gap-4">
            {{-- Filtro de fecha --}}
            <div class="w-auto">
                <label for="fecha" class="block text-sm font-medium text-gray-700 mb-2">Fecha</label>
                <div class="relative">
                    <input
                        type="text"
                        id="fecha"
                        wire:model="rangoFechas"
                        value="{{ $rangoFechas }}"
                        placeholder="Seleccionar rango"
                        class="w-auto border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 datepicker"
                        style="min-width: 220px;"
                        autocomplete="off"
                    >
                </div>
            </div>

            {{-- Filtro de marca --}}
            <div class="w-auto">
                <label for="marca" class="block text-sm font-medium text-gray-700 mb-2">Marca</label>
                <div class="relative">
                    <select
                        id="marca"
                        wire:model.live="marcaSeleccionada"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        style="min-width: 160px;"
                    >
                        @foreach ($marcas as $marca)
                            <option value="{{ $marca }}">{{ $marca }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Filtro de local --}}
            <div class="w-auto">
                <label for="local" class="block text-sm font-medium text-gray-700 mb-2">Local</label>
                <div class="relative">
                    <select
                        id="local"
                        wire:model.live="localSeleccionado"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        style="min-width: 160px;"
                    >
                        @foreach ($locales as $codigo => $nombre)
                            <option value="{{ $codigo }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Botón para descargar --}}
            <div class="ml-auto">
                <button
                    type="button"
                    wire:click="exportarExcel"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <span class="mr-2">DESCARGAR</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Tabla de KPIs --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200">
                <thead class="bg-primary-600">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider w-10">
                            #
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                            KPI
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider w-24">
                            Cantidad
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider w-24">
                            Meta
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider w-24">
                            Contribución
                        </th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider w-24">
                            Desviación
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($kpis as $kpi)
                        <tr class="{{ $loop->even ? 'bg-blue-50' : 'bg-white' }}">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center text-gray-900">
                                {{ $kpi['id'] }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ $kpi['nombre'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                {{ $kpi['cantidad'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center @if(!$kpi['meta']) bg-gray-200 @endif">
                                {{ $kpi['meta'] }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center @if(!$kpi['contribucion']) bg-gray-200 @endif">
                                @if($kpi['contribucion'])
                                    <span class="text-primary-500 font-medium">SÍ</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center @if(!$kpi['desviacion']) bg-gray-200 @endif">
                                @if($kpi['desviacion'])
                                    <span class="text-primary-500 font-medium">{{ $kpi['desviacion'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                <div class="flex flex-col items-center justify-center py-6">
                                    <svg class="w-8 h-8 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <p class="text-gray-500 text-lg font-medium mb-2">No se encontraron KPIs</p>
                                    <p class="text-gray-400 text-sm">Intenta con otros filtros</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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
                    allowInput: true,
                    disableMobile: true,
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length > 0) {
                            window.livewire.find(datepickerEl.closest('[wire\\:id]').getAttribute('wire:id'))
                                .set('rangoFechas', dateStr);
                        }
                    },
                    onClose: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length > 0) {
                            window.livewire.find(datepickerEl.closest('[wire\\:id]').getAttribute('wire:id'))
                                .call('aplicarFiltros');
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
