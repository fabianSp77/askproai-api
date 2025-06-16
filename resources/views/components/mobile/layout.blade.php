<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $deviceType ?? 'desktop' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="theme-color" content="#eab308">
    
    <title>{{ $title ?? 'AskProAI' }}</title>
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">
    
    <!-- Apple Touch Icons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/apple-touch-icon.png">
    
    <!-- Preconnect to external domains -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/css/mobile-framework.css'])
    @livewireStyles
    
    <!-- Mobile-specific styles -->
    <style>
        /* Prevent pull-to-refresh on Chrome Android */
        body {
            overscroll-behavior-y: contain;
        }
        
        /* iOS safe areas */
        .safe-areas {
            padding: env(safe-area-inset-top) env(safe-area-inset-right) env(safe-area-inset-bottom) env(safe-area-inset-left);
        }
        
        /* Disable text selection on UI elements */
        .no-select {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        /* Loading splash screen */
        .splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.3s ease-out;
        }
        
        .splash-screen.hide {
            opacity: 0;
            pointer-events: none;
        }
    </style>
    
    {{ $head ?? '' }}
</head>
<body class="bg-gray-50 text-gray-900 mobile-scroll-hidden {{ $bodyClass ?? '' }}">
    <!-- Splash Screen -->
    <div class="splash-screen" id="splash">
        <div class="text-center">
            <div class="spinner mb-4"></div>
            <p class="text-gray-600">Loading...</p>
        </div>
    </div>

    <!-- Mobile App Shell -->
    <div class="mobile-app-shell" id="app">
        <!-- Top Navigation Bar -->
        @if(!($hideNavigation ?? false))
        <header class="mobile-header fixed top-0 left-0 right-0 bg-white border-b border-gray-200 z-30 safe-top">
            <div class="flex items-center justify-between h-14 px-4">
                <!-- Left Action -->
                <div class="flex-shrink-0">
                    {{ $headerLeft ?? '' }}
                </div>
                
                <!-- Title -->
                <div class="flex-1 text-center">
                    <h1 class="text-lg font-semibold truncate">{{ $pageTitle ?? 'AskProAI' }}</h1>
                </div>
                
                <!-- Right Action -->
                <div class="flex-shrink-0">
                    {{ $headerRight ?? '' }}
                </div>
            </div>
        </header>
        @endif
        
        <!-- Main Content -->
        <main class="mobile-content {{ !($hideNavigation ?? false) ? 'pt-14' : '' }} {{ !($hideTabBar ?? false) ? 'pb-20' : '' }}">
            {{ $slot }}
        </main>
        
        <!-- Bottom Tab Bar -->
        @if(!($hideTabBar ?? false))
        <nav class="mobile-nav-bottom bg-white" id="tabBar">
            <div class="flex items-center justify-around">
                <a href="/mobile/dashboard" class="mobile-nav-item flex-1 {{ request()->is('mobile/dashboard*') ? 'text-amber-600' : 'text-gray-600' }}">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                    </svg>
                    <span class="text-xs">Home</span>
                </a>
                
                <a href="/mobile/appointments" class="mobile-nav-item flex-1 {{ request()->is('mobile/appointments*') ? 'text-amber-600' : 'text-gray-600' }}">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <span class="text-xs">Calendar</span>
                </a>
                
                <a href="/mobile/customers" class="mobile-nav-item flex-1 {{ request()->is('mobile/customers*') ? 'text-amber-600' : 'text-gray-600' }}">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span class="text-xs">Customers</span>
                </a>
                
                <a href="/mobile/profile" class="mobile-nav-item flex-1 {{ request()->is('mobile/profile*') ? 'text-amber-600' : 'text-gray-600' }}">
                    <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="text-xs">Profile</span>
                </a>
            </div>
        </nav>
        @endif
        
        <!-- Floating Action Button -->
        {{ $fab ?? '' }}
        
        <!-- Mobile Modals -->
        {{ $modals ?? '' }}
    </div>
    
    <!-- Pull to Refresh -->
    <div class="pull-to-refresh-indicator" id="refreshIndicator">
        <div class="spinner"></div>
    </div>
    
    @livewireScripts
    @vite('resources/js/app.js')
    
    <!-- Mobile-specific JavaScript -->
    <script>
        // Remove splash screen
        window.addEventListener('load', function() {
            setTimeout(() => {
                document.getElementById('splash').classList.add('hide');
            }, 300);
        });
        
        // Prevent zooming on input focus (iOS)
        document.addEventListener('touchstart', function(event) {
            if (event.touches.length > 1) {
                event.preventDefault();
            }
        });
        
        // Add active states to touch elements
        document.addEventListener('touchstart', function(e) {
            if (e.target.closest('.touch-ripple')) {
                e.target.closest('.touch-ripple').classList.add('touching');
            }
        });
        
        document.addEventListener('touchend', function(e) {
            if (e.target.closest('.touch-ripple')) {
                setTimeout(() => {
                    e.target.closest('.touch-ripple').classList.remove('touching');
                }, 300);
            }
        });
        
        // Simple pull to refresh implementation
        let startY = 0;
        let isPulling = false;
        
        document.addEventListener('touchstart', function(e) {
            if (window.scrollY === 0) {
                startY = e.touches[0].pageY;
            }
        });
        
        document.addEventListener('touchmove', function(e) {
            if (startY > 0) {
                const currentY = e.touches[0].pageY;
                const diff = currentY - startY;
                
                if (diff > 50 && !isPulling) {
                    isPulling = true;
                    document.getElementById('refreshIndicator').classList.add('visible');
                }
            }
        });
        
        document.addEventListener('touchend', function(e) {
            if (isPulling) {
                // Trigger refresh
                window.location.reload();
            }
            startY = 0;
            isPulling = false;
            document.getElementById('refreshIndicator').classList.remove('visible');
        });
        
        // Handle iOS status bar
        if (navigator.standalone) {
            document.documentElement.classList.add('ios-standalone');
        }
    </script>
    
    {{ $scripts ?? '' }}
</body>
</html>