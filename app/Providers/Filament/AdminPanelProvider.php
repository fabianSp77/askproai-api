<?php

namespace App\Providers\Filament;

use App\Http\Middleware\EnsureTwoFactorEnabled;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
// use App\Overrides\CustomStartSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Js;
use Filament\Support\Assets\Css;
use Illuminate\Support\Facades\Vite;
use Filament\Support\Enums\MaxWidth;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // No custom assets needed - they're causing 500 errors
        // All necessary functionality is in the main theme.css
    }
    
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
            // ->locale('de') // Deutsche Lokalisierung für Filament - Not available in Filament v3
            // ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth(MaxWidth::Full) // Use full width for content
            ->navigationGroups([
                'Täglicher Betrieb',        // Daily operations - most important
                'Kundenverwaltung',         // Customer management
                'Unternehmensstruktur',     // Company structure
                'Integrationen',            // Integrations
                'Finanzen & Abrechnung',    // Finance & Billing  
                'Einstellungen',            // Settings
                'System & Monitoring',      // System monitoring (admin only)
                'Entwicklung',              // Development (super admin only)
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                // Pages are auto-discovered, no need to manually register
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                // Widgets will be auto-discovered
                // Default Filament Widgets
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
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
            ->login()
            ->maxContentWidth('full')
            // Global actions not available in Filament 3.3.x - using alternative approach
            // TEMPORARILY DISABLED FOR DEBUGGING
            // ->renderHook(
            //     PanelsRenderHook::USER_MENU_BEFORE,
            //     fn (): string => Blade::render('@include("filament.components.professional-branch-switcher")')
            // )
            // Removed obsolete render hooks that were causing 500 errors
            // ->renderHook(PanelsRenderHook::HEAD_END, ...) // livewire-fix removed
            // ->renderHook(PanelsRenderHook::BODY_END, ...) // csrf-fix removed  
            // ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, ...) // login-button-styles removed
            // TEMPORARILY DISABLED FOR DEBUGGING
            /* ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): string => request()->is('admin/login') ? '
                    <script>
                        // Minimal login fix - ensure form submission works
                        document.addEventListener("DOMContentLoaded", function() {
                            console.log("[LoginFix] Starting minimal fix...");
                            
                            // Wait for Livewire to initialize
                            const waitForLivewire = setInterval(function() {
                                if (window.Livewire) {
                                    clearInterval(waitForLivewire);
                                    console.log("[LoginFix] Livewire ready");
                                    
                                    // Find and fix submit button
                                    const submitButtons = document.querySelectorAll(\'button[type="submit"]\');
                                    submitButtons.forEach(button => {
                                        // Skip non-login buttons
                                        if (button.closest(\'.fi-password-input\')) return;
                                        
                                        console.log("[LoginFix] Found submit button");
                                        
                                        // Ensure button is visible
                                        button.style.opacity = "1";
                                        button.style.visibility = "visible";
                                        button.style.pointerEvents = "auto";
                                        
                                        // Style the button
                                        button.style.backgroundColor = "rgb(251, 191, 36)";
                                        button.style.color = "rgb(0, 0, 0)";
                                        button.style.border = "1px solid rgb(217, 119, 6)";
                                    });
                                }
                            }, 100);
                        });
                    </script>
                ' : ''
            ) */
            ->sidebarCollapsibleOnDesktop() // Only use one collapsible option
            ;
    }
}
