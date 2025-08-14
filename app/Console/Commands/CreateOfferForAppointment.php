<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Jobs\CreateOfferJob;
use App\Services\C4C\OfferService;
use Illuminate\Console\Command;

class CreateOfferForAppointment extends Command
{
    protected $signature = 'offer:create {appointment_id} {--sync : Ejecutar sincrónicamente en lugar de usar job}';
    protected $description = 'Crear oferta para una cita específica';

    public function handle()
    {
        $appointmentId = $this->argument('appointment_id');
        $sync = $this->option('sync');

        $this->info("🎯 Creando oferta para appointment ID: {$appointmentId}");
        $this->newLine();

        // Buscar el appointment
        $appointment = Appointment::find($appointmentId);
        
        if (!$appointment) {
            $this->error("❌ Appointment con ID {$appointmentId} no encontrado");
            return 1;
        }

        $this->info("✅ Appointment encontrado: {$appointment->appointment_number}");
        $this->info("   - Vehicle Brand Code: {$appointment->vehicle_brand_code}");
        $this->info("   - Center Code: {$appointment->center_code}");
        $this->info("   - Package ID: {$appointment->package_id}");
        $this->info("   - C4C UUID: {$appointment->c4c_uuid}");
        $this->info("   - Is Synced: " . ($appointment->is_synced ? 'Sí' : 'No'));
        $this->newLine();

        // Verificar prerrequisitos
        if (!$appointment->canCreateOffer()) {
            $this->error("❌ El appointment no cumple los prerrequisitos para crear oferta:");
            
            if (!$appointment->is_synced) $this->error("   - No está sincronizado con C4C");
            if (!$appointment->c4c_uuid) $this->error("   - No tiene C4C UUID");
            if (!$appointment->package_id) $this->error("   - No tiene Package ID");
            if (!$appointment->vehicle_brand_code) $this->error("   - No tiene Vehicle Brand Code");
            if (!$appointment->center_code) $this->error("   - No tiene Center Code");
            if ($appointment->c4c_offer_id) $this->error("   - Ya tiene oferta creada: {$appointment->c4c_offer_id}");
            if ($appointment->offer_creation_failed) $this->error("   - Creación de oferta marcada como fallida");
            
            return 1;
        }

        $this->info("✅ Prerrequisitos cumplidos");
        $this->newLine();

        if ($sync) {
            // Ejecutar sincrónicamente
            $this->info("🔄 Ejecutando creación de oferta sincrónicamente...");
            
            $offerService = new OfferService();
            $result = $offerService->crearOfertaDesdeCita($appointment);
            
            if ($result['success']) {
                $this->info("✅ Oferta creada exitosamente");
                $this->info("   - C4C Offer ID: " . ($result['c4c_offer_id'] ?? 'N/A'));
                $this->info("   - Message: " . ($result['message'] ?? 'N/A'));
            } else {
                $this->error("❌ Error creando oferta: " . $result['error']);
                return 1;
            }
        } else {
            // Usar job asíncrono
            $this->info("📤 Despachando CreateOfferJob...");
            
            CreateOfferJob::dispatch($appointment);
            
            $this->info("✅ Job despachado exitosamente");
            $this->info("💡 Ejecuta 'php artisan queue:work' para procesar el job");
        }

        return 0;
    }
}
