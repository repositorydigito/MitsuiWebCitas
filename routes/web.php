<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImagenController;

Route::get('/', function () {
    return view('welcome');
});

// Ruta para servir imágenes de campañas
Route::get('/imagen/campana/{id}', [ImagenController::class, 'mostrarImagenCampana'])->name('imagen.campana');
