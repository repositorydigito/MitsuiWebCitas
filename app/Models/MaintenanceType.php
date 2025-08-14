<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceType extends Model
{
    use HasFactory;

    protected $table = 'maintenance_types';

    protected $fillable = [
        'name',
        'code',
        'brand',
        'description',
        'kilometers',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'kilometers' => 'integer',
    ];

    /**
     * Obtener todos los tipos de mantenimiento activos para selectores
     */
    public static function getActivosParaSelector()
    {
        return self::where('is_active', true)
            ->orderBy('kilometers')
            ->pluck('name', 'id')
            ->toArray();
    }

    /**
     * Scope para filtrar solo los activos
     */
    public function scopeActivos($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ordenar por kilómetros
     */
    public function scopeOrdenadoPorKilometros($query)
    {
        return $query->orderBy('kilometers');
    }
}
