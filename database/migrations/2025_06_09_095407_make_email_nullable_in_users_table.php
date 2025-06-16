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
            // Primero eliminar el índice unique
            $table->dropUnique(['email']);

            // Luego hacer el campo nullable
            $table->string('email')->nullable()->change();

            // Crear índice unique solo para valores no nulos
            $table->unique(['email'], 'users_email_unique_when_not_null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Revertir: eliminar índice condicional
            $table->dropUnique('users_email_unique_when_not_null');

            // Hacer el campo no nullable
            $table->string('email')->nullable(false)->change();

            // Restaurar índice unique original
            $table->unique(['email']);
        });
    }
};
