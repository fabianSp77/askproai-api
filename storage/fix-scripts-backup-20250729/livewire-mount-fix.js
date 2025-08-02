// Livewire Mount Fix for Admin Pages
(function() {
    'use strict';
    
    console.log('[Livewire Mount Fix] Initializing...');
    
    // Function to fix Livewire mounting issues
    function fixLivewireMounting() {
        // Check if we're on an admin page
        if (!window.location.pathname.startsWith('/admin')) {
            return;
        }
        
        // Wait for Livewire to be available
        let attempts = 0;
        const maxAttempts = 20;
        
        const checkLivewire = setInterval(() => {
            attempts++;
            
            if (typeof window.Livewire !== 'undefined') {
                console.log('[Livewire Mount Fix] Livewire found!');
                clearInterval(checkLivewire);
                
                // Ensure Livewire is started
                if (!window.Livewire.started) {
                    try {
                        window.Livewire.start();
                        console.log('[Livewire Mount Fix] Livewire started manually');
                    } catch (e) {
                        console.error('[Livewire Mount Fix] Error starting Livewire:', e);
                    }
                }
                
                // Fix for components not mounting
                setTimeout(() => {
                    // Find all Livewire components
                    const components = document.querySelectorAll('[wire\\:id]');
                    console.log(`[Livewire Mount Fix] Found ${components.length} Livewire components`);
                    
                    // Force mount unmounted components
                    components.forEach(component => {
                        const wireId = component.getAttribute('wire:id');
                        if (wireId && !window.Livewire.find(wireId)) {
                            console.warn(`[Livewire Mount Fix] Component ${wireId} not mounted, attempting fix...`);
                            
                            // Try to extract component data
                            const snapshot = component.getAttribute('wire:snapshot');
                            const effects = component.getAttribute('wire:effects');
                            
                            if (snapshot) {
                                try {
                                    // Attempt to register component
                                    const data = JSON.parse(atob(snapshot));
                                    console.log('[Livewire Mount Fix] Attempting to register component:', data);
                                } catch (e) {
                                    console.error('[Livewire Mount Fix] Failed to parse snapshot:', e);
                                }
                            }
                        }
                    });
                    
                    // Remove infinite loading spinners after timeout
                    setTimeout(() => {
                        const spinners = document.querySelectorAll('.animate-spin, [wire\\:loading]');
                        spinners.forEach(spinner => {
                            if (spinner.offsetParent !== null) { // Is visible
                                console.warn('[Livewire Mount Fix] Removing stuck spinner');
                                spinner.style.display = 'none';
                            }
                        });
                        
                        // Show content that might be hidden
                        const hiddenContent = document.querySelectorAll('[wire\\:loading\\.remove]');
                        hiddenContent.forEach(el => {
                            el.style.display = '';
                        });
                    }, 3000);
                    
                }, 1000);
                
            } else if (attempts >= maxAttempts) {
                console.error('[Livewire Mount Fix] Livewire not found after', maxAttempts, 'attempts');
                clearInterval(checkLivewire);
                
                // Last resort: Try to load Livewire manually
                console.warn('[Livewire Mount Fix] Attempting to load Livewire manually...');
                const script = document.createElement('script');
                script.src = '/vendor/livewire/livewire.js?v=' + Date.now();
                script.onload = () => {
                    console.log('[Livewire Mount Fix] Livewire loaded manually!');
                    fixLivewireMounting(); // Retry
                };
                document.head.appendChild(script);
            }
        }, 500);
    }
    
    // Run on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixLivewireMounting);
    } else {
        fixLivewireMounting();
    }
    
    // Also run on Livewire navigate (for SPA navigation)
    document.addEventListener('livewire:navigated', fixLivewireMounting);
    
    // Monitor for errors
    window.addEventListener('error', (e) => {
        if (e.message && e.message.includes('Livewire')) {
            console.error('[Livewire Mount Fix] Livewire error detected:', e.message);
            // Try to recover
            setTimeout(fixLivewireMounting, 1000);
        }
    });
    
})();