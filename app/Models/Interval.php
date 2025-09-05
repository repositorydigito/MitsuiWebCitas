<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interval extends Model
{
    use HasFactory;

    protected $fillable = [
        'local_id',
        'min_reservation_time',
        'min_time_unit',
        'max_reservation_time',
        'max_time_unit',
    ];

    /**
     * Obtiene el local asociado a este intervalo.
     */
    public function local(): BelongsTo
    {
        return $this->belongsTo(Local::class);
    }
}
