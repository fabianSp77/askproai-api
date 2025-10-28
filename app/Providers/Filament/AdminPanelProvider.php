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
            ->authGuard('web')
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
            ->renderHook(
                'panels::body.end',
                fn (): string => <<<'HTML'
                <script>
                    // Configure Tippy.js for HTML tooltips with dark mode support
                    document.addEventListener('DOMContentLoaded', function() {
                        // Detect touch device
                        const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;

                        // Detect dark mode
                        const isDark = document.documentElement.classList.contains('dark');

                        // Set global Tippy.js defaults for HTML tooltips
                        if (typeof tippy !== 'undefined' && tippy.setDefaultProps) {
                            tippy.setDefaultProps({
                                allowHTML: true,
                                interactive: true,
                                maxWidth: isTouchDevice ? '90vw' : 400,
                                trigger: isTouchDevice ? 'click' : 'mouseenter focus',
                                touch: isTouchDevice ? ['hold', 500] : true,
                                theme: isDark ? 'dark' : 'light',
                                onShow(instance) {
                                    // Update theme if changed during session
                                    const currentDark = document.documentElement.classList.contains('dark');
                                    instance.setProps({ theme: currentDark ? 'dark' : 'light' });
                                }
                            });
                        }

                        // Watch for dark mode changes and update all tooltips
                        const observer = new MutationObserver(function(mutations) {
                            mutations.forEach(function(mutation) {
                                if (mutation.attributeName === 'class') {
                                    const isDarkNow = document.documentElement.classList.contains('dark');
                                    const theme = isDarkNow ? 'dark' : 'light';

                                    // Update all active Tippy instances
                                    if (typeof tippy !== 'undefined' && tippy.instances) {
                                        tippy.instances.forEach(instance => {
                                            instance.setProps({ theme: theme });
                                        });
                                    }
                                }
                            });
                        });

                        observer.observe(document.documentElement, {
                            attributes: true,
                            attributeFilter: ['class']
                        });
                    });
                </script>
                HTML
            )
            // NOTE: Removed Livewire.start() - Livewire 3 auto-initializes server-rendered components
            // Calling start() on already-hydrated pages can interfere with directive attachment
            // Livewire will process wire:snapshot attributes automatically
            // MEMORY FIX APPLIED: Circular dependency eliminated (User model no longer has CompanyScope)
            // Re-enabling Discovery after fixing root cause (User.php Line 17 - BelongsToCompany removed)

            // Re-enable all resources for discovery
            // Memory fix applied, badge caching implemented - safe to discover
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')

            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            // Widgets disabled until database fully migrated (some query missing tables)
            // ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
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
