<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Vehicle;

echo "=== LIMPIANDO TABLA VEHICLES ===\n";

$count = Vehicle::count();
echo "Vehículos antes: {$count}\n";

Vehicle::truncate();

$countAfter = Vehicle::count();
echo "Vehículos después: {$countAfter}\n";
echo "✅ Tabla limpiada correctamente\n";
echo "\nAhora cuando vayas a Vehículos, se recargarán con el nuevo formato de ID (VIN real)\n"; 