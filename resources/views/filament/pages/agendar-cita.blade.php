<x-filament-panels::page>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <!-- Indicador de progreso -->
        <div class="flex justify-center items-center mb-8">
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

        <h1 class="text-2xl font-bold text-primary-600 mb-6">Datos de la cita</h1>
        <br>

        <!-- Paso 1: Formulario de datos -->
        <div class="{{ $pasoActual == 1 ? 'block' : 'hidden' }}">

        <!-- Datos de la cita -->
        <div class="mb-4">
            <h2 class="text-xl font-semibold mb-4">1. Revisa tus datos</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <input
            type="text"
            id="nombreCliente"
            wire:model="nombreCliente"
            value="PABLO"
            placeholder="Nombres"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
            readonly
        >
    </div>
    <div>
        <input
            type="email"
            id="emailCliente"
            wire:model="emailCliente"
            value="pablo@mitsui.com.pe"
            placeholder="Email"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
            readonly
        >
    </div>
    <div>
        <input
            type="text"
            id="apellidoCliente"
            wire:model="apellidoCliente"
            value="RODRIGUEZ MENDOZA"
            placeholder="Apellidos"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
            readonly
        >
    </div>
    <div>
        <input
            type="tel"
            id="celularCliente"
            wire:model="celularCliente"
            value="987654321"
            placeholder="Celular"
            class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
            readonly
        >
    </div>
</div>
        </div>
        <br>

        <!-- Local -->
        <div class="mb-4">
            <h2 class="text-xl font-semibold mb-4">2. Elige el local</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($locales as $key => $local)
                    <div class="flex items-start p-3 border rounded-lg {{ $localSeleccionado == $key ? 'border-primary-500 bg-primary-50' : 'border-gray-300' }}">
                        <div class="flex items-center h-5 mt-1 p-2">
                            <input type="radio" id="local-{{ $key }}" name="local" value="{{ $key }}" wire:model="localSeleccionado" class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="local-{{ $key }}" class="font-medium text-gray-700">{{ $local['nombre'] }}</label>
                            <p class="text-gray-500 text-xs">{{ $local['direccion'] }}</p>
                        </div>
                        <div class="ml-auto flex space-x-2 gap-4">
                            <!-- Botón 1: Maps -->
                            <button type="button" class="text-primary-600 hover:text-primary-800">
                                <img src="/images/maps.svg" alt="Maps" class="w-9 h-9 rounded-md">
                            </button>
                            <!-- Botón 2: Waze -->
                            <button type="button" class="text-primary-600 hover:text-primary-800">
                                <img src="/images/waze.svg" alt="Waze" class="w-9 h-9 rounded-md">
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <br>

        <!-- Fecha y hora -->
        <div class="mb-4">
            <h2 class="text-xl font-semibold mb-4">3. Elige una fecha y hora</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Calendario -->
                <div class="border rounded-lg p-4">
                    <!-- Navegación del mes y año -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-2">
                            <button type="button" class="flex items-center justify-center w-8 h-8 text-primary-600" wire:click="cambiarMes(-1)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <span class="text-lg font-medium text-primary-800">Abril</span>
                            <button type="button" class="flex items-center justify-center w-8 h-8 text-primary-600" wire:click="cambiarMes(1)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="flex items-center space-x-2">
                            <button type="button" class="flex items-center justify-center w-8 h-8 text-primary-600" wire:click="cambiarAno(-1)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                            </button>
                            <span class="text-lg font-medium text-primary-800">2025</span>
                            <button type="button" class="flex items-center justify-center w-8 h-8 text-primary-600" wire:click="cambiarAno(1)">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Días de la semana -->
                    <div class="grid grid-cols-7 gap-1 text-center mb-2">
                        <div class="text-base font-medium text-primary-800">L</div>
                        <div class="text-base font-medium text-primary-800">M</div>
                        <div class="text-base font-medium text-primary-800">M</div>
                        <div class="text-base font-medium text-primary-800">J</div>
                        <div class="text-base font-medium text-primary-800">V</div>
                        <div class="text-base font-medium text-primary-800">S</div>
                        <div class="text-base font-medium text-primary-800">D</div>
                    </div>

                    <!-- Días del mes -->
                    <div class="grid grid-cols-7 gap-1 text-center">
                        <!-- Primera semana -->
                        <div class="py-2 text-base text-gray-400">29</div>
                        <div class="py-2 text-base text-gray-400">30</div>
                        <div class="py-2 text-base text-gray-400">31</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '01/04/2025')">1</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '02/04/2025')">2</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '03/04/2025')">3</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '04/04/2025')">4</div>

                        <!-- Segunda semana -->
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-05')">5</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-06')">6</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-07')">7</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-08')">8</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-09')">9</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-10')">10</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-11')">11</div>

                        <!-- Tercera semana -->
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-12')">12</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-13')">13</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-14')">14</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-15')">15</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-16')">16</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-17')">17</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-18')">18</div>

                        <!-- Cuarta semana -->
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-19')">19</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-20')">20</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-21')">21</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-22')">22</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-23')">23</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-24')">24</div>
                        <div class="py-2 text-base text-gray-400">25</div>

                        <!-- Quinta semana -->
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-26')">26</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-27')">27</div>
                        <div class="py-2 text-base text-white bg-primary-600 rounded-md w-8 h-8 flex items-center justify-center mx-auto cursor-pointer" wire:click="$set('fechaSeleccionada', '2024-11-28')">28</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-29')">29</div>
                        <div class="py-2 text-base text-primary-600 cursor-pointer hover:bg-primary-100 rounded-md" wire:click="$set('fechaSeleccionada', '2024-11-30')">30</div>
                        <div class="py-2 text-base text-gray-400">1</div>
                        <div class="py-2 text-base text-gray-400">2</div>
                    </div>
                </div>

                <!-- Horarios disponibles -->
