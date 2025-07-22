/**
 * Livewire Configuration Fix
 * Disables problematic lazy loading for specific components
 */

// This must run before Livewire initializes
(function() {
    'use strict';
    
    // Override Livewire configuration before it loads
    window.livewireScriptConfig = window.livewireScriptConfig || {};
    
    // Hook into Livewire's initialization
    const originalLivewire = window.Livewire;
    Object.defineProperty(window, 'Livewire', {
        get() {
            return originalLivewire;
        },
        set(value) {
            // Livewire is being initialized
            console.log('⚙️ Configuring Livewire to block problematic components');
            
            // Store original
            window._originalLivewire = value;
            
            // Create proxy
            const LivewireProxy = new Proxy(value, {
                get(target, prop) {
                    if (prop === 'hook') {
                        return function(hookName, callback) {
                            if (hookName === 'request') {
                                // Wrap the callback to intercept requests
                                const wrappedCallback = function(options) {
                                    // Check if this is SPECIFICALLY the problematic mountParamsContainer request
                                    if (options.payload && options.payload.fingerprint) {
                                        const fp = options.payload.fingerprint;
                                        // Only block if it's __mountParamsContainer AND has no valid component path
                                        if (fp.name === '__mountParamsContainer' && !fp.path) {
                                            console.warn('Blocking problematic __mountParamsContainer request');
                                            // Call fail immediately
                                            if (options.fail) {
                                                options.fail({ status: 0, response: { message: 'Request blocked' } });
                                            }
                                            return;
                                        }
                                    }
                                    // Call original callback
                                    return callback.call(this, options);
                                };
                                return target.hook.call(target, hookName, wrappedCallback);
                            }
                            return target.hook.call(target, hookName, callback);
                        };
                    }
                    return target[prop];
                }
            });
            
            // Set the proxy
            Object.defineProperty(window, 'Livewire', {
                value: LivewireProxy,
                writable: true,
                configurable: true
            });
        },
        configurable: true
    });
})();

// Also block at Alpine level
document.addEventListener('alpine:init', () => {
    console.log('🔧 Patching Alpine $wire for problematic components');
    
    // Override the $wire magic
    const originalWireMagic = Alpine.magic('wire');
    Alpine.magic('wire', (el) => {
        const wire = originalWireMagic(el);
        
        // Wrap __lazyLoad
        if (wire && wire.__lazyLoad) {
            const originalLazyLoad = wire.__lazyLoad;
            wire.__lazyLoad = function(data) {
                try {
                    const decoded = atob(data);
                    if (decoded.includes('__mountParamsContainer')) {
                        console.warn('Blocked __lazyLoad for __mountParamsContainer');
                        return Promise.resolve({});
                    }
                } catch (e) {
                    // Ignore decode errors
                }
                return originalLazyLoad.call(this, data);
            };
        }
        
        return wire;
    });
});

// Emergency fallback - prevent any lazy load calls
window.addEventListener('load', () => {
    // Find all Alpine components and patch them
    document.querySelectorAll('[x-data]').forEach(el => {
        if (el._x_dataStack) {
            el._x_dataStack.forEach(data => {
                if (data.$wire && data.$wire.__lazyLoad) {
                    const original = data.$wire.__lazyLoad;
                    data.$wire.__lazyLoad = function(payload) {
                        try {
                            const decoded = atob(payload);
                            if (decoded.includes('__mountParamsContainer')) {
                                console.warn('Component-level block of __mountParamsContainer');
                                return Promise.resolve({});
                            }
                        } catch (e) {}
                        return original.call(this, payload);
                    };
                }
            });
        }
    });
});

console.log('🚧 Livewire configuration fix loaded - problematic components will be blocked');