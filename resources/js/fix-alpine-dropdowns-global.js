/**
 * Global Alpine.js Dropdown Fix
 * Ensures dropdown functions are available globally
 */

// Make functions globally available immediately
window.toggleDropdown = function() {
    console.log('[Global] toggleDropdown called');
    if (this && typeof this.open !== 'undefined') {
        this.open = !this.open;
    }
    return false;
};

window.closeDropdown = function() {
    console.log('[Global] closeDropdown called');
    if (this && typeof this.open !== 'undefined') {
        this.open = false;
    }
    return false;
};

window.openDropdown = function() {
    console.log('[Global] openDropdown called');
    if (this && typeof this.open !== 'undefined') {
        this.open = true;
    }
    return false;
};

// Wait for Alpine to be ready
document.addEventListener('alpine:init', () => {
    console.log('[Alpine Global Fix] Registering dropdown data...');
    
    // Register dropdown data component
    Alpine.data('dropdown', () => ({
        open: false,
        
        init() {
            console.log('[Dropdown] Component initialized');
            // Bind methods to ensure proper context
            this.toggleDropdown = this.toggleDropdown.bind(this);
            this.closeDropdown = this.closeDropdown.bind(this);
            this.openDropdown = this.openDropdown.bind(this);
        },
        
        toggleDropdown() {
            console.log('[Dropdown] Toggle:', !this.open);
            this.open = !this.open;
        },
        
        closeDropdown() {
            console.log('[Dropdown] Close');
            this.open = false;
        },
        
        openDropdown() {
            console.log('[Dropdown] Open');
            this.open = true;
        }
    }));
    
    // Also register a magic helper
    Alpine.magic('closeAllDropdowns', () => {
        return () => {
            document.querySelectorAll('[x-data]').forEach(el => {
                const component = Alpine.$data(el);
                if (component && typeof component.open !== 'undefined') {
                    component.open = false;
                }
            });
        };
    });
});

// Fix existing components after Alpine starts
document.addEventListener('alpine:initialized', () => {
    console.log('[Alpine Global Fix] Fixing existing components...');
    
    // Find all Alpine components with dropdown functionality
    document.querySelectorAll('[x-data]').forEach(el => {
        try {
            const component = Alpine.$data(el);
            if (component && typeof component.open !== 'undefined') {
                // Ensure methods exist
                if (!component.toggleDropdown) {
                    component.toggleDropdown = function() {
                        this.open = !this.open;
                    }.bind(component);
                }
                if (!component.closeDropdown) {
                    component.closeDropdown = function() {
                        this.open = false;
                    }.bind(component);
                }
                if (!component.openDropdown) {
                    component.openDropdown = function() {
                        this.open = true;
                    }.bind(component);
                }
                console.log('[Alpine Global Fix] Fixed component:', el);
            }
        } catch (e) {
            console.warn('[Alpine Global Fix] Could not fix component:', e);
        }
    });
});

// Also handle Livewire updates
if (window.Livewire) {
    Livewire.hook('element.initialized', (el, component) => {
        if (el.hasAttribute('x-data') && el.getAttribute('x-data').includes('open')) {
            console.log('[Livewire Hook] Ensuring dropdown functions on:', el);
            // Re-apply fixes after Livewire updates
            setTimeout(() => {
                const alpineComponent = Alpine.$data(el);
                if (alpineComponent && !alpineComponent.closeDropdown) {
                    alpineComponent.toggleDropdown = function() { this.open = !this.open; };
                    alpineComponent.closeDropdown = function() { this.open = false; };
                    alpineComponent.openDropdown = function() { this.open = true; };
                }
            }, 100);
        }
    });
}

console.log('[Alpine Global Fix] Script loaded');