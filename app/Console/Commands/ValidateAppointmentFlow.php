<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\Models\Appointment;
use App\Models\Product;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\C4C\ProductService;
use App\Services\PackageIdCalculator;

class ValidateAppointmentFlow extends Command
{
    protected $signature = 'appointment:validate
                           {--id= : ID específico del appointment a validar}
                           {--hours=24 : Horas hacia atrás para analizar (default: 24h)}
                           {--detailed : Mostrar análisis detallado}
                           {--fix : Intentar correcciones automáticas}';

    protected $description = 'Validar flujo completo de citas: creación → jobs → productos → ofertas';

    protected array $issues = [];
    protected array $warnings = [];
    protected array $success = [];

    public function handle()
    {
        $this->info('🔍 VALIDACIÓN INTEGRAL DEL FLUJO DE CITAS Y OFERTAS');
        $this->info(str_repeat('=', 70));
        $this->newLine();

        // 1. Validar configuración del sistema
        $this->validateSystemConfiguration();

        // 2. Obtener appointments a analizar
        $appointments = $this->getAppointmentsToAnalyze();
        
        if ($appointments->isEmpty()) {
            $this->error('❌ No se encontraron appointments para analizar');
            return 1;
        }

        $this->info("📊 Analizando {$appointments->count()} appointments");
        $this->newLine();

        // 3. Analizar cada appointment
        foreach ($appointments as $appointment) {
            $this->analyzeAppointment($appointment);
            $this->newLine();
        }

        // 4. Mostrar resumen final
        $this->showFinalSummary();

        return 0;
    }

    protected function validateSystemConfiguration(): void
    {
        $this->comment('🔧 VALIDANDO CONFIGURACIÓN DEL SISTEMA');
        $this->line(str_repeat('-', 50));

        // Validar variables de entorno críticas
        $envVars = [
            'C4C_WEBSERVICE_ENABLED',
            'SAP_ENABLED',
            'USE_MOCK_SERVICES',
            'QUEUE_CONNECTION',
            'DB_CONNECTION'
        ];

        foreach ($envVars as $var) {
            $value = env($var);
            if ($value === null) {
                $this->addIssue('CONFIG', "Variable de entorno {$var} no está configurada");
            } else {
                $this->line("  ✅ {$var}: {$value}");
            }
        }

        // Validar conexión de base de datos
        try {
            $appointmentsCount = DB::table('appointments')->count();
            $this->line("  ✅ Conexión BD: OK ({$appointmentsCount} appointments totales)");
        } catch (\Exception $e) {
            $this->addIssue('CONFIG', "Error conexión BD: {$e->getMessage()}");
        }

        // Validar sistema de colas
        $queueConnection = config('queue.default');
        $this->line("  ✅ Sistema de colas: {$queueConnection}");

        $this->newLine();
    }

