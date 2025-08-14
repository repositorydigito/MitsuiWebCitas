<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración crea toda la estructura completa de la base de datos del sistema Mitsui
     * consolidando todas las migraciones anteriores en una sola para mayor limpieza.
     */
    public function up(): void
    {
        // ========================================
        // TABLAS DEL SISTEMA LARAVEL
        // ========================================

        // Tabla de usuarios
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            
            // Campos para documento de identidad
            $table->enum('document_type', ['DNI', 'RUC', 'CE', 'PASAPORTE'])->nullable();
            $table->string('document_number', 20)->nullable();
            
            // Campos de contacto
            $table->string('phone', 20)->nullable();
            
            // Campos de integración con C4C
            $table->string('c4c_internal_id', 50)->nullable();
            $table->string('c4c_uuid', 100)->nullable();
            
            // Campo para identificar clientes comodín
            $table->boolean('is_comodin')->default(false);
            
            $table->rememberToken();
            $table->timestamps();
            
            // Índices
            $table->unique(['email'], 'users_email_unique_when_not_null');
            $table->unique('document_number');
            $table->index('c4c_internal_id');
            $table->index('c4c_uuid');
            $table->index('is_comodin');
        });

        // Tabla de tokens de restablecimiento de contraseña
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('document_type')->nullable();
            $table->string('document_number')->nullable();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
            
            // Índices
            $table->index(['document_type', 'document_number']);
        });

        // Tabla de sesiones
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // Tabla de caché
        Schema::create('cache', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        // Tablas de trabajos (jobs)
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        // ========================================
        // TABLAS DE PERMISOS (SPATIE)
        // ========================================
        
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->bigIncrements('id');
            if ($teams || config('permission.testing')) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
            
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');
                $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            }
        });

        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');
            
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');
                $table->primary([$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            }
        });

        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        // ========================================
        // TABLAS DEL NEGOCIO MITSUI
        // ========================================

        // Tabla de locales/sucursales (premises)
        Schema::create('premises', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('brand')->nullable()->comment('Marca del local: Toyota, Lexus, Hino');
            $table->text('address')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('waze_url')->nullable();
            $table->string('maps_url')->nullable();
            $table->timestamps();
        });

        // Tabla de modelos de vehículos (models)
        Schema::create('models', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->text('description')->nullable();
            $table->string('image', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabla de años por modelo (model_years)
        Schema::create('model_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_id')->constrained('models')->onDelete('cascade');
            $table->year('year');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['model_id', 'year']);
        });

        // Tabla de vehículos (vehicles)
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('vehicle_id')->unique();
            $table->string('license_plate');
            $table->string('model');
            $table->string('year')->nullable();
            $table->string('brand_name')->nullable();
            $table->string('brand_code')->nullable();
            $table->string('color')->nullable();
            $table->string('vin')->nullable();
            $table->string('engine_number')->nullable();
            $table->integer('mileage')->nullable();
            $table->date('last_service_date')->nullable();
            $table->integer('last_service_mileage')->nullable();
            $table->date('next_service_date')->nullable();
            $table->integer('next_service_mileage')->nullable();
            $table->boolean('has_prepaid_maintenance')->default(false);
            $table->date('prepaid_maintenance_expiry')->nullable();
            $table->string('image_url')->nullable();
            $table->string('status')->default('active');
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->timestamps();
            
            // Índices
            $table->index(['user_id', 'status']);
            $table->index(['brand_code', 'status']);
            $table->index('license_plate');
        });

        // Tabla de campañas (campaigns)
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->json('brand')->nullable();
            $table->string('city')->nullable();
            $table->string('status')->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabla de imágenes de campañas (campaign_images)
        Schema::create('campaign_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->string('image_path');
            $table->string('alt_text')->nullable();
            $table->boolean('is_primary')->default(false);
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
            $table->string('premise_code');
            $table->timestamps();
            $table->unique(['campaign_id', 'premise_code']);
        });

        // Tabla de relación campañas-años (campaign_years)
        Schema::create('campaign_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->onDelete('cascade');
            $table->year('year');
            $table->timestamps();
            $table->unique(['campaign_id', 'year']);
        });

        // Tabla de tipos de mantenimiento (maintenance_types)
        Schema::create('maintenance_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('brand', ['Toyota', 'Lexus', 'Hino']);
            $table->text('description')->nullable();
            $table->integer('kilometers');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabla de servicios adicionales (additional_services)
        Schema::create('additional_services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->json('brand');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabla de mantenimientos por modelo (model_maintenances)
        Schema::create('model_maintenances', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('brand', ['Toyota', 'Lexus', 'Hino']);
            $table->string('model');
            $table->string('tipo_valor_trabajo', 100)->nullable();
            $table->integer('kilometers');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Índices
            $table->index(['brand', 'tipo_valor_trabajo'], 'idx_brand_tipo_valor_trabajo');
        });

        // Tabla de índices únicos para model_maintenances
        Schema::create('model_maintenance_unique_indexes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_maintenance_id')->constrained('model_maintenances')->onDelete('cascade');
            $table->timestamps();
        });

        // Tabla de citas (appointments)
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number')->unique();
            $table->foreignId('vehicle_id')->constrained('vehicles')->onDelete('cascade');
            $table->foreignId('premise_id')->constrained('premises')->onDelete('cascade');
            $table->string('customer_ruc')->nullable();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->string('customer_name');
            $table->string('customer_last_name');
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('service_mode')->nullable();
            $table->string('maintenance_type')->nullable();
            $table->text('comments')->nullable();
            $table->json('wildcard_selections')->nullable();
            $table->string('status')->default('pending');
            
            // Campos para integración con C4C
            $table->string('package_id')->nullable()->comment('ID del paquete de mantenimiento desde C4C');
            $table->string('vehicle_plate')->nullable()->comment('Placa del vehículo para referencia rápida');
            $table->string('c4c_offer_id')->nullable()->comment('ID de la oferta creada en C4C');
            $table->timestamp('offer_created_at')->nullable()->comment('Fecha cuando se creó la oferta en C4C');
            $table->boolean('offer_creation_failed')->default(false)->comment('Indica si falló la creación de oferta');
            $table->text('offer_creation_error')->nullable()->comment('Error específico en creación de oferta');
            $table->integer('offer_creation_attempts')->default(0)->comment('Número de intentos de creación de oferta');
            
            $table->timestamps();
            
            // Índices
            $table->index('package_id', 'idx_appointments_package_id');
            $table->index('vehicle_plate', 'idx_appointments_vehicle_plate');
            $table->index('c4c_offer_id', 'idx_appointments_c4c_offer_id');
            $table->index('offer_creation_failed', 'idx_appointments_offer_failed');
        });

        // Tabla de servicios adicionales por cita (appointment_additional_service)
        Schema::create('appointment_additional_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->string('service_name');
            $table->text('notes')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->timestamps();
        });

        // Tabla de productos (products)
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('package_id')->index();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('c4c_object_id')->nullable();
            $table->string('c4c_product_id');
            $table->text('description');
            $table->string('parent_product_id')->nullable();
            $table->string('model_id')->nullable();
            $table->string('material_number')->nullable();
            $table->decimal('base_quantity', 15, 14)->default(0);
            $table->decimal('quantity', 15, 14)->default(0);
            $table->decimal('alt_quantity', 15, 14)->default(0);
            $table->decimal('work_time_value', 15, 14)->default(0);
            $table->string('unit_code')->nullable();
            $table->string('unit_code_1')->nullable();
            $table->string('unit_code_2')->nullable();
            $table->string('position_number')->nullable();
            $table->string('labor_category')->nullable();
            $table->string('position_type')->nullable();
            $table->string('status')->default('02');
            $table->timestamps();
            
            // Índices
            $table->index(['package_id', 'appointment_id']);
            $table->index(['package_id', 'created_at']);
            $table->index(['package_id', 'status']);
            $table->index('c4c_product_id');
            
            // Foreign key
            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
        });

        // Tabla de servicios express (vehicles_express)
        Schema::create('vehicles_express', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('type')->nullable();
            $table->string('model');
            $table->string('brand');
            $table->year('year');
            $table->string('premises');
            $table->json('maintenance');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabla de bloqueos de horarios (blockades)
        Schema::create('blockades', function (Blueprint $table) {
            $table->id();
            $table->string('premises');
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('all_day')->default(false);
            $table->text('comments')->nullable();
            $table->timestamps();
        });

        // Tabla de pop-ups promocionales (pop_ups)
        Schema::create('pop_ups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image_path')->nullable();
            $table->string('sizes')->nullable();
            $table->string('format')->nullable();
            $table->string('url_wp')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabla de intervalos (intervals)
        Schema::create('intervals', function (Blueprint $table) {
            $table->id();
            $table->integer('min_reservation_time')->nullable();
            $table->string('min_time_unit', 10)->default('days');
            $table->integer('max_reservation_time')->nullable();
            $table->string('max_time_unit', 10)->default('days');
            $table->timestamps();
        });

        // Limpiar caché de permisos
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');

        // Eliminar en orden inverso para respetar las foreign keys
        Schema::dropIfExists('intervals');
        Schema::dropIfExists('pop_ups');
        Schema::dropIfExists('blockades');
        Schema::dropIfExists('vehicles_express');
        Schema::dropIfExists('products');
        Schema::dropIfExists('appointment_additional_service');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('model_maintenance_unique_indexes');
        Schema::dropIfExists('model_maintenances');
        Schema::dropIfExists('additional_services');
        Schema::dropIfExists('maintenance_types');
        Schema::dropIfExists('campaign_years');
        Schema::dropIfExists('campaign_premises');
        Schema::dropIfExists('campaign_models');
        Schema::dropIfExists('campaign_images');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('vehicles');
        Schema::dropIfExists('model_years');
        Schema::dropIfExists('models');
        Schema::dropIfExists('premises');

        // Tablas de permisos
        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);

        // Tablas del sistema Laravel
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};