<div class="border rounded-lg p-4">
    <div class="flex flex-wrap -mx-1">

        <!-- Horario 1 -->
        <div x-data="{ selected: @entangle('horaSeleccionada').defer === '08:00 AM' }" class="w-1/3 px-1 mb-2 cursor-pointer flex-shrink-0">
            <div
                class="w-full text-xs sm:text-sm border rounded-lg p-3 text-center h-12 flex items-center justify-center"
                :class="selected ? 'text-white bg-primary-600' : 'text-primary-600 hover:border-primary-600'"
                @click="
                    selected = !selected;
                    $wire.set('horaSeleccionada', selected ? '08:00 AM' : '');
                    $el.closest('.flex').querySelectorAll('[x-data]').forEach(el => {
                        if (el !== $el.closest('[x-data]') && selected) {
                            Alpine.raw(el).__x.$data.selected = false;
                        }
                    });
                "
            >
                <span>08:00 AM</span>
            </div>
        </div>

        <!-- Horario 2 -->
        <div x-data="{ selected: @entangle('horaSeleccionada').defer === '09:15 AM' }" class="w-1/3 px-1 mb-2 cursor-pointer flex-shrink-0">
            <div
                class="w-full text-xs sm:text-sm border rounded-lg p-3 text-center h-12 flex items-center justify-center"
                :class="selected ? 'text-white bg-primary-600' : 'text-primary-600 hover:border-primary-600'"
                @click="
                    selected = !selected;
                    $wire.set('horaSeleccionada', selected ? '09:15 AM' : '');
                    $el.closest('.flex').querySelectorAll('[x-data]').forEach(el => {
                        if (el !== $el.closest('[x-data]') && selected) {
                            Alpine.raw(el).__x.$data.selected = false;
                        }
                    });
                "
            >
                <span>09:15 AM</span>
            </div>
        </div>

        <!-- Horario 3 -->
        <div x-data="{ selected: @entangle('horaSeleccionada').defer === '10:15 AM' }" class="w-1/3 px-1 mb-2 cursor-pointer flex-shrink-0">
            <div
                class="w-full text-xs sm:text-sm border rounded-lg p-3 text-center h-12 flex items-center justify-center"
                :class="selected ? 'text-white bg-primary-600' : 'text-primary-600 hover:border-primary-600'"
                @click="
                    selected = !selected;
                    $wire.set('horaSeleccionada', selected ? '10:15 AM' : '');
                    $el.closest('.flex').querySelectorAll('[x-data]').forEach(el => {
                        if (el !== $el.closest('[x-data]') && selected) {
                            Alpine.raw(el).__x.$data.selected = false;
                        }
                    });
                "
            >
                <span>10:15 AM</span>
            </div>
        </div>

        <!-- Horario 4 -->
        <div x-data="{ selected: @entangle('horaSeleccionada').defer === '11:15 AM' }" class="w-1/3 px-1 mb-2 cursor-pointer flex-shrink-0">
            <div
                class="w-full text-xs sm:text-sm border rounded-lg p-3 text-center h-12 flex items-center justify-center"
                :class="selected ? 'text-white bg-primary-600' : 'text-primary-600 hover:border-primary-600'"
                @click="
                    selected = !selected;
                    $wire.set('horaSeleccionada', selected ? '11:15 AM' : '');
                    $el.closest('.flex').querySelectorAll('[x-data]').forEach(el => {
                        if (el !== $el.closest('[x-data]') && selected) {
                            Alpine.raw(el).__x.$data.selected = false;
                        }
                    });
                "
            >
                <span>11:15 AM</span>
            </div>
        </div>

        <!-- Horario 5 -->
        <div x-data="{ selected: @entangle('horaSeleccionada').defer === '01:00 PM' }" class="w-1/3 px-1 mb-2 cursor-pointer flex-shrink-0">
            <div
                class="w-full text-xs sm:text-sm border rounded-lg p-3 text-center h-12 flex items-center justify-center"
                :class="selected ? 'text-white bg-primary-600' : 'text-primary-600 hover:border-primary-600'"
                @click="
                    selected = !selected;
                    $wire.set('horaSeleccionada', selected ? '01:00 PM' : '');
                    $el.closest('.flex').querySelectorAll('[x-data]').forEach(el => {
                        if (el !== $el.closest('[x-data]') && selected) {
                            Alpine.raw(el).__x.$data.selected = false;
                        }
                    });
                "
            >
                <span>01:00 PM</span>
            </div>
        </div>

        <!-- Horario 6 -->
        <div x-data="{ selected: @entangle('horaSeleccionada').defer === '02:00 PM' }" class="w-1/3 px-1 mb-2 cursor-pointer flex-shrink-0">
            <div
                class="w-full text-xs sm:text-sm border rounded-lg p-3 text-center h-12 flex items-center justify-center"
                :class="selected ? 'text-white bg-primary-600' : 'text-primary-600 hover:border-primary-600'"
                @click="
                    selected = !selected;
                    $wire.set('horaSeleccionada', selected ? '02:00 PM' : '');
                    $el.closest('.flex').querySelectorAll('[x-data]').forEach(el => {
                        if (el !== $el.closest('[x-data]') && selected) {
                            Alpine.raw(el).__x.$data.selected = false;
                        }
                    });
                "
            >
                <span>02:00 PM</span>
            </div>
        </div>

    </div>
