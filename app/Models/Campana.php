<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campana extends Model
{
    use HasFactory;

    protected $table = 'campanas';

    protected $fillable = [
        'codigo',
        'titulo',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'todo_dia',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'todo_dia' => 'boolean',
    ];

    /**
     * Relación con la imagen de la campaña
     */
    public function imagen()
    {
        return $this->hasOne(CampanaImagen::class);
    }

    /**
     * Relación con los modelos seleccionados para la campaña
     */
    public function modelos()
    {
        return $this->belongsToMany(Modelo::class, 'campana_modelos');
    }

    /**
     * Relación con los años seleccionados para la campaña
     */
    public function anos()
    {
        return $this->belongsToMany(ModeloAno::class, 'campana_anos', 'campana_id', 'ano');
    }

    /**
     * Relación con los locales seleccionados para la campaña
     */
    public function locales()
    {
        return $this->belongsToMany(Local::class, 'campana_locales', 'campana_id', 'local_codigo', 'id', 'codigo')
            ->withPivot('local_codigo');
    }
}
