<x-filament-panels::page>
    <div class="rounded-lg">
        <!-- Encabezado con título y botón de agendar cita -->
        <div class="flex flex-col md:flex-row md:justify-between md:items-center border-b gap-3 mb-4">
            <p class="text-base text-gray-500">Conoce los mantenimientos del vehículo</p>
            <button type="button" wire:click="agendarCita" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 w-full md:w-auto">
                Agendar cita
            </button>
        </div>

        <!-- Sección de información general -->
        <div class="mb-4" x-data="{ expanded: true }">
            <div class="rounded-md mb-4 bg-primary-600 text-white p-3 flex justify-between items-center cursor-pointer" @click="expanded = !expanded">
                <h2 class="font-medium">Información general</h2>
                <svg class="w-5 h-5 transform transition-transform duration-200"
                     :class="{ 'rotate-180': expanded }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>

            <div x-show="expanded"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Columna 1: Información básica -->
                <div class="bg-white rounded-lg shadow-sm p-4 space-y-4">
                    <div class="flex">
                        <div class="w-32 font-bold text-blue-900">Modelo</div>
                        <div class="flex-1 text-gray-800">{{ $vehiculo['modelo'] }}</div>
                    </div>

                    <div class="flex">
                        <div class="w-32 font-bold text-blue-900">Kilometraje</div>
                        <div class="flex-1 text-gray-800">{{ $vehiculo['kilometraje'] }}</div>
                    </div>

                    <div class="flex">
                        <div class="w-32 font-bold text-blue-900">Placa</div>
                        <div class="flex-1 text-gray-800">{{ $vehiculo['placa'] }}</div>
                    </div>
                </div>

                <!-- Columna 2: Mantenimientos programados -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="flex justify-between items-start mb-4">
                        <h3 class="font-bold text-blue-900 uppercase">MANTENIMIENTOS PREPAGADOS</h3>
                        <button type="button" class="text-blue-500 hover:text-blue-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <div class="flex">
                            <div class="w-32 font-bold text-blue-900">Vencimiento</div>
                            <div class="flex-1 text-gray-800">{{ $mantenimiento['vencimiento'] }}</div>
                        </div>

                        <div class="flex">
                            <div class="w-32 font-bold text-blue-900 self-start">Disponibles</div>
                            <div class="flex-1 text-gray-800">
                                @foreach ($mantenimiento['disponibles'] as $disponible)
                                    <div>{{ $disponible }}</div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Columna 3: Último mantenimiento -->
                <div class="bg-white rounded-lg shadow-sm p-4">
                    <div class="space-y-4">
                        <div>
                            <div class="font-bold text-blue-900">Último mantenimiento</div>
                            <div class="text-gray-800 mt-1">{{ $mantenimiento['ultimo'] }}</div>
                        </div>

                        <div>
                            <div class="font-bold text-blue-900">Fecha</div>
                            <div class="text-gray-800 mt-1">{{ $mantenimiento['fecha'] }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sección de citas agendadas -->
        <div class="mb-4" x-data="{ expanded: true }">
            <div class="rounded-md mb-4 bg-primary-600 text-white p-3 flex justify-between items-center cursor-pointer" @click="expanded = !expanded">
                <h2 class="font-medium">Citas agendadas</h2>
                <svg class="w-5 h-5 transform transition-transform duration-200"
                     :class="{ 'rotate-180': expanded }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>

            <!-- Diseño responsive para citas agendadas -->
            <div x-show="expanded"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 class="space-y-4">
                @foreach ($citasAgendadas as $cita)
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <!-- Layout responsive: columna en móvil, fila en desktop -->
                        <div class="flex flex-col md:flex-row md:gap-6">

                            <!-- Sección Servicio -->
                            <div class="mb-4 md:mb-0 md:w-1/4">
                                <div class="bg-primary-50 p-3 rounded-lg">
                                    <div class="text-xs font-medium text-primary-800 mb-2">Servicio</div>
                                    <div class="text-sm font-semibold text-gray-900">{{ $cita['servicio'] }}</div>
                                </div>
                            </div>

                            <!-- Sección Estado -->
                            <div class="mb-4 md:mb-0 md:w-2/5">
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <div class="text-xs font-medium text-gray-800 mb-4">Estado del servicio</div>
                                    <div class="flex flex-row justify-between gap-2">
                                        <!-- Estado: Cita confirmada -->
                                        <div class="flex flex-col items-center">
                                            <div class="w-8 h-8 rounded-full flex bg-green-600 items-center justify-center">
                                                <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                            <span class="text-xs font-medium text-green-500 mt-1 text-center">Cita confirmada</span>
                                        </div>

                                        <!-- Estado: En trabajo -->
                                        <div class="flex flex-col items-center opacity-50">
                                            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                                            </div>
                                            <span class="text-xs text-gray-500 mt-1 text-center">En trabajo</span>
                                        </div>

                                        <!-- Estado: Trabajo concluido -->
                                        <div class="flex flex-col items-center opacity-50">
                                            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                                            </div>
                                            <span class="text-xs text-gray-500 mt-1 text-center">Trabajo concluido</span>
                                        </div>

                                        <!-- Estado: Entregado -->
                                        <div class="flex flex-col items-center opacity-50">
                                            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                                            </div>
                                            <span class="text-xs text-gray-500 mt-1 text-center">Entregado</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Sección Datos -->
                            <div class="mb-4 md:mb-0 md:w-1/3">
                                <div class="bg-blue-50 p-3 rounded-lg">
                                    <div class="text-xs font-medium text-blue-800 mb-4">Datos de la cita</div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between">
                                            <span class="text-xs font-medium text-blue-800">Probable entrega:</span>
                                            <span class="text-xs text-gray-600">-</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-xs font-medium text-blue-800">Asesor:</span>
                                            <span class="text-xs text-gray-600">-</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-xs font-medium text-blue-800">Hora:</span>
                                            <span class="text-xs text-gray-600">-</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-xs font-medium text-blue-800">WhatsApp:</span>
                                            <span class="text-xs text-gray-600">-</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-xs font-medium text-blue-800">Sede:</span>
                                            <span class="text-xs text-gray-600">-</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-xs font-medium text-blue-800">Correo:</span>
                                            <span class="text-xs text-gray-600">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de acción -->
                        <div class="mt-4 pt-3 border-t border-gray-200">
                            <div class="flex flex-row md:justify-end gap-2 md:gap-4">
                                <button type="button" class="text-primary-600 hover:text-primary-800 flex items-center justify-center md:justify-start text-sm py-2 px-3 rounded-md hover:bg-primary-50">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Ver comprobante
                                </button>
                                <button type="button" class="text-primary-600 hover:text-primary-800 flex items-center justify-center md:justify-start text-sm py-2 px-3 rounded-md hover:bg-primary-50">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Editar
                                </button>
                                <button type="button" class="text-red-600 hover:text-red-800 flex items-center justify-center md:justify-start text-sm py-2 px-3 rounded-md hover:bg-red-50">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Anular
                                </button>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Sección de historial de servicios -->
        <div x-data="{ expanded: true }">
            <div class="mb-4 rounded-md bg-primary-600 text-white p-3 flex justify-between items-center cursor-pointer" @click="expanded = !expanded">
                <h2 class="font-medium">Historial de servicios</h2>
                <svg class="w-5 h-5 transform transition-transform duration-200"
                     :class="{ 'rotate-180': expanded }"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>

            <div x-show="expanded"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                 x-transition:enter-end="opacity-100 transform translate-y-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 transform translate-y-0"
                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                 class="rounded-md overflow-x-auto">
                <!-- Tabla visible solo en desktop -->
                <div class="hidden md:block">
                    <table class="w-full divide-y divide-gray-200">
                        <thead class="bg-primary-600">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                    Servicio
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                    Fecha
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                    Sede
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                    Asesor
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                    Tipo de pago
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($this->historialPaginado as $servicio)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                        {{ $servicio['servicio'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                        {{ $servicio['fecha'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                        {{ $servicio['sede'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                        {{ $servicio['asesor'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-900">
                                        {{ $servicio['tipo_pago'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Diseño vertical para móvil con acordeón -->
                <div class="md:hidden space-y-4">
                    @foreach ($this->historialPaginado as $index => $servicio)
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden" x-data="{ expanded: false }">
                            <!-- Header del acordeón -->
                            <div class="p-4 cursor-pointer hover:bg-gray-50" @click="expanded = !expanded">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <div class="text-sm font-medium text-primary-800">Servicio</div>
                                        <div class="text-gray-800 font-semibold">{{ $servicio['servicio'] }}</div>
                                    </div>
                                    <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200"
                                         :class="{ 'rotate-180': expanded }"
                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>

                            <!-- Contenido expandible -->
                            <div x-show="expanded"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform -translate-y-2"
                                 x-transition:enter-end="opacity-100 transform translate-y-0"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 transform translate-y-0"
                                 x-transition:leave-end="opacity-0 transform -translate-y-2"
                                 style="display: none;">
                                <div class="px-4 pb-4 space-y-3 border-t border-gray-100">
                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <div class="text-sm font-medium text-primary-800 mb-1">Fecha</div>
                                        <div class="text-gray-800">{{ $servicio['fecha'] }}</div>
                                    </div>

                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <div class="text-sm font-medium text-primary-800 mb-1">Sede</div>
                                        <div class="text-gray-800">{{ $servicio['sede'] }}</div>
                                    </div>

                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <div class="text-sm font-medium text-primary-800 mb-1">Asesor</div>
                                        <div class="text-gray-800">{{ $servicio['asesor'] }}</div>
                                    </div>

                                    <div class="bg-gray-50 p-3 rounded-lg">
                                        <div class="text-sm font-medium text-primary-800 mb-1">Tipo de pago</div>
                                        <div class="text-gray-800">{{ $servicio['tipo_pago'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Paginación -->
                <!-- <div class="px-4 py-3 flex items-center justify-end border-t border-gray-200 sm:px-6">
                    <div class="flex-1 flex justify-between sm:hidden">
                        <a href="#" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Anterior
                        </a>
                        <a href="#" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Siguiente
                        </a>
                    </div>
                    <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-center">
                        <div>
                            <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Anterior</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                                <a href="#" aria-current="page" class="z-10 bg-primary-50 border-primary-500 text-primary-600 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    1
                                </a>
                                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    2
                                </a>
                                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    3
                                </a>
                                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    4
                                </a>
                                <a href="#" class="bg-white border-gray-300 text-gray-500 hover:bg-gray-50 relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    5
                                </a>
                                <a href="#" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Siguiente</span>
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4-4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                    </svg>
                                </a>
                            </nav>
                        </div>
                    </div>
                </div> -->

            </div>
        </div>
    </div>

    <!-- Botón para volver -->
    <div class="mt-6">
        <button type="button" wire:click="volver" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver a vehículos
            </div>
        </button>
    </div>
</x-filament-panels::page>
