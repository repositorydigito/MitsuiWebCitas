<?php

namespace App\Http\Controllers;

use App\Services\C4C\AppointmentService;
use App\Services\C4C\AppointmentQueryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
     *
     * @param AppointmentService $appointmentService
     * @param AppointmentQueryService $appointmentQueryService
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
     * @param Request $request
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
                'data' => null
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
     * @param Request $request
     * @param string $uuid
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
                'data' => null
            ], 422);
        }

        $result = $this->appointmentService->update($uuid, $request->all());

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    /**
     * Delete an appointment.
     *
     * @param string $uuid
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete($uuid)
    {
        $result = $this->appointmentService->delete($uuid);

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 400);
    }

    /**
     * Get pending appointments for a customer.
     *
     * @param Request $request
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
                'data' => null
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
}
