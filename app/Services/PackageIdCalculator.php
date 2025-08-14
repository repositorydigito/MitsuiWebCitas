<?php

namespace App\Services;

use App\Models\Vehicle;
use Illuminate\Support\Facades\Log;

/**
 * Servicio centralizado para calcular package_id dinámicamente
 * basándose en tipo_valor_trabajo del vehículo y maintenance_type
 * 
 * Lógica: M{parte_numérica_tipo_valor_trabajo}-{kilómetros_formateados}
 * Ejemplo: HILUX-2275 + 10,000 Km → M2275-010
 */
class PackageIdCalculator
{
    /**
     * Calcular package_id basándose en la lógica especificada
     */
    public function calculate(Vehicle $vehicle, ?string $maintenanceType): ?string
    {
        Log::info('📦 Calculando package_id', [
            'vehicle_id' => $vehicle->id,
            'license_plate' => $vehicle->license_plate,
            'tipo_valor_trabajo' => $vehicle->tipo_valor_trabajo,
            'brand_code' => $vehicle->brand_code,
            'maintenance_type' => $maintenanceType
        ]);

        // Verificar que el vehículo tenga tipo_valor_trabajo
        if (empty($vehicle->tipo_valor_trabajo)) {
            Log::info('ℹ️ Vehículo sin tipo_valor_trabajo', [
                'vehicle_id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate
            ]);
            return null;
        }

        // Verificar que sea Toyota, Lexus o Hino
        if (!in_array($vehicle->brand_code, ['Z01', 'Z02', 'Z03'])) {
            Log::info('ℹ️ Vehículo de marca no soportada', [
                'vehicle_id' => $vehicle->id,
                'brand_code' => $vehicle->brand_code
            ]);
            return null;
        }

        // Verificar que tenga tipo de mantenimiento
        if (empty($maintenanceType)) {
            Log::info('ℹ️ Sin tipo de mantenimiento', [
                'vehicle_id' => $vehicle->id,
                'maintenance_type' => $maintenanceType
            ]);
            return null;
        }

        // Extraer kilómetros del tipo de mantenimiento
        $kilometers = $this->extractKilometersFromMaintenanceType($maintenanceType);
        
        if (!$kilometers) {
            Log::info('ℹ️ No se pudieron extraer kilómetros', [
                'maintenance_type' => $maintenanceType
            ]);
            return null;
        }

        // Extraer la parte numérica del tipo_valor_trabajo
        $numericPart = $this->extractNumericPartFromTipoValorTrabajo($vehicle->tipo_valor_trabajo);

        if (!$numericPart) {
            Log::info('ℹ️ No se pudo extraer parte numérica', [
                'tipo_valor_trabajo' => $vehicle->tipo_valor_trabajo
            ]);
            return null;
        }

        // Formatear kilómetros a 3 dígitos con ceros a la izquierda
        $kmFormatted = str_pad($kilometers / 1000, 3, '0', STR_PAD_LEFT);
        $packageId = "M{$numericPart}-{$kmFormatted}";

        Log::info('✅ Package ID calculado exitosamente', [
            'vehicle_id' => $vehicle->id,
            'tipo_valor_trabajo' => $vehicle->tipo_valor_trabajo,
            'maintenance_type' => $maintenanceType,
            'numeric_part' => $numericPart,
            'kilometers' => $kilometers,
            'km_formatted' => $kmFormatted,
            'package_id' => $packageId
        ]);

        return $packageId;
    }

    /**
     * Calcular package_id usando código de servicio o campaña
     * Para servicios adicionales y campañas
     */
    public function calculateWithCode(Vehicle $vehicle, string $code): ?string
    {
        Log::info('📦 Calculando package_id con código', [
            'vehicle_id' => $vehicle->id,
            'license_plate' => $vehicle->license_plate,
            'tipo_valor_trabajo' => $vehicle->tipo_valor_trabajo,
            'brand_code' => $vehicle->brand_code,
            'code' => $code
        ]);

        // Verificar que el vehículo tenga tipo_valor_trabajo
        if (empty($vehicle->tipo_valor_trabajo)) {
            Log::info('ℹ️ Vehículo sin tipo_valor_trabajo', [
                'vehicle_id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate
            ]);
            return null;
        }

        // Verificar que sea Toyota, Lexus o Hino
        if (!in_array($vehicle->brand_code, ['Z01', 'Z02', 'Z03'])) {
            Log::info('ℹ️ Vehículo de marca no soportada', [
                'vehicle_id' => $vehicle->id,
                'brand_code' => $vehicle->brand_code
            ]);
            return null;
        }

        // Verificar que tenga código
        if (empty($code)) {
            Log::info('ℹ️ Sin código de servicio/campaña', [
                'vehicle_id' => $vehicle->id,
                'code' => $code
            ]);
            return null;
        }

        // Extraer la parte numérica del tipo_valor_trabajo
        $numericPart = $this->extractNumericPartFromTipoValorTrabajo($vehicle->tipo_valor_trabajo);

        if (!$numericPart) {
            Log::info('ℹ️ No se pudo extraer parte numérica', [
                'tipo_valor_trabajo' => $vehicle->tipo_valor_trabajo
            ]);
            return null;
        }

        $packageId = "M{$numericPart}-{$code}";

        Log::info('✅ Package ID con código calculado exitosamente', [
            'vehicle_id' => $vehicle->id,
            'tipo_valor_trabajo' => $vehicle->tipo_valor_trabajo,
            'code' => $code,
            'numeric_part' => $numericPart,
            'package_id' => $packageId
        ]);

        return $packageId;
    }

