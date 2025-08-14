<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Services\C4C\AppointmentSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncAppointmentCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'appointment:sync
                            {appointment_id? : ID específico del appointment (opcional)}
                            {--all : Sincronizar TODAS las citas pendientes}
                            {--force : Forzar sincronización aunque ya esté sincronizado}';

    /**
     * The console command description.
     */
    protected $description = 'Sincronizar appointments con C4C para obtener package_id y c4c_uuid';

    /**
     * Execute the console command.
     */
    public function handle(AppointmentSyncService $syncService)
    {
        $appointmentId = $this->argument('appointment_id');
        $force = $this->option('force');
        $syncAll = (bool) $this->option('all');

        if ($syncAll) {
            return $this->syncAllPendingAppointments($syncService, $force);
        }

        if (!$appointmentId) {
            $this->error("❌ Debes especificar un appointment_id o usar --all");
            $this->info("💡 Ejemplos:");
            $this->info("   php artisan appointment:sync 55");
            $this->info("   php artisan appointment:sync --all");
            return 1;
        }

        return $this->syncSingleAppointment($syncService, $appointmentId, $force);
    }

    /**
     * Sincronizar todas las citas pendientes
     */
    protected function syncAllPendingAppointments(AppointmentSyncService $syncService, bool $force): int
    {
        $this->info("🔄 Sincronizando TODAS las citas pendientes...");

        // Buscar citas pendientes de sincronización
        $query = Appointment::where('is_synced', false);

        if (!$force) {
            $query->whereNull('c4c_uuid');
        }

        $pendingAppointments = $query->get();

        if ($pendingAppointments->isEmpty()) {
            $this->info("✅ No hay citas pendientes de sincronización");
            return 0;
        }

        $this->info("📋 Encontradas {$pendingAppointments->count()} citas pendientes");

        $successCount = 0;
        $errorCount = 0;

        foreach ($pendingAppointments as $appointment) {
            $this->info("🔄 Sincronizando: {$appointment->appointment_number}");

            $result = $this->syncSingleAppointmentInternal($syncService, $appointment, $force);

            if ($result === 0) {
                $successCount++;
            } else {
                $errorCount++;
            }
        }

        $this->info("📊 Resumen de sincronización:");
        $this->info("   ✅ Exitosas: {$successCount}");
        $this->info("   ❌ Errores: {$errorCount}");

        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * Sincronizar una cita específica
     */
    protected function syncSingleAppointment(AppointmentSyncService $syncService, int $appointmentId, bool $force): int
    {
        $this->info("🔄 Sincronizando appointment ID: {$appointmentId}");

        // Buscar el appointment
        $appointment = Appointment::find($appointmentId);

        if (!$appointment) {
            $this->error("❌ Appointment {$appointmentId} no encontrado");
            return 1;
        }

        return $this->syncSingleAppointmentInternal($syncService, $appointment, $force);
    }

    /**
     * Lógica interna para sincronizar una cita
     */
    protected function syncSingleAppointmentInternal(AppointmentSyncService $syncService, Appointment $appointment, bool $force): int
    {

        // Mostrar estado actual
        $this->table(['Campo', 'Valor'], [
            ['ID', $appointment->id],
            ['Number', $appointment->appointment_number],
            ['Brand Code', $appointment->vehicle_brand_code ?? 'NULL'],
            ['Center Code', $appointment->center_code ?? 'NULL'],
            ['Package ID', $appointment->package_id ?? 'NULL'],
            ['C4C UUID', $appointment->c4c_uuid ?? 'NULL'],
            ['Is Synced', $appointment->is_synced ? 'Sí' : 'No'],
        ]);

        // Verificar si ya está sincronizado
        if ($appointment->is_synced && $appointment->c4c_uuid && !$force) {
            $this->warn("⚠️ El appointment ya está sincronizado. Use --force para forzar.");
            return 0;
        }

        if ($force) {
            $this->warn("🔥 Forzando sincronización...");
        }

        // Ejecutar sincronización
        $this->info("🚀 Ejecutando sincronización con C4C...");

        try {
            $result = $syncService->syncAppointmentToC4C($appointment);

            if ($result['success']) {
                $this->info("✅ Sincronización exitosa");

                // Refrescar datos
                $appointment->refresh();

                $this->table(['Campo', 'Valor Actualizado'], [
                    ['Package ID', $appointment->package_id ?? 'NULL'],
                    ['C4C UUID', $appointment->c4c_uuid ?? 'NULL'],
                    ['Is Synced', $appointment->is_synced ? 'Sí' : 'No'],
                    ['Synced At', $appointment->synced_at ?? 'NULL'],
                ]);

                $this->info("🎉 ¡Appointment sincronizado correctamente!");
                return 0;

            } else {
                $this->error("❌ Error en sincronización: " . ($result['error'] ?? 'Error desconocido'));
                return 1;
            }

        } catch (\Exception $e) {
            $this->error("💥 Excepción durante sincronización: " . $e->getMessage());
            Log::error('Error en comando de sincronización', [
                'appointment_id' => $appointment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }
}
