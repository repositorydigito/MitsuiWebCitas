<?php

namespace App\Http\Controllers;

use App\Services\C4C\AvailabilityService;
use App\Models\Local;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

/**
 * Controlador para gestionar la disponibilidad de horarios
 * Integrado con el proyecto MitsuiWebCitas existente
 */
class AvailabilityController extends Controller
{
    protected AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Obtener horarios disponibles para un local y fecha específica
     */
    public function getAvailableSlots(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'local_code' => 'required|string|exists:premises,code',
            'date' => 'required|date|after_or_equal:today'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
                'slots' => []
            ], 422);
        }

        try {
            $localCode = $request->input('local_code');
            $date = $request->input('date');

            $local = Local::where('code', $localCode)->first();
            if (!$local) {
                return response()->json([
                    'success' => false,
                    'error' => 'Local no encontrado',
                    'slots' => []
                ], 404);
            }

            $c4cCenterId = $this->mapLocalCodeToC4C($localCode);

            Log::info('🔍 Consultando disponibilidad', [
                'local_code' => $localCode,
                'c4c_center_id' => $c4cCenterId,
                'date' => $date,
                'local_name' => $local->name
            ]);

            $c4cResult = $this->availabilityService->getAvailableSlotsWithCache($c4cCenterId, $date);

            if (!$c4cResult['success']) {
                return response()->json([
                    'success' => false,
                    'error' => 'Error al consultar disponibilidad en C4C: ' . $c4cResult['error'],
                    'slots' => []
                ], 500);
            }

            $localAppointments = Appointment::where('premise_id', $local->id)
                ->where('appointment_date', $date)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->get();

            $availableSlots = $this->filterOccupiedSlots($c4cResult['slots'], $localAppointments);

            return response()->json([
                'success' => true,
                'local' => [
                    'code' => $local->code,
                    'name' => $local->name,
                    'c4c_center_id' => $c4cCenterId
                ],
                'date' => $date,
                'day_of_week' => $c4cResult['day_of_week'],
                'slots' => $availableSlots,
                'total_slots' => count($availableSlots),
                'occupied_slots' => $localAppointments->count()
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Error en getAvailableSlots', [
                'local_code' => $request->input('local_code'),
                'date' => $request->input('date'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor',
                'slots' => []
            ], 500);
        }
    }

    /**
     * Verificar disponibilidad de un horario específico
     */
    public function checkSlotAvailability(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'local_code' => 'required|string|exists:premises,code',
            'date' => 'required|date|after_or_equal:today',
            'time' => 'required|date_format:H:i'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
                'available' => false
            ], 422);
        }

        try {
            $localCode = $request->input('local_code');
            $date = $request->input('date');
            $time = $request->input('time') . ':00';

            $local = Local::where('code', $localCode)->first();
            $c4cCenterId = $this->mapLocalCodeToC4C($localCode);

            $c4cResult = $this->availabilityService->checkSlotAvailability($c4cCenterId, $date, $time);

            if (!$c4cResult['success']) {
                return response()->json([
                    'success' => false,
                    'available' => false,
                    'error' => $c4cResult['error']
                ], 500);
            }

            $localAppointment = Appointment::where('premise_id', $local->id)
                ->where('appointment_date', $date)
                ->where('appointment_time', $time)
                ->whereNotIn('status', ['cancelled', 'completed'])
                ->first();

            $isAvailable = $c4cResult['available'] && !$localAppointment;

            return response()->json([
                'success' => true,
                'available' => $isAvailable,
                'c4c_available' => $c4cResult['available'],
                'locally_occupied' => !!$localAppointment,
                'slot_data' => $c4cResult['slot_data'],
                'local_appointment' => $localAppointment ? [
                    'id' => $localAppointment->id,
                    'appointment_number' => $localAppointment->appointment_number,
                    'status' => $localAppointment->status
                ] : null
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Error verificando disponibilidad de slot', [
                'local_code' => $request->input('local_code'),
                'date' => $request->input('date'),
                'time' => $request->input('time'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'available' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Obtener todos los locales activos con sus códigos C4C
     */
    public function getActiveLocals()
    {
        try {
            $locals = Local::where('is_active', true)
                ->select('id', 'code', 'name', 'brand', 'address', 'phone')
                ->orderBy('name')
                ->get();

            $localsWithC4C = $locals->map(function ($local) {
                return [
                    'id' => $local->id,
                    'code' => $local->code,
                    'name' => $local->name,
                    'brand' => $local->brand,
                    'address' => $local->address,
                    'phone' => $local->phone,
                    'c4c_center_id' => $this->mapLocalCodeToC4C($local->code)
                ];
            });

            return response()->json([
                'success' => true,
                'locals' => $localsWithC4C,
                'total' => $localsWithC4C->count()
            ]);

        } catch (\Exception $e) {
            Log::error('💥 Error obteniendo locales activos', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al obtener locales',
                'locals' => []
            ], 500);
        }
    }

    /**
     * Verificar estado de salud de la API de disponibilidad
     */
    public function healthCheck()
    {
        try {
            $result = $this->availabilityService->healthCheck();

            return response()->json($result, $result['success'] ? 200 : 503);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }

    /**
     * Mapear código de local a código C4C
     */
    protected function mapLocalCodeToC4C(string $localCode): string
    {
        $mapping = [
            'M013' => 'M013', // Molina - usar código original
            'M023' => 'M023', // Canada - usar código original
            'M303' => 'M303', // Miraflores - usar código original
            'M313' => 'M313', // Arequipa - usar código original
            'M033' => 'M033', // Hino - usar código original
            'L013' => 'L013', // Lexus - mantener L013
        ];

        return $mapping[$localCode] ?? $localCode;
    }

    /**
     * Filtrar slots ocupados por citas locales
     */
    protected function filterOccupiedSlots(array $c4cSlots, $localAppointments): array
    {
        $occupiedTimes = $localAppointments->pluck('appointment_time')->map(function ($time) {
            return Carbon::parse($time)->format('H:i:s');
        })->toArray();

        return array_filter($c4cSlots, function ($slot) use ($occupiedTimes) {
            return !in_array($slot['start_time_formatted'], $occupiedTimes);
        });
    }
}