    /**
     * Extraer kilómetros del tipo de mantenimiento
     */
    protected function extractKilometersFromMaintenanceType(string $maintenanceType): ?int
    {
        // Patrones para extraer kilómetros
        $patterns = [
            // Formato con código: "mantenimiento_10000"
            '/mantenimiento_(\d+)/i',
            // Formato con separador: "5,000 Km", "5.000 Km"
            '/(\d+)[,.](\d+)\s*km?/i',
            // Formato simple: "5000 km", "20000 km", "15,000 KM"
            '/(\d+)\s*km?/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $maintenanceType, $matches)) {
                if (isset($matches[2]) && !empty($matches[2])) {
                    // Formato con separador (5,000 o 5.000)
                    return (int)($matches[1] . $matches[2]);
                } else {
                    // Formato simple (5000) o código (mantenimiento_10000)
                    $number = (int)$matches[1];
                    // Si es un número pequeño (1-99), multiplicar por 1000
                    return $number <= 99 ? $number * 1000 : $number;
                }
            }
        }

        return null;
    }

    /**
     * Extraer la parte numérica del tipo_valor_trabajo
     * Ejemplos:
     * - "HILUX-2275" → "2275"
     * - "RAV4-1085" → "1085"
     * - "CAMRY-3456" → "3456"
     * - "2275" → "2275"
     */
    protected function extractNumericPartFromTipoValorTrabajo(string $tipoValorTrabajo): ?string
    {
        // Patrón para extraer números después de un guión
        if (preg_match('/.*-(\d+)$/', $tipoValorTrabajo, $matches)) {
            return $matches[1];
        }

        // Si es solo números, devolverlo directamente
        if (preg_match('/^\d+$/', $tipoValorTrabajo)) {
            return $tipoValorTrabajo;
        }

        // Si no se puede extraer, devolver null
        return null;
    }

    /**
     * Verificar si un vehículo es elegible para cálculo de package_id
     */
    public function isEligible(Vehicle $vehicle): bool
    {
        return !empty($vehicle->tipo_valor_trabajo) && 
               in_array($vehicle->brand_code, ['Z01', 'Z02', 'Z03']);
    }

    /**
     * Obtener información de debug sobre por qué no se puede calcular
     */
    public function getDebugInfo(Vehicle $vehicle, ?string $maintenanceType): array
    {
        $issues = [];

        if (empty($vehicle->tipo_valor_trabajo)) {
            $issues[] = 'Vehículo sin tipo_valor_trabajo';
        }

        if (!in_array($vehicle->brand_code, ['Z01', 'Z02', 'Z03'])) {
            $issues[] = "Marca no soportada: {$vehicle->brand_code}";
        }

        if (empty($maintenanceType)) {
            $issues[] = 'Sin tipo de mantenimiento';
        } elseif (!$this->extractKilometersFromMaintenanceType($maintenanceType)) {
            $issues[] = "No se pudieron extraer kilómetros de: {$maintenanceType}";
        }

        if (!empty($vehicle->tipo_valor_trabajo) && 
            !$this->extractNumericPartFromTipoValorTrabajo($vehicle->tipo_valor_trabajo)) {
            $issues[] = "No se pudo extraer parte numérica de: {$vehicle->tipo_valor_trabajo}";
        }

        return [
            'eligible' => empty($issues),
            'issues' => $issues,
            'vehicle_info' => [
                'id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'tipo_valor_trabajo' => $vehicle->tipo_valor_trabajo,
                'brand_code' => $vehicle->brand_code
            ],
            'maintenance_type' => $maintenanceType
        ];
    }
}
