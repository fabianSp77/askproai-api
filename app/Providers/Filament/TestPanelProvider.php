<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('testpanel')
            ->path('testpanel')      // -> /testpanel
            ->login()                // -> /testpanel/login
            ->default()              // startet auf TestDashboard
            ->discoverPages(
                in: app_path('Filament/TestPanel/Pages'),
                for: 'App\\Filament\\TestPanel\\Pages',
            );
    }
}
