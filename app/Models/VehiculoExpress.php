<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VehiculoExpress extends Model
{
    use HasFactory;

    /**
     * La tabla asociada con el modelo.
     *
     * @var string
     */
    protected $table = 'vehicles_express';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'type',
        'model',
        'brand',
        'year',
        'maintenance',
        'premises',
        'is_active',
    ];

    /**
     * Los atributos que deben convertirse a tipos nativos.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'maintenance' => 'array', // Para manejar múltiples mantenimientos
        'year' => 'array', // Para manejar múltiples códigos de motor
    ];
}
