<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Kundenportal') - {{ config('app.name', 'AskProAI') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- Styles -->
    @if(file_exists(public_path('build/manifest.json')))
        @vite(['resources/css/app.css'])
    @else
        <!-- Inline base styles for auth pages -->
        <style>
            /* Base reset and typography */
            *, ::before, ::after {
                box-sizing: border-box;
                border-width: 0;
                border-style: solid;
                border-color: #e5e7eb;
            }
            html {
                line-height: 1.5;
                -webkit-text-size-adjust: 100%;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }
            body {
                margin: 0;
                font-family: inherit;
                line-height: inherit;
            }
        </style>
    @endif
    
    <style>
        :root {
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-200: #bfdbfe;
            --primary-300: #93bbfc;
            --primary-400: #60a5fa;
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
            --primary-800: #1e40af;
            --primary-900: #1e3a8a;
        }
        
        .text-primary-600 { color: var(--primary-600); }
        .text-primary-500 { color: var(--primary-500); }
        .bg-primary-600 { background-color: var(--primary-600); }
        .bg-primary-700 { background-color: var(--primary-700); }
        .hover\:text-primary-500:hover { color: var(--primary-500); }
        .hover\:bg-primary-700:hover { background-color: var(--primary-700); }
        .focus\:ring-primary-500:focus { --tw-ring-color: var(--primary-500); }
        .focus\:border-primary-500:focus { border-color: var(--primary-500); }
    </style>
    
    <!-- Fix for clickable elements -->
    <link href="/css/portal-click-fix-final.css" rel="stylesheet">
    
    <!-- CSRF Setup -->
    <script src="/js/portal-csrf-setup.js"></script>
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @yield('content')
    </div>
    
    {{-- Service Worker Cleanup - Only run if needed --}}
    @if(request()->has('clear-sw'))
        <script src="{{ asset('js/force-unregister-business-sw.js') }}?v={{ time() }}"></script>
    @endif
    
    @stack('scripts')
</body>
</html>