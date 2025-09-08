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

            // 2.1) Estado de descarga de paquete/productos
            $this->diagnosePackageDownload($a);

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
                $this->printJobTimeline($a->id, $a->vehicle->license_plate ?? $a->vehicle_plate, $a->customer_ruc);
            }

            // 5) Análisis de causa raíz (reglas sobre código/modelos/jobs)
            $root = $this->findRootCause($a, $expected);
            if (!empty($root['reasons'])) {
                $this->line('  Posibles causas:');
                foreach ($root['reasons'] as $r) {
                    $this->error('    - ' . $r);
                }
            }
        }

        return self::SUCCESS;
    }

    /**
     * Heurísticas para identificar la causa raíz basadas en código/modelos/jobs.
     */
    protected function findRootCause(\App\Models\Appointment $a, ?string $expectedPackageId): array
    {
        $reasons = [];

        // 1) Cliente wildcard (oferta por flujo distinto)
        $user = \App\Models\User::where('document_number', $a->customer_ruc)->first();
        $isWildcard = $user && $user->c4c_internal_id === '1200166011';
        if ($isWildcard) {
            $reasons[] = 'Cliente comodín: la oferta usa flujo wildcard (sin items), revisar OfferService::crearOfertaWildcard.';
        }

        // 2) Faltan datos críticos para oferta correcta
        if (!$a->vehicle) {
            $reasons[] = 'Vehículo no cargado en la cita: no se puede calcular package_id dinámico.';
        } else {
            if (empty($a->vehicle->tipo_valor_trabajo)) {
                $reasons[] = 'Vehículo sin tipo_valor_trabajo: PackageIdCalculator no puede derivar el paquete correcto.';
            }
        }

        if (empty($a->maintenance_type)) {
            $reasons[] = 'Cita sin maintenance_type: no se puede derivar kilómetros para el paquete.';
        }

        // 3) Marca/centro inconsistentes para mapeo organizacional
        $brandCode = $a->vehicle_brand_code ?: $a->vehicle?->brand_code;
        if (empty($brandCode)) {
            $reasons[] = 'Sin vehicle_brand_code en appointment ni en vehículo: mapeo organizacional puede fallar (oferta errónea).';
        }
        if (empty($a->center_code)) {
            $reasons[] = 'Sin center_code en appointment: mapeo organizacional puede usar defaults incorrectos.';
        } else if (!empty($brandCode)) {
            $exists = CenterOrganizationMapping::forCenterAndBrand($a->center_code, $brandCode)->exists();
            if (!$exists) {
                $reasons[] = "No existe mapeo organizacional para center={$a->center_code}, brand={$brandCode}: oferta puede tomar división/códigos de otra marca.";
            }
        }

        // 4) Mismatch de package_id
        if ($expectedPackageId && $a->package_id && $expectedPackageId !== $a->package_id) {
            $reasons[] = "Mismatch de package_id (esperado={$expectedPackageId} vs BD={$a->package_id}): revisar cálculo dinámico y momento de asignación.";
        }
        if ($expectedPackageId && !$a->package_id) {
            $reasons[] = "package_id esperado={$expectedPackageId} pero la cita no lo tiene: la oferta puede haber usado un paquete por defecto o equivocado.";
        }

        // 5) Señales en logs cercanas a la cita (últimos 20-50KB)
        $logEvidence = $this->collectBriefLogEvidence($a->id, $a->vehicle->license_plate ?? $a->vehicle_plate, $a->customer_ruc);
        if ($logEvidence) {
            $reasons[] = 'Ver evidencias en logs (líneas relevantes encontradas): ' . count($logEvidence) . ' coincidencias.';
        }

        // 6) Heurística exacta: paquete fijado temprano y job de actualización lo omitió
        $logPath = storage_path('logs/laravel.log');
        if (file_exists($logPath)) {
            $content = $this->tailFile($logPath, 50000);
            $hasSetInEnviar = (bool) preg_match('/EnviarCitaC4CJob.*Package ID asignado/i', $content);
            $hasAlreadyHas = (bool) preg_match('/Cita ya tiene package_id/i', $content);
            $hasUpdateTipo = (bool) preg_match('/UpdateVehicleTipoValorTrabajoJob/i', $content);
            if ($hasSetInEnviar && $hasAlreadyHas && $hasUpdateTipo) {
                $reasons[] = 'Paquete asignado temprano (EnviarCitaC4CJob) antes de actualizar tipo_valor_trabajo; luego UpdateAppointmentPackageIdJob lo omitió (already_has_package_id).';
            }
        }

        return [
            'reasons' => $reasons,
        ];
    }

    protected function collectBriefLogEvidence(int $appointmentId, ?string $plate, ?string $dni): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) return [];
        $content = $this->tailFile($logPath, 20000);
        $lines = preg_split('/\r?\n/', $content);
        $hits = [];
        foreach ($lines as $line) {
            if (
                (strpos($line, "appointment_id\" => {$appointmentId}") !== false) ||
                ($plate && stripos($line, $plate) !== false) ||
                ($dni && stripos($line, $dni) !== false)
            ) {
                if (
                    stripos($line, 'Package ID') !== false ||
                    stripos($line, 'Mapeo organizacional') !== false ||
                    stripos($line, 'OfferService') !== false ||
                    stripos($line, 'CreateOfferJob') !== false
                ) {
                    $hits[] = trim($line);
                }
            }
        }
        return array_slice($hits, -10);
    }

    /**
     * Revisar si el paquete se descargó y vinculó correctamente a la cita.
     */
    protected function diagnosePackageDownload(\App\Models\Appointment $a): void
    {
        $packageId = $a->package_id;
        if (!$packageId) {
            $this->warn('  ⚠️ Sin package_id: no es posible descargar productos.');
            // Señalar causas probables exactas
            if (!$a->maintenance_type) {
                $this->error('    · Falta maintenance_type: no se puede calcular el paquete.');
            }
            if (!$a->vehicle) {
                $this->error('    · Falta relación vehicle en la cita.');
            } else if (empty($a->vehicle->tipo_valor_trabajo)) {
                $this->error('    · Vehículo sin tipo_valor_trabajo: no se puede derivar paquete.');
            }
            if ($a->vehicle && !in_array($a->vehicle->brand_code, ['Z01','Z02','Z03'])) {
                $this->error('    · Marca no soportada para paquetes (brand_code distinto de Z01/Z02/Z03).');
            }
            return;
        }

        // Contar productos para la cita
        $appointmentProducts = \App\Models\Product::forAppointment($a->id)->forPackage($packageId)->count();
        // Contar productos maestros recientes
        $masterExistsFresh = \App\Models\Product::existsMasterProductsForPackage($packageId, 24);

        $this->line("  Productos cita ({$packageId}): {$appointmentProducts} | Maestros frescos: ".($masterExistsFresh ? 'SÍ' : 'NO'));

        if ($appointmentProducts === 0) {
            if (!$masterExistsFresh) {
                $this->error('  ❌ No hay productos maestros recientes para el package_id: probable fallo al descargar desde C4C.');
                $this->line('     Revisa logs de DownloadProductsJob/ProductService para este package_id.');
            } else {
                $this->error('  ⚠️ Hay productos maestros pero no están vinculados a la cita: probable fallo al vincular o a disparar CreateOfferJob.');
            }
        }
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
     * Mostrar una línea de tiempo de jobs relevantes para la cita desde logs.
     */
    protected function printJobTimeline(int $appointmentId, ?string $plate, ?string $dni): void
    {
        $logPath = storage_path('logs/laravel.log');
        if (!file_exists($logPath)) return;
        $content = $this->tailFile($logPath, 50000); // ampliar ventana para timeline
        $lines = preg_split('/\r?\n/', $content);

        $keywords = [
            'EnviarCitaC4CJob',
            'SyncAppointmentToC4CJob',
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
                (strpos($line, "appointment_id\" => {$appointmentId}") !== false) ||
                ($plate && stripos($line, $plate) !== false) ||
                ($dni && stripos($line, $dni) !== false)
            ) {
                foreach ($keywords as $k) {
                    if (stripos($line, $k) !== false) {
                        // Extraer timestamp si existe al inicio del log
                        if (preg_match('/^\[(.*?)\]\s+.*$/', $line, $m)) {
                            $events[] = [$m[1], $k, $line];
                        } else {
                            $events[] = ['(sin-ts)', $k, $line];
                        }
                        break;
                    }
                }
            }
        }

        if ($events) {
            $this->line('  (timeline) Jobs y eventos:');
            foreach (array_slice($events, -12) as [$ts, $k, $raw]) {
                $this->line("    · {$ts} | {$k}");
            }
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
