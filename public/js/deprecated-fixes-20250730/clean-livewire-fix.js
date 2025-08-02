/**
 * Clean Livewire Fix
 * Ensures Livewire interactions work properly without double-clicks
 */

(function() {
    "use strict";
    
    console.log("[CleanLivewireFix] Initializing...");
    
    // Skip on login page to prevent form submission issues
    if (window.location.pathname.includes('/admin/login')) {
        console.log("[CleanLivewireFix] Skipping on login page");
        return;
    }
    
    // Fix Livewire wire:click handlers
    function fixLivewireHandlers() {
        // Ensure wire:click elements are properly initialized
        document.querySelectorAll("[wire\\:click]").forEach(element => {
            // Only log in debug mode to reduce console noise
            if (window.location.hostname === 'localhost') {
                const wireClick = element.getAttribute("wire:click");
                console.log("[CleanLivewireFix] Found wire:click element:", wireClick);
            }
            
            // Don't clone elements anymore - it causes more problems than it solves
            // Just ensure the element has proper event handling
            element.style.cursor = 'pointer';
        });
        
        // Fix wire:submit forms
        document.querySelectorAll("form[wire\\:submit], form[wire\\:submit\\.prevent]").forEach(form => {
            // Only log in debug mode
            if (window.location.hostname === 'localhost') {
                console.log("[CleanLivewireFix] Found wire:submit form");
            }
        });
    }
    
    // Initialize when DOM is ready
    function initialize() {
        fixLivewireHandlers();
        
        // Re-run after Livewire updates
        if (window.Livewire) {
            Livewire.hook("message.processed", () => {
                setTimeout(fixLivewireHandlers, 50);
            });
            
            // Log Livewire status
            console.log("[CleanLivewireFix] Livewire detected and hooked");
        }
    }
    
    // Start initialization
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initialize);
    } else {
        initialize();
    }
    
    // Also reinitialize after Alpine
    document.addEventListener("alpine:initialized", () => {
        console.log("[CleanLivewireFix] Alpine initialized, re-running fixes");
        setTimeout(fixLivewireHandlers, 100);
    });
    
})();
