<?php

use App\Http\Controllers\ImagenController;
use App\Livewire\Auth\CreatePassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/admin');
    }
    return redirect('/admin/login');
});

// Ruta para servir imágenes de campañas
Route::get('/imagen/campana/{id}', [ImagenController::class, 'mostrarImagenCampana'])->name('imagen.campana');

// Ruta para crear contraseña de nuevos usuarios
Route::get('/auth/create-password', CreatePassword::class)->name('auth.create-password');
