<x-filament-panels::page>
    {{-- Filtros superiores --}}
    <div class="mb-2 rounded-lg">
        <div class="flex flex-wrap items-end gap-4">
            {{-- Filtro de local --}}
            <div class="w-auto">
                <label for="local" class="block text-sm font-medium text-primary-600 mb-2">Local</label>
                <div class="relative">
                    <select
                        id="local"
                        wire:model.live="localSeleccionado"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        style="min-width: 160px;"
                    >
                        @foreach ($locales as $codigo => $nombre)
                            <option value="{{ $codigo }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Filtro de fecha --}}
            <div class="w-auto">
                <label for="fecha" class="block text-sm font-medium text-primary-600 mb-2">Fecha</label>
                <div class="relative">
                    <input
                        type="text"
                        id="fecha"
                        wire:model.live="rangoFechas"
                        placeholder="Seleccionar rango"
                        class="w-auto border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 datepicker"
                        style="min-width: 220px;"
                        autocomplete="off"
                        readonly
                    >
                </div>
            </div>

            {{-- Filtro de marca --}}
            <div class="w-auto">
                <label for="marca" class="block text-sm font-medium text-primary-600 mb-2">Marca</label>
                <div class="relative">
                    <select
                        id="marca"
                        wire:model.live="marcaSeleccionada"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        style="min-width: 160px;"
                    >
                        @foreach ($marcas as $marca)
                            <option value="{{ $marca }}">{{ $marca }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Mensaje cuando no hay datos --}}
    @if($citasGeneradas === 0)
        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        No hay datos en el rango seleccionado
                    </h3>
                </div>
            </div>
        </div>
    @endif

    <div class="grid md:grid-cols-2 lg:grid-cols-2 gap-6">
    <!-- Columna izquierda: KPIs en 2 filas -->
    <div class="space-y-6">
        <!-- Primera fila de KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-lg shadow border border-gray-200 text-center">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">CITAS GENERADAS</h3>
                <p class="font-bold text-primary-600" style="font-size: 2rem !important;">{{ $citasGeneradas }}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow border border-gray-200 text-center">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">CITAS EFECTIVAS</h3>
                <p class="font-bold text-primary-600" style="font-size: 2rem !important;">{{ $citasEfectivas }}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow border border-gray-200 text-center">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">% EFECTIVIDAD</h3>
                <p class="font-bold text-primary-600" style="font-size: 2rem !important;">{{ $porcentajeEfectividad }}%</p>
            </div>
        </div>

        <!-- Segunda fila de KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white p-4 rounded-lg shadow border border-gray-200 text-center">
                <h3 class="text-xs font-semibold text-gray-700 mb-2">CITAS DIFERIDAS / REPROGRAMADAS</h3>
                <p class="font-bold text-primary-600" style="font-size: 2rem !important;">{{ $citasDiferidas }}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow border border-gray-200 text-center">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">CITAS CANCELADAS</h3>
                <p class="font-bold text-primary-600" style="font-size: 2rem !important;">{{ $citasCanceladas }}</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow border border-gray-200 text-center">
                <h3 class="text-sm font-semibold text-gray-700 mb-2">% CANCELACIÓN</h3>
                <p class="font-bold text-primary-600" style="font-size: 2rem !important;">{{ $porcentajeCancelacion }}%</p>
            </div>
        </div>
    </div>

    <!-- Columna derecha: Gráficos lado a lado -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-semibold text-gray-700">CITAS POR MANTENIMIENTO</h3>
                <span class="text-sm text-gray-500">({{ $citasMantenimiento }})</span>
            </div>
            <br>
            <div class="h-32 flex items-center justify-center" wire:ignore 
                 x-data="{ porcentaje: {{ $porcentajeMantenimiento }} }"
                 x-init="$watch('porcentaje', value => updateMantenimientoChart(value))">
                <canvas id="gaugeMantenimiento" class="max-h-full"></canvas>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-semibold text-gray-700">CITAS SIN MANTENIMIENTO</h3>
                <span class="text-sm text-gray-500">({{ $citasMantenimientoPrepagados }})</span>
            </div>
            <br>
            <div class="h-32 flex items-center justify-center" wire:ignore
                 x-data="{ porcentaje: {{ $porcentajePrepagados }} }"
                 x-init="$watch('porcentaje', value => updatePrepagadosChart(value))">
                <canvas id="gaugePrepagados" class="max-h-full"></canvas>
            </div>
        </div>
    </div>
