<?php

namespace App\Providers\Filament;

use App\Filament\Reseller\Pages\Auth\Login;
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

class ResellerPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('reseller')
            ->path('reseller')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->font('Inter')
            ->brandName('Reseller Portal')
            ->brandLogo(asset('images/reseller-logo.svg'))
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/reseller-favicon.ico'))
            ->navigationGroups([
                'Dashboard' => 1,
                'Kunden' => 2,
                'Provisionen' => 3,
                'Reports' => 4,
                'Einstellungen' => 5,
            ])
            ->discoverResources(in: app_path('Filament/Reseller/Resources'), for: 'App\\Filament\\Reseller\\Resources')
            ->discoverPages(in: app_path('Filament/Reseller/Pages'), for: 'App\\Filament\\Reseller\\Pages')
            ->discoverWidgets(in: app_path('Filament/Reseller/Widgets'), for: 'App\\Filament\\Reseller\\Widgets')
            ->widgets([
                // Default widgets will be registered here
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
                'reseller', // Custom middleware for reseller authentication
            ])
            ->authGuard('reseller') // Use reseller guard
            ->tenant(\App\Models\Tenant::class, ownershipRelationship: 'reseller')
            ->tenantMenuItems([
                'profile' => Pages\MenuItem::make()
                    ->label('Reseller Profil')
                    ->url(fn (): string => route('filament.reseller.pages.profile'))
                    ->icon('heroicon-o-user-circle'),
                'billing' => Pages\MenuItem::make()
                    ->label('Abrechnungseinstellungen')
                    ->url(fn (): string => route('filament.reseller.pages.billing-settings'))
                    ->icon('heroicon-o-credit-card'),
            ])
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s');
    }
}