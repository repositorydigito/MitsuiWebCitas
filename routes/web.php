<?php

use App\Http\Controllers\ImagenController;
use App\Livewire\Auth\CreatePassword;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Ruta para servir imágenes de campañas (por ID o filename)
Route::get('/imagen/campana/{idOrFilename}', [ImagenController::class, 'mostrarImagenCampana'])->name('imagen.campana');

// Ruta para crear contraseña de nuevos usuarios
Route::get('/auth/create-password', CreatePassword::class)->name('auth.create-password');
