<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdditionalService extends Model
{
    use HasFactory;

    protected $table = 'additional_services';

    protected $fillable = [
        'name',
        'code',
        'brand',
        'description',
        'price',
        'duration_minutes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'brand' => 'array',
    ];

    /**
     * Obtener todos los servicios adicionales activos para selectores
     */
    public static function getActivosParaSelector()
    {
        return self::where('is_active', true)
            ->orderBy('name')
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
     * Scope para ordenar por nombre
     */
    public function scopeOrdenadoPorNombre($query)
    {
        return $query->orderBy('name');
    }

    /**
     * Scope para filtrar por marca específica
     */
    public function scopePorMarca($query, $marca)
    {
        // Normalizar la marca para que coincida con el formato almacenado
        $marcaNormalizada = ucfirst(strtolower($marca));
        return $query->whereJsonContains('brand', $marcaNormalizada);
    }

    /**
     * Verificar si el servicio está disponible para una marca específica
     */
    public function estaDisponibleParaMarca($marca)
    {
        return in_array($marca, $this->brand ?? []);
    }

    /**
     * Obtener las marcas como string separado por comas para mostrar
     */
    public function getMarcasTextoAttribute()
    {
        return is_array($this->brand) ? implode(', ', $this->brand) : '';
    }
}
