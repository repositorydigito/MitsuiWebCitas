<?php

namespace App\Services\C4C;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class AppointmentService
{
    /**
     * WSDL URL for appointment management service.
     *
     * @var string
     */
    protected $createWsdl;

    /**
     * WSDL URL for appointment query service.
     *
     * @var string
     */
    protected $queryWsdl;

    /**
     * SOAP method for appointment operations.
     *
     * @var string
     */
    protected $createMethod;

    /**
     * SOAP method for appointment queries.
     *
     * @var string
     */
    protected $queryMethod;

    /**
     * Create a new AppointmentService instance.
     */
    public function __construct()
    {
        // WSDL para crear/gestionar citas (como Python)
        $this->createWsdl = config('c4c.services.appointment.create_wsdl',
            'https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/manageappointmentactivityin1?sap-vhost=my317791.crm.ondemand.com');
        $this->createMethod = config('c4c.services.appointment.create_method',
            'AppointmentActivityBundleMaintainRequest_sync_V1');

        // WSDL para consultar citas (como Python)
        $this->queryWsdl = config('c4c.services.appointment.query_wsdl',
            'https://my317791.crm.ondemand.com/sap/bc/srt/scs/sap/yy6saj0kgy_wscitas?sap-vhost=my317791.crm.ondemand.com');
        $this->queryMethod = config('c4c.services.appointment.query_method',
            'ActivityBOVNCitasQueryByElementsSimpleByRequest_sync');

        Log::info('AppointmentService inicializado', [
            'create_wsdl' => $this->createWsdl,
            'query_wsdl' => $this->queryWsdl
        ]);
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

        $result = C4CClient::call($this->createWsdl, $this->createMethod, $params);

        // Verificar la estructura correcta de la respuesta como viene del HTTP request
        $hasAppointmentData = false;
        $appointmentData = null;

        if ($result['success'] && $result['data']) {
            // Estructura del HTTP request: Body->AppointmentActivityBundleMaintainConfirmation_sync_V1
            if (isset($result['data']->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data']->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1;
                Log::info('✅ Estructura HTTP: Appointment response encontrada');
            }
            // Estructura del SoapClient tradicional
            elseif (isset($result['data']->AppointmentActivityBundleMaintainConfirmation_sync_V1)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data'];
                Log::info('✅ Estructura SoapClient: Appointment response encontrada');
            }
            else {
                Log::warning('❌ No se encontró respuesta de appointment en ninguna estructura conocida');
                Log::info('Estructura disponible: ' . json_encode(array_keys((array)$result['data'])));
            }
        }

        if ($hasAppointmentData && $appointmentData) {
            $formattedResult = $this->formatAppointmentResponse($appointmentData);

            Log::info("📋 Resultado del formateo de cita", [
                'success' => $formattedResult['success'],
                'error' => $formattedResult['error'] ?? 'none'
            ]);

            return $formattedResult;
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Failed to create appointment',
            'data' => null,
        ];
    }

    /**
     * Create a new appointment using simplified structure (like the example).
     *
     * @param array $data
     * @return array
     */
    public function createSimple(array $data): array
    {
        Log::info("🆕 Creando nueva cita (método simplificado)", [
            'customer_id' => $data['customer_id'] ?? 'N/A',
            'start_date' => $data['start_date'] ?? 'N/A',
            'license_plate' => $data['license_plate'] ?? 'N/A'
        ]);

        // Preparar fechas en formato ISO
        $startDateTime = isset($data['start_date']) ? Carbon::parse($data['start_date'])->toISOString() : '2025-05-30T14:00:00Z';
        $endDateTime = isset($data['end_date']) ? Carbon::parse($data['end_date'])->toISOString() : '2025-05-30T15:00:00Z';
        $exitDate = isset($data['start_date']) ? Carbon::parse($data['start_date'])->format('Y-m-d') : '2025-05-30';
        $exitTime = isset($data['end_date']) ? Carbon::parse($data['end_date'])->format('H:i:s') : '15:00:00';

        // Build appointment parameters usando la estructura que SÍ funciona (como Python)
        $params = [
            'AppointmentActivity' => [
                'actionCode' => '01', // Crear nueva cita
                'DocumentTypeCode' => '0001',
                'LifeCycleStatusCode' => '1',
                'MainActivityParty' => [
                    'BusinessPartnerInternalID' => $data['customer_id'] ?? '1270002726',
                ],
                'AttendeeParty' => [
                    'EmployeeID' => $data['employee_id'] ?? '1740',
                ],
                'StartDateTime' => [
                    '_' => $startDateTime,
                    'timeZoneCode' => 'UTC-5',
                ],
                'EndDateTime' => [
                    '_' => $endDateTime,
                    'timeZoneCode' => 'UTC-5',
                ],
                'Text' => [
                    'actionCode' => '01',
                    'TextTypeCode' => '10002',
                    'ContentText' => $data['notes'] ?? 'Nueva cita para ' . ($data['license_plate'] ?? 'vehículo') . ' creada desde Laravel',
                ],
                // Campos personalizados con namespace y6s (EXACTAMENTE como Python)
                'y6s:zClienteComodin' => $data['customer_name'] ?? 'Cliente de Prueba',
                'y6s:zFechaHoraProbSalida' => $exitDate,
                'y6s:zHoraProbSalida' => $exitTime,
                'y6s:zIDCentro' => $data['center_id'] ?? 'M013',
                'y6s:zPlaca' => $data['license_plate'] ?? 'TEST-123',
                'y6s:zEstadoCita' => '1', // Generada
                'y6s:zVieneHCP' => 'X',
                'y6s:zExpress' => $data['is_express'] ?? 'false',
            ],
        ];

        // Usar el método que SÍ funciona (el mismo que Python)
        $result = C4CClient::call($this->createWsdl, 'AppointmentActivityBundleMaintainRequest_sync_V1', $params);

        // Verificar la estructura correcta de la respuesta
        $hasAppointmentData = false;
        $appointmentData = null;

        if ($result['success'] && $result['data']) {
            // Estructura del HTTP request: Body->AppointmentCreateResponse_sync
            if (isset($result['data']->Body->AppointmentCreateResponse_sync)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data']->Body->AppointmentCreateResponse_sync;
                Log::info('✅ Estructura HTTP: AppointmentCreateResponse_sync encontrada');
            }
            // Estructura del SoapClient tradicional
            elseif (isset($result['data']->AppointmentCreateResponse_sync)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data'];
                Log::info('✅ Estructura SoapClient: AppointmentCreateResponse_sync encontrada');
            }
            // Fallback: estructura similar a la de gestión
            elseif (isset($result['data']->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data']->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1;
                Log::info('✅ Estructura HTTP: Fallback a AppointmentActivityBundleMaintainConfirmation_sync_V1');
            }
            else {
                Log::warning('❌ No se encontró respuesta de appointment en ninguna estructura conocida');
                Log::info('Estructura disponible: ' . json_encode(array_keys((array)($result['data']->Body ?? []))));
            }
        }

        if ($hasAppointmentData && $appointmentData) {
            $formattedResult = $this->formatAppointmentResponse($appointmentData);

            Log::info("📋 Resultado del formateo de cita (método simplificado)", [
                'success' => $formattedResult['success'],
                'error' => $formattedResult['error'] ?? 'none'
            ]);

            return $formattedResult;
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Failed to create appointment (simple method)',
            'data' => null,
        ];
    }

    /**
     * Update an existing appointment (Actualizar Cita).
     *
     * @param string $uuid UUID de la cita a actualizar
     * @param array $data Datos a actualizar
     * @return array
     */
    public function update(string $uuid, array $data): array
    {
        Log::info("📝 Actualizando cita", [
            'uuid' => $uuid,
            'fields_to_update' => array_keys($data)
        ]);

        if (empty($uuid)) {
            return [
                'success' => false,
                'error' => 'Appointment UUID is required',
                'data' => null,
            ];
        }

        // Preparar fechas si se proporcionan
        $startDateTime = null;
        $endDateTime = null;
        $exitDate = null;
        $exitTime = null;

        if (isset($data['start_date'])) {
            $startDateTime = Carbon::parse($data['start_date'])->toISOString();
            $exitDate = Carbon::parse($data['start_date'])->format('Y-m-d');
        }

        if (isset($data['end_date'])) {
            $endDateTime = Carbon::parse($data['end_date'])->toISOString();
            $exitTime = Carbon::parse($data['end_date'])->format('H:i:s');
        }

        // Construir parámetros para UPDATE (actionCode="04")
        $params = [
            'AppointmentActivity' => [
                'actionCode' => '04', // Update
                'UUID' => $uuid,
                'y6s:zVieneHCP' => 'X',
            ],
        ];

        // Agregar campos opcionales solo si se proporcionan
        if ($startDateTime) {
            $params['AppointmentActivity']['StartDateTime'] = $startDateTime;
        }

        if ($endDateTime) {
            $params['AppointmentActivity']['EndDateTime'] = $endDateTime;
        }

        if ($exitDate) {
            $params['AppointmentActivity']['y6s:zFechaHoraProbSalida'] = $exitDate;
        }

        if ($exitTime) {
            $params['AppointmentActivity']['y6s:zHoraProbSalida'] = $exitTime;
        }

        if (isset($data['appointment_status'])) {
            $params['AppointmentActivity']['y6s:zEstadoCita'] = $data['appointment_status'];
        }

        if (isset($data['customer_name'])) {
            $params['AppointmentActivity']['y6s:zClienteComodin'] = $data['customer_name'];
        }

        if (isset($data['license_plate'])) {
            $params['AppointmentActivity']['y6s:zPlaca'] = $data['license_plate'];
        }

        if (isset($data['center_id'])) {
            $params['AppointmentActivity']['y6s:zIDCentro'] = $data['center_id'];
        }

        if (isset($data['notes'])) {
            $params['AppointmentActivity']['Text'] = [
                'ContentText' => $data['notes']
            ];
        }

        $result = C4CClient::call($this->createWsdl, $this->createMethod, $params);

        // Verificar la estructura correcta de la respuesta
        $hasAppointmentData = false;
        $appointmentData = null;

        if ($result['success'] && $result['data']) {
            // Estructura del HTTP request: Body->AppointmentActivityBundleMaintainConfirmation_sync_V1
            if (isset($result['data']->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data']->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1;
                Log::info('✅ Estructura HTTP: Update appointment response encontrada');
            }
            // Estructura del SoapClient tradicional
            elseif (isset($result['data']->AppointmentActivityBundleMaintainConfirmation_sync_V1)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data'];
                Log::info('✅ Estructura SoapClient: Update appointment response encontrada');
            }
            else {
                Log::warning('❌ No se encontró respuesta de update appointment en ninguna estructura conocida');
                Log::info('Estructura disponible: ' . json_encode(array_keys((array)($result['data']->Body ?? []))));
            }
        }

        if ($hasAppointmentData && $appointmentData) {
            $formattedResult = $this->formatAppointmentResponse($appointmentData);

            Log::info("📋 Resultado del update de cita", [
                'success' => $formattedResult['success'],
                'error' => $formattedResult['error'] ?? 'none'
            ]);

            return $formattedResult;
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Failed to update appointment',
            'data' => null,
        ];
    }

    /**
     * Delete an appointment (Eliminar Cita).
     *
     * @param string $uuid UUID de la cita a eliminar
     * @return array
     */
    public function delete(string $uuid): array
    {
        Log::info("🗑️ Eliminando cita", [
            'uuid' => $uuid
        ]);

        if (empty($uuid)) {
            return [
                'success' => false,
                'error' => 'Appointment UUID is required',
                'data' => null,
            ];
        }

        // Construir parámetros para DELETE (actionCode="06")
        $params = [
            'AppointmentActivity' => [
                'actionCode' => '06', // Delete
                'UUID' => $uuid,
                'y6s:zVieneHCP' => 'X',
            ],
        ];

        $result = C4CClient::call($this->createWsdl, $this->createMethod, $params);

        // Verificar la estructura correcta de la respuesta
        $hasAppointmentData = false;
        $appointmentData = null;

        if ($result['success'] && $result['data']) {
            // Estructura del HTTP request: Body->AppointmentActivityBundleMaintainConfirmation_sync_V1
            if (isset($result['data']->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data']->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1;
                Log::info('✅ Estructura HTTP: Delete appointment response encontrada');
            }
            // Estructura del SoapClient tradicional
            elseif (isset($result['data']->AppointmentActivityBundleMaintainConfirmation_sync_V1)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data'];
                Log::info('✅ Estructura SoapClient: Delete appointment response encontrada');
            }
            else {
                Log::warning('❌ No se encontró respuesta de delete appointment en ninguna estructura conocida');
                Log::info('Estructura disponible: ' . json_encode(array_keys((array)($result['data']->Body ?? []))));
            }
        }

        if ($hasAppointmentData && $appointmentData) {
            $formattedResult = $this->formatAppointmentResponse($appointmentData);

            Log::info("📋 Resultado del delete de cita", [
                'success' => $formattedResult['success'],
                'error' => $formattedResult['error'] ?? 'none'
            ]);

            return $formattedResult;
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Failed to delete appointment',
            'data' => null,
        ];
    }

    /**
     * Query pending appointments for a client (como Python).
     *
     * @param string $clientId
     * @return array
     */
    public function queryPendingAppointments(string $clientId): array
    {
        Log::info("Consultando citas pendientes para cliente: {$clientId}");

        $params = [
            'ActivitySimpleSelectionBy' => [
                'SelectionByTypeCode' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '1',
                    'LowerBoundaryTypeCode' => '12'
                ],
                'SelectionByPartyID' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '1',
                    'LowerBoundaryPartyID' => $clientId,
                    'UpperBoundaryPartyID' => ''
                ],
                'SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '3',
                    'LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => '1',
                    'UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => '2'
                ]
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => 10000,
                'QueryHitsUnlimitedIndicator' => '',
                'LastReturnedObjectID' => ''
            ]
        ];

        $result = C4CClient::call($this->queryWsdl, $this->queryMethod, $params);

        // Verificar la estructura correcta de la respuesta como viene del HTTP request
        $hasAppointmentData = false;
        $appointmentData = null;

        if ($result['success'] && $result['data']) {
            // Estructura del HTTP request: Body->ActivityBOVNCitasQueryByElementsSimpleByConfirmation_sync (CORRECTO)
            if (isset($result['data']->Body->ActivityBOVNCitasQueryByElementsSimpleByConfirmation_sync)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data']->Body->ActivityBOVNCitasQueryByElementsSimpleByConfirmation_sync;
                Log::info('✅ Estructura HTTP: Query appointments confirmation encontrada');
            }
            // Estructura del SoapClient tradicional
            elseif (isset($result['data']->ActivityBOVNCitasQueryByElementsSimpleByConfirmation_sync)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data'];
                Log::info('✅ Estructura SoapClient: Query appointments confirmation encontrada');
            }
            // Fallback: estructura Response (por si acaso)
            elseif (isset($result['data']->Body->ActivityBOVNCitasQueryByElementsSimpleByResponse_sync)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data']->Body->ActivityBOVNCitasQueryByElementsSimpleByResponse_sync;
                Log::info('✅ Estructura HTTP: Query appointments response encontrada (fallback)');
            }
            else {
                Log::warning('❌ No se encontró respuesta de query appointments en ninguna estructura conocida');
                Log::info('Estructuras disponibles en Body:', [
                    'body_keys' => array_keys((array)($result['data']->Body ?? []))
                ]);
            }
        }

        if ($hasAppointmentData && $appointmentData) {
            $formattedResult = $this->formatAppointmentQueryResponse($appointmentData);

            if ($formattedResult['success']) {
                Log::info("✅ Consulta de citas exitosa para cliente: {$clientId}", [
                    'appointments_found' => $formattedResult['count'] ?? 0
                ]);

                return $formattedResult;
            }

            return $formattedResult;
        }

        Log::warning("No se pudieron consultar las citas para cliente: {$clientId}");

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Failed to query appointments',
            'data' => null,
        ];
    }

