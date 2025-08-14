<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interval extends Model
{
    use HasFactory;

    protected $fillable = [
        'min_reservation_time',
        'min_time_unit',
        'max_reservation_time',
        'max_time_unit',
    ];
}
