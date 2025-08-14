<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CenterOrganizationMapping extends Model
{
    protected $table = 'center_organization_mapping';
    
    protected $fillable = [
        'center_code',
        'brand_code',
        'sales_organization_id',
        'sales_office_id',
        'sales_group_id',
        'distribution_channel_code',
        'division_code',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    /**
     * Scope para búsqueda rápida por centro y marca
     */
    public function scopeForCenterAndBrand(Builder $query, string $centerCode, string $brandCode): Builder
    {
        return $query->where('center_code', $centerCode)
                     ->where('brand_code', $brandCode)
                     ->where('is_active', true);
    }

    /**
     * Scope para obtener centros por marca
     */
    public function scopeForBrand(Builder $query, string $brandCode): Builder
    {
        return $query->where('brand_code', $brandCode)
                     ->where('is_active', true);
    }

    /**
     * Obtener todos los centros disponibles para una marca
     */
    public static function getCentersForBrand(string $brandCode): array
    {
        return static::forBrand($brandCode)
                    ->pluck('center_code')
                    ->unique()
                    ->values()
                    ->toArray();
    }

    /**
     * Verificar si existe mapeo para centro+marca
     */
    public static function mappingExists(string $centerCode, string $brandCode): bool
    {
        return static::forCenterAndBrand($centerCode, $brandCode)->exists();
    }
}
