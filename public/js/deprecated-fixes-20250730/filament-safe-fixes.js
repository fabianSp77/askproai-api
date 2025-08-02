/**
 * Filament Safe Fixes
 * Provides compatibility fixes for Filament v3 components
 */

(function() {
    'use strict';
    
    // Fix for dropdown issues in Filament
    document.addEventListener('DOMContentLoaded', function() {
        // Prevent dropdown conflicts
        const fixDropdowns = () => {
            const dropdowns = document.querySelectorAll('[x-data*="dropdown"]');
            dropdowns.forEach(dropdown => {
                if (!dropdown.hasAttribute('data-fixed')) {
                    dropdown.setAttribute('data-fixed', 'true');
                }
            });
        };
        
        // Initial fix
        fixDropdowns();
        
        // Fix after Livewire updates
        if (window.Livewire) {
            window.Livewire.hook('message.processed', () => {
                setTimeout(fixDropdowns, 100);
            });
        }
    });
    
    // Fix for modal z-index issues
    if (window.Alpine) {
        document.addEventListener('alpine:init', () => {
            Alpine.directive('safe-modal', (el) => {
                el.style.zIndex = '9999';
            });
        });
    }
    
    console.log('Filament Safe Fixes v3.3.14.0 loaded');
})();