<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->middleware(['web'])

            ->authGuard('web')          //  <<<  NEU: Guard auf 'web' stellen

            ->login()                   // Login- / Logout-Seiten aktivieren
            ->plugins([
                FilamentShieldPlugin::make(),
            ]);
    }
}
