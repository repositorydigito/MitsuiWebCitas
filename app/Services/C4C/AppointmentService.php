<?php

namespace App\Services\C4C;

use Carbon\Carbon;

class AppointmentService
{
    /**
     * WSDL URL for appointment service.
     *
     * @var string
     */
    protected $wsdl;

    /**
     * SOAP method for appointment operations.
     *
     * @var string
     */
    protected $method;

    /**
     * Create a new AppointmentService instance.
     */
    public function __construct()
    {
        $this->wsdl = config('c4c.services.appointment.wsdl');
        $this->method = config('c4c.services.appointment.method');
    }

    /**
     * Create a new appointment.
     *
     * @return array
     */
    public function create(array $data)
    {
        // Validate required fields
        $requiredFields = ['customer_id', 'start_date', 'end_date', 'vehicle_plate', 'center_id'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                return [
                    'success' => false,
                    'error' => "Field {$field} is required",
                    'data' => null,
                ];
            }
        }

        // Format dates
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);

        $params = [
            'AppointmentActivity' => [
                'actionCode' => config('c4c.status_codes.action.create'),
                'DocumentTypeCode' => '0001',
                'LifeCycleStatusCode' => config('c4c.status_codes.lifecycle.open'),
                'MainActivityParty' => [
                    'BusinessPartnerInternalID' => $data['customer_id'],
                ],
                'AttendeeParty' => [
                    'EmployeeID' => $data['employee_id'] ?? '7000002', // Default employee ID
                ],
                'StartDateTime' => [
                    '_' => $startDate->format('Y-m-d\TH:i:s\Z'),
                    'timeZoneCode' => 'UTC-5',
                ],
                'EndDateTime' => [
                    '_' => $endDate->format('Y-m-d\TH:i:s\Z'),
                    'timeZoneCode' => 'UTC-5',
                ],
                'Text' => [
                    'actionCode' => config('c4c.status_codes.action.create'),
                    'TextTypeCode' => '10002',
                    'ContentText' => $data['notes'] ?? 'Cita creada desde la aplicación',
                ],
                'y6s:zClienteComodin' => $data['customer_name'] ?? 'Cliente',
                'y6s:zFechaHoraProbSalida' => $endDate->format('Y-m-d'),
                'y6s:zHoraProbSalida' => $endDate->format('H:i:s'),
                'y6s:zIDCentro' => $data['center_id'],
                'y6s:zPlaca' => $data['vehicle_plate'],
                'y6s:zEstadoCita' => config('c4c.status_codes.appointment.generated'),
                'y6s:zVieneHCP' => 'X',
                'y6s:zExpress' => $data['express'] ?? 'false',
            ],
        ];

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        if ($result['success']) {
            return $this->formatAppointmentResponse($result['data']);
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Failed to create appointment',
            'data' => null,
        ];
    }

    /**
     * Update an existing appointment.
     *
     * @return array
     */
    public function update(string $uuid, array $data)
    {
        if (empty($uuid)) {
            return [
                'success' => false,
                'error' => 'Appointment UUID is required',
                'data' => null,
            ];
        }

        $params = [
            'AppointmentActivity' => [
                'actionCode' => config('c4c.status_codes.action.update'),
                'UUID' => $uuid,
            ],
        ];

        // Add optional fields if provided
        if (isset($data['status'])) {
            $params['AppointmentActivity']['LifeCycleStatusCode'] = $data['status'];
        }

        if (isset($data['appointment_status'])) {
            $params['AppointmentActivity']['y6s:zEstadoCita'] = $data['appointment_status'];
        }

        if (isset($data['start_date'])) {
            $startDate = Carbon::parse($data['start_date']);
            $params['AppointmentActivity']['StartDateTime'] = [
                '_' => $startDate->format('Y-m-d\TH:i:s\Z'),
                'timeZoneCode' => 'UTC-5',
            ];
        }

        if (isset($data['end_date'])) {
            $endDate = Carbon::parse($data['end_date']);
            $params['AppointmentActivity']['EndDateTime'] = [
                '_' => $endDate->format('Y-m-d\TH:i:s\Z'),
                'timeZoneCode' => 'UTC-5',
            ];
        }

        // Always add this flag
        $params['AppointmentActivity']['y6s:zVieneHCP'] = 'X';

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        if ($result['success']) {
            return $this->formatAppointmentResponse($result['data']);
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Failed to update appointment',
            'data' => null,
        ];
    }

    /**
     * Delete an appointment.
     *
     * @return array
     */
    public function delete(string $uuid)
    {
        if (empty($uuid)) {
            return [
                'success' => false,
                'error' => 'Appointment UUID is required',
                'data' => null,
            ];
        }

        $params = [
            'AppointmentActivity' => [
                'actionCode' => config('c4c.status_codes.action.delete'),
                'UUID' => $uuid,
                'LifeCycleStatusCode' => config('c4c.status_codes.lifecycle.cancelled'),
                'y6s:zEstadoCita' => config('c4c.status_codes.appointment.deleted'),
                'y6s:zVieneHCP' => 'X',
            ],
        ];

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        if ($result['success']) {
            return $this->formatAppointmentResponse($result['data']);
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Failed to delete appointment',
            'data' => null,
        ];
    }

    /**
     * Format appointment response data.
     *
     * @param  object  $response
     * @return array
     */
    protected function formatAppointmentResponse($response)
    {
        // Check for errors in the response
        if (isset($response->Log) && isset($response->Log->MaximumLogItemSeverityCode) && $response->Log->MaximumLogItemSeverityCode >= 3) {
            $errors = [];

            if (isset($response->Log->Item)) {
                $items = is_array($response->Log->Item) ? $response->Log->Item : [$response->Log->Item];

                foreach ($items as $item) {
                    if ($item->SeverityCode >= 2) {
                        $errors[] = $item->Note ?? 'Unknown error';
                    }
                }
            }

            return [
                'success' => false,
                'error' => implode('; ', $errors) ?: 'Error in appointment operation',
                'data' => null,
            ];
        }

        // Process successful response
        if (isset($response->AppointmentActivity)) {
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'uuid' => $response->AppointmentActivity->UUID ?? null,
                    'id' => $response->AppointmentActivity->ID ?? null,
                    'change_state_id' => $response->AppointmentActivity->ChangeStateID ?? null,
                ],
            ];
        }

        return [
            'success' => true,
            'error' => null,
            'data' => null,
        ];
    }
}
