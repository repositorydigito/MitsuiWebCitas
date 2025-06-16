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
        Schema::table('vehicles', function (Blueprint $table) {
            // Agregar campo user_id como foreign key
            $table->foreignId('user_id')
                ->after('id')
                ->nullable()
                ->constrained('users')
                ->onDelete('cascade');

            // Agregar campos adicionales que faltan en la estructura actual
            $table->string('color')->nullable()->after('brand_code');
            $table->string('vin')->nullable()->after('color');
            $table->string('engine_number')->nullable()->after('vin');
            $table->integer('mileage')->nullable()->after('engine_number');
            $table->date('last_service_date')->nullable()->after('mileage');
            $table->integer('last_service_mileage')->nullable()->after('last_service_date');
            $table->date('next_service_date')->nullable()->after('last_service_mileage');
            $table->integer('next_service_mileage')->nullable()->after('next_service_date');
            $table->boolean('has_prepaid_maintenance')->default(false)->after('next_service_mileage');
            $table->date('prepaid_maintenance_expiry')->nullable()->after('has_prepaid_maintenance');
            $table->string('image_url')->nullable()->after('prepaid_maintenance_expiry');
            $table->string('status')->default('active')->after('image_url');

            // Agregar índices para mejor rendimiento
            $table->index(['user_id', 'status']);
            $table->index(['brand_code', 'status']);
            $table->index('license_plate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            // Eliminar índices primero
            $table->dropIndex(['user_id', 'status']);
            $table->dropIndex(['brand_code', 'status']);
            $table->dropIndex(['license_plate']);

            // Eliminar foreign key constraint
            $table->dropForeign(['user_id']);

            // Eliminar columnas
            $table->dropColumn([
                'user_id',
                'color',
                'vin',
                'engine_number',
                'mileage',
                'last_service_date',
                'last_service_mileage',
                'next_service_date',
                'next_service_mileage',
                'has_prepaid_maintenance',
                'prepaid_maintenance_expiry',
                'image_url',
                'status',
            ]);
        });
    }
};
