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

        <!-- Paso 1: Formulario de creación de campaña -->
        @if ($pasoActual === 1)
            <div>
                <!-- Datos de la campaña -->
                <div class="mb-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Datos de la campaña</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="codigoCampana" class="block text-sm font-medium text-gray-700 mb-1">Código de campaña</label>
                            <input
                                type="text"
                                id="codigoCampana"
                                wire:model="codigoCampana"
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                            @error('codigoCampana') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="tituloCampana" class="block text-sm font-medium text-gray-700 mb-1">Título de campaña</label>
                            <input
                                type="text"
                                id="tituloCampana"
                                wire:model="tituloCampana"
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                            >
                            @error('tituloCampana') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="fechaInicio" class="block text-sm font-medium text-gray-700 mb-1">Elige la fecha de inicio</label>
                            <div class="relative">
                                <input
                                    type="text"
                                    id="fechaInicio"
                                    wire:model="fechaInicio"
                                    placeholder=""
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 datepicker-inicio"
                                    autocomplete="off"
                                >
                            </div>
                            @error('fechaInicio') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label for="fechaFin" class="block text-sm font-medium text-gray-700 mb-1">Elige la fecha de fin</label>
                            <div class="relative">
                                <input
                                    type="text"
                                    id="fechaFin"
                                    wire:model="fechaFin"
                                    placeholder=""
                                    class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 datepicker-fin"
                                    autocomplete="off"
                                >
                            </div>
                            @error('fechaFin') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Segmentación -->
                <div class="mb-6 border-t pt-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Segmentación</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="modelos" class="block text-sm font-medium text-gray-700 mb-1">Elegir modelos</label>
                            <div x-data="{ open: false, selectedOptions: @entangle('modelosSeleccionados') }" class="relative">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="w-full flex justify-between items-center px-3 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-left text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                                >
                                    <span x-text="selectedOptions.length ? (selectedOptions.length + ' seleccionados') : 'Seleccionar modelos'"></span>
                                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                                <div
                                    x-show="open"
                                    @click.away="open = false"
                                    class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-lg py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto max-h-60"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    style="display: none;"
                                >
                                    @foreach ($modelos as $modelo)
                                        <div class="px-4 py-2 hover:bg-gray-100 ">
                                            <label class="flex items-center cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    value="{{ $modelo }}"
                                                    x-model="selectedOptions"
                                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
                                                >
                                                <span class="px-2">{{ $modelo }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="anos" class="block text-sm font-medium text-gray-700 mb-1">Elegir años de los modelos</label>
                            <div x-data="{ open: false, selectedOptions: @entangle('anosSeleccionados') }" class="relative">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="w-full flex justify-between items-center px-3 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-left text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                                >
                                    <span x-text="selectedOptions.length ? (selectedOptions.length + ' seleccionados') : 'Seleccionar años'"></span>
                                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                                <div
                                    x-show="open"
                                    @click.away="open = false"
                                    class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-lg py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto max-h-60"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    style="display: none;"
                                >
                                    @foreach ($anos as $ano)
                                        <div class="px-3 py-2 hover:bg-gray-100">
                                            <label class="flex items-center cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    value="{{ $ano }}"
                                                    x-model="selectedOptions"
                                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
                                                >
                                                <span class="px-2">{{ $ano }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div>
                            <label for="locales" class="block text-sm font-medium text-gray-700 mb-1">Elegir locales</label>
                            <div x-data="{ open: false, selectedOptions: @entangle('localesSeleccionados') }" class="relative">
                                <button
                                    type="button"
                                    @click="open = !open"
                                    class="w-full flex justify-between items-center px-3 py-2 border border-gray-300 rounded-lg shadow-sm bg-white text-left text-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                                >
                                    <span x-text="selectedOptions.length ? (selectedOptions.length + ' seleccionados') : 'Seleccionar locales'"></span>
                                    <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                                <div
                                    x-show="open"
                                    @click.away="open = false"
                                    class="absolute z-10 mt-1 w-full bg-white shadow-lg rounded-lg py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto max-h-60"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    style="display: none;"
                                >
                                    @foreach ($locales as $local)
                                        <div class="px-3 py-2 hover:bg-gray-100">
                                            <label class="flex items-center cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    value="{{ $local }}"
                                                    x-model="selectedOptions"
                                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
                                                >
                                                <span class="px-2">{{ $local }}</span>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            @error('localesSeleccionados') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <!-- Horario para mostrar campaña -->
                <div class="mb-6 border-t pt-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-4">Horario para mostrar campaña</h2>
                    <div class="mb-4">
                        <label class="inline-flex items-center">
                            <input
                                type="checkbox"
                                wire:model="todoElDia"
                                class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
                            >
                            <span class="px-2 text-sm text-gray-700">Todo el día</span>
                        </label>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-data="{}" x-bind:class="{ 'opacity-50': $wire.todoElDia }">
                        <div>
                            <label for="horaInicio" class="block text-sm font-medium text-gray-700 mb-1">Hora de inicio</label>
                            <select
                                id="horaInicio"
                                wire:model="horaInicio"
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                x-bind:disabled="$wire.todoElDia"
                            >
                                @for ($hora = 0; $hora < 24; $hora++)
                                    @for ($minuto = 0; $minuto < 60; $minuto += 30)
                                        <option value="{{ sprintf('%02d:%02d', $hora, $minuto) }}">
                                            {{ sprintf('%02d:%02d', $hora, $minuto) }}
                                        </option>
                                    @endfor
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label for="horaFin" class="block text-sm font-medium text-gray-700 mb-1">Hora de fin</label>
                            <select
                                id="horaFin"
                                wire:model="horaFin"
                                class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                                x-bind:disabled="$wire.todoElDia"
                            >
                                @for ($hora = 0; $hora < 24; $hora++)
                                    @for ($minuto = 0; $minuto < 60; $minuto += 30)
                                        <option value="{{ sprintf('%02d:%02d', $hora, $minuto) }}">
                                            {{ sprintf('%02d:%02d', $hora, $minuto) }}
                                        </option>
                                    @endfor
                                @endfor
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Imagen y Estado -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 border-t pt-6">
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Imagen</h2>
                        <div class="flex items-center w-full">
                            <div class="w-1/2 border border-gray-300 rounded-lg p-2 text-gray-500 mr-2">
                                Sin selección
                            </div>
                            <button
                                type="button"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-primary-600 bg-white border border-primary-600 rounded-lg hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2"
                            >
                                Seleccionar Archivo
                            </button>
                        </div>
                    </div>
                    <div>
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Estado de la campaña</h2>
                        <div class="flex justify-between gap-4">
                            <label class="inline-flex items-center">
                                <input
                                    type="radio"
                                    wire:model="estadoCampana"
                                    value="Activo"
                                    class="rounded-full border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
                                >
                                <span class="px-2 text-sm text-gray-700">Activo</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input
                                    type="radio"
                                    wire:model="estadoCampana"
                                    value="Inactivo"
                                    class="rounded-full border-gray-300 text-primary-600 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50"
                                >
                                <span class="px-2 text-sm text-gray-700">Inactivo</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Paso 2: Resumen de la campaña -->
        @if ($pasoActual === 2)
            <div>
                <h2 class="text-xl font-medium text-gray-900 mb-6">Resumen</h2>

                <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
                    <table class="w-full">
                        <tbody>
                            <tr class="border-b">
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50 w-1/3">Código de campaña</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $codigoCampana }}</td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Título de campaña</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $tituloCampana }}</td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Fecha de inicio</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $fechaInicio }}</td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Fecha de fin</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $fechaFin }}</td>
                            </tr>

                            <tr class="border-b">
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Modelos</td>
                                <td class="px-6 py-3 text-sm text-gray-900">
                                    @if (count($modelosSeleccionados) > 0)
                                        {{ implode(', ', $modelosSeleccionados) }}
                                    @else
                                        Todos
                                    @endif
                                </td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Años</td>
                                <td class="px-6 py-3 text-sm text-gray-900">
                                    @if (count($anosSeleccionados) > 0)
                                        {{ implode(', ', $anosSeleccionados) }}
                                    @else
                                        Todos
                                    @endif
                                </td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Locales</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ implode(', ', $localesSeleccionados) }}</td>
                            </tr>

                            @if ($todoElDia)
                                <tr class="border-b">
                                    <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Horario</td>
                                    <td class="px-6 py-3 text-sm text-gray-900">Todo el día</td>
                                </tr>
                            @else
                                <tr class="border-b">
                                    <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Horario de inicio</td>
                                    <td class="px-6 py-3 text-sm text-gray-900">{{ $horaInicio }}</td>
                                </tr>
                                <tr class="border-b">
                                    <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Horario de fin</td>
                                    <td class="px-6 py-3 text-sm text-gray-900">{{ $horaFin }}</td>
                                </tr>
                            @endif

                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Estado</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $estadoCampana }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Paso 3: Confirmación -->
        @if ($pasoActual === 3)
            <div class="text-center py-8">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-600 mb-6">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">¡Campaña creada con éxito!</h2>

                <div class="bg-white rounded-lg shadow overflow-hidden mb-6 max-w-2xl mx-auto">
                    <table class="w-full">
                        <tbody>
                            <tr class="border-b">
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50 w-1/3">Código de campaña</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $codigoCampana }}</td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Título de campaña</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $tituloCampana }}</td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Fecha de inicio</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $fechaInicio }}</td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Fecha de fin</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $fechaFin }}</td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Modelos</td>
                                <td class="px-6 py-3 text-sm text-gray-900">
                                    @if (count($modelosSeleccionados) > 0)
                                        {{ implode(', ', $modelosSeleccionados) }}
                                    @else
                                        Todos
                                    @endif
                                </td>
                            </tr>
                            <tr class="border-b">
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Locales</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ implode(', ', $localesSeleccionados) }}</td>
                            </tr>
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-primary-600 bg-gray-50">Estado</td>
                                <td class="px-6 py-3 text-sm text-gray-900">{{ $estadoCampana }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Botones de navegación -->
        <div class="flex justify-center mt-8 gap-6 border-t pt-6">
            @if ($pasoActual === 1)
                <button
                    type="button"
                    wire:click="volverACampanas"
                    class="px-6 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 shadow-sm"
                >
                    Volver
                </button>
                <button
                    type="button"
                    wire:click="siguientePaso"
                    class="px-6 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 shadow-sm"
                >
                    Continuar
                </button>
            @elseif ($pasoActual === 2)
                <button
                    type="button"
                    wire:click="anteriorPaso"
                    class="px-6 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 shadow-sm"
                >
                    Volver
                </button>
                <button
                    type="button"
                    wire:click="finalizarCreacion"
                    class="px-6 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 shadow-sm"
                >
                    Confirmar
                </button>
            @elseif ($pasoActual === 3)
                <button
                    type="button"
                    wire:click="volverACampanas"
                    class="px-6 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 shadow-sm"
                >
                    Volver a Campañas
                </button>
            @endif
        </div>
    </div>

    {{-- Scripts para los datepickers --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            initDatepickers();
        });

        document.addEventListener('livewire:navigated', function () {
            initDatepickers();
        });

        function initDatepickers() {
            // Datepicker para fecha de inicio
            const datepickerInicio = document.querySelector('.datepicker-inicio');
            if (datepickerInicio) {
                flatpickr(datepickerInicio, {
                    dateFormat: "d/m/Y",
                    locale: "es",
                    allowInput: true,
                    disableMobile: true,
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length > 0) {
                            @this.set('fechaInicio', dateStr);
                        }
                    }
                });
            }

            // Datepicker para fecha de fin
            const datepickerFin = document.querySelector('.datepicker-fin');
            if (datepickerFin) {
                flatpickr(datepickerFin, {
                    dateFormat: "d/m/Y",
                    locale: "es",
                    allowInput: true,
                    disableMobile: true,
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length > 0) {
                            @this.set('fechaFin', dateStr);
                        }
                    }
                });
            }
        }
    </script>
</x-filament-panels::page>
