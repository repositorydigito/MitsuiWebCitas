<?php

namespace App\Providers;

use App\Models\Vehiculo;
use App\Services\VehiculoSoapService;
use App\Services\VehiculoWebServiceHealthCheck;
use App\Services\MockVehiculoService;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\ServiceProvider;

class VehiculosServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Registrar el servicio de verificación de salud del WebService
        $this->app->singleton(VehiculoWebServiceHealthCheck::class, function ($app) {
            return new VehiculoWebServiceHealthCheck(
                config('vehiculos_webservice.timeout', 5),
                config('vehiculos_webservice.retry_attempts', 2),
                config('vehiculos_webservice.health_check_interval', 300)
            );
        });
        
        // Registrar el servicio de datos mock
        $this->app->singleton(MockVehiculoService::class, function ($app) {
            return new MockVehiculoService();
        });
        
        // Registrar el servicio SOAP de vehículos
        $this->app->singleton(VehiculoSoapService::class, function ($app) {
            return new VehiculoSoapService(
                config('services.vehiculos.wsdl_url'),
                app(VehiculoWebServiceHealthCheck::class),
                app(MockVehiculoService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Simplificado: solo mantener los métodos esenciales para Filament
        Vehiculo::macro('getKeyName', function () {
            return 'numpla';
        });
        
        Vehiculo::macro('getRouteKeyName', function () {
            return 'numpla';
        });
    }
}
