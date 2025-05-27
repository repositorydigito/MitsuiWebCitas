<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopUp extends Model
{
    protected $fillable = [
        'nombre',
        'imagen_path',
        'medidas',
        'formato',
        'url_wp',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];
}
