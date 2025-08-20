<?php

namespace App\Services\C4C;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentQueryService
{
    /**
     * WSDL URL for appointment query service.
     *
     * @var string
     */
    protected $wsdl;

    /**
     * SOAP method for appointment queries.
     *
     * @var string
     */
    protected $method;

    /**
     * Create a new AppointmentQueryService instance.
     */
    public function __construct()
    {
        // Intentar usar el WSDL local primero
        $localWsdl = storage_path('wsdl/wscitas.wsdl');
        if (file_exists($localWsdl)) {
            // Extraer la URL del endpoint del WSDL local
            $this->wsdl = $this->extractEndpointFromWsdl($localWsdl);
            Log::info('AppointmentQueryService usando WSDL local: '.$localWsdl.' -> Endpoint: '.$this->wsdl);
        } else {
            $this->wsdl = config('c4c.services.appointment.query_wsdl');
            Log::info('AppointmentQueryService usando WSDL remoto: '.$this->wsdl);
        }

        $this->method = config('c4c.services.appointment.query_method');
    }

    /**
     * Extract the endpoint URL from a local WSDL file.
     */
    private function extractEndpointFromWsdl(string $wsdlPath): string
    {
        try {
            $wsdlContent = file_get_contents($wsdlPath);

            // Buscar la dirección del servicio SOAP
            if (preg_match('/<soap:address\s+location="([^"]+)"/', $wsdlContent, $matches)) {
                return $matches[1];
            }

            // Fallback: usar configuración remota si no se puede extraer
            Log::warning('No se pudo extraer endpoint del WSDL local, usando configuración remota');
            return config('c4c.services.appointment.query_wsdl');

        } catch (\Exception $e) {
            Log::error('Error al leer WSDL local: ' . $e->getMessage());
            return config('c4c.services.appointment.query_wsdl');
        }
    }

