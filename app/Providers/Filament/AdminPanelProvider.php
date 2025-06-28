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
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Overrides\CustomStartSession;
use App\Http\Middleware\EnsureTwoFactorEnabled;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\Blade;
use Filament\Navigation\NavigationGroup;

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
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->navigationGroups([
                'Dashboard',
                'Täglicher Betrieb',
                'Kundenverwaltung',
                'Verwaltung',
                'Integrationen',
                'Berichte',
                'System',
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
                // Default Filament Widgets
                Widgets\AccountWidget::class,
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
                'branch.context', // Add branch context handling
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureTwoFactorEnabled::class,
            ])
            ->login()
            ->maxContentWidth('full')
            // Global actions not available in Filament 3.3.x - using alternative approach
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => Blade::render('
                    @php
                        $branchContext = app(\App\Services\BranchContextManager::class);
                        $currentBranch = $branchContext->getCurrentBranch();
                        $isAllBranches = $branchContext->isAllBranchesView();
                        $branches = $branchContext->getBranchesForUser();
                    @endphp
                    
                    @if($branches->count() > 1)
                        <div class="fi-dropdown" x-data="{ open: false }">
                            <button
                                @click="open = ! open"
                                type="button"
                                class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 disabled:pointer-events-none disabled:opacity-70 text-sm gap-1.5 px-3 py-2 text-gray-950 bg-white shadow-sm ring-1 ring-gray-950/10 hover:bg-gray-50 dark:text-white dark:bg-white/10 dark:hover:bg-white/20 dark:ring-white/20"
                            >
                                <svg class="fi-btn-icon transition duration-75 h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M4 16.5v-13h-.25a.75.75 0 010-1.5h12.5a.75.75 0 010 1.5H16v13h.25a.75.75 0 010 1.5h-3.5a.75.75 0 01-.75-.75v-2.5a.75.75 0 00-.75-.75h-2.5a.75.75 0 00-.75.75v2.5a.75.75 0 01-.75.75h-3.5a.75.75 0 010-1.5H4zm3-11a.5.5 0 01.5-.5h1a.5.5 0 01.5.5v1a.5.5 0 01-.5.5h-1a.5.5 0 01-.5-.5v-1zM7.5 9a.5.5 0 00-.5.5v1a.5.5 0 00.5.5h1a.5.5 0 00.5-.5v-1a.5.5 0 00-.5-.5h-1zM11 5.5a.5.5 0 01.5-.5h1a.5.5 0 01.5.5v1a.5.5 0 01-.5.5h-1a.5.5 0 01-.5-.5v-1zm.5 3.5a.5.5 0 00-.5.5v1a.5.5 0 00.5.5h1a.5.5 0 00.5-.5v-1a.5.5 0 00-.5-.5h-1z" clip-rule="evenodd" />
                                </svg>
                                <span class="fi-btn-label">
                                    @if($isAllBranches)
                                        Alle Filialen
                                    @else
                                        {{ $currentBranch?->name ?? "Filiale wählen" }}
                                    @endif
                                </span>
                                <svg class="fi-btn-icon transition duration-75 h-5 w-5 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                </svg>
                            </button>
                            
                            <div
                                x-show="open"
                                @click.away="open = false"
                                x-transition:enter="transition ease-out duration-100"
                                x-transition:enter-start="transform opacity-0 scale-95"
                                x-transition:enter-end="transform opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-75"
                                x-transition:leave-start="transform opacity-100 scale-100"
                                x-transition:leave-end="transform opacity-0 scale-95"
                                class="absolute right-0 z-10 mt-2 w-56 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800"
                                style="display: none;"
                            >
                                <div class="py-1">
                                    <a href="{{ url()->current() }}?branch=all" class="text-gray-700 dark:text-gray-200 block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                                        🏢 Alle Filialen
                                    </a>
                                    @foreach($branches as $branch)
                                        <a href="{{ url()->current() }}?branch={{ $branch->id }}" class="text-gray-700 dark:text-gray-200 block px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700">
                                            {{ $branch->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                ')
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
                        'isAllBranches' => $isAllBranches
                    ]);
                }
            );
    }
}
