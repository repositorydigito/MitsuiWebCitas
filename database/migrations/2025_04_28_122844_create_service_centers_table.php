<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('service_centers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Código del centro de servicio');
            $table->string('name')->comment('Nombre del centro de servicio');
            $table->string('address')->comment('Dirección del centro de servicio');
            $table->string('city')->nullable()->comment('Ciudad del centro de servicio');
            $table->string('phone')->nullable()->comment('Teléfono del centro de servicio');
            $table->string('email')->nullable()->comment('Email del centro de servicio');
            $table->string('maps_url')->nullable()->comment('URL de Google Maps');
            $table->string('waze_url')->nullable()->comment('URL de Waze');
            $table->boolean('is_active')->default(true)->comment('Indica si el centro está activo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_centers');
    }
};
