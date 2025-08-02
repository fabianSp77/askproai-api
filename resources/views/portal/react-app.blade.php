<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'AskProAI') }} - Business Portal</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/favicon.png">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Styles -->
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/business-portal.jsx'])
    
    <style>
        #app {
            min-height: 100vh;
        }
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div id="app">
        <div class="loading-spinner">
            <div class="spinner"></div>
        </div>
    </div>
    
    <script>
        // Pass auth state and initial data to React
        window.__INITIAL_STATE__ = {
            auth: {
                user: @json(auth()->guard('portal')->user()),
                check: {{ auth()->guard('portal')->check() ? 'true' : 'false' }}
            },
            csrf_token: '{{ csrf_token() }}',
            routes: {
                login: '{{ route('business.login') }}',
                logout: '{{ route('business.logout') }}',
                dashboard: '{{ route('business.dashboard') }}',
                api: {
                    base: '/business/api',
                    auth: {
                        login: '{{ route('business.api.auth.login') }}',
                        logout: '{{ route('business.api.auth.logout') }}',
                        check: '{{ route('business.api.auth.check') }}',
                    }
                }
            }
        };
    </script>
</body>
</html>