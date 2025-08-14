<?php

namespace App\Http\Controllers;

use App\Jobs\DeleteAppointmentC4CJob;
use App\Models\Appointment;
use App\Services\C4C\AppointmentQueryService;
use App\Services\C4C\AppointmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AppointmentController extends Controller
{
    /**
     * The appointment service instance.
     *
     * @var AppointmentService
     */
    protected $appointmentService;

    /**
     * The appointment query service instance.
     *
     * @var AppointmentQueryService
     */
    protected $appointmentQueryService;

    /**
     * Create a new controller instance.
     */
    public function __construct(
        AppointmentService $appointmentService,
        AppointmentQueryService $appointmentQueryService
    ) {
        $this->appointmentService = $appointmentService;
        $this->appointmentQueryService = $appointmentQueryService;
    }

    /**
     * Create a new appointment.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|string',
            'customer_name' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'vehicle_plate' => 'required|string|max:10',
            'center_id' => 'required|string',
            'notes' => 'nullable|string',
            'express' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
                'data' => null,
            ], 422);
        }

        $result = $this->appointmentService->create($request->all());

        if ($result['success']) {
            return response()->json($result, 201);
        }

        return response()->json($result, 400);
    }

    /**
     * Update an existing appointment.
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $uuid)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:1,2,3,4',
            'appointment_status' => 'nullable|string|in:1,2,3,4,5',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
                'data' => null,
            ], 422);
        }

        $result = $this->appointmentService->update($uuid, $request->all());

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    /**
     * Delete an appointment (with business validations and async processing).
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($uuid)
    {
        Log::info('[AppointmentController] 🗑️ Solicitud de eliminación de cita', [
            'c4c_uuid' => $uuid,
            'user_id' => auth()->id(),
        ]);

        // Validar formato UUID
        if (!$this->isValidUuid($uuid)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid UUID format',
                'data' => null,
            ], 422);
        }

        // Buscar la cita en la base de datos local
        $appointment = Appointment::where('c4c_uuid', $uuid)->first();
        
        if (!$appointment) {
            return response()->json([
                'success' => false,
                'error' => 'Appointment not found in local database',
                'data' => null,
            ], 404);
        }

        // Validaciones de negocio
        $validationResult = $this->validateDeletion($appointment);
        if (!$validationResult['valid']) {
            return response()->json([
                'success' => false,
                'error' => $validationResult['error'],
                'data' => null,
            ], 422);
        }

        // Generar job ID único para tracking
        $jobId = Str::uuid()->toString();

        try {
            // Marcar la cita como "en proceso de eliminación"
            $appointment->update([
                'status' => 'deleting',
                'c4c_status' => 'deleting',
            ]);

            // Disparar job asíncrono para eliminación en C4C
            DeleteAppointmentC4CJob::dispatch($uuid, $appointment->id, $jobId)
                ->onQueue('c4c-delete');

            Log::info('[AppointmentController] ✅ Job de eliminación disparado', [
                'job_id' => $jobId,
                'appointment_id' => $appointment->id,
                'c4c_uuid' => $uuid,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Appointment deletion started',
                'data' => [
                    'job_id' => $jobId,
                    'appointment_id' => $appointment->id,
                    'appointment_number' => $appointment->appointment_number,
                    'status' => 'deleting',
                    'tracking_url' => route('appointments.delete.status', ['job_id' => $jobId]),
                ],
            ], 202); // 202 Accepted - processing

        } catch (\Exception $e) {
            Log::error('[AppointmentController] ❌ Error disparando job de eliminación', [
                'appointment_id' => $appointment->id,
                'c4c_uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            // Revertir el estado si hubo error
            $appointment->update([
                'status' => $appointment->getOriginal('status'),
                'c4c_status' => $appointment->getOriginal('c4c_status'),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to start appointment deletion: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Delete an appointment synchronously (for immediate deletion).
     *
     * @param  string  $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSync($uuid)
    {
        Log::info('[AppointmentController] 🗑️ Solicitud de eliminación síncrona de cita', [
            'c4c_uuid' => $uuid,
            'user_id' => auth()->id(),
        ]);

        // Validar formato UUID
        if (!$this->isValidUuid($uuid)) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid UUID format',
                'data' => null,
            ], 422);
        }

        // Buscar la cita en la base de datos local
        $appointment = Appointment::where('c4c_uuid', $uuid)->first();
        
        if (!$appointment) {
            return response()->json([
                'success' => false,
                'error' => 'Appointment not found in local database',
                'data' => null,
            ], 404);
        }

        // Validaciones de negocio
        $validationResult = $this->validateDeletion($appointment);
        if (!$validationResult['valid']) {
            return response()->json([
                'success' => false,
                'error' => $validationResult['error'],
                'data' => null,
            ], 422);
        }

        try {
            // Eliminar directamente en C4C (síncrono)
            $result = $this->appointmentService->delete($uuid);

            if ($result['success']) {
                // Actualizar estado local
                $appointment->update([
                    'status' => 'deleted',
                    'c4c_status' => 'deleted',
                    'is_synced' => true,
                    'synced_at' => now(),
                    'deleted_at' => now(),
                ]);

                Log::info('[AppointmentController] ✅ Cita eliminada síncronamente', [
                    'appointment_id' => $appointment->id,
                    'c4c_uuid' => $uuid,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Appointment deleted successfully',
                    'data' => [
                        'appointment_id' => $appointment->id,
                        'appointment_number' => $appointment->appointment_number,
                        'status' => 'deleted',
                    ],
                ]);
            }

            return response()->json($result, 400);

        } catch (\Exception $e) {
            Log::error('[AppointmentController] ❌ Error en eliminación síncrona', [
                'appointment_id' => $appointment->id,
                'c4c_uuid' => $uuid,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete appointment: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    /**
     * Get pending appointments for a customer.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingAppointments(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|string',
            'status_codes' => 'nullable|array',
            'status_codes.*' => 'integer|in:1,2,3,4,5,6',
            'limit' => 'nullable|integer|min:1|max:10000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
                'data' => null,
            ], 422);
        }

        $customerId = $request->input('customer_id');
        $statusCodes = $request->input('status_codes', [1, 2]);
        $limit = $request->input('limit', 10000);

        $result = $this->appointmentQueryService->getPendingAppointments($customerId, $statusCodes, $limit);

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    /**
     * Get deletion job status.
     *
     * @param  string  $jobId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDeleteStatus($jobId)
    {
        $status = \Illuminate\Support\Facades\Cache::get("delete_job_{$jobId}");

        if (!$status) {
            return response()->json([
                'success' => false,
                'error' => 'Job not found or expired',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $status,
        ]);
    }

    /**
     * Validate if an appointment can be deleted.
     *
     * @param  Appointment  $appointment
     * @return array
     */
    private function validateDeletion(Appointment $appointment): array
    {
        // Solo se pueden eliminar citas en estados específicos
        $allowedStatuses = ['pending', 'confirmed', 'generated'];
        $allowedC4CStatuses = ['1', '2']; // Generada, Confirmada

        // Verificar estado local
        if (!in_array($appointment->status, $allowedStatuses)) {
            return [
                'valid' => false,
                'error' => "Cannot delete appointment with status: {$appointment->status}. Only pending, confirmed, or generated appointments can be deleted.",
            ];
        }

        // Verificar estado en C4C si está disponible
        if ($appointment->c4c_status && !in_array($appointment->c4c_status, $allowedC4CStatuses)) {
            $statusNames = [
                '1' => 'Generated',
                '2' => 'Confirmed',
                '3' => 'In Workshop',
                '4' => 'Closed Deferred',
                '5' => 'Cancelled',
                '6' => 'Deleted',
            ];
            
            $currentStatusName = $statusNames[$appointment->c4c_status] ?? $appointment->c4c_status;
            
            return [
                'valid' => false,
                'error' => "Cannot delete appointment with C4C status: {$currentStatusName}. Only Generated or Confirmed appointments can be deleted.",
            ];
        }

        // Verificar que no esté ya en proceso de eliminación
        if (in_array($appointment->status, ['deleting', 'delete_failed'])) {
            return [
                'valid' => false,
                'error' => "Appointment is already in deletion process or failed to delete.",
            ];
        }

        // Verificar que no esté ya eliminada
        if ($appointment->deleted_at) {
            return [
                'valid' => false,
                'error' => "Appointment is already deleted.",
            ];
        }

        // Verificar fecha de la cita (opcional - evitar eliminar citas muy próximas)
        if ($appointment->appointment_date && $appointment->appointment_time) {
            $appointmentDateTime = \Carbon\Carbon::parse($appointment->appointment_date . ' ' . $appointment->appointment_time);
            $hoursUntilAppointment = now()->diffInHours($appointmentDateTime, false);
            
            // Si la cita es en menos de 2 horas, requerir confirmación especial
            if ($hoursUntilAppointment >= 0 && $hoursUntilAppointment < 2) {
                Log::warning('[AppointmentController] ⚠️ Intento de eliminar cita próxima', [
                    'appointment_id' => $appointment->id,
                    'appointment_datetime' => $appointmentDateTime,
                    'hours_until' => $hoursUntilAppointment,
                ]);
                
                // Permitir pero con warning
                // En una implementación más estricta, podrías retornar false aquí
            }
        }

        Log::info('[AppointmentController] ✅ Validación de eliminación exitosa', [
            'appointment_id' => $appointment->id,
            'current_status' => $appointment->status,
            'c4c_status' => $appointment->c4c_status,
        ]);

        return [
            'valid' => true,
            'error' => null,
        ];
    }

    /**
     * Validate UUID format.
     *
     * @param  string  $uuid
     * @return bool
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
}
