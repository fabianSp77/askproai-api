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
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::HEAD_START, scopes: $livewire->getRenderHookScopes()) }}

        <meta charset="utf-8" />
        <meta name="csrf-token" content="{{ csrf_token() }}" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        @if ($favicon = filament()->getFavicon())
            <link rel="icon" href="{{ $favicon }}" />
        @endif

        @php
            $title = trim(strip_tags(($livewire ?? null)?->getTitle() ?? ''));
            $brandName = trim(strip_tags(filament()->getBrandName()));
        @endphp

        <title>
            {{ filled($title) ? "{$title} - " : null }} {{ $brandName }}
        </title>

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::STYLES_BEFORE, scopes: $livewire->getRenderHookScopes()) }}

        <style>
            [x-cloak=''],
            [x-cloak='x-cloak'],
            [x-cloak='1'] {
                display: none !important;
            }
            
            /* EMERGENCY FIX FOR ISSUE #578 - Navigation Overlap */
            .fi-layout {
                display: grid !important;
                grid-template-columns: 16rem 1fr !important;
                min-height: 100vh !important;
            }
            
            .fi-sidebar {
                grid-column: 1 !important;
                position: sticky !important;
                top: 0 !important;
                height: 100vh !important;
                overflow-y: auto !important;
                background: white !important;
                border-right: 1px solid rgb(229 231 235) !important;
                z-index: 40 !important;
            }
            
            .fi-main-ctn {
                grid-column: 2 !important;
                opacity: 1 !important;
                display: flex !important;
                flex-direction: column !important;
                min-height: 100vh !important;
                overflow-x: hidden !important;
            }
            
            .fi-sidebar-nav {
                padding: 0.5rem !important;
            }
            
            .fi-sidebar-item {
                margin-bottom: 0.125rem !important;
            }
            
            .fi-sidebar-item a {
                display: flex !important;
                align-items: center !important;
                padding: 0.625rem 0.75rem !important;
                border-radius: 0.5rem !important;
                pointer-events: auto !important;
                position: relative !important;
                z-index: 10 !important;
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
        </style>

        @stack('styles')

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::STYLES_AFTER, scopes: $livewire->getRenderHookScopes()) }}

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
                const loadDarkMode = () => {
                    window.theme = localStorage.getItem('theme') ?? @js(filament()->getDefaultThemeMode()->value)

                    if (
                        window.theme === 'dark' ||
                        (window.theme === 'system' &&
                            window.matchMedia('(prefers-color-scheme: dark)')
                                .matches)
                    ) {
                        document.documentElement.classList.add('dark')
                    }
                }

                loadDarkMode()

                document.addEventListener('livewire:navigated', loadDarkMode)
            </script>
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::HEAD_END, scopes: $livewire->getRenderHookScopes()) }}
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
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::BODY_START, scopes: $livewire->getRenderHookScopes()) }}

        {{ $slot }}

        @livewire(Filament\Livewire\Notifications::class)

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SCRIPTS_BEFORE, scopes: $livewire->getRenderHookScopes()) }}

        @filamentScripts(withCore: true)

        @if (filament()->hasBroadcasting() && config('filament.broadcasting.echo'))
            <script data-navigate-once>
                window.Echo = new window.EchoFactory(@js(config('filament.broadcasting.echo')))

                window.dispatchEvent(new CustomEvent('EchoLoaded'))
            </script>
        @endif

        @if (filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
            <script>
                loadDarkMode()
            </script>
        @endif

        @stack('scripts')

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SCRIPTS_AFTER, scopes: $livewire->getRenderHookScopes()) }}

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::BODY_END, scopes: $livewire->getRenderHookScopes()) }}
        <script>
            // Emergency JavaScript fix for navigation - Issue #578
            document.addEventListener('DOMContentLoaded', function() {
                const layout = document.querySelector('.fi-layout');
                if (layout) {
                    layout.style.display = 'grid';
                    layout.style.gridTemplateColumns = '16rem 1fr';
                }
                
                const sidebar = document.querySelector('.fi-sidebar');
                if (sidebar) {
                    sidebar.style.gridColumn = '1';
                    sidebar.style.position = 'sticky';
                    sidebar.style.top = '0';
                    sidebar.style.height = '100vh';
                    sidebar.style.zIndex = '40';
                }
                
                const mainCtn = document.querySelector('.fi-main-ctn');
                if (mainCtn) {
                    mainCtn.style.gridColumn = '2';
                    mainCtn.style.opacity = '1';
                    mainCtn.style.display = 'flex';
                    mainCtn.style.flexDirection = 'column';
                }
                
                // Ensure all navigation links are clickable
                const navLinks = document.querySelectorAll('.fi-sidebar-item a');
                navLinks.forEach(link => {
                    link.style.pointerEvents = 'auto';
                    link.style.position = 'relative';
                    link.style.zIndex = '10';
                });
                
                console.log('✅ Navigation fix applied via JavaScript - Issue #578');
            });
        </script>
    </body>
</html>