</div>
            </div>
        </div>
        <br>

        <!-- Servicio -->
        <div class="mb-4">
            <h2 class="text-xl font-semibold mb-4">4. Elige el servicio</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="border rounded-lg p-4 {{ $servicioSeleccionado == 'Mantenimiento periódico' ? 'border-gray-500 bg-primary-50' : 'border-gray-300' }}">
                    <div class="flex items-start">
                        <div class="flex items-center h-6 mt-1">
                            <input type="radio" id="servicio-mantenimiento" name="servicio" value="Mantenimiento periódico" wire:model="servicioSeleccionado" class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                        </div>
                        <div class="p-1">
                            <label for="servicio-mantenimiento" class="font-medium text-gray-700">Mantenimiento periódico</label>
                            <p class="text-gray-500 text-xs">Servicio según el kilometraje</p>
                        </div>
                    </div>
                    <br>

                    @if ($servicioSeleccionado == 'Mantenimiento periódico')
                        <div class="mt-4 mb-4">
                            <label for="tipoMantenimiento" class="block text-sm font-medium text-gray-700 mb-2">Tipo de mantenimiento</label>
                            <select id="tipoMantenimiento" wire:model="tipoMantenimiento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                                <option value="Mantenimiento 10000 Km">Mantenimiento 10,000 Km</option>
                                <option value="Mantenimiento 20000 Km">Mantenimiento 20,000 Km</option>
                                <option value="Mantenimiento 30000 Km">Mantenimiento 30,000 Km</option>
                            </select>
                        </div>

                        <div class="mt-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Modalidad</p>
                            <div class="space-y-2">
                                <div class="flex items-center">
                                    <input type="radio" id="modalidad-express" name="modalidad" value="Express (Duración 1h-30 min)" wire:model="modalidadServicio" class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                                    <label for="modalidad-express" class="p-2 text-sm text-gray-700">Express (Duración 1h 30 min)</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="radio" id="modalidad-regular" name="modalidad" value="Regular" wire:model="modalidadServicio" class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                                    <label for="modalidad-regular" class="p-2 text-sm text-gray-700">Regular</label>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="border rounded-lg p-4 {{ $servicioSeleccionado == 'Consultas / otros' ? 'border-gray-300 bg-primary-50' : 'border-gray-300' }}">
    <!-- Primer servicio -->
    <div class="flex items-start mb-4">
        <div class="flex items-center mt-6">
            <input
                type="radio"
                id="servicio-consultas-1"
                name="servicio"
                value="Consultas / otros 1"
                wire:model="servicioSeleccionado"
                class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500"
            >
        </div>
        <div class="p-3">
            <label for="servicio-consultas-1" class="font-medium text-gray-700">Campañas / otros</label>
            <p class="text-gray-500 text-xs">Servicio en base a pedido del cliente (Ej: lavado)</p>
        </div>
    </div>

    <!-- Segundo servicio -->
    <div class="flex items-start mb-4">
        <div class="flex items-center mt-6">
            <input
                type="radio"
                id="servicio-consultas-2"
                name="servicio"
                value="Consultas / otros 2"
                wire:model="servicioSeleccionado"
                class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500"
            >
        </div>
        <div class="p-3">
            <label for="servicio-consultas-2" class="font-medium text-gray-700">Reparación</label>
            <p class="text-gray-500 text-xs">Servicio de diagnóstico y reparación de averías</p>
        </div>
    </div>

    <!-- Tercer servicio -->
    <div class="flex items-start">
        <div class="flex items-center mt-6">
            <input
                type="radio"
                id="servicio-consultas-3"
                name="servicio"
                value="Consultas / otros 3"
                wire:model="servicioSeleccionado"
                class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500"
            >
        </div>
        <div class="p-3">
            <label for="servicio-consultas-3" class="font-medium text-gray-700">Llamado a revisión</label>
            <p class="text-gray-500 text-xs">Revisión del correcto funcionamiento del vehículo</p>
        </div>
    </div>
