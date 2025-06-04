<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Modelo extends Model
{
    use HasFactory;

    protected $table = 'models';

    protected $fillable = [
        'code',
        'name',
        'brand',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relación con los años del modelo
     */
    public function anos()
    {
        return $this->hasMany(ModeloAno::class, 'model_id');
    }

    /**
     * Obtener todos los modelos activos para selectores
     */
    public static function getActivosParaSelector()
    {
        return self::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'code')
            ->toArray();
    }
}
