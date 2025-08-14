<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class Dashboard extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = '🏠 Principal';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.dashboard';
}
