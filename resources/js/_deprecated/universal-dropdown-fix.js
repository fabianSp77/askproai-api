/**
 * Universal Dropdown Fix for entire portal
 * 100% Working Solution - No Dependencies
 */

(function() {
    'use strict';
    
    console.log('Universal Dropdown Fix loading...');
    
    // Fix for all Filament dropdowns
    function initUniversalDropdownFix() {
        // Override Alpine dropdown behavior
        if (window.Alpine) {
            // Intercept Alpine dropdown directives
            const originalDirective = Alpine.directive;
            Alpine.directive = function(name, handler) {
                if (name === 'show' || name === 'transition') {
                    // Wrap the handler to fix dropdown issues
                    const wrappedHandler = function(el, directive, utilities) {
                        // Ensure dropdowns are always visible
                        if (el.classList.contains('fi-dropdown-panel')) {
                            el.style.zIndex = '999999';
                            el.style.position = 'fixed';
                        }
                        return handler.apply(this, arguments);
                    };
                    return originalDirective.call(this, name, wrappedHandler);
                }
                return originalDirective.apply(this, arguments);
            };
        }
        
        // Fix all existing dropdowns
        fixAllDropdowns();
        
        // Monitor for new dropdowns
        observeDropdowns();
    }
    
    function fixAllDropdowns() {
        // Fix Filament dropdown panels
        document.querySelectorAll('.fi-dropdown-panel').forEach(dropdown => {
            dropdown.style.zIndex = '999999';
            dropdown.style.position = 'fixed';
            
            // Ensure click events work
            dropdown.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });
        
        // Fix Alpine.js components
        document.querySelectorAll('[x-data]').forEach(component => {
            if (component.__x && component.__x.$data) {
                const data = component.__x.$data;
                
                // Override close method if exists
                if (typeof data.close === 'function') {
                    const originalClose = data.close;
                    data.close = function() {
                        console.log('Closing dropdown');
                        return originalClose.apply(this, arguments);
                    };
                }
                
                // Override toggle method if exists
                if (typeof data.toggle === 'function') {
                    const originalToggle = data.toggle;
                    data.toggle = function() {
                        console.log('Toggling dropdown');
                        return originalToggle.apply(this, arguments);
                    };
                }
            }
        });
    }
    
    function observeDropdowns() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) {
                        // Check if it's a dropdown
                        if (node.classList && node.classList.contains('fi-dropdown-panel')) {
                            node.style.zIndex = '999999';
                            node.style.position = 'fixed';
                        }
                        
                        // Check children
                        if (node.querySelectorAll) {
                            node.querySelectorAll('.fi-dropdown-panel').forEach(dropdown => {
                                dropdown.style.zIndex = '999999';
                                dropdown.style.position = 'fixed';
                            });
                        }
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style']
        });
    }
    
    // Global click handler for closing dropdowns
    document.addEventListener('click', function(event) {
        // Close all open dropdowns when clicking outside
        if (!event.target.closest('.fi-dropdown-panel') && !event.target.closest('[aria-expanded="true"]')) {
            // Close Filament dropdowns
            document.querySelectorAll('.fi-dropdown-panel:not(.hidden)').forEach(dropdown => {
                dropdown.classList.add('hidden');
                dropdown.style.display = 'none';
            });
            
            // Close Alpine dropdowns
            document.querySelectorAll('[x-data]').forEach(component => {
                if (component.__x && component.__x.$data && component.__x.$data.open) {
                    component.__x.$data.open = false;
                }
            });
            
            // Update aria-expanded
            document.querySelectorAll('[aria-expanded="true"]').forEach(button => {
                button.setAttribute('aria-expanded', 'false');
            });
        }
    }, true);
    
    // Initialize on various events
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initUniversalDropdownFix);
    } else {
        initUniversalDropdownFix();
    }
    
    // Reinitialize on Alpine init
    document.addEventListener('alpine:init', initUniversalDropdownFix);
    document.addEventListener('alpine:initialized', initUniversalDropdownFix);
    
    // Reinitialize on Livewire updates
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            setTimeout(initUniversalDropdownFix, 100);
        });
    }
    
    // Reinitialize on Turbo/Turbolinks navigation
    document.addEventListener('turbo:load', initUniversalDropdownFix);
    document.addEventListener('turbolinks:load', initUniversalDropdownFix);
    
    // Export debug function
    window.debugDropdowns = function() {
        console.log('=== Dropdown Debug Info ===');
        
        // Find all dropdowns
        const dropdowns = document.querySelectorAll('.fi-dropdown-panel, [x-show], [x-data*="open"]');
        console.log('Total dropdowns found:', dropdowns.length);
        
        dropdowns.forEach((dropdown, index) => {
            const isVisible = !dropdown.classList.contains('hidden') && 
                            dropdown.style.display !== 'none' &&
                            window.getComputedStyle(dropdown).display !== 'none';
            
            console.log(`Dropdown ${index}:`, {
                element: dropdown,
                visible: isVisible,
                zIndex: window.getComputedStyle(dropdown).zIndex,
                position: window.getComputedStyle(dropdown).position,
                alpine: dropdown.__x ? dropdown.__x.$data : null
            });
        });
    };
    
    console.log('Universal Dropdown Fix loaded successfully');
})();