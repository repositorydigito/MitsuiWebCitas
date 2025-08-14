<?php

namespace App\Console\Commands;

use App\Models\Vehicle;
use Illuminate\Console\Command;

class SyncVehiclesFromMock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'vehicles:sync-from-mock {--force : Forzar la sincronización incluso si ya existen vehículos}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza vehículos desde los datos mock configurados';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');

        // Verificar si ya existen vehículos en la base de datos
        $existingCount = Vehicle::count();
        if ($existingCount > 0 && ! $force) {
            $this->warn("Ya existen {$existingCount} vehículos en la base de datos.");
            if (! $this->confirm('¿Desea continuar con la sincronización?')) {
                $this->info('Sincronización cancelada.');

                return 0;
            }
        }

        // Ejecutar el seeder de vehículos
        $this->info('Iniciando sincronización de vehículos desde datos mock...');
        $this->call('db:seed', [
            '--class' => 'Database\\Seeders\\VehicleSeeder',
        ]);

        $this->info('Sincronización completada.');

        return 0;
    }
}
