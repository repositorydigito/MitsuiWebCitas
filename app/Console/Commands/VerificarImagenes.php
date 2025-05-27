<?php

namespace App\Console\Commands;

use App\Models\Campana;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerificarImagenes extends Command
{
    protected $signature = 'verificar:imagenes';

    protected $description = 'Verifica las imágenes de campañas en la base de datos';

    public function handle()
    {
        $this->info('Verificando imágenes de campañas...');

        // Verificar imágenes en la tabla campaign_images
        $imagenes = DB::table('campaign_images')->get();

        $this->info('Total de imágenes en campaign_images: '.$imagenes->count());

        foreach ($imagenes as $imagen) {
            $this->line("ID: {$imagen->id} | Campaign ID: {$imagen->campaign_id}");
            $this->line("  Ruta: {$imagen->ruta}");
            $this->line("  Nombre original: {$imagen->nombre_original}");

            // Verificar si el archivo existe
            $rutasAVerificar = [
                storage_path('app/'.$imagen->ruta),
                storage_path('app/private/'.$imagen->ruta),
                storage_path('app/private/public/images/campanas/'.basename($imagen->ruta)),
                public_path($imagen->ruta),
            ];

            $existe = false;
            $rutaEncontrada = null;
            foreach ($rutasAVerificar as $ruta) {
                if (file_exists($ruta)) {
                    $existe = true;
                    $rutaEncontrada = $ruta;
                    break;
                }
            }

            $this->line('  Archivo existe: '.($existe ? "Sí ({$rutaEncontrada})" : 'No'));

            // Generar URL
            $url = route('imagen.campana', ['id' => $imagen->campaign_id]);
            $this->line("  URL: {$url}");
            $this->line('---');
        }

        // Verificar campañas con imágenes
        $this->info("\nVerificando campañas con sus imágenes...");
        $campanas = Campana::with('imagen')->get();

        foreach ($campanas as $campana) {
            $this->line("Campaña: {$campana->codigo} - {$campana->titulo}");
            if ($campana->imagen) {
                $this->line("  Tiene imagen: Sí (ID: {$campana->imagen->id})");
                $url = route('imagen.campana', ['id' => $campana->id]);
                $this->line("  URL: {$url}");
            } else {
                $this->line('  Tiene imagen: No');
            }
            $this->line('---');
        }

        return 0;
    }
}
