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
                        wire:model="rangoFechas"
                        value="{{ $rangoFechas }}"
                        placeholder="Seleccionar rango"
                        class="w-auto border-gray-300 rounded-lg shadow-sm focus:border-primary-500 focus:ring-primary-500 datepicker"
                        style="min-width: 220px;"
                        autocomplete="off"
                    >
                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                    </div>
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
                <h3 class="text-sm font-semibold text-gray-700 mb-2">CITAS NO SHOW</h3>
                <p class="font-bold text-primary-600" style="font-size: 2rem !important;">{{ $porcentajeNoShow }}%</p>
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
            <div class="h-32 flex items-center justify-center">
                <canvas id="gaugeMantenimiento" class="max-h-full"></canvas>
            </div>
        </div>

        <div class="bg-white p-4 rounded-lg shadow border border-gray-200">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-sm font-semibold text-gray-700">CITAS POR MANT. PREPAGADOS</h3>
                <span class="text-sm text-gray-500">({{ $citasMantenimientoPrepagados }})</span>
            </div>
            <br>
            <div class="h-32 flex items-center justify-center">
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
            <div class="h-64">
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
        // Inicializar el datepicker cuando el DOM esté listo
        document.addEventListener('DOMContentLoaded', function () {
            initDatepicker();
            initCharts();
        });

        // Reinicializar el datepicker cuando Livewire actualice el DOM
        document.addEventListener('livewire:load', function () {
            Livewire.hook('message.processed', (message, component) => {
                initDatepicker();
                initCharts();
            });
        });

        let flatpickrInstance = null;

        function initDatepicker() {
            const datepickerEl = document.querySelector('.datepicker');

            // Destruir la instancia anterior si existe
            if (flatpickrInstance !== null) {
                flatpickrInstance.destroy();
            }

            if (datepickerEl) {
                flatpickrInstance = flatpickr(datepickerEl, {
                    mode: "range",
                    dateFormat: "d/m/Y",
                    locale: "es",
                    rangeSeparator: " - ",
                    altInput: false,
                    allowInput: true,
                    disableMobile: true,
                    onChange: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length > 0) {
                            window.livewire.find(datepickerEl.closest('[wire\\:id]').getAttribute('wire:id'))
                                .set('rangoFechas', dateStr);
                        }
                    },
                    onClose: function(selectedDates, dateStr, instance) {
                        if (selectedDates.length > 0) {
                            window.livewire.find(datepickerEl.closest('[wire\\:id]').getAttribute('wire:id'))
                                .call('aplicarFiltros');
                        }
                    }
                });

                // Si ya hay un valor en el modelo, establecerlo en el datepicker
                const initialValue = datepickerEl.value;
                if (initialValue) {
                    const dates = initialValue.split(' - ').map(date => date.trim());
                    if (dates.length > 0) {
                        const parsedDates = dates.map(date => {
                            const parts = date.split('/');
                            return new Date(parts[2], parts[1] - 1, parts[0]);
                        });
                        flatpickrInstance.setDate(parsedDates);
                    }
                }
            }
        }

        function initCharts() {
            // Gráfico de gauge para mantenimiento
            const ctxGaugeMantenimiento = document.getElementById('gaugeMantenimiento');
            if (ctxGaugeMantenimiento) {
                new Chart(ctxGaugeMantenimiento, {
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
                        },
                        elements: {
                            center: {
                                text: '{{ $porcentajeMantenimiento }}%',
                                fontStyle: 'Arial',
                                sidePadding: 20,
                                minFontSize: 20,
                                lineHeight: 25
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

                            const text = "{{ $porcentajeMantenimiento }}%";
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
            if (ctxGaugePrepagados) {
                new Chart(ctxGaugePrepagados, {
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

                            const text = "{{ $porcentajePrepagados }}%";
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
            if (ctxCantidadCitas) {
                const labels = @json($datosCantidadCitas['labels'] ?? []);
                const generadas = @json($datosCantidadCitas['generadas'] ?? []);
                const efectivas = @json($datosCantidadCitas['efectivas'] ?? []);

                new Chart(ctxCantidadCitas, {
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
                                order: 2
                            },
                            {
                                label: 'EFECTIVAS',
                                data: efectivas,
                                backgroundColor: '#60a5fa',
                                borderColor: '#60a5fa',
                                borderWidth: 1,
                                order: 2
                            },
                            {
                                label: 'Tendencia',
                                data: generadas,
                                type: 'line',
                                borderColor: '#10b981',
                                borderWidth: 2,
                                pointRadius: 0,
                                fill: false,
                                tension: 0.4,
                                order: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    display: true,
                                    color: '#e5e7eb'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }

            // Gráfico de línea para tiempo promedio
            const ctxTiempoPromedio = document.getElementById('chartTiempoPromedio');
            if (ctxTiempoPromedio) {
                const labels = @json($datosTiempoPromedio['labels'] ?? []);
                const tiempos = @json($datosTiempoPromedio['tiempos'] ?? []);

                new Chart(ctxTiempoPromedio, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Tiempo (min)',
                            data: tiempos,
                            borderColor: '#8b5cf6',
                            backgroundColor: 'rgba(139, 92, 246, 0.1)',
                            borderWidth: 2,
                            pointBackgroundColor: '#8b5cf6',
                            pointRadius: 4,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                grid: {
                                    display: true,
                                    color: '#e5e7eb'
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                position: 'bottom'
                            }
                        }
                    }
                });
            }
        }
    </script>
</x-filament-panels::page>
