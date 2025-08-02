/**
 * Fix for missing closeDropdown function in Alpine.js components
 */

document.addEventListener('alpine:init', () => {
    console.log('[Dropdown Functions Fix] Initializing...');
    
    // Global Alpine data for dropdowns
    Alpine.data('dropdown', () => ({
        open: false,
        
        toggleDropdown() {
            this.open = !this.open;
        },
        
        closeDropdown() {
            this.open = false;
        },
        
        openDropdown() {
            this.open = true;
        }
    }));
    
    // Also add as global Alpine store for components that might use it differently
    Alpine.store('dropdownFunctions', {
        closeAllDropdowns() {
            // Find all dropdown components and close them
            document.querySelectorAll('[x-data*="open"]').forEach(el => {
                if (el._x_dataStack && el._x_dataStack[0]) {
                    el._x_dataStack[0].open = false;
                }
            });
        }
    });
    
    // Fix existing dropdowns that might be missing the functions
    document.addEventListener('alpine:initialized', () => {
        console.log('[Dropdown Functions Fix] Fixing existing dropdowns...');
        
        document.querySelectorAll('[x-data]').forEach(el => {
            const component = Alpine.$data(el);
            if (component && typeof component.open !== 'undefined') {
                // Add missing functions if they don't exist
                if (!component.toggleDropdown) {
                    component.toggleDropdown = function() {
                        this.open = !this.open;
                    };
                }
                if (!component.closeDropdown) {
                    component.closeDropdown = function() {
                        this.open = false;
                    };
                }
                if (!component.openDropdown) {
                    component.openDropdown = function() {
                        this.open = true;
                    };
                }
            }
        });
        
        console.log('[Dropdown Functions Fix] Fixed existing dropdowns');
    });
});

// Also ensure functions are available globally for inline Alpine usage
window.alpineDropdownHelpers = {
    closeDropdown(el) {
        const component = Alpine.$data(el);
        if (component && typeof component.open !== 'undefined') {
            component.open = false;
        }
    },
    toggleDropdown(el) {
        const component = Alpine.$data(el);
        if (component && typeof component.open !== 'undefined') {
            component.open = !component.open;
        }
    }
};

console.log('[Dropdown Functions Fix] Script loaded');