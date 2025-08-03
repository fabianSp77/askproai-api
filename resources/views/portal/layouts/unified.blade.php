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

    <title>{{ config('app.name', 'AskProAI') }} - Business Portal</title>

    <!-- Tailwind CSS -->
    @if(app()->environment('local'))
        <script src="https://cdn.tailwindcss.com"></script>
    @else
        @vite(['resources/css/app.css'])
    @endif
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Alpine.js (Fixed version for stability) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.3/dist/cdn.min.js"></script>
    
    <!-- Chart.js (optional) -->
    @stack('scripts-head')
    
    <style>
        /* Custom portal styles */
        .sidebar-item {
            transition: all 0.2s ease;
            cursor: pointer;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        
        .sidebar-item:hover {
            background-color: #f3f4f6;
            transform: translateX(2px);
        }
        
        .sidebar-item:active {
            transform: scale(0.98);
        }
        
        .sidebar-item.active {
            background-color: #e5e7eb;
            border-left: 4px solid #3b82f6;
            font-weight: 600;
        }
        
        [x-cloak] { 
            display: none !important; 
        }
        
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f4f6;
            border-radius: 50%;
            border-top-color: #3b82f6;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .sidebar-mobile {
                position: fixed;
                top: 0;
                left: 0;
                height: 100vh;
                width: 16rem; /* 256px */
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
                z-index: 40;
                background: white;
            }
            
            .sidebar-mobile.open {
                transform: translateX(0);
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 30;
            }
        }
    </style>
    
    @stack('styles')
    
    <!-- Modern UI Enhancements -->
    <link href="/css/portal-modern-ui.css" rel="stylesheet">
    
    <!-- Final Click Fix - Load this LAST to override all other CSS -->
    <link href="/css/portal-click-fix-final.css" rel="stylesheet">
