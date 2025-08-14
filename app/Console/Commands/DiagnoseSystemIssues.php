<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Exception;

class DiagnoseSystemIssues extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'system:diagnose 
                            {--performance : Analizar problemas de rendimiento y lentitud}
                            {--validation : Revisar problemas de validación y datos}
                            {--jobs : Analizar estado de jobs y colas}
                            {--logic : Detectar inconsistencias de lógica de negocio}
                            {--all : Ejecutar todos los diagnósticos}
                            {--fix : Intentar aplicar correcciones automáticas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnosticar problemas del sistema: validación, lentitud, jobs y lógica de negocio';

    protected array $issues = [];
    protected array $warnings = [];
    protected array $recommendations = [];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 DIAGNÓSTICO INTEGRAL DEL SISTEMA MITSUI');
        $this->info(str_repeat('=', 60));
        $this->newLine();

        $performance = $this->option('performance');
        $validation = $this->option('validation');
        $jobs = $this->option('jobs');
        $logic = $this->option('logic');
        $all = $this->option('all');
        $fix = $this->option('fix');

        if ($all || (!$performance && !$validation && !$jobs && !$logic)) {
            $performance = $validation = $jobs = $logic = true;
        }

        // 1. Diagnóstico de Performance
        if ($performance) {
            $this->info('🚀 DIAGNÓSTICO DE RENDIMIENTO Y LENTITUD');
            $this->line(str_repeat('-', 50));
            $this->diagnosisPerformance();
            $this->newLine();
        }

        // 2. Diagnóstico de Validación
        if ($validation) {
            $this->info('✅ DIAGNÓSTICO DE VALIDACIÓN Y DATOS');
            $this->line(str_repeat('-', 50));
            $this->diagnosisValidation();
            $this->newLine();
        }

        // 3. Diagnóstico de Jobs
        if ($jobs) {
            $this->info('⚙️  DIAGNÓSTICO DE JOBS Y COLAS');
            $this->line(str_repeat('-', 50));
            $this->diagnosisJobs();
            $this->newLine();
        }

        // 4. Diagnóstico de Lógica
        if ($logic) {
            $this->info('🧠 DIAGNÓSTICO DE LÓGICA DE NEGOCIO');
            $this->line(str_repeat('-', 50));
            $this->diagnosisLogic();
            $this->newLine();
        }

        // 5. Aplicar correcciones automáticas
        if ($fix) {
            $this->info('🔧 APLICANDO CORRECCIONES AUTOMÁTICAS');
            $this->line(str_repeat('-', 50));
            $this->applyAutomaticFixes();
            $this->newLine();
        }

        // 6. Resumen final
        $this->showFinalSummary();

        return 0;
    }

    protected function diagnosisPerformance(): void
    {
        $this->line('📊 Analizando tiempos de consulta y operaciones lentas...');

        // 1. Consultas SQL lentas
        $this->checkSlowQueries();

        // 2. Jobs que tardan mucho
        $this->checkSlowJobs();

        // 3. Memoria y cache
        $this->checkMemoryAndCache();

        // 4. Servicios externos
        $this->checkExternalServicesPerformance();

        // 5. Archivos y logs grandes
        $this->checkLargeFiles();
    }

    protected function checkSlowQueries(): void
    {
        $this->comment('  🔍 Verificando consultas SQL lentas...');

        try {
            // Verificar jobs que han fallado por timeout
            $timeoutJobs = DB::table('jobs')
                ->where('created_at', '>=', now()->subHours(24))
                ->where('attempts', '>', 3)
                ->count();

            if ($timeoutJobs > 10) {
                $this->addIssue('PERFORMANCE', "Detectados {$timeoutJobs} jobs con múltiples intentos en 24h - posibles timeouts");
            }

            // Verificar appointments sin C4C UUID después de mucho tiempo
            $appointmentsWithoutUuid = DB::table('appointments')
                ->whereNull('c4c_uuid')
                ->where('created_at', '<', now()->subHours(1))
                ->count();

            if ($appointmentsWithoutUuid > 0) {
                $this->addIssue('PERFORMANCE', "Encontradas {$appointmentsWithoutUuid} citas sin c4c_uuid después de 1+ horas");
            }

            $this->line('    ✅ Consultas SQL verificadas');

        } catch (Exception $e) {
            $this->addIssue('PERFORMANCE', "Error verificando consultas SQL: {$e->getMessage()}");
        }
    }

    protected function checkSlowJobs(): void
    {
        $this->comment('  ⏱️  Verificando jobs lentos...');

        try {
            // Verificar failed_jobs por patrones específicos
            $recentFailedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subHours(24))
                ->get();

            $timeoutPatterns = ['timeout', 'timed out', 'exceeded', 'connection timeout'];
            $timeoutCount = 0;

            foreach ($recentFailedJobs as $job) {
                $exception = strtolower($job->exception ?? '');
                foreach ($timeoutPatterns as $pattern) {
                    if (str_contains($exception, $pattern)) {
                        $timeoutCount++;
                        break;
                    }
                }
            }

            if ($timeoutCount > 0) {
                $this->addIssue('PERFORMANCE', "Detectados {$timeoutCount} jobs fallidos por timeout en 24h");
            }

            // Verificar jobs específicos problemáticos
            $problematicJobs = [
                'App\\Jobs\\DownloadProductsJob',
                'App\\Jobs\\CreateOfferJob',
                'App\\Jobs\\EnviarCitaC4CJob'
            ];

            foreach ($problematicJobs as $jobClass) {
                $failedCount = DB::table('failed_jobs')
                    ->where('payload', 'like', "%{$jobClass}%")
                    ->where('failed_at', '>=', now()->subHours(24))
                    ->count();

                if ($failedCount > 5) {
                    $this->addIssue('PERFORMANCE', "Job problemático: {$jobClass} falló {$failedCount} veces en 24h");
                }
            }

            $this->line('    ✅ Jobs verificados');

        } catch (Exception $e) {
            $this->addIssue('PERFORMANCE', "Error verificando jobs: {$e->getMessage()}");
        }
    }

    protected function checkMemoryAndCache(): void
    {
        $this->comment('  💾 Verificando memoria y cache...');

        try {
            // Verificar uso de memoria
            $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
            if ($memoryUsage > 128) {
                $this->addWarning('PERFORMANCE', "Uso de memoria alto: {$memoryUsage}MB");
            }

            // Verificar cache de Laravel
            try {
                Cache::put('diagnostic_test', 'test_value', 60);
                $cacheValue = Cache::get('diagnostic_test');
                
                if ($cacheValue !== 'test_value') {
                    $this->addIssue('PERFORMANCE', 'Cache de Laravel no está funcionando correctamente');
                } else {
                    Cache::forget('diagnostic_test');
                }
            } catch (Exception $e) {
                $this->addIssue('PERFORMANCE', "Error en cache: {$e->getMessage()}");
            }

            $this->line('    ✅ Memoria y cache verificados');

        } catch (Exception $e) {
            $this->addIssue('PERFORMANCE', "Error verificando memoria: {$e->getMessage()}");
        }
    }

    protected function checkExternalServicesPerformance(): void
    {
        $this->comment('  🌐 Verificando servicios externos...');

        // Verificar configuración de timeouts
        $timeouts = [
            'C4C_TIMEOUT' => env('C4C_TIMEOUT', 120),
            'vehiculos_webservice.timeout' => config('vehiculos_webservice.timeout', 30),
            'SAP timeout' => 8 // Hardcodeado en VehiculoSoapService
        ];

        foreach ($timeouts as $service => $timeout) {
            if ($timeout > 60) {
                $this->addWarning('PERFORMANCE', "Timeout alto en {$service}: {$timeout}s");
            }
        }

        // Verificar habilitación de servicios
        $sapEnabled = env('SAP_ENABLED', false);
        $sapWebserviceEnabled = env('SAP_WEBSERVICE_ENABLED', false);
        $useMockServices = env('USE_MOCK_SERVICES', false);

        if (!$sapEnabled && !$useMockServices) {
            $this->addWarning('PERFORMANCE', 'SAP deshabilitado pero USE_MOCK_SERVICES también está en false');
        }

        $this->line('    ✅ Servicios externos verificados');
    }

    protected function checkLargeFiles(): void
    {
        $this->comment('  📁 Verificando archivos grandes...');

        try {
            $logFile = storage_path('logs/laravel.log');
            if (File::exists($logFile)) {
                $logSize = File::size($logFile) / 1024 / 1024; // MB
                if ($logSize > 100) {
                    $this->addIssue('PERFORMANCE', "Archivo de log muy grande: {$logSize}MB");
                    $this->addRecommendation('Ejecutar: php artisan log:clear o rotar logs');
                }
            }

            // Verificar cache de vistas compiladas
            $cacheDir = storage_path('framework/cache');
            if (File::exists($cacheDir)) {
                $cacheSize = $this->getDirSize($cacheDir) / 1024 / 1024; // MB
                if ($cacheSize > 50) {
                    $this->addWarning('PERFORMANCE', "Cache compilado grande: {$cacheSize}MB");
                }
            }

            $this->line('    ✅ Archivos verificados');

        } catch (Exception $e) {
            $this->addIssue('PERFORMANCE', "Error verificando archivos: {$e->getMessage()}");
        }
    }

    protected function diagnosisValidation(): void
    {
        $this->line('🔍 Analizando problemas de validación y consistencia de datos...');

        // 1. Appointments sin datos requeridos
        $this->checkAppointmentValidation();

        // 2. Vehículos con datos incompletos
        $this->checkVehicleValidation();

        // 3. Users con problemas de validación
        $this->checkUserValidation();

        // 4. Productos sin vincular
        $this->checkProductValidation();

        // 5. Jobs con parámetros inválidos
        $this->checkJobValidation();
    }

    protected function checkAppointmentValidation(): void
    {
        $this->comment('  📅 Verificando citas...');

        try {
            // Citas sin vehículo asociado
            $appointmentsWithoutVehicle = DB::table('appointments')
                ->whereNull('vehicle_id')
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            if ($appointmentsWithoutVehicle > 0) {
                $this->addIssue('VALIDATION', "Encontradas {$appointmentsWithoutVehicle} citas sin vehículo en últimos 7 días");
            }

            // Citas con fechas inválidas
            $appointmentsWithInvalidDates = DB::table('appointments')
                ->where('scheduled_date', '<', now()->subYears(1))
                ->orWhere('scheduled_date', '>', now()->addYears(1))
                ->count();

            if ($appointmentsWithInvalidDates > 0) {
                $this->addIssue('VALIDATION', "Encontradas {$appointmentsWithInvalidDates} citas con fechas inválidas");
            }

            // Citas sin package_id cuando debería tenerlo
            $appointmentsWithoutPackageId = DB::table('appointments')
                ->whereNull('package_id')
                ->whereNotNull('maintenance_type')
                ->where('created_at', '>=', now()->subDays(3))
                ->count();

            if ($appointmentsWithoutPackageId > 0) {
                $this->addIssue('VALIDATION', "Encontradas {$appointmentsWithoutPackageId} citas sin package_id pero con maintenance_type");
            }

            $this->line('    ✅ Citas verificadas');

        } catch (Exception $e) {
            $this->addIssue('VALIDATION', "Error verificando citas: {$e->getMessage()}");
        }
    }

    protected function checkVehicleValidation(): void
    {
        $this->comment('  🚗 Verificando vehículos...');

        try {
            // Vehículos sin datos básicos
            $vehiclesWithoutBasicData = DB::table('vehicles')
                ->where(function($query) {
                    $query->whereNull('license_plate')
                          ->orWhereNull('brand_code')
                          ->orWhereNull('model')
                          ->orWhereNull('year');
                })
                ->count();

            if ($vehiclesWithoutBasicData > 0) {
                $this->addIssue('VALIDATION', "Encontrados {$vehiclesWithoutBasicData} vehículos con datos básicos incompletos");
            }

            // Vehículos duplicados por placa
            $duplicatePlates = DB::table('vehicles')
                ->select('license_plate')
                ->whereNotNull('license_plate')
                ->groupBy('license_plate')
                ->havingRaw('COUNT(*) > 1')
                ->count();

            if ($duplicatePlates > 0) {
                $this->addIssue('VALIDATION', "Encontradas {$duplicatePlates} placas duplicadas en vehículos");
            }

            // Vehículos sin tipo_valor_trabajo (necesario para ofertas)
            $vehiclesWithoutTipoValor = DB::table('vehicles')
                ->whereNull('tipo_valor_trabajo')
                ->whereIn('brand_code', ['Z01', 'Z02', 'Z03'])
                ->count();

            if ($vehiclesWithoutTipoValor > 0) {
                $this->addWarning('VALIDATION', "Encontrados {$vehiclesWithoutTipoValor} vehículos sin tipo_valor_trabajo");
            }

            $this->line('    ✅ Vehículos verificados');

        } catch (Exception $e) {
            $this->addIssue('VALIDATION', "Error verificando vehículos: {$e->getMessage()}");
        }
    }

    protected function checkUserValidation(): void
    {
        $this->comment('  👤 Verificando usuarios...');

        try {
            // Usuarios sin c4c_internal_id
            $usersWithoutC4CId = DB::table('users')
                ->whereNull('c4c_internal_id')
                ->where('is_comodin', false)
                ->where('created_at', '>=', now()->subDays(7))
                ->count();

            if ($usersWithoutC4CId > 0) {
                $this->addWarning('VALIDATION', "Encontrados {$usersWithoutC4CId} usuarios sin c4c_internal_id en últimos 7 días");
            }

            // Usuarios comodín múltiples
            $comodinUsers = DB::table('users')
                ->where('is_comodin', true)
                ->count();

            if ($comodinUsers > 1) {
                $this->addIssue('VALIDATION', "Encontrados {$comodinUsers} usuarios comodín (debería ser solo 1)");
            } elseif ($comodinUsers == 0) {
                $this->addIssue('VALIDATION', 'No se encontró usuario comodín configurado');
            }

            $this->line('    ✅ Usuarios verificados');

        } catch (Exception $e) {
            $this->addIssue('VALIDATION', "Error verificando usuarios: {$e->getMessage()}");
        }
    }

    protected function checkProductValidation(): void
    {
        $this->comment('  📦 Verificando productos...');

        try {
            // Productos sin unit_code
            $productsWithoutUnitCode = DB::table('products')
                ->whereNull('unit_code')
                ->count();

            if ($productsWithoutUnitCode > 0) {
                $this->addWarning('VALIDATION', "Encontrados {$productsWithoutUnitCode} productos sin unit_code");
            }

            // Appointments con productos pero sin ofertas
            $appointmentsWithProductsNoOffers = DB::table('appointments')
                ->join('appointment_products', 'appointments.id', '=', 'appointment_products.appointment_id')
                ->whereNull('appointments.c4c_offer_id')
                ->where('appointments.created_at', '>=', now()->subDays(3))
                ->distinct()
                ->count('appointments.id');

            if ($appointmentsWithProductsNoOffers > 0) {
                $this->addIssue('VALIDATION', "Encontradas {$appointmentsWithProductsNoOffers} citas con productos pero sin ofertas C4C");
            }

            $this->line('    ✅ Productos verificados');

        } catch (Exception $e) {
            $this->addIssue('VALIDATION', "Error verificando productos: {$e->getMessage()}");
        }
    }

    protected function checkJobValidation(): void
    {
        $this->comment('  ⚙️  Verificando validación en jobs...');

        try {
            // Jobs fallidos por validation errors
            $validationFailedJobs = DB::table('failed_jobs')
                ->where('exception', 'like', '%validation%')
                ->orWhere('exception', 'like', '%ValidationException%')
                ->where('failed_at', '>=', now()->subDays(7))
                ->count();

            if ($validationFailedJobs > 0) {
                $this->addIssue('VALIDATION', "Encontrados {$validationFailedJobs} jobs fallidos por errores de validación");
            }

            $this->line('    ✅ Validación de jobs verificada');

        } catch (Exception $e) {
            $this->addIssue('VALIDATION', "Error verificando validación de jobs: {$e->getMessage()}");
        }
    }

    protected function diagnosisJobs(): void
    {
        $this->line('⚙️ Analizando estado y problemas de jobs...');

        // 1. Estado de las colas
        $this->checkQueueStatus();

        // 2. Jobs fallidos recurrentes
        $this->checkFailedJobs();

        // 3. Jobs atascados
        $this->checkStuckJobs();

        // 4. Flujo de jobs específicos
        $this->checkJobFlow();
    }

    protected function checkQueueStatus(): void
    {
        $this->comment('  📊 Verificando estado de colas...');

        try {
            // Jobs pendientes
            $pendingJobs = DB::table('jobs')->count();
            if ($pendingJobs > 100) {
                $this->addIssue('JOBS', "Cola con muchos jobs pendientes: {$pendingJobs}");
            }

            // Jobs fallidos
            $failedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDays(1))
                ->count();

            if ($failedJobs > 10) {
                $this->addIssue('JOBS', "Muchos jobs fallidos en 24h: {$failedJobs}");
            }

            // Verificar workers activos (simulado)
            $queueConnection = config('queue.default');
            $this->line("    🔧 Conexión de cola: {$queueConnection}");

            $this->line('    ✅ Estado de colas verificado');

        } catch (Exception $e) {
            $this->addIssue('JOBS', "Error verificando colas: {$e->getMessage()}");
        }
    }

    protected function checkFailedJobs(): void
    {
        $this->comment('  ❌ Analizando jobs fallidos...');

        try {
            // Patrones de error más comunes
            $errorPatterns = [
                'timeout' => 'Problemas de timeout',
                'connection' => 'Problemas de conexión',
                'soap' => 'Errores SOAP',
                'c4c' => 'Errores C4C',
                'validation' => 'Errores de validación'
            ];

            $recentFailedJobs = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subDays(7))
                ->get();

            $errorCounts = [];
            foreach ($errorPatterns as $pattern => $description) {
                $count = $recentFailedJobs->filter(function ($job) use ($pattern) {
                    return str_contains(strtolower($job->exception ?? ''), $pattern);
                })->count();

                if ($count > 0) {
                    $errorCounts[$pattern] = $count;
                }
            }

            foreach ($errorCounts as $pattern => $count) {
                if ($count > 5) {
                    $this->addIssue('JOBS', "Patrón de error recurrente '{$pattern}': {$count} jobs fallidos");
                } else {
                    $this->addWarning('JOBS', "Patrón de error '{$pattern}': {$count} jobs fallidos");
                }
            }

            $this->line('    ✅ Jobs fallidos analizados');

        } catch (Exception $e) {
            $this->addIssue('JOBS', "Error analizando jobs fallidos: {$e->getMessage()}");
        }
    }

    protected function checkStuckJobs(): void
    {
        $this->comment('  🔄 Verificando jobs atascados...');

        try {
            // Jobs muy antiguos en cola
            $stuckJobs = DB::table('jobs')
                ->where('created_at', '<', now()->subHours(6))
                ->count();

            if ($stuckJobs > 0) {
                $this->addIssue('JOBS', "Encontrados {$stuckJobs} jobs atascados (>6 horas en cola)");
            }

            // Jobs con muchos intentos
            $highAttemptJobs = DB::table('jobs')
                ->where('attempts', '>', 3)
                ->count();

            if ($highAttemptJobs > 0) {
                $this->addWarning('JOBS', "Encontrados {$highAttemptJobs} jobs con múltiples intentos");
            }

            $this->line('    ✅ Jobs atascados verificados');

        } catch (Exception $e) {
            $this->addIssue('JOBS', "Error verificando jobs atascados: {$e->getMessage()}");
        }
    }

    protected function checkJobFlow(): void
    {
        $this->comment('  🔄 Verificando flujo de jobs específicos...');

        try {
            // Verificar flujo de citas → ofertas
            $citasSinOfertas = DB::table('appointments')
                ->whereNotNull('c4c_uuid')
                ->whereNull('c4c_offer_id')
                ->where('created_at', '>=', now()->subHours(2))
                ->where('created_at', '<=', now()->subHour())
                ->count();

            if ($citasSinOfertas > 0) {
                $this->addIssue('JOBS', "Detectadas {$citasSinOfertas} citas enviadas a C4C pero sin ofertas después de 1+ horas");
            }

            // Verificar appointments con package_id pero sin productos
            $citasConPackageSinProductos = DB::table('appointments')
                ->whereNotNull('package_id')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('appointment_products')
                          ->whereColumn('appointment_products.appointment_id', 'appointments.id');
                })
                ->where('created_at', '>=', now()->subDays(1))
                ->count();

            if ($citasConPackageSinProductos > 0) {
                $this->addIssue('JOBS', "Detectadas {$citasConPackageSinProductos} citas con package_id pero sin productos descargados");
            }

            $this->line('    ✅ Flujo de jobs verificado');

        } catch (Exception $e) {
            $this->addIssue('JOBS', "Error verificando flujo de jobs: {$e->getMessage()}");
        }
    }

    protected function diagnosisLogic(): void
    {
        $this->line('🧠 Analizando inconsistencias de lógica de negocio...');

        // 1. Validar lógica de clientes wildcard
        $this->checkWildcardLogic();

        // 2. Validar lógica de package_id
        $this->checkPackageIdLogic();

        // 3. Validar flujo de prioridades
        $this->checkPriorityLogic();

        // 4. Validar configuración de servicios
        $this->checkServiceLogic();
    }

    protected function checkWildcardLogic(): void
    {
        $this->comment('  🎭 Verificando lógica de clientes wildcard...');

        try {
            // Usuario wildcard debe existir y tener c4c_internal_id específico
            $wildcardUser = DB::table('users')
                ->where('c4c_internal_id', '1200166011')
                ->where('is_comodin', true)
                ->first();

            if (!$wildcardUser) {
                $this->addIssue('LOGIC', 'Usuario wildcard con c4c_internal_id "1200166011" no encontrado');
            }

            // Verificar offers wildcard vs normales
            $wildcardOffers = DB::table('appointments')
                ->join('users', 'appointments.user_id', '=', 'users.id')
                ->where('users.c4c_internal_id', '1200166011')
                ->whereNotNull('appointments.c4c_offer_id')
                ->count();

            $normalOffers = DB::table('appointments')
                ->join('users', 'appointments.user_id', '=', 'users.id')
                ->where('users.c4c_internal_id', '!=', '1200166011')
                ->whereNotNull('appointments.c4c_offer_id')
                ->count();

            $this->line("    📊 Ofertas wildcard: {$wildcardOffers}, Ofertas normales: {$normalOffers}");

            $this->line('    ✅ Lógica wildcard verificada');

        } catch (Exception $e) {
            $this->addIssue('LOGIC', "Error verificando lógica wildcard: {$e->getMessage()}");
        }
    }

    protected function checkPackageIdLogic(): void
    {
        $this->comment('  📦 Verificando lógica de package_id...');

        try {
            // Appointments con maintenance_type pero sin package_id
            $inconsistentPackageIds = DB::table('appointments')
                ->whereNotNull('maintenance_type')
                ->whereNull('package_id')
                ->join('vehicles', 'appointments.vehicle_id', '=', 'vehicles.id')
                ->whereNotNull('vehicles.tipo_valor_trabajo')
                ->whereIn('vehicles.brand_code', ['Z01', 'Z02', 'Z03'])
                ->where('appointments.created_at', '>=', now()->subDays(3))
                ->count();

            if ($inconsistentPackageIds > 0) {
                $this->addIssue('LOGIC', "Detectadas {$inconsistentPackageIds} citas que deberían tener package_id pero no lo tienen");
            }

            // Package_ids con formato incorrecto
            $invalidPackageIds = DB::table('appointments')
                ->whereNotNull('package_id')
                ->where('package_id', 'not regexp', '^M[0-9]+-[0-9A-Z]+$')
                ->count();

            if ($invalidPackageIds > 0) {
                $this->addWarning('LOGIC', "Detectados {$invalidPackageIds} package_ids con formato incorrecto");
            }

            $this->line('    ✅ Lógica de package_id verificada');

        } catch (Exception $e) {
            $this->addIssue('LOGIC', "Error verificando lógica package_id: {$e->getMessage()}");
        }
    }

    protected function checkPriorityLogic(): void
    {
        $this->comment('  🔄 Verificando lógica de prioridades...');

        try {
            // Verificar que las prioridades se respeten en appointments recientes
            $appointmentsConMantenimiento = DB::table('appointments')
                ->whereNotNull('maintenance_type')
                ->whereJsonContains('servicios_adicionales', [])
                ->where('created_at', '>=', now()->subDays(1))
                ->count();

            $appointmentsSoloServicios = DB::table('appointments')
                ->whereNull('maintenance_type')
                ->where('servicios_adicionales', '!=', '[]')
                ->whereNotNull('servicios_adicionales')
                ->where('created_at', '>=', now()->subDays(1))
                ->count();

            if ($appointmentsConMantenimiento > 0 || $appointmentsSoloServicios > 0) {
                $this->line("    📊 Citas con mantenimiento: {$appointmentsConMantenimiento}");
                $this->line("    📊 Citas solo servicios: {$appointmentsSoloServicios}");
            }

            $this->line('    ✅ Lógica de prioridades verificada');

        } catch (Exception $e) {
            $this->addIssue('LOGIC', "Error verificando prioridades: {$e->getMessage()}");
        }
    }

    protected function checkServiceLogic(): void
    {
        $this->comment('  🔧 Verificando configuración de servicios...');

        // Verificar coherencia en configuración
        $sapEnabled = env('SAP_ENABLED', false);
        $sapWebserviceEnabled = env('SAP_WEBSERVICE_ENABLED', false);
        $c4cWebserviceEnabled = env('C4C_WEBSERVICE_ENABLED', false);
        $useMockServices = env('USE_MOCK_SERVICES', false);

        // Configuración contradictoria
        if ($sapEnabled && !$sapWebserviceEnabled) {
            $this->addWarning('LOGIC', 'SAP_ENABLED=true pero SAP_WEBSERVICE_ENABLED=false - configuración contradictoria');
        }

        if (!$sapEnabled && !$c4cWebserviceEnabled && !$useMockServices) {
            $this->addIssue('LOGIC', 'Todos los servicios están deshabilitados - sistema no funcional');
        }

        if ($useMockServices && ($sapEnabled || $c4cWebserviceEnabled)) {
            $this->addWarning('LOGIC', 'USE_MOCK_SERVICES=true con servicios reales habilitados - comportamiento impredecible');
        }

        $this->line('    ✅ Configuración de servicios verificada');
    }

    protected function applyAutomaticFixes(): void
    {
        $this->line('🔧 Aplicando correcciones automáticas...');

        $fixes = 0;

        // 1. Limpiar jobs fallidos antiguos
        $oldFailedJobs = DB::table('failed_jobs')
            ->where('failed_at', '<', now()->subDays(7))
            ->count();

        if ($oldFailedJobs > 0) {
            DB::table('failed_jobs')
                ->where('failed_at', '<', now()->subDays(7))
                ->delete();
            
            $this->line("  ✅ Eliminados {$oldFailedJobs} jobs fallidos antiguos");
            $fixes++;
        }

        // 2. Limpiar cache
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('view:clear');
            $this->line('  ✅ Cache limpiado');
            $fixes++;
        } catch (Exception $e) {
            $this->line("  ❌ Error limpiando cache: {$e->getMessage()}");
        }

        // 3. Optimizar autoloader
        try {
            \Artisan::call('optimize');
            $this->line('  ✅ Laravel optimizado');
            $fixes++;
        } catch (Exception $e) {
            $this->line("  ❌ Error optimizando: {$e->getMessage()}");
        }

        if ($fixes > 0) {
            $this->info("  📊 Total de correcciones aplicadas: {$fixes}");
        } else {
            $this->comment('  ℹ️  No se encontraron problemas que corregir automáticamente');
        }
    }

    protected function showFinalSummary(): void
    {
        $this->info('📊 RESUMEN FINAL DEL DIAGNÓSTICO');
        $this->info(str_repeat('=', 60));

        // Issues críticos
        if (!empty($this->issues)) {
            $this->error('❌ PROBLEMAS CRÍTICOS ENCONTRADOS:');
            foreach ($this->issues as $category => $issueList) {
                $this->line("  🔴 {$category}:");
                foreach ($issueList as $issue) {
                    $this->line("    • {$issue}");
                }
            }
            $this->newLine();
        }

        // Advertencias
        if (!empty($this->warnings)) {
            $this->warn('⚠️  ADVERTENCIAS:');
            foreach ($this->warnings as $category => $warningList) {
                $this->line("  🟡 {$category}:");
                foreach ($warningList as $warning) {
                    $this->line("    • {$warning}");
                }
            }
            $this->newLine();
        }

        // Recomendaciones
        if (!empty($this->recommendations)) {
            $this->comment('💡 RECOMENDACIONES:');
            foreach ($this->recommendations as $recommendation) {
                $this->line("  • {$recommendation}");
            }
            $this->newLine();
        }

        // Estado general
        $totalIssues = array_sum(array_map('count', $this->issues));
        $totalWarnings = array_sum(array_map('count', $this->warnings));

        if ($totalIssues == 0 && $totalWarnings == 0) {
            $this->info('✅ SISTEMA EN BUEN ESTADO - No se encontraron problemas críticos');
        } elseif ($totalIssues == 0) {
            $this->info('🟡 SISTEMA OPERATIVO - Solo advertencias menores encontradas');
        } else {
            $this->error('🔴 SISTEMA CON PROBLEMAS - Requiere atención inmediata');
        }

        $this->line("\n📈 Estadísticas:");
        $this->line("  • Problemas críticos: {$totalIssues}");
        $this->line("  • Advertencias: {$totalWarnings}");
        $this->line("  • Recomendaciones: " . count($this->recommendations));
    }

    protected function addIssue(string $category, string $issue): void
    {
        if (!isset($this->issues[$category])) {
            $this->issues[$category] = [];
        }
        $this->issues[$category][] = $issue;
    }

    protected function addWarning(string $category, string $warning): void
    {
        if (!isset($this->warnings[$category])) {
            $this->warnings[$category] = [];
        }
        $this->warnings[$category][] = $warning;
    }

    protected function addRecommendation(string $recommendation): void
    {
        $this->recommendations[] = $recommendation;
    }

    protected function getDirSize(string $directory): int
    {
        $size = 0;
        if (File::exists($directory)) {
            $files = File::allFiles($directory);
            foreach ($files as $file) {
                $size += $file->getSize();
            }
        }
        return $size;
    }
}