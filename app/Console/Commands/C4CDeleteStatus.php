<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class C4CDeleteStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:delete-status 
                            {job_id : ID del job de eliminación a consultar}
                            {--watch : Monitorear continuamente cada 5 segundos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consultar el estado de un job de eliminación de cita';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jobId = $this->argument('job_id');
        $isWatch = $this->option('watch');

        if ($isWatch) {
            return $this->watchJobStatus($jobId);
        } else {
            return $this->showJobStatus($jobId);
        }
    }

    /**
     * Mostrar estado del job una vez
     */
    private function showJobStatus(string $jobId): int
    {
        $status = Cache::get("delete_job_{$jobId}");

        if (!$status) {
            $this->error('❌ Job no encontrado o expirado');
            $this->info("🔍 Job ID: {$jobId}");
            return 1;
        }

        $this->displayJobStatus($status, $jobId);
        return 0;
    }

    /**
     * Monitorear estado del job continuamente
     */
    private function watchJobStatus(string $jobId): int
    {
        $this->info("👀 Monitoreando job de eliminación: {$jobId}");
        $this->info("⏹️ Presiona Ctrl+C para detener el monitoreo");
        $this->newLine();

        $iteration = 0;
        $lastStatus = null;

        while (true) {
            $status = Cache::get("delete_job_{$jobId}");

            if (!$status) {
                $this->error('❌ Job no encontrado o expirado');
                return 1;
            }

            // Solo mostrar si el estado cambió o cada 10 iteraciones
            if ($lastStatus !== $status['status'] || $iteration % 10 === 0) {
                $this->line("\n" . str_repeat('=', 50));
                $this->info("🕐 " . now()->format('H:i:s') . " - Iteración #" . ($iteration + 1));
                $this->displayJobStatus($status, $jobId);
                $lastStatus = $status['status'];
            }

            // Si el job terminó (completado o fallido), salir
            if (in_array($status['status'], ['completed', 'failed'])) {
                $this->newLine();
                if ($status['status'] === 'completed') {
                    $this->info('🎉 Job completado exitosamente!');
                } else {
                    $this->error('💥 Job falló después de todos los intentos');
                }
                return $status['status'] === 'completed' ? 0 : 1;
            }

            $iteration++;
            sleep(5); // Esperar 5 segundos
        }
    }

    /**
     * Mostrar información del job
     */
    private function displayJobStatus(array $status, string $jobId): void
    {
        $this->info("🔍 Job ID: {$jobId}");
        
        // Estado con color
        $statusDisplay = $this->getStatusDisplay($status['status']);
        $this->line("📊 Estado: {$statusDisplay}");
        
        // Progress bar
        $progress = $status['progress'] ?? 0;
        $progressBar = $this->createProgressBar($progress);
        $this->line("📈 Progreso: {$progressBar} {$progress}%");
        
        // Mensaje
        if (!empty($status['message'])) {
            $this->info("💬 Mensaje: {$status['message']}");
        }

        // Información del appointment
        if (!empty($status['appointment_id'])) {
            $this->info("📋 Appointment ID: {$status['appointment_id']}");
        }
        
        if (!empty($status['appointment_number'])) {
            $this->info("📊 Número de Cita: {$status['appointment_number']}");
        }

        // Información de errores
        if (!empty($status['error'])) {
            $this->error("❌ Error: {$status['error']}");
        }

        // Información de reintentos
        if (!empty($status['attempt'])) {
            $this->warn("🔄 Intento: {$status['attempt']}");
        }

        // Información adicional
        if (!empty($status['fatal'])) {
            $this->error("💀 Error fatal - No se reintentará");
        }

        // Timestamp
        if (!empty($status['updated_at'])) {
            $updatedAt = \Carbon\Carbon::parse($status['updated_at']);
            $this->comment("⏰ Actualizado: {$updatedAt->format('Y-m-d H:i:s')} ({$updatedAt->diffForHumans()})");
        }
    }

    /**
     * Obtener display del estado con colores
     */
    private function getStatusDisplay(string $status): string
    {
        return match($status) {
            'processing' => '<fg=blue>🔄 Procesando</>',
            'completed' => '<fg=green>✅ Completado</>',
            'failed' => '<fg=red>❌ Fallido</>',
            'retrying' => '<fg=yellow>🔄 Reintentando</>',
            default => "<fg=gray>{$status}</>",
        };
    }

    /**
     * Crear barra de progreso visual
     */
    private function createProgressBar(int $progress): string
    {
        $width = 20;
        $filled = (int)($progress / 100 * $width);
        $empty = $width - $filled;
        
        return '[' . 
               '<fg=green>' . str_repeat('█', $filled) . '</>' .
               '<fg=gray>' . str_repeat('░', $empty) . '</>' .
               ']';
    }
}