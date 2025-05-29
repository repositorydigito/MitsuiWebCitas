<x-filament-panels::page>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <!-- Indicador de progreso -->
        <div class="flex justify-center items-center mb-4">
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
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4" wire:key="locales-container-{{ $localSeleccionado }}">
                @foreach ($locales as $key => $local)
                    <div class="flex items-center p-3 border rounded-lg {{ $localSeleccionado == $key ? 'border-primary-500 bg-primary-50' : 'border-gray-300' }}" wire:key="local-{{ $key }}">
                        <div class="flex items-center h-5 mt-1 p-2">
                            <input type="radio" id="local-{{ $key }}" name="local" value="{{ $key }}" wire:model.live="localSeleccionado" class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="local-{{ $key }}" class="font-medium text-gray-700">{{ $local['nombre'] }}</label>
                            <p class="text-gray-500 text-xs">{{ $local['direccion'] }}</p>
                            <p class="text-gray-500 text-xs">{{ $local['telefono'] }}</p>
                        </div>
                        <div class="ml-auto flex space-x-2 gap-4">
                            @if(!empty($local['maps_url']))
                                <!-- Botón Maps -->
                                <a href="{{ $local['maps_url'] }}" target="_blank" class="text-primary-600 hover:text-primary-800">
                                    <img src="/images/maps.svg" alt="Maps" class="w-9 h-9 rounded-md">
                                </a>
                            @endif
                            @if(!empty($local['waze_url']))
                                <!-- Botón Waze -->
                                <a href="{{ $local['waze_url'] }}" target="_blank" class="text-primary-600 hover:text-primary-800">
                                    <img src="/images/waze.svg" alt="Waze" class="w-9 h-9 rounded-md">
                                </a>
                            @endif
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
                            <span class="text-lg font-medium text-primary-800">{{ $this->nombreMesActual }}</span>
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
                            <span class="text-lg font-medium text-primary-800">{{ $anoActual }}</span>
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
                        @foreach($diasCalendario as $dia)
                            <div
                                class="py-2 text-base {{ $dia['esActual'] ? ($dia['disponible'] ? 'cursor-pointer hover:bg-primary-100' : 'text-gray-400') : 'text-gray-400' }}
                                {{ $dia['fecha'] === $fechaSeleccionada ? 'bg-primary-500 text-white rounded-md' : ($dia['disponible'] && $dia['esActual'] ? 'text-primary-600' : '') }}
                                {{ $dia['esPasado'] ? 'opacity-50' : '' }}"
                                @if($dia['disponible'])
                                    wire:click="seleccionarFecha('{{ $dia['fecha'] }}')"
                                @endif
                            >
                                {{ $dia['dia'] }}

                                @if($dia['esHoy'])
                                    <div class="w-1 h-1 {{ $dia['fecha'] === $fechaSeleccionada ? 'bg-white' : 'bg-primary-600' }} rounded-full mx-auto mt-1"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Horarios disponibles -->
                <div class="border rounded-lg p-4">
                    <h3 class="text-lg font-medium text-primary-800 mb-4">Horarios disponibles</h3>

                    @if(empty($fechaSeleccionada))
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-4 h-4 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p>Selecciona una fecha para ver los horarios disponibles</p>
                        </div>
                    @elseif(empty($horariosDisponibles))
                        <div class="text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p>No hay horarios disponibles para esta fecha</p>
                            <p class="text-xs mt-2">Esta fecha puede estar bloqueada o todos los horarios están ocupados</p>
                        </div>
                    @else
                        <style>
                            .horarios-grid {
                                display: grid;
                                grid-template-columns: repeat(3, 1fr);
                                gap: 8px;
                            }

                            .horario-item {
                                cursor: pointer;
                            }

                            .horario-card {
                                width: 100%;
                                height: 48px;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                text-align: center;
                                border-radius: 0.5rem;
                                border-width: 1px;
                                font-size: 0.75rem;
                                padding: 0.25rem;
                            }
                        </style>

                        <div class="horarios-grid">
                            @foreach($horariosDisponibles as $hora)
                                <div class="horario-item">
                                    <div
                                        class="horario-card {{ $horaSeleccionada === $hora ? 'text-white bg-primary-600' : 'text-primary-600 hover:border-primary-600' }}"
                                        wire:click="seleccionarHora('{{ $hora }}')"
                                    >
                                        <span>{{ $hora }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if(count($horariosDisponibles) < 3)
                            <div class="mt-4 text-xs text-gray-500 bg-yellow-50 p-2 rounded-md border border-yellow-200">
                                <p class="flex items-center">
                                    <svg class="w-4 h-4 mr-1 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                    </svg>
                                    Quedan pocos horarios disponibles para esta fecha
                                </p>
                            </div>
                        @endif
                    @endif
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
                            <select id="tipoMantenimiento" wire:model.live="tipoMantenimiento" class="w-full rounded-md border-gray-300 shadow-sm focus:border-primary-500 focus:ring focus:ring-primary-500 focus:ring-opacity-50">
                                <option value="10,000 Km">10,000 Km</option>
                                <option value="20,000 Km">20,000 Km</option>
                                <option value="30,000 Km">30,000 Km</option>
                            </select>
                        </div>

                        <div class="mt-4">
                            <p class="text-sm font-medium text-gray-700 mb-2">Modalidad</p>
                            <div class="space-y-2" wire:key="modalidades-{{ $localSeleccionado }}-{{ $tipoMantenimiento }}">
                                @foreach($modalidadesDisponibles as $value => $label)
                                    <div class="flex items-center">
                                        <input type="radio" id="modalidad-{{ $loop->index }}" name="modalidad" value="{{ $value }}" wire:model="modalidadServicio" class="h-4 w-4 text-primary-600 border-gray-300 focus:ring-primary-500">
                                        <label for="modalidad-{{ $loop->index }}" class="p-2 text-sm text-gray-700">{{ $label }}</label>
                                    </div>
                                @endforeach
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



            <!-- Estilos para el carrusel -->
            <style>
                .carousel-container {
                    position: relative;
                    width: 100%;
                    overflow: hidden;
                }

                .carousel-items {
                    display: flex;
                    overflow-x: auto;
                    scroll-behavior: smooth;
                    -webkit-overflow-scrolling: touch;
                    scrollbar-width: none;
                }

                .carousel-items::-webkit-scrollbar {
                    display: none;
                }

                .carousel-item {
                    flex: 0 0 100%;
                    width: 100%;
                    padding: 0 8px;
                    box-sizing: border-box;
                }

                @media (min-width: 768px) {
                    .carousel-item {
                        flex: 0 0 33.333%;
                        width: 33.333%;
                    }
                }

                .carousel-nav {
                    position: absolute;
                    top: 50%;
                    transform: translateY(-50%);
                    width: 40px;
                    height: 40px;
                    background: white;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
                    cursor: pointer;
                    z-index: 10;
                    border: 1px solid #e5e7eb;
                }

                .carousel-nav-left {
                    left: 5px;
                }

                .carousel-nav-right {
                    right: 5px;
                }
            </style>

            <!-- Campañas disponibles -->
            @if(count($campanasDisponibles) > 0)
                <div class="mt-2" wire:key="campanas-container-{{ $localSeleccionado }}-{{ count($campanasDisponibles) }}">
                    <div x-data="{
                        activeSlide: 0,
                        totalSlides: {{ count($campanasDisponibles) }},
                        slidesPerView: window.innerWidth < 768 ? 1 : 3,

                        init() {
                            window.addEventListener('resize', () => {
                                this.slidesPerView = window.innerWidth < 768 ? 1 : 3;
                            });
                        },

                        next() {
                            if (this.activeSlide < this.totalSlides - this.slidesPerView) {
                                this.activeSlide++;
                                this.scrollToSlide();
                            }
                        },

                        prev() {
                            if (this.activeSlide > 0) {
                                this.activeSlide--;
                                this.scrollToSlide();
                            }
                        },

                        scrollToSlide() {
                            const container = this.$refs.carousel;
                            const slideWidth = container.offsetWidth / this.slidesPerView;
                            container.scrollTo({
                                left: this.activeSlide * slideWidth,
                                behavior: 'smooth'
                            });
                        }
                    }" x-init="init()" class="carousel-container">
                        <div x-ref="carousel" class="carousel-items">
                            @foreach($campanasDisponibles as $index => $campana)
                                <div class="carousel-item" x-data="{ selected: {{ in_array('campana_'.$campana['id'], $serviciosAdicionales) ? 'true' : 'false' }} }" wire:key="campana-{{ $campana['id'] }}">
                                    <div
                                        class="border rounded-lg overflow-hidden cursor-pointer"
                                        :class="selected ? 'border-primary-500 border-2' : 'border-gray-300'"
                                        @click="
                                            selected = !selected;
                                            if (selected) {
                                                $wire.serviciosAdicionales = [...$wire.serviciosAdicionales, 'campana_{{ $campana['id'] }}'];
                                            } else {
                                                $wire.serviciosAdicionales = $wire.serviciosAdicionales.filter(item => item !== 'campana_{{ $campana['id'] }}');
                                            }
                                        "
                                    >
                                        <img src="{{ $campana['imagen'] }}" alt="{{ $campana['titulo'] }}" class="w-full h-96 object-cover" loading="lazy">
                                        <div class="p-2" :class="selected ? 'bg-primary-100' : ''">
                                            <h4 class="text-sm font-medium" :class="selected ? 'text-primary-800' : ''">{{ $campana['titulo'] }}</h4>
                                            <p class="text-xs text-gray-400 mt-1">
                                                @if(isset($campana['fecha_fin']))
                                                    Válido hasta: {{ \Carbon\Carbon::parse($campana['fecha_fin'])->format('d/m/Y') }}
                                                @else
                                                    Campaña permanente
                                                @endif
                                            </p>
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
                            @endforeach
                        </div>

                        <!-- Botones de navegación -->
                        <div @click="prev()" x-show="activeSlide > 0" class="carousel-nav carousel-nav-left">
                            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </div>
                        <div @click="next()" x-show="activeSlide < totalSlides - slidesPerView" class="carousel-nav carousel-nav-right">
                            <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </div>
                </div>
            @endif

            @if(count($campanasDisponibles) == 0)
                <div class="text-center py-8 text-gray-500 bg-gray-50 rounded-lg">
                    <svg class="w-8 h-8 mx-auto text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p>No hay campañas disponibles en este momento</p>
                </div>
            @endif
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
                                <!-- Debug: {{ json_encode($vehiculo) }}
                                <div class="text-xs text-gray-500">ID: {{ $vehiculo['id'] ?? 'No disponible' }}</div>-->
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
                            <td class="py-2 text-gray-800">
                                @if($servicioSeleccionado == 'Mantenimiento periódico')
                                    Mantenimiento periódico
                                @elseif($servicioSeleccionado == 'Consultas / otros 1')
                                    Campañas / otros
                                @elseif($servicioSeleccionado == 'Consultas / otros 2')
                                    Reparación
                                @elseif($servicioSeleccionado == 'Consultas / otros 3')
                                    Llamado a revisión
                                @else
                                    {{ $servicioSeleccionado }}
                                @endif
                            </td>
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
                            <td class="py-2 text-gray-800">
                                @if($servicioSeleccionado == 'Mantenimiento periódico')
                                    Mantenimiento periódico
                                @elseif($servicioSeleccionado == 'Consultas / otros 1')
                                    Campañas / otros
                                @elseif($servicioSeleccionado == 'Consultas / otros 2')
                                    Reparación
                                @elseif($servicioSeleccionado == 'Consultas / otros 3')
                                    Llamado a revisión
                                @else
                                    {{ $servicioSeleccionado }}
                                @endif
                            </td>
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

    <!-- Modal de Pop-ups -->
    <div x-data="{ show: @entangle('mostrarModalPopups') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Fondo oscuro -->
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity">
                <div class="absolute inset-0 bg-black/50"></div>
            </div>

            <!-- Modal -->
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <!-- Botón de cerrar -->
                <div class="absolute top-0 right-0 p-4">
                    <button @click="show = false" wire:click="$refresh; $redirect('{{ \App\Filament\Pages\Vehiculos::getUrl() }}')" type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Contenido del modal -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                ¿Deseas conocer nuestros servicios?
                            </h3>
                            <p class="text-sm text-gray-500 mb-4">
                                Recibe información sobre nuestros servicios. Elige uno y
                            </p>
                            <p class="text-sm text-gray-500 mb-4">
                                recibirás un mensaje por whatsapp.
                            </p>

                            <!-- Lista de pop-ups -->
                            <div class="mt-4 space-y-4">
                                @foreach($popupsDisponibles as $popup)
                                    <div class="flex items-center border rounded-lg p-2 {{ in_array($popup['id'], $popupsSeleccionados) ? 'border-primary-500 bg-primary-50' : 'border-gray-300' }}">
                                        <div class="flex items-center h-5">
                                            <input
                                                type="checkbox"
                                                id="popup-{{ $popup['id'] }}"
                                                wire:click="togglePopup({{ $popup['id'] }})"
                                                {{ in_array($popup['id'], $popupsSeleccionados) ? 'checked' : '' }}
                                                class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500"
                                            >
                                        </div>
                                        <div class="ml-3 text-sm flex-grow">
                                            <label for="popup-{{ $popup['id'] }}" class="font-medium text-gray-700">{{ $popup['nombre'] }}</label>
                                        </div>
                                        <div>
                                            <img src="{{ $popup['imagen'] }}" alt="{{ $popup['nombre'] }}" class="h-24 w-32 object-contain">
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones del modal -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button wire:click="solicitarInformacion" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Solicitar información
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Resumen de Pop-ups -->
    <div x-data="{ show: @entangle('mostrarModalResumenPopups') }" x-show="show" x-cloak class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Fondo oscuro -->
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity">
                <div class="absolute inset-0 bg-black/50"></div>
            </div>

            <!-- Modal -->
            <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <!-- Botón de cerrar -->
                <div class="absolute top-0 right-0 px-4 pt-4">
                    <button @click="show = false" wire:click="cerrarResumen" type="button" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Contenido del modal -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4 text-center">
                                Resumen
                            </h3>

                            <!-- Mensaje de éxito -->
                            <div class="bg-green-100 border-l-4 border-green-500 text-primary-600 mb-4 pt-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-primary-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-sm">Datos guardados con éxito</p>
                                    </div>
                                </div>
                            </div>

                            <p class="text-sm text-gray-700 mb-1 text-center">
                                Pronto serás contactado por whatsapp para recibir
                            </p>
                            <p class="text-sm text-gray-700 mb-4 text-center">
                                más información sobre los siguientes servicios.
                            </p>

                            <!-- Lista de pop-ups seleccionados -->
                            <div class="mt-4 space-y-4">
                                @foreach($popupsDisponibles as $popup)
                                    @if(in_array($popup['id'], $popupsSeleccionados))
                                        <div class="flex items-center border rounded-lg p-2 border-gray-300">
                                            <div class="ml-3 text-sm flex-grow">
                                                <span class="font-medium text-gray-700">{{ $popup['nombre'] }}</span>
                                            </div>
                                            <div class="ml-auto">
                                                <img src="{{ $popup['imagen'] }}" alt="{{ $popup['nombre'] }}" class="h-24 w-32 object-contain">
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones del modal -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button wire:click="cerrarResumen" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

