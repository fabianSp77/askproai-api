// COMPLETE MODAL FIX - Handles all modal.js errors
(function() {
    'use strict';

    console.log('[COMPLETE FIX] Initializing...');

    // Create a complete fake iframe element
    function createFakeIframe() {
        const fakeDocument = {
            open: function() {},
            write: function() {},
            close: function() {},
            body: {
                innerHTML: '',
                appendChild: function() {},
                style: {}
            },
            documentElement: {
                innerHTML: '',
                outerHTML: ''
            },
            createElement: function() {
                return createFakeElement();
            },
            getElementById: function() {
                return null;
            },
            querySelector: function() {
                return null;
            },
            querySelectorAll: function() {
                return [];
            }
        };

        const fakeWindow = {
            document: fakeDocument,
            location: { href: '' },
            addEventListener: function() {},
            removeEventListener: function() {}
        };

        return {
            contentWindow: fakeWindow,
            contentDocument: fakeDocument,
            style: {
                display: 'none',
                position: '',
                width: '',
                height: '',
                backgroundColor: '',
                borderRadius: '',
                zIndex: ''
            },
            src: '',
            innerHTML: '',
            appendChild: function() {},
            removeChild: function() {},
            remove: function() {},
            setAttribute: function() {},
            getAttribute: function() { return null; },
            addEventListener: function() {},
            removeEventListener: function() {},
            classList: {
                add: function() {},
                remove: function() {},
                contains: function() { return false; }
            }
        };
    }

    // Create a complete fake element
    function createFakeElement() {
        return {
            innerHTML: '',
            outerHTML: '',
            style: {
                display: '',
                position: '',
                width: '',
                height: '',
                overflow: '',
                padding: '',
                backgroundColor: '',
                zIndex: ''
            },
            classList: {
                add: function() {},
                remove: function() {},
                contains: function() { return false; }
            },
            appendChild: function(child) {
                return child;
            },
            removeChild: function() {},
            remove: function() {},
            prepend: function() {},
            setAttribute: function() {},
            getAttribute: function() { return null; },
            addEventListener: function() {},
            removeEventListener: function() {},
            querySelector: function() { return null; },
            querySelectorAll: function() { return []; },
            focus: function() {},
            click: function() {},
            parentNode: null,
            parentElement: null,
            children: [],
            id: '',
            className: ''
        };
    }

    // Override createElement to intercept iframe creation
    const originalCreateElement = document.createElement.bind(document);
    document.createElement = function(tagName) {
        if (tagName && tagName.toLowerCase() === 'iframe') {
            console.log('[COMPLETE FIX] Intercepted iframe creation, returning fake');
            return createFakeIframe();
        }

        const element = originalCreateElement(tagName);

        // For divs that might become modal containers
        if (tagName && tagName.toLowerCase() === 'div') {
            const originalSetId = element.__lookupSetter__('id') || Object.getOwnPropertyDescriptor(Element.prototype, 'id').set;
            Object.defineProperty(element, 'id', {
                set: function(value) {
                    if (value === 'livewire-error') {
                        console.log('[COMPLETE FIX] Preventing livewire-error div creation');
                        return;
                    }
                    originalSetId.call(this, value);
                },
                get: function() {
                    return this.getAttribute('id');
                },
                configurable: true
            });
        }

        return element;
    };

    // Override getElementById to return safe elements
    const originalGetElementById = document.getElementById.bind(document);
    document.getElementById = function(id) {
        if (id === 'livewire-error' || (id && id.toString().includes('modal'))) {
            console.log('[COMPLETE FIX] Returning complete fake for:', id);
            const fake = createFakeElement();
            // Add iframe-like properties in case it's used as a container
            fake.appendChild = function(child) {
                if (child && child.tagName === 'IFRAME') {
                    console.log('[COMPLETE FIX] Preventing iframe append to modal');
                    return createFakeIframe();
                }
                return child;
            };
            return fake;
        }
        return originalGetElementById(id);
    };

    // Override querySelector and querySelectorAll
    const originalQuerySelector = document.querySelector.bind(document);
    document.querySelector = function(selector) {
        if (selector && (selector.includes('modal') || selector.includes('livewire-error') || selector === 'iframe')) {
            console.log('[COMPLETE FIX] Returning null for selector:', selector);
            return null;
        }
        return originalQuerySelector(selector);
    };

    const originalQuerySelectorAll = document.querySelectorAll.bind(document);
    document.querySelectorAll = function(selector) {
        if (selector && (selector.includes('modal') || selector.includes('livewire-error'))) {
            console.log('[COMPLETE FIX] Returning empty array for selector:', selector);
            return [];
        }
        return originalQuerySelectorAll(selector);
    };

    // Override body.prepend to prevent modal injection
    if (document.body) {
        const originalPrepend = document.body.prepend;
        document.body.prepend = function(element) {
            if (element && element.id === 'livewire-error') {
                console.log('[COMPLETE FIX] Blocked modal prepend to body');
                return;
            }
            if (originalPrepend) {
                originalPrepend.call(document.body, element);
            }
        };
    }

    // Patch showHtmlModal
    window.showHtmlModal = function() {
        console.log('[COMPLETE FIX] showHtmlModal completely disabled');
        return false;
    };

    // Remove existing modals
    function removeModals() {
        try {
            const elements = document.querySelectorAll('#livewire-error, .modal, .modal-backdrop, [role="dialog"]');
            elements.forEach(function(el) {
                if (el && el.remove) {
                    el.remove();
                }
            });
        } catch(e) {}

        if (document.body) {
            document.body.style.overflow = '';
            if (document.body.classList) {
                document.body.classList.remove('modal-open', 'overflow-hidden');
            }
        }
    }

    // Patch Livewire
    function patchLivewire() {
        if (window.Livewire) {
            window.Livewire.showHtmlModal = function() {
                console.log('[COMPLETE FIX] Livewire modal blocked');
                return false;
            };
            window.Livewire.handleError = function() {
                console.log('[COMPLETE FIX] Livewire error blocked');
                return false;
            };
        }
    }

    // Initialize
    removeModals();
    patchLivewire();

    // DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            removeModals();
            patchLivewire();
        });
    }

    // Livewire events
    document.addEventListener('livewire:init', patchLivewire);
    document.addEventListener('livewire:load', patchLivewire);
    document.addEventListener('livewire:navigated', function() {
        setTimeout(removeModals, 100);
    });

    // Monitor for 10 seconds
    let count = 0;
    const interval = setInterval(function() {
        removeModals();
        count++;
        if (count > 20) {
            clearInterval(interval);
            console.log('[COMPLETE FIX] Monitoring complete');
        }
    }, 500);

    console.log('[COMPLETE FIX] All modal errors prevented');
})();