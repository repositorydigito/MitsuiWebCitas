<x-filament-panels::page>
    <div class="bg-gray rounded-lg shadow-sm">
        <!-- Encabezado con título y botón de agendar cita -->
        <div class="flex justify-between items-center p-2 border-b">
            <p class="text-base text-gray-600">Conoce los mantenimientos del vehículo</p>
            <button type="button" wire:click="agendarCita" class="px-4 py-2 bg-gray-400 text-white rounded-md hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2">
                Agendar cita
            </button>
        </div>

        <!-- Sección de información general -->
        <div class="mb-4">
            <div class="rounded-md mb-4 bg-primary-600 text-white p-3 flex justify-between items-center cursor-pointer">
                <h2 class="font-medium">Información general</h2>
                <svg class="w-5 h-5 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>

            <div class="p-4 grid grid-cols-1 md:grid-cols-3 gap-6">
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
        <div class="mb-4">
            <div class="rounded-md mb-4 bg-primary-600 text-white p-3 flex justify-between items-center cursor-pointer">
                <h2 class="font-medium">Citas agendadas</h2>
                <svg class="w-5 h-5 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>

            <div class="rounded-md overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 table-auto">
                    <thead class="bg-primary-500">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                Servicio
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                Estado
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                Datos
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($citasAgendadas as $cita)
                            <tr>
                                <td class="px-3">
                                    <div class="text-sm text-center text-gray-900">{{ $cita['servicio'] }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex justify-between">
                                        <!-- Estado: Cita confirmada -->
                                        <div class="flex flex-col items-center">
                                            <div class="w-8 h-8 rounded-full bg-primary-500 flex items-center justify-center">
                                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                            <span class="text-xs font-medium text-gray-900 mt-1">Cita</span>
                                            <span class="text-xs font-medium text-gray-900 mt-1">confirmada</span>
                                        </div>

                                        <!-- Estado: En trabajo -->
                                        <div class="flex flex-col items-center opacity-50">
                                            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                                            </div>
                                            <span class="text-xs text-gray-500 mt-1">En trabajo</span>
                                        </div>

                                        <!-- Estado: Trabajo concluido -->
                                        <div class="flex flex-col items-center opacity-50">
                                            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                                            </div>
                                            <span class="text-xs text-gray-500 mt-1">Trabajo</span>
                                            <span class="text-xs text-gray-500 mt-1">concluido</span>
                                        </div>

                                        <!-- Estado: Entregado -->
                                        <div class="flex flex-col items-center opacity-50">
                                            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center">
                                                <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                                            </div>
                                            <span class="text-xs text-gray-500 mt-1">Entregado</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="grid grid-cols-2 gap-x-8 gap-y-2">
                                        <div class="flex justify-between">
                                            <div class="text-sm font-medium text-blue-800">Probable entrega</div>
                                            <div class="text-sm text-gray-500">-</div>
                                        </div>
                                        <div class="flex justify-between">
                                            <div class="text-sm font-medium text-blue-800">Asesor</div>
                                            <div class="text-sm text-gray-500">-</div>
                                        </div>
                                        <div class="flex justify-between">
                                            <div class="text-sm font-medium text-blue-800">Hora</div>
                                            <div class="text-sm text-gray-500">-</div>
                                        </div>
                                        <div class="flex justify-between">
                                            <div class="text-sm font-medium text-blue-800">Whatsapp</div>
                                            <div class="text-sm text-gray-500">-</div>
                                        </div>
                                        <div class="flex justify-between">
                                            <div class="text-sm font-medium text-blue-800">Sede</div>
                                            <div class="text-sm text-gray-500">-</div>
                                        </div>
                                        <div class="flex justify-between">
                                            <div class="text-sm font-medium text-blue-800">Correo</div>
                                            <div class="text-sm text-gray-500">-</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="3" class="px-6 py-2 text-right">
                                    <div class="flex justify-end gap-8">
                                        <button type="button" class="text-primary-600 hover:text-primary-800 flex items-center text-sm">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Ver comprobante
                                        </button>
                                        <button type="button" class="text-primary-600 hover:text-primary-800 flex items-center text-sm">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Editar
                                        </button>
                                        <button type="button" class="text-red-600 hover:text-red-800 flex items-center text-sm">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                            Anular
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sección de historial de servicios -->
        <div>
            <div class="mb-4 rounded-md bg-primary-600 text-white p-3 flex justify-between items-center cursor-pointer">
                <h2 class="font-medium">Historial de servicios</h2>
                <svg class="w-5 h-5 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>

            <div class="rounded-md overflow-x-auto">
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
