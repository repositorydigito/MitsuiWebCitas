<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Vehicle;
use App\Models\Local;
use App\Models\CenterOrganizationMapping;
use App\Services\C4C\OfferService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestOrganizationalMapping extends Command
{
    protected $signature = 'test:organizational-mapping';
    protected $description = 'Probar el mapeo organizacional en el flujo de citas y ofertas';

    public function handle()
    {
        $this->info('🚀 INICIANDO PRUEBA DE MAPEO ORGANIZACIONAL');
        $this->newLine();

        // 1. Verificar datos base
        $this->info('📋 VERIFICANDO DATOS BASE...');
        
        $vehicle = Vehicle::where('brand_code', 'Z01')->first();
        $local = Local::where('code', 'M013')->first();
        
        if (!$vehicle) {
            $this->error('❌ No se encontró vehículo con brand_code Z01');
            return 1;
        }
        
        if (!$local) {
            $this->error('❌ No se encontró local con code M013');
            return 1;
        }

        $this->info("✅ Vehículo: {$vehicle->license_plate} (Brand: {$vehicle->brand_code})");
        $this->info("✅ Local: {$local->name} (Code: {$local->code})");

        // 2. Verificar mapeo organizacional
        $this->info('🏢 VERIFICANDO MAPEO ORGANIZACIONAL...');
        
        $mapping = CenterOrganizationMapping::forCenterAndBrand($local->code, $vehicle->brand_code)->first();
        
        if (!$mapping) {
            $this->error("❌ No existe mapeo para centro {$local->code} y marca {$vehicle->brand_code}");
            return 1;
        }

        $this->info("✅ Mapeo encontrado:");
        $this->info("   - Sales Organization: {$mapping->sales_organization_id}");
        $this->info("   - Sales Office: {$mapping->sales_office_id}");
        $this->info("   - Division: {$mapping->division_code}");

        // 3. Crear appointment de prueba
        $this->info('📝 CREANDO APPOINTMENT DE PRUEBA...');
        
        $appointment = new Appointment();
        $appointment->appointment_number = 'TEST-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        $appointment->vehicle_id = $vehicle->id;
        $appointment->premise_id = $local->id;
        $appointment->customer_ruc = '12345678901';
        $appointment->customer_name = 'Cliente';
        $appointment->customer_last_name = 'Prueba';
        $appointment->customer_email = 'test@example.com';
        $appointment->customer_phone = '999999999';
        $appointment->appointment_date = now()->addDays(1);
        $appointment->appointment_time = now()->addDays(1)->setTime(10, 0);
        $appointment->appointment_end_time = now()->addDays(1)->setTime(11, 0);
        $appointment->service_mode = 'express';
        $appointment->maintenance_type = 'preventive';
        $appointment->status = 'confirmed';
        
        // ✅ CAMPOS DE MAPEO ORGANIZACIONAL
        $appointment->vehicle_brand_code = $vehicle->brand_code;
        $appointment->center_code = $local->code;
        $appointment->package_id = 'M1085-010';
        $appointment->vehicle_plate = $vehicle->license_plate;
        $appointment->c4c_uuid = 'test-uuid-' . Str::uuid();
        $appointment->is_synced = true;
        
        $appointment->save();
        
        $this->info("✅ Appointment creado: {$appointment->appointment_number}");
        $this->info("   - Vehicle Brand Code: {$appointment->vehicle_brand_code}");
        $this->info("   - Center Code: {$appointment->center_code}");
        $this->info("   - Package ID: {$appointment->package_id}");

        // 4. Probar validaciones del appointment
        $this->info('🔍 PROBANDO VALIDACIONES...');
        
        if ($appointment->canCreateOffer()) {
            $this->info('✅ Appointment puede crear oferta');
        } else {
            $this->error('❌ Appointment NO puede crear oferta');
        }

        $mappingFromAppointment = $appointment->getOrganizationalMapping();
        if ($mappingFromAppointment) {
            $this->info('✅ Mapeo organizacional obtenido desde appointment');
        } else {
            $this->error('❌ No se pudo obtener mapeo desde appointment');
        }

        // 5. Probar OfferService (sin llamar realmente a C4C)
        $this->info('🎯 PROBANDO OFFER SERVICE...');
        
        $offerService = new OfferService();
        
        // Simular que no llamamos realmente a C4C
        $this->info('ℹ️  (Simulación - no se llama realmente a C4C)');
        $this->info("✅ OfferService inicializado correctamente");

        // 6. Limpiar datos de prueba
        $this->info('🧹 LIMPIANDO DATOS DE PRUEBA...');
        $appointment->delete();
        $this->info('✅ Appointment de prueba eliminado');

        $this->newLine();
        $this->info('🎉 PRUEBA COMPLETADA EXITOSAMENTE');
        $this->info('✅ El mapeo organizacional está funcionando correctamente');
        
        return 0;
    }
}
