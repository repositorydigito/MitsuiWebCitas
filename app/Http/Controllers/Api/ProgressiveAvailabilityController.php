<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\C4C\AvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * API Controller para Progressive Loading de disponibilidad
 * Separar consultas básicas (OData rápido) de validación completa (SOAP lento)
 */
class ProgressiveAvailabilityController extends Controller
{
    protected AvailabilityService $availabilityService;

    public function __construct(AvailabilityService $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    /**
     * Obtener slots básicos sin validación de capacidad (rápido ~500ms)
     * Solo consulta OData para obtener horarios y zTope
     */
    public function getBasicSlots(string $centerId, string $fecha): JsonResponse
    {
        try {
            Log::info('📋 [Progressive API] Obteniendo slots básicos', [
                'centro_id' => $centerId,
                'fecha' => $fecha
            ]);

            $startTime = microtime(true);

            // Obtener solo datos OData básicos sin validación SOAP
            $basicSlots = $this->getBasicSlotsFromOData($centerId, $fecha);

            $endTime = microtime(true);
            $executionTimeMs = round(($endTime - $startTime) * 1000, 2);

            Log::info('✅ [Progressive API] Slots básicos obtenidos', [
                'centro_id' => $centerId,
                'fecha' => $fecha,
                'total_slots' => count($basicSlots),
                'tiempo_ms' => $executionTimeMs
            ]);

            return response()->json([
                'success' => true,
                'center_id' => $centerId,
                'date' => $fecha,
                'slots' => $basicSlots,
                'total_slots' => count($basicSlots),
                'execution_time_ms' => $executionTimeMs,
                'type' => 'basic'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [Progressive API] Error obteniendo slots básicos', [
                'centro_id' => $centerId,
                'fecha' => $fecha,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo slots básicos: ' . $e->getMessage(),
                'center_id' => $centerId,
                'date' => $fecha,
                'slots' => []
            ], 500);
        }
    }

    /**
     * Obtener slots con validación completa de capacidad (lento ~7s)
     * Incluye consultas SOAP para validar disponibilidad real
     */
    public function getValidatedSlots(string $centerId, string $fecha): JsonResponse
    {
        try {
            Log::info('🔍 [Progressive API] Obteniendo slots validados', [
                'centro_id' => $centerId,
                'fecha' => $fecha
            ]);

            $startTime = microtime(true);

            // Usar método completo existente con validación de capacidad
            $result = $this->availabilityService->getAvailableSlots($centerId, $fecha);

            $endTime = microtime(true);
            $executionTimeMs = round(($endTime - $startTime) * 1000, 2);

            if ($result['success']) {
                Log::info('✅ [Progressive API] Slots validados obtenidos', [
                    'centro_id' => $centerId,
                    'fecha' => $fecha,
                    'total_slots' => $result['total_slots'],
                    'available_slots' => $result['available_slots'],
                    'tiempo_ms' => $executionTimeMs
                ]);

                return response()->json([
                    'success' => true,
                    'center_id' => $centerId,
                    'date' => $fecha,
                    'slots' => $result['slots'],
                    'total_slots' => $result['total_slots'],
                    'available_slots' => $result['available_slots'],
                    'execution_time_ms' => $executionTimeMs,
                    'type' => 'validated'
                ]);
            } else {
                throw new \Exception($result['error']);
            }

        } catch (\Exception $e) {
            Log::error('❌ [Progressive API] Error obteniendo slots validados', [
                'centro_id' => $centerId,
                'fecha' => $fecha,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error obteniendo slots validados: ' . $e->getMessage(),
                'center_id' => $centerId,
                'date' => $fecha,
                'slots' => []
            ], 500);
        }
    }

    /**
     * Método helper para obtener solo datos OData básicos
     * Evita las consultas SOAP lentas
     */
    protected function getBasicSlotsFromOData(string $centerId, string $fecha): array
    {
        // Obtener day of week
        $dayOfWeek = \Carbon\Carbon::parse($fecha)->dayOfWeek ?: 7;

        // Usar cliente HTTP directo para consulta OData rápida
        $baseUrl = config('c4c.availability.base_url');
        $username = env('C4C_AVAILABILITY_USERNAME', '_ODATA');
        $password = env('C4C_AVAILABILITY_PASSWORD', '/sap/ap/ui/cloginA!"2');

        $filters = [
            "zIDCentro eq '{$centerId}'",
            "zDia eq '{$dayOfWeek}'",
            "zEstado eq '1'"
        ];

        $queryParams = [
            '$format' => 'json',
            '$filter' => implode(' and ', $filters),
            '$orderby' => 'zItem',
            '$top' => 100
        ];

        $response = \Illuminate\Support\Facades\Http::withBasicAuth($username, $password)
            ->timeout(30)
            ->get($baseUrl . '/BOCitasPorLocalRootCollection', $queryParams);

        if (!$response->successful()) {
            throw new \Exception('Error en consulta OData: ' . $response->status());
        }

        $data = $response->json();

        if (!isset($data['d']['results'])) {
            return [];
        }

        // Procesar slots básicos sin validación de capacidad
        $slots = [];
        foreach ($data['d']['results'] as $slot) {
            $processedSlot = [
                'object_id' => $slot['ObjectID'] ?? '',
                'item' => $slot['zItem'] ?? '',
                'center_id' => $slot['zIDCentro'] ?? $centerId,
                'day' => $slot['zDia'] ?? '',
                'start_time_iso' => $slot['zHoraInicio'] ?? '',
                'start_time_formatted' => $this->convertISO8601ToTime($slot['zHoraInicio'] ?? ''),
                'capacity' => (float) ($slot['zTope'] ?? 0),
                'duration_minutes' => (float) ($slot['zDuracion'] ?? 0),
                'validity_start' => $this->convertSAPDateToCarbon($slot['zFechaInicioValidez'] ?? ''),
                'validity_end' => $this->convertSAPDateToCarbon($slot['zFechaFinValidez'] ?? ''),
                'status' => $slot['zEstado'] ?? '',
                'end_time_iso' => $slot['zHoraFin'] ?? '',
                'date' => $fecha,
                'is_available' => true, // Optimistic, se validará después
                'basic_only' => true // Flag para indicar que es solo básico
            ];

            $slots[] = $processedSlot;
        }

        // Ordenar por hora
        usort($slots, function($a, $b) {
            return strcmp($a['start_time_formatted'], $b['start_time_formatted']);
        });

        return $slots;
    }

    /**
     * Convertir formato ISO 8601 a hora normal (HH:MM:SS)
     */
    protected function convertISO8601ToTime(string $iso8601): string
    {
        if (preg_match('/PT(\d+)H(\d+)M(\d+)S/', $iso8601, $matches)) {
            return sprintf('%02d:%02d:%02d', $matches[1], $matches[2], $matches[3]);
        }
        return '00:00:00';
    }

    /**
     * Convertir fecha SAP a Carbon
     */
    protected function convertSAPDateToCarbon(string $sapDate): ?\Carbon\Carbon
    {
        if (preg_match('/\/Date\((\d+)\)\//', $sapDate, $matches)) {
            return \Carbon\Carbon::createFromTimestamp($matches[1] / 1000);
        }
        return null;
    }

    /**
     * Health check para progressive loading
     */
    public function healthCheck(): JsonResponse
    {
        try {
            $healthResult = $this->availabilityService->healthCheck();
            
            return response()->json([
                'progressive_api' => 'healthy',
                'availability_service' => $healthResult,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'progressive_api' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ], 500);
        }
    }
}