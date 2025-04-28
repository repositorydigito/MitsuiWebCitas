<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AppointmentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// C4C API Routes
Route::prefix('c4c')->group(function () {
    // Customer routes
    Route::post('/customers/find', [CustomerController::class, 'findByDocument']);
    
    // Appointment routes
    Route::post('/appointments', [AppointmentController::class, 'create']);
    Route::put('/appointments/{uuid}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{uuid}', [AppointmentController::class, 'delete']);
    Route::get('/appointments/pending', [AppointmentController::class, 'getPendingAppointments']);
});
