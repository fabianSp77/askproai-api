<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @auth('portal')
    <meta name="user" content="{{ json_encode([
        'id' => auth()->guard('portal')->user()->id,
        'name' => auth()->guard('portal')->user()->name,
        'email' => auth()->guard('portal')->user()->email,
        'role' => auth()->guard('portal')->user()->role ?? 'user'
    ]) }}">
    @endauth

    <title>{{ config('app.name', 'AskProAI') }} - @yield('page-title', 'Business Portal')</title>

    {{-- Critical CSS (inline for fast loading) --}}
    <style>{!! file_get_contents(public_path('build/assets/critical.css')) !!}</style>
    
    {{-- Preload critical resources --}}
    <link rel="preload" href="/build/assets/portal.js" as="script">
    <link rel="preload" href="/build/assets/vendor-react.js" as="script">
    
    {{-- Main App Styles --}}
    @vite(['resources/css/app.css'])
    
    {{-- Font Awesome (load async) --}}
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    
    @stack('styles')
    
    <script>
        // Add loaded class when styles are ready
        document.addEventListener('DOMContentLoaded', function() {
            document.body.classList.add('loaded');
        });
    </script>
</head>
<body class="bg-gray-50 filament-body">
    @guest('portal')
        <script>window.location.href = '/business/login';</script>
    @else
        <div class="flex h-screen overflow-hidden">
            {{-- Sidebar --}}
            <div class="w-64 bg-white shadow-lg flex-shrink-0 flex flex-col">
                {{-- Logo --}}
                <div class="p-6 border-b">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                            <i class="fas fa-robot text-white"></i>
                        </div>
                        <h1 class="text-xl font-bold text-gray-800">{{ config('app.name', 'AskProAI') }}</h1>
                    </div>
                </div>
                
                {{-- Navigation --}}
                <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                    @php
                        $navItems = [
                            ['route' => 'business.dashboard', 'icon' => 'fa-dashboard', 'label' => 'Dashboard'],
                            ['route' => 'business.calls.index', 'icon' => 'fa-phone', 'label' => 'Anrufe'],
                            ['route' => 'business.appointments.index', 'icon' => 'fa-calendar', 'label' => 'Termine'],
                            ['route' => 'business.customers.index', 'icon' => 'fa-users', 'label' => 'Kunden'],
                            ['route' => 'business.team.index', 'icon' => 'fa-user-group', 'label' => 'Team'],
                            ['route' => 'business.analytics.index', 'icon' => 'fa-chart-line', 'label' => 'Analytics'],
                            ['route' => 'business.settings.index', 'icon' => 'fa-gear', 'label' => 'Einstellungen'],
                            ['route' => 'business.billing.index', 'icon' => 'fa-credit-card', 'label' => 'Abrechnung'],
                        ];
                    @endphp
                    
                    @foreach($navItems as $item)
                        <a href="{{ route($item['route']) }}" 
                           class="sidebar-item {{ request()->routeIs(str_replace('.index', '.*', $item['route'])) ? 'active' : '' }} flex items-center p-3 rounded-lg">
                            <i class="fas {{ $item['icon'] }} mr-3 text-gray-600 w-5"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
                
                {{-- User Menu --}}
                <div class="p-4 border-t">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-8 h-8 bg-gray-300 rounded-full mr-3"></div>
                            <div>
                                <p class="text-sm font-medium">{{ auth()->guard('portal')->user()->name }}</p>
                                <p class="text-xs text-gray-500">{{ auth()->guard('portal')->user()->email }}</p>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('business.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            {{-- Main Content --}}
            <div class="flex-1 flex flex-col overflow-hidden">
                {{-- Header --}}
                <header class="bg-white shadow-sm px-6 py-4">
                    <h2 class="text-2xl font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>
                </header>
                
                {{-- Content --}}
                <main class="flex-1 overflow-y-auto bg-gray-50 p-6">
                    @yield('content')
                </main>
            </div>
        </div>
    @endguest
    
    {{-- Load Portal Bundle --}}
    @vite(['resources/js/bundles/portal.jsx'])
    
    @stack('scripts')
    
    <style>
        /* Optimized sidebar styles */
        .sidebar-item {
            transition: all 0.2s ease;
        }
        
        .sidebar-item:hover {
            background-color: #f3f4f6;
            transform: translateX(2px);
        }
        
        .sidebar-item.active {
            background-color: #e5e7eb;
            border-left: 4px solid #3b82f6;
            font-weight: 600;
        }
    </style>
</body>
</html>