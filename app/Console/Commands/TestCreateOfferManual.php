<?php

namespace App\Console\Commands;

use App\Jobs\CreateOfferJob;
use App\Models\Appointment;
use Illuminate\Console\Command;

class TestCreateOfferManual extends Command
{
    protected $signature = 'c4c:test-create-offer {appointment_id=70}';
    protected $description = 'Manually test CreateOfferJob with corrected OfferService';

    public function handle()
    {
        $appointmentId = $this->argument('appointment_id');
        
        $this->info("🧪 Testing CreateOfferJob manually for appointment: {$appointmentId}");
        
        // Verificar que la cita existe
        $appointment = Appointment::find($appointmentId);
        
        if (!$appointment) {
            $this->error("❌ Appointment {$appointmentId} not found");
            return Command::FAILURE;
        }
        
        // Verificar que tiene productos
        $productos = \App\Models\Product::where('appointment_id', $appointmentId)->count();
        
        if ($productos === 0) {
            $this->error("❌ No products found for appointment {$appointmentId}");
            return Command::FAILURE;
        }
        
        $this->info("📋 Appointment info:");
        $this->line("ID: {$appointment->id}");
        $this->line("Package ID: {$appointment->package_id}");
        $this->line("Products: {$productos}");
        $this->line("Current offer ID: " . ($appointment->c4c_offer_id ?? 'NULL'));
        
        $this->info("\n🚀 Ejecutando CreateOfferJob...");
        
        try {
            // Crear y ejecutar el job sincrónicamente
            $job = new CreateOfferJob($appointment);
            $offerService = app(\App\Services\C4C\OfferService::class);
            $job->handle($offerService);
            
            // Verificar resultado
            $appointment->refresh();
            
            $this->info("\n📊 Resultado:");
            $this->line("C4C Offer ID: " . ($appointment->c4c_offer_id ?? 'NULL'));
            $this->line("Offer Created At: " . ($appointment->offer_created_at ?? 'NULL'));
            $this->line("Creation Failed: " . ($appointment->offer_creation_failed ?? 'false'));
            $this->line("Creation Error: " . ($appointment->offer_creation_error ?? 'NULL'));
            $this->line("Creation Attempts: " . ($appointment->offer_creation_attempts ?? '0'));
            
            if ($appointment->c4c_offer_id) {
                $this->info("\n✅ ¡OFERTA CREADA EXITOSAMENTE!");
                $this->line("La oferta ahora incluye los {$productos} productos descargados.");
            } else {
                $this->error("\n❌ La oferta no se creó");
                if ($appointment->offer_creation_error) {
                    $this->line("Error: " . $appointment->offer_creation_error);
                }
            }
            
        } catch (\Exception $e) {
            $this->error("💥 Error ejecutando CreateOfferJob: " . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}