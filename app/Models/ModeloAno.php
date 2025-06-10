<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModeloAno extends Model
{
    use HasFactory;

    protected $table = 'model_years';

    protected $fillable = [
        'model_id',
        'year',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relación con el modelo
     */
    public function modelo()
    {
        return $this->belongsTo(Modelo::class, 'model_id');
    }

    /**
     * Obtener todos los años activos para un modelo específico
     */
    public static function getAnosActivosParaModelo($modeloId)
    {
        return self::where('model_id', $modeloId)
            ->where('is_active', true)
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
    }

    /**
     * Obtener todos los años activos para selectores
     */
    public static function getAnosActivosParaSelector()
    {
        return self::where('is_active', true)
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->unique()
            ->toArray();
    }

    /**
     * Crear años por defecto para todos los modelos que no tengan años
     */
    public static function crearAnosPorDefecto()
    {
        $modelos = \App\Models\Modelo::where('is_active', true)->get();
        $anosDefecto = ['2018', '2019', '2020', '2021', '2022', '2023', '2024', '2025'];

        foreach ($modelos as $modelo) {
            // Verificar si el modelo ya tiene años
            $tieneAnos = self::where('model_id', $modelo->id)->exists();

            if (!$tieneAnos) {
                // Crear años por defecto para este modelo
                foreach ($anosDefecto as $ano) {
                    self::create([
                        'model_id' => $modelo->id,
                        'year' => $ano,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }
}
