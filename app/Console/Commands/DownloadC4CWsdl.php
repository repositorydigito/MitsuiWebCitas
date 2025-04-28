<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class DownloadC4CWsdl extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'c4c:download-wsdl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Descarga los archivos WSDL de C4C y los guarda localmente';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Descargando archivos WSDL de C4C...');

        $wsdls = [
            'customer' => [
                'url' => config('c4c.services.customer.wsdl'),
                'path' => storage_path('wsdl/querycustomerin.wsdl'),
            ],
            'appointment' => [
                'url' => config('c4c.services.appointment.wsdl'),
                'path' => storage_path('wsdl/manageappointmentactivityin.wsdl'),
            ],
            'appointment_query' => [
                'url' => config('c4c.services.appointment_query.wsdl'),
                'path' => storage_path('wsdl/wscitas.wsdl'),
            ],
        ];

        // Asegurarse de que el directorio exista
        if (!File::exists(storage_path('wsdl'))) {
            File::makeDirectory(storage_path('wsdl'), 0755, true);
        }

        $username = config('c4c.auth.username');
        $password = config('c4c.auth.password');

        foreach ($wsdls as $name => $wsdl) {
            $this->info("Descargando WSDL de {$name}...");
            
            try {
                $response = Http::withBasicAuth($username, $password)
                    ->withOptions([
                        'verify' => false,
                        'timeout' => 30,
                    ])
                    ->get($wsdl['url']);
                
                if ($response->successful()) {
                    File::put($wsdl['path'], $response->body());
                    $this->info("WSDL de {$name} descargado correctamente en {$wsdl['path']}");
                } else {
                    $this->error("Error al descargar WSDL de {$name}: " . $response->status());
                }
            } catch (\Exception $e) {
                $this->error("Error al descargar WSDL de {$name}: " . $e->getMessage());
            }
        }

        $this->info('Proceso completado.');
    }
}
