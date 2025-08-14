<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use Illuminate\Console\Command;

class CheckOfferStatus extends Command
{
    protected $signature = 'offer:status {--appointment_id= : ID específico de appointment} {--recent=10 : Número de appointments recientes a mostrar}';
    protected $description = 'Verificar estado de ofertas en appointments';

    public function handle()
    {
        $appointmentId = $this->option('appointment_id');
        $recent = $this->option('recent');

        $this->info('📊 ESTADO DE OFERTAS EN APPOINTMENTS');
        $this->newLine();

        if ($appointmentId) {
            // Mostrar appointment específico
            $appointment = Appointment::find($appointmentId);
            
            if (!$appointment) {
                $this->error("❌ Appointment con ID {$appointmentId} no encontrado");
                return 1;
            }

            $this->showAppointmentDetails($appointment);
        } else {
            // Mostrar estadísticas generales
            $this->showGeneralStats();
            $this->newLine();
            
            // Mostrar appointments recientes
            $this->info("📋 ÚLTIMOS {$recent} APPOINTMENTS:");
            $this->newLine();
            
            $appointments = Appointment::orderBy('created_at', 'desc')
                                     ->limit($recent)
                                     ->get();

            foreach ($appointments as $appointment) {
                $this->showAppointmentSummary($appointment);
            }
        }

        return 0;
    }

    private function showGeneralStats()
    {
        $total = Appointment::count();
        $withOffers = Appointment::whereNotNull('c4c_offer_id')->count();
        $withPackageId = Appointment::whereNotNull('package_id')->count();
        $withBrandCode = Appointment::whereNotNull('vehicle_brand_code')->count();
        $withCenterCode = Appointment::whereNotNull('center_code')->count();
        $synced = Appointment::where('is_synced', true)->count();
        $failed = Appointment::where('offer_creation_failed', true)->count();

        $this->info("📈 ESTADÍSTICAS GENERALES:");
        $this->info("   Total appointments: {$total}");
        $this->info("   Con ofertas creadas: {$withOffers}");
        $this->info("   Con package_id: {$withPackageId}");
        $this->info("   Con vehicle_brand_code: {$withBrandCode}");
        $this->info("   Con center_code: {$withCenterCode}");
        $this->info("   Sincronizados con C4C: {$synced}");
        $this->info("   Con creación de oferta fallida: {$failed}");
    }

    private function showAppointmentDetails(Appointment $appointment)
    {
        $this->info("🔍 DETALLES DEL APPOINTMENT {$appointment->id}:");
        $this->info("   Número: {$appointment->appointment_number}");
        $this->info("   Fecha: {$appointment->appointment_date}");
        $this->info("   Cliente: {$appointment->customer_name} {$appointment->customer_last_name}");
        $this->newLine();
        
        $this->info("📋 DATOS PARA OFERTAS:");
        $this->info("   Package ID: " . ($appointment->package_id ?: '❌ NO'));
        $this->info("   Vehicle Brand Code: " . ($appointment->vehicle_brand_code ?: '❌ NO'));
        $this->info("   Center Code: " . ($appointment->center_code ?: '❌ NO'));
        $this->info("   C4C UUID: " . ($appointment->c4c_uuid ?: '❌ NO'));
        $this->info("   Is Synced: " . ($appointment->is_synced ? '✅ SÍ' : '❌ NO'));
        $this->newLine();
        
        $this->info("🎯 ESTADO DE OFERTA:");
        $this->info("   C4C Offer ID: " . ($appointment->c4c_offer_id ?: '❌ NO CREADA'));
        $this->info("   Offer Created At: " . ($appointment->offer_created_at ?: '❌ NO'));
        $this->info("   Creation Failed: " . ($appointment->offer_creation_failed ? '❌ SÍ' : '✅ NO'));
        $this->info("   Creation Error: " . ($appointment->offer_creation_error ?: '✅ NINGUNO'));
        $this->info("   Creation Attempts: " . ($appointment->offer_creation_attempts ?: '0'));
        $this->newLine();
        
        $this->info("🔧 PUEDE CREAR OFERTA: " . ($appointment->canCreateOffer() ? '✅ SÍ' : '❌ NO'));
        
        if ($appointment->getOrganizationalMapping()) {
            $mapping = $appointment->getOrganizationalMapping();
            $this->info("🏢 MAPEO ORGANIZACIONAL: ✅ ENCONTRADO");
            $this->info("   Sales Organization: {$mapping->sales_organization_id}");
            $this->info("   Sales Office: {$mapping->sales_office_id}");
            $this->info("   Division: {$mapping->division_code}");
        } else {
            $this->error("🏢 MAPEO ORGANIZACIONAL: ❌ NO ENCONTRADO");
        }
    }

    private function showAppointmentSummary(Appointment $appointment)
    {
        $status = '❌';
        if ($appointment->c4c_offer_id) {
            $status = '✅ OFERTA';
        } elseif ($appointment->offer_creation_failed) {
            $status = '❌ FALLIDA';
        } elseif ($appointment->canCreateOffer()) {
            $status = '🟡 PENDIENTE';
        }

        $this->info("   [{$appointment->id}] {$appointment->appointment_number} - {$status}");
        $this->info("      Brand: " . ($appointment->vehicle_brand_code ?: 'N/A') . 
                   " | Center: " . ($appointment->center_code ?: 'N/A') . 
                   " | Package: " . ($appointment->package_id ?: 'N/A'));
    }
}
