// NUCLEAR POPUP BLOCKER - Nothing gets through (FIXED VERSION)
(function() {
    console.log('[NUCLEAR] Initializing total popup annihilation...');

    // Store original functions
    const originalAlert = window.alert;
    const originalConfirm = window.confirm;
    const originalPrompt = window.prompt;
    const originalOpen = window.open;

    // Kill all popups
    window.alert = function() { console.log('[NUCLEAR] Blocked alert'); return true; };
    window.confirm = function() { console.log('[NUCLEAR] Blocked confirm'); return true; };
    window.prompt = function() { console.log('[NUCLEAR] Blocked prompt'); return ''; };
    window.open = function() { console.log('[NUCLEAR] Blocked window.open'); return null; };

    // Override showHtmlModal completely
    Object.defineProperty(window, 'showHtmlModal', {
        value: function() {
            console.log('[NUCLEAR] showHtmlModal terminated');
            return false;
        },
        writable: false,
        configurable: false
    });

    // Create a MutationObserver to instantly remove ANY overlay elements
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node && node.nodeType === 1) { // Element node
                    // Safely get attributes with null checks
                    const id = (node.id || '').toString();
                    const className = (node.className || '').toString();
                    const style = (node.getAttribute && node.getAttribute('style') || '').toString();

                    // Aggressive removal conditions
                    if (id === 'livewire-error' ||
                        id.includes('modal') ||
                        id.includes('overlay') ||
                        id.includes('popup') ||
                        className.includes('modal') ||
                        className.includes('overlay') ||
                        className.includes('dialog') ||
                        className.includes('popup') ||
                        style.includes('position: fixed') ||
                        style.includes('position:fixed') ||
                        style.includes('z-index') ||
                        node.tagName === 'DIALOG' ||
                        (node.getAttribute && node.getAttribute('role') === 'dialog') ||
                        (node.getAttribute && node.getAttribute('aria-modal') === 'true')) {

                        console.log('[NUCLEAR] Destroying element:', id || className || node.tagName);
                        node.remove();

                        // Also reset body styles if body exists
                        if (document.body) {
                            document.body.style.overflow = '';
                            document.body.classList.remove('modal-open', 'overflow-hidden');
                        }
                    }

                    // Check children for iframes (often used in modals)
                    if (node.querySelector) {
                        try {
                            const iframe = node.querySelector('iframe');
                            if (iframe) {
                                const iframeStyle = (iframe.getAttribute && iframe.getAttribute('style') || '').toString();
                                if (iframeStyle.includes('position') || iframeStyle.includes('100%')) {
                                    console.log('[NUCLEAR] Destroying iframe container');
                                    node.remove();
                                }
                            }
                        } catch(e) {
                            // Ignore querySelector errors
                        }
                    }
                }
            });
        });
    });

    // Start observing when document is ready
    function startObserving() {
        if (document.documentElement) {
            observer.observe(document.documentElement, {
                childList: true,
                subtree: true,
                attributes: false,
                characterData: false
            });
        }
    }

    // Kill any existing overlays immediately
    function destroyAllOverlays() {
        // Safety check for document.body
        if (!document.body) {
            return;
        }

        // Remove by ID
        ['livewire-error', 'modal', 'overlay', 'popup', 'dialog'].forEach(function(id) {
            const elem = document.getElementById(id);
            if (elem) {
                elem.remove();
                console.log('[NUCLEAR] Removed existing:', id);
            }
        });

        // Remove by selector - VERY aggressive
        const selectors = [
            '#livewire-error',
            '[id*="modal"]',
            '[id*="overlay"]',
            '[id*="popup"]',
            '[class*="modal"]',
            '[class*="overlay"]',
            '[class*="dialog"]',
            '[class*="popup"]',
            '[role="dialog"]',
            '[aria-modal="true"]',
            'dialog',
            '.fixed.inset-0',
            'div[style*="position: fixed"]',
            'div[style*="position:fixed"]',
            'div[style*="z-index"]',
            'iframe[style*="position"]'
        ];

        selectors.forEach(function(selector) {
            try {
                const elements = document.querySelectorAll(selector);
                elements.forEach(function(elem) {
                    // Don't remove navigation or essential UI
                    if (!elem.classList.contains('fi-sidebar') &&
                        !elem.classList.contains('fi-topbar') &&
                        !elem.closest('.fi-sidebar') &&
                        !elem.closest('.fi-topbar')) {

                        try {
                            const computed = window.getComputedStyle(elem);
                            const zIndex = parseInt(computed.zIndex) || 0;

                            // Remove if it looks like an overlay
                            if (computed.position === 'fixed' && zIndex > 100) {
                                elem.remove();
                                console.log('[NUCLEAR] Destroyed overlay:', selector);
                            }
                        } catch(e) {
                            // Ignore style computation errors
                        }
                    }
                });
            } catch(e) {
                // Ignore selector errors
            }
        });

        // Reset body safely
        if (document.body) {
            document.body.style.overflow = '';
            document.body.style.position = '';
            if (document.body.classList) {
                document.body.classList.remove('modal-open', 'overflow-hidden', 'no-scroll');
            }
        }
    }

    // Wait for DOM to be ready
    function whenReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    // Initialize when ready
    whenReady(function() {
        startObserving();
        destroyAllOverlays();
    });

    // Run on various events
    window.addEventListener('load', destroyAllOverlays);

    // Livewire specific handling
    document.addEventListener('livewire:init', function() {
        console.log('[NUCLEAR] Disabling Livewire modals');
        if (window.Livewire) {
            window.Livewire.showHtmlModal = function() { return false; };
            window.Livewire.handleError = function() { return false; };
            window.Livewire.onError = function() { return false; };
        }
        destroyAllOverlays();
    });

    document.addEventListener('livewire:initialized', destroyAllOverlays);
    document.addEventListener('livewire:load', destroyAllOverlays);
    document.addEventListener('livewire:navigated', destroyAllOverlays);
    document.addEventListener('livewire:navigating', destroyAllOverlays);

    // Continuous destruction for first 15 seconds (only after DOM ready)
    whenReady(function() {
        let destroyCount = 0;
        const destroyInterval = setInterval(function() {
            destroyAllOverlays();
            destroyCount++;
            if (destroyCount > 30) {
                clearInterval(destroyInterval);
                console.log('[NUCLEAR] Continuous destruction phase complete');
            }
        }, 500);
    });

    // Override createElement to prevent modal creation
    const originalCreateElement = document.createElement;
    document.createElement = function(tagName) {
        const elem = originalCreateElement.call(document, tagName);

        // Add interceptor to catch modal-like elements
        const originalSetAttribute = elem.setAttribute;
        elem.setAttribute = function(name, value) {
            if (name === 'id' && typeof value === 'string' && (value === 'livewire-error' || value.includes('modal'))) {
                console.log('[NUCLEAR] Prevented creation of:', value);
                return;
            }
            return originalSetAttribute.call(elem, name, value);
        };

        return elem;
    };

    console.log('[NUCLEAR] Total popup annihilation active - NOTHING will display');
})();