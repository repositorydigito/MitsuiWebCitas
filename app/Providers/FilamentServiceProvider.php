<?php

namespace App\Providers;

use App\Filament\Pages\Vehiculos;
use App\Filament\Pages\MiCuenta;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
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
                MiCuenta::class,
            ]);
        });

    }
}
