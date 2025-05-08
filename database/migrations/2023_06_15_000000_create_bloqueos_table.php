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
        Schema::create('bloqueos', function (Blueprint $table) {
            $table->id();
            $table->string('local');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->string('hora_inicio')->nullable();
            $table->string('hora_fin')->nullable();
            $table->boolean('todo_dia')->default(false);
            $table->text('comentarios')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bloqueos');
    }
};
