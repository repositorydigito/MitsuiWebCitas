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
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con el modelo
     */
    public function modelo()
    {
        return $this->belongsTo(Modelo::class);
    }

    /**
     * Obtener todos los años activos para un modelo específico
     */
    public static function getAnosActivosParaModelo($modeloId)
    {
        return self::where('model_id', $modeloId)
            ->where('activo', true)
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();
    }

    /**
     * Obtener todos los años activos para selectores
     */
    public static function getAnosActivosParaSelector()
    {
        return self::where('activo', true)
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->unique()
            ->toArray();
    }
}
