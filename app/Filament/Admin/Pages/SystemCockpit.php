<?php

namespace App\Filament\Admin\Pages;

use Filament\Pages\Page;

class SystemCockpit extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-bolt';
    protected static ?string $navigationLabel = 'System Cockpit';
    protected static ?string $navigationGroup = 'System & Monitoring';
    protected static string $view = 'filament.admin.pages.system-cockpit';
    protected static ?int $navigationSort = 10;
    
    public static function shouldRegisterNavigation(): bool
    {
        return false; // Deaktiviert zugunsten von SystemCockpitSimple
    }
}
