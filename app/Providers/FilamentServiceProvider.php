<?php

namespace App\Providers;

use App\Filament\Pages\Vehiculos;
use Illuminate\Support\ServiceProvider;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->resolving('filament', function ($filament, $app) {
            // Registrar la página una vez que Filament se inicialice
            $filament->registerPages([
                Vehiculos::class,
            ]);
        });

    }
}
