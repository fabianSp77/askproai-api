/**
 * Comprehensive Alpine Dropdown Fix
 * Ensures all dropdown components have the necessary methods
 */

console.log('[Alpine Dropdown Comprehensive Fix] Loading...');

// Store original Alpine.data method
let originalAlpineData;

// Override Alpine.data to add dropdown methods to all components
document.addEventListener('alpine:init', () => {
    console.log('[Alpine Dropdown Fix] Intercepting Alpine.data...');
    
    originalAlpineData = Alpine.data;
    
    Alpine.data = function(name, callback) {
        // Wrap the original callback
        const wrappedCallback = function(...args) {
            const component = callback(...args);
            
            // Add dropdown methods if they don't exist
            if (!component.toggleDropdown) {
                component.toggleDropdown = function() {
                    // Check for various property names
                    if (this.showDropdown !== undefined) {
                        this.showDropdown = !this.showDropdown;
                    } else if (this.open !== undefined) {
                        this.open = !this.open;
                    } else if (this.showDateFilter !== undefined) {
                        this.showDateFilter = !this.showDateFilter;
                    } else {
                        // Create the property if it doesn't exist
                        this.showDropdown = !this.showDropdown;
                    }
                };
            }
            
            if (!component.closeDropdown) {
                component.closeDropdown = function() {
                    if (this.showDropdown !== undefined) {
                        this.showDropdown = false;
                    }
                    if (this.open !== undefined) {
                        this.open = false;
                    }
                    if (this.showDateFilter !== undefined) {
                        this.showDateFilter = false;
                    }
                };
            }
            
            if (!component.openDropdown) {
                component.openDropdown = function() {
                    if (this.showDropdown !== undefined) {
                        this.showDropdown = true;
                    }
                    if (this.open !== undefined) {
                        this.open = true;
                    }
                    if (this.showDateFilter !== undefined) {
                        this.showDateFilter = true;
                    }
                };
            }
            
            return component;
        };
        
        // Call original Alpine.data with wrapped callback
        return originalAlpineData.call(this, name, wrappedCallback);
    };
});

// Also ensure global functions are available
window.toggleDropdown = function() {
    if (this && (this.showDropdown !== undefined || this.open !== undefined || this.showDateFilter !== undefined)) {
        if (this.showDropdown !== undefined) {
            this.showDropdown = !this.showDropdown;
        } else if (this.open !== undefined) {
            this.open = !this.open;
        } else if (this.showDateFilter !== undefined) {
            this.showDateFilter = !this.showDateFilter;
        }
    }
};

window.closeDropdown = function() {
    if (this) {
        if (this.showDropdown !== undefined) this.showDropdown = false;
        if (this.open !== undefined) this.open = false;
        if (this.showDateFilter !== undefined) this.showDateFilter = false;
    }
};

window.openDropdown = function() {
    if (this) {
        if (this.showDropdown !== undefined) this.showDropdown = true;
        if (this.open !== undefined) this.open = true;
        if (this.showDateFilter !== undefined) this.showDateFilter = true;
    }
};

console.log('[Alpine Dropdown Comprehensive Fix] Ready');