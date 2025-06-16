<x-filament-panels::page>
    <div class="mb-6">
        <p class="text-sm text-gray-600">Consulta el detalle de una cita agendada.</p>
    </div>

    <div class="flex justify-start items-center mb-6 gap-4">
        <!-- Selector de local -->
        <div class="w-1/3">
            <select wire:model.live="data.selectedLocal" class="w-full rounded-lg border-gray-300 dark:border-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                @foreach(\App\Models\Local::where('is_active', true)->orderBy('name')->get() as $local)
                    <option value="{{ $local->code }}">{{ $local->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Selector de semana -->
        <div class="flex items-center justify-center">
            <button wire:click="previousWeek" class="p-2 hover:bg-gray-100 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <button class="mx-2 rounded-lg border-gray-300 dark:border-gray-700 shadow-sm focus:border-primary-500 focus:ring-primary-500">
                <option>Semana {{ $selectedWeek->weekOfYear }}, {{ $selectedWeek->format('Y') }}</option>
            </button>
            <button wire:click="nextWeek" class="p-2 hover:bg-gray-100 rounded-full">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>
    </div>

    <!-- Título de programación -->
    <div class="bg-primary-500 text-white font-semibold p-3 rounded-lg">
        Programación Toyota -
        {{ \App\Models\Local::where('code', $data['selectedLocal'] ?? $selectedLocal)->value('name') ?? 'Seleccione un local' }}
    </div>

    <div class="flex justify-between">
        <div class="mb-4">
            <!-- Grilla de horarios -->
            <div class="rounded-lg overflow-x-auto bg-white">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="w-20 bg-primary-600 text-white p-2"></th>
                            @foreach ($weekDays as $day)
                                <th class="border p-2 text-center bg-primary-600 text-white">
                                    <div class="text-sm font-medium px-1">{{ $day['dayName'] }} {{ $day['dayNumber']}}/{{ $day['date']->format('m') }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $prevHourBase = '';
                            $rowspanActive = false;
                        @endphp

                        @foreach ($timeSlots as $time => $slots)
                            @php
                                // Determinar si es hora en punto (00 minutos)
                                $isHourStart = strpos($time, ':00') !== false;
                                $hourBase = substr($time, 0, strpos($time, ':'));
                                $ampm = strpos($time, 'AM') !== false ? 'AM' : 'PM';
                                $fullHour = $hourBase . ':00 ' . $ampm;

                                // Verificar si es una nueva hora en punto
                                $isNewHour = $hourBase !== $prevHourBase || $prevHourBase === '';
                                $prevHourBase = $hourBase;
                            @endphp

                            <tr>
                                @if ($isHourStart)
                                    <!-- Hora en punto - mostrar celda con rowspan=4 (4 intervalos de 15 minutos) -->
                                    <td class="border text-center text-sm font-medium bg-primary-600 text-white" rowspan="4">{{ $time }}</td>
                                    @php $rowspanActive = true; @endphp
                                @endif

                                @foreach ($weekDays as $day)
                                    <td class="border p-1">
                                        <div class="h-6 flex items-center justify-center">
                                            @php
                                                $fecha = $day['date']->format('Y-m-d');
                                                $slot = $timeSlots[$time][$fecha] ?? null;
                                                $bloqueado = $slot['bloqueado'] ?? false;
                                                $reservado = $slot['reservado'] ?? false;
                                                $seleccionado = $slot['seleccionado'] ?? false;
                                            @endphp

                                            @if($bloqueado)
                                                <!-- Slot bloqueado - se puede seleccionar -->
                                                <div
                                                    wire:click="toggleSlot('{{ $time }}', '{{ $fecha }}', 'bloqueado')"
                                                    class="w-full h-full {{ $seleccionado ? 'border-2 border-primary-500' : 'bg-white border border-gray-200' }} rounded flex items-center justify-center cursor-pointer"
                                                >
                                                    <img src="{{ asset('images/lock.svg') }}" alt="Bloqueado" class="w-4 h-4">
                                                </div>
                                            @elseif($reservado)
                                                <!-- Slot reservado - se puede seleccionar -->
                                                <div
                                                    wire:click="toggleSlot('{{ $time }}', '{{ $fecha }}', 'reservado')"
                                                    class="w-full h-full {{ $seleccionado ? 'border-2 border-primary-500' : 'bg-white border border-gray-200' }} rounded flex items-center justify-center cursor-pointer"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </div>
                                            @else
                                                <!-- Slot disponible -->
                                                <div
                                                    wire:click="toggleSlot('{{ $time }}', '{{ $fecha }}', 'disponible')"
                                                    class="w-full h-full {{ $seleccionado ? 'border-2 border-primary-500' : 'bg-white border border-gray-200 hover:bg-blue-50' }} rounded cursor-pointer"
                                                ></div>
                                            @endif
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Panel lateral de información y configuraciones -->
        <div>
            <!-- Panel de información según el tipo de celda seleccionada -->
            @if($selectedSlotStatus === 'bloqueado')
                <!-- Espacio bloqueado -->
                <div class="border border-primary-500 rounded-lg flex flex-col items-center justify-center h-64 mb-4">
                    <div class="mb-4 bg-white rounded-full p-4">
                        <!-- Icono de candado desde archivo SVG -->
                        <img src="{{ asset('images/calendarBlock.svg') }}" alt="Bloqueado" class="h-8 w-8">
                    </div>
                    <h3 class="font-semibold text-primary-800 mb-2">Espacio No Disponible</h3>
                    <p class="text-sm text-center text-gray-600">Este horario está bloqueado, no se</p>
                    <p class="text-sm text-center text-gray-600 mb-4">puede reservar citas</p>
                </div>
            @elseif($selectedSlotStatus === 'reservado')
                <!-- Espacio reservado -->
                <div class="border border-primary-500 rounded-lg flex flex-col items-center justify-center h-64 mb-4">
                    <div class="mb-4 bg-white rounded-full p-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-14 w-14 text-primary-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h3 class="font-semibold text-primary-800 mb-2">Espacio Reservado</h3>
                    <p class="text-sm text-center text-gray-600 mb-4">Este horario ya tiene una cita reservada</p>
                </div>
            @else
                <!-- Espacio disponible (por defecto) -->
                <div class="border border-primary-500 rounded-lg flex flex-col items-center justify-center h-64 mb-4">
                    <div class="mb-4 bg-white rounded-full p-4">
                        <!-- Icono de calendario desde archivo SVG -->
                        <img src="{{ asset('images/calendar.svg') }}" alt="Calendario" class="h-14 w-14">
                    </div>
                    <h3 class="font-semibold text-primary-800 mb-2">Espacio disponible</h3>
                    <p class="text-sm text-center text-gray-600 mb-4">Aún no hay una cita reservada en este espacio</p>
                </div>
            @endif

            <div class="flex flex-col bg-white rounded-lg shadow p-4">
            <!-- Configuraciones del calendario -->
            <div class="mb-4">
                <h3 class="font-semibold mb-4">CONFIGURACIONES DEL CALENDARIO</h3>

                <form wire:submit.prevent="saveSettings">
                    <div class="space-y-4 mb-4">
                        <div>
                            <h3 class="font-semibold mb-4">Tiempo para permitir una reserva</h3>
                            <div class="mb-4">
                                <label class="block text-sm text-gray-700 mb-2">Mínimo</label>
                                <div class="flex gap-2 items-center">
                                    <select wire:model="minTimeUnit" class="rounded-lg border border-primary-500 text-gray-700 py-2 px-3 w-full">
                                        <option value="days">Días</option>
                                        <option value="hours">Horas</option>
                                        <option value="minutes">Minutos</option>
                                    </select>
                                    <input type="number" wire:model="minReservationTime" value="" class="rounded-lg border border-primary-500 text-gray-700 py-2 px-3 w-full">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-2">Máximo</label>
                                <div class="flex gap-2 items-center">
                                    <select wire:model="maxTimeUnit" class="rounded-lg border border-primary-500 text-gray-700 py-2 px-3 w-full">
                                        <option value="days">Días</option>
                                        <option value="hours">Horas</option>
                                        <option value="minutes">Minutos</option>
                                    </select>
                                    <input type="number" wire:model="maxReservationTime" value="" class="rounded-lg border border-primary-500 text-gray-700 py-2 px-3 w-full">
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="w-full mt-4 bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg">
                        Guardar
                    </button>
                </form>
            </div>

            <!-- Periodos donde no habrá atención -->
            <div>
                <h3 class="font-semibold mb-4">Periodos donde no habrá atención</h3>
                <button wire:click="programBlock" class="w-full bg-primary-600 hover:bg-primary-700 text-white py-2 px-4 rounded-lg">
                    Programar bloqueo
                </button>
            </div>

            </div>

        </div>

    </div>

    <!-- Leyenda -->
    <div class="mt-4 flex flex-wrap gap-4 rounded-lg">
        <div class="flex items-center gap-2">
            <div class="w-10 h-6 bg-white border border-gray-300 rounded mr-2"></div>
            <span class="text-sm text-blue-900 font-medium">Disponible</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-10 h-6 border border-gray-300 rounded mr-2"></div>
            <span class="text-sm text-blue-900 font-medium">Reservado</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-10 h-6 bg-white border border-gray-300 rounded mr-2 flex items-center justify-center">
                <img src="{{ asset('images/lock.svg') }}" alt="Bloqueado" class="w-4 h-4">
            </div>
            <span class="text-sm text-blue-900 font-medium">Bloqueado</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-10 h-6 border-2 border-primary-500 rounded mr-2"></div>
            <span class="text-sm text-blue-900 font-medium">Seleccionado</span>
        </div>
    </div>
</x-filament-panels::page>
