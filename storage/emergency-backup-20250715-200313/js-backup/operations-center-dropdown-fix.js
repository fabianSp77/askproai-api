// Operations Center Dropdown Fix
(function() {
    'use strict';
    
    console.log('Operations Center Dropdown Fix loading...');
    
    // Function to fix dropdown behavior
    function fixOperationsCenterDropdowns() {
        // Find all dropdown triggers
        const dropdownTriggers = document.querySelectorAll('[x-on\\:click="toggle"], [x-on\\:click="open = !open"], button[aria-haspopup="true"]');
        
        dropdownTriggers.forEach(trigger => {
            // Skip if already fixed
            if (trigger.hasAttribute('data-ops-dropdown-fixed')) return;
            trigger.setAttribute('data-ops-dropdown-fixed', 'true');
            
            // Find the parent Alpine component
            const alpineComponent = trigger.closest('[x-data]');
            if (!alpineComponent) return;
            
            // Add click handler for proper toggle
            trigger.addEventListener('click', function(e) {
                e.stopPropagation();
                
                // Try Alpine data access
                if (alpineComponent.__x && alpineComponent.__x.$data) {
                    const data = alpineComponent.__x.$data;
                    if ('open' in data) {
                        data.open = !data.open;
                        console.log('Toggled dropdown:', data.open);
                    }
                }
            });
        });
        
        // Global click handler to close dropdowns
        document.addEventListener('click', function(e) {
            // Don't close if clicking inside a dropdown
            if (e.target.closest('.fi-dropdown-panel, [x-ref="panel"]')) return;
            
            // Close all open dropdowns
            document.querySelectorAll('[x-data]').forEach(component => {
                if (component.__x && component.__x.$data && component.__x.$data.open) {
                    // Don't close if we clicked the trigger
                    const trigger = component.querySelector('[x-on\\:click="toggle"], [x-on\\:click="open = !open"]');
                    if (trigger && trigger.contains(e.target)) return;
                    
                    component.__x.$data.open = false;
                }
            });
        });
        
        // Fix z-index for dropdown panels
        const panels = document.querySelectorAll('.fi-dropdown-panel, [x-ref="panel"]');
        panels.forEach(panel => {
            panel.style.setProperty('z-index', '9999', 'important');
            panel.style.setProperty('position', 'absolute', 'important');
        });
        
        // Ensure dropdown items are clickable
        const dropdownItems = document.querySelectorAll('.fi-dropdown-item, [role="menuitem"]');
        dropdownItems.forEach(item => {
            item.style.setProperty('cursor', 'pointer', 'important');
            item.style.setProperty('pointer-events', 'auto', 'important');
            
            // Ensure links and buttons inside are clickable
            const interactiveElements = item.querySelectorAll('a, button');
            interactiveElements.forEach(el => {
                el.style.setProperty('pointer-events', 'auto', 'important');
            });
        });
    }
    
    // Function to reinitialize Alpine dropdowns
    function reinitializeAlpineDropdowns() {
        if (!window.Alpine) return;
        
        // Find all dropdown components that aren't initialized
        document.querySelectorAll('[x-data*="open"]:not([data-alpine-initialized])').forEach(el => {
            if (!el.__x) {
                try {
                    Alpine.initTree(el);
                    el.setAttribute('data-alpine-initialized', 'true');
                    console.log('Initialized Alpine dropdown:', el);
                } catch (e) {
                    console.error('Failed to initialize Alpine component:', e);
                }
            }
        });
    }
    
    // Apply fixes when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(fixOperationsCenterDropdowns, 100);
            setTimeout(reinitializeAlpineDropdowns, 200);
        });
    } else {
        setTimeout(fixOperationsCenterDropdowns, 100);
        setTimeout(reinitializeAlpineDropdowns, 200);
    }
    
    // Reapply fixes after Livewire updates
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            setTimeout(fixOperationsCenterDropdowns, 50);
            setTimeout(reinitializeAlpineDropdowns, 100);
        });
    }
    
    // Monitor for dynamic content
    const observer = new MutationObserver(function(mutations) {
        let shouldFix = false;
        mutations.forEach(mutation => {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && (
                        node.matches?.('[x-data]') || 
                        node.querySelector?.('[x-data]') ||
                        node.classList?.contains('fi-dropdown')
                    )) {
                        shouldFix = true;
                    }
                });
            }
        });
        
        if (shouldFix) {
            setTimeout(fixOperationsCenterDropdowns, 50);
            setTimeout(reinitializeAlpineDropdowns, 100);
        }
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Export for debugging
    window.operationsCenterFixes = {
        fixDropdowns: fixOperationsCenterDropdowns,
        reinitializeAlpine: reinitializeAlpineDropdowns,
        checkDropdownStates: function() {
            const dropdowns = document.querySelectorAll('[x-data*="open"]');
            console.log('Operations Center Dropdowns:', dropdowns.length);
            dropdowns.forEach((dd, i) => {
                const isOpen = dd.__x ? dd.__x.$data.open : 'Not initialized';
                console.log(`Dropdown ${i}:`, {
                    element: dd,
                    isOpen: isOpen,
                    hasAlpine: !!dd.__x
                });
            });
        }
    };
    
    console.log('Operations Center Dropdown Fix loaded');
})();