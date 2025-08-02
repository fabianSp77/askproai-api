@props([
    'livewire' => null,
])

<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ __('filament-panels::layout.direction') ?? 'ltr' }}"
    @class([
        'fi min-h-screen',
        'dark' => filament()->hasDarkModeForced(),
    ])
>
    <head>
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::HEAD_START, scopes: $livewire?->getRenderHookScopes() ?? []) }}

        <meta charset="utf-8" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        @if ($favicon = filament()->getFavicon())
            <link rel="icon" href="{{ $favicon }}" />
        @endif

        <title>
            {{ filled($title = strip_tags(($livewire ?? null)?->getTitle() ?? '')) ? "{$title} - " : null }}
            {{ strip_tags(filament()->getBrandName()) }}
        </title>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::STYLES_BEFORE, scopes: $livewire?->getRenderHookScopes() ?? []) }}

        <style>
            [x-cloak=''],
            [x-cloak='x-cloak'],
            [x-cloak='1'] {
                display: none !important;
            }

            @media (max-width: 1023px) {
                [x-cloak='-lg'] {
                    display: none !important;
                }
            }

            @media (min-width: 1024px) {
                [x-cloak='lg'] {
                    display: none !important;
                }
            }
        </style>

        @filamentStyles

        {{ filament()->getTheme()->getHtml() }}
        {{ filament()->getFontHtml() }}

        <style>
            :root {
                --font-family: '{!! filament()->getFontFamily() !!}';
                --sidebar-width: {{ filament()->getSidebarWidth() }};
                --collapsed-sidebar-width: {{ filament()->getCollapsedSidebarWidth() }};
                --default-theme-mode: {{ filament()->getDefaultThemeMode()->value }};
            }
            
            /* CRITICAL FIX: Ensure ALL interactive elements are clickable */
            a, button, input, select, textarea,
            [role="button"], [role="link"], [role="menuitem"],
            [wire\:click], [x-on\:click], [onclick],
            .cursor-pointer, .fi-btn, .fi-link, .fi-dropdown-item,
            .fi-sidebar-nav-item, .fi-topbar-item, .fi-ac-trigger,
            .fi-ta-header-cell-label, .fi-ta-row, .fi-ta-cell,
            .fi-pagination-item, .fi-breadcrumb-item {
                pointer-events: auto !important;
                cursor: pointer !important;
                position: relative !important;
                z-index: 1 !important;
            }
            
            /* Ensure main content areas don't block interactions */
            body, .fi-body, .fi-main, .fi-main-ctn, .fi-page,
            .fi-section, .fi-resource, .fi-ta-ctn {
                pointer-events: auto !important;
            }
            
            /* Specifically fix dropdowns and navigation */
            .fi-dropdown-panel, .fi-dropdown-list, .fi-sidebar-nav,
            .fi-topbar, .fi-breadcrumbs {
                pointer-events: auto !important;
                z-index: 1000 !important;
            }

            /* Fix for black screen issues */
            body.fi-sidebar-open::before,
            body.fi-sidebar-open::after {
                display: none !important;
                content: none !important;
            }
            
            /* Ensure body and login forms are visible */
            body {
                overflow: visible !important;
                background-color: rgb(249 250 251) !important;
            }
            
            .fi-simple-page,
            .fi-login-panel,
            form {
                opacity: 1 !important;
                visibility: visible !important;
            }
            
            /* Fix icon sizes */
            .fi-icon svg,
            .fi-ta-icon svg {
                max-width: 1.25rem !important;
                max-height: 1.25rem !important;
            }
            
            .fi-modal-icon svg,
            .fi-empty-state-icon svg {
                max-width: 2rem !important;
                max-height: 2rem !important;
            }
            
            /* Hide stuck loading spinners */
            .fi-login .fi-loading-indicator,
            .fi-login .animate-spin,
            .fi-simple-page .fi-loading-indicator,
            .fi-simple-page .animate-spin {
                display: none !important;
            }

            /* Fix for animation conflicts */
            .fi-dropdown-panel {
                transition: all 0.2s ease !important;
            }

            /* Ensure proper event handling */
            .fi-dropdown-trigger:hover,
            .fi-btn:hover,
            .fi-link:hover {
                cursor: pointer !important;
            }
        </style>

        @stack('styles')

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::STYLES_AFTER, scopes: $livewire?->getRenderHookScopes() ?? []) }}

        @if (! filament()->hasDarkMode())
            <script>
                localStorage.setItem('theme', 'light')
            </script>
        @elseif (filament()->hasDarkModeForced())
            <script>
                localStorage.setItem('theme', 'dark')
            </script>
        @else
            <script>
                const theme = localStorage.getItem('theme') ?? @js(filament()->getDefaultThemeMode()->value)
                
                if (
                    theme === 'dark' ||
                    (theme === 'system' &&
                        window.matchMedia('(prefers-color-scheme: dark)')
                            .matches)
                ) {
                    document.documentElement.classList.add('dark')
                }
            </script>
        @endif

        @livewireStyles

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::HEAD_END, scopes: $livewire?->getRenderHookScopes() ?? []) }}
    </head>

    <body
        {{ $attributes
                ->merge(($livewire ?? null)?->getExtraBodyAttributes() ?? [], escape: false)
                ->class([
                    'fi-body',
                    'fi-panel-' . filament()->getId(),
                    'min-h-screen bg-gray-50 font-normal text-gray-950 antialiased dark:bg-gray-950 dark:text-white',
                ]) }}
    >
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::BODY_START, scopes: $livewire?->getRenderHookScopes() ?? []) }}

        {{ $slot }}

        @livewire(Filament\Livewire\Notifications::class)

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SCRIPTS_BEFORE, scopes: $livewire?->getRenderHookScopes() ?? []) }}

        @livewireScripts
        
        @filamentScripts(withCore: true)

        {{-- CRITICAL: Load admin bundle for admin panel --}}
        @if(filament()->getId() === 'admin')
            @vite(['resources/js/bundles/admin.js'])
        @endif

        {{-- Essential JavaScript fixes --}}
        <script>
            // Wait for Alpine and Livewire to be ready
            document.addEventListener('alpine:init', () => {
                console.log('[Admin Panel] Alpine initialized');
                
                // Fix dropdown behavior
                Alpine.data('adminDropdown', () => ({
                    open: false,
                    
                    toggle() {
                        this.open = !this.open;
                    },
                    
                    close() {
                        this.open = false;
                    }
                }));
            });

            // Ensure all elements are clickable after DOM loads
            document.addEventListener('DOMContentLoaded', () => {
                console.log('[Admin Panel] DOM loaded, fixing interactions...');
                
                // Fix any elements that might have pointer-events: none
                const fixClickability = () => {
                    document.querySelectorAll('a, button, [role="button"], .fi-btn, .fi-link, .fi-dropdown-trigger').forEach(el => {
                        if (getComputedStyle(el).pointerEvents === 'none') {
                            el.style.pointerEvents = 'auto';
                            el.style.cursor = 'pointer';
                        }
                    });
                };

                // Fix immediately and after any dynamic content changes
                fixClickability();
                
                // Use MutationObserver to fix dynamically added elements
                const observer = new MutationObserver(() => {
                    fixClickability();
                });
                
                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            });

            // Fix Livewire interactions
            document.addEventListener('livewire:load', () => {
                console.log('[Admin Panel] Livewire loaded');
                
                // Ensure Livewire events work properly
                Livewire.on('refreshComponent', () => {
                    setTimeout(() => {
                        document.querySelectorAll('[wire\\:click]').forEach(el => {
                            el.style.pointerEvents = 'auto';
                            el.style.cursor = 'pointer';
                        });
                    }, 100);
                });
            });

            // Global debug function
            window.debugAdminPanel = function() {
                const info = {
                    alpine: typeof Alpine !== 'undefined',
                    livewire: typeof Livewire !== 'undefined',
                    clickableElements: document.querySelectorAll('a, button, [role="button"]').length,
                    blockedElements: 0,
                    dropdowns: document.querySelectorAll('[x-data*="open"]').length
                };
                
                document.querySelectorAll('a, button, [role="button"], .fi-btn, .fi-link').forEach(el => {
                    if (getComputedStyle(el).pointerEvents === 'none') {
                        info.blockedElements++;
                        console.warn('Blocked element:', el);
                    }
                });
                
                console.table(info);
                return info;
            };
        </script>

        @if (filament()->hasBroadcasting() && config('filament.broadcasting.echo'))
            <script data-navigate-once>
                window.Echo = new window.EchoFactory(@js(config('filament.broadcasting.echo')))
                window.dispatchEvent(new CustomEvent('EchoLoaded'))
            </script>
        @endif

        @stack('scripts')

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SCRIPTS_AFTER, scopes: $livewire?->getRenderHookScopes() ?? []) }}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::BODY_END, scopes: $livewire?->getRenderHookScopes() ?? []) }}
    </body>
</html>