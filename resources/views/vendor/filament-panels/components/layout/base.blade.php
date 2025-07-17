<!DOCTYPE html>
<html
    lang="{{ filament()->getLocale() }}"
    dir="{{ filament()->getDirection() }}"
    @class([
        'fi',
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

        <title>
            {{ filled($title = strip_tags(($livewire ?? null)?->getTitle() ?? '')) ? "{$title} - " : null }}
            {{ strip_tags(filament()->getBrandName()) }}
        </title>

        <style>
            [x-cloak], [x-cloak] * {
                display: none !important;
            }
        </style>

        @filamentStyles

        {{ filament()->getTheme()->getHtml() }}
        {{ filament()->getFontHtml() }}

        <style>
            :root {
                --font-family: {!! filament()->getFontFamily() !!};
                --sidebar-width: {{ filament()->getSidebarWidth() }};
                --collapsed-sidebar-width: {{ filament()->getCollapsedSidebarWidth() }};
                --default-theme-mode: {{ filament()->getDefaultThemeMode()->value }};
            }
        </style>

        @stack('styles')

        @include('filament.admin.resources.layouts.assets')
        @include('filament.admin.resources.layouts.css-fix')

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::HEAD_END, scopes: $livewire->getRenderHookScopes()) }}
    </head>

    <body class="fi-body {{ \Filament\Support\get_body_classes($livewire->getRenderHookScopes()) }}">
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::BODY_START, scopes: $livewire->getRenderHookScopes()) }}

        {{ $slot }}

        {{-- TEMPORARY: All fix scripts disabled for demo --}}
        {{-- Scripts will be restored after demo with: php restore-all-scripts.php --}}
        
        @filamentScripts(withCore: true)

        @if (filament()->hasBroadcasting() && config('filament.broadcasting.echo'))
            <script data-navigate-once>
                window.Echo = new window.EchoFactory(@js(config('filament.broadcasting.echo')))
                window.dispatchEvent(new CustomEvent('EchoLoaded'))
            </script>
        @endif

        @if (filament()->hasDarkMode() && (! filament()->hasDarkModeForced()))
            <script>
                const theme = localStorage.getItem('theme') ?? @js(filament()->getDefaultThemeMode()->value)

                const lightMode = theme === 'light' || (theme === 'system' && ! window.matchMedia('(prefers-color-scheme: dark)').matches)

                if (
                    lightMode &&
                    document.documentElement.classList.contains('dark')
                ) {
                    document.documentElement.classList.remove('dark')
                } else if (
                    ! lightMode &&
                    ! document.documentElement.classList.contains('dark')
                ) {
                    document.documentElement.classList.add('dark')
                }

                const handleSystemThemeChange = () => {
                    const theme = localStorage.getItem('theme') ?? @js(filament()->getDefaultThemeMode()->value)

                    if (theme === 'system') {
                        if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                            document.documentElement.classList.add('dark')
                        } else {
                            document.documentElement.classList.remove('dark')
                        }
                    }
                }

                window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', handleSystemThemeChange)
            </script>
        @endif

        @stack('scripts')

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SCRIPTS_AFTER, scopes: $livewire->getRenderHookScopes()) }}
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::BODY_END, scopes: $livewire->getRenderHookScopes()) }}
    </body>
</html>