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
use Filament\Support\Enums\MaxWidth;

/**
 * Emergency Admin Panel Provider - Minimal configuration to prevent memory exhaustion
 * 
 * This is a stripped-down version that only loads essential resources to prevent
 * the memory exhaustion issue caused by auto-discovering 217 Filament resources.
 */
class AdminPanelProviderEmergency extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->favicon('/favicon.svg')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth(MaxWidth::Full)
            ->navigationGroups([
                'Emergency Access',
                'System',
            ])
            // MANUAL REGISTRATION - Only essential resources to prevent memory exhaustion
            ->resources([
                // Only load the most critical resources found in the directory
                \App\Filament\Admin\Resources\CompanyResource::class,
                \App\Filament\Admin\Resources\UserResource::class,
                \App\Filament\Admin\Resources\CallResource::class,
                \App\Filament\Admin\Resources\AppointmentResource::class,
            ])
            ->pages([
                // Only essential pages
                Pages\Dashboard::class,
            ])
            ->widgets([
                // Minimal widgets
                Widgets\AccountWidget::class,
            ])
            ->middleware([
                \App\Http\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
                \Illuminate\View\Middleware\ShareErrorsFromSession::class,
                \App\Http\Middleware\VerifyCsrfToken::class,
                \Illuminate\Routing\Middleware\SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                AuthenticateSession::class,
            ])
            ->maxContentWidth('full')
            ->sidebarCollapsibleOnDesktop();
    }
}