</head>
<body class="bg-gray-50" x-data="portalApp()">
    @if(!Auth::guard('portal')->check())
        <!-- Redirect to login if not authenticated -->
        <script>window.location.href = '/business/login';</script>
    @else
        <div class="flex h-screen overflow-hidden">
            <!-- Mobile Sidebar Overlay -->
            <div class="sidebar-overlay" 
                 x-show="sidebarOpen" 
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="sidebarOpen = false"
                 style="display: none;"></div>
            
            <!-- Sidebar -->
            <div class="w-64 bg-white shadow-lg flex-shrink-0 flex flex-col sidebar-mobile md:translate-x-0 md:static md:inset-0" :class="{'open': sidebarOpen}">
                <!-- Logo -->
                <div class="p-6 border-b">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center mr-3">
                                <i class="fas fa-robot text-white"></i>
                            </div>
                            <h1 class="text-xl font-bold text-gray-800">{{ config('app.name', 'AskProAI') }}</h1>
                        </div>
                        <!-- Close button for mobile -->
                        <button @click="sidebarOpen = false" class="md:hidden text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Navigation -->
                <nav class="flex-1 p-4 space-y-1 overflow-y-auto">
                    <a href="{{ route('business.dashboard') }}" 
                       @click="sidebarOpen = false"
                       class="sidebar-item {{ request()->routeIs('business.dashboard') ? 'active' : '' }} flex items-center p-3 rounded-lg">
                        <i class="fas fa-dashboard mr-3 text-gray-600 w-5"></i>
                        <span>Dashboard</span>
                    </a>
                    
                    <a href="{{ route('business.calls.index') }}" 
                       @click="sidebarOpen = false"
                       class="sidebar-item {{ request()->routeIs('business.calls.*') ? 'active' : '' }} flex items-center p-3 rounded-lg relative">
                        <i class="fas fa-phone mr-3 text-gray-600 w-5"></i>
                        <span>Anrufe</span>
                        @if(isset($newCallsCount) && $newCallsCount > 0)
                        <span class="ml-auto bg-blue-100 text-blue-600 px-2 py-1 rounded-full text-xs font-semibold">
                            {{ $newCallsCount }}
                        </span>
                        @endif
                    </a>
                    
                    <a href="{{ route('business.appointments.index') }}" 
                       @click="sidebarOpen = false"
                       class="sidebar-item {{ request()->routeIs('business.appointments.*') ? 'active' : '' }} flex items-center p-3 rounded-lg">
                        <i class="fas fa-calendar mr-3 text-gray-600 w-5"></i>
                        <span>Termine</span>
                    </a>
                    
                    <a href="{{ route('business.customers.index') }}" 
                       @click="sidebarOpen = false"
                       class="sidebar-item {{ request()->routeIs('business.customers.*') ? 'active' : '' }} flex items-center p-3 rounded-lg">
                        <i class="fas fa-users mr-3 text-gray-600 w-5"></i>
                        <span>Kunden</span>
                    </a>
                    
                    <a href="{{ route('business.team.index') }}" 
                       @click="sidebarOpen = false"
                       class="sidebar-item {{ request()->routeIs('business.team.*') ? 'active' : '' }} flex items-center p-3 rounded-lg">
                        <i class="fas fa-user-friends mr-3 text-gray-600 w-5"></i>
                        <span>Team</span>
                    </a>
                    
                    <a href="{{ route('business.analytics.index') }}" 
                       @click="sidebarOpen = false"
                       class="sidebar-item {{ request()->routeIs('business.analytics.*') ? 'active' : '' }} flex items-center p-3 rounded-lg">
                        <i class="fas fa-chart-bar mr-3 text-gray-600 w-5"></i>
                        <span>Analysen</span>
                    </a>
                    
                    <a href="{{ route('business.billing.index') }}" 
                       @click="sidebarOpen = false"
                       class="sidebar-item {{ request()->routeIs('business.billing.*') ? 'active' : '' }} flex items-center p-3 rounded-lg">
                        <i class="fas fa-credit-card mr-3 text-gray-600 w-5"></i>
                        <span>Abrechnung</span>
                    </a>
                    
                    <a href="{{ route('business.settings.index') }}" 
                       @click="sidebarOpen = false"
                       class="sidebar-item {{ request()->routeIs('business.settings.*') ? 'active' : '' }} flex items-center p-3 rounded-lg">
                        <i class="fas fa-cog mr-3 text-gray-600 w-5"></i>
                        <span>Einstellungen</span>
                    </a>
                </nav>
                
                <!-- User Info -->
                <div class="p-4 border-t bg-gray-50">
                    <div class="flex items-center">
                        <div class="w-8 h-8 bg-gray-300 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-gray-600 text-sm"></i>
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-700">{{ auth()->guard('portal')->user()->name }}</p>
                            <p class="text-xs text-gray-500">{{ auth()->guard('portal')->user()->email }}</p>
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

            <!-- Main Content Area -->
            <div class="flex-1 flex flex-col">
                <!-- Header -->
                <header class="bg-white shadow-sm border-b px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-2xl font-semibold text-gray-800">
                            @yield('page-title', 'Dashboard')
                        </h2>
                        <div class="flex items-center space-x-4">
                            <!-- Mobile menu button -->
                            <button @click="sidebarOpen = !sidebarOpen" class="md:hidden text-gray-600 hover:text-gray-800">
                                <i class="fas fa-bars text-xl"></i>
                            </button>
                            
                            @yield('header-actions')
                            
                            <!-- Notification Bell -->
                            <button class="relative p-2 text-gray-600 hover:text-gray-800" 
                                    x-data="{ hasNotifications: false }"
                                    @click="$dispatch('toggle-notifications')">
                                <i class="fas fa-bell"></i>
                                <span x-show="hasNotifications" class="absolute top-0 right-0 h-2 w-2 bg-red-500 rounded-full"></span>
                            </button>
                            
                            <!-- Quick Actions -->
                            @if(!request()->routeIs('business.appointments.create'))
                            <a href="{{ route('business.appointments.create') }}" 
                               class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition duration-200">
                                <i class="fas fa-plus mr-2"></i>
                                Neuer Termin
                            </a>
                            @endif
                        </div>
                    </div>
                </header>

                <!-- Main Content -->
                <main class="flex-1 overflow-y-auto">
                    @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 m-6 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('success') }}</span>
                    </div>
                    @endif
                    
                    @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 m-6 rounded relative" role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                    </div>
                    @endif
                    
                    @yield('content')
                </main>
            </div>
        </div>
    @endif

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

    <!-- CSRF Token Setup - Load FIRST -->
    <script src="/js/portal-csrf-setup.js"></script>
    
    <!-- Load API Client -->
    <script src="/js/business-portal-api-client.js"></script>
    
    <!-- Mobile Menu Handler -->
    <script src="/js/portal-mobile-menu.js"></script>
    
    <!-- Modern UI Enhancements -->
    <script src="/js/portal-enhancements.js"></script>
    
    <script>
        // Initialize Alpine.js portal app
        function portalApp() {
            return {
                sidebarOpen: false,
                user: @json(auth()->guard('portal')->user() ?? null),
                
                init() {
                    // Initialize API client (if available)
                    if (typeof BusinessPortalAPI !== 'undefined') {
                        window.apiClient = new BusinessPortalAPI({
                            baseURL: '/business/api',
                            debug: false
                        });
                    }
                    
                    // Check authentication - disabled as Laravel middleware handles this
                    // this.checkAuth();
                },
                
                async checkAuth() {
                    // Disabled - Laravel middleware handles authentication
                    return;
                },
                
                showToast(message, type = 'info') {
                    const toast = document.createElement('div');
                    const bgColor = {
                        success: 'bg-green-500',
                        error: 'bg-red-500',
                        warning: 'bg-yellow-500',
                        info: 'bg-blue-500'
                    }[type] || 'bg-gray-500';
                    
                    toast.className = `${bgColor} text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
                    toast.innerHTML = `
                        <div class="flex items-center">
                            <i class="fas fa-${type === 'success' ? 'check' : 'info'}-circle mr-2"></i>
                            <span>${message}</span>
                        </div>
                    `;
                    
                    const container = document.getElementById('toast-container');
                    container.appendChild(toast);
                    
                    // Animate in
                    setTimeout(() => {
                        toast.classList.remove('translate-x-full');
                    }, 100);
                    
                    // Remove after 3 seconds
                    setTimeout(() => {
                        toast.classList.add('translate-x-full');
                        setTimeout(() => toast.remove(), 300);
                    }, 3000);
                }
            }
        }
        
        // Global helper for showing toasts
        window.showToast = function(message, type) {
            const event = new CustomEvent('show-toast', { 
                detail: { message, type } 
            });
            window.dispatchEvent(event);
        };
        
        // Listen for toast events
        window.addEventListener('show-toast', (e) => {
            if (window.Alpine && window.Alpine.$data) {
                const app = document.querySelector('[x-data]').__x.$data;
                if (app && app.showToast) {
                    app.showToast(e.detail.message, e.detail.type);
                }
            }
        });
    </script>
    
    @stack('scripts')
    
    <!-- Alpine.js Debug Helper -->
    <script>
        // Log Alpine.js initialization status
        document.addEventListener('alpine:init', () => {
            console.log('[Alpine] ✅ Alpine.js initialized successfully');
        });
        
        // Check if Alpine loaded after DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                if (typeof Alpine === 'undefined') {
                    console.error('[Alpine] ❌ Alpine.js failed to load!');
                } else {
                    console.log('[Alpine] ✅ Alpine.js is available');
                }
            }, 500);
        });
    </script>
</body>
</html>