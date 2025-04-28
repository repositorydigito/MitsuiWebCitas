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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_id')->unique()->comment('ID único del vehículo');
            $table->string('license_plate')->unique()->comment('Placa del vehículo');
            $table->string('model')->comment('Modelo del vehículo');
            $table->string('year')->comment('Año del modelo');
            $table->string('brand_code')->comment('Código de la marca (Z01=TOYOTA, Z02=LEXUS, Z03=HINO)');
            $table->string('brand_name')->nullable()->comment('Nombre de la marca');
            $table->string('color')->nullable()->comment('Color del vehículo');
            $table->string('vin')->nullable()->comment('Número de VIN');
            $table->string('engine_number')->nullable()->comment('Número de motor');
            $table->integer('mileage')->default(0)->comment('Kilometraje actual');
            $table->date('last_service_date')->nullable()->comment('Fecha del último servicio');
            $table->integer('last_service_mileage')->nullable()->comment('Kilometraje del último servicio');
            $table->date('next_service_date')->nullable()->comment('Fecha recomendada para el próximo servicio');
            $table->integer('next_service_mileage')->nullable()->comment('Kilometraje recomendado para el próximo servicio');
            $table->boolean('has_prepaid_maintenance')->default(false)->comment('Tiene mantenimiento prepagado');
            $table->date('prepaid_maintenance_expiry')->nullable()->comment('Fecha de vencimiento del mantenimiento prepagado');
            $table->string('image_url')->nullable()->comment('URL de la imagen del vehículo');
            $table->unsignedBigInteger('user_id')->nullable()->comment('ID del usuario propietario');
            $table->string('status')->default('active')->comment('Estado del vehículo (active, inactive)');
            $table->timestamps();
            $table->softDeletes();

            // Índices
            $table->index('brand_code');
            $table->index('user_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
