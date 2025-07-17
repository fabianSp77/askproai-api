// Admin Portal Fixes - Behebt Livewire/Alpine Konflikte
(function() {
    'use strict';
    
    //console.log('Admin Portal Fixes loading...');
    
    // Early initialization - don't wait for DOMContentLoaded
    setupEventListeners();
    
    // Fix 1: Portal Redirect Handler - Register immediately
    window.addEventListener('redirect-to-portal', function(event) {
        //console.log('Redirect to portal triggered:', event.detail);
        if (event.detail && event.detail.url) {
            // Use location.href for clean redirect
            window.location.href = event.detail.url;
        }
    });
    
    // Also listen for Livewire events directly
    if (window.Livewire) {
        window.Livewire.on('redirect-to-portal', function(event) {
            //console.log('Livewire redirect-to-portal event:', event);
            if (event && event.url) {
                window.location.href = event.url;
            }
        });
    }
    
    // Setup event listeners
    function setupEventListeners() {
        // Wait for Livewire to be available
        if (typeof Livewire === 'undefined') {
            setTimeout(setupEventListeners, 50);
            return;
        }
        
        // Livewire v3 syntax for listening to events
        Livewire.on('redirect-to-portal', (event) => {
            //console.log('Livewire v3 redirect event:', event);
            if (event && event.url) {
                window.location.href = event.url;
            }
        });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        // Fix 2: Dropdown Close Problem
        fixFilamentDropdowns();
        
        // Fix 3: Alpine.js Re-initialization after Livewire updates
        if (window.Livewire) {
            Livewire.hook('message.processed', (message, component) => {
                // Re-initialize Alpine components
                setTimeout(() => {
                    if (window.Alpine) {
                        // Find all uninitialized Alpine components
                        document.querySelectorAll('[x-data]:not([x-data-initialized])').forEach(el => {
                            if (!el.__x) {
                                Alpine.initTree(el);
                                el.setAttribute('x-data-initialized', 'true');
                            }
                        });
                    }
                    
                    // Re-apply dropdown fixes
                    fixFilamentDropdowns();
                }, 100);
            });
        }
    });
    
    // Fix Filament Dropdowns
    function fixFilamentDropdowns() {
        //console.log('Fixing Filament dropdowns...');
        
        // Fix dropdown toggles
        document.querySelectorAll('[x-on\\:click="toggle"]').forEach(button => {
            if (!button.hasAttribute('data-dropdown-fixed')) {
                button.setAttribute('data-dropdown-fixed', 'true');
                
                // Clone and replace to remove old event listeners
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
                
                // Add new click handler
                newButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const dropdown = this.closest('[x-data]');
                    if (dropdown && dropdown.__x) {
                        dropdown.__x.$data.open = !dropdown.__x.$data.open;
                    }
                });
            }
        });
        
        // Fix dropdown close on outside click
        document.addEventListener('click', function(e) {
            // Close all open dropdowns when clicking outside
            document.querySelectorAll('[x-data*="open"]:not([x-data*="false"])').forEach(dropdown => {
                if (dropdown.__x && !dropdown.contains(e.target)) {
                    dropdown.__x.$data.open = false;
                }
            });
        });
        
        // Fix z-index issues
        document.querySelectorAll('.fi-dropdown-panel').forEach(panel => {
            panel.style.zIndex = '9999';
        });
    }
    
    // Fix 4: Button Click Handler Enhancement
    document.addEventListener('click', function(e) {
        const button = e.target.closest('button[wire\\:click]');
        if (button) {
            // Prevent double clicks
            if (button.hasAttribute('data-clicking')) {
                e.preventDefault();
                return;
            }
            
            button.setAttribute('data-clicking', 'true');
            setTimeout(() => {
                button.removeAttribute('data-clicking');
            }, 1000);
            
            // Log for debugging
            //console.log('Livewire button clicked:', button.getAttribute('wire:click'));
        }
    });
    
    // Fix 5: Alpine Component Registration
    if (window.Alpine) {
        // Register missing Alpine components
        Alpine.data('dropdown', () => ({
            open: false,
            toggle() {
                this.open = !this.open;
            },
            close() {
                this.open = false;
            }
        }));
        
        // Register smart dropdown with better handling
        Alpine.data('smartDropdown', () => ({
            open: false,
            init() {
                // Close on escape
                this.$watch('open', value => {
                    if (value) {
                        document.addEventListener('keydown', this.handleEscape);
                    } else {
                        document.removeEventListener('keydown', this.handleEscape);
                    }
                });
            },
            handleEscape(e) {
                if (e.key === 'Escape') {
                    this.open = false;
                }
            },
            toggle() {
                this.open = !this.open;
            },
            close() {
                this.open = false;
            }
        }));
    }
    
    // Export fixes for debugging
    window.adminPortalFixes = {
        fixDropdowns: fixFilamentDropdowns,
        reinitializeAlpine: function() {
            document.querySelectorAll('[x-data]').forEach(el => {
                if (!el.__x && window.Alpine) {
                    Alpine.initTree(el);
                }
            });
        },
        checkDropdowns: function() {
            const dropdowns = document.querySelectorAll('[x-data*="open"]');
            //console.log('Found dropdowns:', dropdowns.length);
            dropdowns.forEach((dd, i) => {
                //console.log(`Dropdown ${i}:`, {
                    element: dd,
                    alpineData: dd.__x ? dd.__x.$data : 'Not initialized',
                    open: dd.__x ? dd.__x.$data.open : 'N/A'
                });
            });
        }
    };
    
    //console.log('Admin Portal Fixes loaded successfully');
})();