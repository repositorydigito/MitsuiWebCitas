<?php

namespace App\Filament\Pages;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;

class Home extends Page
{
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Home';

    protected static ?string $navigationGroup = '🏠 Principal';

    protected static ?int $navigationSort = 0; // Para que aparezca primero

    protected static string $view = 'filament.pages.home';

    public function getTitle(): string
    {
        return '';
    }
}
