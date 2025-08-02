/**
 * Simple Dropdown Fix for Filament
 * Works with inline Alpine.js expressions
 */

console.log('[Simple Dropdown Fix] Loading...');

// Define functions in the global scope that Alpine can use
window.toggleDropdown = function() {
    // When called from Alpine, 'this' is the component context
    if (this && this.open !== undefined) {
        this.open = !this.open;
        console.log('[Dropdown] Toggled to:', this.open);
    }
};

window.closeDropdown = function() {
    // When called from Alpine, 'this' is the component context
    if (this && this.open !== undefined) {
        this.open = false;
        console.log('[Dropdown] Closed');
    }
};

window.openDropdown = function() {
    // When called from Alpine, 'this' is the component context
    if (this && this.open !== undefined) {
        this.open = true;
        console.log('[Dropdown] Opened');
    }
};

// Wait for Alpine to be available
document.addEventListener('DOMContentLoaded', () => {
    // Check if Alpine is loaded
    if (window.Alpine) {
        console.log('[Simple Dropdown Fix] Alpine detected, registering data...');
        
        // If Alpine hasn't started yet, register our data
        if (!Alpine.started) {
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
        }
    }
});

// Also listen for Alpine init event
document.addEventListener('alpine:init', () => {
    console.log('[Simple Dropdown Fix] Alpine initializing...');
    
    // Ensure dropdown data is registered
    Alpine.data('simpleDropdown', () => ({
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        },
        open() {
            this.open = true;
        }
    }));
});

console.log('[Simple Dropdown Fix] Ready');