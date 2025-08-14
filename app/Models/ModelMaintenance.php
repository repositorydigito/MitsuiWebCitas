<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ModelMaintenance extends Model
{
    use HasFactory;

    protected $table = 'model_maintenances';

    protected $fillable = [
        'name',
        'code',
        'brand',
        'tipo_valor_trabajo',
        'kilometers',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'kilometers' => 'integer',
    ];

    /**
     * Obtener todos los mantenimientos activos para selectores
     */
    public static function getActivosParaSelector()
    {
        return self::where('is_active', true)
            ->orderBy('brand')
            ->orderBy('tipo_valor_trabajo')
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
     * Scope para ordenar por marca, tipo_valor_trabajo y kilómetros
     */
    public function scopeOrdenadoPorModelo($query)
    {
        return $query->orderBy('brand')->orderBy('tipo_valor_trabajo')->orderBy('kilometers');
    }

    /**
     * Scope para filtrar por marca
     */
    public function scopePorMarca($query, $brand)
    {
        return $query->where('brand', $brand);
    }

    /**
     * Scope para filtrar por tipo_valor_trabajo (coincidencia exacta)
     */
    public function scopePorTipoValorTrabajo($query, $tipoValorTrabajo)
    {
        return $query->where('tipo_valor_trabajo', $tipoValorTrabajo);
    }

    /**
     * Scope para filtrar por tipo_valor_trabajo con coincidencia parcial
     */
    public function scopePorTipoValorTrabajoParcial($query, $tipoValorTrabajo)
    {
        return $query->where('tipo_valor_trabajo', 'LIKE', '%' . $tipoValorTrabajo . '%');
    }



    /**
     * Obtener mantenimientos por marca y tipo_valor_trabajo específico
     */
    public static function getPorMarcaYTipoValorTrabajo($brand, $tipoValorTrabajo)
    {
        return self::where('brand', $brand)
            ->where('tipo_valor_trabajo', $tipoValorTrabajo)
            ->activos()
            ->ordenadoPorModelo()
            ->get();
    }



    /**
     * Obtener todos los tipos_valor_trabajo únicos por marca
     */
    public static function getTiposValorTrabajoPorMarca($brand = null)
    {
        $query = self::select('tipo_valor_trabajo')->distinct()->whereNotNull('tipo_valor_trabajo');
        
        if ($brand) {
            $query->where('brand', $brand);
        }
        
        return $query->orderBy('tipo_valor_trabajo')->pluck('tipo_valor_trabajo')->toArray();
    }



    /**
     * Verificar si existe un mantenimiento para marca, tipo_valor_trabajo y kilómetros específicos
     */
    public static function existeMantenimientoPorTipoValorTrabajo($brand, $tipoValorTrabajo, $kilometers, $excludeId = null)
    {
        $query = self::where('brand', $brand)
            ->where('tipo_valor_trabajo', $tipoValorTrabajo)
            ->where('kilometers', $kilometers);
            
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }


}