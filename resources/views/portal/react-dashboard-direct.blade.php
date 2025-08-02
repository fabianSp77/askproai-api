<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'AskProAI') }} - Business Portal</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    
    <!-- Icons -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Production CSS -->
    <link rel="stylesheet" href="/build/assets/app-CxN0OuGD.css">
    
    <style>
        #app { min-height: 100vh; }
        .loading-spinner {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
    </style>
    
    <script>
        // Pre-initialize auth state
        window.__PRELOADED_AUTH_STATE__ = {
            authenticated: true,
            user: {
                id: {{ $user->id ?? 41 }},
                name: "{{ $user->name ?? 'Demo User' }}",
                email: "{{ $user->email ?? 'demo@askproai.de' }}",
                company_id: {{ $user->company_id ?? 1 }},
                role: "{{ $user->role ?? 'admin' }}"
            }
        };
        
        // Override fetch for auth endpoints
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            if (url.includes('/api/user') || url.includes('/business/api/user')) {
                return Promise.resolve({
                    ok: true,
                    status: 200,
                    json: () => Promise.resolve(window.__PRELOADED_AUTH_STATE__.user),
                    headers: new Headers({'content-type': 'application/json'})
                });
            }
            return originalFetch.apply(this, arguments);
        };
        
        // Set localStorage
        localStorage.setItem('portal_auth', 'true');
        localStorage.setItem('portal_user', JSON.stringify(window.__PRELOADED_AUTH_STATE__.user));
    </script>
</head>
<body class="font-sans antialiased">
    @php
        $user = Auth::guard('portal')->user();
        $userData = null;
        if ($user) {
            $userData = [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'company_id' => $user->company_id,
                'role' => $user->role ?? 'user'
            ];
        }
    @endphp
    
    <div id="app" 
         data-auth="{{ json_encode(['user' => $userData]) }}"
         data-api-url="{{ url('/api') }}"
         data-csrf="{{ csrf_token() }}"
         data-initial-route="/">
        <div class="loading-spinner">
            <p>Lade Business Portal...</p>
        </div>
    </div>
    
    <script>
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}',
        };
    </script>
    
    <!-- React App Bundle -->
    <script type="module" src="/build/assets/app-react-simple-DPGvTkrt.js"></script>
</body>
</html>