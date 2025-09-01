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
            'query_wsdl' => $this->queryWsdl,
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

        // Format dates - Convertir hora local (Perú UTC-5) a UTC para envío a C4C
        // Se suma 5 horas porque Perú está en UTC-5, entonces UTC = hora_local + 5
        $originalStart = $data['start_date'];
        $originalEnd = $data['end_date'];
        $startDate = Carbon::parse($data['start_date'])->addHours(5);
        $endDate = Carbon::parse($data['end_date'])->addHours(5);

        Log::info('🕐 [AppointmentService::create] Conversión de fechas para envío a C4C', [
            'hora_original_start' => $originalStart,
            'hora_original_end' => $originalEnd,
            'hora_enviada_start' => $startDate->format('Y-m-d\TH:i:s\Z'),
            'hora_enviada_end' => $endDate->format('Y-m-d\TH:i:s\Z'),
            'diferencia_aplicada' => '+5 horas'
        ]);

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
                'yax:zClienteComodin' => $data['customer_name'] ?? 'Cliente',
                // Campos de INICIO (AGREGADOS para que WSCitas devuelva la hora correcta) - USAR HORA ORIGINAL SIN CONVERSIÓN UTC
                'yax:zFechaInicio' => Carbon::parse($data['start_date'])->format('Y-m-d'),
                'yax:zHoraInicio' => Carbon::parse($data['start_date'])->format('H:i:s'),
                // Campos de SALIDA (YA EXISTÍAN)
                'yax:zFechaHoraProbSalida' => $endDate->format('Y-m-d'),
                'yax:zHoraProbSalida' => $endDate->format('H:i:s'),
                'yax:zIDCentro' => $data['center_id'],
                'yax:zPlaca' => $data['vehicle_plate'],
                'yax:zEstadoCita' => config('c4c.status_codes.appointment.generated'),
                'yax:zVieneHCP' => 'X',
                'yax:zExpress' => $data['express'] ?? 'false',
            ],
        ];

        $result = C4CClient::call($this->createWsdl, $this->createMethod, $params);

        // ✅ SÍNCRONO: Si hay UUID en la respuesta, guardarlo inmediatamente
        if ($result['success'] && isset($result['uuid']) && !empty($result['uuid'])) {
            $this->guardarUuidEnAppointment($data, $result);
        }

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
            } else {
                Log::warning('❌ No se encontró respuesta de appointment en ninguna estructura conocida');
                Log::info('Estructura disponible: '.json_encode(array_keys((array) $result['data'])));
            }
        }

        if ($hasAppointmentData && $appointmentData) {
            $formattedResult = $this->formatAppointmentResponse($appointmentData);

            Log::info('📋 Resultado del formateo de cita', [
                'success' => $formattedResult['success'],
                'error' => $formattedResult['error'] ?? 'none',
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
     */
    public function createSimple(array $data): array
    {
        Log::info('🆕 Creando nueva cita (método simplificado)', [
            'customer_id' => $data['customer_id'] ?? 'N/A',
            'start_date' => $data['start_date'] ?? 'N/A',
            'license_plate' => $data['vehicle_plate'] ?? 'N/A',
        ]);

        // Preparar fechas en formato ISO - Convertir hora local (Perú UTC-5) a UTC para envío a C4C
        // Se suma 5 horas porque Perú está en UTC-5, entonces UTC = hora_local + 5
        $originalStartSimple = $data['start_date'] ?? 'N/A';
        $originalEndSimple = $data['end_date'] ?? 'N/A';
        $startDateTime = isset($data['start_date']) ? Carbon::parse($data['start_date'])->addHours(5)->toISOString() : '2025-05-30T14:00:00Z';
        $endDateTime = isset($data['end_date']) ? Carbon::parse($data['end_date'])->addHours(5)->toISOString() : '2025-05-30T15:00:00Z';

        Log::info('🕐 [AppointmentService::createSimple] Conversión de fechas para envío a C4C', [
            'hora_original_start' => $originalStartSimple,
            'hora_original_end' => $originalEndSimple,
            'hora_enviada_start' => $startDateTime,
            'hora_enviada_end' => $endDateTime,
            'diferencia_aplicada' => '+5 horas'
        ]);
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
                    'ContentText' => $data['notes'] ?? 'Nueva cita para '.($data['vehicle_plate'] ?? 'vehículo').' creada desde Laravel',
                ],
                // Campos personalizados con namespace y6s (EXACTAMENTE como Python)
                'yax:zClienteComodin' => $data['customer_name'] ?? 'Cliente de Prueba',
                'yax:zFechaHoraProbSalida' => $exitDate,
                'yax:zHoraProbSalida' => $exitTime,
                'yax:zIDCentro' => $data['center_id'] ?? 'M013',
                'yax:zPlaca' => $data['vehicle_plate'] ?? 'BJD-733',
                'yax:zEstadoCita' => '1', // Generada
                'yax:zVieneHCP' => 'X',
                'yax:zExpress' => $data['is_express'] ?? 'false',
            ],
        ];

        // Usar el método que SÍ funciona (el mismo que Python)
        $result = C4CClient::call($this->createWsdl, 'AppointmentActivityBundleMaintainRequest_sync_V1', $params);

        // ✅ SÍNCRONO: Si hay UUID en la respuesta, guardarlo inmediatamente
        if ($result['success'] && isset($result['uuid']) && !empty($result['uuid'])) {
            $this->guardarUuidEnAppointment($data, $result);
        }

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
            } else {
                Log::warning('❌ No se encontró respuesta de appointment en ninguna estructura conocida');
                Log::info('Estructura disponible: '.json_encode(array_keys((array) ($result['data']->Body ?? []))));
            }
        }

        if ($hasAppointmentData && $appointmentData) {
            $formattedResult = $this->formatAppointmentResponse($appointmentData);

            Log::info('📋 Resultado del formateo de cita (método simplificado)', [
                'success' => $formattedResult['success'],
                'error' => $formattedResult['error'] ?? 'none',
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
     * @param  string  $uuid  UUID de la cita a actualizar
     * @param  array  $data  Datos a actualizar
     */
    public function update(string $uuid, array $data): array
    {
        Log::info('📝 Actualizando cita', [
            'uuid' => $uuid,
            'fields_to_update' => array_keys($data),
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
                'yax:zVieneHCP' => 'X',
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
            $params['AppointmentActivity']['yax:zFechaHoraProbSalida'] = $exitDate;
        }

        if ($exitTime) {
            $params['AppointmentActivity']['yax:zHoraProbSalida'] = $exitTime;
        }

        if (isset($data['appointment_status'])) {
            $params['AppointmentActivity']['yax:zEstadoCita'] = $data['appointment_status'];
        }

        if (isset($data['customer_name'])) {
            $params['AppointmentActivity']['yax:zClienteComodin'] = $data['customer_name'];
        }

        if (isset($data['vehicle_plate'])) {
            $params['AppointmentActivity']['yax:zPlaca'] = $data['vehicle_plate'];
        }

        if (isset($data['center_id'])) {
            $params['AppointmentActivity']['yax:zIDCentro'] = $data['center_id'];
        }

        if (isset($data['notes'])) {
            $params['AppointmentActivity']['Text'] = [
                'ContentText' => $data['notes'],
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
            } else {
                Log::warning('❌ No se encontró respuesta de update appointment en ninguna estructura conocida');
                Log::info('Estructura disponible: '.json_encode(array_keys((array) ($result['data']->Body ?? []))));
            }
        }

        if ($hasAppointmentData && $appointmentData) {
            $formattedResult = $this->formatAppointmentResponse($appointmentData);

            Log::info('📋 Resultado del update de cita', [
                'success' => $formattedResult['success'],
                'error' => $formattedResult['error'] ?? 'none',
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
     * Actualizar solo el estado de una cita (versión simplificada)
     */
    public function updateStatus(string $uuid, int $status): array
    {
        Log::info('📝 Actualizando solo estado de cita', [
            'uuid' => $uuid,
            'new_status' => $status,
        ]);

        if (empty($uuid)) {
            return [
                'success' => false,
                'error' => 'Appointment UUID is required',
                'data' => null,
            ];
        }

        // ✅ NUEVO: Intentar obtener datos de la cita desde BD local para incluir información real
        $appointmentData = $this->getAppointmentDataForUpdate($uuid);

        // Construir parámetros con datos reales de la cita si están disponibles
        $params = [
            'AppointmentActivity' => [
                'actionCode' => '04', // Update
                'UUID' => $uuid,
                'LifeCycleStatusCode' => 2, // Según documentación para updates
                'yax:zEstadoCita' => $status,
                'yax:zVieneHCP' => 'X', // Campo requerido
            ],
        ];

        // ✅ INCLUIR DATOS REALES DE LA CITA SI ESTÁN DISPONIBLES
        if ($appointmentData) {
            if (!empty($appointmentData['vehicle_plate'])) {
                $params['AppointmentActivity']['yax:zPlaca'] = $appointmentData['vehicle_plate'];
            }
            if (!empty($appointmentData['customer_name'])) {
                $params['AppointmentActivity']['yax:zClienteComodin'] = $appointmentData['customer_name'];
            }
            if (!empty($appointmentData['center_code'])) {
                $params['AppointmentActivity']['yax:zIDCentro'] = $appointmentData['center_code'];
            }

            // ✅ INCLUIR FECHAS ORIGINALES (mismo patrón que cancel() y create())
            if (!empty($appointmentData['start_date'])) {
                // StartDateTime con conversión UTC (mismo patrón que cancel())
                $startDateTime = Carbon::parse($appointmentData['start_date'])->addHours(5)->format('Y-m-d\TH:i:s\Z');
                $params['AppointmentActivity']['StartDateTime'] = [
                    '_' => $startDateTime,
                    'timeZoneCode' => 'UTC-5',
                ];

                // Campos de inicio locales (mismo patrón que create())
                $params['AppointmentActivity']['yax:zFechaInicio'] = Carbon::parse($appointmentData['start_date'])->format('Y-m-d');
                $params['AppointmentActivity']['yax:zHoraInicio'] = Carbon::parse($appointmentData['start_date'])->format('H:i:s');
            }

            if (!empty($appointmentData['end_date'])) {
                // EndDateTime con conversión UTC
                $endDateTime = Carbon::parse($appointmentData['end_date'])->addHours(5)->format('Y-m-d\TH:i:s\Z');
                $params['AppointmentActivity']['EndDateTime'] = [
                    '_' => $endDateTime,
                    'timeZoneCode' => 'UTC-5',
                ];

                // Campos de salida (mismo patrón que create())
                $params['AppointmentActivity']['yax:zFechaHoraProbSalida'] = Carbon::parse($appointmentData['end_date'])->format('Y-m-d');
                $params['AppointmentActivity']['yax:zHoraProbSalida'] = Carbon::parse($appointmentData['end_date'])->format('H:i:s');
            }

            Log::info('✅ Datos originales de cita incluidos en actualización', [
                'vehicle_plate' => $appointmentData['vehicle_plate'] ?? 'N/A',
                'customer_name' => $appointmentData['customer_name'] ?? 'N/A',
                'center_code' => $appointmentData['center_code'] ?? 'N/A',
                'start_date' => $appointmentData['start_date'] ?? 'N/A',
                'end_date' => $appointmentData['end_date'] ?? 'N/A'
            ]);
        } else {
            Log::warning('⚠️ No se encontraron datos locales para la cita, usando actualización mínima');
        }

        Log::info('📤 Enviando parámetros con datos originales a C4C', [
            'params' => $params
        ]);

        $result = C4CClient::call($this->createWsdl, $this->createMethod, $params);

        // Verificar la estructura correcta de la respuesta
        $hasAppointmentData = false;
        $appointmentData = null;

        if ($result['success'] && $result['data']) {
            // Estructura del HTTP request: Body->AppointmentActivityBundleMaintainConfirmation_sync_V1
            if (isset($result['data']->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data']->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1;
                Log::info('✅ Estructura HTTP: Update status response encontrada');
            }
            // Estructura del SoapClient tradicional
            elseif (isset($result['data']->AppointmentActivityBundleMaintainConfirmation_sync_V1)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data'];
                Log::info('✅ Estructura SoapClient: Update status response encontrada');
            } else {
                Log::warning('❌ No se encontró respuesta de update en ninguna estructura conocida');
                Log::info('Estructura disponible: '.json_encode(array_keys((array) $result['data'])));
            }
        }

        if ($hasAppointmentData && $appointmentData) {
            $formattedResult = $this->formatAppointmentResponse($appointmentData);

            Log::info('📋 Resultado del update de estado', [
                'success' => $formattedResult['success'],
                'error' => $formattedResult['error'] ?? 'none',
            ]);

            return $formattedResult;
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Failed to update appointment status',
            'data' => null,
        ];
    }

    /**
     * Cancel an appointment (Cancelar Cita).
     *
     * @param  array  $data  Datos de cancelación
     */
    public function cancel(array $data): array
    {
        Log::info('🚫 [AppointmentService::cancel] ========== INICIO CANCELACIÓN ==========');
        Log::info('🚫 [AppointmentService::cancel] Cancelando cita', [
            'uuid' => $data['uuid'] ?? 'N/A',
            'action_code' => $data['action_code'] ?? 'N/A',
            'estado_cita' => $data['estado_cita'] ?? 'N/A',
            'wsdl' => $this->createWsdl,
            'method' => $this->createMethod,
        ]);

        $uuid = $data['uuid'] ?? '';
        if (empty($uuid)) {
            Log::error('❌ [AppointmentService::cancel] UUID vacío');
            return [
                'success' => false,
                'error' => 'Appointment UUID is required',
                'data' => null,
            ];
        }

        // ✅ OBTENER FECHAS ORIGINALES DE LA BD LOCAL
        $appointmentData = $this->getAppointmentDataForUpdate($uuid);

        Log::info('🔍 [AppointmentService::cancel] INICIO CANCELACIÓN - Datos recuperados de BD', [
            'uuid' => $uuid,
            'appointment_data_exists' => $appointmentData ? 'SÍ' : 'NO',
            'start_date' => $appointmentData['start_date'] ?? 'NO DISPONIBLE',
            'end_date' => $appointmentData['end_date'] ?? 'NO DISPONIBLE',
            'appointment_id' => $appointmentData['id'] ?? 'NO DISPONIBLE',
        ]);

        // Construir parámetros para CANCELAR (actionCode="04" como ATRIBUTO)
        $params = [
            'AppointmentActivity' => [
                '@actionCode' => $data['action_code'] ?? '04', // Modificar - COMO ATRIBUTO
                'UUID' => $uuid,
                'LifeCycleStatusCode' => $data['lifecycle_status'] ?? '2',
                'yax:zEstadoCita' => $data['estado_cita'] ?? '6', // Estado cancelado
                'yax:zVieneHCP' => $data['viene_hcp'] ?? 'X',
            ],
        ];

        // ✅ INCLUIR FECHAS ORIGINALES SI ESTÁN DISPONIBLES
        if ($appointmentData && !empty($appointmentData['start_date'])) {
            $startDateTime = Carbon::parse($appointmentData['start_date'])->addHours(5)->format('Y-m-d\TH:i:s\Z');
            $params['AppointmentActivity']['StartDateTime'] = [
                '_' => $startDateTime,
                'timeZoneCode' => 'UTC-5',
            ];

            Log::info('📅 [AppointmentService::cancel] Fecha START incluida en cancelación', [
                'fecha_original' => $appointmentData['start_date'],
                'fecha_enviada' => $startDateTime,
                'timezone' => 'UTC-5',
            ]);
        } else {
            Log::warning('⚠️ [AppointmentService::cancel] NO se incluye fecha START - datos no disponibles');
        }

        if ($appointmentData && !empty($appointmentData['end_date'])) {
            $endDateTime = Carbon::parse($appointmentData['end_date'])->addHours(5)->format('Y-m-d\TH:i:s\Z');
            $params['AppointmentActivity']['EndDateTime'] = [
                '_' => $endDateTime,
                'timeZoneCode' => 'UTC-5',
            ];

            Log::info('📅 [AppointmentService::cancel] Fecha END incluida en cancelación', [
                'fecha_original' => $appointmentData['end_date'],
                'fecha_enviada' => $endDateTime,
                'timezone' => 'UTC-5',
            ]);
        } else {
            Log::warning('⚠️ [AppointmentService::cancel] NO se incluye fecha END - datos no disponibles');
        }

        Log::info('📤 [AppointmentService::cancel] PARÁMETROS COMPLETOS A ENVIAR A C4C', [
            'params_completos' => $params,
            'action_code' => $params['AppointmentActivity']['@actionCode'],
            'lifecycle_status' => $params['AppointmentActivity']['LifeCycleStatusCode'],
            'estado_cita' => $params['AppointmentActivity']['yax:zEstadoCita'],
            'uuid' => $params['AppointmentActivity']['UUID'],
            'tiene_start_date' => isset($params['AppointmentActivity']['StartDateTime']),
            'tiene_end_date' => isset($params['AppointmentActivity']['EndDateTime']),
        ]);

        Log::info('🚀 [AppointmentService::cancel] Enviando request a C4C con WSDL: ' . $this->createWsdl);
        $result = C4CClient::call($this->createWsdl, $this->createMethod, $params);

        Log::info('📥 [AppointmentService::cancel] RESPUESTA COMPLETA DE C4C', [
            'success' => $result['success'],
            'error' => $result['error'] ?? 'none',
            'data_exists' => isset($result['data']),
            'data_type' => isset($result['data']) ? gettype($result['data']) : 'null',
            'response_completa' => $result['data'] ?? 'NO DATA',
        ]);

        // Verificar la estructura correcta de la respuesta
        $hasAppointmentData = false;
        $appointmentData = null;

        if ($result['success'] && $result['data']) {
            // Estructura del HTTP request: Body->AppointmentActivityBundleMaintainConfirmation_sync_V1
            if (isset($result['data']->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data']->Body->AppointmentActivityBundleMaintainConfirmation_sync_V1;
                Log::info('✅ Estructura HTTP: Cancel appointment response encontrada');
            }
            // Estructura del SoapClient tradicional
            elseif (isset($result['data']->AppointmentActivityBundleMaintainConfirmation_sync_V1)) {
                $hasAppointmentData = true;
                $appointmentData = $result['data'];
                Log::info('✅ Estructura SoapClient: Cancel appointment response encontrada');
            } else {
                Log::warning('❌ No se encontró respuesta de cancel appointment en ninguna estructura conocida');
                Log::info('Estructura disponible: '.json_encode(array_keys((array) ($result['data']->Body ?? []))));
            }
        }

        if ($hasAppointmentData && $appointmentData) {
            $formattedResult = $this->formatAppointmentResponse($appointmentData);

            Log::info('📋 Resultado del formateo de cancelación', [
                'success' => $formattedResult['success'],
                'error' => $formattedResult['error'] ?? 'none',
            ]);

            return $formattedResult;
        }

        return [
            'success' => false,
            'error' => $result['error'] ?? 'Failed to cancel appointment',
            'data' => null,
        ];
    }

    /**
     * Delete an appointment (Eliminar Cita).
     *
     * @param  string  $uuid  UUID de la cita a eliminar
     */
    public function delete(string $uuid): array
    {
        Log::info('🗑️ [AppointmentService::delete] ========== INICIO ELIMINACIÓN ==========');
        Log::info('🗑️ [AppointmentService::delete] Eliminando cita', [
            'uuid' => $uuid,
            'wsdl' => $this->createWsdl,
            'method' => $this->createMethod,
        ]);

        if (empty($uuid)) {
            Log::error('❌ [AppointmentService::delete] UUID vacío');
            return [
                'success' => false,
                'error' => 'Appointment UUID is required',
                'data' => null,
            ];
        }

        // Construir parámetros para DELETE (actionCode="04" como ATRIBUTO)
        $params = [
            'AppointmentActivity' => [
                '@actionCode' => '04', // Delete - COMO ATRIBUTO
                'UUID' => $uuid,
                'LifeCycleStatusCode' => '2', // Requerido para eliminación
                'yax:zEstadoCita' => '6', // Estado eliminado
                'yax:zVieneHCP' => 'X',
            ],
        ];

        Log::info('📤 [AppointmentService::delete] Parámetros a enviar', [
            'params' => $params,
            'action_code' => $params['AppointmentActivity']['@actionCode'],
            'lifecycle_status' => $params['AppointmentActivity']['LifeCycleStatusCode'],
            'estado_cita' => $params['AppointmentActivity']['yax:zEstadoCita'],
        ]);

        Log::info('🚀 [AppointmentService::delete] Enviando request a C4C...');
        $result = C4CClient::call($this->createWsdl, $this->createMethod, $params);

        Log::info('📥 [AppointmentService::delete] Respuesta recibida de C4C', [
            'success' => $result['success'],
            'error' => $result['error'] ?? 'none',
            'data_exists' => isset($result['data']),
            'data_type' => isset($result['data']) ? gettype($result['data']) : 'null',
        ]);

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
            } else {
                Log::warning('❌ No se encontró respuesta de delete appointment en ninguna estructura conocida');
                Log::info('Estructura disponible: '.json_encode(array_keys((array) ($result['data']->Body ?? []))));
            }
        }

        if ($hasAppointmentData && $appointmentData) {
            Log::info('✅ [AppointmentService::delete] AppointmentData encontrada', [
                'appointment_data_type' => gettype($appointmentData),
                'appointment_data_keys' => is_object($appointmentData) ? array_keys((array)$appointmentData) : 'not_object',
            ]);

            $formattedResult = $this->formatAppointmentResponse($appointmentData);

            Log::info('📋 [AppointmentService::delete] Resultado del delete de cita', [
                'success' => $formattedResult['success'],
                'error' => $formattedResult['error'] ?? 'none',
                'data' => $formattedResult['data'] ?? null,
            ]);

            Log::info('🗑️ [AppointmentService::delete] ========== FIN ELIMINACIÓN ==========');
            return $formattedResult;
        }

        Log::error('❌ [AppointmentService::delete] No se encontró AppointmentData en la respuesta');
        Log::info('🗑️ [AppointmentService::delete] ========== FIN ELIMINACIÓN (ERROR) ==========');
        return [
            'success' => false,
            'error' => $result['error'] ?? 'Failed to delete appointment',
            'data' => null,
        ];
    }

    /**
     * Query pending appointments for a client (como Python).
     */
    public function queryPendingAppointments(string $clientId): array
    {
        Log::info("Consultando citas pendientes para cliente: {$clientId}");

        $params = [
            'ActivitySimpleSelectionBy' => [
                'SelectionByTypeCode' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '1',
                    'LowerBoundaryTypeCode' => '12',
                ],
                'SelectionByPartyID' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '1',
                    'LowerBoundaryPartyID' => $clientId,
                    'UpperBoundaryPartyID' => '',
                ],
                'SelectionByzEstadoCita_5PEND6QL5482763O1SFB05YP5' => [
                    'InclusionExclusionCode' => 'I',
                    'IntervalBoundaryTypeCode' => '3',
                    'LowerBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => '1',
                    'UpperBoundaryzEstadoCita_5PEND6QL5482763O1SFB05YP5' => '3', // Modificado: incluir estado 3 (En taller)
                ],
            ],
            'ProcessingConditions' => [
                'QueryHitsMaximumNumberValue' => 10000,
                'QueryHitsUnlimitedIndicator' => '',
                'LastReturnedObjectID' => '',
            ],
        ];

        $result = C4CClient::call($this->queryWsdl, $this->queryMethod, $params);
        Log::info('🔍 [DEBUG] Respuesta cruda de WSCitas:', $result);

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
            } else {
                Log::warning('❌ No se encontró respuesta de query appointments en ninguna estructura conocida');
                Log::info('Estructuras disponibles en Body:', [
                    'body_keys' => array_keys((array) ($result['data']->Body ?? [])),
                ]);
            }
        }

        if ($hasAppointmentData && $appointmentData) {
            $formattedResult = $this->formatAppointmentQueryResponse($appointmentData);

            if ($formattedResult['success']) {
                Log::info("✅ Consulta de citas exitosa para cliente: {$clientId}", [
                    'appointments_found' => $formattedResult['count'] ?? 0,
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
     * @param  object  $response
     */
    protected function formatAppointmentQueryResponse($response): array
    {
        // Verificar si hay citas en la respuesta
        if (! isset($response->Activity) && isset($response->ProcessingConditions)) {
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
        if (! isset($response->Activity) && ! isset($response->ProcessingConditions)) {
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
            if (! is_array($activityData)) {
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
        Log::info('📋 Formateando respuesta de appointment', [
            'has_log' => isset($response->Log),
            'has_appointment_activity' => isset($response->AppointmentActivity),
            'response_keys' => array_keys((array) $response),
        ]);

        // Check for errors in the response
        if (isset($response->Log)) {
            $log = $response->Log;
            $maxSeverity = $log->MaximumLogItemSeverityCode ?? 0;

            Log::info('📋 Log encontrado', [
                'max_severity' => $maxSeverity,
                'has_items' => isset($log->Item),
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
            if (! empty($errors)) {
                Log::warning('❌ Errores críticos en la respuesta', ['errors' => $errors]);

                return [
                    'success' => false,
                    'error' => implode('; ', $errors),
                    'warnings' => $warnings,
                    'data' => null,
                ];
            }

            // Si solo hay warnings, considerarlo como éxito con advertencias
            if (! empty($warnings)) {
                Log::info('⚠️ Warnings en la respuesta', ['warnings' => $warnings]);

                // ✅ EXTRAER UUID DE AppointmentActivity si existe
                $uuid = $response->AppointmentActivity->UUID ?? null;
                $appointmentId = $response->AppointmentActivity->ID ?? null;

                return [
                    'success' => true,
                    'error' => null,
                    'warnings' => $warnings,
                    'uuid' => $uuid,                    // ✅ AGREGAR UUID AQUÍ
                    'appointment_id' => $appointmentId, // ✅ AGREGAR ID AQUÍ
                    'data' => [
                        'status' => 'created_with_warnings',
                        'warnings' => $warnings,
                        'uuid' => $uuid,                // ✅ TAMBIÉN EN DATA
                        'appointment_id' => $appointmentId,
                    ],
                ];
            }
        }

        // Process successful response
        if (isset($response->AppointmentActivity)) {
            Log::info('✅ AppointmentActivity encontrada en respuesta');

            $uuid = $response->AppointmentActivity->UUID ?? null;
            $appointmentId = $response->AppointmentActivity->ID ?? null;

            return [
                'success' => true,
                'error' => null,
                'uuid' => $uuid,                    // ✅ AGREGAR UUID AQUÍ
                'appointment_id' => $appointmentId, // ✅ AGREGAR ID AQUÍ
                'data' => [
                    'uuid' => $uuid,
                    'id' => $appointmentId,
                    'change_state_id' => $response->AppointmentActivity->ChangeStateID ?? null,
                    'status' => 'created',
                ],
            ];
        }

        // Si no hay AppointmentActivity pero tampoco errores críticos, asumir éxito
        Log::info('✅ Respuesta sin errores críticos - asumiendo éxito');

        return [
            'success' => true,
            'error' => null,
            'data' => [
                'status' => 'processed',
                'message' => 'Request processed successfully',
            ],
        ];
    }

    /**
     * ✅ NUEVO: Guardar UUID en appointment inmediatamente (SÍNCRONO)
     */
    private function guardarUuidEnAppointment(array $data, array $result): void
    {
        try {
            // Buscar appointment por datos únicos
            $appointment = null;

            // Si viene appointment_id en los datos, usarlo
            if (isset($data['appointment_id'])) {
                $appointment = \App\Models\Appointment::find($data['appointment_id']);
            }
            // Si no, buscar por placa y fecha (más reciente)
            elseif (isset($data['vehicle_plate']) && isset($data['start_date'])) {
                $appointment = \App\Models\Appointment::where('vehicle_plate', $data['vehicle_plate'])
                    ->whereDate('appointment_date', Carbon::parse($data['start_date'])->format('Y-m-d'))
                    ->whereNull('c4c_uuid') // Solo los que no tienen UUID aún
                    ->orderBy('created_at', 'desc')
                    ->first();
            }

            if ($appointment) {
                $appointment->update([
                    'c4c_uuid' => $result['uuid'],
                    'c4c_appointment_id' => $result['appointment_id'] ?? null,
                    'is_synced' => true,
                    'synced_at' => now(),
                    'c4c_status' => 'created'
                ]);

                Log::info('✅ UUID guardado síncronamente en appointment', [
                    'appointment_id' => $appointment->id,
                    'appointment_number' => $appointment->appointment_number,
                    'c4c_uuid' => $result['uuid'],
                    'c4c_appointment_id' => $result['appointment_id'] ?? null
                ]);
            } else {
                Log::warning('⚠️ No se encontró appointment para guardar UUID', [
                    'license_plate' => $data['vehicle_plate'] ?? null,
                    'start_date' => $data['start_date'] ?? null,
                    'c4c_uuid' => $result['uuid']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('❌ Error guardando UUID en appointment', [
                'error' => $e->getMessage(),
                'c4c_uuid' => $result['uuid'] ?? null
            ]);
        }
    }

    /**
     * ✅ NUEVO: Obtener datos de cita desde BD local para incluir en actualizaciones
     */
    private function getAppointmentDataForUpdate(string $uuid): ?array
    {
        try {
            // Buscar appointment en BD local por UUID de C4C
            $appointment = \App\Models\Appointment::where('c4c_uuid', $uuid)->first();

            if ($appointment) {
                Log::info('📋 Datos de cita encontrados en BD local', [
                    'appointment_id' => $appointment->id,
                    'appointment_number' => $appointment->appointment_number,
                    'vehicle_plate' => $appointment->vehicle_plate,
                    'customer_name' => $appointment->customer_name,
                    'center_code' => $appointment->center_code
                ]);

                // Preparar datos para inclusión en request C4C
                $customerFullName = trim(($appointment->customer_name ?? '') . ' ' . ($appointment->customer_last_name ?? ''));

                // ✅ CONSTRUIR FECHAS COMPLETAS PARA CANCELACIÓN
                $startDate = null;
                $endDate = null;

                if ($appointment->appointment_date && $appointment->appointment_time) {
                    // Extraer solo la fecha (YYYY-MM-DD) y la hora (HH:MM:SS)
                    $dateOnly = substr($appointment->appointment_date, 0, 10); // "2025-07-19"
                    $timeOnly = substr($appointment->appointment_time, 11) ?: $appointment->appointment_time; // "09:45:00"

                    $startDate = $dateOnly . ' ' . $timeOnly; // "2025-07-19 09:45:00"
                    $endDate = $startDate; // Usar la misma fecha para start_date y end_date
                }

                return [
                    'vehicle_plate' => $appointment->vehicle_plate,
                    'customer_name' => $customerFullName ?: $appointment->customer_name,
                    'center_code' => $appointment->center_code,
                    'appointment_number' => $appointment->appointment_number,
                    'appointment_id' => $appointment->id,
                    'start_date' => $startDate,  // ✅ AGREGADO: Fecha completa
                    'end_date' => $endDate,      // ✅ AGREGADO: Misma fecha para ambos
                ];
            }

            Log::warning('⚠️ No se encontró appointment en BD local para UUID', [
                'uuid' => $uuid
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('❌ Error obteniendo datos de appointment desde BD', [
                'error' => $e->getMessage(),
                'uuid' => $uuid
            ]);

            return null;
        }
    }
}
