@php
    $navigationService = app(\App\Services\NavigationService::class);
    $navigation = $navigationService->getNavigation();
    $currentPath = request()->path();
@endphp

<!-- Mobile Bottom Navigation -->
<div class="fixed bottom-0 left-0 right-0 bg-white border-t border-gray-200 lg:hidden z-50" 
     style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="grid grid-cols-4 py-2">
        <!-- Dashboard -->
        <a href="/admin" 
           class="flex flex-col items-center justify-center p-2 text-xs transition-colors
                  {{ request()->is('admin') ? 'text-indigo-600' : 'text-gray-500' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6a2 2 0 01-2 2H10a2 2 0 01-2-2V5z" />
            </svg>
            <span class="font-medium">Dashboard</span>
        </a>
        
        <!-- Calls -->
        <a href="/admin/calls" 
           class="flex flex-col items-center justify-center p-2 text-xs transition-colors
                  {{ request()->is('admin/calls*') ? 'text-indigo-600' : 'text-gray-500' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            <span class="font-medium">Calls</span>
        </a>
        
        <!-- Customers -->
        <a href="/admin/customers" 
           class="flex flex-col items-center justify-center p-2 text-xs transition-colors
                  {{ request()->is('admin/customers*') ? 'text-indigo-600' : 'text-gray-500' }}">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0" />
            </svg>
            <span class="font-medium">Customers</span>
        </a>
        
        <!-- More -->
        <button type="button"
                onclick="window.stripeMenu?.toggleMobileMenu()" 
                class="flex flex-col items-center justify-center p-2 text-xs text-gray-500 transition-colors">
            <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            <span class="font-medium">More</span>
        </button>
    </div>
</div>

<!-- Add bottom padding to body content when bottom nav is present -->
<style>
    @media (max-width: 1023px) {
        body.filament-app {
            padding-bottom: 80px !important;
        }
        
        .fi-sidebar-nav {
            padding-bottom: 80px !important;
        }
        
        /* Add safe area support for devices with notches */
        .mobile-bottom-nav {
            padding-bottom: max(8px, env(safe-area-inset-bottom));
        }
    }
    
    /* Active state styling */
    .mobile-bottom-nav a.active {
        color: rgb(99 102 241);
    }
    
    /* Tap highlight removal */
    .mobile-bottom-nav a, .mobile-bottom-nav button {
        -webkit-tap-highlight-color: transparent;
    }
    
    /* Smooth transitions */
    .mobile-bottom-nav a, .mobile-bottom-nav button {
        transition: color 0.2s ease-in-out, background-color 0.2s ease-in-out;
    }
    
    /* Pressed state */
    .mobile-bottom-nav a:active, .mobile-bottom-nav button:active {
        background-color: rgb(245 245 245);
        border-radius: 8px;
    }
</style>

<!-- Enhanced Mobile Bottom Navigation with JS -->
<script>
    // Enhance mobile bottom navigation
    document.addEventListener('DOMContentLoaded', function() {
        const mobileNav = document.querySelector('[data-mobile-bottom-nav]');
        const links = document.querySelectorAll('.mobile-bottom-nav a');
        
        function updateActiveStates() {
            const currentPath = window.location.pathname;
            
            links.forEach(link => {
                const linkPath = new URL(link.href).pathname;
                const isActive = isActivePath(currentPath, linkPath);
                
                link.classList.toggle('active', isActive);
                
                if (isActive) {
                    link.style.color = 'rgb(99 102 241)';
                } else {
                    link.style.color = 'rgb(107 114 128)';
                }
            });
        }
        
        function isActivePath(current, link) {
            if (current === link) return true;
            if (current === '/admin' && link === '/admin') return true;
            if (link !== '/admin' && current.startsWith(link + '/')) return true;
            return false;
        }
        
        // Update on page load
        updateActiveStates();
        
        // Update on navigation (for SPA-style navigation)
        window.addEventListener('popstate', updateActiveStates);
        
        // Add haptic feedback on supported devices
        if ('vibrate' in navigator) {
            links.forEach(link => {
                link.addEventListener('touchstart', () => {
                    navigator.vibrate(10);
                }, { passive: true });
            });
        }
    });
</script>