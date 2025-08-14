<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use App\Services\C4C\VehicleService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateVehicleTipoValorTrabajo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vehicles:update-tipo-valor-trabajo 
                            {--placa= : Actualizar solo un vehículo específico por placa}
                            {--dry-run : Solo mostrar qué se actualizaría sin hacer cambios}
                            {--force : Forzar actualización incluso si ya tiene valor}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar tipo_valor_trabajo de vehículos consultando C4C webservice';

    protected VehicleService $vehicleService;

    public function __construct(VehicleService $vehicleService)
    {
        parent::__construct();
        $this->vehicleService = $vehicleService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚗 Actualizando tipo_valor_trabajo desde C4C webservice');
        $this->line('');

        $placaEspecifica = $this->option('placa');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se realizarán cambios reales');
            $this->line('');
        }

        try {
            // Determinar qué vehículos procesar
            if ($placaEspecifica) {
                $vehiculos = Vehicle::where('license_plate', $placaEspecifica)
                                  ->where('status', 'active')
                                  ->get();
                
                if ($vehiculos->isEmpty()) {
                    $this->error("❌ No se encontró vehículo con placa: {$placaEspecifica}");
                    return 1;
                }
            } else {
                $vehiculos = Vehicle::where('status', 'active')->get();
            }

            $this->info("📊 Total de vehículos a procesar: " . $vehiculos->count());
            $this->line('');

            $procesados = 0;
            $actualizados = 0;
            $errores = 0;
            $noEncontrados = 0;
            $yaActualizados = 0;

            $progressBar = $this->output->createProgressBar($vehiculos->count());
            $progressBar->start();

            foreach ($vehiculos as $vehiculo) {
                $procesados++;

                // Mostrar información del vehículo actual
                $this->line('');
                $this->info("🔄 Procesando: {$vehiculo->license_plate} ({$vehiculo->model})");
                $this->line("   Tipo actual: " . ($vehiculo->tipo_valor_trabajo ?? 'NO DEFINIDO'));

                try {
                    // Consultar C4C
                    $tipoValorTrabajo = $this->vehicleService->obtenerTipoValorTrabajoPorPlaca($vehiculo->license_plate);

                    if ($tipoValorTrabajo) {
                        // Verificar si necesita actualización
                        $necesitaActualizacion = $force || empty($vehiculo->tipo_valor_trabajo) || 
                                               $vehiculo->tipo_valor_trabajo !== $tipoValorTrabajo;

                        if ($necesitaActualizacion) {
                            if (!$dryRun) {
                                $vehiculo->tipo_valor_trabajo = $tipoValorTrabajo;
                                $vehiculo->save();
                            }

                            $this->line("   ✅ " . ($dryRun ? 'SE ACTUALIZARÍA' : 'ACTUALIZADO') . ": {$tipoValorTrabajo}");
                            $actualizados++;
                        } else {
                            $this->line("   ℹ️  Ya tiene el valor correcto: {$tipoValorTrabajo}");
                            $yaActualizados++;
                        }
                    } else {
                        $this->line("   ⚠️  No encontrado en C4C");
                        $noEncontrados++;
                    }

                } catch (\Exception $e) {
                    $this->line("   ❌ ERROR: " . $e->getMessage());
                    $errores++;
                    
                    Log::error('Error actualizando tipo_valor_trabajo', [
                        'vehiculo_id' => $vehiculo->id,
                        'placa' => $vehiculo->license_plate,
                        'error' => $e->getMessage()
                    ]);
                }

                $progressBar->advance();
                
                // Pausa para no saturar el webservice
                usleep(500000); // 0.5 segundos
            }

            $progressBar->finish();
            $this->line('');
            $this->line('');

            // Mostrar resumen
            $this->info('📈 RESUMEN FINAL:');
            $this->table(
                ['Métrica', 'Cantidad'],
                [
                    ['Total procesados', $procesados],
                    ['Actualizados', $actualizados],
                    ['Ya tenían valor correcto', $yaActualizados],
                    ['No encontrados en C4C', $noEncontrados],
                    ['Errores', $errores],
                ]
            );

            if ($actualizados > 0) {
                $mensaje = $dryRun ? 
                    "ℹ️  Se actualizarían {$actualizados} vehículos (ejecutar sin --dry-run para aplicar)" :
                    "✅ Proceso completado. {$actualizados} vehículos actualizados.";
                $this->info($mensaje);
            } else {
                $this->info('ℹ️  No se requirieron actualizaciones.');
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('💥 ERROR CRÍTICO: ' . $e->getMessage());
            Log::error('Error crítico en UpdateVehicleTipoValorTrabajo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