    /**
     * Format appointment query response.
     *
     * @param object $response
     * @return array
     */
    protected function formatAppointmentQueryResponse($response): array
    {
        // Verificar si hay citas en la respuesta
        if (!isset($response->Activity) && isset($response->ProcessingConditions)) {
            // No hay citas, pero hay información de procesamiento
            return [
                'success' => true,
                'data' => [],
                'count' => 0,
                'processing_conditions' => [
                    'returned_query_hits_number_value' => $response->ProcessingConditions->ReturnedQueryHitsNumberValue ?? 0,
                    'more_hits_available_indicator' => $response->ProcessingConditions->MoreHitsAvailableIndicator ?? false,
                ],
            ];
        }

        // Si no hay información de citas ni de procesamiento, es un error
        if (!isset($response->Activity) && !isset($response->ProcessingConditions)) {
            return [
                'success' => false,
                'error' => 'Invalid response format',
                'data' => null,
                'count' => 0,
            ];
        }

        $appointments = [];
        if (isset($response->Activity)) {
            $activityData = $response->Activity;

            // If only one appointment is returned, convert to array
            if (!is_array($activityData)) {
                $activityData = [$activityData];
            }

            foreach ($activityData as $activity) {
                // Formatear datos básicos de la cita
                $formattedAppointment = [
                    'uuid' => $activity->UUID ?? null,
                    'id' => $activity->ID ?? null,
                    'change_state_id' => $activity->ChangeStateID ?? null,
                    'life_cycle_status_code' => $activity->LifeCycleStatusCode ?? null,
                    'life_cycle_status_name' => $activity->LifeCycleStatusName ?? null,
                ];

                // Fechas y horarios (múltiples formatos disponibles)
                $formattedAppointment['start_date_time'] = $activity->ScheduledStartDateTime ?? $activity->StartDateTime ?? $activity->ActualStartDateTime ?? null;
                $formattedAppointment['end_date_time'] = $activity->ScheduledEndDateTime ?? $activity->EndDateTime ?? $activity->ActualEndDateTime ?? null;
                $formattedAppointment['scheduled_start_date'] = $activity->ScheduledStartDate ?? null;
                $formattedAppointment['scheduled_end_date'] = $activity->ScheduledEndDate ?? null;
                $formattedAppointment['reported_date'] = $activity->ReportedDate ?? null;
                $formattedAppointment['creation_date'] = $activity->CreationDate ?? null;
                $formattedAppointment['last_change_date'] = $activity->LastChangeDate ?? null;

                // Información del cliente y vehículo
                $formattedAppointment['client_name'] = $activity->zNombresConductor ?? $activity->zClienteComodin ?? null;
                $formattedAppointment['client_dni'] = $activity->zActDNI ?? null;
                $formattedAppointment['client_id'] = $activity->zIDCliente ?? null;
                $formattedAppointment['client_phone'] = $activity->zTelefonoCliente ?? $activity->zCelularConductor ?? null;
                $formattedAppointment['client_landline'] = $activity->zTelefonoFijoCliente ?? null;
                $formattedAppointment['client_address'] = $activity->zDireccionCliente ?? null;

                // Información del vehículo
                $formattedAppointment['license_plate'] = $activity->zPlaca ?? null;
                $formattedAppointment['vin'] = $activity->zVIN ?? $activity->zVINTmp ?? null;
                $formattedAppointment['vehicle_model'] = $activity->zDesModeloVeh ?? null;
                $formattedAppointment['vehicle_version'] = $activity->zVersionVeh ?? null;
                $formattedAppointment['vehicle_year'] = $activity->zAnnioVeh ?? null;
                $formattedAppointment['vehicle_color'] = $activity->zColorVeh ?? null;
                $formattedAppointment['vehicle_mileage'] = $activity->zKilometrajeVeh ?? null;
                $formattedAppointment['engine'] = $activity->zMotor ?? null;

                // Información de la cita
                $formattedAppointment['appointment_status'] = $activity->zEstadoCita ?? null;
                $formattedAppointment['appointment_status_name'] = $activity->zEstadoCitaName ?? null;
                $formattedAppointment['center_id'] = $activity->zIDCentro ?? null;
                $formattedAppointment['center_description'] = $activity->zDescCentro ?? null;
                $formattedAppointment['subject_name'] = $activity->SubjectName ?? null;
                $formattedAppointment['location_name'] = $activity->LocationName ?? null;
                $formattedAppointment['priority_code'] = $activity->PriorityCode ?? null;
                $formattedAppointment['priority_name'] = $activity->PriorityName ?? null;

                // Horarios específicos
                $formattedAppointment['exit_date'] = $activity->zFechaHoraProbSalida ?? $activity->zFecha ?? null;
                $formattedAppointment['exit_time'] = $activity->zHoraProbSalida ?? null;
                $formattedAppointment['start_time'] = $activity->zHoraInicio ?? null;

                // Servicios adicionales
                $formattedAppointment['request_taxi'] = $activity->zSolicitarTaxi ?? null;
                $formattedAppointment['request_taxi_name'] = $activity->zDesSolitarTaxi ?? $activity->zSolicitarTaxiName ?? null;
                $formattedAppointment['telemarketing_advisor'] = $activity->zAsesorTelemarketing ?? null;

                // Información organizacional
                $formattedAppointment['sales_organization_id'] = $activity->SalesOrganisationID ?? null;
                $formattedAppointment['distribution_channel_code'] = $activity->DistributionChannelCode ?? null;
                $formattedAppointment['distribution_channel_name'] = $activity->DistributionChannelName ?? null;
                $formattedAppointment['division_code'] = $activity->DivisionCode ?? null;
                $formattedAppointment['division_name'] = $activity->DivisionName ?? null;

                // Coordenadas geográficas
                if (isset($activity->StartGeoCoordinates)) {
                    $formattedAppointment['start_latitude'] = $activity->StartGeoCoordinates->LatitudeMeasure ?? null;
                    $formattedAppointment['start_longitude'] = $activity->StartGeoCoordinates->LongitudeMeasure ?? null;
                }
                if (isset($activity->EndGeoCoordinates)) {
                    $formattedAppointment['end_latitude'] = $activity->EndGeoCoordinates->LatitudeMeasure ?? null;
                    $formattedAppointment['end_longitude'] = $activity->EndGeoCoordinates->LongitudeMeasure ?? null;
                }

                // Flags adicionales
                $formattedAppointment['variable_flag'] = $activity->zVariable ?? null;
                $formattedAppointment['flag_tre'] = $activity->zFlagtre ?? null;
                $formattedAppointment['flag_one'] = $activity->zFlagOne ?? null;

                $appointments[] = $formattedAppointment;
            }
        }

        // Crear la respuesta
        $result = [
            'success' => true,
            'error' => null,
            'data' => $appointments,
            'count' => count($appointments),
        ];

        // Agregar información de procesamiento si está disponible
        if (isset($response->ProcessingConditions)) {
            $result['processing_conditions'] = [
                'returned_query_hits_number_value' => $response->ProcessingConditions->ReturnedQueryHitsNumberValue ?? 0,
                'more_hits_available_indicator' => $response->ProcessingConditions->MoreHitsAvailableIndicator ?? false,
                'last_returned_object_id' => $response->ProcessingConditions->LastReturnedObjectID ?? null,
            ];
        }

        return $result;
    }

