<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bloqueo extends Model
{
    use HasFactory;

    protected $table = 'blockades';

    protected $fillable = [
        'local',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'all_day',
        'comentarios',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'all_day' => 'boolean',
    ];

    /**
     * Verifica si un bloqueo afecta a una fecha y hora específicas
     *
     * @param  string  $local
     * @return bool
     */
    public static function estaBloquedo($local, Carbon $fecha, string $hora)
    {
        $fechaStr = $fecha->format('Y-m-d');

        // Convertir la hora a minutos para facilitar la comparación
        [$horaH, $horaM] = explode(':', $hora);
        $horaMinutos = ((int) $horaH * 60) + (int) $horaM;

        // Buscar bloqueos que afecten a esta fecha y local
        $bloqueos = self::where('local', $local)
            ->where(function ($query) use ($fechaStr) {
                $query->where('start_date', '<=', $fechaStr)
                    ->where('end_date', '>=', $fechaStr);
            })
            ->get();

        // Verificar si alguno de los bloqueos afecta a esta hora
        foreach ($bloqueos as $bloqueo) {
            // Si es todo el día, está bloqueado
            if ($bloqueo->all_day) {
                return true;
            }

            // Convertir las horas de inicio y fin a minutos
            [$inicioH, $inicioM] = explode(':', $bloqueo->start_time);
            [$finH, $finM] = explode(':', $bloqueo->end_time);
            $inicioMinutos = ((int) $inicioH * 60) + (int) $inicioM;
            $finMinutos = ((int) $finH * 60) + (int) $finM;

            // Verificar si la hora actual está dentro del rango de bloqueo
            if ($horaMinutos >= $inicioMinutos && $horaMinutos < $finMinutos) {
                return true;
            }
        }

        return false;
    }
}
