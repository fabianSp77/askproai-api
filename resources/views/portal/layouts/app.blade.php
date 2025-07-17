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
    
    @auth('web')
    @if(!auth()->guard('portal')->check())
    <meta name="user" content="{{ json_encode([
        'id' => auth()->user()->id,
        'name' => auth()->user()->name,
        'email' => auth()->user()->email,
        'role' => 'admin'
    ]) }}">
    @endif
    @endauth

    <title>{{ config('app.name', 'AskProAI') }} - Business Portal</title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="icon" type="image/png" href="/favicon-32x32.png" sizes="32x32">
    <link rel="icon" type="image/png" href="/favicon-16x16.png" sizes="16x16">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/manifest.json">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#667eea">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="AskProAI">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    {{-- Admin banner removed - using React portal now --}}
    
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="shrink-0 flex items-center">
                            <a href="{{ route('business.dashboard') }}" class="text-xl font-semibold">
                                @if(Auth::guard('portal')->check())
                                    {{ Auth::guard('portal')->user()->company->name }} Portal
                                @elseif(session('admin_viewing_company'))
                                    {{ session('admin_viewing_company') }} Portal
                                @else
                                    Business Portal
                                @endif
                            </a>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                            <x-nav-link :href="route('business.dashboard')" :active="request()->routeIs('business.dashboard')">
                                Dashboard
                            </x-nav-link>
                            <x-nav-link :href="route('business.calls.index')" :active="request()->routeIs('business.calls.*')">
                                Anrufe
                            </x-nav-link>
                            @if(session('is_admin_viewing') || (Auth::guard('portal')->check() && Auth::guard('portal')->user()->company && method_exists(Auth::guard('portal')->user()->company, 'hasModule') && Auth::guard('portal')->user()->company->hasModule('appointments')))
                                <x-nav-link :href="route('business.appointments.index')" :active="request()->routeIs('business.appointments.*')">
                                    Termine
                                </x-nav-link>
                            @endif
                            @if(session('is_admin_viewing') || (Auth::guard('portal')->check() && Auth::guard('portal')->user()->hasPermission('billing.view')))
                                <x-nav-link :href="route('business.billing.index')" :active="request()->routeIs('business.billing.*')">
                                    Abrechnung
                                </x-nav-link>
                            @endif
                            @if(session('is_admin_viewing') || (Auth::guard('portal')->check() && Auth::guard('portal')->user()->hasPermission('analytics.view_team')))
                                <x-nav-link :href="route('business.analytics.index')" :active="request()->routeIs('business.analytics.*')">
                                    Analysen
                                </x-nav-link>
                            @endif
                            @if(session('is_admin_viewing') || (Auth::guard('portal')->check() && Auth::guard('portal')->user()->hasPermission('team.view')))
                                <x-nav-link :href="route('business.team.index')" :active="request()->routeIs('business.team.*')">
                                    Team
                                </x-nav-link>
                            @endif
                        </div>
                    </div>

                    <!-- Settings Dropdown -->
                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                    <div>
                                        @if(Auth::guard('portal')->check())
                                            {{ Auth::guard('portal')->user()->name }}
                                        @elseif(session('is_admin_viewing'))
                                            Admin Access
                                        @else
                                            Guest
                                        @endif
                                    </div>
                                    <div class="ml-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                @if(!session('is_admin_viewing'))
                                    <x-dropdown-link :href="route('business.settings.index')">
                                        Einstellungen
                                    </x-dropdown-link>
                                @endif

                                @if(session('is_admin_viewing'))
                                    <!-- Admin Exit -->
                                    <x-dropdown-link :href="route('business.admin.exit')">
                                        Admin-Zugriff beenden
                                    </x-dropdown-link>
                                @elseif(Auth::guard('portal')->check())
                                    <!-- Logout -->
                                    <form method="POST" action="{{ route('business.logout') }}">
                                        @csrf
                                        <x-dropdown-link :href="route('business.logout')"
                                                onclick="event.preventDefault();
                                                            this.closest('form').submit();">
                                            Abmelden
                                        </x-dropdown-link>
                                    </form>
                                @endif
                            </x-slot>
                        </x-dropdown>
                    </div>

                    <!-- Hamburger -->
                    <div class="-mr-2 flex items-center sm:hidden">
                        <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                            <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Responsive Navigation Menu -->
            <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <x-responsive-nav-link :href="route('business.dashboard')" :active="request()->routeIs('business.dashboard')">
                        Dashboard
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('business.calls.index')" :active="request()->routeIs('business.calls.*')">
                        Anrufe
                    </x-responsive-nav-link>
                </div>

                <!-- Responsive Settings Options -->
                <div class="pt-4 pb-1 border-t border-gray-200">
                    <div class="px-4">
                        @if(session('is_admin_viewing'))
                            <div class="font-medium text-base text-gray-800">Admin Zugriff</div>
                            <div class="font-medium text-sm text-gray-500">{{ session('admin_viewing_company') }}</div>
                        @elseif(Auth::guard('portal')->check())
                            <div class="font-medium text-base text-gray-800">{{ Auth::guard('portal')->user()->name }}</div>
                            <div class="font-medium text-sm text-gray-500">{{ Auth::guard('portal')->user()->email }}</div>
                        @endif
                    </div>

                    <div class="mt-3 space-y-1">
                        @if(!session('is_admin_viewing'))
                            <x-responsive-nav-link :href="route('business.settings.index')">
                                Einstellungen
                            </x-responsive-nav-link>
                        @endif

                        @if(session('is_admin_viewing'))
                            <!-- Admin Exit -->
                            <x-responsive-nav-link :href="route('business.admin.exit')">
                                Admin-Zugriff beenden
                            </x-responsive-nav-link>
                        @elseif(Auth::guard('portal')->check())
                            <!-- Logout -->
                            <form method="POST" action="{{ route('business.logout') }}">
                                @csrf
                                <x-responsive-nav-link :href="route('business.logout')"
                                        onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    Abmelden
                                </x-responsive-nav-link>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Heading -->
        @if (isset($header))
            <header class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endif

        <!-- Page Content -->
        <main>
            @if (session('success'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    
    {{-- Alpine.js Initialization Helper --}}
    <script>
        // Ensure Alpine components are properly initialized
        document.addEventListener('alpine:init', () => {
            console.log('Alpine initializing in Business Portal...');
        });
        
        // Helper function to reinitialize Alpine components after AJAX
        window.reinitializeAlpine = function(container = document.body) {
            if (window.Alpine) {
                const components = container.querySelectorAll('[x-data]:not([x-data-initialized])');
                components.forEach(el => {
                    try {
                        window.Alpine.initTree(el);
                        el.setAttribute('x-data-initialized', 'true');
                    } catch (e) {
                        console.warn('Failed to initialize Alpine component:', e);
                    }
                });
            }
        };
        
        // Global error handler for Alpine
        window.addEventListener('alpine:expression-error', (event) => {
            console.error('Alpine Expression Error:', event.detail);
            // Try to recover
            setTimeout(() => {
                if (window.PortalAlpineStabilizer) {
                    window.PortalAlpineStabilizer.fixComponent(event.detail.el);
                }
            }, 100);
        });
    </script>
    
    {{-- Include Help Widget --}}
    @include('components.help-widget')
    
    @stack('scripts')
</body>
</html>