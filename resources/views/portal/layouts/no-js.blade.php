<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta http-equiv="refresh" content="300"> {{-- Auto refresh every 5 minutes --}}
    
    <title>@yield('title', 'Portal') - {{ config('app.name') }}</title>
    
    {{-- Critical inline CSS for no-JS version --}}
    <style>
        /* Reset and base styles */
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, sans-serif; line-height: 1.5; color: #1a202c; background: #f7fafc; }
        a { color: #3182ce; text-decoration: none; }
        a:hover { text-decoration: underline; }
        
        /* Layout */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 1rem; }
        .header { background: white; border-bottom: 1px solid #e2e8f0; padding: 1rem 0; }
        .nav { display: flex; gap: 2rem; margin-top: 1rem; }
        .nav a { padding: 0.5rem 1rem; border-radius: 0.375rem; }
        .nav a.active { background: #edf2f7; font-weight: 600; }
        .main { min-height: calc(100vh - 200px); padding: 2rem 0; }
        .footer { background: #2d3748; color: white; padding: 2rem 0; margin-top: 4rem; }
        
        /* Components */
        .card { background: white; border-radius: 0.5rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 1.5rem; margin-bottom: 1.5rem; }
        .btn { display: inline-block; padding: 0.5rem 1rem; background: #3182ce; color: white; border-radius: 0.375rem; border: none; cursor: pointer; }
        .btn:hover { background: #2c5282; text-decoration: none; }
        .alert { padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem; }
        .alert-success { background: #c6f6d5; color: #22543d; }
        .alert-error { background: #fed7d7; color: #742a2a; }
        
        /* Tables */
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #f7fafc; font-weight: 600; }
        
        /* Forms */
        .form-group { margin-bottom: 1rem; }
        label { display: block; margin-bottom: 0.25rem; font-weight: 500; }
        input, select, textarea { width: 100%; padding: 0.5rem; border: 1px solid #e2e8f0; border-radius: 0.375rem; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #3182ce; box-shadow: 0 0 0 3px rgba(49, 130, 206, 0.1); }
        
        /* Responsive */
        @media (max-width: 768px) {
            .nav { flex-direction: column; gap: 0.5rem; }
            .hide-mobile { display: none; }
        }
        
        /* Print styles */
        @media print {
            .no-print { display: none !important; }
            .header, .footer { display: none; }
            body { background: white; }
        }
    </style>
    
    {{-- Additional CSS files --}}
    @foreach($enhancementAssets['css'] as $css)
        <link rel="stylesheet" href="{{ asset($css) }}">
    @endforeach
    
    @stack('styles')
</head>
<body>
    {{-- Skip to content link for accessibility --}}
    <a href="#main-content" class="sr-only">Skip to main content</a>
    
    {{-- Header --}}
    <header class="header no-print">
        <div class="container">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <h1 style="margin: 0; font-size: 1.5rem;">
                    <a href="{{ route('portal.dashboard') }}" style="color: inherit;">
                        {{ config('app.name') }} Portal
                    </a>
                </h1>
                
                {{-- User info --}}
                <div>
                    @auth
                        <span>{{ auth()->user()->name }}</span> |
                        <a href="{{ route('portal.settings') }}">Einstellungen</a> |
                        <form method="POST" action="{{ route('portal.logout') }}" style="display: inline;">
                            @csrf
                            <button type="submit" style="background: none; border: none; color: #3182ce; cursor: pointer; padding: 0;">
                                Abmelden
                            </button>
                        </form>
                    @endauth
                </div>
            </div>
            
            {{-- Navigation --}}
            <nav class="nav">
                <a href="{{ route('portal.dashboard') }}" class="{{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                    Dashboard
                </a>
                <a href="{{ route('portal.calls.index') }}" class="{{ request()->routeIs('portal.calls.*') ? 'active' : '' }}">
                    Anrufe
                </a>
                <a href="{{ route('portal.appointments.index') }}" class="{{ request()->routeIs('portal.appointments.*') ? 'active' : '' }}">
                    Termine
                </a>
                <a href="{{ route('portal.customers.index') }}" class="{{ request()->routeIs('portal.customers.*') ? 'active' : '' }}">
                    Kunden
                </a>
                <a href="{{ route('portal.team.index') }}" class="{{ request()->routeIs('portal.team.*') ? 'active' : '' }}">
                    Team
                </a>
                <a href="{{ route('portal.billing.index') }}" class="{{ request()->routeIs('portal.billing.*') ? 'active' : '' }}">
                    Abrechnung
                </a>
            </nav>
        </div>
    </header>
    
    {{-- Main content --}}
    <main id="main-content" class="main">
        <div class="container">
            {{-- Breadcrumbs --}}
            @if(isset($breadcrumbs))
                <nav aria-label="Breadcrumb" style="margin-bottom: 1rem;">
                    <ol style="display: flex; list-style: none; padding: 0; margin: 0;">
                        @foreach($breadcrumbs as $crumb)
                            <li style="display: flex; align-items: center;">
                                @if(!$loop->last)
                                    <a href="{{ $crumb['url'] }}">{{ $crumb['title'] }}</a>
                                    <span style="margin: 0 0.5rem;">/</span>
                                @else
                                    <span aria-current="page">{{ $crumb['title'] }}</span>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif
            
            {{-- Flash messages --}}
            @if(session('success'))
                <div class="alert alert-success" role="alert">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-error" role="alert">
                    {{ session('error') }}
                </div>
            @endif
            
            {{-- Page content --}}
            @yield('content')
        </div>
    </main>
    
    {{-- Footer --}}
    <footer class="footer no-print">
        <div class="container">
            <p style="margin: 0; text-align: center;">
                &copy; {{ date('Y') }} {{ config('app.name') }}. Alle Rechte vorbehalten.
            </p>
            <p style="margin: 0.5rem 0 0; text-align: center; font-size: 0.875rem; opacity: 0.8;">
                Version ohne JavaScript | 
                <a href="?enhancement_level=2" style="color: inherit;">Zur modernen Version wechseln</a>
            </p>
        </div>
    </footer>
    
    {{-- No JavaScript fallback messages --}}
    <noscript>
        <div style="position: fixed; bottom: 20px; right: 20px; background: #fef3c7; border: 1px solid #f59e0b; padding: 1rem; border-radius: 0.375rem; max-width: 300px;">
            <strong>JavaScript ist deaktiviert</strong><br>
            Für die beste Erfahrung aktivieren Sie bitte JavaScript in Ihrem Browser.
        </div>
    </noscript>
</body>
</html>