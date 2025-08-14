<?php

namespace App\Console\Commands;

use App\Jobs\DownloadProductsJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestAutomaticFlow extends Command
{
    protected $signature = 'c4c:test-automatic-flow {package_id=M2275-010} {appointment_id=70}';
    protected $description = 'Test the automatic flow: DownloadProductsJob -> CreateOfferJob';

    public function handle()
    {
        $packageId = $this->argument('package_id');
        $appointmentId = $this->argument('appointment_id');
        
        $this->info("🧪 Testing automatic flow for package: {$packageId}, appointment: {$appointmentId}");
        
        // Verificar appointment existe
        $appointment = \App\Models\Appointment::find($appointmentId);
        if (!$appointment) {
            $this->error("❌ Appointment {$appointmentId} not found");
            return Command::FAILURE;
        }
        
        // Limpiar oferta anterior si existe
        $appointment->update([
            'c4c_offer_id' => null,
            'offer_created_at' => null,
            'offer_creation_failed' => 0,
            'offer_creation_error' => null,
            'offer_creation_attempts' => 0
        ]);
        
        // Limpiar productos específicos de la cita
        \App\Models\Product::where('appointment_id', $appointmentId)->delete();
        
        $this->info("🧹 Estado limpiado para nueva prueba");
        
        // Verificar jobs pendientes antes
        $jobsAntes = DB::table('jobs')->count();
        $this->line("Jobs pendientes antes: {$jobsAntes}");
        
        // Disparar DownloadProductsJob con appointment_id
        $this->info("\n🚀 Disparando DownloadProductsJob...");
        DownloadProductsJob::dispatch($packageId, $appointmentId)->onQueue('products');
        
        // Verificar jobs después
        $jobsDespues = DB::table('jobs')->count();
        $this->line("Jobs pendientes después: {$jobsDespues}");
        
        $this->info("\n📋 Jobs en cola:");
        $jobs = DB::table('jobs')->select('id', 'queue', 'payload')->get();
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload, true);
            $jobClass = $payload['displayName'] ?? 'Unknown';
            $this->line("  - Queue: {$job->queue} | Class: {$jobClass}");
        }
        
        $this->info("\n⏳ Ahora ejecuta el queue worker para procesar automáticamente:");
        $this->line("php artisan queue:work --queue=products,offers --stop-when-empty");
        
        $this->info("\n🎯 Flujo esperado:");
        $this->line("1. DownloadProductsJob descarga productos");
        $this->line("2. DownloadProductsJob dispara CreateOfferJob automáticamente");
        $this->line("3. CreateOfferJob crea oferta con todos los productos");
        
        return Command::SUCCESS;
    }
}