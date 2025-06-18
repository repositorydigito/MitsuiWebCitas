<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Esta migración crea toda la estructura de la base de datos del sistema Mitsui
     * basada en el estado actual de las tablas existentes.
     */
    public function up(): void
    {
        // Tabla de locales/sucursales (premises)
        Schema::create('premises', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Código del local
            $table->string('name'); // Nombre del local
            $table->text('address')->nullable(); // Dirección
            $table->string('location')->nullable(); // Ubicación/teléfono
            $table->boolean('is_active')->default(true); // Estado activo
            $table->string('waze_url')->nullable(); // URL de Waze
            $table->string('maps_url')->nullable(); // URL de Google Maps
            $table->timestamps();
        });

        // Tabla de modelos de vehículos (models)
        Schema::create('models', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // Código del modelo
            $table->string('name'); // Nombre del modelo
            $table->string('brand')->nullable(); // Marca
            $table->text('description')->nullable(); // Descripción
            $table->boolean('is_active')->default(true); // Estado activo
            $table->timestamps();
        });

        // Tabla de años por modelo (model_years)
        Schema::create('model_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('models')->onDelete('cascade');
            $table->year('year'); // Año del modelo
            $table->boolean('is_active')->default(true); // Estado activo
            $table->timestamps();

            $table->unique(['model_id', 'year']);
        });

        // Tabla de campañas (campaigns)
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // Título de la campaña
            $table->text('description')->nullable(); // Descripción
            $table->string('city')->nullable(); // Ciudad
            $table->string('status')->default('active'); // Estado
            $table->date('start_date')->nullable(); // Fecha de inicio
            $table->date('end_date')->nullable(); // Fecha de fin
            $table->boolean('is_active')->default(true); // Estado activo
            $table->timestamps();
        });

        // Tabla de imágenes de campañas (campaign_images)
        Schema::create('campaign_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->string('image_path'); // Ruta de la imagen
            $table->string('alt_text')->nullable(); // Texto alternativo
            $table->boolean('is_primary')->default(false); // Imagen principal
            $table->timestamps();
        });

        // Tabla de relación campañas-modelos (campaign_models)
        Schema::create('campaign_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->foreignId('model_id')->constrained('models')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['campaign_id', 'model_id']);
        });

        // Tabla de relación campañas-locales (campaign_premises)
        Schema::create('campaign_premises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->string('premise_code'); // Código del local (no FK para flexibilidad)
            $table->timestamps();

            $table->unique(['campaign_id', 'premise_code']);
        });

        // Tabla de relación campañas-años (campaign_years)
        Schema::create('campaign_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->year('year'); // Año directamente, no FK
            $table->timestamps();

            $table->unique(['campaign_id', 'year']);
        });

        // Tabla de bloqueos de horarios (blockades)
        Schema::create('blockades', function (Blueprint $table) {
            $table->id();
            $table->string('premises'); // Código del local
            $table->date('start_date'); // Fecha de inicio
            $table->date('end_date'); // Fecha de fin
            $table->time('start_time')->nullable(); // Hora de inicio
            $table->time('end_time')->nullable(); // Hora de fin
            $table->boolean('all_day')->default(false); // Bloqueo de todo el día
            $table->text('comments')->nullable(); // Comentarios del bloqueo
            $table->timestamps();
        });

        // Tabla de vehículos (vehicles)
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->string('vehicle_id')->unique(); // ID del vehículo
            $table->string('license_plate'); // Placa
            $table->string('model'); // Modelo
            $table->string('year')->nullable(); // Año
            $table->string('brand_name')->nullable(); // Nombre de la marca
            $table->string('brand_code')->nullable(); // Código de la marca
            $table->string('customer_name')->nullable(); // Nombre del cliente
            $table->string('customer_phone')->nullable(); // Teléfono del cliente
            $table->string('customer_email')->nullable(); // Email del cliente
            $table->timestamps();
        });

        // Tabla de servicios express (vehicles_express)
        Schema::create('vehicles_express', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable(); // Código del servicio express
            $table->string('type')->nullable(); // Tipo del servicio express
            $table->string('model'); // Modelo del vehículo
            $table->string('brand'); // Marca del vehículo
            $table->year('year'); // Año del vehículo
            $table->string('premises'); // Código del local donde se ofrece
            $table->json('maintenance'); // Tipos de mantenimiento (JSON)
            $table->boolean('is_active')->default(true); // Estado activo
            $table->timestamps();
        });

        // Tabla de pop-ups promocionales (pop_ups)
        Schema::create('pop_ups', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del pop-up
            $table->string('image_path')->nullable(); // Ruta de la imagen
            $table->string('sizes')->nullable(); // Tamaño de la imagen
            $table->string('format')->nullable(); // Formato de la imagen
            $table->string('url_wp')->nullable(); // URL de WhatsApp
            $table->boolean('is_active')->default(true); // Estado activo
            $table->timestamps();
        });

        // Tabla de tipos de mantenimiento (maintenance_types)
        Schema::create('maintenance_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del tipo de mantenimiento (ej: "5,000 Km")
            $table->string('code')->unique(); // Código único (ej: "MANT_5K")
            $table->text('description')->nullable(); // Descripción del mantenimiento
            $table->integer('kilometers'); // Kilómetros del mantenimiento
            $table->boolean('is_active')->default(true); // Estado activo
            $table->timestamps();
        });

        // Tabla de servicios adicionales (additional_services)
        Schema::create('additional_services', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del servicio adicional
            $table->string('code')->unique(); // Código único del servicio
            $table->text('description')->nullable(); // Descripción del servicio
            $table->decimal('price', 10, 2)->nullable(); // Precio del servicio
            $table->integer('duration_minutes')->default(60); // Duración en minutos
            $table->boolean('is_active')->default(true); // Estado activo
            $table->timestamps();
        });

        // Tabla de citas (appointments)
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number')->unique(); // Número de cita
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('premise_id')->constrained('premises')->onDelete('cascade');
            $table->string('customer_ruc')->nullable(); // RUC del cliente
            $table->date('appointment_date'); // Fecha de la cita
            $table->time('appointment_time'); // Hora de la cita
            $table->string('customer_name'); // Nombre del cliente
            $table->string('customer_last_name'); // Apellido del cliente
            $table->string('customer_phone')->nullable(); // Teléfono del cliente
            $table->string('customer_email')->nullable(); // Email del cliente
            $table->string('service_mode')->nullable(); // Modo de servicio
            $table->string('maintenance_type')->nullable(); // Tipo de mantenimiento
            $table->text('comments')->nullable(); // Comentarios
            $table->string('status')->default('pending'); // Estado de la cita
            $table->timestamps();
        });

        // Tabla de servicios adicionales por cita (appointment_additional_service)
        Schema::create('appointment_additional_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->string('service_name'); // Nombre del servicio
            $table->text('notes')->nullable(); // Notas del servicio
            $table->decimal('price', 10, 2)->nullable(); // Precio del servicio
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminar en orden inverso para respetar las foreign keys
        Schema::dropIfExists('appointment_additional_service');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('additional_services');
        Schema::dropIfExists('maintenance_types');
        Schema::dropIfExists('pop_ups');
        Schema::dropIfExists('vehicles_express');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('blockades');
        Schema::dropIfExists('campaign_years');
        Schema::dropIfExists('campaign_premises');
        Schema::dropIfExists('campaign_models');
        Schema::dropIfExists('campaign_images');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('model_years');
        Schema::dropIfExists('models');
        Schema::dropIfExists('premises');
    }
};
