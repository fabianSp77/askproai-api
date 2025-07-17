// Alpine.js Dropdown Enhancement
// Fixes dropdown close behavior after emergency CSS overrides

document.addEventListener('alpine:init', () => {
    console.log('Alpine Dropdown Enhancement Loading...');
    
    // Enhanced dropdown component
    Alpine.data('enhancedDropdown', () => ({
        open: false,
        
        init() {
            // Ensure initial state is applied
            this.$nextTick(() => {
                if (!this.open) {
                    this.$el.querySelector('.fi-dropdown-panel')?.setAttribute('style', 'display: none !important');
                }
            });
        },
        
        toggle() {
            this.open = !this.open;
            this.updateDisplay();
        },
        
        close() {
            this.open = false;
            this.updateDisplay();
        },
        
        updateDisplay() {
            this.$nextTick(() => {
                const panel = this.$el.querySelector('.fi-dropdown-panel');
                if (panel) {
                    if (this.open) {
                        panel.style.display = 'block';
                        panel.style.visibility = 'visible';
                    } else {
                        panel.style.display = 'none';
                        panel.style.visibility = 'hidden';
                    }
                }
            });
        }
    }));
    
    // Override default Filament dropdown behavior
    const originalData = Alpine.data;
    Alpine.data = function(name, callback) {
        if (name === 'dropdown') {
            return originalData.call(this, name, () => {
                const original = callback();
                return {
                    ...original,
                    open: false,
                    
                    init() {
                        if (original.init) original.init.call(this);
                        
                        // Force close on init if not open
                        this.$nextTick(() => {
                            if (!this.open) {
                                const panel = this.$refs.panel || this.$el.querySelector('.fi-dropdown-panel');
                                if (panel) {
                                    panel.style.display = 'none';
                                }
                            }
                        });
                        
                        // Add click outside listener
                        const clickHandler = (e) => {
                            if (!this.$el.contains(e.target)) {
                                this.open = false;
                            }
                        };
                        
                        document.addEventListener('click', clickHandler);
                        
                        // Cleanup
                        this.$cleanup(() => {
                            document.removeEventListener('click', clickHandler);
                        });
                    },
                    
                    toggle() {
                        this.open = !this.open;
                        if (original.toggle) original.toggle.call(this);
                    },
                    
                    close() {
                        this.open = false;
                        if (original.close) original.close.call(this);
                    }
                };
            });
        }
        return originalData.call(this, name, callback);
    };
    
    // Global click handler for dropdowns
    document.addEventListener('click', (e) => {
        // Handle Filament action dropdowns
        if (e.target.closest('.fi-dropdown-trigger')) {
            return; // Let Alpine handle the toggle
        }
        
        // Close all open dropdowns when clicking outside
        document.querySelectorAll('[x-data].fi-dropdown').forEach(dropdown => {
            if (!dropdown.contains(e.target)) {
                const component = Alpine.$data(dropdown);
                if (component && component.open) {
                    component.open = false;
                }
            }
        });
    });
});

// Fix dropdowns after Livewire updates
document.addEventListener('livewire:navigated', () => {
    setTimeout(() => {
        fixAllDropdowns();
    }, 100);
});

// Fix dropdowns on page load
document.addEventListener('DOMContentLoaded', () => {
    setTimeout(() => {
        fixAllDropdowns();
    }, 500);
});

function fixAllDropdowns() {
    console.log('Fixing all dropdowns...');
    
    // Find all dropdown panels that should be hidden
    document.querySelectorAll('.fi-dropdown-panel').forEach(panel => {
        const dropdown = panel.closest('[x-data]');
        if (dropdown) {
            const alpineData = Alpine.$data(dropdown);
            if (alpineData && alpineData.open === false) {
                panel.style.display = 'none';
                panel.style.visibility = 'hidden';
            }
        } else {
            // No Alpine data, hide by default
            if (!panel.classList.contains('fi-dropdown-open')) {
                panel.style.display = 'none';
            }
        }
    });
    
    // Fix x-show attributes
    document.querySelectorAll('[x-show]').forEach(el => {
        const showExpression = el.getAttribute('x-show');
        if (showExpression === 'false' || showExpression === '!open') {
            el.style.display = 'none';
        }
    });
}