<?php

namespace App\Services\C4C;

use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class MockAppointmentService
{
    /**
     * Create a new appointment.
     *
     * @param array $data
     * @return array
     */
    public function create(array $data)
    {
        Log::info("MockAppointmentService: Creando cita con datos: " . json_encode($data));
        
        // Validate required fields
        $requiredFields = ['customer_id', 'start_date', 'end_date', 'vehicle_plate', 'center_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'error' => "Field {$field} is required",
                    'data' => null
                ];
            }
        }
        
        // Generate mock response data
        $uuid = 'fb859c15-e812-1edf-91ce-' . Str::random(12);
        $id = rand(6000, 7000);
        $changeStateId = date('Ymdhis') . '.' . rand(1000000, 9999999);
        
        return [
            'success' => true,
            'error' => null,
            'data' => [
                'uuid' => $uuid,
                'id' => $id,
                'change_state_id' => $changeStateId,
                'groupware_item_id' => '',
                'mock_data' => [
                    'customer_id' => $data['customer_id'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'vehicle_plate' => $data['vehicle_plate'],
                    'center_id' => $data['center_id'],
                    'customer_name' => $data['customer_name'] ?? 'Cliente',
                    'notes' => $data['notes'] ?? 'Cita creada desde la aplicación',
                    'express' => $data['express'] ?? 'false',
                ]
            ]
        ];
    }
    
    /**
     * Update an existing appointment.
     *
     * @param string $uuid
     * @param array $data
     * @return array
     */
    public function update(string $uuid, array $data)
    {
        Log::info("MockAppointmentService: Actualizando cita {$uuid} con datos: " . json_encode($data));
        
        if (empty($uuid)) {
            return [
                'success' => false,
                'error' => 'Appointment UUID is required',
                'data' => null
            ];
        }
        
        // Generate mock response data
        $id = rand(6000, 7000);
        $changeStateId = date('Ymdhis') . '.' . rand(1000000, 9999999);
        
        return [
            'success' => true,
            'error' => null,
            'data' => [
                'uuid' => $uuid,
                'id' => $id,
                'change_state_id' => $changeStateId,
                'groupware_item_id' => '',
                'mock_data' => [
                    'status' => $data['status'] ?? null,
                    'appointment_status' => $data['appointment_status'] ?? null,
                    'start_date' => $data['start_date'] ?? null,
                    'end_date' => $data['end_date'] ?? null,
                ]
            ]
        ];
    }
    
    /**
     * Delete an appointment.
     *
     * @param string $uuid
     * @return array
     */
    public function delete(string $uuid)
    {
        Log::info("MockAppointmentService: Eliminando cita {$uuid}");
        
        if (empty($uuid)) {
            return [
                'success' => false,
                'error' => 'Appointment UUID is required',
                'data' => null
            ];
        }
        
        // Generate mock response data
        $id = rand(5000, 6000);
        $changeStateId = date('Ymdhis') . '.' . rand(1000000, 9999999);
        
        return [
            'success' => true,
            'error' => null,
            'data' => [
                'uuid' => $uuid,
                'id' => $id,
                'change_state_id' => $changeStateId,
                'groupware_item_id' => '',
                'status' => 'deleted'
            ]
        ];
    }
}
