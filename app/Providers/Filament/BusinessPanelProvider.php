<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureCompanyContext;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class BusinessPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('business')
            ->path('business')
            ->login()
            ->passwordReset()
            ->profile()
            ->brandName('Business Portal')
            ->brandLogo('/images/logo-business.svg')
            ->favicon('/favicon-business.svg')
            ->colors([
                'primary' => Color::Blue,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Green,
                'warning' => Color::Orange,
                'danger' => Color::Red,
            ])
            ->maxContentWidth(MaxWidth::Full)
            ->navigationGroups([
                'Dashboard',                  // Overview and stats
                'Calls & Appointments',       // Core business data
                'Customer Management',        // CRM features
                'Analytics & Reports',        // Business insights
                'Settings',                   // User and company settings
            ])
            ->discoverResources(
                in: app_path('Filament/Business/Resources'), 
                for: 'App\\Filament\\Business\\Resources'
            )
            ->discoverPages(
                in: app_path('Filament/Business/Pages'), 
                for: 'App\\Filament\\Business\\Pages'
            )
            ->discoverWidgets(
                in: app_path('Filament/Business/Widgets'), 
                for: 'App\\Filament\\Business\\Widgets'
            )
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
                EnsureCompanyContext::class, // Ensure company context is set
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('web') // Use unified web guard
            ->tenant(\App\Models\Company::class) // Multi-tenancy by company
            ->tenantMenuItems([
                'register' => false, // Disable tenant registration
                'profile' => true,   // Allow company profile editing
            ])
            ->userMenuItems([
                'profile' => \Filament\Navigation\MenuItem::make()->label('My Profile'),
                'settings' => \Filament\Navigation\MenuItem::make()
                    ->label('Settings')
                    ->url(fn (): string => '/business/settings')
                    ->icon('heroicon-o-cog-6-tooth'),
                'logout' => \Filament\Navigation\MenuItem::make()->label('Log Out'),
            ])
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop(false)
            ->topNavigation(false) // Keep sidebar navigation
            ->breadcrumbs(true)
            ->darkMode(true)
            ->spa() // Enable SPA mode for better performance
            ;
    }
}