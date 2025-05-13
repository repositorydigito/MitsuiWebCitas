<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModeloAno extends Model
{
    use HasFactory;

    protected $table = 'modelo_anos';

    protected $fillable = [
        'modelo_id',
        'ano',
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
        return self::where('modelo_id', $modeloId)
            ->where('activo', true)
            ->orderBy('ano', 'desc')
            ->pluck('ano')
            ->toArray();
    }

    /**
     * Obtener todos los años activos para selectores
     */
    public static function getAnosActivosParaSelector()
    {
        return self::where('activo', true)
            ->orderBy('ano', 'desc')
            ->pluck('ano')
            ->unique()
            ->toArray();
    }
}
