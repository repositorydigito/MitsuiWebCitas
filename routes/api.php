<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\AvailabilityController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

    // New customer search methods (like Python examples)
    Route::post('/customers/find-with-fallback', [CustomerController::class, 'findWithFallback']);
    Route::post('/customers/find-multiple', [CustomerController::class, 'findMultiple']);

    // Appointment routes
    Route::post('/appointments', [AppointmentController::class, 'create']);
    Route::put('/appointments/{uuid}', [AppointmentController::class, 'update']);
    Route::delete('/appointments/{uuid}', [AppointmentController::class, 'delete']);
    Route::delete('/appointments/{uuid}/sync', [AppointmentController::class, 'deleteSync'])->name('appointments.delete.sync');
    Route::get('/appointments/delete/status/{job_id}', [AppointmentController::class, 'getDeleteStatus'])->name('appointments.delete.status');
    Route::get('/appointments/pending', [AppointmentController::class, 'getPendingAppointments']);

    // Bulk appointment verification (like Python example 4)
    Route::post('/appointments/bulk-check', [AppointmentController::class, 'bulkCheckPendingAppointments']);
});

// Availability API Routes (NEW - Integración con API de Disponibilidad)
Route::prefix('availability')->group(function () {
    // Health check de la API de disponibilidad
    Route::get('/health', [AvailabilityController::class, 'healthCheck']);
    
    // Obtener locales activos con códigos C4C
    Route::get('/locals', [AvailabilityController::class, 'getActiveLocals']);
    
    // Obtener horarios disponibles para un local y fecha
    Route::get('/slots', [AvailabilityController::class, 'getAvailableSlots']);
    
    // Verificar disponibilidad de un horario específico
    Route::post('/check-slot', [AvailabilityController::class, 'checkSlotAvailability']);
    
    // Obtener disponibilidad para un rango de fechas
    Route::get('/range', [AvailabilityController::class, 'getAvailabilityRange']);
});

// Sync API Routes (NEW - Sincronización con C4C)
Route::prefix('sync')->group(function () {
    // Sincronizar una cita específica con C4C
    Route::post('/appointment/{appointment}', function(\App\Models\Appointment $appointment) {
        $syncService = app(\App\Services\C4C\AppointmentSyncService::class);
        $result = $syncService->syncAppointmentToC4C($appointment);
        
        return response()->json($result, $result['success'] ? 200 : 400);
    });
    
    // Sincronizar múltiples citas pendientes
    Route::post('/appointments/bulk', function() {
        $pendingAppointments = \App\Models\Appointment::where('is_synced', false)
            ->where('status', '!=', 'cancelled')
            ->limit(10)
            ->get();
            
        $results = [];
        foreach ($pendingAppointments as $appointment) {
            \App\Jobs\SyncAppointmentToC4CJob::dispatch($appointment);
            $results[] = [
                'appointment_id' => $appointment->id,
                'appointment_number' => $appointment->appointment_number,
                'queued' => true
            ];
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Citas encoladas para sincronización',
            'queued_appointments' => $results,
            'total' => count($results)
        ]);
    });
    
    // Obtener estado de sincronización
    Route::get('/status', function() {
        $stats = [
            'total_appointments' => \App\Models\Appointment::count(),
            'synced_appointments' => \App\Models\Appointment::where('is_synced', true)->count(),
            'pending_sync' => \App\Models\Appointment::where('is_synced', false)
                ->where('status', '!=', 'cancelled')->count(),
            'failed_sync' => \App\Models\Appointment::where('c4c_status', 'sync_failed')->count(),
        ];
        
        return response()->json([
            'success' => true,
            'sync_statistics' => $stats,
            'sync_percentage' => $stats['total_appointments'] > 0 ? 
                round(($stats['synced_appointments'] / $stats['total_appointments']) * 100, 2) : 0
        ]);
    });
});

