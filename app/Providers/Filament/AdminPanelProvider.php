<?php

namespace App\Providers\Filament;

use Filament\Facades\Filament;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->middleware(['web'])
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->profile()

            /* -------- automatische Discovery -------- */
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages    (in: app_path('Filament/Pages'),     for: 'App\\Filament\\Pages')
            ->discoverWidgets  (in: app_path('Filament/Widgets'),   for: 'App\\Filament\\Widgets')

            /* -------- zusätzlicher Navigations-Eintrag -------- */
            ->navigationItems([
                NavigationItem::make()
                    ->group('System')
                    ->label('Queues / Horizon')
                    ->icon('heroicon-o-sparkles')
                    ->url('/admin/horizon', shouldOpenInNewTab: true)
                    ->visible(fn () => auth()->user()?->hasRole('admin')),
            ]);
    }
}
