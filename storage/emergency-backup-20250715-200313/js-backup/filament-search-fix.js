// Filament Search Dropdown Fix
(function() {
    'use strict';
    
    console.log('[Filament Search Fix] Loading...');
    
    function fixSearchDropdowns() {
        console.log('[Filament Search Fix] Looking for search dropdowns...');
        
        // Find all Filament select/search components
        const searchComponents = document.querySelectorAll(
            '.fi-fo-select, ' +
            '.fi-select-container, ' +
            '[wire\\:model*="filters"], ' +
            '[wire\\:model*="tableFilters"], ' +
            '[x-data*="select"], ' +
            'input[role="combobox"]'
        );
        
        console.log(`[Filament Search Fix] Found ${searchComponents.length} search components`);
        
        searchComponents.forEach((component, index) => {
            // Find the container
            const container = component.closest('.fi-input-wrp, .relative, [x-data]');
            if (!container) return;
            
            // Check if already fixed
            if (container.dataset.searchFixed) return;
            container.dataset.searchFixed = 'true';
            
            console.log(`[Filament Search Fix] Fixing search component ${index + 1}`);
            
            // Find the input and dropdown
            const input = container.querySelector('input[type="text"], input[role="combobox"]');
            const dropdown = container.querySelector('.absolute, [x-ref="listbox"], [role="listbox"]');
            
            if (input && dropdown) {
                // Ensure dropdown is initially hidden
                dropdown.style.display = 'none';
                dropdown.style.zIndex = '9999';
                dropdown.style.position = 'absolute';
                
                // Handle input focus
                input.addEventListener('focus', function() {
                    console.log('[Filament Search Fix] Input focused');
                    dropdown.style.display = 'block';
                });
                
                // Handle input blur (with delay to allow clicking items)
                input.addEventListener('blur', function() {
                    setTimeout(() => {
                        if (!dropdown.matches(':hover')) {
                            dropdown.style.display = 'none';
                        }
                    }, 200);
                });
                
                // Handle item clicks
                dropdown.querySelectorAll('[role="option"], li, button').forEach(item => {
                    item.style.cursor = 'pointer';
                    item.addEventListener('click', function() {
                        console.log('[Filament Search Fix] Item selected:', item.textContent.trim());
                        // Let Livewire handle the selection
                        setTimeout(() => {
                            dropdown.style.display = 'none';
                        }, 100);
                    });
                });
            }
            
            // Alternative: Fix button-triggered dropdowns
            const button = container.querySelector('button[type="button"]');
            const panel = container.querySelector('.fi-dropdown-panel, .absolute');
            
            if (button && panel && !button.dataset.altFixed) {
                button.dataset.altFixed = 'true';
                
                // Remove existing handlers
                const newButton = button.cloneNode(true);
                button.parentNode.replaceChild(newButton, button);
                
                // Add new handler
                newButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const isVisible = panel.style.display === 'block';
                    
                    // Close all dropdowns
                    document.querySelectorAll('[data-search-panel]').forEach(p => {
                        p.style.display = 'none';
                    });
                    
                    if (!isVisible) {
                        panel.style.display = 'block';
                        panel.dataset.searchPanel = 'true';
                        
                        // Focus search input if exists
                        const searchInput = panel.querySelector('input[type="text"], input[type="search"]');
                        if (searchInput) {
                            searchInput.focus();
                        }
                    } else {
                        panel.style.display = 'none';
                    }
                });
            }
        });
        
        // Fix table filters specifically
        const tableFilters = document.querySelectorAll('.fi-ta-filters-form');
        tableFilters.forEach(form => {
            console.log('[Filament Search Fix] Found table filter form');
            
            form.querySelectorAll('.fi-dropdown').forEach(dropdown => {
                if (!dropdown.dataset.tableFilterFixed) {
                    dropdown.dataset.tableFilterFixed = 'true';
                    fixDropdownComponent(dropdown);
                }
            });
        });
    }
    
    function fixDropdownComponent(dropdown) {
        const trigger = dropdown.querySelector('button');
        const panel = dropdown.querySelector('.fi-dropdown-panel');
        
        if (!trigger || !panel) return;
        
        // Clone to remove old handlers
        const newTrigger = trigger.cloneNode(true);
        trigger.parentNode.replaceChild(newTrigger, trigger);
        
        let isOpen = false;
        
        newTrigger.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            isOpen = !isOpen;
            panel.style.display = isOpen ? 'block' : 'none';
            
            if (isOpen) {
                // Position the panel
                const rect = newTrigger.getBoundingClientRect();
                panel.style.position = 'fixed';
                panel.style.top = (rect.bottom + 5) + 'px';
                panel.style.left = rect.left + 'px';
                panel.style.width = Math.max(rect.width, 200) + 'px';
                panel.style.zIndex = '99999';
            }
        });
        
        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!dropdown.contains(e.target) && isOpen) {
                isOpen = false;
                panel.style.display = 'none';
            }
        });
    }
    
    // Run when ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixSearchDropdowns);
    } else {
        fixSearchDropdowns();
    }
    
    // Re-run after navigation
    document.addEventListener('livewire:navigated', function() {
        setTimeout(fixSearchDropdowns, 100);
    });
    
    // Watch for new content
    const observer = new MutationObserver(function(mutations) {
        let shouldReapply = false;
        mutations.forEach(mutation => {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && node.querySelector && 
                        (node.querySelector('.fi-fo-select') || 
                         node.querySelector('.fi-ta-filters'))) {
                        shouldReapply = true;
                    }
                });
            }
        });
        
        if (shouldReapply) {
            setTimeout(fixSearchDropdowns, 100);
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Export for debugging
    window.filamentSearchFix = {
        fix: fixSearchDropdowns,
        debug: function() {
            console.log('Search components:', document.querySelectorAll('.fi-fo-select, [wire\\:model*="filters"]').length);
            console.log('Fixed components:', document.querySelectorAll('[data-search-fixed]').length);
        }
    };
    
    console.log('[Filament Search Fix] Ready');
})();