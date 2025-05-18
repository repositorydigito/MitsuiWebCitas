@php
    use Illuminate\Support\Str;
@endphp

<x-filament-panels::page>
    <h3>Selecciona el vehículo para agendar una cita</h3>

    {{-- Barra de búsqueda y botones (responsive) --}}
    <div class="mb-4">
        {{-- Vista para móvil --}}
        <div class="lg:hidden space-y-4">
            <div class="w-full">
                <div class="relative">
                    <input
                        type="text"
                        wire:model.debounce.300ms="search"
                        placeholder="Buscar por placa"
                        class="w-full pl-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-white"
                    >
                </div>
            </div>

            <div class="flex gap-2">
                <button type="button" class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-primary-500 bg-white border border-primary-500 rounded-lg hover:bg-blue-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                    <svg class="w-4 h-4 mr-1 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="currentColor">
                        <path d="M16.006 3C9.375 3 3.995 8.38 3.995 15.01c0 2.648.695 5.13 2.021 7.338l-2.13 6.233 6.398-2.1A12.9 12.9 0 0016 27.01c6.632 0 12.01-5.38 12.01-12.003C28.01 8.38 22.63 3 16.006 3zm0 22.014a10.01 10.01 0 01-5.108-1.39l-.365-.218-3.796 1.243 1.243-3.688-.238-.379a9.978 9.978 0 01-1.565-5.26c0-5.52 4.488-10.007 10.016-10.007 5.52 0 10.007 4.487 10.007 10.007 0 5.528-4.487 10.002-10.007 10.002zm5.584-7.423c-.303-.15-1.787-.88-2.065-.983-.276-.102-.478-.15-.68.15-.203.3-.78.983-.956 1.187-.177.203-.352.228-.654.076-.303-.15-1.278-.47-2.434-1.5-.9-.8-1.51-1.788-1.686-2.09-.177-.3-.02-.46.133-.61.137-.136.303-.354.455-.532.152-.18.203-.3.303-.5.1-.2.05-.38-.025-.532-.077-.15-.68-1.638-.93-2.243-.244-.585-.49-.5-.68-.51l-.58-.01c-.2 0-.526.076-.8.38s-1.05 1.02-1.05 2.48 1.077 2.88 1.225 3.078c.15.2 2.12 3.23 5.137 4.527.717.31 1.276.494 1.712.63.718.228 1.372.197 1.886.12.575-.085 1.787-.73 2.04-1.437.25-.707.25-1.313.177-1.437-.074-.125-.276-.2-.58-.35z"/>
                    </svg>
                    <span class="whitespace-nowrap overflow-hidden text-ellipsis">Contactar central</span>
                </button>

                <button type="button" class="flex-1 inline-flex items-center justify-center px-3 py-2 text-sm font-medium text-primary-500 bg-white border border-primary-500 rounded-lg hover:bg-[#e6f6fc] focus:ring-2 focus:ring-[#169FDB] focus:ring-offset-1">
                    <svg class="w-4 h-4 mr-1 text-[#169FDB]" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884a1 1 0 01.57-1.11l2.828-1.26a1 1 0 011.185.297l1.516 1.89a1 1 0 01-.105 1.382L6.387 8.21a11.03 11.03 0 005.404 5.404l1.127-1.61a1 1 0 011.382-.105l1.89 1.516a1 1 0 01.297 1.185l-1.26 2.828a1 1 0 01-1.11.57c-3.213-.56-6.173-2.345-8.543-4.714s-4.154-5.33-4.714-8.543z" />
                    </svg>
                    <span class="whitespace-nowrap overflow-hidden text-ellipsis">Llamar central</span>
                </button>
            </div>

            <div class="w-full">
                <a href="{{ \App\Filament\Pages\AgregarVehiculo::getUrl() }}" class="inline-flex w-full items-center justify-center px-4 py-2 text-sm font-medium text-primary-500 bg-white border border-primary-500 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                    <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Agregar vehículo
                </a>
            </div>
        </div>

        {{-- Vista para desktop --}}
        <div class="hidden lg:flex flex-wrap gap-4 items-center justify-between">
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <input
                        type="text"
                        wire:model.debounce.300ms="search"
                        placeholder="Buscar por placa"
                        class="w-full pl-4 py-2 rounded-lg border border-gray-300 dark:border-gray-700 focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-white"
                    >
                </div>
            </div>
            <div class="flex gap-2">
                <button type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-primary-500 bg-white border border-primary-500 rounded-lg hover:bg-blue-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-1">
                    <svg class="w-4 h-4 mr-2 text-blue-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" fill="currentColor">
                        <path d="M16.006 3C9.375 3 3.995 8.38 3.995 15.01c0 2.648.695 5.13 2.021 7.338l-2.13 6.233 6.398-2.1A12.9 12.9 0 0016 27.01c6.632 0 12.01-5.38 12.01-12.003C28.01 8.38 22.63 3 16.006 3zm0 22.014a10.01 10.01 0 01-5.108-1.39l-.365-.218-3.796 1.243 1.243-3.688-.238-.379a9.978 9.978 0 01-1.565-5.26c0-5.52 4.488-10.007 10.016-10.007 5.52 0 10.007 4.487 10.007 10.007 0 5.528-4.487 10.002-10.007 10.002zm5.584-7.423c-.303-.15-1.787-.88-2.065-.983-.276-.102-.478-.15-.68.15-.203.3-.78.983-.956 1.187-.177.203-.352.228-.654.076-.303-.15-1.278-.47-2.434-1.5-.9-.8-1.51-1.788-1.686-2.09-.177-.3-.02-.46.133-.61.137-.136.303-.354.455-.532.152-.18.203-.3.303-.5.1-.2.05-.38-.025-.532-.077-.15-.68-1.638-.93-2.243-.244-.585-.49-.5-.68-.51l-.58-.01c-.2 0-.526.076-.8.38s-1.05 1.02-1.05 2.48 1.077 2.88 1.225 3.078c.15.2 2.12 3.23 5.137 4.527.717.31 1.276.494 1.712.63.718.228 1.372.197 1.886.12.575-.085 1.787-.73 2.04-1.437.25-.707.25-1.313.177-1.437-.074-.125-.276-.2-.58-.35z"/>
                    </svg>
                    Contactar central
                </button>

                <button type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-primary-500 bg-white border border-primary-500 rounded-lg hover:bg-[#e6f6fc] focus:ring-2 focus:ring-[#169FDB] focus:ring-offset-1">
                    <svg class="w-4 h-4 mr-2 text-[#169FDB]" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2.003 5.884a1 1 0 01.57-1.11l2.828-1.26a1 1 0 011.185.297l1.516 1.89a1 1 0 01-.105 1.382L6.387 8.21a11.03 11.03 0 005.404 5.404l1.127-1.61a1 1 0 011.382-.105l1.89 1.516a1 1 0 01.297 1.185l-1.26 2.828a1 1 0 01-1.11.57c-3.213-.56-6.173-2.345-8.543-4.714s-4.154-5.33-4.714-8.543z" />
                    </svg>
                    Llamar central
                </button>

                <a href="{{ \App\Filament\Pages\AgregarVehiculo::getUrl() }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-primary-500 bg-white border border-primary-500 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                    <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                    </svg>
                    Agregar vehículo
                </a>
            </div>
        </div>
    </div>

    {{-- Pestañas de marcas (responsive) --}}
    <div class="mb-6">
        {{-- Vista móvil: pestañas scrollables horizontalmente --}}
        <div class="lg:hidden relative">
            <div class="overflow-x-auto scrollbar-hide pb-1">
                <div class="flex whitespace-nowrap border-b border-gray-200 min-w-full">
                    @foreach ($marcasInfo as $codigo => $nombre)
                        <button
                            wire:click="selectTab('{{ $codigo }}')"
                            class="px-4 py-2 text-sm font-medium flex-shrink-0 {{ $activeTab === $codigo ? 'border-b-2 border-primary-500 text-primary-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                        >
                            {{ $nombre }}
                            @if(isset($marcaCounts[$codigo]) && $marcaCounts[$codigo] > 0)
                                <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-gray-100 text-gray-700">{{ $marcaCounts[$codigo] }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>
            </div>
            {{-- Indicadores de scroll --}}
            <div class="absolute left-0 top-0 bottom-0 w-8 bg-gradient-to-r from-white to-transparent pointer-events-none"></div>
            <div class="absolute right-0 top-0 bottom-0 w-8 bg-gradient-to-l from-white to-transparent pointer-events-none"></div>
        </div>

        {{-- Vista desktop: pestañas normales --}}
        <div class="hidden lg:block">
            <div class="flex border-b border-gray-200">
                @foreach ($marcasInfo as $codigo => $nombre)
                    <button
                        wire:click="selectTab('{{ $codigo }}')"
                        class="px-6 py-3 text-sm font-medium {{ $activeTab === $codigo ? 'border-b-2 border-primary-500 text-primary-600' : 'text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        {{ $nombre }}
                        @if(isset($marcaCounts[$codigo]) && $marcaCounts[$codigo] > 0)
                            <span class="ml-2 px-2 py-0.5 text-xs rounded-full bg-gray-100 text-gray-700">{{ $marcaCounts[$codigo] }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Contenido de las Marcas --}}
    <div>
        @foreach ($marcasInfo as $codigo => $nombre)
            {{-- El contenido de cada marca --}}
            <div
                wire:key="tab-content-{{ $codigo }}"
            >
                {{-- Verificación si el contenido de la marca tiene vehículos --}}
                @if($activeTab === $codigo)
                    @php
                        $currentPaginator = $this->vehiculosPaginados;
                        $marcaActual = $this->marcasInfo[$codigo] ?? 'Desconocida';
                    @endphp

                    @if($currentPaginator && $currentPaginator->total() > 0)

                    {{-- Vista móvil: tarjetas --}}
                        <div class="lg:hidden space-y-4 mb-4">
                            @foreach ($currentPaginator->items() as $vehiculo)
                                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden">
                                    <div class="p-4">
                                        <div class="flex items-center mb-3">
                                            <div class="h-16 w-20 rounded overflow-hidden bg-gray-100 dark:bg-gray-700 flex items-center justify-center border border-gray-200 dark:border-gray-600 mr-3">
                                                @if(isset($vehiculo['foto_url']))
                                                    <img src="{{ $vehiculo['foto_url'] }}" alt="Vehículo {{ $vehiculo['modver'] }}" class="h-full w-full object-cover">
                                                @elseif(Str::contains($vehiculo['modver'], 'COROLLA CROSS'))
                                                    <img src="{{ asset('images/CorollaCross.jpeg') }}" alt="Vehículo {{ $vehiculo['modver'] }}" class="h-full w-full object-cover">
                                                @elseif(Str::contains($vehiculo['modver'], 'ETIOS'))
                                                    <img src="{{ asset('images/Etios.jpeg') }}" alt="Vehículo {{ $vehiculo['modver'] }}" class="h-full w-full object-cover">
                                                @elseif(Str::contains($vehiculo['modver'], 'YARIS CROSS'))
                                                    <img src="{{ asset('images/YarisCross.jpeg') }}" alt="Vehículo {{ $vehiculo['modver'] }}" class="h-full w-full object-cover">
                                                @else
                                                    <div class="text-center p-1">
                                                        <img src="{{ asset('images/no-image.svg') }}" alt="Sin imagen" class="h-10 w-10 mx-auto">
                                                        <span class="text-xs text-gray-500 dark:text-gray-400 block">Sin foto</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div class="px-4">
                                                <h3 class="text-base font-bold text-gray-900 dark:text-white">
                                                    {{ $vehiculo['modver'] }}
                                                </h3>
                                                <div class="text-sm font-medium text-gray-600 dark:text-gray-300">
                                                    Placa: {{ $vehiculo['numpla'] ?? 'No disponible' }}
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-300">
                                                    Año: {{ $vehiculo['aniomod'] }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-cols-3 justify-between gap-2 mt-3">
                                            <a href="{{ \App\Filament\Pages\DetalleVehiculo::getUrl(['vehiculoId' => $vehiculo['vhclie'] ?? '']) }}" class="gap-1 inline-flex items-center justify-center px-2 py-1.5 text-xs font-medium text-primary-500 bg-white border border-primary-500 rounded hover:bg-gray-50 focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                                                <svg class="w-3 h-3 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                                </svg>
                                                Ver detalle
                                            </a>
                                            <button
                                                wire:click="eliminarVehiculo('{{ $vehiculo['vhclie'] }}')"
                                                onclick="return confirm('¿Estás seguro de que deseas retirar este vehículo?')"
                                                class="gap-1 inline-flex items-center justify-center px-2 py-1.5 text-xs font-medium text-primary-500 bg-white border border-primary-500 rounded hover:bg-danger-50 focus:ring-2 focus:ring-danger-500 focus:ring-offset-1">
                                                <svg class="w-3 h-3 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                                                </svg>
                                                Retirar
                                            </button>
                                            <a href="{{ route('filament.admin.pages.agendar-cita', ['vehiculoId' => $vehiculo['vhclie'] ?? '']) }}" class="gap-1 inline-flex items-center justify-center px-2 py-1.5 text-xs font-medium text-white bg-primary-600 border border-transparent rounded hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                                                <svg class="w-3 h-3 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                                                </svg>
                                                Agendar cita
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach

                            <div class="px-4 py-3 flex justify-center">
                                {{ $currentPaginator->links('vendor.pagination.default') }}
                            </div>
                        </div>

                        {{-- Vista desktop: tabla --}}
                        <div class="hidden lg:block overflow-x-auto bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm mb-4 w-full">
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
                                        <th scope="col" class="px-6 py-4 text-center text-xs font-medium text-white uppercase tracking-wider font-semibold">
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
                                                    @elseif(Str::contains($vehiculo['modver'], 'COROLLA CROSS'))
                                                        <img src="{{ asset('images/CorollaCross.jpeg') }}" alt="Vehículo {{ $vehiculo['modver'] }}" class="h-full w-full object-cover">
                                                    @elseif(Str::contains($vehiculo['modver'], 'ETIOS'))
                                                        <img src="{{ asset('images/Etios.jpeg') }}" alt="Vehículo {{ $vehiculo['modver'] }}" class="h-full w-full object-cover">
                                                    @elseif(Str::contains($vehiculo['modver'], 'YARIS CROSS'))
                                                        <img src="{{ asset('images/YarisCross.jpeg') }}" alt="Vehículo {{ $vehiculo['modver'] }}" class="h-full w-full object-cover">
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
                                                    {{ $vehiculo['numpla'] ?? 'No disponible' }}
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
                                                <!-- Botones de acción -->
                                                <a href="{{ \App\Filament\Pages\DetalleVehiculo::getUrl(['vehiculoId' => $vehiculo['vhclie'] ?? '']) }}" class="inline-flex items-center px-2 py-1 text-sm font-medium text-primary-500 bg-white rounded hover:bg-gray-50 focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" />
                                                    </svg>
                                                    Ver detalle
                                                </a>
                                                <button
                                                    wire:click="eliminarVehiculo('{{ $vehiculo['vhclie'] }}')"
                                                    onclick="return confirm('¿Estás seguro de que deseas retirar este vehículo?')"
                                                    class="inline-flex items-center px-2 py-1 text-sm font-medium text-primary-500 bg-white rounded hover:bg-danger-50 focus:ring-2 focus:ring-danger-500 focus:ring-offset-1">
                                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" />
                                                    </svg>
                                                    Retirar
                                                </button>
                                                <a href="{{ route('filament.admin.pages.agendar-cita', ['vehiculoId' => $vehiculo['vhclie'] ?? '']) }}" class="inline-flex items-center px-2 py-1 text-sm font-medium text-white bg-primary-600 border border-transparent rounded hover:bg-primary-700 focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                                                    <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" />
                                                    </svg>
                                                    Agendar cita
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>

                            <div class="px-6 py-4 flex justify-end">
                                {{ $currentPaginator->links('vendor.pagination.default') }}
                            </div>
                        </div>
                    @else
                        {{-- Mensaje de no hay vehículos (responsive) --}}
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm py-6 px-4 mb-4 w-full text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400 text-lg font-medium mb-2">No se encontraron vehículos</p>
                            <p class="text-gray-500 dark:text-gray-400 text-sm max-w-md mx-auto">No hay vehículos registrados para la marca {{ $marcaActual }}.</p>
                            <div class="mt-4">
                                <a href="{{ \App\Filament\Pages\AgregarVehiculo::getUrl() }}" class="inline-flex items-center px-4 py-2 text-sm font-medium text-primary-500 bg-white border border-primary-500 rounded-lg hover:bg-gray-50 focus:ring-2 focus:ring-primary-500 focus:ring-offset-1">
                                    <svg class="w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                        <path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" />
                                    </svg>
                                    Agregar un vehículo
                                </a>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
