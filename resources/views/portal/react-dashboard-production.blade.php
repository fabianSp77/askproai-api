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
    <meta name="apple-mobile-web-app-title" content="AskProAI">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="AskProAI Business Portal">
    <meta name="description" content="AI-gestütztes Business Portal für Anrufverwaltung und Terminbuchungen">
    
    <!-- Icons -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Ant Design CSS Reset -->
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
        }
        #app {
            min-height: 100vh;
        }
        .loading-spinner {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3b82f6;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    
    <!-- Production CSS -->
    <link rel="stylesheet" href="/build/assets/app-B1ObeGPD.css">
    <link rel="stylesheet" href="/css/dashboard-fixes.css">
    <link rel="stylesheet" href="/css/dashboard-improvements.css">
    <link rel="stylesheet" href="/css/dashboard-visual-fixes.css">
    <script src="/js/dashboard-fixes.js"></script>
    <script src="/js/dashboard-improvements.js"></script>
    <script src="/js/dashboard-visual-fixes.js"></script>
</head>
<body class="font-sans antialiased">
    @php
        $user = Auth::guard('portal')->user();
        $isAdminViewing = session('is_admin_viewing', false);
        $adminViewingCompany = session('admin_viewing_company', '');
        
        // Ensure user data is properly serialized
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
         data-auth="{{ json_encode(['user' => $userData, 'isAdminViewing' => $isAdminViewing, 'adminViewingCompany' => $adminViewingCompany]) }}"
         data-api-url="{{ url('/api') }}"
         data-csrf="{{ csrf_token() }}"
         data-initial-route="{{ request()->path() === 'business' ? '/' : '/' . str_replace('business/', '', request()->path()) }}">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <p>Lade Business Portal...</p>
        </div>
    </div>
    
    <script>
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}',
        };
        
        // Debug auth state
        console.log('Auth state:', {
            user: @json($userData),
            isAdminViewing: {{ $isAdminViewing ? 'true' : 'false' }},
            hasPortalAuth: {{ Auth::guard('portal')->check() ? 'true' : 'false' }},
            sessionId: '{{ session()->getId() }}'
        });
    </script>
    
    <!-- Production JS -->
    <script type="module" src="/build/assets/PortalApp-B84hu0o9.js"></script>
</body>
</html>