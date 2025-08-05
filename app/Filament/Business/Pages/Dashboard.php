<?php

namespace App\Filament\Business\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    // Use Filament's default dashboard view
    // protected static string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        return [];
    }
}