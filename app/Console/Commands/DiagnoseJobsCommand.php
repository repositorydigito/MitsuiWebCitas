<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\Product;
use App\Models\CenterOrganizationMapping;
use App\Services\PackageIdCalculator;
use Illuminate\Console\Command;

class DiagnoseJobsCommand extends Command
{
    protected $signature = 'jobs:diagnose '
        . '{--last=10 : Número de citas a revisar} '
        . '{--dni= : Filtrar por DNI/RUC} '
        . '{--plate= : Filtrar por placa} '
        . '{--log-bytes=200000 : Bytes a leer de logs} '
        . '{--log-lines=200 : Máximo de líneas de log a mostrar} '
        . '{--raw-logs : Imprimir líneas completas} '
        . '{--json= : Ruta de archivo para exportar diagnóstico en JSON}';

    protected $description = 'Diagnostica la cadena de jobs (sync, TVT, package, productos, oferta) para detectar inconsistencias de lógica/descarga/logs.';

    public function handle(): int
    {
        $last = (int) $this->option('last');
        $dni = $this->option('dni');
        $plate = $this->option('plate');
        $logBytes = (int) $this->option('log-bytes');
        $logMaxLines = (int) $this->option('log-lines');
        $rawLogs = (bool) $this->option('raw-logs');
        $jsonPath = $this->option('json');

        $this->info('🧪 Diagnóstico de cadena de jobs');
        $this->line("Parámetros: last={$last}, dni=".($dni ?: 'N/A').", plate=".($plate ?: 'N/A'));

        $appts = Appointment::query()
            ->when($dni, fn($q) => $q->where('customer_ruc', $dni))
            ->when($plate, function ($q) use ($plate) {
                $q->where(function ($sub) use ($plate) {
                    $sub->where('vehicle_plate', $plate)
                        ->orWhereHas('vehicle', fn($v) => $v->where('license_plate', $plate));
                });
            })
            ->with('vehicle')
            ->orderByDesc('created_at')
            ->limit($last)
            ->get();

        if ($appts->isEmpty()) {
            $this->warn('No hay citas que cumplan el filtro.');
            return self::SUCCESS;
        }

        $calc = app(PackageIdCalculator::class);
        $results = [];

        foreach ($appts as $a) {
            $this->newLine();
            $this->info("Cita #{$a->id} ({$a->appointment_number})");
            $this->line("  Cliente: {$a->customer_ruc} | Placa: ".($a->vehicle->license_plate ?? $a->vehicle_plate ?? 'N/A'));

            // Expectation: paquete calculado por TVT + mantenimiento
            $expected = null;
            if ($a->vehicle && $a->maintenance_type) {
                try { $expected = $calc->calculate($a->vehicle, $a->maintenance_type); } catch (\Throwable) {}
            }

            // Productos y oferta
            $productsForAppt = Product::forAppointment($a->id)->count();
            $productsForPkg = $a->package_id ? Product::forAppointment($a->id)->forPackage($a->package_id)->count() : 0;
            $masterFresh = $a->package_id ? Product::existsMasterProductsForPackage($a->package_id, 24) : false;

            // Mapping organizacional
            $brand = $a->vehicle_brand_code ?: $a->vehicle?->brand_code;
            $mappingExists = null;
            if ($a->center_code && $brand) {
                $mappingExists = CenterOrganizationMapping::forCenterAndBrand($a->center_code, $brand)->exists();
            }

            // Inconsistencias
            $issues = [];
            if ($expected && $a->package_id && $expected !== $a->package_id) {
                $issues[] = "Mismatch paquete: esperado={$expected}, bd={$a->package_id}";
            }
            if (!$a->package_id) {
                $issues[] = 'Sin package_id: no descargará productos ni ofertará';
            } else {
                if ($productsForAppt === 0) {
                    $issues[] = 'Sin productos para la cita';
                    if (!$masterFresh) $issues[] = 'No hay productos maestros recientes para ese paquete';
                } elseif ($productsForPkg === 0) {
                    $issues[] = 'Productos de la cita no coinciden con package_id actual';
                }
            }
            if ($a->c4c_offer_id && $productsForAppt === 0) {
                $issues[] = 'Oferta creada sin productos de cita';
            }
            if ($mappingExists === false) {
                $issues[] = 'Falta mapeo organizacional center_code + brand';
            }

            $this->line('  Estado:');
            $this->line('    - package_id: ' . ($a->package_id ?? 'NULO') . ' | esperado: ' . ($expected ?? 'N/A'));
            $this->line("    - productos cita: {$productsForAppt} (para paquete actual: {$productsForPkg}) | maestros frescos: " . ($masterFresh ? 'SÍ' : 'NO'));
            $this->line('    - oferta: ' . ($a->c4c_offer_id ?: 'NO'));
            if ($mappingExists !== null) {
                $this->line('    - mapeo organizacional: ' . ($mappingExists ? 'OK' : 'FALTA'));
            }

            // Timeline de jobs desde logs
            $timeline = $this->extractJobTimeline($a, $logBytes);
            if ($timeline) {
                $this->line('  Timeline de jobs:');
                foreach ($timeline as $evt) {
                    $this->line('    · ' . $evt);
                }
            }

            // Evidencia de logs cruda
            $hits = $this->findRelevantLogLines($a, $logBytes);
            if ($hits) {
                $this->line('  Logs relevantes:');
                foreach (array_slice($hits, -$logMaxLines) as $h) {
                    $this->line('    · ' . ($rawLogs ? $h : $this->compactLogLine($h)));
                }
            }

            if ($issues) {
                $this->error('  Inconsistencias detectadas:');
                foreach ($issues as $i) { $this->error('    - ' . $i); }
            } else {
                $this->info('  Sin inconsistencias críticas');
            }

            $results[] = [
                'appointment_id' => $a->id,
                'appointment_number' => $a->appointment_number,
                'package_id' => $a->package_id,
                'expected_package_id' => $expected,
                'products_for_appointment' => $productsForAppt,
                'products_for_current_package' => $productsForPkg,
                'master_products_fresh' => $masterFresh,
                'offer_id' => $a->c4c_offer_id,
                'mapping_exists' => $mappingExists,
                'issues' => $issues,
            ];
        }

        if ($jsonPath) {
            @file_put_contents($jsonPath, json_encode($results, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
            $this->info("📄 Reporte JSON guardado en: {$jsonPath}");
        }

        return self::SUCCESS;
    }

    protected function extractJobTimeline(Appointment $a, int $logBytes): array
    {
        $content = $this->tailLogsAcrossFiles($logBytes);
        if ($content === '') return [];
        $lines = preg_split('/\r?\n/', $content);
        $keys = [
            'SyncAppointmentToC4CJob',
            'EnviarCitaC4CJob',
            'ProcessAppointmentAfterCreationJob',
            'UpdateVehicleTipoValorTrabajoJob',
            'UpdateAppointmentPackageIdJob',
            'DownloadProductsJob',
            'CreateOfferJob',
            'Package ID actualizado',
            'Cita ya tiene package_id',
        ];
        $events = [];
        foreach ($lines as $line) {
            if (
                (strpos($line, "appointment_id\" => {$a->id}") !== false) ||
                ($a->vehicle?->license_plate && stripos($line, $a->vehicle->license_plate) !== false) ||
                ($a->vehicle_plate && stripos($line, $a->vehicle_plate) !== false) ||
                (stripos($line, (string)$a->customer_ruc) !== false)
            ) {
                foreach ($keys as $k) {
                    if (stripos($line, $k) !== false) {
                        if (preg_match('/^\[(.*?)\]/', $line, $m)) {
                            $events[] = $m[1] . ' | ' . $k;
                        } else {
                            $events[] = $k;
                        }
                        break;
                    }
                }
            }
        }
        return array_slice($events, -12);
    }

    protected function findRelevantLogLines(Appointment $a, int $logBytes): array
    {
        $content = $this->tailLogsAcrossFiles($logBytes);
        if ($content === '') return [];
        $patterns = [
            '/CreateOfferJob/i',
            '/OfferService/i',
            '/package_id/i',
            '/vehicle_brand_code/i',
            '/license_plate/i',
            '/UpdateAppointmentPackageIdJob/i',
            '/UpdateVehicleTipoValorTrabajoJob/i',
            '/DownloadProductsJob/i',
        ];
        $hits = [];
        foreach (preg_split('/\r?\n/', $content) as $line) {
            if (
                (strpos($line, "appointment_id\" => {$a->id}") !== false) ||
                ($a->vehicle?->license_plate && stripos($line, $a->vehicle->license_plate) !== false) ||
                ($a->vehicle_plate && stripos($line, $a->vehicle_plate) !== false) ||
                (stripos($line, (string)$a->customer_ruc) !== false)
            ) {
                foreach ($patterns as $p) {
                    if (preg_match($p, $line)) { $hits[] = $line; break; }
                }
            }
        }
        return $hits;
    }

    protected function tailLogsAcrossFiles(int $maxBytes): string
    {
        $dir = storage_path('logs');
        if (!is_dir($dir)) return '';
        $files = glob($dir . '/*.log') ?: [];
        usort($files, fn($a, $b) => (@filemtime($b) ?: 0) <=> (@filemtime($a) ?: 0));
        $remaining = $maxBytes; $chunks = [];
        foreach ($files as $f) {
            if ($remaining <= 0) break;
            $size = @filesize($f) ?: 0; if ($size <= 0) continue;
            $read = min($size, $remaining);
            $chunks[] = $this->tailFile($f, $read);
            $remaining -= $read;
        }
        return implode("\n--- FILE BREAK ---\n", array_filter($chunks));
    }

    protected function tailFile(string $file, int $maxBytes): string
    {
        $size = @filesize($file) ?: 0; if ($size <= 0) return '';
        $fp = @fopen($file, 'r'); if (!$fp) return '';
        if ($size > $maxBytes) { fseek($fp, -$maxBytes, SEEK_END); }
        $data = stream_get_contents($fp) ?: '';
        fclose($fp);
        return $data;
    }

    protected function compactLogLine(string $line): string
    {
        $line = preg_replace('/\{[^}]*\}/', '{...}', $line, 1);
        return strlen($line) > 300 ? substr($line, 0, 300) . ' …' : $line;
    }
}

