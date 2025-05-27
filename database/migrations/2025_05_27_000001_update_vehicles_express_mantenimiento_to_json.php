<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero, convertir los datos existentes a formato JSON
        $vehiculos = DB::table('vehicles_express')->get();

        foreach ($vehiculos as $vehiculo) {
            if ($vehiculo->mantenimiento && ! is_array(json_decode($vehiculo->mantenimiento, true))) {
                // Convertir el mantenimiento actual a un array JSON
                $mantenimientoArray = [$vehiculo->mantenimiento];
                DB::table('vehicles_express')
                    ->where('id', $vehiculo->id)
                    ->update(['mantenimiento' => json_encode($mantenimientoArray)]);
            }
        }

        // Cambiar el tipo de columna a JSON
        Schema::table('vehicles_express', function (Blueprint $table) {
            $table->json('mantenimiento')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Convertir de vuelta a string tomando el primer elemento del array
        $vehiculos = DB::table('vehicles_express')->get();

        foreach ($vehiculos as $vehiculo) {
            $mantenimientos = json_decode($vehiculo->mantenimiento, true);
            if (is_array($mantenimientos) && ! empty($mantenimientos)) {
                $primerMantenimiento = $mantenimientos[0];
                DB::table('vehicles_express')
                    ->where('id', $vehiculo->id)
                    ->update(['mantenimiento' => $primerMantenimiento]);
            }
        }

        Schema::table('vehicles_express', function (Blueprint $table) {
            $table->string('mantenimiento')->change();
        });
    }
};
