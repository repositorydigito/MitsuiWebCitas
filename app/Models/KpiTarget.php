<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiTarget extends Model
{
    use HasFactory;

    protected $fillable = [
        'kpi_id',
        'brand',
        'local',
        'month',
        'year',
        'target_value',
    ];

    /**
     * Obtener el valor meta para un KPI específico basado en filtros
     *
     * @param string $kpiId
     * @param string|null $brand
     * @param string|null $local
     * @param int|null $month
     * @param int|null $year
     * @return int|null
     */
    public static function getTargetValue(string $kpiId, ?string $brand = null, ?string $local = null, ?int $month = null, ?int $year = null): ?int
    {
        // Crear un array de condiciones ordenadas por prioridad
        $conditions = [
            // Más específico primero
            ['brand' => $brand, 'local' => $local, 'month' => $month, 'year' => $year],
            ['brand' => $brand, 'local' => $local, 'month' => $month, 'year' => null],
            ['brand' => $brand, 'local' => $local, 'month' => null, 'year' => $year],
            ['brand' => $brand, 'local' => $local, 'month' => null, 'year' => null],
            ['brand' => $brand, 'local' => null, 'month' => $month, 'year' => $year],
            ['brand' => $brand, 'local' => null, 'month' => $month, 'year' => null],
            ['brand' => $brand, 'local' => null, 'month' => null, 'year' => $year],
            ['brand' => $brand, 'local' => null, 'month' => null, 'year' => null],
            ['brand' => null, 'local' => $local, 'month' => $month, 'year' => $year],
            ['brand' => null, 'local' => $local, 'month' => $month, 'year' => null],
            ['brand' => null, 'local' => $local, 'month' => null, 'year' => $year],
            ['brand' => null, 'local' => $local, 'month' => null, 'year' => null],
            ['brand' => null, 'local' => null, 'month' => $month, 'year' => $year],
            ['brand' => null, 'local' => null, 'month' => $month, 'year' => null],
            ['brand' => null, 'local' => null, 'month' => null, 'year' => $year],
            // Menos específico al final
            ['brand' => null, 'local' => null, 'month' => null, 'year' => null],
        ];

        // Buscar la mejor coincidencia
        foreach ($conditions as $condition) {
            $query = self::where('kpi_id', $kpiId);
            
            foreach ($condition as $key => $value) {
                if ($value === null) {
                    $query->whereNull($key);
                } else {
                    $query->where($key, $value);
                }
            }
            
            $target = $query->first();
            
            if ($target) {
                return (int)$target->target_value;
            }
        }
        
        return null;
    }
}