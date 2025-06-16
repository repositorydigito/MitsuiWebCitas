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
        Schema::table('users', function (Blueprint $table) {
            // Campos para documento de identidad
            $table->enum('document_type', ['DNI', 'RUC', 'CE', 'PASAPORTE'])
                ->after('email')
                ->nullable();

            $table->string('document_number', 20)
                ->after('document_type')
                ->nullable()
                ->unique();

            // Campos de contacto
            $table->string('phone', 20)
                ->after('document_number')
                ->nullable();

            // Campos de integración con C4C
            $table->string('c4c_internal_id', 50)
                ->after('phone')
                ->nullable()
                ->index();

            $table->string('c4c_uuid', 100)
                ->after('c4c_internal_id')
                ->nullable()
                ->index();

            // Campo para identificar clientes comodín
            $table->boolean('is_comodin')
                ->after('c4c_uuid')
                ->default(false)
                ->index();

            // Hacer email nullable ya que puede no estar disponible en algunos casos
            $table->string('email')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'document_type',
                'document_number',
                'phone',
                'c4c_internal_id',
                'c4c_uuid',
                'is_comodin',
            ]);

            // Restaurar email como obligatorio
            $table->string('email')->nullable(false)->change();
        });
    }
};