    /**
     * Format appointment response data.
     *
     * @param  object  $response
     * @return array
     */
    protected function formatAppointmentResponse($response)
    {
        Log::info("📋 Formateando respuesta de appointment", [
            'has_log' => isset($response->Log),
            'has_appointment_activity' => isset($response->AppointmentActivity),
            'response_keys' => array_keys((array)$response)
        ]);

        // Check for errors in the response
        if (isset($response->Log)) {
            $log = $response->Log;
            $maxSeverity = $log->MaximumLogItemSeverityCode ?? 0;

            Log::info("📋 Log encontrado", [
                'max_severity' => $maxSeverity,
                'has_items' => isset($log->Item)
            ]);

            $errors = [];
            $warnings = [];

            if (isset($log->Item)) {
                $items = is_array($log->Item) ? $log->Item : [$log->Item];

                foreach ($items as $item) {
                    $severity = $item->SeverityCode ?? 0;
                    $note = $item->Note ?? 'Unknown message';

                    if ($severity >= 3) {
                        $errors[] = $note;
                    } elseif ($severity >= 2) {
                        $warnings[] = $note;
                    }
                }
            }

            // Si hay errores críticos (severity >= 3), considerarlo como fallo
            if (!empty($errors)) {
                Log::warning("❌ Errores críticos en la respuesta", ['errors' => $errors]);
                return [
                    'success' => false,
                    'error' => implode('; ', $errors),
                    'warnings' => $warnings,
                    'data' => null,
                ];
            }

            // Si solo hay warnings, considerarlo como éxito con advertencias
            if (!empty($warnings)) {
                Log::info("⚠️ Warnings en la respuesta", ['warnings' => $warnings]);
                return [
                    'success' => true,
                    'error' => null,
                    'warnings' => $warnings,
                    'data' => [
                        'status' => 'created_with_warnings',
                        'warnings' => $warnings
                    ],
                ];
            }
        }

        // Process successful response
        if (isset($response->AppointmentActivity)) {
            Log::info("✅ AppointmentActivity encontrada en respuesta");
            return [
                'success' => true,
                'error' => null,
                'data' => [
                    'uuid' => $response->AppointmentActivity->UUID ?? null,
                    'id' => $response->AppointmentActivity->ID ?? null,
                    'change_state_id' => $response->AppointmentActivity->ChangeStateID ?? null,
                    'status' => 'created'
                ],
            ];
        }

        // Si no hay AppointmentActivity pero tampoco errores críticos, asumir éxito
        Log::info("✅ Respuesta sin errores críticos - asumiendo éxito");
        return [
            'success' => true,
            'error' => null,
            'data' => [
                'status' => 'processed',
                'message' => 'Request processed successfully'
            ],
        ];
    }
}
