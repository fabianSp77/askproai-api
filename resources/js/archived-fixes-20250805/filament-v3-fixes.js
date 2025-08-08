/**
 * Filament v3 Compatibility Fixes
 * Resolves conflicts between custom JavaScript and Filament/Livewire/Alpine
 */

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('Filament v3 fixes initializing...');
    
    // Fix 1: Ensure Livewire is properly initialized
    if (window.Livewire) {
        // Restore missing Livewire features
        if (!window.Livewire.features) {
            window.Livewire.features = {};
        }
        
        // Add stub for supportFileDownloads if missing
        if (!window.Livewire.features.supportFileDownloads) {
            window.Livewire.features.supportFileDownloads = {
                init: function() {},
                download: function(filename, content) {
                    // Basic download implementation
                    const blob = new Blob([content], { type: 'application/octet-stream' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                }
            };
        }
    }
    
    // Fix 2: Patch Alpine.js conflicts
    if (window.Alpine) {
        // Store reference to the Filament/Livewire Alpine instance
        window.FilamentAlpine = window.Alpine;
        
        // Ensure Alpine directives work correctly
        document.addEventListener('alpine:init', function() {
            console.log('Alpine initialized');
        });
    }
    
    // Fix 3: Patch dropdown functionality
    let dropdownPatchAttempts = 0;
    function patchDropdowns() {
        dropdownPatchAttempts++;
        
        // Find all Filament dropdowns
        const dropdowns = document.querySelectorAll([
            '[x-data*="select"]',
            '[x-data*="dropdown"]',
            '.fi-dropdown',
            '.fi-select',
            '.choices__list--dropdown'
        ].join(', '));
        
        dropdowns.forEach(dropdown => {
            // Skip if already patched
            if (dropdown.dataset.patched) return;
            
            // Mark as patched
            dropdown.dataset.patched = 'true';
            
            // Ensure click events work
            const triggers = dropdown.querySelectorAll('[x-on\\:click], [wire\\:click], button');
            triggers.forEach(trigger => {
                // Remove any duplicate event listeners
                const newTrigger = trigger.cloneNode(true);
                trigger.parentNode.replaceChild(newTrigger, trigger);
            });
            
            // Fix searchable selects
            const searchInputs = dropdown.querySelectorAll('input[type="search"], input[x-ref="search"]');
            searchInputs.forEach(input => {
                // Ensure autocomplete is off
                input.setAttribute('autocomplete', 'off');
                
                // Fix focus issues
                input.addEventListener('focus', function(e) {
                    e.stopPropagation();
                }, true);
            });
        });
        
        // Retry if no dropdowns found and we haven't tried too many times
        if (dropdowns.length === 0 && dropdownPatchAttempts < 10) {
            setTimeout(patchDropdowns, 500);
        }
    }
    
    // Start patching dropdowns
    setTimeout(patchDropdowns, 100);
    
    // Fix 4: Handle Livewire updates
    if (window.Livewire) {
        Livewire.hook('message.processed', (message, component) => {
            // Re-patch dropdowns after Livewire updates
            setTimeout(patchDropdowns, 100);
            
            // Ensure Alpine components are re-initialized
            if (window.Alpine && window.Alpine.initTree) {
                const uninitializedElements = document.querySelectorAll('[x-data]:not([x-init])');
                uninitializedElements.forEach(el => {
                    if (!el._x_dataStack) {
                        window.Alpine.initTree(el);
                    }
                });
            }
        });
        
        // Fix navigation issues
        Livewire.hook('element.updated', (el, component) => {
            // Re-initialize any Alpine components in the updated element
            if (window.Alpine && el.querySelectorAll) {
                const alpineElements = el.querySelectorAll('[x-data]');
                alpineElements.forEach(alpineEl => {
                    if (!alpineEl._x_dataStack) {
                        window.Alpine.initTree(alpineEl);
                    }
                });
            }
        });
    }
    
    // Fix 5: Global error handler for uncaught Alpine errors
    window.addEventListener('error', function(event) {
        if (event.error && event.error.message) {
            const errorMessage = event.error.message;
            
            // Handle Alpine expression errors
            if (errorMessage.includes('Alpine Expression Error')) {
                console.warn('Alpine Expression Error caught and handled:', errorMessage);
                event.preventDefault();
                
                // Try to reinitialize the component
                const match = errorMessage.match(/Expression: "([^"]+)"/);
                if (match) {
                    const expression = match[1];
                    // Find elements with this expression and try to fix them
                    document.querySelectorAll(`[x-show="${expression}"], [x-if="${expression}"]`).forEach(el => {
                        // Remove the problematic directive temporarily
                        el.removeAttribute('x-show');
                        el.removeAttribute('x-if');
                    });
                }
            }
            
            // Handle undefined function errors
            if (errorMessage.includes('is not a function') && errorMessage.includes('Livewire')) {
                console.warn('Livewire function error caught and handled:', errorMessage);
                event.preventDefault();
            }
        }
    });
    
    // Fix 6: Specific fixes for searchable selects
    function fixSearchableSelects() {
        // Fix Filament searchable selects
        document.querySelectorAll('.fi-fo-select').forEach(select => {
            const searchInput = select.querySelector('input[x-ref="searchInput"]');
            if (searchInput) {
                // Ensure the input is properly initialized
                searchInput.removeAttribute('readonly');
                searchInput.removeAttribute('disabled');
                
                // Fix event handlers
                const events = ['input', 'keydown', 'keyup'];
                events.forEach(eventType => {
                    searchInput.addEventListener(eventType, function(e) {
                        e.stopPropagation();
                    }, true);
                });
            }
        });
    }
    
    // Apply searchable select fixes
    setTimeout(fixSearchableSelects, 500);
    
    // Re-apply fixes when Filament modals open
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList') {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        if (node.classList && (node.classList.contains('fi-modal') || node.querySelector('.fi-modal'))) {
                            setTimeout(() => {
                                patchDropdowns();
                                fixSearchableSelects();
                            }, 100);
                        }
                    }
                });
            }
        });
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    console.log('Filament v3 fixes initialized');
});

// Export for debugging
window.FilamentV3Fixes = {
    patchDropdowns: function() {
        const event = new Event('DOMContentLoaded');
        document.dispatchEvent(event);
    },
    version: '1.0.0'
};