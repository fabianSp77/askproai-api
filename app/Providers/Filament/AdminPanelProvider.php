<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')

            /* ---------- Auth ---------- */
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->profile()

            /* ---------- Auto-Discovery ---------- */
            ->discoverResources(app_path('Filament/Resources'), 'App\\Filament\\Resources')
            ->discoverPages    (app_path('Filament/Pages'),     'App\\Filament\\Pages')
            ->discoverWidgets  (app_path('Filament/Widgets'),   'App\\Filament\\Widgets')

            /* ---------- Middleware ---------- */
            ->middleware(['web']);
    }
}
