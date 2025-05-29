<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campana extends Model
{
    use HasFactory;

    protected $table = 'campaigns';

    protected $fillable = [
        'codigo',
        'titulo',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'all_day',
        'estado',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'all_day' => 'boolean',
    ];

    /**
     * Relación con la imagen de la campaña
     */
    public function imagen()
    {
        return $this->hasOne(CampanaImagen::class, 'campaign_id');
    }

    /**
     * Relación con los modelos seleccionados para la campaña
     */
    public function modelos()
    {
        return $this->belongsToMany(Modelo::class, 'campaign_models', 'campaign_id', 'model_id');
    }

    /**
     * Relación con los años seleccionados para la campaña
     */
    public function anos()
    {
        return $this->belongsToMany(ModeloAno::class, 'campaign_years', 'campaign_id', 'year');
    }

    /**
     * Relación con los locales seleccionados para la campaña
     */
    public function locales()
    {
        return $this->belongsToMany(Local::class, 'campaign_premises', 'campaign_id', 'premise_code', 'id', 'code')
            ->withPivot('premise_code');
    }
}
