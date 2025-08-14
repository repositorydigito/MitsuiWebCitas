<x-filament-panels::page>
    <div class="bg-white rounded-lg shadow-sm p-6">
        <!-- Título de la página -->
        <div class="mb-4 text-center">
            <h1 class="text-2xl font-bold text-gray-900">Carga de Campañas</h1>
            <p class="text-gray-600 mt-1">Sube un archivo Excel con las campañas que deseas cargar</p>
        </div>

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

        <!-- Paso 1: Subir archivo Excel e imágenes -->
        @if ($pasoActual === 1)
            <div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <!-- Subir archivo Excel -->
                    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Archivo Excel</h2>
                        <div class="flex flex-col space-y-4">
                            <div class="flex items-center w-full">
                                <label for="archivoExcel" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-4">
                                        <svg class="w-8 h-8 mb-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Haz clic para subir</span> o arrastra y suelta</p>
                                        <p class="text-xs text-gray-500">Excel (.xlsx, .xls)</p>
                                    </div>
                                    <input
                                        id="archivoExcel"
                                        type="file"
                                        wire:model="archivoExcel"
                                        accept=".xlsx,.xls"
                                        class="hidden"
                                    />
                                </label>
                            </div>

                            @error('archivoExcel')
                                <span class="text-primary-500 text-sm">{{ $message }}</span>
                            @enderror

                            <div class="flex items-center justify-between bg-gray-100 p-3 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="w-6 h-6 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span class="text-sm text-gray-700 truncate max-w-xs">{{ $nombreArchivo }}</span>
                                </div>
                                @if($archivoExcel)
                                    <button
                                        type="button"
                                        wire:click="$set('archivoExcel', null)"
                                        class="text-red-500 hover:text-red-700"
                                        title="Eliminar archivo"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                @endif
                            </div>

                            <button
                                type="button"
                                wire:click="descargarPlantilla"
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-primary-600 bg-white border border-primary-600 rounded-lg hover:bg-primary-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                                Descargar plantilla
                            </button>
                        </div>
                    </div>

                    <!-- Subir imágenes -->
                    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Imágenes</h2>
                        <div class="flex flex-col space-y-4">
                            <div class="flex items-center w-full">
                                <label for="imagenes" class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-300 rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-4">
                                        <svg class="w-8 h-8 mb-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Haz clic para subir</span> o arrastra y suelta</p>
                                        <p class="text-xs text-gray-500">PNG, JPG o JPEG (MAX. 10MB)</p>
                                    </div>
                                    <input
                                        id="imagenes"
                                        type="file"
                                        wire:model="imagenes"
                                        accept="image/*"
                                        multiple
                                        class="hidden"
                                    />
                                </label>
                            </div>

                            @error('imagenes')
                                <span class="text-primary-500 text-sm">{{ $message }}</span>
                            @enderror

                            @if(count($imagenes) > 0)
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-2">
                                    @foreach($imagenes as $index => $imagen)
                                        <div class="relative group">
                                            <img
                                                src="{{ $imagen->temporaryUrl() }}"
                                                alt="Vista previa"
                                                class="h-24 w-full object-cover rounded-lg border border-gray-300"
                                            >
                                            <div class="absolute inset-0 flex items-start justify-start group-hover:opacity-100 transition-opacity rounded-lg">
                                                <button
                                                    type="button"
                                                    wire:click="removeImage({{ $index }})"
                                                    class="text-primary-500"
                                                    title="Eliminar imagen"
                                                >
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                            <div class="text-center text-primary-500 text-xs mt-1">
                                                Imagen {{ $index + 1 }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <p class="text-sm text-gray-500">
                                    <strong>Importante:</strong> El orden de las imágenes debe coincidir con el orden de las campañas en el archivo Excel.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="flex justify-between mt-6 pt-4 border-t border-gray-200">
                    <button
                        type="button"
                        wire:click="volverACampanas"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Volver
                    </button>
                    <button
                        type="button"
                        wire:click="procesarArchivo"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        Continuar
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <!-- Paso 2: Revisar datos y confirmar -->
        @if ($pasoActual === 2)
            <div>
                <h2 class="text-xl font-medium text-gray-900 mb-6">Resumen de campañas a cargar</h2>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-primary-600">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    #
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Código Campaña
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Campaña
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Fecha Inicio
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Fecha Fin
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Local
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Marca
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Estado
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Imagen
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($campanasProcesadas as $index => $campana)
                                <tr class="{{ isset($errores[$index + 1]) && count($errores[$index + 1]) > 0 ? 'bg-red-50' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $index + 1 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $campana['codigo'] }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                        {{ $campana['nombre'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $campana['fecha_inicio'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $campana['fecha_fin'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $campana['local'] }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($campana['marca'] === 'Toyota') bg-red-100 text-red-800
                                            @elseif($campana['marca'] === 'Lexus') bg-blue-100 text-blue-800
                                            @else bg-orange-100 text-orange-800
                                            @endif">
                                            {{ $campana['marca'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $campana['estado'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $campana['estado'] ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        @if($campana['imagen'] !== null && isset($imagenes[$campana['imagen']]))
                                            <img
                                                src="{{ $imagenes[$campana['imagen']]->temporaryUrl() }}"
                                                alt="Imagen de campaña"
                                                class="h-10 w-10 object-cover rounded-lg"
                                            >
                                        @else
                                            <span class="text-red-500">Sin imagen</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-between mt-6 pt-4 border-t border-gray-200">
                    <button
                        type="button"
                        wire:click="volverPasoAnterior"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Volver
                    </button>
                    <button
                        type="button"
                        wire:click="guardarCampanas"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        Guardar campañas
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
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
                <h2 class="text-2xl font-bold text-gray-900 mb-2">¡Campañas cargadas con éxito!</h2>
                <p class="text-gray-600 mb-4">Se han cargado {{ $campanasAgregadas }} campañas correctamente.</p>

                <div class="flex justify-center">
                    <button
                        type="button"
                        wire:click="volverACampanas"
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-primary-600 border border-transparent rounded-lg hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Volver a campañas
                    </button>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
