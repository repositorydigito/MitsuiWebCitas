<?php                                                                                                                                                                                                   

namespace App\Providers\Filament;

use App\Filament\Pages\AdministrarLocales;
use App\Filament\Pages\AdministrarModelos;
use App\Filament\Pages\AgendarCita;
use App\Filament\Pages\AgregarVehiculo;
use App\Filament\Pages\Campanas;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\DashboardKpi;
use App\Filament\Pages\DetalleVehiculo;
use App\Filament\Pages\Kpis;
use App\Filament\Pages\ProgramacionCitasServicio;
use App\Filament\Pages\ProgramarBloqueo;
use App\Filament\Pages\Vehiculos;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use App\Filament\Pages\Auth\Login;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Filament\Navigation\NavigationGroup;

class AdminPanelProvider extends PanelProvider
{


    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->darkMode(false)
            ->sidebarFullyCollapsibleOnDesktop()
            ->colors([
                'primary' => '#0075BF',
                'secondary' => '#073568',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                Vehiculos::class,
                AgendarCita::class,
                DetalleVehiculo::class,
                AgregarVehiculo::class,
                Campanas::class,
                ProgramacionCitasServicio::class,
                ProgramarBloqueo::class,
                AdministrarLocales::class,
                AdministrarModelos::class,
                Kpis::class,
                DashboardKpi::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('🏠 Principal')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('🚗 Vehículos')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('📅 Citas & Servicios')
                    ->collapsed(false),
                NavigationGroup::make()
                    ->label('📢 Marketing')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('📊 Reportes & KPIs')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('⚙️ Configuración')
                    ->collapsed(true),
                NavigationGroup::make()
                    ->label('👥 Administración')
                    ->collapsed(true),
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                FilamentEditProfilePlugin::make()
                    ->setTitle('Editar Perfil')
                    ->setNavigationLabel('Perfil')
                    ->setIcon('heroicon-o-user-circle')
                    ->setNavigationGroup('👥 Administración')
                    ->shouldRegisterNavigation(),
            ])
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => Blade::render('<div id="corporate-theme-enhancer"></div>'),
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn () => \Illuminate\Support\Facades\Auth::check() ? view('customFooter') : '',
            )
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => Blade::render('<div id="corporate-theme-enhancer"></div>'),
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn () => \Illuminate\Support\Facades\Auth::check() ? view('customHeader') : '',
            );
    }
}
