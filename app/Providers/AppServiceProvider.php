<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // El servicio VehiculoSoapService ya está registrado en VehiculosServiceProvider
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Suprimir warnings de deprecación para trim(null)
        error_reporting(E_ALL & ~E_DEPRECATED);
    }
}
