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
                'panels::head.end',
                fn (): string => '<script>console.log("🔍 Loading Tippy.js IIFE build...");</script><script src="https://unpkg.com/@popperjs/core@2/dist/umd/popper.min.js"></script><script src="https://unpkg.com/tippy.js@6/dist/tippy.umd.min.js" onload="console.log(\'✅ Tippy.js loaded:\', typeof window.tippy, window.tippy);"></script><link rel="stylesheet" href="https://unpkg.com/tippy.js@6/dist/tippy.css" />'
            )
            // Cal.com Atoms Scripts Integration
            ->renderHook(
                'panels::head.end',
                fn (): string => \Illuminate\Support\Facades\Vite::useHotFile(public_path('hot'))
                    ->useBuildDirectory('build')
                    ->withEntryPoints(['resources/js/calcom-atoms.jsx', 'resources/css/calcom-atoms.css'])
                    ->toHtml()
            )
            ->renderHook(
                'panels::head.end',
                fn (): string => '<script>
                    // Global Cal.com configuration
                    window.CalcomConfig = {
                        teamId: ' . config('calcom.team_id') . ',
                        apiUrl: \'' . config('calcom.base_url') . '\',
                        defaultBranchId: ' . (auth()->user()?->branch_id ? '"' . auth()->user()->branch_id . '"' : 'null') . ',
                        companyId: ' . (auth()->user()?->company_id ?? 'null') . ',
                        layout: \'MONTH_VIEW\',
                        autoSelectSingleBranch: true,
                    };
                    console.log("✅ CalcomConfig loaded:", window.CalcomConfig);
                </script>'
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => '<script>
console.log("🔍 Tooltip patch script starting...");
console.log("Alpine at script start:", typeof window.Alpine);
console.log("Tippy at script start:", typeof window.tippy);

document.addEventListener("alpine:init", function() {
    console.log("🎯 Alpine:init event fired!");
    console.log("Alpine now available:", typeof window.Alpine !== "undefined");
    console.log("Tippy now available:", typeof window.tippy !== "undefined");

    if (typeof window.Alpine === "undefined") {
        console.error("❌ Alpine still not available after alpine:init event!");
        return;
    }

    if (typeof window.tippy === "undefined") {
        console.error("❌ Tippy not available! Waiting 1 second...");
        setTimeout(function() {
            if (typeof window.tippy === "undefined") {
                console.error("❌ Tippy still not available after 1s. CDN may be blocked.");
                return;
            }
            initTooltipPatch();
        }, 1000);
        return;
    }

    initTooltipPatch();
});

function initTooltipPatch() {
    console.log("🔧 Patching Alpine Tooltip directive...");

    function decodeHtmlEntities(text) {
        if (typeof text !== "string") return text;
        if (!text.includes("&lt;") && !text.includes("&gt;")) return text;
        const textarea = document.createElement("textarea");
        textarea.innerHTML = text;
        return textarea.value;
    }

    const isTouchDevice = ("ontouchstart" in window) || (navigator.maxTouchPoints > 0);
    const isDark = document.documentElement.classList.contains("dark");

    window.Alpine.directive("tooltip", (el, { expression, modifiers }, { evaluateLater, effect }) => {
        let getContent = evaluateLater(expression);

        effect(() => {
            getContent((value) => {
                let config = typeof value === "string" ? { content: value } : value;
                let content = config.content || value;
                content = decodeHtmlEntities(content);

                const tippyConfig = {
                    content: content,
                    allowHTML: true,
                    interactive: true,
                    maxWidth: isTouchDevice ? "90vw" : 400,
                    trigger: isTouchDevice ? "click" : "mouseenter focus",
                    touch: isTouchDevice ? ["hold", 500] : true,
                    theme: isDark ? "dark" : "light",
                    onShow(instance) {
                        const currentDark = document.documentElement.classList.contains("dark");
                        instance.setProps({ theme: currentDark ? "dark" : "light" });
                    }
                };

                if (el._tippy) {
                    el._tippy.setProps(tippyConfig);
                } else {
                    window.tippy(el, tippyConfig);
                }
            });
        });
    });

    console.log("✅ Alpine Tooltip patched with HTML support (Mobile: " + isTouchDevice + ")");

    // Dark mode observer
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === "class") {
                const isDarkNow = document.documentElement.classList.contains("dark");
                const theme = isDarkNow ? "dark" : "light";
                if (typeof window.tippy !== "undefined" && window.tippy.instances) {
                    window.tippy.instances.forEach(instance => {
                        instance.setProps({ theme: theme });
                    });
                }
            }
        });
    });
    observer.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["class"]
    });
    console.log("✅ Dark mode observer initialized");
}
</script>'
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
            // Widgets re-enabled for GAP-010 metrics (2025-11-03)
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\RescheduleFirstMetricsWidget::class,
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