@push('scripts')
<script>
    document.addEventListener('livewire:load', function () {
        Livewire.on('mostrarResumen', function () {
            document.getElementById('resumen-cita').scrollIntoView({ behavior: 'smooth' });
        });

        // Evento para actualizar la vista cuando cambian los horarios
        Livewire.on('horarios-actualizados', function () {
            console.log('Horarios actualizados, refrescando vista');
            // Forzar actualización de Alpine.js
            document.querySelectorAll('[x-data]').forEach(el => {
                if (el.__x) {
                    el.__x.updateElements(el);
                }
            });
        });

        // Evento para mostrar notificaciones
        Livewire.on('notify', function (data) {
            console.log('Notificación:', data);

            // Crear elemento de notificación
            const notification = document.createElement('div');
            notification.className = 'fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-500 transform translate-x-full';

            // Aplicar estilos según el tipo
            if (data.type === 'error') {
                notification.className += ' bg-red-500 text-white';
            } else if (data.type === 'success') {
                notification.className += ' bg-green-500 text-white';
            } else {
                notification.className += ' bg-primary-500 text-white';
            }

            // Agregar mensaje
            notification.textContent = data.message;

            // Agregar al DOM
            document.body.appendChild(notification);

            // Mostrar con animación
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 10);

            // Ocultar después de 3 segundos
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    notification.remove();
                }, 500);
            }, 3000);
        });
    });
</script>
@endpush
