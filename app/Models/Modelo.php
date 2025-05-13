<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modelo extends Model
{
    use HasFactory;

    protected $table = 'modelos';

    protected $fillable = [
        'codigo',
        'nombre',
        'marca',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con los años del modelo
     */
    public function anos()
    {
        return $this->hasMany(ModeloAno::class);
    }

    /**
     * Obtener todos los modelos activos para selectores
     */
    public static function getActivosParaSelector()
    {
        return self::where('activo', true)
            ->orderBy('nombre')
            ->pluck('nombre', 'codigo')
            ->toArray();
    }
}
