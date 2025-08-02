/**
 * Filament Safe Fixes
 * Minimal fixes that don't interfere with Filament's core functionality
 */

(function() {
    'use strict';
    
    // Only add autocomplete attributes, nothing else
    function addAutocompleteAttributes() {
        // Wait for Filament to fully initialize
        if (!window.Alpine || !document.querySelector('.fi-body')) {
            setTimeout(addAutocompleteAttributes, 100);
            return;
        }
        
        // Only fix search inputs in dropdowns
        const searchInputs = document.querySelectorAll('.fi-dropdown-panel input[type="search"]');
        searchInputs.forEach(input => {
            if (!input.hasAttribute('autocomplete')) {
                input.setAttribute('autocomplete', 'off');
            }
        });
        
        // Fix login form inputs
        const emailInputs = document.querySelectorAll('input[type="email"]');
        emailInputs.forEach(input => {
            if (!input.hasAttribute('autocomplete')) {
                input.setAttribute('autocomplete', 'username');
            }
        });
    }
    
    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', addAutocompleteAttributes);
    } else {
        addAutocompleteAttributes();
    }
    
    // Also run after Livewire updates
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            setTimeout(addAutocompleteAttributes, 100);
        });
    }
})();