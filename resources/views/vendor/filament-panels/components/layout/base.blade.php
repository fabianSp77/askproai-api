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
        
        {{-- Must load before Livewire/Alpine --}}
        {{-- Temporarily disabled: <script src="{{ asset('js/livewire-config-fix.js') }}?v={{ time() }}"></script> --}}

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
        
        {{-- Filament Core App CSS manuell einbinden --}}
        <link href="https://api.askproai.de/css/filament/filament/app.css?v=3.3.14.0" rel="stylesheet">

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
        
        {{-- Admin Layout Fix - DISABLED causing horizontal scroll --}}
        {{-- <link rel="stylesheet" href="{{ asset('css/admin-layout-fix.css') }}?v={{ time() }}"> --}}

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

        @livewireStyles
        
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::HEAD_END, scopes: $livewire->getRenderHookScopes()) }}
        
        {{-- Block problematic requests early --}}
        {{-- Temporarily disabled: <script src="{{ asset('js/block-mount-params.js') }}?v={{ time() }}"></script> --}}
        
        {{-- Console cleanup to reduce noise --}}
        {{-- @if(!config('app.debug'))
            <script src="{{ asset('js/console-cleanup.js') }}?v={{ time() }}"></script>
        @endif --}}
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

        @livewireScripts
        
        {{-- Fix document.write violations before other scripts --}}
        {{-- <script src="{{ asset('js/document-write-fix.js') }}?v={{ time() }}"></script> --}}
        
        {{-- Prevent Livewire error modals --}}
        {{-- Temporarily disabled: <script src="{{ asset('js/livewire-modal-fix.js') }}?v={{ time() }}"></script> --}}
        
        {{-- Handle Livewire errors gracefully --}}
        {{-- Temporarily disabled: <script src="{{ asset('js/livewire-error-handler.js') }}?v={{ time() }}"></script> --}}
        
        @filamentScripts(withCore: true)
        
        {{-- Fix Alpine stores after Filament loads --}}
        <script src="{{ asset('js/filament-alpine-fix.js') }}?v={{ time() }}"></script>
        
        {{-- Fix modal outerHTML errors --}}
        <script src="{{ asset('js/modal-fix.js') }}?v={{ time() }}"></script>
        
        {{-- Remove error overlay --}}
        <script src="{{ asset('js/remove-error-overlay.js') }}?v={{ time() }}"></script>
        
        {{-- Ensure button functionality --}}
        <script src="{{ asset('js/button-click-handler.js') }}?v={{ time() }}"></script>
        
        {{-- Fix Filament Toggle Buttons --}}
        <script src="{{ asset('js/filament-toggle-buttons-fix.js') }}?v={{ time() }}"></script>
        
        {{-- Fix Livewire Reactive Components --}}
        <script src="{{ asset('js/livewire-reactive-fix.js') }}?v={{ time() }}"></script>
        
        {{-- Debug wizard form --}}
        <script src="{{ asset('js/debug-wizard-form.js') }}?v={{ time() }}"></script>
        
        {{-- Force wizard reactivity --}}
        <script src="{{ asset('js/force-wizard-reactivity.js') }}?v={{ time() }}"></script>
        
        {{-- Fix wizard toggle directly --}}
        <script src="{{ asset('js/fix-wizard-toggle.js') }}?v={{ time() }}"></script>
        
        {{-- Final wizard fix --}}
        <script src="{{ asset('js/final-wizard-fix.js') }}?v={{ time() }}"></script>
        
        {{-- Wizard form handler --}}
        <script src="{{ asset('js/wizard-form-handler.js') }}?v={{ time() }}"></script>
        
        {{-- Clean table layout - NO horizontal scrolling --}}
        <script src="{{ asset('js/clean-table-layout.js') }}?v={{ time() }}"></script>
        
        {{-- Test minimal setup --}}
        <script src="{{ asset('js/test-minimal-setup.js') }}?v={{ time() }}"></script>

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
    </body>
</html>
