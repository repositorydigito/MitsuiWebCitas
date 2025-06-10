<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ModeloAno;
use App\Models\Modelo;

class PoblarAnos extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mitsui:poblar-anos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Poblar años por defecto para todos los modelos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Iniciando población de años...');

        try {
            // Obtener todos los modelos activos
            $modelos = Modelo::where('is_active', true)->get();
            $anosDefecto = ['2018', '2019', '2020', '2021', '2022', '2023', '2024', '2025'];
            
            $this->info("Encontrados {$modelos->count()} modelos activos");
            
            $modelosActualizados = 0;
            $anosCreados = 0;

            foreach ($modelos as $modelo) {
                $this->line("Procesando modelo: {$modelo->name} ({$modelo->code})");
                
                // Verificar si el modelo ya tiene años
                $anosExistentes = ModeloAno::where('model_id', $modelo->id)->count();
                
                if ($anosExistentes == 0) {
                    $this->warn("  - Modelo sin años, creando años por defecto...");
                    
                    // Crear años por defecto para este modelo
                    foreach ($anosDefecto as $ano) {
                        ModeloAno::create([
                            'model_id' => $modelo->id,
                            'year' => $ano,
                            'is_active' => true,
                        ]);
                        $anosCreados++;
                    }
                    
                    $modelosActualizados++;
                    $this->info("  - Creados " . count($anosDefecto) . " años para {$modelo->name}");
                } else {
                    $this->line("  - Modelo ya tiene {$anosExistentes} años");
                }
            }

            $this->info("\n✅ Proceso completado:");
            $this->info("   - Modelos actualizados: {$modelosActualizados}");
            $this->info("   - Años creados: {$anosCreados}");
            
            // Verificar años disponibles
            $anosDisponibles = ModeloAno::where('is_active', true)
                ->pluck('year')
                ->unique()
                ->sort()
                ->values()
                ->toArray();
                
            $this->info("   - Años disponibles: " . implode(', ', $anosDisponibles));

        } catch (\Exception $e) {
            $this->error("Error al poblar años: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
