// Fix Alpine.js and Livewire initialization issues
(function() {
    'use strict';
    
    //console.log('[Fix Alpine/Livewire] Initializing fixes...');
    
    // Problem 1: Alpine components not initializing
    function initializeAlpineComponents() {
        if (!window.Alpine) {
            console.error('[Fix] Alpine not loaded yet, retrying...');
            setTimeout(initializeAlpineComponents, 100);
            return;
        }
        
        //console.log('[Fix] Checking Alpine components...');
        
        // Find all x-data components
        const components = document.querySelectorAll('[x-data]:not([data-alpine-initialized])');
        let initialized = 0;
        
        components.forEach(el => {
            if (!el.__x) {
                try {
                    // Mark as being initialized to prevent double init
                    el.setAttribute('data-alpine-initialized', 'true');
                    
                    // Initialize the component
                    window.Alpine.initTree(el);
                    initialized++;
                    //console.log('[Fix] Initialized Alpine component:', el);
                } catch (e) {
                    console.error('[Fix] Failed to initialize Alpine component:', el, e);
                    el.removeAttribute('data-alpine-initialized');
                }
            }
        });
        
        if (initialized > 0) {
            //console.log(`[Fix] Initialized ${initialized} Alpine components`);
        }
    }
    
    // Problem 2: Livewire components not connecting
    function ensureLivewireConnection() {
        if (!window.Livewire) {
            console.error('[Fix] Livewire not loaded yet, retrying...');
            setTimeout(ensureLivewireConnection, 100);
            return;
        }
        
        //console.log('[Fix] Checking Livewire components...');
        
        // Find all Livewire components
        const components = document.querySelectorAll('[wire\\:id]');
        let connected = 0;
        
        components.forEach(el => {
            const id = el.getAttribute('wire:id');
            const component = window.Livewire.find(id);
            
            if (!component) {
                console.warn('[Fix] Livewire component not found:', id);
                // Try to reconnect
                try {
                    window.Livewire.rescan();
                    connected++;
                } catch (e) {
                    console.error('[Fix] Failed to reconnect Livewire component:', id, e);
                }
            } else {
                // Component exists, ensure events are bound
                ensureWireEvents(el);
            }
        });
        
        if (connected > 0) {
            //console.log(`[Fix] Reconnected ${connected} Livewire components`);
        }
    }
    
    // Problem 3: wire:click and wire:model not working
    function ensureWireEvents(element) {
        // Fix wire:click
        element.querySelectorAll('[wire\\:click]').forEach(el => {
            if (!el.hasAttribute('data-wire-click-fixed')) {
                el.setAttribute('data-wire-click-fixed', 'true');
                
                // Ensure element is clickable
                el.style.cursor = 'pointer';
                el.style.pointerEvents = 'auto';
                
                // Add tabindex if missing
                if (!el.hasAttribute('tabindex') && (el.tagName === 'DIV' || el.tagName === 'SPAN')) {
                    el.setAttribute('tabindex', '0');
                }
                
                //console.log('[Fix] Fixed wire:click on:', el);
            }
        });
        
        // Fix wire:model
        element.querySelectorAll('[wire\\:model]').forEach(el => {
            if (!el.hasAttribute('data-wire-model-fixed')) {
                el.setAttribute('data-wire-model-fixed', 'true');
                
                // Ensure element is not disabled
                if (el.hasAttribute('disabled') && !el.hasAttribute('data-intended-disabled')) {
                    el.removeAttribute('disabled');
                    //console.log('[Fix] Enabled wire:model element:', el);
                }
            }
        });
    }
    
    // Problem 4: Statistics boxes not updating
    function fixStatisticsBoxes() {
        //console.log('[Fix] Checking statistics boxes...');
        
        // Find all stat value elements that might be empty
        const statElements = document.querySelectorAll('.fi-stats-overview-stat-value, [wire\\:poll]');
        
        statElements.forEach(el => {
            // Check if element is empty or shows placeholder
            if (el.textContent.trim() === '' || el.textContent.trim() === '-') {
                //console.log('[Fix] Found empty stat element:', el);
                
                // If it has wire:poll, try to trigger an update
                const component = el.closest('[wire\\:id]');
                if (component) {
                    const id = component.getAttribute('wire:id');
                    const livewireComponent = window.Livewire?.find(id);
                    if (livewireComponent) {
                        //console.log('[Fix] Triggering refresh for component:', id);
                        livewireComponent.$refresh();
                    }
                }
            }
        });
    }
    
    // Problem 5: Dropdowns not opening due to Alpine issues
    function fixDropdowns() {
        //console.log('[Fix] Fixing dropdowns...');
        
        document.querySelectorAll('.fi-dropdown').forEach(dropdown => {
            const trigger = dropdown.querySelector('button[aria-haspopup="true"]');
            const panel = dropdown.querySelector('.fi-dropdown-panel');
            
            if (trigger && panel && !trigger.hasAttribute('data-dropdown-fixed')) {
                trigger.setAttribute('data-dropdown-fixed', 'true');
                
                // Ensure Alpine data is set
                if (!dropdown.hasAttribute('x-data') && !dropdown.__x) {
                    dropdown.setAttribute('x-data', '{ open: false }');
                    if (window.Alpine) {
                        window.Alpine.initTree(dropdown);
                    }
                }
                
                //console.log('[Fix] Fixed dropdown:', dropdown);
            }
        });
    }
    
    // Run fixes when DOM is ready
    function runAllFixes() {
        //console.log('[Fix] Running all fixes...');
        initializeAlpineComponents();
        ensureLivewireConnection();
        fixStatisticsBoxes();
        fixDropdowns();
    }
    
    // Wait for both Alpine and Livewire to be ready
    function waitForFrameworks(callback) {
        let attempts = 0;
        const maxAttempts = 50; // 5 seconds
        
        const checkInterval = setInterval(() => {
            attempts++;
            
            if (window.Alpine && window.Livewire) {
                clearInterval(checkInterval);
                //console.log('[Fix] Both frameworks detected');
                callback();
            } else if (attempts >= maxAttempts) {
                clearInterval(checkInterval);
                console.error('[Fix] Timeout waiting for frameworks');
                console.error('Alpine loaded:', !!window.Alpine);
                console.error('Livewire loaded:', !!window.Livewire);
            } else {
                if (attempts % 10 === 0) {
                    //console.log('[Fix] Still waiting for frameworks...', {
                        alpine: !!window.Alpine,
                        livewire: !!window.Livewire
                    });
                }
            }
        }, 100);
    }
    
    // Start the fix process
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            waitForFrameworks(runAllFixes);
        });
    } else {
        waitForFrameworks(runAllFixes);
    }
    
    // Re-run fixes after Livewire navigations
    document.addEventListener('livewire:navigated', () => {
        //console.log('[Fix] Livewire navigation detected, re-running fixes...');
        setTimeout(runAllFixes, 100);
    });
    
    // Re-run fixes after Livewire updates
    if (window.Livewire) {
        window.Livewire.hook('message.processed', () => {
            setTimeout(() => {
                initializeAlpineComponents();
                fixDropdowns();
            }, 50);
        });
    }
    
    // Expose fix functions for manual testing
    window.alpineLivewireFix = {
        runAll: runAllFixes,
        initAlpine: initializeAlpineComponents,
        fixLivewire: ensureLivewireConnection,
        fixStats: fixStatisticsBoxes,
        fixDropdowns: fixDropdowns,
        status: () => {
            //console.log('=== Framework Status ===');
            //console.log('Alpine loaded:', !!window.Alpine);
            //console.log('Livewire loaded:', !!window.Livewire);
            //console.log('Alpine components:', document.querySelectorAll('[x-data]').length);
            //console.log('- Initialized:', document.querySelectorAll('[x-data][data-alpine-initialized]').length);
            //console.log('Livewire components:', document.querySelectorAll('[wire\\:id]').length);
            //console.log('Wire:click elements:', document.querySelectorAll('[wire\\:click]').length);
            //console.log('Wire:model elements:', document.querySelectorAll('[wire\\:model]').length);
            //console.log('Statistics boxes:', document.querySelectorAll('.fi-stats-overview-stat-value').length);
            //console.log('Dropdowns:', document.querySelectorAll('.fi-dropdown').length);
        }
    };
    
    //console.log('[Fix] Fix script loaded. Use window.alpineLivewireFix.status() for diagnostics.');
})();