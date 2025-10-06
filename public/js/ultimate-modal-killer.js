// ULTIMATE MODAL KILLER - Prevents ALL modal operations
(function() {
    'use strict';

    console.log('[ULTIMATE KILLER] Initializing complete modal prevention...');

    // Override ALL methods that might set innerHTML
    const protectElement = function(element) {
        if (!element || typeof element !== 'object') return element;

        // Create a proxy to intercept property access
        return new Proxy(element, {
            set: function(target, property, value) {
                if (property === 'innerHTML' || property === 'outerHTML') {
                    console.warn('[ULTIMATE KILLER] Blocked innerHTML/outerHTML modification');
                    return true; // Pretend it succeeded
                }
                target[property] = value;
                return true;
            },
            get: function(target, property) {
                if (property === 'contentWindow' || property === 'contentDocument') {
                    console.warn('[ULTIMATE KILLER] Intercepted iframe content access');
                    // Return a fake object that won't error
                    return {
                        document: {
                            open: () => {},
                            write: () => {},
                            close: () => {},
                            body: { innerHTML: '' },
                            documentElement: { innerHTML: '' }
                        }
                    };
                }
                return target[property];
            }
        });
    };

    // Override getElementById to return protected elements
    const originalGetElementById = document.getElementById;
    document.getElementById = function(id) {
        const element = originalGetElementById.call(document, id);
        if (id === 'livewire-error' || (id && id.includes('modal'))) {
            console.log('[ULTIMATE KILLER] Intercepted getElementById for:', id);
            return null; // Return null for modal elements
        }
        return element;
    };

    // Override querySelector to return protected elements
    const originalQuerySelector = document.querySelector;
    document.querySelector = function(selector) {
        if (selector && (selector.includes('modal') || selector.includes('livewire-error'))) {
            console.log('[ULTIMATE KILLER] Intercepted querySelector for:', selector);
            return null;
        }
        return originalQuerySelector.call(document, selector);
    };

    // Override querySelectorAll
    const originalQuerySelectorAll = document.querySelectorAll;
    document.querySelectorAll = function(selector) {
        if (selector && (selector.includes('modal') || selector.includes('livewire-error'))) {
            console.log('[ULTIMATE KILLER] Intercepted querySelectorAll for:', selector);
            return []; // Return empty array
        }
        return originalQuerySelectorAll.call(document, selector);
    };

    // Patch the global showHtmlModal function
    Object.defineProperty(window, 'showHtmlModal', {
        value: function() {
            console.log('[ULTIMATE KILLER] showHtmlModal completely disabled');
            return false;
        },
        writable: false,
        configurable: false
    });

    // Override any function that starts with 'show' or contains 'modal'
    const handler = {
        set(target, property, value) {
            if (typeof property === 'string') {
                const propLower = property.toLowerCase();
                if (propLower.includes('modal') || propLower.startsWith('show')) {
                    if (typeof value === 'function') {
                        console.log('[ULTIMATE KILLER] Overriding function:', property);
                        target[property] = function() {
                            console.log('[ULTIMATE KILLER] Blocked:', property);
                            return false;
                        };
                        return true;
                    }
                }
            }
            target[property] = value;
            return true;
        }
    };

    // Apply to window object
    window = new Proxy(window, handler);

    // Patch Livewire when it loads
    function patchLivewire() {
        if (window.Livewire) {
            console.log('[ULTIMATE KILLER] Patching Livewire...');

            // Disable all error handling
            window.Livewire.showHtmlModal = () => false;
            window.Livewire.handleError = () => false;
            window.Livewire.onError = () => false;

            // Patch the hook system
            if (window.Livewire.hook) {
                const originalHook = window.Livewire.hook;
                window.Livewire.hook = function(name, callback) {
                    if (name.includes('error') || name.includes('modal')) {
                        console.log('[ULTIMATE KILLER] Blocked Livewire hook:', name);
                        return;
                    }
                    return originalHook.call(this, name, callback);
                };
            }

            // Patch directive system
            if (window.Livewire.directive) {
                const originalDirective = window.Livewire.directive;
                window.Livewire.directive = function(name, callback) {
                    if (name.includes('error') || name.includes('modal')) {
                        console.log('[ULTIMATE KILLER] Blocked Livewire directive:', name);
                        return;
                    }
                    return originalDirective.call(this, name, callback);
                };
            }
        }
    }

    // Monitor for Livewire
    let livewireCheckCount = 0;
    const livewireCheck = setInterval(() => {
        patchLivewire();
        livewireCheckCount++;
        if (livewireCheckCount > 20) {
            clearInterval(livewireCheck);
        }
    }, 500);

    // Listen for all Livewire events
    ['livewire:init', 'livewire:initialized', 'livewire:load', 'livewire:navigated'].forEach(event => {
        document.addEventListener(event, patchLivewire);
    });

    // Nuclear option: Override createElement to prevent iframe creation
    const originalCreateElement = document.createElement;
    document.createElement = function(tagName) {
        if (tagName.toLowerCase() === 'iframe') {
            console.log('[ULTIMATE KILLER] Prevented iframe creation');
            // Return a fake div instead
            const div = originalCreateElement.call(document, 'div');
            div.style.display = 'none';
            return div;
        }
        return originalCreateElement.call(document, tagName);
    };

    console.log('[ULTIMATE KILLER] All modal operations completely disabled');
})();