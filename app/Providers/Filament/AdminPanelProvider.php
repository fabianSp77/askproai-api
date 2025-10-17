<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Filament\Navigation\NavigationItem;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // CRITICAL DEBUG: Log Filament panel config start
        \Log::info('🎨 AdminPanelProvider::panel() START - Memory: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB');

        $configuredPanel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->renderHook(
                'panels::head.end',
                fn (): string => \Illuminate\Support\Facades\Vite::useHotFile(public_path('hot'))
                    ->useBuildDirectory('build')
                    ->withEntryPoints(['resources/css/call-detail-full-width.css'])
                    ->toHtml()
            )
            // NOTE: Removed Livewire.start() - Livewire 3 auto-initializes server-rendered components
            // Calling start() on already-hydrated pages can interfere with directive attachment
            // Livewire will process wire:snapshot attributes automatically
            // MEMORY FIX APPLIED: Circular dependency eliminated (User model no longer has CompanyScope)
            // Re-enabling Discovery after fixing root cause (User.php Line 17 - BelongsToCompany removed)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // ALL WIDGETS DISABLED FOR DEBUGGING
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
                // \App\Filament\Widgets\CalcomSyncStatusWidget::class,
                // \App\Filament\Widgets\CalcomSyncActivityWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->navigationItems([
                NavigationItem::make('Retell Agent Update')
                    ->url('/guides/retell-agent-update.html', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-document-text')
                    ->group('Anleitungen')
                    ->sort(100),
            ]);

        // CRITICAL DEBUG: Log Filament panel config end
        \Log::info('✅ AdminPanelProvider::panel() END - Memory: ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB');

        return $configuredPanel;
    }
}
