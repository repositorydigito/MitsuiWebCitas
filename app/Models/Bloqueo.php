<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Bloqueo extends Model
{
    use HasFactory;

    protected $table = 'bloqueos';

    protected $fillable = [
        'local',
        'fecha_inicio',
        'fecha_fin',
        'hora_inicio',
        'hora_fin',
        'todo_dia',
        'comentarios',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'todo_dia' => 'boolean',
    ];

    /**
     * Verifica si un bloqueo afecta a una fecha y hora específicas
     *
     * @param string $local
     * @param Carbon $fecha
     * @param string $hora
     * @return bool
     */
    public static function estaBloquedo($local, Carbon $fecha, string $hora)
    {
        $fechaStr = $fecha->format('Y-m-d');

        // Convertir la hora a minutos para facilitar la comparación
        list($horaH, $horaM) = explode(':', $hora);
        $horaMinutos = ((int)$horaH * 60) + (int)$horaM;

        // Buscar bloqueos que afecten a esta fecha y local
        $bloqueos = self::where('local', $local)
            ->where(function ($query) use ($fechaStr) {
                $query->where('fecha_inicio', '<=', $fechaStr)
                      ->where('fecha_fin', '>=', $fechaStr);
            })
            ->get();

        // Verificar si alguno de los bloqueos afecta a esta hora
        foreach ($bloqueos as $bloqueo) {
            // Si es todo el día, está bloqueado
            if ($bloqueo->todo_dia) {
                return true;
            }

            // Convertir las horas de inicio y fin a minutos
            list($inicioH, $inicioM) = explode(':', $bloqueo->hora_inicio);
            list($finH, $finM) = explode(':', $bloqueo->hora_fin);
            $inicioMinutos = ((int)$inicioH * 60) + (int)$inicioM;
            $finMinutos = ((int)$finH * 60) + (int)$finM;

            // Verificar si la hora actual está dentro del rango de bloqueo
            if ($horaMinutos >= $inicioMinutos && $horaMinutos < $finMinutos) {
                return true;
            }
        }

        return false;
    }
}
