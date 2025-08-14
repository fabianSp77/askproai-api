<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class FilamentBadgeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // ← Guard verhindert Fehler beim package:discover
        if (! class_exists(\Filament\Facades\Filament::class)) {
            return;        // Filament noch nicht gebootet
        }

        // Netdata-Badge (optional)
        if (view()->exists('livewire.admin.netdata-badge')) {
            \Filament\Facades\Filament::registerRenderHook(
                'panels::topbar.end',
                fn (): string => view('livewire.admin.netdata-badge')->render(),
            );
        }

        // Horizon-Badge
        \Filament\Facades\Filament::registerRenderHook(
            'panels::topbar.end',
            fn (): string => view('livewire.admin.horizon-badge')->render(),
        );

        // + künftige Badges hier andocken …
    }
}
