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
            
            /* Remove overly aggressive pointer-events fix */
            /* Only target specific problem areas */
            .fi-sidebar-open::before {
                display: none !important;
                content: none !important;
                pointer-events: none !important;
                z-index: -9999 !important;
            }
            
            a, button, input, select, textarea,
            .fi-btn, .fi-link, .fi-sidebar-nav a {
                pointer-events: auto !important;
                cursor: pointer !important;
                position: relative !important;
                z-index: 10 !important;
            }
        </style>

        {{-- Emergency CSS fixes for GitHub Issues #476 & #478 --}}
        @if(filament()->getId() === 'admin')
            {{-- Emergency fix for login inputs --}}
            @if(request()->routeIs('filament.*.auth.login'))
                <link rel="stylesheet" href="{{ asset('css/login-input-force-fix.css') }}?v={{ time() }}">
            @endif
            
            @vite([
                'resources/css/filament/admin/emergency-fix-476.css',
                'resources/css/filament/admin/emergency-icon-fix-478.css',
                'resources/css/filament/admin/consolidated-interactions.css',
                'resources/css/filament/admin/consolidated-layout.css',
                'resources/css/filament/admin/navigation-ultimate-fix.css'
            ])
        @endif

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
        
        {{-- TEMPORARY INLINE FIX FOR NAVIGATION --}}
        @include('vendor.filament-panels.components.layout.navigation-inline-fix')
        
        {{-- ABSOLUTE EMERGENCY FIX --}}
        <script>
        // INLINE EMERGENCY FIX - No external dependencies
        (function() {
            console.error('🚨 INLINE EMERGENCY FIX ACTIVATED');
            
            function createEmergencyMenu() {
                if (document.getElementById('inline-emergency-menu')) return;
                
                const menu = document.createElement('div');
                menu.id = 'inline-emergency-menu';
                menu.style.cssText = 'position:fixed;top:20px;right:20px;background:white;border:3px solid red;padding:20px;z-index:2147483647;box-shadow:0 0 30px rgba(0,0,0,0.5);max-height:80vh;overflow-y:auto;';
                
                menu.innerHTML = `
                    <h3 style="color:red;margin:0 0 10px 0;">🚨 EMERGENCY NAV</h3>
                    <div id="emergency-links"></div>
                    <button onclick="this.parentElement.remove()" style="width:100%;margin-top:10px;padding:10px;background:red;color:white;border:none;cursor:pointer;">Close</button>
                `;
                
                document.body.appendChild(menu);
                
                const linksContainer = document.getElementById('emergency-links');
                const links = [
                    {url: '/admin', text: '🏠 Dashboard'},
                    {url: '/admin/calls', text: '📞 Calls'},
                    {url: '/admin/customers', text: '👥 Customers'},
                    {url: '/admin/appointments', text: '📅 Appointments'},
                    {url: '/admin/companies', text: '🏢 Companies'},
                    {url: '/admin/branches', text: '🏪 Branches'},
                    {url: '/admin/staff', text: '👷 Staff'},
                    {url: '/admin/language-settings', text: '🌐 Language Settings'},
                    {url: '/admin/a-i-call-center', text: '🤖 AI Call Center'},
                    {url: '/admin/retell-configuration-center', text: '⚙️ Retell Config'}
                ];
                
                links.forEach(link => {
                    const btn = document.createElement('button');
                    btn.style.cssText = 'display:block;width:100%;padding:10px;margin:5px 0;background:#3B82F6;color:white;border:none;cursor:pointer;';
                    btn.textContent = link.text;
                    btn.onclick = function() {
                        console.log('Emergency nav to:', link.url);
                        window.location.href = link.url;
                    };
                    linksContainer.appendChild(btn);
                });
            }
            
            // Create menu when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', createEmergencyMenu);
            } else {
                createEmergencyMenu();
            }
            
            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.altKey && e.key === 'h') {
                    createEmergencyMenu();
                    alert('Emergency menu created. Use Alt+D for Dashboard, Alt+C for Calls');
                }
                if (e.altKey && e.key === 'd') window.location.href = '/admin';
                if (e.altKey && e.key === 'c') window.location.href = '/admin/calls';
            });
        })();
        </script>
        
        {{-- Console cleanup must load first --}}
        <script src="{{ asset('js/console-cleanup.js') }}?v={{ time() }}"></script>
        
        {{-- Alpine missing components - fixes undefined component errors --}}
        <script src="{{ asset('js/alpine-missing-components.js') }}?v={{ time() }}"></script>
        
        {{-- Navigation fix - ensures all links are clickable --}}
        <script src="{{ asset('js/navigation-fix-clean.js') }}?v={{ time() }}"></script>
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
        
        {{-- Load Alpine components BEFORE Alpine.js initialization --}}
        @if(filament()->getId() === 'admin')
            {{-- Alpine components removed - causing conflicts --}}
        @endif
        
        @filamentScripts(withCore: true)

        {{-- CRITICAL: Load admin bundle for admin panel --}}
        @if(filament()->getId() === 'admin')
            @vite(['resources/js/bundles/admin.js'])
            {{-- Removed conflicting scripts - using minimal fix instead --}}
            
            {{-- Login page optimization --}}
            @if(request()->routeIs('filament.*.auth.login'))
                <script src="{{ asset('js/login-input-emergency-fix.js') }}?v={{ time() }}"></script>
                <script src="{{ asset('js/login-page-optimized.js') }}?v={{ time() }}"></script>
            @endif
        @endif

        {{-- Essential JavaScript fixes --}}
        <script>
            // Wait for Alpine and Livewire to be ready
            document.addEventListener('alpine:init', () => {
                // console.log('[Admin Panel] Alpine initialized');
                
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
                // console.log('[Admin Panel] DOM loaded, fixing interactions...');
                
                // EMERGENCY FIX: Force all inputs to be interactive on login page
                if (window.location.pathname.includes('login')) {
                    // console.log('🚨 Applying login input emergency fix...');
                    
                    // Fix all inputs immediately
                    const fixAllInputs = () => {
                        const inputs = document.querySelectorAll('input, textarea, select');
                        inputs.forEach(input => {
                            input.style.pointerEvents = 'auto';
                            input.style.userSelect = 'text';
                            input.style.webkitUserSelect = 'text';
                            input.style.cursor = 'text';
                            input.removeAttribute('readonly');
                            input.removeAttribute('disabled');
                            // console.log('Fixed input:', input.type || input.tagName);
                        });
                        
                        // Remove any blocking overlays
                        document.querySelectorAll('.fi-modal-overlay, .fixed.inset-0').forEach(el => {
                            el.style.display = 'none';
                        });
                    };
                    
                    // Run multiple times to catch dynamic content
                    fixAllInputs();
                    setTimeout(fixAllInputs, 100);
                    setTimeout(fixAllInputs, 500);
                    setTimeout(fixAllInputs, 1000);
                }
                
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
        
        {{-- Removed emergency framework fix - replaced with minimal fix --}}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::BODY_END, scopes: $livewire?->getRenderHookScopes() ?? []) }}
    </body>
</html>