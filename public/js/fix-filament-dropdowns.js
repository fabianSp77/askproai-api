// Fix for Filament dropdown issues with Alpine.js
(function() {
    'use strict';
    
    // Wait for Alpine and Filament to be ready
    function fixDropdowns() {
        if (typeof window.Alpine === 'undefined' || typeof window.Filament === 'undefined') {
            setTimeout(fixDropdowns, 50);
            return;
        }
        
        //console.log('Fixing Filament dropdowns...');
        
        // Fix for Livewire/Alpine dropdown conflicts
        document.addEventListener('livewire:navigated', () => {
            // Re-initialize Alpine components after Livewire navigation
            setTimeout(() => {
                const dropdowns = document.querySelectorAll('[x-data*="dropdown"], [x-data*="Dropdown"]');
                dropdowns.forEach(dropdown => {
                    if (!dropdown.hasAttribute('x-data-fixed')) {
                        try {
                            window.Alpine.initTree(dropdown);
                            dropdown.setAttribute('x-data-fixed', 'true');
                        } catch (e) {
                            console.error('Failed to reinitialize dropdown:', e);
                        }
                    }
                });
            }, 100);
        });
        
        // Override Filament dropdown behavior
        if (window.Filament && window.Filament.registerAlpineComponent) {
            // Register a fixed dropdown component
            window.Alpine.data('filamentDropdownFixed', () => ({
                open: false,
                toggle() {
                    this.open = !this.open;
                },
                close() {
                    this.open = false;
                },
                init() {
                    // Ensure dropdown closes when clicking outside
                    this.$watch('open', value => {
                        if (value) {
                            this.$nextTick(() => {
                                const clickHandler = (e) => {
                                    if (!this.$el.contains(e.target)) {
                                        this.open = false;
                                        document.removeEventListener('click', clickHandler);
                                    }
                                };
                                document.addEventListener('click', clickHandler);
                            });
                        }
                    });
                }
            }));
        }
        
        // Fix z-index stacking issues
        const style = document.createElement('style');
        style.textContent = `
            /* Fix dropdown z-index issues */
            .fi-dropdown-panel,
            [x-ref="panel"],
            [role="menu"] {
                z-index: 999999 !important;
                position: absolute !important;
            }
            
            /* Fix dropdown visibility */
            [x-show][x-transition] {
                will-change: transform, opacity;
            }
            
            /* Ensure dropdowns appear above modals */
            .fi-modal-window + .fi-dropdown-panel {
                z-index: 9999999 !important;
            }
            
            /* Fix dropdown positioning */
            [x-anchor] {
                position: relative !important;
            }
        `;
        document.head.appendChild(style);
        
        //console.log('Filament dropdown fixes applied');
    }
    
    // Start fixing process
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixDropdowns);
    } else {
        fixDropdowns();
    }
    
    // Global function to manually fix a specific dropdown
    window.fixFilamentDropdown = function(selector) {
        const element = document.querySelector(selector);
        if (element && window.Alpine) {
            try {
                window.Alpine.initTree(element);
                //console.log('Fixed dropdown:', selector);
                return true;
            } catch (e) {
                console.error('Failed to fix dropdown:', e);
                return false;
            }
        }
        return false;
    };
})();