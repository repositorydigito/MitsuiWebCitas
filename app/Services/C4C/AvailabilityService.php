<?php

namespace App\Services\C4C;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * Servicio para consultar disponibilidad de horarios desde la API OData de Mitsui C4C
 * Integrado con el proyecto MitsuiWebCitas existente
 */
class AvailabilityService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected int $timeout;

    public function __construct()
    {
        // Usar credenciales específicas para el servicio de disponibilidad OData
        $this->baseUrl = env('C4C_AVAILABILITY_BASE_URL', 
            'https://my317791.crm.ondemand.com/sap/c4c/odata/cust/v1/cita_x_centro');
        $this->username = env('C4C_AVAILABILITY_USERNAME', '_ODATA');
        $this->password = env('C4C_AVAILABILITY_PASSWORD', '/sap/ap/ui/cloginA!"2');
        $this->timeout = env('C4C_AVAILABILITY_TIMEOUT', 120);

        Log::info('AvailabilityService inicializado', [
            'base_url' => $this->baseUrl,
            'username' => $this->username,
            'timeout' => $this->timeout
        ]);
    }

    /**
     * Obtener horarios disponibles para un centro y día específico
     */
    public function getAvailableSlots(string $centerId, string $date): array
    {
        try {
            $dayOfWeek = Carbon::parse($date)->dayOfWeek ?: 7;

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

            Log::info('🔍 Consultando disponibilidad', [
                'centro' => $centerId,
                'fecha' => $date,
                'dia_semana' => $dayOfWeek,
                'filtros' => $filters
            ]);

            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->get($this->baseUrl . '/BOCitasPorLocalRootCollection', $queryParams);

            if (!$response->successful()) {
                Log::error('❌ Error en consulta de disponibilidad', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);

                return [
                    'success' => false,
                    'error' => 'Error al consultar disponibilidad: ' . $response->status(),
                    'slots' => []
                ];
            }

            $data = $response->json();
            $slots = $this->processAvailabilityData($data, $centerId, $date);

            Log::info('✅ Disponibilidad obtenida exitosamente', [
                'centro' => $centerId,
                'fecha' => $date,
                'slots_encontrados' => count($slots)
            ]);

            return [
                'success' => true,
                'center_id' => $centerId,
                'date' => $date,
                'day_of_week' => $dayOfWeek,
                'slots' => $slots,
                'total_slots' => count($slots)
            ];

        } catch (\Exception $e) {
            Log::error('💥 Excepción en getAvailableSlots', [
                'centro' => $centerId,
                'fecha' => $date,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage(),
                'slots' => []
            ];
        }
    }

    /**
     * Obtener horarios disponibles con cache
     */
    public function getAvailableSlotsWithCache(string $centerId, string $date, int $cacheTtl = 300): array
    {
        $cacheKey = "availability:{$centerId}:{$date}";

        return Cache::remember($cacheKey, $cacheTtl, function() use ($centerId, $date) {
            return $this->getAvailableSlots($centerId, $date);
        });
    }

    /**
     * Verificar disponibilidad de un horario específico
     */
    public function checkSlotAvailability(string $centerId, string $date, string $time): array
    {
        try {
            $dayOfWeek = Carbon::parse($date)->dayOfWeek ?: 7;
            $timeISO = $this->convertTimeToISO8601($time);

            $filters = [
                "zIDCentro eq '{$centerId}'",
                "zDia eq '{$dayOfWeek}'",
                "zHoraInicio eq '{$timeISO}'",
                "zEstado eq '1'"
            ];

            $queryParams = [
                '$format' => 'json',
                '$filter' => implode(' and ', $filters),
                '$top' => 1
            ];

            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout($this->timeout)
                ->get($this->baseUrl . '/BOCitasPorLocalRootCollection', $queryParams);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'available' => false,
                    'error' => 'Error al verificar disponibilidad'
                ];
            }

            $data = $response->json();
            $isAvailable = isset($data['d']['results']) && count($data['d']['results']) > 0;

            return [
                'success' => true,
                'available' => $isAvailable,
                'center_id' => $centerId,
                'date' => $date,
                'time' => $time,
                'slot_data' => $isAvailable ? $data['d']['results'][0] : null
            ];

        } catch (\Exception $e) {
            Log::error('💥 Error verificando disponibilidad de slot', [
                'centro' => $centerId,
                'fecha' => $date,
                'hora' => $time,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'available' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verificar conectividad con la API
     */
    public function healthCheck(): array
    {
        try {
            $startTime = microtime(true);

            $response = Http::withBasicAuth($this->username, $this->password)
                ->timeout(30)
                ->get($this->baseUrl . '/BOCitasPorLocalRootCollection', [
                    '$format' => 'json',
                    '$top' => 1
                ]);

            $endTime = microtime(true);
            $responseTime = round(($endTime - $startTime) * 1000, 2);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => 'healthy',
                    'response_time_ms' => $responseTime,
                    'timestamp' => now()->toISOString()
                ];
            }

            return [
                'success' => false,
                'status' => 'unhealthy',
                'error' => 'HTTP ' . $response->status(),
                'response_time_ms' => $responseTime,
                'timestamp' => now()->toISOString()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now()->toISOString()
            ];
        }
    }

    /**
     * Procesar datos de disponibilidad de la API
     */
    protected function processAvailabilityData(array $data, string $centerId, string $date): array
    {
        $slots = [];

        if (!isset($data['d']['results'])) {
            return $slots;
        }

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
                'date' => $date,
                'is_available' => true
            ];

            $slots[] = $processedSlot;
        }

        usort($slots, function($a, $b) {
            return strcmp($a['start_time_formatted'], $b['start_time_formatted']);
        });

        return $slots;
    }

    /**
     * Convertir hora a formato ISO 8601 (PT##H##M##S)
     */
    protected function convertTimeToISO8601(string $time): string
    {
        $parts = explode(':', $time);
        $hours = str_pad($parts[0] ?? '00', 2, '0', STR_PAD_LEFT);
        $minutes = str_pad($parts[1] ?? '00', 2, '0', STR_PAD_LEFT);
        $seconds = str_pad($parts[2] ?? '00', 2, '0', STR_PAD_LEFT);
        
        return "PT{$hours}H{$minutes}M{$seconds}S";
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
    protected function convertSAPDateToCarbon(string $sapDate): ?Carbon
    {
        if (preg_match('/\/Date\((\d+)\)\//', $sapDate, $matches)) {
            return Carbon::createFromTimestamp($matches[1] / 1000);
        }
        return null;
    }
}