</div>




    {{-- Filtros para gráficos inferiores --}}
    <div class="mb-6 bg-blue-50 rounded-lg">
        <div class="flex flex-wrap items-end gap-4">
            {{-- Filtro de local --}}
            <div class="w-auto">
                <label for="local2" class="block text-sm font-medium text-primary-600 mb-2">Local</label>
                <div class="relative">
                    <select
                        id="local2"
                        wire:model.live="localSeleccionadoGraficos"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        style="min-width: 160px;"
                    >
                        @foreach ($locales as $codigo => $nombre)
                            <option value="{{ $codigo }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    </div>
                </div>
            </div>

            {{-- Filtro de marca --}}
            <div class="w-auto">
                <label for="marca2" class="block text-sm font-medium text-primary-600 mb-2">Marca</label>
                <div class="relative">
                    <select
                        id="marca2"
                        wire:model.live="marcaSeleccionadaGraficos"
                        class="w-full border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500"
                        style="min-width: 160px;"
                    >
                        @foreach ($marcas as $marca)
                            <option value="{{ $marca }}">{{ $marca }}</option>
                        @endforeach
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Cuarta fila: Gráficos de línea y barras --}}
    <div class="grid grid-cols-1 mb-6">
        {{-- Gráfico: Cantidad de Citas --}}
        <div class="bg-white p-4 rounded-lg shadow">
            <h3 class="text-sm font-semibold text-gray-700 mb-4">CANTIDAD DE CITAS</h3>
            <div class="h-64" wire:ignore>
                <canvas id="chartCantidadCitas"></canvas>
            </div>
        </div>
    </div>

    {{-- Scripts para el datepicker --}}
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

    {{-- Script para Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // Variables globales para mantener las instancias de los gráficos
        let chartMantenimiento = null;
        let chartPrepagados = null;
        let chartCantidadCitas = null;
        let chartTiempoPromedio = null;
        let flatpickrInstance = null;

        // Función para calcular línea de tendencia usando regresión lineal
        function calculateTrendLine(data) {
            if (!data || data.length < 2) {
                return data; // Si no hay suficientes datos, devolver los originales
            }

            const n = data.length;
            let sumX = 0, sumY = 0, sumXY = 0, sumXX = 0;

            // Calcular sumas necesarias para la regresión lineal
            for (let i = 0; i < n; i++) {
                const x = i; // Posición en el array (0, 1, 2, ...)
                const y = data[i] || 0; // Valor de citas
                
                sumX += x;
                sumY += y;
                sumXY += x * y;
                sumXX += x * x;
            }

            // Calcular pendiente (m) e intercepto (b) de la línea y = mx + b
            const slope = (n * sumXY - sumX * sumY) / (n * sumXX - sumX * sumX);
            const intercept = (sumY - slope * sumX) / n;

            // Generar puntos de la línea de tendencia
            const trendLine = [];
            for (let i = 0; i < n; i++) {
                trendLine.push(Math.max(0, Math.round(slope * i + intercept))); // No valores negativos
            }

            console.log('📈 Línea de tendencia calculada:', {
                slope: slope.toFixed(2),
                intercept: intercept.toFixed(2),
                original: data,
                trend: trendLine
            });

            return trendLine;
        }

        // Inicializar cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function () {
            initDatepicker();
            initCharts();
        });

        // Escuchar eventos de Livewire para actualizar gráficos
        document.addEventListener('livewire:navigated', function () {
            initDatepicker();
            initCharts();
        });

        // Escuchar cuando Livewire actualiza el componente
        document.addEventListener('livewire:update', function () {
            setTimeout(() => {
                initDatepicker(); // Reinicializar datepicker si es necesario
                updateCharts();
            }, 100);
        });

        // Escuchar evento de Livewire para actualizar gráficos
        window.addEventListener('updateCharts', function (event) {
            console.log('📡 Evento updateCharts recibido con datos:', event.detail);
            setTimeout(() => {
                updateChartsWithData(event.detail);
            }, 50);
        });

        // Fallback para versiones anteriores de Livewire
        if (typeof Livewire !== 'undefined') {
            Livewire.hook('message.processed', (message, component) => {
                setTimeout(() => {
                    initDatepicker();
                    updateCharts();
                }, 100);
            });
        }

        // Escuchar cambios específicos en el modelo de Livewire
        document.addEventListener('livewire:updated', function (event) {
            // Solo actualizar el datepicker si cambió el rango de fechas desde el servidor
            if (event.detail && event.detail.name === 'rangoFechas') {
                console.log('📅 Rango de fechas actualizado desde servidor:', event.detail.value);
                setTimeout(() => {
                    updateDatepickerValue();
                }, 50);
            }
        });

        function initDatepicker() {
            const datepickerEl = document.querySelector('.datepicker');

            // Si ya existe una instancia y el elemento es el mismo, no recrear
            if (flatpickrInstance !== null && flatpickrInstance.element === datepickerEl) {
                console.log('📅 Datepicker ya existe, actualizando valor...');
                updateDatepickerValue();
                return;
            }

            // Destruir la instancia anterior si existe
            if (flatpickrInstance !== null) {
                flatpickrInstance.destroy();
                flatpickrInstance = null;
            }

            if (datepickerEl) {
                console.log('📅 Creando nueva instancia de datepicker...');
                flatpickrInstance = flatpickr(datepickerEl, {
                    mode: "range",
                    dateFormat: "d/m/Y",
                    locale: "es",
                    rangeSeparator: " - ",
                    altInput: false,
                    allowInput: true,
                    disableMobile: true,
                    onChange: function(selectedDates, dateStr, instance) {
                        console.log('📅 Rango de fechas cambiado:', dateStr);
                        console.log('📅 Fechas seleccionadas:', selectedDates);
                        
                        // Solo procesar si tenemos un rango completo
                        if (selectedDates.length !== 2) {
                            console.log('⏳ Esperando segunda fecha...');
                            return;
                        }
                        
                        // Forzar el formato correcto si viene con "a"
                        if (dateStr.includes(' a ')) {
                            dateStr = dateStr.replace(' a ', ' - ');
                            console.log('🔧 Formato corregido:', dateStr);
                        }
                        
                        console.log('✅ Rango completo, actualizando Livewire...');
                        
                        // Usar Livewire para actualizar el modelo
                        const livewireComponent = window.Livewire.find(datepickerEl.closest('[wire\\:id]').getAttribute('wire:id'));
                        if (livewireComponent) {
                            // Desactivar temporalmente los eventos para evitar loops
                            datepickerEl.setAttribute('data-updating', 'true');
                            livewireComponent.set('rangoFechas', dateStr);
                        }
                    },
                    onClose: function(selectedDates, dateStr, instance) {
                        console.log('🔒 Datepicker cerrado:', dateStr);
                        // Remover el flag de actualización
                        datepickerEl.removeAttribute('data-updating');
                    }
                });

                // Establecer el valor inicial
                updateDatepickerValue();
            }
        }

        function updateDatepickerValue() {
            const datepickerEl = document.querySelector('.datepicker');
            if (!datepickerEl || !flatpickrInstance) return;

            // No actualizar si estamos en medio de una actualización desde el datepicker
            if (datepickerEl.getAttribute('data-updating') === 'true') {
                console.log('📅 Saltando actualización, datepicker está actualizando...');
                return;
            }

            const currentValue = datepickerEl.value;
            console.log('📅 Actualizando datepicker con valor:', currentValue);

            if (currentValue && currentValue.includes(' - ')) {
                const dates = currentValue.split(' - ').map(date => date.trim());
                if (dates.length === 2) {
                    try {
                        const parsedDates = dates.map(date => {
                            const parts = date.split('/');
                            return new Date(parts[2], parts[1] - 1, parts[0]);
                        });
                        
                        // Verificar si las fechas son diferentes a las actuales
                        const currentDates = flatpickrInstance.selectedDates;
                        const needsUpdate = currentDates.length !== 2 || 
                            currentDates[0].getTime() !== parsedDates[0].getTime() ||
                            currentDates[1].getTime() !== parsedDates[1].getTime();

                        if (needsUpdate) {
                            console.log('📅 Estableciendo fechas en datepicker:', parsedDates);
                            flatpickrInstance.setDate(parsedDates, false); // false = no trigger onChange
                        }
                    } catch (e) {
                        console.error('❌ Error parseando fechas para datepicker:', e);
                    }
                }
            }
        }

        function initCharts() {
            console.log('🚀 Inicializando gráficos...');
            
            // Gráfico de gauge para mantenimiento
            const ctxGaugeMantenimiento = document.getElementById('gaugeMantenimiento');
            if (ctxGaugeMantenimiento && !chartMantenimiento) {
                console.log('📊 Creando gráfico de mantenimiento...');
                chartMantenimiento = new Chart(ctxGaugeMantenimiento, {
                    type: 'doughnut',
                    data: {
                        datasets: [{
                            data: [{{ $porcentajeMantenimiento }}, 100 - {{ $porcentajeMantenimiento }}],
                            backgroundColor: ['#0075BF', '#e5e7eb'],
                            borderWidth: 0,
                            cutout: '80%'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: false
                            }
                        }
                    },
                    plugins: [{
                        id: 'centerText',
                        afterDraw: function(chart) {
                            const width = chart.width;
                            const height = chart.height;
                            const ctx = chart.ctx;

                            ctx.restore();
                            const fontSize = (height / 100).toFixed(2);
                            ctx.font = fontSize + "em sans-serif";
                            ctx.textBaseline = "middle";

                            const text = chart.data.datasets[0].data[0] + "%";
                            const textX = Math.round((width - ctx.measureText(text).width) / 2);
                            const textY = height / 2;

                            ctx.fillStyle = "#000";
                            ctx.fillText(text, textX, textY);
                            ctx.save();
                        }
                    }]
                });
            }

            // Gráfico de gauge para prepagados
            const ctxGaugePrepagados = document.getElementById('gaugePrepagados');
            if (ctxGaugePrepagados && !chartPrepagados) {
                console.log('📊 Creando gráfico de prepagados...');
                chartPrepagados = new Chart(ctxGaugePrepagados, {
                    type: 'doughnut',
                    data: {
                        datasets: [{
                            data: [{{ $porcentajePrepagados }}, 100 - {{ $porcentajePrepagados }}],
                            backgroundColor: ['#0075BF', '#e5e7eb'],
                            borderWidth: 0,
                            cutout: '80%'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                enabled: false
                            }
                        }
                    },
                    plugins: [{
                        id: 'centerText',
                        afterDraw: function(chart) {
                            const width = chart.width;
                            const height = chart.height;
                            const ctx = chart.ctx;

                            ctx.restore();
                            const fontSize = (height / 100).toFixed(2);
                            ctx.font = fontSize + "em sans-serif";
                            ctx.textBaseline = "middle";

                            const text = chart.data.datasets[0].data[0] + "%";
                            const textX = Math.round((width - ctx.measureText(text).width) / 2);
                            const textY = height / 2;

                            ctx.fillStyle = "#000";
                            ctx.fillText(text, textX, textY);
                            ctx.save();
                        }
                    }]
                });
            }

            // Gráfico de barras y línea para cantidad de citas
            const ctxCantidadCitas = document.getElementById('chartCantidadCitas');
            if (ctxCantidadCitas && !chartCantidadCitas) {
                console.log('📊 Creando gráfico de cantidad de citas...');
                const labels = @json($datosCantidadCitas['labels'] ?? []);
                const generadas = @json($datosCantidadCitas['generadas'] ?? []);
                const efectivas = @json($datosCantidadCitas['efectivas'] ?? []);

                // Calcular línea de tendencia para citas generadas
                const tendenciaGeneradas = calculateTrendLine(generadas);

                chartCantidadCitas = new Chart(ctxCantidadCitas, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'GENERADAS',
                                data: generadas,
                                backgroundColor: '#3b82f6',
                                borderColor: '#3b82f6',
                                borderWidth: 1,
                                order: 3
                            },
                            {
                                label: 'EFECTIVAS',
                                data: efectivas,
                                backgroundColor: '#60a5fa',
                                borderColor: '#60a5fa',
                                borderWidth: 1,
                                order: 3
                            },
                            {
                                label: 'TENDENCIA',
                                data: tendenciaGeneradas,
                                type: 'line',
                                borderColor: '#f59e0b', // Color ámbar para destacar
                                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                                borderWidth: 3,
                                pointRadius: 5,
                                pointBackgroundColor: '#f59e0b',
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                pointHoverRadius: 7,
                                fill: false,
                                tension: 0.2, // Suavizar un poco la línea
                                order: 1,
                                borderDash: [8, 4], // Línea punteada más elegante
                                pointStyle: 'triangle' // Estilo de punto diferente
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            intersect: false,
                            mode: 'index'
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    display: true,
                                    color: '#e5e7eb'
                                },
                                title: {
                                    display: true,
                                    text: '',
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                },
                                title: {
                                    display: true,
                                    text: 'Período',
                                    font: {
                                        size: 12,
                                        weight: 'bold'
                                    }
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    usePointStyle: true,
                                    padding: 20,
                                    font: {
                                        size: 11
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                                titleColor: '#ffffff',
                                bodyColor: '#ffffff',
                                borderColor: '#374151',
                                borderWidth: 1,
                                cornerRadius: 6,
                                displayColors: true,
                                callbacks: {
                                    title: function(context) {
                                        return 'Período: ' + context[0].label;
                                    },
                                    label: function(context) {
                                        const label = context.dataset.label || '';
                                        const value = context.parsed.y;
                                        
                                        if (label === 'TENDENCIA') {
                                            return `${label}: ${value} (proyección)`;
                                        }
                                        return `${label}: ${value} citas`;
                                    }
                                }
                            }
                        }
                    }
                });
            }
        }

        function updateCharts() {
            // Función de fallback que usa valores estáticos
            updateChartsWithData({
                porcentajeMantenimiento: {{ $porcentajeMantenimiento }},
                porcentajePrepagados: {{ $porcentajePrepagados }},
                datosCantidadCitas: @json($datosCantidadCitas)
            });
        }

        function updateChartsWithData(data) {
            console.log('🔄 Actualizando gráficos con datos:', data);
            
            // Verificar si los elementos del DOM existen
            const ctxMantenimiento = document.getElementById('gaugeMantenimiento');
            const ctxPrepagados = document.getElementById('gaugePrepagados');
            const ctxCantidadCitas = document.getElementById('chartCantidadCitas');

            // Si los elementos no existen, los gráficos fueron destruidos por Livewire
            if (!ctxMantenimiento || !ctxPrepagados || !ctxCantidadCitas) {
                console.log('⚠️ Elementos del DOM no encontrados, reinicializando gráficos...');
                destroyCharts();
                initCharts();
                return;
            }

            // Verificar si los gráficos existen y sus canvas siguen siendo válidos
            if (chartMantenimiento && chartMantenimiento.canvas && chartMantenimiento.canvas.parentNode) {
                try {
                    const newPercentageMantenimiento = data.porcentajeMantenimiento || 0;
                    chartMantenimiento.data.datasets[0].data = [newPercentageMantenimiento, 100 - newPercentageMantenimiento];
                    chartMantenimiento.update('none');
                    console.log('✅ Gráfico de mantenimiento actualizado:', newPercentageMantenimiento + '%');
                } catch (e) {
                    console.log('❌ Error actualizando gráfico de mantenimiento:', e);
                    chartMantenimiento = null;
                }
            }

            if (chartPrepagados && chartPrepagados.canvas && chartPrepagados.canvas.parentNode) {
                try {
                    const newPercentagePrepagados = data.porcentajePrepagados || 0;
                    chartPrepagados.data.datasets[0].data = [newPercentagePrepagados, 100 - newPercentagePrepagados];
                    chartPrepagados.update('none');
                    console.log('✅ Gráfico de prepagados actualizado:', newPercentagePrepagados + '%');
                } catch (e) {
                    console.log('❌ Error actualizando gráfico de prepagados:', e);
                    chartPrepagados = null;
                }
            }

            if (chartCantidadCitas && chartCantidadCitas.canvas && chartCantidadCitas.canvas.parentNode) {
                try {
                    const chartData = data.datosCantidadCitas || { labels: [], generadas: [], efectivas: [] };
                    const newLabels = chartData.labels || [];
                    const newGeneradas = chartData.generadas || [];
                    const newEfectivas = chartData.efectivas || [];

                    // Recalcular línea de tendencia con los nuevos datos
                    const newTendencia = calculateTrendLine(newGeneradas);

                    chartCantidadCitas.data.labels = newLabels;
                    chartCantidadCitas.data.datasets[0].data = newGeneradas;
                    chartCantidadCitas.data.datasets[1].data = newEfectivas;
                    chartCantidadCitas.data.datasets[2].data = newTendencia; // Usar tendencia calculada
                    chartCantidadCitas.update('none');
                    console.log('✅ Gráfico de cantidad de citas actualizado con nueva tendencia');
                } catch (e) {
                    console.log('❌ Error actualizando gráfico de cantidad:', e);
                    chartCantidadCitas = null;
                }
            }

            // Si algún gráfico no existe o falló, recrear todos
            if (!chartMantenimiento || !chartPrepagados || !chartCantidadCitas) {
                console.log('🔄 Recreando gráficos faltantes...');
                initCharts();
            }
        }

        function destroyCharts() {
            console.log('🗑️ Destruyendo gráficos existentes...');
            
            if (chartMantenimiento) {
                chartMantenimiento.destroy();
                chartMantenimiento = null;
            }
            if (chartPrepagados) {
                chartPrepagados.destroy();
                chartPrepagados = null;
            }
            if (chartCantidadCitas) {
                chartCantidadCitas.destroy();
                chartCantidadCitas = null;
            }
            if (chartTiempoPromedio) {
                chartTiempoPromedio.destroy();
                chartTiempoPromedio = null;
            }
        }

        // Funciones específicas para actualizar gráficos individuales
        function updateMantenimientoChart(newPercentage) {
            console.log('🔄 Actualizando gráfico de mantenimiento:', newPercentage + '%');
            if (chartMantenimiento && chartMantenimiento.canvas && chartMantenimiento.canvas.parentNode) {
                try {
                    chartMantenimiento.data.datasets[0].data = [newPercentage, 100 - newPercentage];
                    chartMantenimiento.update('none');
                } catch (e) {
                    console.log('❌ Error actualizando gráfico de mantenimiento:', e);
                }
            }
        }

        function updatePrepagadosChart(newPercentage) {
            console.log('🔄 Actualizando gráfico de prepagados:', newPercentage + '%');
            if (chartPrepagados && chartPrepagados.canvas && chartPrepagados.canvas.parentNode) {
                try {
                    chartPrepagados.data.datasets[0].data = [newPercentage, 100 - newPercentage];
                    chartPrepagados.update('none');
                } catch (e) {
                    console.log('❌ Error actualizando gráfico de prepagados:', e);
                }
            }
        }

        // Función global para actualizar todos los gráficos desde Livewire
        window.updateAllCharts = function(data) {
            console.log('🌐 Actualizando todos los gráficos:', data);
            updateMantenimientoChart(data.porcentajeMantenimiento || 0);
            updatePrepagadosChart(data.porcentajePrepagados || 0);
            
            // Actualizar gráfico de cantidad de citas
            if (chartCantidadCitas && data.datosCantidadCitas) {
                const chartData = data.datosCantidadCitas;
                const generadas = chartData.generadas || [];
                const tendencia = calculateTrendLine(generadas);
                
                chartCantidadCitas.data.labels = chartData.labels || [];
                chartCantidadCitas.data.datasets[0].data = generadas;
                chartCantidadCitas.data.datasets[1].data = chartData.efectivas || [];
                chartCantidadCitas.data.datasets[2].data = tendencia;
                chartCantidadCitas.update('none');
            }
        };

        // Función de debug para probar filtros
        function debugFiltros() {
            console.log('🔍 DEBUG - Estado actual de filtros:');
            console.log('📅 Rango de fechas:', @this.get('rangoFechas'));
            console.log('📅 Fecha inicio:', @this.get('fechaInicio'));
            console.log('📅 Fecha fin:', @this.get('fechaFin'));
            console.log('🏢 Local seleccionado:', @this.get('localSeleccionado'));
            console.log('🚗 Marca seleccionada:', @this.get('marcaSeleccionada'));
            console.log('📊 Citas generadas:', @this.get('citasGeneradas'));
            
            console.log('\n� rRANGOS CON DATOS DISPONIBLES:');
            console.log('   • 13/08/2025 - 16/08/2025 (20 citas)');
            console.log('   • 14/08/2025 - 14/08/2025 (9 citas)');
            console.log('   • 13/08/2025 - 28/08/2025 (36 citas - TODOS LOS DATOS)');
            
            // Forzar aplicación de filtros
            console.log('\n🔄 Forzando aplicación de filtros...');
            @this.call('debugFiltros');
        }

        // Función para probar con un rango específico
        function probarRangoConDatos() {
            console.log('🧪 PROBANDO CON RANGO QUE TIENE DATOS...');
            @this.call('probarRango', '14/08/2025 - 15/08/2025');
        }
    </script>
</x-filament-panels::page>
