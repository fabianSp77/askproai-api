/**
 * Wizard Dropdown Fix
 * Specifically fixes dropdown issues in the Quick Setup Wizard
 */

(function() {
    'use strict';
    
    function fixWizardDropdowns() {
        // Wait for Alpine and Filament to be ready
        if (!window.Alpine || !window.Livewire) {
            setTimeout(fixWizardDropdowns, 100);
            return;
        }
        
        // Fix searchable select dropdowns
        const fixSearchableSelects = () => {
            // Find all searchable select containers
            const selectContainers = document.querySelectorAll('.fi-select-container');
            
            selectContainers.forEach(container => {
                // Find the search input within
                const searchInput = container.querySelector('input[type="search"], input[role="combobox"]');
                if (searchInput && !searchInput.hasAttribute('autocomplete')) {
                    searchInput.setAttribute('autocomplete', 'off');
                }
                
                // Ensure dropdown panel has proper z-index
                const dropdownPanel = container.querySelector('.fi-dropdown-panel');
                if (dropdownPanel) {
                    dropdownPanel.style.zIndex = '999999';
                }
            });
            
            // Fix Choices.js selects (used by Filament)
            const choicesInputs = document.querySelectorAll('.choices__input');
            choicesInputs.forEach(input => {
                if (!input.hasAttribute('autocomplete')) {
                    input.setAttribute('autocomplete', 'off');
                }
            });
        };
        
        // Fix dropdown positioning
        const fixDropdownPositioning = () => {
            // Override Filament's dropdown positioning
            document.addEventListener('click', (e) => {
                if (e.target.closest('.fi-select-trigger')) {
                    setTimeout(() => {
                        const openDropdowns = document.querySelectorAll('.fi-dropdown-panel');
                        openDropdowns.forEach(dropdown => {
                            if (dropdown.style.display !== 'none') {
                                dropdown.style.zIndex = '999999';
                                dropdown.style.position = 'absolute';
                            }
                        });
                    }, 10);
                }
            }, true);
        };
        
        // Initialize fixes
        fixSearchableSelects();
        fixDropdownPositioning();
        
        // Re-run after Livewire updates
        if (window.Livewire) {
            Livewire.hook('message.processed', () => {
                setTimeout(fixSearchableSelects, 50);
            });
            
            // Hook into morph events
            Livewire.hook('element.updated', (el, component) => {
                if (el.querySelector('.fi-select-container')) {
                    setTimeout(fixSearchableSelects, 50);
                }
            });
        }
        
        // Monitor for dynamic content
        const observer = new MutationObserver((mutations) => {
            let hasNewSelects = false;
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && (
                        node.classList?.contains('fi-select-container') ||
                        node.querySelector?.('.fi-select-container')
                    )) {
                        hasNewSelects = true;
                    }
                });
            });
            
            if (hasNewSelects) {
                setTimeout(fixSearchableSelects, 50);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Start the fix
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixWizardDropdowns);
    } else {
        fixWizardDropdowns();
    }
})();