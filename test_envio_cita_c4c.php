<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Vehicle;
use App\Models\User;
use App\Models\Local;
use App\Services\C4C\AppointmentService;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

echo "=== TEST ENVÍO COMPLETO DE CITA A C4C ===\n";
echo "Flujo: vehiculoId → Datos BD → Crear cita → Enviar C4C\n\n";

// 1. VERIFICAR VEHÍCULO EN BD
echo "1. 🚗 VERIFICANDO VEHÍCULO PERSISTIDO:\n";
$vehiculoId = 'VINAPP01234567891'; // VIN corregido
$vehiculo = Vehicle::where('vehicle_id', $vehiculoId)
    ->orWhere('license_plate', $vehiculoId)
    ->first();

if (!$vehiculo) {
    echo "❌ No se encontró vehículo con ID: {$vehiculoId}\n";
    echo "💡 Ve a /admin/vehiculos para cargar vehículos en BD\n";
    exit(1);
}

echo "✅ Vehículo encontrado:\n";
echo "   ID: {$vehiculo->vehicle_id}\n";
echo "   Placa: {$vehiculo->license_plate}\n";
echo "   Modelo: {$vehiculo->model}\n";
echo "   Fuente: {$vehiculo->data_source}\n\n";

// 2. VERIFICAR USUARIO DISPONIBLE EN BD
echo "2. 👤 BUSCANDO USUARIO DISPONIBLE EN BD:\n";
// Buscar usuario con c4c_internal_id configurado
$user = User::whereNotNull('c4c_internal_id')->first();
if (!$user) {
    echo "⚠️  No hay usuarios con C4C ID. Usando el primero disponible...\n";
    $user = User::first(); // Fallback al primer usuario
}
if (!$user) {
    echo "❌ No hay usuarios en la base de datos\n";
    echo "💡 Crear un usuario primero\n";
    exit(1);
}

echo "ℹ️  Usuarios disponibles en BD:\n";
User::select('id', 'name', 'email', 'document_number', 'c4c_internal_id')->take(5)->get()->each(function($u) {
    echo "   [{$u->id}] {$u->name} | {$u->email} | {$u->document_number} | C4C: " . ($u->c4c_internal_id ?? 'NULL') . "\n";
});
echo "\n✅ Usando usuario seleccionado:\n";

echo "✅ Usuario encontrado:\n";
echo "   Nombre: {$user->name}\n";
echo "   Email: {$user->email}\n";
echo "   Documento: {$user->document_number}\n";
echo "   C4C ID: " . ($user->c4c_internal_id ?? 'NO CONFIGURADO') . "\n\n";

// 3. VERIFICAR LOCAL
echo "3. 🏢 VERIFICANDO LOCAL:\n";
$local = Local::where('code', 'M013')->first();
if (!$local) {
    echo "❌ No se encontró local M013\n";
    exit(1);
}

echo "✅ Local encontrado:\n";
echo "   Código: {$local->code}\n";
echo "   Nombre: {$local->name}\n\n";

// 4. PREPARAR DATOS DE LA CITA
echo "4. 📋 PREPARANDO DATOS DE LA CITA:\n";
$citaData = [
    'customer_id' => $user->c4c_internal_id ?? '1270002726', // Cliente de prueba
    'employee_id' => '1740', // ID del asesor
    'start_date' => now()->addDays(15)->setHour(14)->setMinute(0), // Fecha más futura, 2:00 PM
    'end_date' => now()->addDays(15)->setHour(14)->setMinute(45), // 45 minutos después
    'center_id' => 'M013', // Volver a MOLINA que tiene configuración
    'vehicle_plate' => 'NEW-' . rand(100, 999), // Placa aleatoria para evitar duplicados
    'customer_name' => $user->name,
    'notes' => 'Cita de prueba desde Laravel - Horario estándar - VehículoID: ' . $vehiculoId,
    'express' => false,
];

echo "📊 DATOS PREPARADOS:\n";
foreach ($citaData as $key => $value) {
    echo "   {$key}: {$value}\n";
}
echo "\n";

// 5. ENVIAR A C4C (SERVICIO REAL)
echo "5. 🚀 ENVIANDO CITA A C4C (SERVICIO REAL):\n";
echo "   URL: https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/manageappointmentactivityin1\n";
echo "   Método: AppointmentActivityBundleMaintainRequest_sync_V1\n\n";
$appointmentService = app(\App\Services\C4C\AppointmentService::class);

try {
    $resultado = $appointmentService->create($citaData);
    
    if ($resultado['success']) {
        echo "✅ CITA ENVIADA EXITOSAMENTE:\n";
        echo "   UUID: " . ($resultado['data']['uuid'] ?? 'N/A') . "\n";
        echo "   ID: " . ($resultado['data']['id'] ?? 'N/A') . "\n";
        echo "   Change State ID: " . ($resultado['data']['change_state_id'] ?? 'N/A') . "\n";
        
        // 6. GUARDAR EN BD LOCAL
        echo "\n6. 💾 GUARDANDO EN BD LOCAL:\n";
        $appointment = new \App\Models\Appointment();
        $appointment->appointment_number = 'TEST-' . date('Ymd') . '-' . strtoupper(substr(md5(rand()), 0, 5));
        $appointment->vehicle_id = $vehiculo->id;
        $appointment->premise_id = $local->id;
        $appointment->customer_ruc = $user->document_number;
        $appointment->customer_name = explode(' ', $user->name)[0] ?? $user->name;
        $appointment->customer_last_name = explode(' ', $user->name, 2)[1] ?? '';
        $appointment->customer_email = $user->email;
        $appointment->customer_phone = $user->phone ?? '987654321';
        $appointment->appointment_date = $citaData['start_date']->format('Y-m-d');
        $appointment->appointment_time = $citaData['start_date']->format('H:i:s');
        $appointment->service_mode = 'Test';
        $appointment->maintenance_type = 'Mantenimiento periódico';
        $appointment->comments = $citaData['notes'];
        $appointment->status = 'confirmed';
        $appointment->c4c_uuid = $resultado['data']['uuid'] ?? null;
        $appointment->is_synced = true;
        $appointment->synced_at = now();
        $appointment->save();
        
        echo "✅ Appointment guardado con ID: {$appointment->id}\n";
        echo "   Número: {$appointment->appointment_number}\n";
        echo "   C4C UUID: {$appointment->c4c_uuid}\n";
        
    } else {
        echo "❌ ERROR AL ENVIAR CITA:\n";
        echo "   Error: " . ($resultado['error'] ?? 'Unknown error') . "\n";
        
        if (isset($resultado['data'])) {
            echo "   Data: " . json_encode($resultado['data'], JSON_PRETTY_PRINT) . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ EXCEPCIÓN AL ENVIAR CITA:\n";
    echo "   Mensaje: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== FIN DEL TEST ===\n"; 