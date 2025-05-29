<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Local extends Model
{
    use HasFactory;

    protected $table = 'premises';

    protected $fillable = [
        'code',
        'name',
        'address',
        'phone',
        'opening_time',
        'closing_time',
        'is_active',
        'waze_url',
        'maps_url',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Obtener todos los locales activos para selectores
     */
    public static function getActivosParaSelector()
    {
        return self::where('is_active', true)
            ->orderBy('name')
            ->pluck('name', 'code')
            ->toArray();
    }
}
