{{-- Sección de horarios disponibles con integración C4C --}}
<div class="border rounded-lg p-4">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-medium text-primary-800">Horarios disponibles</h3>
        
        {{-- NUEVO: Indicador de estado C4C --}}
        <div class="flex items-center space-x-2">
            @if($estadoConexionC4C === 'connected')
                <div class="flex items-center text-green-600 text-sm">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    C4C Conectado
                </div>
            @elseif($estadoConexionC4C === 'error')
                <div class="flex items-center text-amber-600 text-sm">
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    Modo Local
                </div>
            @endif

            {{-- NUEVO: Toggle para alternar entre C4C y local --}}
            @if($estadoConexionC4C === 'connected')
                <button 
                    type="button"
                    wire:click="toggleHorariosC4C"
                    class="text-xs px-2 py-1 rounded {{ $usarHorariosC4C ? 'bg-primary-100 text-primary-700' : 'bg-gray-100 text-gray-700' }} hover:bg-primary-200 transition-colors"
                    title="{{ $usarHorariosC4C ? 'Cambiar a horarios locales' : 'Cambiar a horarios C4C' }}"
                >
                    {{ $usarHorariosC4C ? 'C4C' : 'Local' }}
                </button>
            @endif
        </div>
    </div>

    {{-- NUEVO: Indicador de carga --}}
    @if($consultandoDisponibilidad)
        <div class="text-center py-8">
            <div class="inline-flex items-center text-primary-600">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Consultando disponibilidad en tiempo real...
            </div>
        </div>
    @elseif(!empty($errorDisponibilidad))
        {{-- NUEVO: Mostrar errores de disponibilidad --}}
        <div class="bg-amber-50 border border-amber-200 rounded-md p-3 mb-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-amber-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-amber-700">{{ $errorDisponibilidad }}</p>
                </div>
            </div>
        </div>
    @endif

    @if(empty($fechaSeleccionada))
        <div class="text-center py-8 text-gray-500">
            <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
            </svg>
            <p>Selecciona una fecha para ver los horarios disponibles</p>
        </div>
    @elseif(empty($horariosDisponibles) && !$consultandoDisponibilidad)
        <div class="text-center py-8 text-gray-500">
            <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <p>No hay horarios disponibles para esta fecha</p>
            <p class="text-xs mt-2">
                @if($usarHorariosC4C)
                    Esta fecha puede estar bloqueada en C4C o todos los horarios están ocupados
                @else
                    Esta fecha puede estar bloqueada o todos los horarios están ocupados
                @endif
            </p>
        </div>
    @else
        {{-- NUEVO: Información adicional sobre la fuente de datos --}}
        @if(!empty($horariosDisponibles))
            <div class="mb-3 text-xs text-gray-600 flex items-center justify-between">
                <span>
                    @if($usarHorariosC4C && !empty($slotsC4C))
                        <span class="inline-flex items-center">
                            <span class="w-2 h-2 bg-green-500 rounded-full mr-1"></span>
                            Horarios en tiempo real desde C4C ({{ count($horariosDisponibles) }} disponibles)
                        </span>
                    @else
                        <span class="inline-flex items-center">
                            <span class="w-2 h-2 bg-blue-500 rounded-full mr-1"></span>
                            Horarios locales ({{ count($horariosDisponibles) }} disponibles)
                        </span>
                    @endif
                </span>
                
                {{-- Botón para recargar horarios --}}
                <button 
                    type="button"
                    wire:click="cargarHorariosDisponibles"
                    class="text-primary-600 hover:text-primary-800 text-xs"
                    title="Recargar horarios"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </button>
            </div>
        @endif

        <style>
            .horarios-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 8px;
            }
            
            .horario-slot {
                position: relative;
                transition: all 0.2s ease;
            }
            
            .horario-slot:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            }
            
            .horario-slot.c4c-slot {
                border-left: 3px solid #10b981;
            }
            
            .horario-slot.local-slot {
                border-left: 3px solid #3b82f6;
            }
        </style>

        <div class="horarios-grid">
            @foreach($horariosDisponibles as $hora)
                @php
                    $slotInfo = $this->getSlotInfo($hora);
                    $isSelected = $horaSeleccionada === $hora;
                    $isC4CSlot = $slotInfo['origen'] === 'c4c';
                @endphp
                
                <div class="horario-slot {{ $isC4CSlot ? 'c4c-slot' : 'local-slot' }}">
                    <button
                        type="button"
                        wire:click="seleccionarHora('{{ $hora }}')"
                        class="w-full px-3 py-2 text-sm rounded-md border transition-colors
                            {{ $isSelected 
                                ? 'bg-primary-500 text-white border-primary-500' 
                                : 'bg-white text-gray-700 border-gray-300 hover:bg-primary-50 hover:border-primary-300' 
                            }}"
                        title="{{ $isC4CSlot ? 'Horario desde C4C' : 'Horario local' }} - Duración: {{ $slotInfo['duracion'] }}"
                    >
                        <div class="font-medium">
                            {{ \Carbon\Carbon::parse($hora)->format('H:i') }}
                        </div>
                        
                        {{-- NUEVO: Indicador visual del origen --}}
                        @if($usarHorariosC4C && !empty($slotsC4C))
                            <div class="text-xs opacity-75 mt-1">
                                @if($isC4CSlot)
                                    <span class="text-green-600">●</span> {{ $slotInfo['duracion'] }}
                                @else
                                    <span class="text-blue-600">●</span> Local
                                @endif
                            </div>
                        @endif
                    </button>
                    
                    {{-- NUEVO: Tooltip con información adicional --}}
                    @if($isC4CSlot && !empty($slotInfo['object_id']))
                        <div class="absolute z-10 invisible group-hover:visible bg-gray-800 text-white text-xs rounded py-1 px-2 -top-8 left-1/2 transform -translate-x-1/2">
                            C4C ID: {{ substr($slotInfo['object_id'], 0, 8) }}...
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- NUEVO: Leyenda de colores --}}
        @if($usarHorariosC4C && !empty($slotsC4C))
            <div class="mt-4 pt-3 border-t border-gray-200">
                <div class="flex items-center justify-center space-x-6 text-xs text-gray-600">
                    <div class="flex items-center">
                        <span class="w-3 h-3 bg-green-500 rounded-sm mr-1"></span>
                        Horarios C4C en tiempo real
                    </div>
                    <div class="flex items-center">
                        <span class="w-3 h-3 bg-blue-500 rounded-sm mr-1"></span>
                        Horarios locales
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

