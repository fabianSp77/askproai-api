// Direct Dropdown Fix - Aggressive approach
(function() {
    'use strict';
    
    //console.log('[Dropdown Fix Direct] Starting...');
    
    // Fix function that works with any dropdown structure
    function fixDropdown(container) {
        // Find trigger element (button, div with click handler, etc.)
        const triggers = container.querySelectorAll('[x-on\\:click], [wire\\:click], button[aria-haspopup="true"], [role="button"]');
        const panels = container.querySelectorAll('[x-show], [x-ref="panel"], .dropdown-menu, .absolute.z-10, [role="menu"]');
        
        triggers.forEach(trigger => {
            if (trigger.dataset.dropdownFixed) return;
            trigger.dataset.dropdownFixed = 'true';
            
            // Remove any Alpine click handlers temporarily
            const originalClick = trigger.getAttribute('x-on:click') || trigger.getAttribute('@click');
            
            // Add our own handler
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                //console.log('[Dropdown Fix Direct] Trigger clicked');
                
                // Find the associated panel
                let panel = null;
                
                // Try to find panel as sibling
                panel = trigger.nextElementSibling;
                if (!panel || !panel.matches('[x-show], [x-ref="panel"], .absolute')) {
                    // Try to find panel as child of parent
                    panel = trigger.parentElement.querySelector('[x-show], [x-ref="panel"], .absolute');
                }
                if (!panel) {
                    // Try to find panel in container
                    panel = container.querySelector('[x-show], [x-ref="panel"], .absolute.z-10');
                }
                
                if (panel) {
                    // Toggle visibility
                    const isHidden = panel.style.display === 'none' || !panel.style.display || panel.classList.contains('hidden');
                    
                    // Close all other panels first
                    document.querySelectorAll('[data-dropdown-panel="true"]').forEach(p => {
                        p.style.display = 'none';
                        p.classList.add('hidden');
                    });
                    
                    if (isHidden) {
                        panel.style.display = 'block';
                        panel.classList.remove('hidden');
                        panel.dataset.dropdownPanel = 'true';
                        //console.log('[Dropdown Fix Direct] Panel opened');
                    } else {
                        panel.style.display = 'none';
                        panel.classList.add('hidden');
                        //console.log('[Dropdown Fix Direct] Panel closed');
                    }
                    
                    // Try to update Alpine state
                    if (window.Alpine && container.__x) {
                        try {
                            container.__x.$data.open = !isHidden;
                        } catch (e) {}
                    }
                }
            });
            
            // Ensure trigger is clickable
            trigger.style.cursor = 'pointer';
            trigger.style.pointerEvents = 'auto';
            trigger.disabled = false;
        });
        
        // Make all items in panels clickable
        panels.forEach(panel => {
            panel.querySelectorAll('button, a, [role="menuitem"], [wire\\:click]').forEach(item => {
                item.style.cursor = 'pointer';
                item.style.pointerEvents = 'auto';
                
                // Add click handler for items
                if (!item.dataset.itemFixed) {
                    item.dataset.itemFixed = 'true';
                    item.addEventListener('click', function(e) {
                        //console.log('[Dropdown Fix Direct] Item clicked:', item.textContent.trim());
                        // Don't prevent default - let Livewire/Alpine handle the action
                        
                        // Close the dropdown after selection
                        setTimeout(() => {
                            if (panel.dataset.dropdownPanel) {
                                panel.style.display = 'none';
                                panel.classList.add('hidden');
                                delete panel.dataset.dropdownPanel;
                            }
                        }, 100);
                    });
                }
            });
        });
    }
    
    // Find and fix all dropdowns
    function fixAllDropdowns() {
        //console.log('[Dropdown Fix Direct] Searching for dropdowns...');
        
        // Method 1: Filament dropdowns
        document.querySelectorAll('.fi-dropdown').forEach((dropdown, i) => {
            //console.log(`[Dropdown Fix Direct] Found Filament dropdown ${i + 1}`);
            fixDropdown(dropdown);
        });
        
        // Method 2: Alpine dropdowns
        document.querySelectorAll('[x-data*="dropdown"], [x-data*="open"]').forEach((dropdown, i) => {
            //console.log(`[Dropdown Fix Direct] Found Alpine dropdown ${i + 1}`);
            fixDropdown(dropdown);
        });
        
        // Method 3: Generic dropdown patterns
        document.querySelectorAll('.relative').forEach(container => {
            if (container.querySelector('button') && container.querySelector('.absolute')) {
                //console.log('[Dropdown Fix Direct] Found generic dropdown');
                fixDropdown(container);
            }
        });
        
        // Method 4: Specific selectors for branch/filter dropdowns
        document.querySelectorAll('[class*="branch"], [class*="filter"], [class*="select"]').forEach(element => {
            if (element.querySelector('button, [role="button"]')) {
                //console.log('[Dropdown Fix Direct] Found specific dropdown');
                fixDropdown(element);
            }
        });
        
        // Method 5: Look for company/branch search dropdowns specifically
        document.querySelectorAll('[wire\\:model*="Company"], [wire\\:model*="Branch"], [wire\\:model*="selectedCompany"], [wire\\:model*="selectedBranch"]').forEach(element => {
            const container = element.closest('.relative, .fi-dropdown, [x-data]');
            if (container) {
                //console.log('[Dropdown Fix Direct] Found company/branch search dropdown');
                fixDropdown(container);
            }
        });
        
        // Method 6: Date range pickers
        document.querySelectorAll('[wire\\:model*="date"], [wire\\:model*="Date"], input[type="date"], .fi-fo-date-time-picker').forEach(element => {
            const container = element.closest('.relative, .fi-dropdown, [x-data]');
            if (container) {
                //console.log('[Dropdown Fix Direct] Found date picker dropdown');
                fixDropdown(container);
            }
        });
        
        // Method 7: Table filters and actions
        document.querySelectorAll('.fi-ta-filters, .fi-ta-actions, [class*="table-filter"]').forEach(element => {
            if (element.querySelector('button')) {
                //console.log('[Dropdown Fix Direct] Found table filter/action dropdown');
                fixDropdown(element);
            }
        });
    }
    
    // Apply fixes when ready
    function applyFixes() {
        fixAllDropdowns();
        
        // Global click handler to close dropdowns
        document.addEventListener('click', function(e) {
            if (!e.target.closest('[data-dropdown-fixed], [data-dropdown-panel]')) {
                document.querySelectorAll('[data-dropdown-panel="true"]').forEach(panel => {
                    panel.style.display = 'none';
                    panel.classList.add('hidden');
                });
            }
        });
    }
    
    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', applyFixes);
    } else {
        applyFixes();
    }
    
    // Re-run after dynamic content loads
    const observer = new MutationObserver(function(mutations) {
        let shouldReapply = false;
        mutations.forEach(mutation => {
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && node.classList && 
                        (node.classList.contains('fi-dropdown') || 
                         node.querySelector?.('.fi-dropdown') ||
                         node.querySelector?.('[x-data*="dropdown"]'))) {
                        shouldReapply = true;
                    }
                });
            }
        });
        
        if (shouldReapply) {
            //console.log('[Dropdown Fix Direct] New content detected, reapplying...');
            setTimeout(fixAllDropdowns, 100);
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Livewire navigation support
    document.addEventListener('livewire:navigated', function() {
        //console.log('[Dropdown Fix Direct] Livewire navigated, reapplying...');
        setTimeout(applyFixes, 100);
    });
    
    // Export for debugging
    window.dropdownFixDirect = {
        fixAll: fixAllDropdowns,
        debug: function() {
            //console.log('=== Dropdown Debug Info ===');
            //console.log('Filament dropdowns:', document.querySelectorAll('.fi-dropdown').length);
            //console.log('Alpine dropdowns:', document.querySelectorAll('[x-data*="dropdown"]').length);
            //console.log('Fixed triggers:', document.querySelectorAll('[data-dropdown-fixed]').length);
            //console.log('All buttons:', document.querySelectorAll('button').length);
            
            // List all dropdowns
            document.querySelectorAll('.fi-dropdown, [x-data*="dropdown"]').forEach((dd, i) => {
                //console.log(`Dropdown ${i + 1}:`, {
                    classes: dd.className,
                    hasButton: !!dd.querySelector('button'),
                    hasPanel: !!dd.querySelector('.absolute, [x-show]')
                });
            });
        }
    };
    
    //console.log('[Dropdown Fix Direct] Loaded. Use window.dropdownFixDirect.debug() for info');
})();