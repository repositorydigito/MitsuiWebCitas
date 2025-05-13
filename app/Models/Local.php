<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Local extends Model
{
    use HasFactory;

    protected $table = 'locales';

    protected $fillable = [
        'codigo',
        'nombre',
        'direccion',
        'telefono',
        'horario_apertura',
        'horario_cierre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Obtener todos los locales activos para selectores
     */
    public static function getActivosParaSelector()
    {
        return self::where('activo', true)
            ->orderBy('nombre')
            ->pluck('nombre', 'codigo')
            ->toArray();
    }
}
