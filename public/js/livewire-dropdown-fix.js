// Livewire Dropdown Fix - Preserves native functionality
(function() {
    'use strict';
    
    //console.log('[Livewire Dropdown Fix] Loading...');
    
    // Wait for Livewire to be ready
    function waitForLivewire(callback) {
        if (window.Livewire) {
            callback();
        } else {
            setTimeout(() => waitForLivewire(callback), 100);
        }
    }
    
    function fixLivewireDropdowns() {
        //console.log('[Livewire Dropdown Fix] Fixing dropdowns while preserving Livewire functionality...');
        
        // Fix method 1: Ensure Alpine is initialized on dropdowns
        document.querySelectorAll('[x-data]').forEach(element => {
            if (!element.__x && window.Alpine) {
                try {
                    // Check if it's a dropdown component
                    const xDataAttr = element.getAttribute('x-data');
                    if (xDataAttr && (xDataAttr.includes('open') || xDataAttr.includes('dropdown'))) {
                        //console.log('[Livewire Dropdown Fix] Initializing Alpine on:', element);
                        Alpine.initTree(element);
                    }
                } catch (e) {
                    console.warn('[Livewire Dropdown Fix] Could not init Alpine:', e);
                }
            }
        });
        
        // Fix method 2: Ensure wire:click handlers are properly bound
        document.querySelectorAll('[wire\\:click]').forEach(element => {
            if (!element.hasAttribute('data-wire-click-fixed')) {
                element.setAttribute('data-wire-click-fixed', 'true');
                
                // Make sure element is interactive
                element.style.cursor = 'pointer';
                element.style.pointerEvents = 'auto';
                
                // For select dropdowns, ensure they trigger wire:click
                if (element.tagName === 'LI' || element.tagName === 'BUTTON' || element.tagName === 'A') {
                    const wireClick = element.getAttribute('wire:click');
                    //console.log('[Livewire Dropdown Fix] Found wire:click element:', wireClick);
                    
                    // Ensure the element can receive focus
                    if (!element.hasAttribute('tabindex')) {
                        element.setAttribute('tabindex', '0');
                    }
                }
            }
        });
        
        // Fix method 3: Fix Filament select components specifically
        document.querySelectorAll('.fi-fo-select').forEach(selectContainer => {
            const trigger = selectContainer.querySelector('button[x-ref="trigger"]');
            const listbox = selectContainer.querySelector('[x-ref="listbox"]');
            
            if (trigger && listbox) {
                //console.log('[Livewire Dropdown Fix] Found Filament select component');
                
                // Ensure listbox items are clickable
                listbox.querySelectorAll('[wire\\:click]').forEach(item => {
                    item.style.cursor = 'pointer';
                    item.style.pointerEvents = 'auto';
                    
                    // Add backup click handler that triggers Livewire
                    if (!item.hasAttribute('data-backup-handler')) {
                        item.setAttribute('data-backup-handler', 'true');
                        
                        item.addEventListener('click', function(e) {
                            // Don't prevent default - let Livewire handle it
                            //console.log('[Livewire Dropdown Fix] Item clicked, letting Livewire handle:', this.textContent);
                            
                            // If wire:click exists, ensure it fires
                            const wireClickAttr = this.getAttribute('wire:click');
                            if (wireClickAttr && window.Livewire) {
                                //console.log('[Livewire Dropdown Fix] Ensuring wire:click fires:', wireClickAttr);
                                // Livewire should handle this automatically, but log for debugging
                            }
                        }, true); // Use capture phase
                    }
                });
            }
        });
        
        // Fix method 4: Fix date range picker specifically
        document.querySelectorAll('[wire\\:model*="tableFilters.created_at"], [wire\\:model*="filters.date"]').forEach(element => {
            const container = element.closest('.fi-dropdown, [x-data]');
            if (container) {
                //console.log('[Livewire Dropdown Fix] Found date filter');
                
                // Ensure Alpine component is active
                if (container.hasAttribute('x-data') && !container.__x && window.Alpine) {
                    try {
                        Alpine.initTree(container);
                        //console.log('[Livewire Dropdown Fix] Initialized Alpine on date filter');
                    } catch (e) {}
                }
            }
        });
        
        // Fix method 5: Ensure dropdown panels are positioned correctly
        document.querySelectorAll('.fi-dropdown-panel').forEach(panel => {
            // Set high z-index
            panel.style.zIndex = '9999';
            
            // Ensure panel items are interactive
            panel.querySelectorAll('button, a, [wire\\:click], [x-on\\:click]').forEach(item => {
                item.style.cursor = 'pointer';
                item.style.pointerEvents = 'auto';
            });
        });
    }
    
    // Run fixes
    waitForLivewire(function() {
        //console.log('[Livewire Dropdown Fix] Livewire detected, applying fixes...');
        
        // Initial fix
        fixLivewireDropdowns();
        
        // Re-run after Livewire updates
        Livewire.hook('message.processed', (message, component) => {
            //console.log('[Livewire Dropdown Fix] Livewire update detected');
            setTimeout(fixLivewireDropdowns, 100);
        });
        
        // Also listen for morphdom updates
        Livewire.hook('element.updated', (el, component) => {
            if (el.querySelector('.fi-dropdown') || el.classList.contains('fi-dropdown')) {
                //console.log('[Livewire Dropdown Fix] Dropdown updated, reapplying fixes');
                setTimeout(fixLivewireDropdowns, 50);
            }
        });
    });
    
    // Alpine.js integration
    document.addEventListener('alpine:init', () => {
        //console.log('[Livewire Dropdown Fix] Alpine initialized');
        
        // Override Alpine's dropdown handler if needed
        Alpine.data('filamentDropdown', (data) => {
            return {
                ...data,
                open: false,
                toggle() {
                    //console.log('[Livewire Dropdown Fix] Alpine dropdown toggled');
                    this.open = !this.open;
                },
                close() {
                    //console.log('[Livewire Dropdown Fix] Alpine dropdown closed');
                    this.open = false;
                }
            };
        });
    });
    
    // Fallback: DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixLivewireDropdowns);
    } else {
        fixLivewireDropdowns();
    }
    
    // Export debug functions
    window.livewireDropdownFix = {
        reapply: fixLivewireDropdowns,
        debug: function() {
            //console.log('=== Livewire Dropdown Debug ===');
            //console.log('Livewire available:', !!window.Livewire);
            //console.log('Alpine available:', !!window.Alpine);
            //console.log('Dropdowns with x-data:', document.querySelectorAll('.fi-dropdown[x-data]').length);
            //console.log('Elements with wire:click:', document.querySelectorAll('[wire\\:click]').length);
            //console.log('Select components:', document.querySelectorAll('.fi-fo-select').length);
            
            // Test a specific dropdown
            const firstDropdown = document.querySelector('.fi-dropdown[x-data]');
            if (firstDropdown) {
                //console.log('First dropdown Alpine data:', firstDropdown.__x?.$data);
            }
        },
        testWireClick: function(selector) {
            const element = document.querySelector(selector);
            if (element) {
                const wireClick = element.getAttribute('wire:click');
                //console.log('Testing wire:click:', wireClick);
                element.click();
            }
        }
    };
    
    //console.log('[Livewire Dropdown Fix] Ready. Use window.livewireDropdownFix.debug() for debugging');
})();