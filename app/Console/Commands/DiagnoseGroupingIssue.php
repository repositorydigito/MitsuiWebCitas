<?php

namespace App\Console\Commands;

use App\Models\ModelMaintenance;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class DiagnoseGroupingIssue extends Command
{
    protected $signature = 'diagnose:grouping';
    protected $description = 'Diagnose grouping issues in ModelMaintenance data';

    public function handle()
    {
        $this->info('🔍 Diagnosticando problema de agrupación en ModelMaintenance...');
        $this->newLine();

        // Obtener todos los datos tal como lo hace el componente
        $mantenimientos = ModelMaintenance::ordenadoPorModelo()->get();
        
        $this->info("📊 Total de registros en base de datos: {$mantenimientos->count()}");
        $this->newLine();

        // Convertir a array como en el componente
        $mantenimientosArray = $mantenimientos->map(function ($mantenimiento) {
            return [
                'id' => $mantenimiento->id,
                'name' => $mantenimiento->name,
                'code' => $mantenimiento->code,
                'brand' => $mantenimiento->brand,
                'tipo_valor_trabajo' => $mantenimiento->tipo_valor_trabajo,
                'kilometers' => $mantenimiento->kilometers,
                'description' => $mantenimiento->description,
                'is_active' => $mantenimiento->is_active,
            ];
        });

        // Mostrar muestra de datos
        $this->info('📋 Muestra de datos (primeros 5 registros):');
        $sample = $mantenimientosArray->take(5);
        $this->table(
            ['ID', 'Name', 'Code', 'Brand', 'Tipo Valor Trabajo', 'Kilometers', 'Active'],
            $sample->map(function($item) {
                return [
                    $item['id'],
                    $item['name'],
                    $item['code'],
                    $item['brand'],
                    $item['tipo_valor_trabajo'],
                    $item['kilometers'],
                    $item['is_active'] ? 'Yes' : 'No'
                ];
            })->toArray()
        );
        $this->newLine();

        // Analizar claves de agrupación
        $groupingKeys = $mantenimientosArray->map(function($item) {
            return $item['brand'] . '|' . $item['code'] . '|' . $item['kilometers'];
        });

        $uniqueGroupingKeys = $groupingKeys->unique();
        $this->info("🔑 Claves de agrupación únicas: {$uniqueGroupingKeys->count()}");
        $this->info("📝 Total de registros: {$mantenimientosArray->count()}");
        
        if ($uniqueGroupingKeys->count() === $mantenimientosArray->count()) {
            $this->error('❌ PROBLEMA DETECTADO: Cada registro tiene una clave de agrupación única!');
            $this->error('   Esto significa que no hay registros para agrupar.');
            $this->error('   Cada combinación de (brand, code, kilometers) es única.');
        } else {
            $this->info('✅ Hay registros que pueden ser agrupados.');
        }
        $this->newLine();

        // Probar la agrupación
        $grupos = $mantenimientosArray->groupBy(['brand', 'code', 'kilometers']);
        
        $this->info("📊 Resultado de agrupación:");
        $this->info("   Grupos de marca: {$grupos->count()}");
        
        $totalGroupsAfterAllLevels = 0;
        foreach ($grupos as $marca => $porCodigo) {
            $this->info("   Marca '{$marca}': {$porCodigo->count()} códigos");
            foreach ($porCodigo as $codigo => $porKilometros) {
                $this->info("     Código '{$codigo}': {$porKilometros->count()} grupos de kilómetros");
                foreach ($porKilometros as $kilometros => $mantenimientos) {
                    $totalGroupsAfterAllLevels++;
                    $tipos = $mantenimientos->pluck('tipo_valor_trabajo')->filter()->implode(', ');
                    $this->info("       {$kilometros} km: {$mantenimientos->count()} registros -> Tipos: {$tipos}");
                }
            }
        }
        
        $this->newLine();
        $this->info("🎯 Total de filas que se mostrarían en la tabla: {$totalGroupsAfterAllLevels}");
        
        if ($totalGroupsAfterAllLevels === $mantenimientosArray->count()) {
            $this->error('❌ CONFIRMADO: No hay agrupación efectiva happening!');
            $this->error('   Cada registro se muestra como una fila separada.');
            $this->newLine();
            $this->info('💡 POSIBLES CAUSAS:');
            $this->info('   1. Los datos en producción no tienen registros duplicados con misma combinación');
            $this->info('   2. La estructura de datos cambió después de las migraciones');
            $this->info('   3. Faltan datos que deberían estar agrupados');
        } else {
            $this->info('✅ La agrupación está funcionando correctamente.');
        }

        // Analizar duplicados potenciales
        $this->newLine();
        $this->info('🔍 Analizando potenciales duplicados...');
        
        $potentialGroups = $mantenimientosArray->groupBy(function($item) {
            return $item['brand'] . '|' . $item['code'] . '|' . $item['kilometers'];
        })->filter(function($group) {
            return $group->count() > 1;
        });
        
        if ($potentialGroups->count() > 0) {
            $this->info("✅ Encontrados {$potentialGroups->count()} grupos con múltiples registros:");
            foreach ($potentialGroups as $key => $group) {
                $parts = explode('|', $key);
                $this->info("   Grupo: {$parts[0]} | {$parts[1]} | {$parts[2]} km ({$group->count()} registros)");
                foreach ($group as $item) {
                    $this->info("     - ID {$item['id']}: {$item['tipo_valor_trabajo']}");
                }
            }
        } else {
            $this->error('❌ No se encontraron grupos con múltiples registros.');
            $this->error('   Esto confirma que cada combinación de (brand, code, kilometers) es única.');
        }

        $this->newLine();
        $this->info('📝 RECOMENDACIONES:');
        
        if ($potentialGroups->count() === 0) {
            $this->info('1. Verificar si la migración cambió la estructura de datos inadvertidamente');
            $this->info('2. Revisar si en local hay datos de prueba que permiten agrupación');
            $this->info('3. Considerar cambiar la lógica de agrupación o la interfaz');
            $this->info('4. Verificar si faltan datos en producción que deberían existir');
        }

        return Command::SUCCESS;
    }
}