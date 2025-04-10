<x-filament-panels::page>
    <div x-data="{ activeTab: @entangle('activeTab') }">
        {{-- Pestañas --}}
        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" role="tablist">
                @forelse ($marcasInfo as $codigo => $nombre)
                    <li class="mr-2" role="presentation">
                        <button
                            class="inline-flex items-center p-4 border-b-2 rounded-t-lg transition-all duration-200"
                            :class="{
                                'border-primary-600 text-primary-600 dark:border-primary-500 dark:text-primary-500 font-semibold': activeTab === '{{ $codigo }}',
                                'border-transparent hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300': activeTab !== '{{ $codigo }}'
                            }"
                            type="button"
                            role="tab"
                            wire:click="selectTab('{{ $codigo }}')"
                            :aria-selected="activeTab === '{{ $codigo }}'"
                        >
                            {{ $nombre }}
                            <span class="ml-2 px-2 py-0.5 rounded-full text-xs" 
                                :class="{'bg-primary-100 text-primary-700 dark:bg-primary-900 dark:text-primary-300': activeTab === '{{ $codigo }}', 
                                         'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-400': activeTab !== '{{ $codigo }}'}">
                                {{ $marcaCounts[$codigo] ?? 0 }}
                            </span>
                        </button>
                    </li>
                @empty
                    <li class="p-4 text-gray-500 dark:text-gray-400">No hay marcas definidas.</li>
                @endforelse
            </ul>
        </div>

        {{-- Contenido de las Pestañas --}}
        <div>
            @foreach ($marcasInfo as $codigo => $nombre)
                <div
                    x-show="activeTab === '{{ $codigo }}'"
                    role="tabpanel"
                    aria-labelledby="tab-{{ $codigo }}"
                    wire:key="tab-content-{{ $codigo }}" 
                >
      
                    @if($activeTab === $codigo)
                        {{-- Si es la pestaña activa, obtener el paginador AHORA --}}
                        @php
                            // Logs de vista eliminados
                            $currentPaginator = $this->vehiculosPaginados;
                        @endphp
                        
                        {{-- Usar $currentPaginator para renderizar --}}
                        @if($currentPaginator && $currentPaginator->total() > 0)
                            <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm mb-4 w-full">
                                <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-primary-600 dark:bg-primary-700">
                                        <tr>
                                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider font-semibold">
                                                Foto
                                            </th>
                                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider font-semibold">
                                                Modelo
                                            </th>
                                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider font-semibold">
                                                Placa
                                            </th>
                                            <th scope="col" class="px-6 py-4 text-left text-xs font-medium text-white uppercase tracking-wider font-semibold">
                                                Año
                                            </th>
                                            <th scope="col" class="px-6 py-4 text-right text-xs font-medium text-white uppercase tracking-wider font-semibold">
                                                Acciones
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                        @foreach ($currentPaginator->items() as $vehiculo)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 border-b border-gray-200 dark:border-gray-700 transition-colors">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="h-14 w-16 rounded overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center border border-gray-200 dark:border-gray-600">
                                                            @if(isset($vehiculo['foto_url']))
                                                                <img src="{{ $vehiculo['foto_url'] }}" alt="Vehículo {{ $vehiculo['modver'] }}" class="h-full w-full object-cover">
                                                            @else
                                                                <div class="text-center p-1">
                                                                    <img src="{{ asset('images/no-image.svg') }}" alt="Sin imagen" class="h-10 w-10 mx-auto">
                                                                    <span class="text-xs text-gray-500 dark:text-gray-400 block">Sin foto</span>
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <span class="text-sm font-bold text-gray-900 dark:text-white">
                                                            {{ $vehiculo['modver'] }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <span class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                                            {{ $vehiculo['numpla'] }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <span class="text-sm text-gray-600 dark:text-gray-300">
                                                            {{ $vehiculo['aniomod'] }}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex flex-wrap gap-2 justify-end">
                                                        <button type="button" class="inline-flex items-center px-2 py-1 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded hover:bg-gray-50 focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                                                            <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                                            </svg>
                                                            Ver detalle
                                                        </button>
                                                        <button type="button" class="inline-flex items-center px-2 py-1 text-sm font-medium text-danger-700 bg-white border border-danger-300 rounded hover:bg-danger-50 focus:ring-2 focus:ring-danger-500 focus:ring-offset-1">
                                                            <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                                                            </svg>
                                                            Retirar
                                                        </button>
                                                        <button type="button" class="inline-flex items-center px-2 py-1 text-sm font-medium text-white bg-primary-600 border border-transparent rounded hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                                                            <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                                                            </svg>
                                                            Agendar cita
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            {{-- Renderizar los enlaces de paginación --}}
                             <div class="flex justify-center mt-6">
                                <div class="pagination-wrapper py-2 px-4 bg-white dark:bg-gray-800 shadow rounded-lg">
                                    {{ $currentPaginator->links() }}
                                </div>
                            </div>

                        @else
                             {{-- Mensaje si no hay vehículos para esta marca (o $currentPaginator es null) --}}
                             <div class="p-4 text-center text-gray-500 dark:text-gray-400">
                                 No se encontraron vehículos para {{ $nombre }}.
                             </div>
                        @endif
                    @endif {{-- Fin de @if($activeTab === $codigo) --}}
                </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
