<x-filament-panels::page>
    <div>
        <!-- Indicador de progreso -->
        <div class="bg-white rounded-lg shadow-sm p-6 flex justify-center items-center mb-8">
            <div class="flex items-center">
                @for ($i = 1; $i <= $totalPasos; $i++)
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $pasoActual >= $i ? 'bg-primary-600 text-white' : 'bg-gray-300 text-gray-700' }} border border-gray-400">
                            {{ $i }}
                        </div>
                        @if ($i < $totalPasos)
                            <div class="h-1 w-16 {{ $pasoActual > $i ? 'bg-primary-600' : 'bg-gray-300' }} mx-2"></div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>
        <br>

        <!-- Paso 1: Datos del vehículo -->
        <div class="bg-white rounded-lg shadow-sm p-6 {{ $pasoActual == 1 ? 'block' : 'hidden' }}">
            <h2 class="text-xl font-semibold text-[#0A2463] mb-2">Datos del vehículo</h2>
            <p class="text-gray-600 mb-4">Ingresa los datos del vehículo</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-6">
                    <div class="relative">
                        <input 
                            type="text" 
                            id="placa" 
                            wire:model="placa" 
                            placeholder="Placa" 
                            class="text-primary-600 w-full rounded-md border-primary-600 shadow-sm focus:border-primary-600 focus:ring focus:ring-primary-600 focus:ring-opacity-50"
                        >
                    </div>

                    <div>
                        <input 
                            type="text" 
                            id="modelo" 
                            wire:model="modelo" 
                            placeholder="Modelo" 
                            class="text-primary-600 w-full rounded-md border-primary-600 shadow-sm focus:border-primary-600 focus:ring focus:ring-primary-600 focus:ring-opacity-50"
                        >
                    </div>

                    <div>
                        <input 
                            type="text" 
                            id="anio" 
                            wire:model="anio" 
                            placeholder="Año" 
                            class="text-primary-600 w-full rounded-md border-primary-600 shadow-sm focus:border-primary-600 focus:ring focus:ring-primary-600 focus:ring-opacity-50"
                        >
                    </div>
                </div>

                <div class="flex flex-col justify-center items-center gap-4 py-4 border border-primary-600 rounded-md">
                    <!-- Tacómetro -->
                    <div>
                        <img src="{{ asset('images/tacometro.png') }}" alt="Tacómetro" width="174" height="174">
                    </div>

                    <div class="w-auto">
                        <input type="text" placeholder="Kilometraje (km)" id="kilometraje" wire:model="kilometraje" class="w-full rounded-md border-primary-600 shadow-sm focus:border-[#0075BF] focus:ring focus:ring-[#0075BF] focus:ring-opacity-50">
                    </div>
                </div>
            </div>
        </div>

        <!-- Paso 2: Resumen de datos -->
        <div class="{{ $pasoActual == 2 ? 'block' : 'hidden' }}">
            <h2 class="text-xl font-semibold text-[#0A2463] mb-4">Resumen</h2>

            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-900">Placa</dt>
                        <dd class="text-sm text-gray-700 col-span-2">{{ $placa ?: 'No especificado' }}</dd>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-900">Modelo</dt>
                        <dd class="text-sm text-gray-700 col-span-2">{{ $modelo ?: 'No especificado' }}</dd>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-900">Año</dt>
                        <dd class="text-sm text-gray-700 col-span-2">{{ $anio ?: 'No especificado' }}</dd>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-900">Kilometraje</dt>
                        <dd class="text-sm text-gray-700 col-span-2">{{ $kilometraje ? $kilometraje . ' km' : 'No especificado' }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Paso 3: Confirmación -->
        <div class="{{ $pasoActual == 3 ? 'block' : 'hidden' }}">
            <div class="bg-green-100 border-l-4 border-green-500 p-4 mb-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="px-2">
                        <p class="text-sm text-green-700">Vehículo agregado con éxito.</p>
                    </div>
                </div>
            </div>

            <h2 class="text-xl font-semibold text-[#0A2463] mb-4">Resumen</h2>

            <div class="bg-white rounded-lg border border-gray-200 overflow-hidden mb-4">
                <dl class="divide-y divide-gray-200">
                    <div class="px-4 py-3 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-900">Placa</dt>
                        <dd class="text-sm text-gray-700 col-span-2">{{ $placa ?: 'No especificado' }}</dd>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-900">Modelo</dt>
                        <dd class="text-sm text-gray-700 col-span-2">{{ $modelo ?: 'No especificado' }}</dd>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-900">Año</dt>
                        <dd class="text-sm text-gray-700 col-span-2">{{ $anio ?: 'No especificado' }}</dd>
                    </div>
                    <div class="px-4 py-3 grid grid-cols-3 gap-4">
                        <dt class="text-sm font-medium text-gray-900">Kilometraje</dt>
                        <dd class="text-sm text-gray-700 col-span-2">{{ $kilometraje ? $kilometraje . ' km' : 'No especificado' }}</dd>
                    </div>
                </dl>
            </div>

            <div class="flex justify-center">
                <button type="button" wire:click="volverAVehiculos" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-[#0066A6] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0075BF] relative">
                    <div class="absolute -bottom-4 -right-4 w-8 h-8 bg-red-300 rounded-full opacity-50"></div>
                    Cerrar
                </button>
            </div>
        </div>

        <!-- Botones de navegación (solo para pasos 1 y 2) -->
        <div class="flex justify-center mt-8 gap-6 border-t pt-6 {{ $pasoActual == 3 ? 'hidden' : 'block' }}">
            <button type="button" wire:click="{{ $pasoActual == 1 ? 'volverAVehiculos' : 'volver' }}" class="px-6 py-2 border border-[#0075BF] rounded-md text-sm font-medium text-[#0075BF] bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0075BF] shadow-sm">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver
                </div>
            </button>

            <button type="button" wire:click="{{ $pasoActual == 2 ? 'confirmar' : 'continuar' }}" 
            class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-[#0066A6] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#0075BF]">
                {{ $pasoActual == 2 ? 'Confirmar' : 'Continuar' }}
            </button>
        </div>
    </div>
</x-filament-panels::page>
