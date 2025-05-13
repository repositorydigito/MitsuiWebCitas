<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampanaImagen extends Model
{
    use HasFactory;

    protected $table = 'campana_imagenes';

    protected $fillable = [
        'campana_id',
        'ruta',
        'nombre_original',
        'mime_type',
        'tamano',
    ];

    /**
     * Relación con la campaña
     */
    public function campana()
    {
        return $this->belongsTo(Campana::class);
    }
}
