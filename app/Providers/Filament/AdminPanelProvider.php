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
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Overrides\CustomStartSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                \App\Filament\Admin\Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                // Core Widgets
                \App\Filament\Admin\Widgets\SystemStatsOverview::class,
                \App\Filament\Admin\Widgets\RecentAppointments::class,
                \App\Filament\Admin\Widgets\RecentCalls::class,
                \App\Filament\Admin\Widgets\QuickActionsWidget::class,
                \App\Filament\Admin\Widgets\CustomerMetricsWidget::class,
                \App\Filament\Admin\Widgets\BranchComparisonWidget::class,
                \App\Filament\Admin\Widgets\LiveAppointmentBoard::class,
                \App\Filament\Admin\Widgets\RecentActivityWidget::class,
                // Default Filament Widgets
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                \App\Http\Middleware\ResponseWrapper::class, // Fix Livewire Redirector issues
                \App\Http\Middleware\EnsureProperResponseFormat::class, // Ensure proper response format
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                CustomStartSession::class, // Use our custom StartSession that handles Livewire
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
            ->login();
    }
}
