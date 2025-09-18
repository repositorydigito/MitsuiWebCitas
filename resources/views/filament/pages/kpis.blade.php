<x-filament-panels::page>
    {{-- Filtros y búsqueda --}}
    <div class="mb-4 rounded-lg">
        <div class="flex flex-wrap items-end gap-4">
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
            {{-- Filtro de mes y año --}}
            <div class="w-auto">
                <label for="mes" class="block text-sm font-medium text-gray-700 mb-2">Mes y Año</label>
                <div class="flex gap-2">
                    <select
                        id="mes"
                        wire:model.live="mesSeleccionado"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        style="min-width: 120px;"
                    >
                        <option value="">Seleccionar mes</option>
                        <option value="1">Enero</option>
                        <option value="2">Febrero</option>
                        <option value="3">Marzo</option>
                        <option value="4">Abril</option>
                        <option value="5">Mayo</option>
                        <option value="6">Junio</option>
                        <option value="7">Julio</option>
                        <option value="8">Agosto</option>
                        <option value="9">Septiembre</option>
                        <option value="10">Octubre</option>
                        <option value="11">Noviembre</option>
                        <option value="12">Diciembre</option>
                    </select>
                    
                    <select
                        id="anio"
                        wire:model.live="anioSeleccionado"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        style="min-width: 100px;"
                    >
                        <option value="">Seleccionar año</option>
                        @for ($i = date('Y'); $i >= 2020; $i--)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            {{-- Botones de acción --}}
            <div class="ml-auto flex gap-2">
                <button
                    type="button"
                    wire:click="limpiarFiltros"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                >
                    <span class="mr-2">LIMPIAR</span>
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 110 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd" />
                    </svg>
                </button>
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
                            Cumplimiento
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
                                <div class="flex items-center justify-between">
                                    <span>{{ $kpi['meta'] ?? '-' }}</span>
                                    <button 
                                        wire:click="openModal('{{ $kpi['id'] }}')"
                                        class="text-primary-600 hover:text-primary-800"
                                        title="Configurar meta"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-center @if(!$kpi['desviacion']) bg-gray-200 @endif">
                                @if($kpi['desviacion'])
                                    <span class="font-medium @if(str_starts_with($kpi['desviacion'], '+')) text-green-600 @elseif(str_starts_with($kpi['desviacion'], '-')) text-red-600 @else text-gray-600 @endif">
                                        {{ $kpi['desviacion'] }}
                                    </span>
                                @else
                                    <span class="text-gray-400">-</span>
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

    <!-- Modal para configurar metas -->
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 py-6 text-center sm:block sm:p-0">

            <!-- Overlay de fondo oscuro (clic para cerrar) -->
            <div class="fixed inset-0 bg-black/50" aria-hidden="true" wire:click="cerrarModal"></div>

                <!-- Fondo oscuro -->
                <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <!-- Contenido del modal -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="pb-4 text-lg leading-6 font-medium text-gray-900">
                                    Configurar Meta
                                </h3>
                                <div class="mt-2">
                                    <form>
                                        <div class="grid grid-cols-1 gap-4">
                                            <!-- Marca -->
                                            <div>
                                                <label for="modalBrand" class="block text-left text-sm font-medium text-gray-700">Marca</label>
                                                <select
                                                    id="modalBrand"
                                                    wire:model.live="modalBrand"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                                >
                                                    <option value="">Todas</option>
                                                    @foreach($marcas as $marca)
                                                        @if($marca !== 'Todas')
                                                            <option value="{{ $marca }}">{{ $marca }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- Local -->
                                            <div>
                                                <label for="modalLocal" class="block text-left text-sm font-medium text-gray-700">Local</label>
                                                <select
                                                    id="modalLocal"
                                                    wire:model="modalLocal"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                                >
                                                    <option value="">Todos</option>
                                                    @foreach($localesModal as $codigo => $nombre)
                                                        @if($codigo !== 'Todos')
                                                            <option value="{{ $codigo }}">{{ $nombre }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>

                                            <!-- Mes -->
                                            <div>
                                                <label for="modalMonth" class="block text-left text-sm font-medium text-gray-700">Mes</label>
                                                <select
                                                    id="modalMonth"
                                                    wire:model="modalMonth"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                                >
                                                    <option value="">Todos</option>
                                                    <option value="1">Enero</option>
                                                    <option value="2">Febrero</option>
                                                    <option value="3">Marzo</option>
                                                    <option value="4">Abril</option>
                                                    <option value="5">Mayo</option>
                                                    <option value="6">Junio</option>
                                                    <option value="7">Julio</option>
                                                    <option value="8">Agosto</option>
                                                    <option value="9">Septiembre</option>
                                                    <option value="10">Octubre</option>
                                                    <option value="11">Noviembre</option>
                                                    <option value="12">Diciembre</option>
                                                </select>
                                            </div>

                                            <!-- Año -->
                                            <div>
                                                <label for="modalYear" class="block text-left text-sm font-medium text-gray-700">Año</label>
                                                <select
                                                    id="modalYear"
                                                    wire:model="modalYear"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                                >
                                                    <option value="">Todos</option>
                                                    @for ($i = date('Y'); $i >= 2020; $i--)
                                                        <option value="{{ $i }}">{{ $i }}</option>
                                                    @endfor
                                                </select>
                                            </div>

                                            <!-- Valor de la meta -->
                                            <div>
                                                <label for="targetValue" class="block text-left text-sm font-medium text-gray-700">Valor de la Meta</label>
                                                <input
                                                    type="number"
                                                    id="targetValue"
                                                    wire:model="targetValue"
                                                    min="0"
                                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                                >
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 gap-2 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button
                            type="button"
                            wire:click="saveTarget"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Guardar
                        </button>
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Eliminamos los scripts del datepicker ya que no los necesitamos --}}
</x-filament-panels::page>