<?php

use App\Http\Controllers\ImagenController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Ruta para servir imágenes de campañas
Route::get('/imagen/campana/{id}', [ImagenController::class, 'mostrarImagenCampana'])->name('imagen.campana');
