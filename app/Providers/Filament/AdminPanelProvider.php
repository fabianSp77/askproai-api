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

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Filament handles its own assets - we don't need to add anything here
        // The assets.blade.php file will handle our custom CSS/JS
        
        // Register searchable select fix
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            PanelsRenderHook::SCRIPTS_AFTER,
            fn (): string => Blade::render('@vite(["resources/js/filament-searchable-select-fix.js"])'),
        );
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
            ->viteTheme([
                'resources/css/filament/admin/theme.css',
                'resources/css/filament/admin/dropdown-fixes.css',
                'resources/css/filament/admin/wizard-component-fixes.css',
                'resources/css/filament/admin/monitoring-dashboard-responsive.css'
            ])
            ->navigationGroups([
                'Dashboard',
                'Täglicher Betrieb',
                'Unternehmensstruktur',
                'Einrichtung & Konfiguration',
                'Abrechnung',
                'Berichte & Analysen',
                'System',
                'System & Verwaltung',
                'Compliance & Sicherheit',
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->pages([
                // Pages are auto-discovered, no need to manually register
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->widgets([
                // Live Call Widget - Show first
                \App\Filament\Admin\Widgets\LiveCallsWidget::class,
                // Phone Agent Status Widget - Show high for monitoring
                \App\Filament\Admin\Widgets\PhoneAgentStatusWidget::class,
                // Core Widgets
                \App\Filament\Admin\Widgets\SystemStatsOverview::class,
                \App\Filament\Admin\Widgets\SubscriptionStatusWidget::class, // Billing widget
                \App\Filament\Admin\Widgets\RecentAppointments::class,
                \App\Filament\Admin\Widgets\RecentCalls::class,
                \App\Filament\Admin\Widgets\QuickActionsWidget::class,
                \App\Filament\Admin\Widgets\CustomerMetricsWidget::class,
                \App\Filament\Admin\Widgets\BranchComparisonWidget::class,
                \App\Filament\Admin\Widgets\LiveAppointmentBoard::class,
                \App\Filament\Admin\Widgets\RecentActivityWidget::class,
                // ML Performance Widget
                \App\Filament\Admin\Widgets\AgentPerformanceWidget::class,
                // Documentation Health Widget (Admin only)
                \App\Filament\Admin\Widgets\DocumentationHealthWidget::class,
                // System Health Overview Widget
                \App\Filament\Admin\Widgets\SystemHealthOverview::class,
                // Default Filament Widgets
                // \App\Filament\Admin\Widgets\EnhancedAccountWidget::class, // Temporarily disabled
                Widgets\AccountWidget::class, // Restored original
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                // \App\Http\Middleware\ResponseWrapper::class, // Fix Livewire Redirector issues
                // \App\Http\Middleware\EnsureProperResponseFormat::class, // Ensure proper response format
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class, // Use standard StartSession temporarily
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\BranchContextMiddleware::class, // Use full class name
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureTwoFactorEnabled::class,
            ])
            ->login()
            ->maxContentWidth('full')
            // Global actions not available in Filament 3.3.x - using alternative approach
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): string => Blade::render('@include("filament.components.professional-branch-switcher")')
            )
            ->sidebarCollapsibleOnDesktop()
            ->sidebarFullyCollapsibleOnDesktop()
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_BEFORE,
                fn (): string => '<button onclick="document.querySelector(\'.fi-sidebar\').classList.toggle(\'translate-x-0\')" class="lg:hidden p-2 rounded-lg text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-700"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg></button>'
            )
            ->renderHook(
                PanelsRenderHook::SIDEBAR_NAV_START,
                function (): string {
                    // Show current branch context in sidebar for mobile
                    $branchContext = app(\App\Services\BranchContextManager::class);
                    $currentBranch = $branchContext->getCurrentBranch();
                    $isAllBranches = $branchContext->isAllBranchesView();

                    return Blade::render('
                        <div class="px-3 py-2 mb-3 bg-gray-100 dark:bg-gray-800 rounded-lg lg:hidden">
                            <div class="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Aktuelle Ansicht</div>
                            <div class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                                @if($isAllBranches)
                                    🏢 Alle Filialen
                                @elseif($currentBranch)
                                    {{ $currentBranch->name }}
                                @else
                                    <span class="text-gray-400">Keine Filiale gewählt</span>
                                @endif
                            </div>
                        </div>
                    ', [
                        'currentBranch' => $currentBranch,
                        'isAllBranches' => $isAllBranches,
                    ]);
                }
            );
    }
}
