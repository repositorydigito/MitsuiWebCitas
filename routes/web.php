<?php

use App\Http\Controllers\CustomPasswordController;
use App\Http\Controllers\ImagenController;
use App\Livewire\Auth\CreatePassword;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// Ruta para servir imágenes de campañas (por ID o filename)
Route::get('/imagen/campana/{idOrFilename}', [ImagenController::class, 'mostrarImagenCampana'])->name('imagen.campana');

// Ruta para crear contraseña de nuevos usuarios
Route::get('/auth/create-password', CreatePassword::class)->name('auth.create-password');

// Ruta para recuperación de contraseña (password.request)
Route::get('/forgot-password', function () {
    return view('filament.pages.auth.forgot-password');
})->name('password.request');

// Ruta para enviar email de recuperación de contraseña
Route::post('/forgot-password', [CustomPasswordController::class, 'sendResetLink'])->name('password.send-reset-link');

// Ruta fallback para evitar error de Route [login] not defined
Route::get('/login', function () {
    return redirect(filament()->getLoginUrl());
})->name('login');

// Ruta para mostrar formulario de restablecimiento de contraseña
Route::get('/reset-password/{token}', [CustomPasswordController::class, 'showResetForm'])->name('password.reset');

// Ruta para actualizar contraseña
Route::post('/reset-password', [CustomPasswordController::class, 'update'])->name('password.update');
