// Debug Alpine.js and Livewire initialization
(function() {
    'use strict';
    
    console.log('[Debug Alpine/Livewire] Starting diagnostic...');
    
    // Check if scripts are loaded
    function checkScriptStatus() {
        const status = {
            alpine: {
                loaded: typeof window.Alpine !== 'undefined',
                version: window.Alpine?.version || 'not loaded',
                started: window.Alpine?.started || false
            },
            livewire: {
                loaded: typeof window.Livewire !== 'undefined',
                version: window.Livewire?.version || 'not loaded',
                components: window.Livewire ? Object.keys(window.Livewire.components || {}).length : 0
            },
            timestamp: new Date().toISOString()
        };
        
        console.log('=== Script Status ===', status);
        return status;
    }
    
    // Monitor Alpine initialization
    if (typeof window.Alpine === 'undefined') {
        console.error('[Debug] Alpine is NOT loaded!');
        
        // Try to detect when Alpine loads
        let alpineCheckInterval = setInterval(() => {
            if (typeof window.Alpine !== 'undefined') {
                console.log('[Debug] Alpine detected!', window.Alpine);
                clearInterval(alpineCheckInterval);
                initAlpineDebug();
            }
        }, 100);
        
        // Stop checking after 5 seconds
        setTimeout(() => clearInterval(alpineCheckInterval), 5000);
    } else {
        console.log('[Debug] Alpine already loaded');
        initAlpineDebug();
    }
    
    function initAlpineDebug() {
        // Check Alpine components
        console.log('[Debug] Alpine components:', document.querySelectorAll('[x-data]').length);
        
        // Monitor Alpine lifecycle
        document.addEventListener('alpine:init', () => {
            console.log('[Debug] Alpine:init event fired');
        });
        
        document.addEventListener('alpine:initialized', () => {
            console.log('[Debug] Alpine:initialized event fired');
            
            // Check components again
            const components = document.querySelectorAll('[x-data]');
            console.log(`[Debug] Found ${components.length} Alpine components`);
            
            // Check if components are initialized
            components.forEach((el, index) => {
                if (el.__x) {
                    console.log(`[Debug] Component ${index} initialized:`, el.__x.$data);
                } else {
                    console.warn(`[Debug] Component ${index} NOT initialized:`, el);
                }
            });
        });
    }
    
    // Monitor Livewire initialization
    if (typeof window.Livewire === 'undefined') {
        console.error('[Debug] Livewire is NOT loaded!');
        
        // Try to detect when Livewire loads
        let livewireCheckInterval = setInterval(() => {
            if (typeof window.Livewire !== 'undefined') {
                console.log('[Debug] Livewire detected!', window.Livewire);
                clearInterval(livewireCheckInterval);
                initLivewireDebug();
            }
        }, 100);
        
        // Stop checking after 5 seconds
        setTimeout(() => clearInterval(livewireCheckInterval), 5000);
    } else {
        console.log('[Debug] Livewire already loaded');
        initLivewireDebug();
    }
    
    function initLivewireDebug() {
        // Hook into Livewire lifecycle
        if (window.Livewire.hook) {
            window.Livewire.hook('message.sent', (message, component) => {
                console.log('[Debug] Livewire message sent:', message);
            });
            
            window.Livewire.hook('message.received', (message, component) => {
                console.log('[Debug] Livewire message received:', message);
            });
            
            window.Livewire.hook('message.processed', (message, component) => {
                console.log('[Debug] Livewire message processed:', message);
            });
        }
        
        // Check wire:click elements
        const wireClickElements = document.querySelectorAll('[wire\\:click]');
        console.log(`[Debug] Found ${wireClickElements.length} wire:click elements`);
        
        // Check wire:model elements
        const wireModelElements = document.querySelectorAll('[wire\\:model]');
        console.log(`[Debug] Found ${wireModelElements.length} wire:model elements`);
        
        // Check Livewire components
        const livewireComponents = document.querySelectorAll('[wire\\:id]');
        console.log(`[Debug] Found ${livewireComponents.length} Livewire components`);
        
        // List component IDs
        livewireComponents.forEach((el, index) => {
            const id = el.getAttribute('wire:id');
            const component = window.Livewire.find(id);
            console.log(`[Debug] Component ${index}: ID=${id}, Found=${!!component}`);
        });
    }
    
    // Check script loading order
    console.log('[Debug] Script loading order check:');
    console.log('- document.readyState:', document.readyState);
    console.log('- Scripts in head:', document.querySelectorAll('head script').length);
    console.log('- Scripts in body:', document.querySelectorAll('body script').length);
    
    // Monitor DOM ready states
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            console.log('[Debug] DOMContentLoaded fired');
            checkScriptStatus();
        });
    }
    
    window.addEventListener('load', () => {
        console.log('[Debug] Window load event fired');
        checkScriptStatus();
        
        // Final check after everything should be loaded
        setTimeout(() => {
            console.log('[Debug] Final status check:');
            const finalStatus = checkScriptStatus();
            
            if (!finalStatus.alpine.loaded || !finalStatus.livewire.loaded) {
                console.error('[Debug] CRITICAL: Scripts not loaded properly!');
                console.error('Alpine loaded:', finalStatus.alpine.loaded);
                console.error('Livewire loaded:', finalStatus.livewire.loaded);
                
                // Check for error messages
                const errors = document.querySelectorAll('.error, [data-error]');
                if (errors.length > 0) {
                    console.error('[Debug] Found error elements:', errors);
                }
            }
        }, 1000);
    });
    
    // Expose debug functions
    window.alpineLivewireDebug = {
        status: checkScriptStatus,
        checkAlpine: () => {
            const components = document.querySelectorAll('[x-data]');
            console.log(`Found ${components.length} Alpine components`);
            components.forEach((el, i) => {
                console.log(`Component ${i}:`, {
                    element: el,
                    initialized: !!el.__x,
                    data: el.__x?.$data || 'not initialized'
                });
            });
        },
        checkLivewire: () => {
            const components = document.querySelectorAll('[wire\\:id]');
            console.log(`Found ${components.length} Livewire components`);
            components.forEach((el, i) => {
                const id = el.getAttribute('wire:id');
                const component = window.Livewire?.find(id);
                console.log(`Component ${i}:`, {
                    id: id,
                    element: el,
                    found: !!component,
                    data: component?.$wire || 'not found'
                });
            });
        },
        reinitAlpine: () => {
            if (window.Alpine) {
                console.log('[Debug] Attempting to reinitialize Alpine components...');
                document.querySelectorAll('[x-data]').forEach(el => {
                    if (!el.__x) {
                        try {
                            window.Alpine.initTree(el);
                            console.log('[Debug] Initialized:', el);
                        } catch (e) {
                            console.error('[Debug] Failed to initialize:', el, e);
                        }
                    }
                });
            }
        }
    };
    
    console.log('[Debug] Debug script loaded. Use window.alpineLivewireDebug for manual debugging.');
})();