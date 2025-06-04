<x-filament-panels::page>
    <div class="bg-white rounded-lg shadow-sm p-6">

        <!-- Indicador de progreso -->
        <div class="flex justify-center items-center mb-8">
            <div class="flex items-center">
                @for ($i = 1; $i <= $totalSteps; $i++)
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center {{ $currentStep >= $i ? 'bg-primary-600 text-white' : 'bg-gray-300 text-gray-700' }} border border-gray-400">
                            {{ $i }}
                        </div>
                        @if ($i < $totalSteps)
                            <div class="h-1 w-16 {{ $currentStep > $i ? 'bg-primary-600' : 'bg-gray-300' }} mx-2"></div>
                        @endif
                    </div>
                @endfor
            </div>
        </div>

        <!-- Paso 1: Formulario de datos -->
        @if ($currentStep === 1)
            <div>
                <h2 class="text-lg font-semibold text-blue-900 mb-2">Datos de la cita</h2>
                <p class="text-gray-600 mb-4">Elige los datos para crear un bloqueo de fechas en el calendario.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div class="w-full">
                            <select wire:model="data.premises" class="w-full rounded-lg border {{ $errors['premises'] ? 'border-red-500' : 'border-primary-500' }} text-gray-700 py-2 px-3">
                                <option value="">Elegir local</option>
                                @foreach(\App\Models\Local::where('is_active', true)->orderBy('name')->get() as $local)
                                    <option value="{{ $local->code }}">{{ $local->name }}</option>
                                @endforeach
                            </select>
                            @if($errors['premises'])
                                <p class="text-primary-500 text-sm mt-1">Elige una opción disponible.</p>
                            @endif
                        </div>

                    <div class="flex items-center gap-2">
                        <input type="checkbox" wire:model.live="data.all_day" wire:click="$refresh" id="all_day" class="rounded border-primary-500 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200 focus:ring-opacity-50">
                        <label for="all_day" class="ml-2 text-gray-700">Todo el día</label>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <input type="date" wire:model="data.start_date" class="w-full rounded-lg border {{ $errors['start_date'] ? 'border-red-500' : 'border-primary-500' }} text-gray-700 py-2 px-3" placeholder="Elige la fecha de inicio">
                        @if($errors['start_date'])
                            <p class="text-primary-500 text-sm mt-1">Elige una opción disponible.</p>
                        @endif
                    </div>
                    <div>
                        @if ($data['all_day'])
                            <div class="w-full rounded-lg border border-gray-300 bg-gray-100 text-gray-500 py-2 px-3">
                                08:00 AM
                            </div>
                        @else
                            <select wire:model="data.start_time" class="w-full rounded-lg border {{ $errors['start_time'] ? 'border-red-500' : 'border-primary-500' }} text-gray-700 py-2 px-3">
                                <option value="">Hora de inicio</option>
                                @for ($hour = 8; $hour <= 18; $hour++)
                                    <option value="{{ sprintf('%02d', $hour) }}:00">{{ sprintf('%02d', $hour) }}:00</option>
                                    @if ($hour < 18)
                                        <option value="{{ sprintf('%02d', $hour) }}:30">{{ sprintf('%02d', $hour) }}:30</option>
                                    @endif
                                @endfor
                            </select>
                            @if($errors['start_time'])
                                <p class="text-primary-500 text-sm mt-1">Elige una opción disponible.</p>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <input type="date" wire:model="data.end_date" class="w-full rounded-lg border {{ $errors['end_date'] ? 'border-red-500' : 'border-primary-500' }} text-gray-700 py-2 px-3" placeholder="Elige la fecha de fin">
                        @if($errors['end_date'])
                            <p class="text-primary-500 text-sm mt-1">Elige una opción disponible.</p>
                        @endif
                    </div>
                    <div>
                        @if ($data['all_day'])
                            <div class="w-full rounded-lg border border-gray-300 bg-gray-100 text-gray-500 py-2 px-3">
                                06:00 PM
                            </div>
                        @else
                            <select wire:model="data.end_time" class="w-full rounded-lg border {{ $errors['end_time'] ? 'border-red-500' : 'border-primary-500' }} text-gray-700 py-2 px-3">
                                <option value="">Hora de fin</option>
                                @for ($hour = 8; $hour <= 18; $hour++)
                                    <option value="{{ sprintf('%02d', $hour) }}:00">{{ sprintf('%02d', $hour) }}:00</option>
                                    @if ($hour < 18)
                                        <option value="{{ sprintf('%02d', $hour) }}:30">{{ sprintf('%02d', $hour) }}:30</option>
                                    @endif
                                @endfor
                            </select>
                            @if($errors['end_time'])
                                <p class="text-primary-500 text-sm mt-1">Elige una opción disponible.</p>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="mb-4">
                    <textarea wire:model="data.comments" class="w-full rounded-lg border border-primary-500 text-gray-700 py-2 px-3" rows="4" placeholder="Comentarios u observaciones"></textarea>
                </div>

                <div class="flex justify-between border-t pt-4">
                    <div>
                        <button type="button" wire:click="cerrarYVolver" class="px-6 py-2 border border-primary-500 text-primary-500 rounded-lg hover:bg-gray-50">
                            Volver
                        </button>

                        <!-- Botón de depuración (solo en desarrollo) -->
                        @if(config('app.env') === 'local')
                            <button type="button" wire:click="debug" class="ml-2 px-4 py-2 bg-gray-500 text-white rounded-lg">
                                Debug
                            </button>
                        @endif
                    </div>

                    <button type="button" wire:click="nextStep" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        Continuar
                    </button>
                </div>
            </div>
        @endif

        <!-- Paso 2: Resumen y confirmación -->
        @if ($currentStep === 2)
            <div>
                <h2 class="text-lg font-semibold text-blue-900 mb-2">Resumen del bloqueo</h2>
                <p class="text-gray-600 mb-4">Revisa los datos antes de confirmar</p>

                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <div class="mb-4">
                        <p class="text-sm text-gray-500">Local</p>
                        <p class="font-medium">
                                {{ \App\Models\Local::where('code', $premises)->value('name') ?? $premises }}
                        </p>
                    </div>
                    <div class="mb-4">
                        <p class="text-sm text-gray-500">Duración</p>
                        <p class="font-medium">
                                @if($all_day)
                                    Todo el día
                                @else
                                    {{ $start_time }} - {{ $end_time }}
                                @endif
                        </p>
                    </div>
                    <div class="mb-4">
                            <p class="text-sm text-gray-500">Fecha de inicio</p>
                            <p class="font-medium">{{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }}</p>
                    </div>
                    <div class="mb-4">
                            <p class="text-sm text-gray-500">Fecha de fin</p>
                            <p class="font-medium">{{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</p>
                    </div>

                @if($comments)
                    <div class="mb-4">
                        <p class="text-sm text-gray-500">Comentarios</p>
                        <p class="font-medium">{{ $comments }}</p>
                    </div>
                @endif
                </div>

                <div class="flex justify-between mt-8 border-t pt-4">
                    <button type="button" wire:click="previousStep" class="px-6 py-2 border border-primary-500 text-primary-500 rounded-lg hover:bg-gray-50">
                        Volver
                    </button>

                    <button type="button" wire:click="confirmarBloqueo" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        Confirmar
                    </button>
                </div>
            </div>
        @endif

        <!-- Paso 3: Confirmación exitosa -->
        @if ($currentStep === 3)
            <div>
                <div class="text-center mb-6">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-green-100 text-green-500 mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h2 class="text-lg font-semibold text-blue-900 mb-2">¡Bloqueo programado con éxito!</h2>
                    <p class="text-gray-600 mb-4">El bloqueo ha sido registrado en el sistema</p>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                        <div class="mb-4">
                            <p class="text-sm text-gray-500">Local</p>
                            <p class="font-medium">
                                {{ \App\Models\Local::where('code', $premises)->value('name') ?? $premises }}
                            </p>
                        </div>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500">Duración</p>
                            <p class="font-medium">
                                @if($all_day)
                                    Todo el día
                                @else
                                    {{ $start_time }} - {{ $end_time }}
                                @endif
                            </p>
                        </div>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500">Fecha de inicio</p>
                            <p class="font-medium">{{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }}</p>
                        </div>
                        <div class="mb-4">
                            <p class="text-sm text-gray-500">Fecha de fin</p>
                            <p class="font-medium">{{ \Carbon\Carbon::parse($end_date)->format('d/m/Y') }}</p>
                        </div>

                    @if($comments)
                        <div class="mb-4">
                            <p class="text-sm text-gray-500">Comentarios</p>
                            <p class="font-medium">{{ $comments }}</p>
                        </div>
                    @endif
                </div>

                <div class="flex justify-center mt-8 border-t pt-4">
                    <button type="button" wire:click="cerrarYVolver" class="px-6 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                        Cerrar
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
