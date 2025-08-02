// Clean Livewire Fix - Prevents multiple instances
(function() {
    'use strict';
    
    console.log('[Clean Fix] Starting...');
    
    // Check if Livewire is already loaded or being loaded
    if (window.Livewire || window.livewireScriptLoading) {
        console.log('[Clean Fix] Livewire already exists or loading, skipping');
        return;
    }
    
    // Mark that we're loading
    window.livewireScriptLoading = true;
    
    // Wait for DOM
    function init() {
        // Check again to prevent race conditions
        if (window.Livewire) {
            console.log('[Clean Fix] Livewire appeared, skipping');
            return;
        }
        
        // Check if Livewire script tag already exists
        const existingScript = document.querySelector('script[src*="livewire.js"]');
        if (existingScript) {
            console.log('[Clean Fix] Livewire script tag exists, waiting...');
            return;
        }
        
        // Check if we're on an admin page that needs Livewire
        if (!window.location.pathname.startsWith('/admin')) {
            console.log('[Clean Fix] Not on admin page, skipping');
            return;
        }
        
        // Load Livewire ONCE
        console.log('[Clean Fix] Loading Livewire...');
        const script = document.createElement('script');
        script.src = '/vendor/livewire/livewire.js';
        script.async = true;
        script.onload = function() {
            console.log('[Clean Fix] Livewire loaded successfully');
            window.livewireScriptLoading = false;
            
            // Remove loading spinners after a delay
            setTimeout(function() {
                const spinners = document.querySelectorAll('.animate-spin, [wire\\:loading]');
                spinners.forEach(function(spinner) {
                    if (spinner.offsetParent !== null) {
                        spinner.style.display = 'none';
                    }
                });
                console.log('[Clean Fix] Removed loading spinners');
            }, 2000);
        };
        script.onerror = function() {
            console.error('[Clean Fix] Failed to load Livewire');
            window.livewireScriptLoading = false;
        };
        
        document.head.appendChild(script);
    }
    
    // Initialize when ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();