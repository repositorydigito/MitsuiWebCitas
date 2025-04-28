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
        Schema::create('appointment_additional_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignId('additional_service_id')->constrained('additional_services')->onDelete('cascade');
            $table->decimal('price', 10, 2)->nullable()->comment('Precio del servicio adicional al momento de la cita');
            $table->text('notes')->nullable()->comment('Notas específicas para este servicio adicional');
            $table->timestamps();

            // Asegurar que no haya duplicados
            $table->unique(['appointment_id', 'additional_service_id'], 'app_add_service_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointment_additional_service');
    }
};