    protected function getAppointmentsToAnalyze()
    {
        $appointmentId = $this->option('id');
        $hours = (int) $this->option('hours');

        if ($appointmentId) {
            return Appointment::where('id', $appointmentId)
                ->with(['vehicle', 'products'])
                ->get();
        }

        return Appointment::where('created_at', '>=', now()->subHours($hours))
            ->with(['vehicle', 'products'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    protected function analyzeAppointment(Appointment $appointment): void
    {
        $this->info("🎯 ANALIZANDO APPOINTMENT #{$appointment->id}");
        $this->info("   Número: {$appointment->appointment_number}");
        $this->info("   Creado: {$appointment->created_at}");
        $this->info("   Estado: {$appointment->status}");
        $this->line(str_repeat('-', 60));

        // 1. Identificar tipo de cliente
        $clientType = $this->identifyClientType($appointment);
        $this->line("👤 Tipo de cliente: {$clientType}");

        // 2. Validar modalidad de servicio
        $this->validateServiceMode($appointment);

        // 3. Validar package_id y cálculo
        $this->validatePackageId($appointment);

        // 4. Validar flujo de jobs
        $this->validateJobFlow($appointment, $clientType);

        // 5. Validar productos (solo cliente normal)
        if ($clientType === 'NORMAL') {
            $this->validateProducts($appointment);
        }

        // 6. Validar creación de oferta
        $this->validateOfferCreation($appointment);

        // 7. Analizar logs específicos
        $this->analyzeLogs($appointment);

        // 8. Aplicar correcciones si es necesario
        if ($this->option('fix')) {
            $this->attemptFixes($appointment);
        }
    }

    protected function identifyClientType(Appointment $appointment): string
    {
        // Buscar usuario por customer_ruc ya que no hay relación directa user
        try {
            $user = User::where('document_number', $appointment->customer_ruc)->first();
            
            if (!$user) {
                $this->addWarning('CLIENT', "No se encontró usuario con RUC {$appointment->customer_ruc}");
                return 'UNKNOWN';
            }

            $isWildcard = $user->c4c_internal_id === '1200166011';
            return $isWildcard ? 'WILDCARD' : 'NORMAL';
            
        } catch (\Exception $e) {
            $this->addWarning('CLIENT', "Error identificando tipo cliente: {$e->getMessage()}");
            return 'UNKNOWN';
        }
    }

    protected function validateServiceMode(Appointment $appointment): void
    {
        $serviceMode = $appointment->service_mode ?? 'regular';
        $this->line("🚀 Modalidad de servicio: {$serviceMode}");

        if ($serviceMode === 'express') {
            // Validar que tenga los requisitos para Express
            if (empty($appointment->vehicle->nummot ?? '')) {
                $this->addWarning('EXPRESS', "Appointment #{$appointment->id} marcado como Express pero sin código de motor");
            } else {
                $this->addSuccess('EXPRESS', "Modalidad Express correctamente configurada");
            }
        }
    }

    protected function validatePackageId(Appointment $appointment): void
    {
        $packageId = $appointment->package_id;
        $maintenanceType = $appointment->maintenance_type;

        $this->line("📦 Package ID: " . ($packageId ?? 'NULL'));
        $this->line("🔧 Maintenance Type: " . ($maintenanceType ?? 'NULL'));

        if (!$packageId && $maintenanceType) {
            $this->addIssue('PACKAGE_ID', "Appointment #{$appointment->id} tiene maintenance_type pero no package_id");
            
            // Intentar recalcular
            if ($appointment->vehicle && $this->option('detailed')) {
                try {
                    $calculator = new PackageIdCalculator();
                    $recalculatedId = $calculator->calculate($appointment->vehicle, $maintenanceType);
                    $this->line("  💡 Package ID recalculado: " . ($recalculatedId ?? 'NULL'));
                    
                    if ($recalculatedId) {
                        $this->addWarning('PACKAGE_ID', "Package ID puede recalcularse automáticamente");
                    }
                } catch (\Exception $e) {
                    $this->addIssue('PACKAGE_ID', "Error recalculando package_id: {$e->getMessage()}");
                }
            }
        } elseif ($packageId) {
            // Validar formato del package_id
            if (preg_match('/^M\d+-\w+$/', $packageId)) {
                $this->addSuccess('PACKAGE_ID', "Format de package_id correcto: {$packageId}");
            } else {
                $this->addWarning('PACKAGE_ID', "Formato de package_id irregular: {$packageId}");
            }
        }
    }

    protected function validateJobFlow(Appointment $appointment, string $clientType): void
    {
        $this->line("⚙️ Validando flujo de jobs detalladamente...");
        
        $timeSinceCreation = now()->diffInMinutes($appointment->created_at);
        $this->line("  ⏱️ Tiempo desde creación: {$timeSinceCreation} minutos");

        // Verificar si tiene c4c_uuid (cita creada en C4C)
        if ($appointment->c4c_uuid) {
            $this->addSuccess('JOBS', "Cita creada en C4C: {$appointment->c4c_uuid}");
            
            // Verificar flujo según tipo de cliente
            if ($clientType === 'NORMAL') {
                $this->validateNormalClientFlow($appointment, $timeSinceCreation);
            } elseif ($clientType === 'WILDCARD') {
                $this->validateWildcardClientFlow($appointment, $timeSinceCreation);
            }
            
        } else {
            if ($timeSinceCreation > 5) {
                $this->addIssue('JOBS', "EnviarCitaC4CJob falló - No c4c_uuid después de {$timeSinceCreation}min");
                
                // Buscar razón específica del fallo
                $this->investigateC4CJobFailure($appointment);
            } else {
                $this->addWarning('JOBS', "Cita reciente, c4c_uuid puede estar procesándose");
            }
        }

        // Verificar failed_jobs con detalles
        $failedJobs = $this->getFailedJobsForAppointment($appointment->id);
        if ($failedJobs->isNotEmpty()) {
            $this->error("  🚨 JOBS FALLIDOS DETECTADOS:");
            foreach ($failedJobs as $failedJob) {
                $jobClass = $this->extractJobClass($failedJob->payload);
                $failedAt = Carbon::parse($failedJob->failed_at);
                
                $this->addIssue('JOBS', "Job fallido: {$jobClass} - {$failedAt->diffForHumans()}");
                $this->line("    💥 Excepción: " . \Illuminate\Support\Str::limit($failedJob->exception, 200));
            }
        }
        
        // Verificar jobs pendientes
        $pendingJobs = DB::table('jobs')
            ->where('payload', 'like', "%{$appointment->id}%")
            ->count();
            
        if ($pendingJobs > 0) {
            $this->addWarning('JOBS', "Jobs pendientes en cola: {$pendingJobs}");
        }
    }
    
    protected function validateNormalClientFlow(Appointment $appointment, int $timeSinceCreation): void
    {
        $this->line("  📋 Validando flujo CLIENTE NORMAL:");
        
        // Paso 1: EnviarCitaC4CJob ✅ (ya confirmado por c4c_uuid)
        $this->line("    1️⃣ EnviarCitaC4CJob: ✅ COMPLETADO");
        
        // Paso 2: DownloadProductsJob
        $productsCount = $appointment->products()->count();
        if ($appointment->package_id) {
            if ($productsCount > 0) {
                $this->addSuccess('JOBS', "2️⃣ DownloadProductsJob: ✅ COMPLETADO - {$productsCount} productos");
                
                // Paso 3: CreateOfferJob
                $this->validateOfferJobExecution($appointment, $timeSinceCreation);
            } else {
                // INVESTIGAR SIEMPRE si no hay productos - sin importar el tiempo
                $this->addIssue('JOBS', "2️⃣ DownloadProductsJob: ❌ FALLÓ - Sin productos después de {$timeSinceCreation}min");
                $this->investigateDownloadProductsFailure($appointment);
            }
        } else {
            $this->addIssue('JOBS', "2️⃣ DownloadProductsJob: ❌ NO PUEDE EJECUTARSE - Sin package_id");
        }
    }
    
    protected function validateWildcardClientFlow(Appointment $appointment, int $timeSinceCreation): void
    {
        $this->line("  🎭 Validando flujo CLIENTE WILDCARD:");
        
        // Paso 1: EnviarCitaC4CJob ✅ (ya confirmado por c4c_uuid)
        $this->line("    1️⃣ EnviarCitaC4CJob: ✅ COMPLETADO");
        
        // Paso 2: CreateOfferJob (inmediato, salta DownloadProductsJob)
        $this->line("    2️⃣ DownloadProductsJob: ⏭️ SALTADO (cliente wildcard)");
        
        // Paso 3: CreateOfferJob
        $this->validateOfferJobExecution($appointment, $timeSinceCreation);
    }
    
    protected function validateOfferJobExecution(Appointment $appointment, int $timeSinceCreation): void
    {
        if ($appointment->c4c_offer_id) {
            $this->addSuccess('JOBS', "3️⃣ CreateOfferJob: ✅ COMPLETADO - Oferta: {$appointment->c4c_offer_id}");
            
            if ($appointment->offer_created_at) {
                $offerTime = Carbon::parse($appointment->offer_created_at);
                $totalTime = $appointment->created_at->diffInMinutes($offerTime);
                $this->line("    ⏱️ Tiempo total cita→oferta: {$totalTime} minutos");
            }
        } else {
            // SIEMPRE investigar appointments sin oferta para este análisis
            $this->addIssue('JOBS', "3️⃣ CreateOfferJob: ❌ FALLÓ - Sin oferta después de {$timeSinceCreation}min");
            
            if ($appointment->offer_creation_failed) {
                $this->addIssue('JOBS', "Error específico: {$appointment->offer_creation_error}");
            }
            
            $this->investigateCreateOfferFailure($appointment);
            
            // BÚSQUEDA EXHAUSTIVA DE XML DE OFERTAS
            $this->line("🔍 INVESTIGACIÓN EXHAUSTIVA - Buscando logs de XML de ofertas...");
            $this->searchJobExecutionLogs($appointment, 'CreateOfferJob');
        }
    }
    
    protected function investigateC4CJobFailure(Appointment $appointment): void
    {
        $this->line("    🔍 Investigando fallo EnviarCitaC4CJob...");
        
        // Verificar configuración C4C
        $c4cEnabled = env('C4C_WEBSERVICE_ENABLED', false);
        if (!$c4cEnabled) {
            $this->addIssue('CONFIG', "C4C_WEBSERVICE_ENABLED está deshabilitado");
        }
        
        // Verificar datos requeridos
        if (!$appointment->vehicle_id) {
            $this->addIssue('DATA', "Appointment sin vehicle_id");
        }
        if (!$appointment->premise_id) {
            $this->addIssue('DATA', "Appointment sin premise_id");
        }
    }
    
    protected function investigateDownloadProductsFailure(Appointment $appointment): void
    {
        $this->error("    🔍 INVESTIGACIÓN PROFUNDA - DownloadProductsJob NO EJECUTADO:");
        
        if (!$appointment->package_id) {
            $this->addIssue('DATA', "Sin package_id para descargar productos");
            return;
        }
        
        $this->line("    📦 Package ID: {$appointment->package_id}");
        $this->line("    🔍 Buscando evidencia de ejecución del job...");
        
        // Verificar en logs específicamente
        $this->searchJobExecutionLogs($appointment, 'DownloadProductsJob');
        
        // NUEVA FUNCIONALIDAD: Verificar endpoint de productos C4C
        $this->investigateProductsEndpoint($appointment->package_id);
        
        // Verificar en failed_jobs específicamente
        $downloadFailedJobs = DB::table('failed_jobs')
            ->where('payload', 'like', '%DownloadProductsJob%')
            ->where('payload', 'like', "%{$appointment->id}%")
            ->orderBy('failed_at', 'desc')
            ->get();
            
        if ($downloadFailedJobs->isNotEmpty()) {
            $this->error("    🚨 DownloadProductsJob FALLÓ:");
            foreach ($downloadFailedJobs as $job) {
                $failedAt = Carbon::parse($job->failed_at);
                $this->line("      ❌ Falló: {$failedAt->diffForHumans()}");
                $this->line("      💥 Error: " . \Illuminate\Support\Str::limit($job->exception, 300));
            }
        } else {
            $this->error("    ❓ DownloadProductsJob NUNCA SE EJECUTÓ - Investigando por qué...");
            $this->investigateJobNeverRan($appointment, 'DownloadProductsJob');
        }
        
        // Verificar configuración
        $c4cEnabled = env('C4C_WEBSERVICE_ENABLED', false);
        if (!$c4cEnabled) {
            $this->addIssue('CONFIG', "C4C_WEBSERVICE_ENABLED deshabilitado");
        }
    }
    
    protected function investigateProductsEndpoint(string $packageId): void
    {
        $this->comment("    🌐 VERIFICANDO ENDPOINT DE PRODUCTOS C4C:");
        
        // Construir URL del endpoint
        $baseUrl = env('C4C_PRODUCTS_URL', 'https://my317791.crm.ondemand.com/sap/c4c/odata/cust/v1/obtenerlistadoproductos/BOListaProductosProductosVinculadosCollection');
        $filter = "zEstado eq '02' and zIDPadre eq '{$packageId}'";
        $fullUrl = $baseUrl . '?$filter=' . urlencode($filter);
        
        $this->line("    🔗 URL: " . $fullUrl);
        $this->line("    📋 Filtros: zEstado='02' AND zIDPadre='{$packageId}'");
        
        // Buscar logs específicos de este endpoint
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            $this->comment("    🔍 Buscando logs de consultas de productos...");
            
            $patterns = [
                "Consultando productos vinculados C4C",
                "obtenerProductosVinculados",
                "ProductService.*makeRequest",
                $packageId,
                "BOListaProductosProductosVinculadosCollection",
                "Error.*ProductService",
                "productos obtenidos de C4C"
            ];
            
            $foundProductLogs = false;
            
            foreach ($patterns as $pattern) {
                $command = "grep -i '{$pattern}' " . escapeshellarg($logFile) . " | tail -10 2>/dev/null || true";
                $output = shell_exec($command);
                
                if (!empty(trim($output))) {
                    $foundProductLogs = true;
                    $this->line("    📄 Logs encontrados para patrón: {$pattern} (" . count(explode("\n", trim($output))) . " líneas)");
                }
            }
            
            if (!$foundProductLogs) {
                $this->error("    ❌ NO se encontraron logs de consultas de productos!");
                $this->line("    💡 Esto indica que ProductService nunca intentó consultar C4C");
            }
        }
        
        // Verificar configuración del endpoint
        $this->validateProductsConfiguration();
    }
    
    protected function validateProductsConfiguration(): void
    {
        $this->comment("    ⚙️ VALIDANDO CONFIGURACIÓN ENDPOINT PRODUCTOS:");
        
        $config = [
            'C4C_PRODUCTS_URL' => env('C4C_PRODUCTS_URL'),
            'C4C_PRODUCTS_USERNAME' => env('C4C_PRODUCTS_USERNAME'),
            'C4C_PRODUCTS_PASSWORD' => env('C4C_PRODUCTS_PASSWORD') ? '***SET***' : 'NOT_SET',
            'C4C_TIMEOUT' => env('C4C_TIMEOUT', 120)
        ];
        
        foreach ($config as $key => $value) {
            if (empty($value) && $key !== 'C4C_TIMEOUT') {
                $this->error("    ❌ {$key}: NO CONFIGURADO");
                $this->addIssue('CONFIG', "Variable {$key} no está configurada");
            } else {
                $this->line("    ✅ {$key}: " . (is_string($value) ? \Illuminate\Support\Str::limit($value, 60) : $value));
            }
        }
    }
    
    protected function searchJobExecutionLogs(Appointment $appointment, string $jobName): void
    {
        $this->comment("🔍 Buscando logs de ejecución para {$jobName}...");
        
        $logFiles = [
            storage_path('logs/laravel.log'),
            storage_path('logs/jobs.log'),
            storage_path('logs/c4c.log')
        ];
        
        $foundLogs = false;
        
        foreach ($logFiles as $logFile) {
            if (!file_exists($logFile)) {
                continue;
            }
            
            $patterns = [
                $jobName,
                "appointment.*{$appointment->id}",
                "job.*{$jobName}.*started",
                "job.*{$jobName}.*completed",
                "job.*{$jobName}.*failed",
                "c4c.*offer.*xml",
                "xml.*offer",
                "CustomerQuote",
                "oferta.*xml",
                "generando.*xml",
                "enviando.*oferta",
                "Creating.*offer.*XML",
                "OfferService.*crearOferta"
            ];
            
            foreach ($patterns as $pattern) {
                $command = "grep -i '{$pattern}' " . escapeshellarg($logFile) . " | head -50 2>/dev/null || true";
                $output = shell_exec($command);
                
                if (!empty(trim($output))) {
                    $foundLogs = true;
                    $lines = count(explode("\n", trim($output)));
                    $this->line("    📄 Encontrado en " . basename($logFile) . " - patrón: {$pattern} ({$lines} líneas)");
                }
            }
        }
        
        if (!$foundLogs) {
            $this->warn("    ⚠️  No se encontraron logs específicos para {$jobName}");
        }
        
        // Búsqueda específica de XML de ofertas
        $this->searchOfferXmlLogs($appointment);
    }
    
    protected function searchOfferXmlLogs(Appointment $appointment): void
    {
        $this->comment("🔍 Buscando específicamente logs de XML de ofertas...");
        
        $logFiles = [
            storage_path('logs/laravel.log'),
            storage_path('logs/c4c.log'),
            storage_path('logs/soap.log')
        ];
        
        $xmlPatterns = [
            "CustomerQuote",
            "ProcessingTypeCode.*Z300",
            "BuyerParty.*BusinessPartnerInternalID",
            "y6s:zOVPlaca",
            "y6s:zOVKilometraje", 
            "crearOfertaDesdeCita",
            "crearOfertaWildcard",
            "XML.*oferta.*generado",
            "Enviando.*XML.*C4C",
            "CreateOfferJob.*ejecutando",
            "OfferService.*generando",
            "appointment.*{$appointment->id}.*offer"
        ];
        
        $foundXmlLogs = false;
        
        foreach ($logFiles as $logFile) {
            if (!file_exists($logFile)) {
                continue;
            }
            
            foreach ($xmlPatterns as $pattern) {
                $command = "grep -i '{$pattern}' " . escapeshellarg($logFile) . " | grep -v 'DEBUG' | head -20 2>/dev/null || true";
                $output = shell_exec($command);
                
                if (!empty(trim($output))) {
                    $foundXmlLogs = true;
                    $lines = count(explode("\n", trim($output)));
                    $this->line("    🔧 XML/Oferta logs encontrados - patrón: {$pattern} ({$lines} líneas)");
                }
            }
        }
        
        if (!$foundXmlLogs) {
            $this->error("    ❌ NO se encontraron logs de generación de XML de ofertas!");
            $this->line("    💡 Esto indica que CreateOfferJob nunca ejecutó OfferService");
        }
        
        // Verificar logs de respuesta C4C
        $this->searchC4CResponseLogs($appointment);
    }
    
    protected function searchC4CResponseLogs(Appointment $appointment): void
    {
        $this->comment("🔍 Buscando logs de respuesta C4C...");
        
        $logFiles = [
            storage_path('logs/laravel.log'),
            storage_path('logs/c4c.log')
        ];
        
        $responsePatterns = [
            "C4C.*response",
            "respuesta.*C4C",
            "offer.*created.*successfully",
            "oferta.*creada.*exitosamente",
            "c4c_offer_id",
            "Error.*creating.*offer",
            "Error.*creando.*oferta",
            "appointment.*{$appointment->id}.*response"
        ];
        
        $foundResponseLogs = false;
        
        foreach ($logFiles as $logFile) {
            if (!file_exists($logFile)) {
                continue;
            }
            
            foreach ($responsePatterns as $pattern) {
                $command = "grep -i '{$pattern}' " . escapeshellarg($logFile) . " | head -10 2>/dev/null || true";
                $output = shell_exec($command);
                
                if (!empty(trim($output))) {
                    $foundResponseLogs = true;
                    $lines = count(explode("\n", trim($output)));
                    $this->line("    📡 Respuesta C4C encontrada - patrón: {$pattern} ({$lines} líneas)");
                }
            }
        }
        
        if (!$foundResponseLogs) {
            $this->error("    ❌ NO se encontraron logs de respuesta C4C para ofertas!");
        }
    }
    
    protected function analyzeAppointmentFlow(array $logs, Appointment $appointment): void
    {
        $this->line("    🔍 ANÁLISIS DE FLUJO DETECTADO:");
        
        $flowSteps = [
            'Cita Creada' => ['CitaCreada', 'appointment.*created', 'creando.*cita'],
            'EnviarCita' => ['EnviarCitaC4CJob', 'enviando.*cita', 'sending.*appointment'],
            'C4C Response' => ['c4c_uuid', 'C4C.*response', 'respuesta.*C4C'],
            'DownloadProducts' => ['DownloadProductsJob', 'descargando.*productos', 'downloading.*products'],
            'CreateOffer' => ['CreateOfferJob', 'creando.*oferta', 'creating.*offer'],
            'Express Check' => ['express', 'Express', 'modalidad.*express']
        ];
        
        foreach ($flowSteps as $step => $patterns) {
            $matches = [];
            foreach ($logs as $log) {
                foreach ($patterns as $pattern) {
                    if (preg_match("/{$pattern}/i", $log)) {
                        $matches[] = $log;
                        break;
                    }
                }
            }
            
            if (!empty($matches)) {
                $this->line("      ✅ {$step}: " . count($matches) . " eventos");
                // Mostrar el último match
                $lastMatch = end($matches);
                $this->line("         ➡️ " . \Illuminate\Support\Str::limit(trim($lastMatch), 120));
            } else {
                $this->error("      ❌ {$step}: NO DETECTADO");
            }
        }
        
        // Verificar específicamente tipo de cliente en logs
        $this->checkClientTypeInLogs($logs, $appointment);
    }
    
    protected function checkClientTypeInLogs(array $logs, Appointment $appointment): void
    {
        $this->line("    🎭 VERIFICANDO TIPO DE CLIENTE EN LOGS:");
        
        $wildcardPatterns = ['1200166011', 'wildcard', 'comodin'];
        $expressPatterns = ['express', 'Express', 'servicio.*express'];
        
        $isWildcardInLogs = false;
        $isExpressInLogs = false;
        
        foreach ($logs as $log) {
            foreach ($wildcardPatterns as $pattern) {
                if (str_contains($log, $pattern)) {
                    $isWildcardInLogs = true;
                    break;
                }
            }
            
            foreach ($expressPatterns as $pattern) {
                if (preg_match("/{$pattern}/i", $log)) {
                    $isExpressInLogs = true;
                    break;
                }
            }
        }
        
        // Analizar consecuencias
        if ($isWildcardInLogs) {
            $this->line("      🎭 CLIENTE WILDCARD detectado en logs");
            if ($appointment->package_id) {
                $this->error("    ⚠️ INCONSISTENCIA: Cliente WILDCARD pero tiene package_id");
            }
        }
        
        if ($isExpressInLogs) {
            $this->line("      🚀 MODALIDAD EXPRESS detectada en logs");
        }
        
        if (!$isWildcardInLogs && !$isExpressInLogs) {
            $this->line("    📋 CLIENTE NORMAL - Debería ejecutar DownloadProductsJob");
        }
    }
    
    protected function searchByDate(Appointment $appointment): void
    {
        $this->line("    📅 BÚSQUEDA POR FECHA...");
        
        $logFile = storage_path('logs/laravel.log');
        $createdDate = $appointment->created_at->format('Y-m-d');
        $createdHour = $appointment->created_at->format('H:');
        
        try {
            // Buscar logs de esa fecha y hora específica
            $command = "grep '{$createdDate}.*{$createdHour}' " . escapeshellarg($logFile) . " | head -20 2>/dev/null || true";
            $output = shell_exec($command);
            
            if ($output) {
                $lines = count(explode("\n", trim($output)));
                $this->comment("    📄 LOGS DE LA HORA DE CREACIÓN: {$lines} líneas encontradas");
            } else {
                $this->error("    ❌ No hay logs de esa fecha/hora específica");
            }
        } catch (\Exception $e) {
            $this->error("    ❌ Error en búsqueda por fecha: {$e->getMessage()}");
        }
    }
    
    protected function investigateJobNeverRan(Appointment $appointment, string $jobName): void
    {
        $this->line("    🔍 Investigando por qué {$jobName} nunca se ejecutó:");
        
        // Verificar si EnviarCitaC4CJob debería haber disparado DownloadProductsJob
        if ($jobName === 'DownloadProductsJob') {
            $this->line("    📋 Verificando si EnviarCitaC4CJob debería haber disparado DownloadProductsJob...");
            
            // Buscar logs de EnviarCitaC4CJob
            $this->searchEnviarCitaJobDispatch($appointment);
            
            // Verificar tiempo desde que se envió la cita
            if ($appointment->c4c_uuid) {
                $timeSinceCreation = now()->diffInMinutes($appointment->created_at);
                $this->line("    ⏱️ Tiempo desde creación: {$timeSinceCreation} minutos");
                
                if ($timeSinceCreation > 5) {
                    $this->error("    🚨 PROBLEMA: EnviarCitaC4CJob se ejecutó (tiene c4c_uuid) pero NO disparó DownloadProductsJob");
                    $this->line("    💡 POSIBLES CAUSAS:");
                    $this->line("      • Error en EnviarCitaC4CJob después de crear la cita");
                    $this->line("      • Fallo en dispatch() de DownloadProductsJob");
                    $this->line("      • Problema en la lógica de cliente normal vs wildcard");
                }
            }
        }
        
        // Verificar configuración de colas
        $queueConnection = config('queue.default');
        $this->line("    🔧 Configuración de cola: {$queueConnection}");
        
        if ($queueConnection === 'sync') {
            $this->addWarning('CONFIG', 'Cola configurada como SYNC - jobs se ejecutan inmediatamente');
        }
        
        // Verificar workers activos
        $pendingJobsTotal = DB::table('jobs')->count();
        $this->line("    📊 Total jobs pendientes en sistema: {$pendingJobsTotal}");
        
        if ($pendingJobsTotal > 50) {
            $this->addWarning('QUEUE', 'Muchos jobs pendientes - posible problema con workers');
        }
    }
    
    protected function searchEnviarCitaJobDispatch(Appointment $appointment): void
    {
        $logFile = storage_path('logs/laravel.log');
        if (!File::exists($logFile)) return;
        
        $logContent = File::get($logFile);
        $lines = explode("\n", $logContent);
        
        $dispatchLogs = [];
        $appointmentId = $appointment->id;
        $appointmentNumber = $appointment->appointment_number;
        
        foreach ($lines as $line) {
            if ((str_contains($line, 'EnviarCita') || str_contains($line, 'DownloadProducts') || str_contains($line, 'dispatch')) &&
                (str_contains($line, $appointmentId) || str_contains($line, $appointmentNumber))) {
                $dispatchLogs[] = $line;
            }
        }
        
        if (!empty($dispatchLogs)) {
            $this->comment("    📄 LOGS DE DISPATCH DE JOBS: " . count($dispatchLogs) . " eventos encontrados");
        } else {
            $this->error("    ❌ NO HAY LOGS DE DISPATCH - Problema en EnviarCitaC4CJob");
        }
    }
    
    protected function investigateCreateOfferFailure(Appointment $appointment): void
    {
        $this->line("    🔍 Investigando fallo CreateOfferJob...");
        
        // Verificar si debe tener productos (cliente normal)
        $user = User::where('document_number', $appointment->customer_ruc)->first();
        if ($user && $user->c4c_internal_id !== '1200166011') {
            // Cliente normal - debe tener productos
            $productsCount = $appointment->products()->count();
            if ($productsCount === 0) {
                $this->addIssue('DATA', "Cliente normal sin productos - CreateOfferJob no puede ejecutarse");
            }
        }
        
        // Verificar configuración
        $c4cEnabled = env('C4C_WEBSERVICE_ENABLED', false);
        if (!$c4cEnabled) {
            $this->addIssue('CONFIG', "C4C_WEBSERVICE_ENABLED deshabilitado - no puede crear ofertas");
        }
    }

    protected function validateProducts(Appointment $appointment): void
    {
        $this->line("📦 Validando productos...");
        
        $productsCount = $appointment->products()->count();
        
        if ($appointment->package_id && $productsCount === 0) {
            $this->addIssue('PRODUCTS', "Appointment con package_id pero sin productos - REQUIERE CORRECCIÓN");
            
            // ANÁLISIS ESPECÍFICO del problema
            $this->diagnoseProductsIssue($appointment);
            
            // OFRECER SOLUCIÓN AUTOMÁTICA si --fix está activado
            if ($this->option('fix')) {
                $this->attemptProductsFix($appointment);
            } else {
                $this->comment("  💡 SOLUCIÓN: Ejecuta con --fix para re-disparar DownloadProductsJob automáticamente");
                $this->line("     Comando: php artisan appointment:validate --id={$appointment->id} --fix");
            }
            
        } elseif ($productsCount > 0) {
            $this->addSuccess('PRODUCTS', "Productos vinculados: {$productsCount}");
            
            if ($this->option('detailed')) {
                $products = $appointment->products()->take(3)->get();
                foreach ($products as $product) {
                    $this->line("  - {$product->material_description} (Qty: {$product->quantity})");
                }
                
                // VERIFICAR CALIDAD de los productos
                $this->validateProductsQuality($appointment, $productsCount);
            }
        }
    }
    
    protected function diagnoseProductsIssue(Appointment $appointment): void
    {
        $this->error("  🔍 DIAGNÓSTICO ESPECÍFICO:");
        
        // 1. Verificar si existen productos maestros para este package_id
        $masterProducts = DB::table('products')
            ->where('package_id', $appointment->package_id)
            ->whereNull('appointment_id')
            ->count();
            
        if ($masterProducts > 0) {
            $this->line("  ✅ Productos maestros existen: {$masterProducts}");
            $this->line("  💡 PROBLEMA: Los productos no se vincularon al appointment");
            $this->line("  🔧 SOLUCIÓN: Re-ejecutar vinculación de productos");
        } else {
            $this->error("  ❌ NO existen productos maestros para package_id: {$appointment->package_id}");
            $this->line("  💡 PROBLEMA: DownloadProductsJob nunca descargó productos de C4C");
            $this->line("  🔧 SOLUCIÓN: Re-ejecutar DownloadProductsJob");
        }
        
        // 2. Verificar hace cuánto se creó la cita
        $timeElapsed = now()->diffInMinutes($appointment->created_at);
        if ($timeElapsed < 10) {
            $this->line("  ⏰ Cita reciente ({$timeElapsed}min) - Es normal que aún no tenga productos");
        } else {
            $this->error("  ⏰ Cita antigua ({$timeElapsed}min) - Debería tener productos ya");
        }
        
        // 3. Verificar configuración de productos
        $productsUrl = env('C4C_PRODUCTS_URL');
        if (empty($productsUrl)) {
            $this->error("  ❌ C4C_PRODUCTS_URL no configurado");
        }
    }
    
    protected function validateProductsQuality(Appointment $appointment, int $productsCount): void
    {
        // Verificar tipos de productos
        $productTypes = $appointment->products()
            ->selectRaw('position_type, COUNT(*) as count')
            ->groupBy('position_type')
            ->get();
            
        $this->line("  📊 Tipos de productos:");
        foreach ($productTypes as $type) {
            $typeName = match($type->position_type) {
                'P001' => 'Servicios',
                'P002' => 'Materiales',
                'P009' => 'Gastos',
                'P010' => 'Textos',
                default => $type->position_type
            };
            $this->line("    - {$typeName}: {$type->count}");
        }
        
        // Verificar productos sin descripción o con datos incompletos
        $incompleteProducts = $appointment->products()
            ->where(function($query) {
                $query->whereNull('material_description')
                      ->orWhere('material_description', '')
                      ->orWhereNull('quantity')
                      ->orWhere('quantity', 0);
            })
            ->count();
            
        if ($incompleteProducts > 0) {
            $this->addWarning('PRODUCTS', "Productos con datos incompletos: {$incompleteProducts}");
        }
    }
    
    protected function attemptProductsFix(Appointment $appointment): void
    {
        $this->comment("  🔧 APLICANDO CORRECCIÓN AUTOMÁTICA:");
        
        try {
            // Verificar si hay productos maestros
            $masterProducts = DB::table('products')
                ->where('package_id', $appointment->package_id)
                ->whereNull('appointment_id')
                ->count();
                
            if ($masterProducts > 0) {
                // CASO 1: Productos existen, solo falta vincular
                $this->line("    🔗 Vinculando productos existentes...");
                
                $updated = DB::table('products')
                    ->where('package_id', $appointment->package_id)
                    ->whereNull('appointment_id')
                    ->update([
                        'appointment_id' => $appointment->id,
                        'updated_at' => now()
                    ]);
                    
                if ($updated > 0) {
                    $this->addSuccess('FIXES', "Productos vinculados automáticamente: {$updated}");
                    
                    // Disparar CreateOfferJob si no tiene oferta
                    if (!$appointment->c4c_offer_id) {
                        \App\Jobs\CreateOfferJob::dispatch($appointment)->onQueue('offers');
                        $this->line("    🎯 CreateOfferJob disparado automáticamente");
                    }
                } else {
                    $this->line("    ❌ No se pudieron vincular productos");
                }
                
            } else {
                // CASO 2: No hay productos, re-disparar DownloadProductsJob
                $this->line("    📥 Re-disparando DownloadProductsJob...");
                
                \App\Jobs\DownloadProductsJob::dispatch($appointment->package_id, $appointment->id)
                    ->onQueue('products');
                    
                $this->addSuccess('FIXES', "DownloadProductsJob re-disparado para package_id: {$appointment->package_id}");
                $this->line("    ⏰ Espera 1-2 minutos y vuelve a validar");
            }
            
        } catch (\Exception $e) {
            $this->addIssue('FIXES', "Error aplicando corrección: {$e->getMessage()}");
        }
    }

    protected function validateOfferCreation(Appointment $appointment): void
    {
        $this->line("💰 Validando creación de oferta...");

        if ($appointment->c4c_offer_id) {
            $this->addSuccess('OFFER', "Oferta creada: {$appointment->c4c_offer_id}");
            
            if ($appointment->offer_created_at) {
                $creationTime = Carbon::parse($appointment->offer_created_at);
                $timeDiff = $appointment->created_at->diffInMinutes($creationTime);
                $this->line("  ⏱️ Tiempo de creación: {$timeDiff} minutos");
            }
        } else {
            // Verificar si debe tener oferta
            $timeSinceCreation = now()->diffInHours($appointment->created_at);
            
            if ($appointment->c4c_uuid && $timeSinceCreation > 1) {
                $this->addIssue('OFFER', "Cita enviada a C4C pero sin oferta después de {$timeSinceCreation}h");
                
                // Verificar errores de creación de oferta
                if ($appointment->offer_creation_failed) {
                    $this->addIssue('OFFER', "Error en creación: {$appointment->offer_creation_error}");
                }
            } elseif ($timeSinceCreation < 2) {
                $this->addWarning('OFFER', "Oferta puede estar procesándose (creado hace {$timeSinceCreation}h)");
            }
        }
    }

    protected function analyzeLogs(Appointment $appointment): void
    {
        $this->line("📋 Analizando logs profundamente...");
        
        $logFile = storage_path('logs/laravel.log');
        if (!File::exists($logFile)) {
            $this->addWarning('LOGS', 'Archivo de logs no encontrado');
            return;
        }

        // Buscar logs relacionados con este appointment
        $appointmentNumber = $appointment->appointment_number;
        $appointmentId = $appointment->id;
        
        $logContent = File::get($logFile);
        $lines = explode("\n", $logContent);
        
        $relevantLogs = [];
        $jobLogs = [];
        $errorLogs = [];
        
        foreach ($lines as $line) {
            // Buscar por appointment number, ID, y patrones relacionados
            if (str_contains($line, $appointmentNumber) || 
                str_contains($line, "appointment.*{$appointmentId}") ||
                str_contains($line, "appointment_id.*{$appointmentId}") ||
                str_contains($line, "appointmentId.*{$appointmentId}")) {
                
                $relevantLogs[] = $line;
                
                // Categorizar logs
                if (str_contains($line, 'Job') || str_contains($line, 'Queue')) {
                    $jobLogs[] = $line;
                }
                if (str_contains($line, 'ERROR') || str_contains($line, 'Exception') || str_contains($line, 'Failed')) {
                    $errorLogs[] = $line;
                }
            }
        }

        $this->line("  📄 Total logs encontrados: " . count($relevantLogs));
        
        if (!empty($errorLogs)) {
            $this->error("  🚨 ERRORES ENCONTRADOS: " . count($errorLogs) . " líneas de error");
            // Solo mostrar el primer y último error para contexto
            if (count($errorLogs) > 0) {
                $firstError = reset($errorLogs);
                $lastError = end($errorLogs);
                $this->line("    ❌ Primer error: " . \Illuminate\Support\Str::limit($firstError, 100));
                if ($firstError !== $lastError) {
                    $this->line("    ❌ Último error: " . \Illuminate\Support\Str::limit($lastError, 100));
                }
            }
        }
        
        if (!empty($jobLogs)) {
            $this->comment("  ⚙️ LOGS DE JOBS: " . count($jobLogs) . " eventos de jobs detectados");
        }
        
        // Solo mostrar conteo de logs relevantes
        if (!empty($relevantLogs)) {
            $this->comment("  📝 LOGS RELEVANTES TOTALES: " . count($relevantLogs) . " entradas encontradas");
        }
        
        // Analizar patrones específicos
        $this->analyzeLogPatterns($relevantLogs, $appointment);
    }
    
    protected function analyzeLogPatterns(array $logs, Appointment $appointment): void
    {
        $this->line("  🔍 ANÁLISIS DE PATRONES:");
        
        $patterns = [
            'DownloadProducts' => ['DownloadProductsJob', 'productos.*descargados', 'products.*downloaded'],
            'CreateOffer' => ['CreateOfferJob', 'oferta.*creada', 'offer.*created'],
            'EnviarCita' => ['EnviarCitaC4CJob', 'cita.*enviada', 'appointment.*sent'],
            'C4C_Response' => ['C4C.*response', 'respuesta.*C4C', 'c4c_uuid'],
            'Package_Issues' => ['package.*not.*found', 'package_id.*null', 'paquete.*error'],
            'Job_Failures' => ['job.*failed', 'failed.*job', 'Exception.*Job']
        ];
        
        foreach ($patterns as $category => $patternList) {
            $matches = 0;
            $lastMatch = '';
            
            foreach ($logs as $log) {
                foreach ($patternList as $pattern) {
                    if (preg_match("/{$pattern}/i", $log)) {
                        $matches++;
                        $lastMatch = $log;
                        break;
                    }
                }
            }
            
            if ($matches > 0) {
                $this->line("    📊 {$category}: {$matches} ocurrencias detectadas");
            }
        }
        
        // Verificar jobs en failed_jobs table
        $this->checkFailedJobsDetailed($appointment->id);
        
        // Verificar jobs pendientes
        $this->checkPendingJobs($appointment->id);
    }
    
    protected function checkFailedJobsDetailed(int $appointmentId): void
    {
        $this->line("  🔍 VERIFICANDO JOBS FALLIDOS EN BD:");
        
        $failedJobs = DB::table('failed_jobs')
            ->where(function($query) use ($appointmentId) {
                $query->where('payload', 'like', "%appointment.*{$appointmentId}%")
                      ->orWhere('payload', 'like', "%appointmentId.*{$appointmentId}%")
                      ->orWhere('payload', 'like', "%appointment_id.*{$appointmentId}%");
            })
            ->orderBy('failed_at', 'desc')
            ->get();
            
        if ($failedJobs->isNotEmpty()) {
            $this->error("    🚨 JOBS FALLIDOS ENCONTRADOS: " . $failedJobs->count());
            
            foreach ($failedJobs as $job) {
                $jobClass = $this->extractJobClass($job->payload);
                $failedAt = Carbon::parse($job->failed_at);
                
                $this->line("      ❌ {$jobClass} - Falló: {$failedAt->diffForHumans()}");
                
                // Extraer error específico
                $exception = $job->exception ?? '';
                if (strlen($exception) > 0) {
                    $firstLine = strtok($exception, "\n");
                    $this->line("         💥 Error: " . \Illuminate\Support\Str::limit($firstLine, 100));
                }
            }
        } else {
            $this->line("    ✅ No hay jobs fallidos para este appointment");
        }
    }
    
    protected function checkPendingJobs(int $appointmentId): void
    {
        $this->line("  🔍 VERIFICANDO JOBS PENDIENTES EN COLA:");
        
        $pendingJobs = DB::table('jobs')
            ->where(function($query) use ($appointmentId) {
                $query->where('payload', 'like', "%appointment.*{$appointmentId}%")
                      ->orWhere('payload', 'like', "%appointmentId.*{$appointmentId}%")
                      ->orWhere('payload', 'like', "%appointment_id.*{$appointmentId}%");
            })
            ->orderBy('created_at', 'desc')
            ->get();
            
        if ($pendingJobs->isNotEmpty()) {
            $this->comment("    ⏳ JOBS PENDIENTES: " . $pendingJobs->count());
            
            foreach ($pendingJobs as $job) {
                $jobClass = $this->extractJobClass($job->payload);
                $createdAt = Carbon::createFromTimestamp($job->created_at);
                
                $this->line("      ⏱️ {$jobClass} - Creado: {$createdAt->diffForHumans()} - Intentos: {$job->attempts}");
            }
        } else {
            $this->line("    ℹ️ No hay jobs pendientes para este appointment");
        }
    }

    protected function attemptFixes(Appointment $appointment): void
    {
        $this->comment("🔧 Intentando correcciones automáticas...");

        $fixes = 0;

        // Fix 1: Recalcular package_id si falta
        if (!$appointment->package_id && $appointment->maintenance_type && $appointment->vehicle) {
            try {
                $calculator = new PackageIdCalculator();
                $packageId = $calculator->calculate($appointment->vehicle, $appointment->maintenance_type);
                
                if ($packageId) {
                    $appointment->update(['package_id' => $packageId]);
                    $this->line("  ✅ Package ID recalculado: {$packageId}");
                    $fixes++;
                }
            } catch (\Exception $e) {
                $this->line("  ❌ Error recalculando package_id: {$e->getMessage()}");
            }
        }

        // Fix 2: Reenviar jobs fallidos recientes
        $recentFailedJobs = $this->getFailedJobsForAppointment($appointment->id)
            ->where('failed_at', '>', now()->subHours(1));

        if ($recentFailedJobs->isNotEmpty()) {
            foreach ($recentFailedJobs as $failedJob) {
                try {
                    // Reintentar el job
                    \Artisan::call('queue:retry', ['id' => $failedJob->id]);
                    $this->line("  ✅ Job reintentado: " . $this->extractJobClass($failedJob->payload));
                    $fixes++;
                } catch (\Exception $e) {
                    $this->line("  ❌ Error reintentando job: {$e->getMessage()}");
                }
            }
        }

        if ($fixes > 0) {
            $this->addSuccess('FIXES', "Aplicadas {$fixes} correcciones automáticas");
        } else {
            $this->line("  ℹ️ No se encontraron correcciones automáticas aplicables");
        }
    }

    protected function getFailedJobsForAppointment(int $appointmentId)
    {
        return DB::table('failed_jobs')
            ->where('payload', 'like', "%appointment.*{$appointmentId}%")
            ->orWhere('payload', 'like', "%appointmentId.*{$appointmentId}%")
            ->orderBy('failed_at', 'desc')
            ->get();
    }

    protected function extractJobClass(string $payload): string
    {
        $decoded = json_decode($payload, true);
        $command = $decoded['data']['command'] ?? '';
        
        // Extraer clase del job
        if (preg_match('/O:\d+:"([^"]+)"/', $command, $matches)) {
            $fullClass = $matches[1];
            return class_basename($fullClass);
        }
        
        return 'Unknown Job';
    }

    protected function showFinalSummary(): void
    {
        $this->info('📊 RESUMEN FINAL DE VALIDACIÓN');
        $this->info(str_repeat('=', 70));

        // Éxitos
        if (!empty($this->success)) {
            $this->info('✅ PROCESOS EXITOSOS:');
            foreach ($this->success as $category => $messages) {
                $this->line("  🟢 {$category}:");
                foreach ($messages as $message) {
                    $this->line("    • {$message}");
                }
            }
            $this->newLine();
        }

        // Advertencias
        if (!empty($this->warnings)) {
            $this->warn('⚠️ ADVERTENCIAS:');
            foreach ($this->warnings as $category => $messages) {
                $this->line("  🟡 {$category}:");
                foreach ($messages as $message) {
                    $this->line("    • {$message}");
                }
            }
            $this->newLine();
        }

        // Problemas críticos
        if (!empty($this->issues)) {
            $this->error('❌ PROBLEMAS CRÍTICOS:');
            foreach ($this->issues as $category => $messages) {
                $this->line("  🔴 {$category}:");
                foreach ($messages as $message) {
                    $this->line("    • {$message}");
                }
            }
            $this->newLine();
        }

        // Estado general
        $totalIssues = array_sum(array_map('count', $this->issues));
        $totalWarnings = array_sum(array_map('count', $this->warnings));
        $totalSuccess = array_sum(array_map('count', $this->success));

        if ($totalIssues == 0 && $totalWarnings == 0) {
            $this->info('✅ TODOS LOS FLUJOS FUNCIONAN CORRECTAMENTE');
        } elseif ($totalIssues == 0) {
            $this->info('🟡 FLUJOS OPERATIVOS CON ADVERTENCIAS MENORES');
        } else {
            $this->error('🔴 SE ENCONTRARON PROBLEMAS CRÍTICOS QUE REQUIEREN ATENCIÓN');
        }

        $this->line("");
        $this->line("📈 Estadísticas:");
        $this->line("  • Procesos exitosos: {$totalSuccess}");
        $this->line("  • Advertencias: {$totalWarnings}");
        $this->line("  • Problemas críticos: {$totalIssues}");
        $this->newLine();

        $this->comment('💡 COMANDOS ÚTILES:');
        $this->line('  php artisan appointment:validate --id=123 --detailed');
        $this->line('  php artisan appointment:validate --hours=48 --fix');
        $this->line('  php artisan queue:work  # Procesar jobs pendientes');
        $this->line('  php artisan queue:failed  # Ver jobs fallidos');
        
        // COMANDOS ESPECÍFICOS para problemas detectados
        if (array_key_exists('PRODUCTS', $this->issues)) {
            $this->newLine();
            $this->comment('🔧 COMANDOS ESPECÍFICOS PARA PROBLEMAS DE PRODUCTOS:');
            $this->line('  php artisan appointment:validate --hours=24 --fix  # Corregir automáticamente');
            $this->line('  php artisan queue:work --queue=products  # Procesar cola de productos');
        }
        
        if (array_key_exists('JOBS', $this->issues)) {
            $this->newLine();
            $this->comment('⚙️ COMANDOS PARA PROBLEMAS DE JOBS:');
            $this->line('  php artisan queue:restart  # Reiniciar workers');
            $this->line('  php artisan queue:retry all  # Reintentar jobs fallidos');
        }
    }

    protected function addIssue(string $category, string $message): void
    {
        if (!isset($this->issues[$category])) {
            $this->issues[$category] = [];
        }
        $this->issues[$category][] = $message;
    }

    protected function addWarning(string $category, string $message): void
    {
        if (!isset($this->warnings[$category])) {
            $this->warnings[$category] = [];
        }
        $this->warnings[$category][] = $message;
    }

    protected function addSuccess(string $category, string $message): void
    {
        if (!isset($this->success[$category])) {
            $this->success[$category] = [];
        }
        $this->success[$category][] = $message;
    }
}