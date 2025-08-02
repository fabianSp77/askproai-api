/**
 * Immediate Alpine.js Dropdown Fix
 * This MUST run before Alpine initializes to prevent errors
 */

// Wait for Alpine to be available
const waitForAlpine = setInterval(() => {
    if (window.Alpine) {
        clearInterval(waitForAlpine);
        console.log('[Alpine Dropdown Fix] Alpine detected, applying fixes...');
        
        // Hook into Alpine before it starts
        if (!Alpine.started) {
            // Register dropdown data before Alpine processes any components
            Alpine.data('dropdown', () => ({
                open: false,
                toggleDropdown() { 
                    console.log('[Dropdown] Toggling:', !this.open);
                    this.open = !this.open; 
                },
                closeDropdown() { 
                    console.log('[Dropdown] Closing');
                    this.open = false; 
                },
                openDropdown() { 
                    console.log('[Dropdown] Opening');
                    this.open = true; 
                }
            }));
            
            // Also add magic helper
            Alpine.magic('dropdown', () => ({
                close: () => {
                    document.querySelectorAll('[x-data*="open"]').forEach(el => {
                        const component = Alpine.$data(el);
                        if (component && typeof component.open !== 'undefined') {
                            component.open = false;
                        }
                    });
                }
            }));
            
            console.log('[Alpine Dropdown Fix] Dropdown functions registered');
        }
    }
}, 10);

// Fallback: Create global functions for templates that use them directly
window.closeDropdown = function() {
    console.log('[Global] closeDropdown called');
    // If called without context, close all dropdowns
    if (window.Alpine && window.Alpine.$data) {
        document.querySelectorAll('[x-data*="open"]').forEach(el => {
            const component = Alpine.$data(el);
            if (component && typeof component.open !== 'undefined') {
                component.open = false;
            }
        });
    }
    return false; // Prevent default action
};

window.toggleDropdown = function() {
    console.log('[Global] toggleDropdown called');
    return false; // Prevent default action
};

// Ensure functions exist in Alpine's evaluation context
document.addEventListener('alpine:init', () => {
    console.log('[Alpine Dropdown Fix] Alpine init event fired');
    
    // Double-check that our data is registered
    if (!Alpine.data('dropdown')) {
        Alpine.data('dropdown', () => ({
            open: false,
            toggleDropdown() { this.open = !this.open; },
            closeDropdown() { this.open = false; },
            openDropdown() { this.open = true; }
        }));
    }
});

console.log('[Alpine Dropdown Fix] Script loaded and ready');