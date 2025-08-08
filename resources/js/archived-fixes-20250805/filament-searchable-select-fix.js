/**
 * Filament v3 Searchable Select Fix
 * Addresses the known issue where searchable selects don't work after being enabled from a disabled state
 */

document.addEventListener('DOMContentLoaded', function() {
    // Patch searchable select components
    function patchSearchableSelects() {
        const searchableSelects = document.querySelectorAll('.fi-fo-select:has(input[x-ref="searchInput"])');
        
        searchableSelects.forEach(select => {
            // Check if this select has been patched
            if (select.dataset.searchablePatch) return;
            select.dataset.searchablePatch = 'true';
            
            // Find the Alpine component
            const alpineComponent = select._x_dataStack?.[0];
            if (!alpineComponent) return;
            
            // Override the disabled state watcher
            const originalDisabled = Object.getOwnPropertyDescriptor(alpineComponent, 'disabled');
            if (originalDisabled) {
                Object.defineProperty(alpineComponent, 'disabled', {
                    get: originalDisabled.get,
                    set: function(value) {
                        const wasDisabled = this.disabled;
                        originalDisabled.set.call(this, value);
                        
                        // If transitioning from disabled to enabled
                        if (wasDisabled && !value) {
                            // Reinitialize the search functionality
                            setTimeout(() => {
                                const searchInput = select.querySelector('input[x-ref="searchInput"]');
                                if (searchInput) {
                                    searchInput.removeAttribute('disabled');
                                    searchInput.removeAttribute('readonly');
                                    
                                    // Trigger Alpine to re-evaluate
                                    searchInput.dispatchEvent(new Event('input', { bubbles: true }));
                                }
                            }, 100);
                        }
                    }
                });
            }
        });
    }
    
    // Initial patch
    setTimeout(patchSearchableSelects, 500);
    
    // Re-patch after Livewire updates
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            setTimeout(patchSearchableSelects, 100);
        });
    }
    
    // Watch for dynamically added selects
    const observer = new MutationObserver((mutations) => {
        let shouldPatch = false;
        mutations.forEach(mutation => {
            mutation.addedNodes.forEach(node => {
                if (node.nodeType === 1 && (node.classList?.contains('fi-fo-select') || node.querySelector?.('.fi-fo-select'))) {
                    shouldPatch = true;
                }
            });
        });
        if (shouldPatch) {
            setTimeout(patchSearchableSelects, 100);
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});

// Export for debugging
window.FilamentSearchableSelectFix = {
    patch: function() {
        const event = new Event('DOMContentLoaded');
        document.dispatchEvent(event);
    },
    version: '1.0.0'
};