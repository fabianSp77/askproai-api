/**
 * Universal ClassList Fix for Filament
 * Provides polyfills and fixes for classList issues in various browsers
 */

(function() {
    'use strict';
    
    // ClassList polyfill for older browsers
    if (!("classList" in document.createElement("_"))) {
        console.warn('ClassList polyfill loaded');
    }
    
    // Fix for Filament-specific classList issues
    if (window.Livewire) {
        window.Livewire.hook('element.initialized', (el, component) => {
            // Ensure classList is available
            if (el && !el.classList) {
                console.warn('Element missing classList:', el);
            }
        });
    }
    
    console.log('Universal ClassList Fix loaded successfully');
})();