    /**
     * Get pending appointments for a customer.
     *
     * @return array
     */
    public function getPendingAppointments(string $customerId, array $options = [])
    {
        Log::info("Consultando citas pendientes para el cliente: {$customerId}");

        // Validar ID de cliente
        if (empty($customerId)) {
            Log::error('ID de cliente requerido');

            return [
                'success' => false,
                'error' => 'ID de cliente requerido',
                'data' => null,
            ];
        }

        // Configurar estados de cita a consultar
        $statusCodes = $options['status_codes'] ?? [1, 2]; // Por defecto: Generada y Confirmada

        // Asegurar que statusCodes sea un array
        if (! is_array($statusCodes)) {
            $statusCodes = [$statusCodes];
        }

        // Default to status codes 1 (Generated) and 2 (Confirmed) if not specified
        if (empty($statusCodes)) {
            $statusCodes = [1, 2];
        }

        // Configurar límite de resultados
        $limit = $options['limit'] ?? 10000;

        $params = [
            'ActivitySimpleSelectionBy' => [
                'SelectionByTypeCode' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '1',
                    'LowerBoundaryTypeCode' => '12', // Fixed type: 12 (Appointment)
                ],
                'SelectionByPartyID' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '1',
                    'LowerBoundaryPartyID' => $customerId,
                    'UpperBoundaryPartyID' => '',
                ],
                'SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '3', // Range
                    'LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => (string) min($statusCodes),
                    'UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => (string) max($statusCodes),
                ],
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => $limit,
                'QueryHitsUnlimitedIndicator' => '',
                'LastReturnedObjectID' => '',
            ],
        ];

        // Agregar filtros adicionales si están presentes
        if (isset($options['start_date']) && isset($options['end_date'])) {
            $startDate = Carbon::parse($options['start_date'])->format('Y-m-d');
            $endDate = Carbon::parse($options['end_date'])->format('Y-m-d');

            $params['ActivitySimpleSelectionBy']['SelectionByScheduledStartDate'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '3', // Rango
                'LowerBoundaryScheduledStartDate' => $startDate,
                'UpperBoundaryScheduledStartDate' => $endDate,
            ];
        }

        if (isset($options['center_id'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByzIDCentro'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryzIDCentro' => $options['center_id'],
                'UpperBoundaryzIDCentro' => '',
            ];
        }

        if (isset($options['vehicle_plate'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByzPlaca'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryzPlaca' => $options['vehicle_plate'],
                'UpperBoundaryzPlaca' => '',
            ];
        }

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        if ($result['success']) {
            return $this->formatAppointmentQueryResponse($result['data']);
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Error al consultar citas pendientes',
            'data' => null,
        ];
    }

    /**
     * Get all appointments for a customer.
     *
     * @return array
     */
    public function getAllAppointments(string $customerId, array $options = [])
    {
        Log::info("Consultando todas las citas para el cliente: {$customerId}");

        // Validar ID de cliente
        if (empty($customerId)) {
            Log::error('ID de cliente requerido');

            return [
                'success' => false,
                'error' => 'ID de cliente requerido',
                'data' => null,
            ];
        }

        // Configurar límite de resultados
        $limit = $options['limit'] ?? 10000;

        $params = [
            'ActivitySimpleSelectionBy' => [
                'SelectionByTypeCode' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '1',
                    'LowerBoundaryTypeCode' => '12', // Appointment (valor fijo)
                ],
                'SelectionByPartyID' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '1',
                    'LowerBoundaryPartyID' => $customerId,
                    'UpperBoundaryPartyID' => '',
                ],
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => $limit,
                'QueryHitsUnlimitedIndicator' => '',
                'LastReturnedObjectID' => '',
            ],
        ];

        // Agregar filtros adicionales si están presentes
        if (isset($options['start_date']) && isset($options['end_date'])) {
            $startDate = Carbon::parse($options['start_date'])->format('Y-m-d');
            $endDate = Carbon::parse($options['end_date'])->format('Y-m-d');

            $params['ActivitySimpleSelectionBy']['SelectionByScheduledStartDate'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '3', // Rango
                'LowerBoundaryScheduledStartDate' => $startDate,
                'UpperBoundaryScheduledStartDate' => $endDate,
            ];
        }

        if (isset($options['center_id'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByzIDCentro'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryzIDCentro' => $options['center_id'],
                'UpperBoundaryzIDCentro' => '',
            ];
        }

        if (isset($options['vehicle_plate'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByzPlaca'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryzPlaca' => $options['vehicle_plate'],
                'UpperBoundaryzPlaca' => '',
            ];
        }

        if (isset($options['status'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => $options['status'],
                'UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => '',
            ];
        }

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        if ($result['success']) {
            return $this->formatAppointmentQueryResponse($result['data']);
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Error al consultar citas',
            'data' => null,
        ];
    }

    /**
     * Get appointments by vehicle plate.
     *
     * @return array
     */
    public function getAppointmentsByVehiclePlate(string $vehiclePlate, array $options = [])
    {
        Log::info("Consultando citas por placa de vehículo: {$vehiclePlate}");

        // Validar placa de vehículo
        if (empty($vehiclePlate)) {
            Log::error('Placa de vehículo requerida');

            return [
                'success' => false,
                'error' => 'Placa de vehículo requerida',
                'data' => null,
            ];
        }

        // Configurar límite de resultados
        $limit = $options['limit'] ?? 10000;

        $params = [
            'ActivitySimpleSelectionBy' => [
                'SelectionByTypeCode' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '1',
                    'LowerBoundaryTypeCode' => '12', // Appointment (valor fijo)
                ],
                'SelectionByzPlaca' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '1',
                    'LowerBoundaryzPlaca' => $vehiclePlate,
                    'UpperBoundaryzPlaca' => '',
                ],
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => $limit,
                'QueryHitsUnlimitedIndicator' => '',
                'LastReturnedObjectID' => '',
            ],
        ];

        // Agregar filtros adicionales si están presentes
        if (isset($options['start_date']) && isset($options['end_date'])) {
            $startDate = Carbon::parse($options['start_date'])->format('Y-m-d');
            $endDate = Carbon::parse($options['end_date'])->format('Y-m-d');

            $params['ActivitySimpleSelectionBy']['SelectionByScheduledStartDate'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '3', // Rango
                'LowerBoundaryScheduledStartDate' => $startDate,
                'UpperBoundaryScheduledStartDate' => $endDate,
            ];
        }

        if (isset($options['center_id'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByzIDCentro'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryzIDCentro' => $options['center_id'],
                'UpperBoundaryzIDCentro' => '',
            ];
        }

        if (isset($options['status'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => $options['status'],
                'UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => '',
            ];
        }

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        if ($result['success']) {
            return $this->formatAppointmentQueryResponse($result['data']);
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Error al consultar citas por placa de vehículo',
            'data' => null,
        ];
    }

    /**
     * Get appointments by center.
     *
     * @return array
     */
    public function getAppointmentsByCenter(string $centerId, array $options = [])
    {
        Log::info("Consultando citas por centro: {$centerId}");

        // Validar ID de centro
        if (empty($centerId)) {
            Log::error('ID de centro requerido');

            return [
                'success' => false,
                'error' => 'ID de centro requerido',
                'data' => null,
            ];
        }

        // Configurar límite de resultados
        $limit = $options['limit'] ?? 10000;

        $params = [
            'ActivitySimpleSelectionBy' => [
                'SelectionByTypeCode' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '1',
                    'LowerBoundaryTypeCode' => '12', // Appointment (valor fijo)
                ],
                'SelectionByzIDCentro' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '1',
                    'LowerBoundaryzIDCentro' => $centerId,
                    'UpperBoundaryzIDCentro' => '',
                ],
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => $limit,
                'QueryHitsUnlimitedIndicator' => '',
                'LastReturnedObjectID' => '',
            ],
        ];

        // Agregar filtros adicionales si están presentes
        if (isset($options['start_date']) && isset($options['end_date'])) {
            $startDate = Carbon::parse($options['start_date'])->format('Y-m-d');
            $endDate = Carbon::parse($options['end_date'])->format('Y-m-d');

            $params['ActivitySimpleSelectionBy']['SelectionByScheduledStartDate'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '3', // Rango
                'LowerBoundaryScheduledStartDate' => $startDate,
                'UpperBoundaryScheduledStartDate' => $endDate,
            ];
        }

        if (isset($options['vehicle_plate'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByzPlaca'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryzPlaca' => $options['vehicle_plate'],
                'UpperBoundaryzPlaca' => '',
            ];
        }

        if (isset($options['status'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => $options['status'],
                'UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => '',
            ];
        }

        if (isset($options['customer_id'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByPartyID'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryPartyID' => $options['customer_id'],
                'UpperBoundaryPartyID' => '',
            ];
        }

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        if ($result['success']) {
            return $this->formatAppointmentQueryResponse($result['data']);
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Error al consultar citas por centro',
            'data' => null,
        ];
    }

    /**
     * Get appointments by date range.
     *
     * @return array
     */
    public function getAppointmentsByDateRange(string $startDate, string $endDate, array $options = [])
    {
        Log::info("Consultando citas por rango de fechas: {$startDate} - {$endDate}");

        // Validar fechas
        try {
            $startDateObj = Carbon::parse($startDate);
            $endDateObj = Carbon::parse($endDate);
        } catch (\Exception $e) {
            Log::error('Formato de fecha inválido: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Formato de fecha inválido: '.$e->getMessage(),
                'data' => null,
            ];
        }

        // Configurar límite de resultados
        $limit = $options['limit'] ?? 10000;

        $params = [
            'ActivitySimpleSelectionBy' => [
                'SelectionByTypeCode' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '1',
                    'LowerBoundaryTypeCode' => '12', // Appointment (valor fijo)
                ],
                'SelectionByScheduledStartDate' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '3', // Rango
                    'LowerBoundaryScheduledStartDate' => $startDateObj->format('Y-m-d'),
                    'UpperBoundaryScheduledStartDate' => $endDateObj->format('Y-m-d'),
                ],
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => $limit,
                'QueryHitsUnlimitedIndicator' => '',
                'LastReturnedObjectID' => '',
            ],
        ];

        // Agregar filtros adicionales si están presentes
        if (isset($options['center_id'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByzIDCentro'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryzIDCentro' => $options['center_id'],
                'UpperBoundaryzIDCentro' => '',
            ];
        }

        if (isset($options['vehicle_plate'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByzPlaca'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryzPlaca' => $options['vehicle_plate'],
                'UpperBoundaryzPlaca' => '',
            ];
        }

        if (isset($options['status'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => $options['status'],
                'UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => '',
            ];
        }

        if (isset($options['customer_id'])) {
            $params['ActivitySimpleSelectionBy']['SelectionByPartyID'] = [
                'InclusionExclusionCode' => 'I',
                'IntervalBoundaryTypeCode' => '1',
                'LowerBoundaryPartyID' => $options['customer_id'],
                'UpperBoundaryPartyID' => '',
            ];
        }

        $result = C4CClient::call($this->wsdl, $this->method, $params);

        if ($result['success']) {
            return $this->formatAppointmentQueryResponse($result['data']);
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Error al consultar citas por rango de fechas',
            'data' => null,
        ];
    }

    /**
     * Format appointment query response data.
     *
     * @param  object  $response
     * @return array
     */
    protected function formatAppointmentQueryResponse($response)
    {
        // Verificar si hay errores en la respuesta
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
                'error' => implode('; ', $errors) ?: 'Error en la consulta de citas',
                'data' => null,
            ];
        }

        $appointments = [];

        // Verificar si hay citas en la respuesta
        if (! isset($response->Activity)) {
            // No hay citas, pero la consulta fue exitosa
            return [
                'success' => true,
                'error' => null,
                'data' => [],
                'count' => 0,
                'processing_conditions' => [
                    'returned_query_hits_number_value' => $response->ProcessingConditions->ReturnedQueryHitsNumberValue ?? 0,
                    'more_hits_available_indicator' => $response->ProcessingConditions->MoreHitsAvailableIndicator ?? false,
                ],
            ];
        }

        // Procesar las citas
        $activityData = is_array($response->Activity) ? $response->Activity : [$response->Activity];

        foreach ($activityData as $activity) {
            $startDateTime = null;
            $endDateTime = null;
            $actualStartDateTime = null;
            $actualEndDateTime = null;
            $reportedDateTime = null;
            $creationDateTime = null;
            $lastChangeDateTime = null;

            // Parsear fechas y horas - SIMPLIFICADO para evitar errores
            $startDateTime = null;
            $endDateTime = null;
            $actualStartDateTime = null;
            $actualEndDateTime = null;
            $reportedDateTime = null;
            $creationDateTime = null;
            $lastChangeDateTime = null;

            // Por ahora saltamos el parsing de fechas complejas para que funcione el flujo
            // TODO: Implementar parsing robusto de fechas C4C

            // TODO: Implementar parsing de fechas cuando se resuelva el formato de objetos C4C
            // Por ahora todas las fechas parseadas quedan como null

            $appointment = [
                'id' => $activity->ID ?? null,
                'uuid' => $activity->UUID ?? null,
                'subject' => $activity->SubjectName ?? null,
                'location' => $activity->LocationName ?? null,
                'type' => [
                    'code' => $activity->TypeCode ?? null,
                    'name' => $activity->TypeName ?? null,
                    'processing_code' => $activity->ProcessingTypeCode ?? null,
                    'processing_name' => $activity->ProcessingTypeName ?? null,
                ],
                'status' => [
                    'lifecycle_code' => $activity->LifeCycleStatusCode ?? null,
                    'lifecycle_name' => $activity->LifeCycleStatusName ?? null,
                    'appointment_code' => $activity->zEstadoCita ?? null,
                    'appointment_name' => $activity->zEstadoCitaName ?? null,
                    'priority_code' => $activity->PriorityCode ?? null,
                    'priority_name' => $activity->PriorityName ?? null,
                    'initiator_code' => $activity->InitiatorCode ?? null,
                    'initiator_name' => $activity->InitiatorName ?? null,
                    'information_sensitivity_code' => $activity->InformationSensitivityCode ?? null,
                    'information_sensitivity_name' => $activity->InformationSensitivityName ?? null,
                    'group_code' => $activity->GroupCode ?? null,
                    'group_name' => $activity->GroupName ?? null,
                    'data_origin_type_code' => $activity->DataOriginTypeCode ?? null,
                    'data_origin_type_name' => $activity->DataOriginTypeName ?? null,
                ],
                'dates' => [
                    'scheduled_start' => $startDateTime ? $startDateTime->format('Y-m-d H:i:s') : null,
                    'scheduled_end' => $endDateTime ? $endDateTime->format('Y-m-d H:i:s') : null,
                    'scheduled_start_date' => $activity->ScheduledStartDate ?? null,
                    'scheduled_end_date' => $activity->ScheduledEndDate ?? null,
                    'actual_start' => $actualStartDateTime ? $actualStartDateTime->format('Y-m-d H:i:s') : null,
                    'actual_end' => $actualEndDateTime ? $actualEndDateTime->format('Y-m-d H:i:s') : null,
                    'reported' => $reportedDateTime ? $reportedDateTime->format('Y-m-d H:i:s') : null,
                    'reported_date' => $activity->ReportedDate ?? null,
                    'creation' => $creationDateTime ? $creationDateTime->format('Y-m-d H:i:s') : null,
                    'last_change' => $lastChangeDateTime ? $lastChangeDateTime->format('Y-m-d H:i:s') : null,
                    'creation_date' => $activity->CreationDate ?? null,
                    'last_change_date' => $activity->LastChangeDate ?? null,
                    'appointment_date' => $activity->zFecha ?? null,
                    'probable_exit_date' => $activity->zFechaHoraProbSalida ?? null,
                    'probable_exit_time' => $activity->zHoraProbSalida ?? null,
                    'start_time' => $activity->zHoraInicio ?? null,
                ],
                'organization' => [
                    'sales_organization_id' => $activity->SalesOrganisationID ?? null,
                    'sales_organization_uuid' => $activity->SalesOrganisationUUID ?? null,
                    'distribution_channel_code' => $activity->DistributionChannelCode ?? null,
                    'distribution_channel_name' => $activity->DistributionChannelName ?? null,
                    'division_code' => $activity->DivisionCode ?? null,
                    'division_name' => $activity->DivisionName ?? null,
                ],
                'vehicle' => [
                    'plate' => $activity->zPlaca ?? null,
                    'vin' => $activity->zVIN ?? null,
                    'vin_tmp' => $activity->zVINTmp ?? null,
                    'model_code' => $activity->zModeloVeh ?? null,
                    'model_description' => $activity->zDesModeloVeh ?? null,
                    'version' => $activity->zVersionVeh ?? null,
                    'year' => $activity->zAnnioVeh ?? null,
                    'color' => $activity->zColorVeh ?? null,
                    'motor' => $activity->zMotor ?? null,
                    'mileage' => $activity->zKilometrajeVeh ?? null,
                ],
                'customer' => [
                    'id' => $activity->zIDCliente ?? null,
                    'phone' => $activity->zTelefonoCliente ?? null,
                    'fixed_phone' => $activity->zTelefonoFijoCliente ?? null,
                    'address' => $activity->zDireccionCliente ?? null,
                ],
                'driver' => [
                    'name' => $activity->zNombresConductor ?? null,
                    'phone' => $activity->zCelularConductor ?? null,
                    'dni' => $activity->zActDNI ?? null,
                ],
                'center' => [
                    'id' => $activity->zIDCentro ?? null,
                    'description' => $activity->zDescCentro ?? null,
                ],
                'taxi' => [
                    'requested' => $activity->zSolicitarTaxi ?? null,
                    'description' => $activity->zDesSolitarTaxi ?? null,
                ],
                'telemarketing' => [
                    'advisor' => $activity->zAsesorTelemarketing ?? null,
                ],
                'flags' => [
                    'variable' => $activity->zVariable ?? null,
                    'flagtre' => $activity->zFlagtre ?? null,
                    'flagone' => $activity->zFlagOne ?? null,
                ],
            ];

            // Agregar coordenadas geográficas si están disponibles
            if (isset($activity->StartGeoCoordinates)) {
                $appointment['geo']['start'] = [
                    'latitude' => $activity->StartGeoCoordinates->LatitudeMeasure ?? null,
                    'longitude' => $activity->StartGeoCoordinates->LongitudeMeasure ?? null,
                ];
            }

            if (isset($activity->EndGeoCoordinates)) {
                $appointment['geo']['end'] = [
                    'latitude' => $activity->EndGeoCoordinates->LatitudeMeasure ?? null,
                    'longitude' => $activity->EndGeoCoordinates->LongitudeMeasure ?? null,
                ];
            }

            $appointments[] = $appointment;
        }

        return [
            'success' => true,
            'error' => null,
            'data' => $appointments,
            'count' => count($appointments),
            'processing_conditions' => [
                'returned_query_hits_number_value' => $response->ProcessingConditions->ReturnedQueryHitsNumberValue ?? count($appointments),
                'more_hits_available_indicator' => $response->ProcessingConditions->MoreHitsAvailableIndicator ?? false,
            ],
        ];
    }

    /**
     * Check pending appointments for multiple clients (bulk operation).
     *
     * @return array
     */
    public function bulkCheckPendingAppointments(array $clientIds)
    {
        Log::info('Verificación masiva de citas para '.count($clientIds).' clientes');

        $results = [];
        $totalAppointments = 0;
        $successfulChecks = 0;
        $clientsWithAppointments = 0;

        foreach ($clientIds as $clientId) {
            Log::info("Verificando cliente: {$clientId}");

            try {
                $result = $this->getPendingAppointments($clientId);

                if ($result['success']) {
                    $appointmentCount = $result['count'] ?? 0;
                    $hasAppointments = $appointmentCount > 0;

                    $results[] = [
                        'client_id' => $clientId,
                        'success' => true,
                        'pending_appointments' => $appointmentCount,
                        'has_appointments' => $hasAppointments,
                        'appointments_data' => $result['data'] ?? [],
                    ];

                    $totalAppointments += $appointmentCount;
                    $successfulChecks++;

                    if ($hasAppointments) {
                        $clientsWithAppointments++;
                    }

                    Log::info("Cliente {$clientId}: {$appointmentCount} cita(s)");
                } else {
                    $results[] = [
                        'client_id' => $clientId,
                        'success' => false,
                        'error' => $result['error'] ?? 'Unknown error',
                        'pending_appointments' => 0,
                        'has_appointments' => false,
                    ];

                    Log::error("Error consultando cliente {$clientId}: ".($result['error'] ?? 'Unknown error'));
                }
            } catch (\Exception $e) {
                $results[] = [
                    'client_id' => $clientId,
                    'success' => false,
                    'error' => $e->getMessage(),
                    'pending_appointments' => 0,
                    'has_appointments' => false,
                ];

                Log::error("Excepción consultando cliente {$clientId}: ".$e->getMessage());
            }
        }

        $summary = [
            'total_clients_checked' => count($clientIds),
            'successful_checks' => $successfulChecks,
            'failed_checks' => count($clientIds) - $successfulChecks,
            'total_pending_appointments' => $totalAppointments,
            'clients_with_appointments' => $clientsWithAppointments,
            'clients_without_appointments' => $successfulChecks - $clientsWithAppointments,
            'detailed_results' => $results,
        ];

        Log::info("Resumen verificación masiva: {$successfulChecks}/".count($clientIds)." exitosos, {$totalAppointments} citas total");

        return $summary;
    }
}
