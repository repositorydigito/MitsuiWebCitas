<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'appointment_id',
        'c4c_object_id',
        'c4c_product_id',
        'description',
        'parent_product_id',
        'model_id',
        'material_number',
        'base_quantity',
        'quantity',
        'alt_quantity',
        'work_time_value',
        'unit_code',
        'unit_code_1',
        'unit_code_2',
        'position_number',
        'labor_category',
        'position_type',
        'status',
    ];

    protected $casts = [
        'base_quantity' => 'decimal:14',
        'quantity' => 'decimal:14',
        'alt_quantity' => 'decimal:14',
        'work_time_value' => 'decimal:14',
    ];

    /**
     * Relación con Appointment
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Scope: Productos maestros (sin cita específica)
     */
    public function scopeMaster($query)
    {
        return $query->whereNull('appointment_id');
    }

    /**
     * Scope: Productos de una cita específica
     */
    public function scopeForAppointment($query, $appointmentId)
    {
        return $query->where('appointment_id', $appointmentId);
    }

    /**
     * Scope: Productos por package_id
     */
    public function scopeForPackage($query, $packageId)
    {
        return $query->where('package_id', $packageId);
    }

    /**
     * Scope: Productos activos
     */
    public function scopeActive($query)
    {
        return $query->where('status', '02');
    }

    /**
     * Scope: Cache válido (últimas N horas)
     */
    public function scopeFresh($query, $hours = 24)
    {
        return $query->where('created_at', '>', now()->subHours($hours));
    }

    /**
     * Scope: Productos por tipo de posición
     */
    public function scopeByPositionType($query, $positionType)
    {
        return $query->where('position_type', $positionType);
    }

    /**
     * Scope: Servicios (P001)
     */
    public function scopeServices($query)
    {
        return $query->where('position_type', 'P001');
    }

    /**
     * Scope: Materiales (P002)
     */
    public function scopeMaterials($query)
    {
        return $query->where('position_type', 'P002');
    }

    /**
     * Verificar si existen productos maestros para un package_id
     */
    public static function existsMasterProductsForPackage(string $packageId, int $cacheHours = 24): bool
    {
        return static::forPackage($packageId)
            ->master()
            ->active()
            ->fresh($cacheHours)
            ->exists();
    }

    /**
     * Obtener productos maestros para un package_id
     */
    public static function getMasterProductsForPackage(string $packageId): \Illuminate\Database\Eloquent\Collection
    {
        return static::forPackage($packageId)
            ->master()
            ->active()
            ->orderBy('position_number')
            ->get();
    }

    /**
     * Crear productos para una cita específica desde productos maestros
     */
    public static function createProductsForAppointment(int $appointmentId, string $packageId): int
    {
        $masterProducts = static::getMasterProductsForPackage($packageId);
        $created = 0;

        foreach ($masterProducts as $product) {
            static::create([
                'appointment_id' => $appointmentId,
                'package_id' => $product->package_id,
                'c4c_object_id' => $product->c4c_object_id,
                'c4c_product_id' => $product->c4c_product_id,
                'description' => $product->description,
                'parent_product_id' => $product->parent_product_id,
                'model_id' => $product->model_id,
                'material_number' => $product->material_number,
                'base_quantity' => $product->base_quantity,
                'quantity' => $product->quantity,
                'alt_quantity' => $product->alt_quantity,
                'work_time_value' => $product->work_time_value,
                'unit_code' => $product->unit_code,
                'unit_code_1' => $product->unit_code_1,
                'unit_code_2' => $product->unit_code_2,
                'position_number' => $product->position_number,
                'labor_category' => $product->labor_category,
                'position_type' => $product->position_type,
                'status' => $product->status,
            ]);
            $created++;
        }

        Log::info('✅ Productos vinculados a cita', [
            'appointment_id' => $appointmentId,
            'package_id' => $packageId,
            'productos_creados' => $created
        ]);

        return $created;
    }

    /**
     * Mapear datos de C4C al formato del modelo
     */
    public static function mapFromC4CData($c4cProduct, string $packageId): array
    {
        // ✅ DETERMINAR UNIT_CODE APROPIADO BASÁNDOSE EN TIPO DE POSICIÓN
        $positionType = $c4cProduct->zTipoPosicion ?? null;
        $unitCodeFromC4C = $c4cProduct->unitCode ?? null;

        // ✅ NUEVA LÓGICA: Basada en zTipoPosicion según requerimientos
        // P001 (Servicios) → HUR (Horas)
        // Todos los otros casos → EA (Each)
        $finalUnitCode = $unitCodeFromC4C;
        if (empty($finalUnitCode)) {
            switch ($positionType) {
                case 'P001': // Servicios
                    $finalUnitCode = 'HUR'; // Horas
                    break;
                case 'P002': // Materiales/Partes
                case 'P009': // Componentes
                case 'P010': // Material específico
                default:
                    $finalUnitCode = 'EA'; // Each por defecto
                    break;
            }
        }

        return [
            'package_id' => $packageId,
            'appointment_id' => null, // Producto maestro
            'c4c_object_id' => $c4cProduct->ObjectID ?? null,
            'c4c_product_id' => $c4cProduct->zIDProductoVinculado ?? '',
            'description' => $c4cProduct->zDescripcionProductoVinculado ?? '',
            'parent_product_id' => $c4cProduct->zIDPadreProductoVinc ?? null,
            'model_id' => $c4cProduct->zIDModeloPrdVinc ?? null,
            'material_number' => $c4cProduct->zMatnr ?? null,
            'base_quantity' => (float) ($c4cProduct->zMENGE ?? 0),
            'quantity' => (float) ($c4cProduct->zZMENG ?? 0), // ✅ CORREGIDO: Usar zZMENG real del endpoint
            'alt_quantity' => (float) ($c4cProduct->zZMENG ?? 0),
            'work_time_value' => (float) ($c4cProduct->zTiempoValorTrabajo ?? 0),
            'unit_code' => $finalUnitCode, // ✅ CORREGIDO: Usa valor determinado arriba
            'unit_code_1' => $c4cProduct->unitCode1 ?? null,
            'unit_code_2' => $c4cProduct->unitCode2 ?? null,
            'position_number' => $c4cProduct->zPOSNR ?? null,
            'labor_category' => $c4cProduct->zLBRCAT ?? null,
            'position_type' => $c4cProduct->zTipoPosicion ?? null,
            'status' => $c4cProduct->zEstado ?? '02',
        ];
    }

    /**
     * Obtener resumen de productos por tipo
     */
    public function scopeProductSummary($query, string $packageId)
    {
        return $query->forPackage($packageId)
            ->master()
            ->active()
            ->selectRaw('
                position_type,
                COUNT(*) as total_products,
                SUM(quantity) as total_quantity,
                SUM(work_time_value) as total_work_time
            ')
            ->groupBy('position_type')
            ->orderBy('position_type');
    }
}
