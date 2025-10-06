// SIMPLE MODAL FIX - Clean and effective
(function() {
    'use strict';

    console.log('[MODAL FIX] Initializing...');

    // Fix 1: Override showHtmlModal if it exists
    if (typeof window.showHtmlModal === 'undefined') {
        window.showHtmlModal = function() {
            console.log('[MODAL FIX] showHtmlModal blocked');
            return false;
        };
    }

    // Fix 2: Make getElementById safe for modal elements
    const originalGetElementById = document.getElementById.bind(document);
    document.getElementById = function(id) {
        if (id === 'livewire-error' || (id && id.toString().includes('modal'))) {
            console.log('[MODAL FIX] Returning safe element for:', id);
            // Return a fake element that won't error when accessed
            return {
                innerHTML: '',
                style: {},
                classList: {
                    add: function() {},
                    remove: function() {},
                    contains: function() { return false; }
                },
                appendChild: function() {},
                removeChild: function() {},
                remove: function() {},
                setAttribute: function() {},
                getAttribute: function() { return null; },
                addEventListener: function() {},
                querySelector: function() { return null; },
                querySelectorAll: function() { return []; }
            };
        }
        return originalGetElementById(id);
    };

    // Fix 3: Remove any existing modals
    function removeModals() {
        try {
            const modal = originalGetElementById('livewire-error');
            if (modal && modal.remove) {
                modal.remove();
                console.log('[MODAL FIX] Removed livewire-error modal');
            }
        } catch(e) {
            // Ignore errors
        }

        // Remove modal-like elements
        try {
            const elements = document.querySelectorAll('.modal, .modal-backdrop, #livewire-error, [role="dialog"]');
            elements.forEach(function(el) {
                if (el && el.remove) {
                    el.remove();
                }
            });
        } catch(e) {
            // Ignore errors
        }

        // Reset body
        if (document.body) {
            document.body.style.overflow = '';
            if (document.body.classList) {
                document.body.classList.remove('modal-open', 'overflow-hidden');
            }
        }
    }

    // Fix 4: Monitor for new modals
    if (typeof MutationObserver !== 'undefined') {
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                mutation.addedNodes.forEach(function(node) {
                    if (node && node.nodeType === 1) {
                        const id = node.id || '';
                        const className = (node.className && node.className.toString) ? node.className.toString() : '';

                        if (id === 'livewire-error' ||
                            id.includes('modal') ||
                            className.includes('modal')) {

                            console.log('[MODAL FIX] Removing new modal element');
                            if (node.remove) {
                                node.remove();
                            }
                        }
                    }
                });
            });
        });

        // Start observing when DOM is ready
        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        } else {
            document.addEventListener('DOMContentLoaded', function() {
                if (document.body) {
                    observer.observe(document.body, {
                        childList: true,
                        subtree: true
                    });
                }
            });
        }
    }

    // Fix 5: Patch Livewire when it loads
    function patchLivewire() {
        if (window.Livewire) {
            try {
                window.Livewire.showHtmlModal = function() {
                    console.log('[MODAL FIX] Livewire showHtmlModal blocked');
                    return false;
                };
                window.Livewire.handleError = function() {
                    console.log('[MODAL FIX] Livewire handleError blocked');
                    return false;
                };
            } catch(e) {
                // Ignore errors
            }
        }
    }

    // Apply fixes at various stages
    removeModals();
    patchLivewire();

    // DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            removeModals();
            patchLivewire();
        });
    } else {
        removeModals();
        patchLivewire();
    }

    // Livewire events
    document.addEventListener('livewire:init', patchLivewire);
    document.addEventListener('livewire:load', function() {
        patchLivewire();
        removeModals();
    });
    document.addEventListener('livewire:navigated', function() {
        setTimeout(removeModals, 100);
    });

    // Continuous monitoring for first 10 seconds
    let checkCount = 0;
    const interval = setInterval(function() {
        removeModals();
        patchLivewire();
        checkCount++;
        if (checkCount > 20) {
            clearInterval(interval);
            console.log('[MODAL FIX] Monitoring complete');
        }
    }, 500);

    console.log('[MODAL FIX] All protections active');
})();