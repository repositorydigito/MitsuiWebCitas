<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class DetalleVehiculo extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Detalle del Vehículo';

    protected static ?string $title = 'Detalle del vehículo';

    protected static string $view = 'filament.pages.detalle-vehiculo';

    // Ocultar de la navegación principal ya que se accederá desde la página de vehículos
    protected static bool $shouldRegisterNavigation = false;

    // Datos del vehículo
    public ?array $vehiculo = [
        'modelo' => 'RAV4 LIMITED',
        'kilometraje' => '12,530 Km',
        'placa' => 'DEF-456',
    ];

    // Datos de mantenimiento
    public array $mantenimiento = [
        'ultimo' => '25,000 Km',
        'fecha' => 'Noviembre 2024',
        'vencimiento' => '30/06/2025',
        'disponibles' => [
            '1 Servicio 40,000 Km',
            '1 Servicio 20,000 Km'
        ]
    ];

    // Citas agendadas
    public array $citasAgendadas = [
        [
            'servicio' => 'Mantenimiento 200,000 Km',
            'estado' => 'confirmada', // confirmada, en_trabajo, trabajo_concluido, entregado
            'probable_entrega' => '-',
            'hora' => '-',
            'sede' => '-',
            'asesor' => '-',
            'whatsapp' => '-',
            'correo' => '-'
        ]
    ];

    // Historial de servicios
    public Collection $historialServicios;
    public int $currentPage = 1;
    public int $perPage = 10;

    public function mount($vehiculoId = null): void
    {
        // En una implementación real, aquí cargaríamos los datos del vehículo
        // basados en el ID recibido
        if ($vehiculoId) {
            Log::info("[DetalleVehiculo] Cargando datos para vehículo ID: {$vehiculoId}");
            // Simulamos cargar datos del vehículo
            // En un caso real, aquí consultaríamos a la API o base de datos
        }

        // Inicializar el historial de servicios con datos de ejemplo
        $this->inicializarHistorialServicios();
    }

    protected function inicializarHistorialServicios(): void
    {
        // Crear datos de ejemplo para el historial de servicios
        $servicios = [];

        for ($i = 0; $i < 1; $i++) {
            $sedes = ['La Molina', 'Canadá', 'Lima'];
            $servicios[] = [
                'servicio' => 'Mantenimiento 15,000 Km',
                'fecha' => '30/10/2023',
                'sede' => $sedes[array_rand($sedes)],
                'asesor' => 'Luis Gonzales',
                'tipo_pago' => 'Contado'
            ];
        }

        $this->historialServicios = collect($servicios);
    }

    public function getHistorialPaginadoProperty(): LengthAwarePaginator
    {
        $page = request()->query('page', $this->currentPage);

        return new LengthAwarePaginator(
            $this->historialServicios->forPage($page, $this->perPage),
            $this->historialServicios->count(),
            $this->perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    // Método para volver a la página de vehículos
    public function volver(): void
    {
        $this->redirect(Vehiculos::getUrl());
    }

    // Método para ir a agendar cita
    public function agendarCita(): void
    {
        // Asegurarse de que la placa no tenga espacios adicionales
        $placa = $this->vehiculo['placa'] ?? '';
        $placa = trim(str_replace(' ', '', $placa));

        // Registrar la placa original y la placa limpia
        Log::info("[DetalleVehiculo] Datos del vehículo para agendar cita:", [
            'placa_original' => $this->vehiculo['placa'] ?? 'No disponible',
            'placa_limpia' => $placa,
            'modelo' => $this->vehiculo['modelo'] ?? 'No disponible'
        ]);

        // Redirigir a la página de agendar cita con la placa como parámetro
        $this->redirect(AgendarCita::getUrl(['vehiculoId' => $placa]));
    }
}
