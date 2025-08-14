<?php

namespace App\Providers;

use App\Services\C4C\AppointmentQueryService;
use App\Services\C4C\AppointmentService;
use App\Services\C4C\CustomerService;
use Illuminate\Support\ServiceProvider;

class C4CServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(CustomerService::class, function ($app) {
            return new CustomerService;
        });

        $this->app->singleton(AppointmentService::class, function ($app) {
            return new AppointmentService;
        });

        $this->app->singleton(AppointmentQueryService::class, function ($app) {
            return new AppointmentQueryService;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../../config/c4c.php' => config_path('c4c.php'),
        ], 'c4c-config');
    }
}
