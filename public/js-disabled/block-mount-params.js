/**
 * Block MountParamsContainer Requests
 * Prevents the problematic lazy loading requests
 */
(function() {
    'use strict';
    
    console.log('🚫 Blocking MountParamsContainer requests');
    
    // Override fetch to intercept requests
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        const [url, options] = args;
        
        // Check if this is a Livewire request
        if (url && url.includes('livewire/update')) {
            // Check the body for __mountParamsContainer
            if (options && options.body) {
                try {
                    const bodyStr = typeof options.body === 'string' ? options.body : options.body.toString();
                    if (bodyStr.includes('__mountParamsContainer') || bodyStr.includes('mountParamsContainer')) {
                        console.warn('Blocked MountParamsContainer request');
                        // Return a fake successful response
                        return Promise.resolve(new Response(JSON.stringify({
                            effects: {
                                html: '',
                                dirty: []
                            },
                            serverMemo: {
                                data: {},
                                checksum: ''
                            }
                        }), {
                            status: 200,
                            headers: { 'Content-Type': 'application/json' }
                        }));
                    }
                } catch (e) {
                    // Ignore parsing errors
                }
            }
        }
        
        // Call original fetch for other requests
        return originalFetch.apply(this, args);
    };
    
    // Also try to prevent the lazy loading calls
    if (window.Alpine) {
        const originalWireProxy = window.Alpine.magic('wire');
        if (originalWireProxy) {
            window.Alpine.magic('wire', () => {
                return new Proxy({}, {
                    get(target, property) {
                        if (property === '__lazyLoad') {
                            return function(data) {
                                // Decode the base64 data to check
                                try {
                                    const decoded = atob(data);
                                    if (decoded.includes('__mountParamsContainer')) {
                                        console.warn('Blocked __lazyLoad for MountParamsContainer');
                                        return Promise.resolve();
                                    }
                                } catch (e) {
                                    // Ignore decode errors
                                }
                                // Call original for other components
                                const wire = originalWireProxy();
                                return wire[property] ? wire[property](data) : undefined;
                            };
                        }
                        const wire = originalWireProxy();
                        return wire[property];
                    }
                });
            });
        }
    }
    
    // Block at document level too
    document.addEventListener('alpine:init', () => {
        // Override $wire.__lazyLoad globally
        const checkAndBlock = () => {
            const elements = document.querySelectorAll('[wire\\:id]');
            elements.forEach(el => {
                if (el.__x && el.__x.$wire && el.__x.$wire.__lazyLoad) {
                    const original = el.__x.$wire.__lazyLoad;
                    el.__x.$wire.__lazyLoad = function(data) {
                        try {
                            const decoded = atob(data);
                            if (decoded.includes('__mountParamsContainer')) {
                                console.warn('Blocked component-level __lazyLoad');
                                return Promise.resolve();
                            }
                        } catch (e) {}
                        return original.call(this, data);
                    };
                }
            });
        };
        
        checkAndBlock();
        // Check again after DOM updates
        setTimeout(checkAndBlock, 1000);
    });
})();