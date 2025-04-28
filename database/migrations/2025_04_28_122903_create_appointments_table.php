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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number')->unique()->comment('Número de cita');
            $table->string('c4c_uuid')->nullable()->comment('UUID de la cita en SAP C4C');

            // Relaciones
            $table->foreignId('vehicle_id')->constrained('vehicles')->comment('ID del vehículo');
            $table->foreignId('service_center_id')->constrained('service_centers')->comment('ID del centro de servicio');
            $table->foreignId('service_type_id')->constrained('service_types')->comment('ID del tipo de servicio');

            // Datos del cliente
            $table->string('customer_ruc')->default('20605414410')->comment('RUC del cliente');
            $table->string('customer_name')->comment('Nombre del cliente');
            $table->string('customer_last_name')->comment('Apellido del cliente');
            $table->string('customer_email')->nullable()->comment('Email del cliente');
            $table->string('customer_phone')->nullable()->comment('Teléfono del cliente');

            // Datos de la cita
            $table->date('appointment_date')->comment('Fecha de la cita');
            $table->time('appointment_time')->comment('Hora de la cita en formato HH:MM:SS');
            $table->dateTime('appointment_end_time')->nullable()->comment('Hora de finalización estimada');
            $table->string('service_mode')->default('regular')->comment('Modalidad del servicio (regular, express)');
            $table->string('maintenance_type')->nullable()->comment('Tipo de mantenimiento (10000 km, 20000 km, etc.)');
            $table->text('comments')->nullable()->comment('Comentarios u observaciones');

            // Estado de la cita
            $table->string('status')->default('pending')->comment('Estado de la cita (pending, confirmed, in_progress, completed, cancelled)');
            $table->string('c4c_status')->nullable()->comment('Estado de la cita en SAP C4C');
            $table->boolean('is_synced')->default(false)->comment('Indica si la cita está sincronizada con SAP C4C');
            $table->dateTime('synced_at')->nullable()->comment('Fecha y hora de la última sincronización');

            // Campos de auditoría
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
