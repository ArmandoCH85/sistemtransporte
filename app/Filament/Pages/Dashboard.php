<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationLabel = 'Inicio';
    protected static ?string $navigationGroup = null;
    protected static ?string $title = 'Inicio';
    protected static ?int $navigationSort = -2;
    protected static string $view = 'custom.dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
        //no hacer webadas si no ser puto
    }
}
