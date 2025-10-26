<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
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
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Customer Panel Provider
 *
 * Provides a separate Filament panel for end customers (hairdressers, staff)
 * at /portal route with read-only access to their data.
 *
 * Security:
 * - Feature flag protection (FEATURE_CUSTOMER_PORTAL)
 * - Panel-specific authentication via User::canAccessCustomerPortal()
 * - Multi-tenant isolation via BelongsToCompany trait
 * - Policy-based authorization for all resources
 *
 * Phase 1 Features (Read-Only):
 * - Call History (Retell AI transcripts)
 * - Appointments (calendar + list view)
 * - Dashboard (stats overview)
 *
 * Phase 2 Features (Future):
 * - Customer Management (own CRM)
 * - Service Management
 * - Staff Management
 *
 * @see config/features.php
 * @see app/Models/User::canAccessCustomerPortal()
 */
class CustomerPanelProvider extends PanelProvider
{
    /**
     * Boot the service provider.
     *
     * Feature Flag Check: Only register panel if feature is enabled
     */
    public function boot(): void
    {
        // Skip panel registration if feature is disabled
        // Note: Panel registration is handled by panel() method, not boot()
    }

    public function panel(Panel $panel): Panel
    {

        return $panel
            // ============================================================
            // Panel Configuration
            // ============================================================
            ->id('portal')
            ->path('portal')
            ->brandName('AskPro AI - Kundenportal')

            // ============================================================
            // Authentication
            // ============================================================
            ->login()
            ->passwordReset()
            // Email verification disabled for customer portal
            // ->emailVerification()

            // ============================================================
            // Styling & Branding
            // ============================================================
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Slate,
            ])
            ->darkMode(true)
            ->sidebarCollapsibleOnDesktop()

            // ============================================================
            // Resource Discovery
            // Portal-specific resources in app/Filament/Customer/
            // ============================================================
            ->discoverResources(
                in: app_path('Filament/Customer/Resources'),
                for: 'App\\Filament\\Customer\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Customer/Pages'),
                for: 'App\\Filament\\Customer\\Pages'
            )
            ->discoverWidgets(
                in: app_path('Filament/Customer/Widgets'),
                for: 'App\\Filament\\Customer\\Widgets'
            )

            // ============================================================
            // Default Pages
            // ============================================================
            ->pages([
                // Dashboard will be created in app/Filament/Customer/Pages/Dashboard.php
            ])

            // ============================================================
            // Widgets (Dashboard)
            // ============================================================
            ->widgets([
                // Widgets will be auto-discovered from app/Filament/Customer/Widgets/
            ])

            // ============================================================
            // Middleware Stack
            // ============================================================
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
                // Feature flag check is handled in User::canAccessPanel()
            ])
            ->authMiddleware([
                Authenticate::class,
            ])

            // ============================================================
            // Navigation Configuration
            // ============================================================
            ->navigationGroups([
                'CRM',
                'Abrechnung',
            ])

            // ============================================================
            // User Menu Configuration
            // ============================================================
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()
                    ->label('Profil')
                    ->url(fn () => '#')
                    ->icon('heroicon-o-user-circle'),
                'logout' => \Filament\Navigation\MenuItem::make()
                    ->label('Abmelden')
                    ->url(fn () => route('filament.portal.auth.logout'))
                    ->icon('heroicon-o-arrow-right-on-rectangle'),
            ])

            // ============================================================
            // Company Scoping (via Eloquent Queries)
            // Note: We use query-level scoping instead of Filament tenancy
            // to avoid tenant selection screens. All resources filter by
            // auth()->user()->company_id in their getEloquentQuery() methods.
            // ============================================================

            // ============================================================
            // Global Search (Disabled for Phase 1)
            // ============================================================
            ->globalSearch(false)

            // ============================================================
            // Database Notifications (Phase 2)
            // ============================================================
            ->databaseNotifications(false)

            // ============================================================
            // Spa Mode (Better UX)
            // ============================================================
            ->spa();
    }
}
