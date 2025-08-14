<?php

namespace App\Console\Commands;

use App\Jobs\UpdateAppointmentPackageIdJob;
use App\Models\Appointment;
use App\Models\Vehicle;
use App\Services\PackageIdCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateAppointmentPackageIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'appointments:update-package-ids 
                            {--appointment-id= : Actualizar solo una cita específica por ID}
                            {--dry-run : Solo mostrar qué se actualizaría sin hacer cambios}
                            {--force : Forzar actualización incluso si ya tiene package_id}
                            {--sync : Ejecutar de forma síncrona en lugar de usar jobs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Actualizar package_id de citas basándose en tipo_valor_trabajo del vehículo y tipo de mantenimiento';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📦 Actualizando package_id de citas');
        $this->line('');

        $appointmentId = $this->option('appointment-id');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $sync = $this->option('sync');

        if ($dryRun) {
            $this->warn('⚠️  MODO DRY-RUN: No se realizarán cambios reales');
            $this->line('');
        }

        try {
            if ($appointmentId) {
                return $this->handleSingleAppointment($appointmentId, $dryRun, $force, $sync);
            } else {
                return $this->handleBulkUpdate($dryRun, $force, $sync);
            }

        } catch (\Exception $e) {
            $this->error('❌ Error ejecutando comando: ' . $e->getMessage());
            Log::error('Error en comando update-package-ids', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Manejar actualización de una cita específica
     */
    protected function handleSingleAppointment(int $appointmentId, bool $dryRun, bool $force, bool $sync): int
    {
        $appointment = Appointment::with('vehicle')->find($appointmentId);

        if (!$appointment) {
            $this->error("❌ No se encontró cita con ID: {$appointmentId}");
            return 1;
        }

        $this->info("🔍 Procesando cita específica: {$appointmentId}");
        $this->displayAppointmentInfo($appointment);

        if ($dryRun) {
            $result = $this->simulatePackageIdCalculation($appointment, $force);
            $this->displayDryRunResult($result);
            return 0;
        }

        if ($sync) {
            // Ejecutar de forma síncrona
            $result = $this->processAppointmentSync($appointment, $force);
            $this->displaySyncResult($result);
        } else {
            // Despachar job
            UpdateAppointmentPackageIdJob::dispatch($appointmentId, $force);
            $this->info('✅ Job despachado para procesar la cita');
        }

        return 0;
    }

    /**
     * Manejar actualización masiva
     */
    protected function handleBulkUpdate(bool $dryRun, bool $force, bool $sync): int
    {
        // Obtener estadísticas
        $stats = $this->getUpdateStats($force);
        
        $this->info('📊 Estadísticas de citas:');
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Total de citas', $stats['total']],
                ['Con maintenance_type', $stats['with_maintenance_type']],
                ['Con vehículo Toyota/Lexus/Hino', $stats['toyota_lexus_hino']],
                ['Sin package_id', $stats['without_package_id']],
                ['A procesar', $stats['to_process']],
            ]
        );

        if ($stats['to_process'] === 0) {
            $this->info('ℹ️  No hay citas que procesar');
            return 0;
        }

        if (!$force && !$this->confirm("¿Procesar {$stats['to_process']} citas?")) {
            $this->info('Operación cancelada');
            return 0;
        }

        if ($dryRun) {
            return $this->handleDryRunBulk($force);
        }

        if ($sync) {
            return $this->handleSyncBulk($force);
        } else {
            // Despachar job para procesamiento masivo
            UpdateAppointmentPackageIdJob::dispatch(null, $force);
            $this->info('✅ Job despachado para procesamiento masivo');
            $this->info('💡 Monitorea el progreso con: php artisan queue:work');
        }

        return 0;
    }

    /**
     * Obtener estadísticas de actualización
     */
    protected function getUpdateStats(bool $force): array
    {
        $total = Appointment::count();
        $withMaintenanceType = Appointment::whereNotNull('maintenance_type')->count();
        
        $toyotaLexusHino = Appointment::whereHas('vehicle', function ($q) {
            $q->whereIn('brand_code', ['Z01', 'Z02', 'Z03'])
              ->whereNotNull('tipo_valor_trabajo');
        })->count();

        $withoutPackageId = Appointment::where(function($q) {
                $q->whereNull('package_id')
                  ->orWhere('package_id', '');
            })
            ->whereNotNull('maintenance_type')
            ->whereHas('vehicle', function ($q) {
                $q->whereIn('brand_code', ['Z01', 'Z02', 'Z03'])
                  ->whereNotNull('tipo_valor_trabajo');
            })->count();

        $toProcess = $force ? $toyotaLexusHino : $withoutPackageId;

        return [
            'total' => $total,
            'with_maintenance_type' => $withMaintenanceType,
            'toyota_lexus_hino' => $toyotaLexusHino,
            'without_package_id' => $withoutPackageId,
            'to_process' => $toProcess,
        ];
    }

    /**
     * Mostrar información de una cita
     */
    protected function displayAppointmentInfo(Appointment $appointment): void
    {
        $this->line("   - ID: {$appointment->id}");
        $this->line("   - Número: {$appointment->appointment_number}");
        $this->line("   - Tipo mantenimiento: " . ($appointment->maintenance_type ?? 'NO DEFINIDO'));
        $this->line("   - Package ID actual: " . ($appointment->package_id ?? 'NO DEFINIDO'));
        
        if ($appointment->vehicle) {
            $this->line("   - Vehículo: {$appointment->vehicle->license_plate} ({$appointment->vehicle->model})");
            $this->line("   - Marca: {$appointment->vehicle->brand_name} ({$appointment->vehicle->brand_code})");
            $this->line("   - Tipo valor trabajo: " . ($appointment->vehicle->tipo_valor_trabajo ?? 'NO DEFINIDO'));
        } else {
            $this->line("   - Vehículo: NO ASOCIADO");
        }
        $this->line('');
    }

    /**
     * Simular cálculo de package_id para dry-run
     */
    protected function simulatePackageIdCalculation(Appointment $appointment, bool $force): array
    {
        if (!$force && !empty($appointment->package_id)) {
            return [
                'would_update' => false,
                'reason' => 'already_has_package_id',
                'current_package_id' => $appointment->package_id
            ];
        }

        if (!$appointment->vehicle) {
            return [
                'would_update' => false,
                'reason' => 'no_vehicle'
            ];
        }

        $vehicle = $appointment->vehicle;
        $calculator = app(PackageIdCalculator::class);
        $newPackageId = $calculator->calculate($vehicle, $appointment->maintenance_type);

        return [
            'would_update' => !empty($newPackageId),
            'new_package_id' => $newPackageId,
            'reason' => empty($newPackageId) ? 'calculation_failed' : 'success'
        ];
    }

    /**
     * Mostrar resultado de dry-run
     */
    protected function displayDryRunResult(array $result): void
    {
        if ($result['would_update']) {
            $this->line("   ✅ SE ACTUALIZARÍA: {$result['new_package_id']}");
        } else {
            $reason = match ($result['reason']) {
                'already_has_package_id' => "Ya tiene package_id: {$result['current_package_id']}",
                'no_vehicle' => 'Sin vehículo asociado',
                'calculation_failed' => 'No se pudo calcular package_id',
                default => 'Razón desconocida'
            };
            $this->line("   ℹ️  NO SE ACTUALIZARÍA: {$reason}");
        }
    }

    /**
     * Procesar cita de forma síncrona
     */
    protected function processAppointmentSync(Appointment $appointment, bool $force): array
    {
        // Lógica similar al job pero ejecutada directamente
        if (!$force && !empty($appointment->package_id)) {
            return ['updated' => false, 'reason' => 'already_has_package_id'];
        }

        if (!$appointment->vehicle) {
            return ['updated' => false, 'reason' => 'no_vehicle'];
        }

        $calculator = app(PackageIdCalculator::class);
        $newPackageId = $calculator->calculate($appointment->vehicle, $appointment->maintenance_type);

        if (!$newPackageId) {
            return ['updated' => false, 'reason' => 'calculation_failed'];
        }

        $appointment->package_id = $newPackageId;
        $appointment->save();

        return ['updated' => true, 'package_id' => $newPackageId];
    }

    /**
     * Mostrar resultado de procesamiento síncrono
     */
    protected function displaySyncResult(array $result): void
    {
        if ($result['updated']) {
            $this->line("   ✅ ACTUALIZADO: {$result['package_id']}");
        } else {
            $reason = match ($result['reason']) {
                'already_has_package_id' => 'Ya tiene package_id',
                'no_vehicle' => 'Sin vehículo asociado',
                'calculation_failed' => 'No se pudo calcular package_id',
                default => 'Razón desconocida'
            };
            $this->line("   ℹ️  NO ACTUALIZADO: {$reason}");
        }
    }

    /**
     * Manejar dry-run masivo
     */
    protected function handleDryRunBulk(bool $force): int
    {
        $query = Appointment::with('vehicle')
            ->whereNotNull('maintenance_type')
            ->whereHas('vehicle', function ($q) {
                $q->whereNotNull('tipo_valor_trabajo')
                  ->whereIn('brand_code', ['Z01', 'Z02', 'Z03']);
            });

        if (!$force) {
            $query->where(function($q) {
                $q->whereNull('package_id')
                  ->orWhere('package_id', '');
            });
        }

        $appointments = $query->limit(10)->get(); // Mostrar solo primeras 10

        $this->info('📋 Primeras 10 citas que se procesarían:');
        $this->line('');

        foreach ($appointments as $appointment) {
            $this->info("Cita #{$appointment->id}:");
            $this->displayAppointmentInfo($appointment);
            
            $result = $this->simulatePackageIdCalculation($appointment, $force);
            $this->displayDryRunResult($result);
            $this->line('');
        }

        return 0;
    }

    /**
     * Manejar procesamiento síncrono masivo
     */
    protected function handleSyncBulk(bool $force): int
    {
        $query = Appointment::with('vehicle')
            ->whereNotNull('maintenance_type')
            ->whereHas('vehicle', function ($q) {
                $q->whereNotNull('tipo_valor_trabajo')
                  ->whereIn('brand_code', ['Z01', 'Z02', 'Z03']);
            });

        if (!$force) {
            $query->where(function($q) {
                $q->whereNull('package_id')
                  ->orWhere('package_id', '');
            });
        }

        $appointments = $query->get();
        $progressBar = $this->output->createProgressBar($appointments->count());
        $progressBar->start();

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($appointments as $appointment) {
            try {
                $result = $this->processAppointmentSync($appointment, $force);
                
                if ($result['updated']) {
                    $updated++;
                } else {
                    $skipped++;
                }

            } catch (\Exception $e) {
                $errors++;
                Log::error('Error procesando cita', [
                    'appointment_id' => $appointment->id,
                    'error' => $e->getMessage()
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line('');
        $this->line('');

        $this->info('📈 RESUMEN FINAL:');
        $this->table(
            ['Métrica', 'Cantidad'],
            [
                ['Actualizadas', $updated],
                ['Omitidas', $skipped],
                ['Errores', $errors],
            ]
        );

        return 0;
    }


}
