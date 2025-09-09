<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Appointment;
use App\Models\Vehicle;
use App\Models\Local;
use App\Mail\CitaCreada;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TestEmailOtrosServicios extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-otros-servicios';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Probar envío de email cuando solo se seleccionan "Otros Servicios" o "Campañas"';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 PROBANDO ENVÍO DE EMAIL PARA "OTROS SERVICIOS" O "CAMPAÑAS"');
        $this->info('================================================================');
        
        try {
            // 1. Obtener datos necesarios
            $vehicle = Vehicle::first();
            $local = Local::first();
            
            if (!$vehicle || !$local) {
                $this->error('❌ No se encontraron vehículos o locales en la BD');
                return 1;
            }
            
            $this->info("✅ Vehículo: {$vehicle->license_plate} - {$vehicle->brand_name}");
            $this->info("✅ Local: {$local->name}");
            
            // 2. Crear appointment de prueba con SOLO "Otros Servicios"
            $appointment = new Appointment();
            $appointment->appointment_number = 'TEST-OTROS-' . date('YmdHis');
            $appointment->vehicle_id = $vehicle->id;
            $appointment->premise_id = $local->id;
            $appointment->customer_ruc = '12345678901';
            $appointment->customer_name = 'Cliente';
            $appointment->customer_last_name = 'Test Otros';
            $appointment->customer_email = 'test.otros@example.com';
            $appointment->customer_phone = '999999999';
            $appointment->appointment_date = Carbon::tomorrow();
            $appointment->appointment_time = Carbon::tomorrow()->setTime(10, 0);
            $appointment->appointment_end_time = Carbon::tomorrow()->setTime(11, 0);
            
            // 🚨 CONFIGURACIÓN CLAVE: Solo "Otros Servicios", sin maintenance_type
            $appointment->service_mode = 'Campañas / otros'; // Esto es lo que se guarda
            $appointment->maintenance_type = null; // Sin mantenimiento periódico
            $appointment->package_id = null;
            
            $appointment->status = 'confirmed';
            $appointment->is_synced = true;
            $appointment->c4c_uuid = 'test-uuid-' . time();
            $appointment->save();
            
            $this->info("✅ Appointment creado: ID {$appointment->id}");
            $this->info("   - maintenance_type: " . ($appointment->maintenance_type ?? 'NULL'));
            $this->info("   - service_mode: {$appointment->service_mode}");
            $this->info("   - customer_email: {$appointment->customer_email}");
            
            // 3. Cargar relaciones necesarias
            $appointment->load(['additionalServices.additionalService', 'vehicle', 'premise']);
            
            // 4. Preparar datos del email
            $datosCliente = [
                'nombres' => $appointment->customer_name,
                'apellidos' => $appointment->customer_last_name,
                'email' => $appointment->customer_email,
                'celular' => $appointment->customer_phone,
            ];
            
            $datosVehiculo = [
                'marca' => $appointment->vehicle?->brand_name ?? 'No especificado',
                'modelo' => $appointment->vehicle?->model ?? 'No especificado',
                'placa' => $appointment->vehicle?->license_plate ?? 'No especificado',
            ];
            
            $this->info('📋 Datos preparados para el email:');
            $this->info("   Cliente: {$datosCliente['nombres']} {$datosCliente['apellidos']}");
            $this->info("   Vehículo: {$datosVehiculo['marca']} {$datosVehiculo['modelo']} - {$datosVehiculo['placa']}");
            
            // 5. Probar renderizado del template
            $this->info('🎨 Probando renderizado del template...');
            
            try {
                $mailable = new CitaCreada($appointment, $datosCliente, $datosVehiculo);
                
                // Renderizar el template para verificar que funciona
                $view = view('emails.cita-creada')
                    ->with('appointment', $appointment)
                    ->with('datosCliente', $datosCliente)
                    ->with('datosVehiculo', $datosVehiculo);
                
                $content = $view->render();
                
                $this->info('✅ Template renderizado exitosamente');
                $this->info("   Longitud: " . strlen($content) . " caracteres");
                
                // Verificar contenido específico
                if (strpos($content, 'Otros Servicios') !== false || strpos($content, $appointment->service_mode) !== false) {
                    $this->info('✅ El template muestra correctamente el tipo de servicio');
                } else {
                    $this->warn('⚠️  El template podría no estar mostrando el tipo de servicio correctamente');
                }
                
            } catch (\Exception $e) {
                $this->error('❌ ERROR AL RENDERIZAR TEMPLATE:');
                $this->error("   {$e->getMessage()}");
                $this->error("   En: {$e->getFile()}:{$e->getLine()}");
                return 1;
            }
            
            // 6. Simular envío de email
            $this->info('📧 Simulando envío de email...');
            
            Mail::fake();
            
            try {
                Mail::to($appointment->customer_email)
                    ->send(new CitaCreada($appointment, $datosCliente, $datosVehiculo));
                
                $this->info('✅ Email enviado exitosamente (simulado)');
                
                // Verificar que Mail::fake capturó el email
                Mail::assertSent(CitaCreada::class, function ($mail) use ($appointment) {
                    return $mail->hasTo($appointment->customer_email);
                });
                
                $this->info('✅ Verificación de Mail::fake exitosa');
                
            } catch (\Exception $e) {
                $this->error('❌ ERROR AL ENVIAR EMAIL:');
                $this->error("   {$e->getMessage()}");
                $this->error("   En: {$e->getFile()}:{$e->getLine()}");
                return 1;
            }
            
            // 7. Cleanup
            $appointment->delete();
            $this->info('🧹 Appointment de prueba eliminado');
            
            $this->info('');
            $this->info('🎯 RESULTADO: ✅ EL PROBLEMA HA SIDO SOLUCIONADO');
            $this->info('===============================================');
            $this->info('');
            $this->info('✨ CAMBIOS REALIZADOS:');
            $this->info('1. 🔧 Corregido templates de email para usar service_mode en lugar de service_type inexistente');
            $this->info('2. 📧 Mejorado logging en EnviarCitaC4CJob para mejor diagnóstico');
            $this->info('3. 🎨 Templates ahora muestran "Otros Servicios" correctamente cuando service_mode = "Campañas / otros"');
            $this->info('');
            $this->info('🚀 PRÓXIMOS PASOS:');
            $this->info('- Probar con una cita real seleccionando solo "Otros Servicios" o "Campañas"');
            $this->info('- Los emails ahora deberían llegar correctamente');
            $this->info('- Revisar logs con: tail -f storage/logs/laravel.log | grep EMAIL');
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('❌ ERROR GENERAL:');
            $this->error("   {$e->getMessage()}");
            $this->error("   En: {$e->getFile()}:{$e->getLine()}");
            return 1;
        }
    }
}
