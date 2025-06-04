<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PopUp extends Model
{
    protected $fillable = [
        'name',
        'image_path',
        'sizes',
        'format',
        'url_wp',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
