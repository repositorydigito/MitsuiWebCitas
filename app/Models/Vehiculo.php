<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    // No tiene tabla real en la base de datos
    protected $table = 'vehiculos';

    // Estos son los campos que esperamos del XML
    protected $fillable = [
        'vhclie',
        'numpla',
        'aniomod',
        'modver',
    ];

    // No usamos timestamps ya que es solo para representar los datos del SOAP
    public $timestamps = false;
}