</div>

            </div>
        </div>
        <br>

        <!-- Servicios adicionales -->
        <div class="mb-4">
            <h2 class="text-xl font-semibold mb-4">5. Elige un servicio adicional (opcional)</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Servicio 1 -->
                <div class="relative" x-data="{ selected: false }">
                    <div
                        class="border rounded-lg overflow-hidden cursor-pointer"
                        :class="selected ? 'border-primary-500 ring-2 ring-primary-500' : 'border-gray-300'"
                        @click="selected = !selected"
                    >
                        <img src="{{ asset('images/toyota-hilux.jpg') }}" alt="Restauración de faros" class="w-full h-32 object-cover">
                        <div class="p-2 text-center" :class="selected ? 'bg-primary-100' : ''">
                            <span class="text-sm font-medium" :class="selected ? 'text-primary-800' : ''">Restauración de faros</span>
                        </div>
                        <div
                            class="absolute top-2 left-2 bg-primary-500 text-white rounded-full p-1"
                            x-show="selected"
                            style="display: none;"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Servicio 2 -->
                <div class="relative" x-data="{ selected: false }">
                    <div
                        class="border rounded-lg overflow-hidden cursor-pointer"
                        :class="selected ? 'border-primary-500 ring-2 ring-primary-500' : 'border-gray-300'"
                        @click="selected = !selected"
                    >
                        <img src="{{ asset('images/toyota-hilux.jpg') }}" alt="Restauración de rines" class="w-full h-32 object-cover">
                        <div class="p-2 text-center" :class="selected ? 'bg-primary-100' : ''">
                            <span class="text-sm font-medium" :class="selected ? 'text-primary-800' : ''">Restauración de rines</span>
                        </div>
                        <div
                            class="absolute top-2 left-2 bg-primary-500 text-white rounded-full p-1"
                            x-show="selected"
                            style="display: none;"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Servicio 3 -->
                <div class="relative" x-data="{ selected: false }">
                    <div
                        class="border rounded-lg overflow-hidden cursor-pointer"
                        :class="selected ? 'border-primary-500 ring-2 ring-primary-500' : 'border-gray-300'"
                        @click="selected = !selected"
                    >
                        <img src="{{ asset('images/toyota-hilux.jpg') }}" alt="Restauración de focos" class="w-full h-32 object-cover">
                        <div class="p-2 text-center" :class="selected ? 'bg-primary-100' : ''">
                            <span class="text-sm font-medium" :class="selected ? 'text-primary-800' : ''">Restauración de focos</span>
                        </div>
                        <div
                            class="absolute top-2 left-2 bg-primary-500 text-white rounded-full p-1"
                            x-show="selected"
                            style="display: none;"
                        >
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br>

        <!-- Comentarios -->
        <div class="mb-4">
            <h2 class="text-xl font-semibold mb-4">6. Escribe un comentario u observación</h2>
            <textarea id="comentarios" wire:model="comentarios" rows="5" placeholder="Ejemplo: Mi auto presenta ruidos al frenar" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"></textarea>
        </div>

        <!-- Botón de finalizar paso 1 -->
        <div class="flex justify-center gap-4">
            <button type="button" wire:click="volverAVehiculos" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Volver
                </div>
            </button>
            <button type="button" wire:click="finalizarAgendamiento" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Continuar
            </button>
        </div>
        </div>

        <!-- Paso 2: Resumen de datos -->
        <div class="{{ $pasoActual == 2 ? 'block' : 'hidden' }}">
            <h2 class="text-xl font-semibold mb-4">Resumen</h2>
            <p class="text-gray-600 mb-4">Notificaremos la confirmación de tu cita en el siguiente correo y celular. Si deseas cambiarlos vuelve al paso anterior.</p>

            <div class="bg-white p-6 rounded-lg border border-gray-200 mb-4">
                <table class="w-full">
                    <tbody>
                        <!-- Datos personales -->
                        <tr>
                            <td colspan="2" class="pb-2">
                                <h3 class="font-medium text-primary-700 text-lg border-b border-gray-200 pb-1 mb-2">Datos personales</h3>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Nombre Completo</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $nombreCliente }} {{ $apellidoCliente }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Celular</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $celularCliente }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Correo</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $emailCliente }}</td>
                        </tr>

                        <!-- Datos del vehículo -->
                        <tr>
                            <td colspan="2" class="pt-4 pb-2">
                                <h3 class="font-medium text-success-700 text-lg border-b border-gray-200 pb-1 mb-2">Datos del vehículo</h3>
                                <!-- Debug: {{ json_encode($vehiculo) }} -->
                                <div class="text-xs text-gray-500">ID: {{ $vehiculo['id'] ?? 'No disponible' }}</div>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-success-800">Modelo</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $vehiculo['modelo'] ?: 'No especificado' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-success-800">Placa</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $vehiculo['placa'] ?: 'No especificado' }}</td>
                        </tr>

                        <!-- Datos de la cita -->
                        <tr>
                            <td colspan="2" class="pt-4 pb-2">
                                <h3 class="font-medium text-info-700 text-lg border-b border-gray-200 pb-1 mb-2">Datos de la cita</h3>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-info-800">Local</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ !empty($localSeleccionado) && isset($locales[$localSeleccionado]) ? $locales[$localSeleccionado]['nombre'] : 'No seleccionado' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Fecha</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $fechaSeleccionada ?: 'No seleccionada' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Hora</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $horaSeleccionada ?: 'No seleccionada' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Servicio</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $servicioSeleccionado }}</td>
                        </tr>

                        @if ($servicioSeleccionado == 'Mantenimiento periódico')
                            <tr>
                                <td class="py-2 pr-4 w-1/3">
                                    <span class="font-medium text-primary-800">Mantenimiento</span>
                                </td>
                                <td class="py-2 text-gray-800">{{ $tipoMantenimiento }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 pr-4 w-1/3">
                                    <span class="font-medium text-primary-800">Modalidad</span>
                                </td>
                                <td class="py-2 text-gray-800">{{ $modalidadServicio }}</td>
                            </tr>
                        @endif

                            <tr>
                                <td class="py-2 pr-4 w-1/3">
                                    <span class="font-medium text-primary-800">Adicionales</span>
                                </td>
                                <td class="py-2 text-gray-800">
                                    @foreach ($serviciosAdicionales as $servicio)
                                        {{ $opcionesServiciosAdicionales[$servicio] ?? $servicio }}@if (!$loop->last), @endif
                                    @endforeach
                                </td>
                            </tr>

                            <tr>
                                <td class="py-2 pr-4 w-1/3">
                                    <span class="font-medium text-primary-800">Comentario</span>
                                </td>
                                <td class="py-2 text-gray-800">{{ $comentarios }}</td>
                            </tr>
                    </tbody>
                </table>
            </div>

            <!-- Botones de navegación paso 2 -->
            <div class="flex justify-center gap-4">
                <button type="button" wire:click="volver" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                        </svg>
                        Volver
                    </div>
                </button>
                <button type="button" wire:click="continuar" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-success-500">
                    Confirmar cita
                </button>
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
                    <div>
                        <p class="text-sm text-green-500">Cita reservada con éxito.</p>
                    </div>
                </div>
            </div>

            <h2 class="text-xl font-semibold mb-4">Resumen</h2>

            <div class="bg-white p-6 rounded-lg border border-gray-200 mb-4">
                <table class="w-full">
                    <tbody>
                        <!-- Datos personales -->
                        <tr>
                            <td colspan="2" class="pb-2">
                                <h3 class="font-medium text-primary-700 text-lg border-b border-gray-200 pb-1 mb-2">Datos personales</h3>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Nombre</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $nombreCliente }} {{ $apellidoCliente }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Celular</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $celularCliente }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Correo</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $emailCliente }}</td>
                        </tr>

                        <!-- Datos del vehículo -->
                        <tr>
                            <td colspan="2" class="pt-4 pb-2">
                                <h3 class="font-medium text-primary-700 text-lg border-b border-gray-200 pb-1 mb-2">Datos del vehículo</h3>
                                <!-- Debug: {{ json_encode($vehiculo) }} -->
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Modelo</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $vehiculo['modelo'] ?: 'No especificado' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Placa</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $vehiculo['placa'] ?: 'No especificado' }}</td>
                        </tr>

                        <!-- Datos de la cita -->
                        <tr>
                            <td colspan="2" class="pt-4 pb-2">
                                <h3 class="font-medium text-primary-700 text-lg border-b border-gray-200 pb-1 mb-2">Datos de la cita</h3>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Local</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ !empty($localSeleccionado) && isset($locales[$localSeleccionado]) ? $locales[$localSeleccionado]['nombre'] : 'No seleccionado' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Fecha</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $fechaSeleccionada ?: 'No seleccionada' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Hora</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $horaSeleccionada ?: 'No seleccionada' }}</td>
                        </tr>
                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Servicio</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $servicioSeleccionado }}</td>
                        </tr>

                        @if ($servicioSeleccionado == 'Mantenimiento periódico')
                            <tr>
                                <td class="py-2 pr-4 w-1/3">
                                    <span class="font-medium text-primary-800">Mantenimiento</span>
                                </td>
                                <td class="py-2 text-gray-800">{{ $tipoMantenimiento }}</td>
                            </tr>
                            <tr>
                                <td class="py-2 pr-4 w-1/3">
                                    <span class="font-medium text-primary-800">Modalidad</span>
                                </td>
                                <td class="py-2 text-gray-800">{{ $modalidadServicio }}</td>
                            </tr>
                        @endif

                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Adicionales</span>
                            </td>
                            <td class="py-2 text-gray-800">
                                @foreach ($serviciosAdicionales as $servicio)
                                    {{ $opcionesServiciosAdicionales[$servicio] ?? $servicio }}@if (!$loop->last), @endif
                                @endforeach
                            </td>
                        </tr>

                        <tr>
                            <td class="py-2 pr-4 w-1/3">
                                <span class="font-medium text-primary-800">Comentario</span>
                            </td>
                            <td class="py-2 text-gray-800">{{ $comentarios }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex justify-center">
                <button type="button" wire:click="cerrarYVolverACitas" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-danger-500">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
