<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\CenterOrganizationMapping;
use App\Services\PackageIdCalculator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DiagnoseOffersCommand extends Command
{
    protected $signature = 'offers:diagnose {--last=10} {--dni=} {--plate=} {--mode=both : logs|db|both}';

    protected $description = 'Diagnostica las últimas citas y posibles errores de paquete/oferta (DB y/o logs)';

    public function handle(): int
    {
        $last = (int) $this->option('last');
        $dni = $this->option('dni');
        $plate = $this->option('plate');
        $mode = strtolower($this->option('mode') ?? 'both');

        $this->info('🔎 Diagnóstico de ofertas y paquetes');
        $this->line("Parámetros: last={$last}, dni=".($dni ?: 'N/A').", plate=".($plate ?: 'N/A').", mode={$mode}");

        // 1) Cargar citas desde la BD según filtros
        $appointments = Appointment::query()
            ->when($dni, fn($q) => $q->where('customer_ruc', $dni))
            ->when($plate, function ($q) use ($plate) {
                $q->where(function ($sub) use ($plate) {
                    $sub->where('vehicle_plate', $plate)
                        ->orWhereHas('vehicle', fn($v) => $v->where('license_plate', $plate));
                });
            })
            ->with(['vehicle'])
            ->orderByDesc('created_at')
            ->limit($last)
            ->get();

        if ($appointments->isEmpty()) {
            $this->warn('No se encontraron citas con esos filtros.');
            return self::SUCCESS;
        }

        $calculator = app(PackageIdCalculator::class);

        foreach ($appointments as $a) {
            $this->newLine();
            $this->info("Cita #{$a->id} ({$a->appointment_number})");
            $this->line("  Cliente: {$a->customer_name} {$a->customer_last_name} | DNI/RUC: {$a->customer_ruc}");
            $this->line("  Placa: ".($a->vehicle->license_plate ?? $a->vehicle_plate ?? 'N/A')." | Fecha: ".($a->appointment_date?->format('Y-m-d') ?? 'N/A')." " . ($a->appointment_time?->format('H:i:s') ?? ''));
            $this->line("  Centro: ".($a->center_code ?: 'N/A')." | Marca Vehículo: ".($a->vehicle_brand_code ?: ($a->vehicle->brand_code ?? 'N/A')));
            $this->line("  Mantenimiento: ".($a->maintenance_type ?? 'N/A')." | TipoValorTrabajo: ".($a->vehicle->tipo_valor_trabajo ?? 'N/A'));
            $this->line("  package_id (BD): ".($a->package_id ?? 'NULO')." | c4c_offer_id: ".($a->c4c_offer_id ?? 'NULO'));

            // 2) Recalcular package_id esperado con la lógica central
            $expected = null;
            try {
                if ($a->vehicle && $a->maintenance_type) {
                    $expected = $calculator->calculate($a->vehicle, $a->maintenance_type);
                }
            } catch (\Throwable $e) {
                $expected = null;
            }

            $this->line("  package_id (esperado): ".($expected ?? 'N/A'));
            if ($expected && $a->package_id && $expected !== $a->package_id) {
                $this->error("  ⚠️ Mismatch de paquete: esperado={$expected} vs BD={$a->package_id}");
            }

            // 3) Validar mapeo organizacional centro+marca
            $mapping = null;
            if ($a->center_code && ($a->vehicle_brand_code || $a->vehicle?->brand_code)) {
                $brandCode = $a->vehicle_brand_code ?: $a->vehicle?->brand_code;
                $mapping = CenterOrganizationMapping::forCenterAndBrand($a->center_code, $brandCode)->first();
                $this->line("  Mapeo organizacional: ".($mapping ? 'OK' : 'NO ENCONTRADO'));
                if (!$mapping) {
                    $this->warn("  ⚠️ Falta mapping organizacional para center={$a->center_code}, brand={$brandCode}");
                }
            } else {
                $this->warn('  ⚠️ Cita sin center_code o vehicle_brand_code');
            }

            // 4) Lectura de logs (si corresponde)
            if (in_array($mode, ['both','logs'])) {
                $this->diagnoseFromLogs($a->id, $a->vehicle->license_plate ?? $a->vehicle_plate, $a->customer_ruc);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Buscar señales en logs para una cita específica
     */
    protected function diagnoseFromLogs(int $appointmentId, ?string $plate, ?string $dni): void
    {
        try {
            $logPath = storage_path('logs/laravel.log');
            if (!file_exists($logPath)) {
                $this->warn('  (logs) No existe storage/logs/laravel.log');
                return;
            }

            $content = $this->tailFile($logPath, 20000); // leer últimos ~20KB
            $patterns = [
                '/CreateOfferJob.*appointment_id[^\d]*(\d+)/i',
                '/OfferService.*center_code[^A-Za-z0-9]*([A-Z0-9]+)/i',
                '/OfferService.*brand_code[^A-Za-z0-9]*([A-Z0-9]+)/i',
                '/package_id[^A-Za-z0-9]*([A-Z0-9\-]+)/i',
                '/vehicle_brand_code[^A-Za-z0-9]*([A-Z0-9]+)/i',
                '/vehicle.*license_plate[^A-Za-z0-9]*([A-Z0-9\-]+)/i',
                '/CustomerQuoteBundleMaintainRequest_sync_V1/i',
                '/zOVIDCentro/i'
            ];

            $lines = preg_split('/\r?\n/', $content);

            $hits = [];
            foreach ($lines as $line) {
                if (
                    (strpos($line, "appointment_id\" => {$appointmentId}") !== false) ||
                    ($plate && stripos($line, $plate) !== false) ||
                    ($dni && stripos($line, $dni) !== false)
                ) {
                    // Extraer datos útiles
                    $snippet = $line;
                    foreach ($patterns as $p) {
                        if (preg_match($p, $line, $m)) {
                            $hits[] = trim($snippet);
                            break;
                        }
                    }
                }
            }

            if ($hits) {
                $this->line('  (logs) Coincidencias relevantes:');
                foreach (array_slice($hits, -10) as $h) {
                    $this->line('    · ' . $h);
                }
            } else {
                $this->line('  (logs) Sin coincidencias relevantes recientes para esta cita');
            }

        } catch (\Throwable $e) {
            $this->warn('  (logs) Error analizando logs: ' . $e->getMessage());
        }
    }

    /**
     * Leer cola final del archivo sin cargarlo completo
     */
    protected function tailFile(string $file, int $maxBytes = 20000): string
    {
        $size = filesize($file);
        $fp = fopen($file, 'r');
        if ($size > $maxBytes) {
            fseek($fp, -$maxBytes, SEEK_END);
        }
        $data = stream_get_contents($fp);
        fclose($fp);
        return $data ?: '';
    }
}

