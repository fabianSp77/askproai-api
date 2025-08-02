// Filament Select Component Fix
(function() {
    'use strict';
    
    //console.log('[Filament Select Fix] Initializing...');
    
    let fixApplied = false;
    
    // Fix Filament Select components
    function fixFilamentSelects() {
        if (fixApplied) return;
        
        //console.log('[Filament Select Fix] Applying fixes...');
        
        // Find all Filament form components with selects
        document.querySelectorAll('.fi-fo-field-wrp').forEach(fieldWrapper => {
            const select = fieldWrapper.querySelector('select[wire\\\\:model], select[x-model]');
            if (!select) return;
            
            //console.log('[Filament Select Fix] Found select field:', select);
            
            // Check if it's a reactive select
            const wireModel = select.getAttribute('wire:model');
            const xModel = select.getAttribute('x-model');
            
            if (wireModel) {
                // Ensure Livewire bindings work
                select.addEventListener('change', function(e) {
                    //console.log('[Filament Select Fix] Select changed:', e.target.value);
                    
                    // Force Livewire update if needed
                    if (window.Livewire) {
                        const component = Livewire.find(select.closest('[wire\\\\:id]')?.getAttribute('wire:id'));
                        if (component) {
                            const modelName = wireModel.replace('defer', '').replace('lazy', '').trim();
                            component.set(modelName, e.target.value);
                            //console.log('[Filament Select Fix] Updated Livewire model:', modelName, e.target.value);
                        }
                    }
                });
            }
        });
        
        // Fix Select2/Choices.js components
        fixEnhancedSelects();
        
        fixApplied = true;
    }
    
    // Fix enhanced select libraries (Select2, Choices.js, etc.)
    function fixEnhancedSelects() {
        // Wait a bit for enhanced selects to initialize
        setTimeout(() => {
            // Fix Select2
            if (window.jQuery && window.jQuery.fn.select2) {
                jQuery('select').on('select2:select', function(e) {
                    const wireModel = this.getAttribute('wire:model');
                    if (wireModel && window.Livewire) {
                        const component = Livewire.find(this.closest('[wire\\\\:id]')?.getAttribute('wire:id'));
                        if (component) {
                            component.set(wireModel, e.params.data.id);
                            //console.log('[Filament Select Fix] Select2 updated:', wireModel, e.params.data.id);
                        }
                    }
                });
            }
            
            // Fix Choices.js
            document.querySelectorAll('.choices__input').forEach(choicesElement => {
                const selectElement = choicesElement.closest('.choices')?.querySelector('select');
                if (selectElement) {
                    const wireModel = selectElement.getAttribute('wire:model');
                    if (wireModel) {
                        choicesElement.addEventListener('change', function() {
                            if (window.Livewire) {
                                const component = Livewire.find(selectElement.closest('[wire\\\\:id]')?.getAttribute('wire:id'));
                                if (component) {
                                    component.set(wireModel, selectElement.value);
                                    //console.log('[Filament Select Fix] Choices.js updated:', wireModel, selectElement.value);
                                }
                            }
                        });
                    }
                }
            });
        }, 1000);
    }
    
    // Monitor for Livewire updates
    function setupLivewireHooks() {
        if (!window.Livewire) {
            setTimeout(setupLivewireHooks, 100);
            return;
        }
        
        //console.log('[Filament Select Fix] Setting up Livewire hooks...');
        
        // Re-apply fixes after Livewire updates
        Livewire.hook('message.processed', (message, component) => {
            //console.log('[Filament Select Fix] Livewire message processed');
            setTimeout(() => {
                fixApplied = false;
                fixFilamentSelects();
            }, 100);
        });
        
        // Fix after morphdom updates
        Livewire.hook('element.updated', (el, component) => {
            if (el.tagName === 'SELECT') {
                //console.log('[Filament Select Fix] Select element updated:', el);
                setTimeout(() => {
                    fixApplied = false;
                    fixFilamentSelects();
                }, 50);
            }
        });
    }
    
    // Initialize when DOM is ready
    function init() {
        //console.log('[Filament Select Fix] DOM ready, initializing...');
        
        // Initial fix
        fixFilamentSelects();
        
        // Setup Livewire hooks
        setupLivewireHooks();
        
        // Re-apply on navigation
        document.addEventListener('livewire:navigated', () => {
            //console.log('[Filament Select Fix] Navigation detected');
            fixApplied = false;
            fixFilamentSelects();
        });
        
        // Monitor DOM changes
        const observer = new MutationObserver((mutations) => {
            let hasSelectChanges = false;
            
            mutations.forEach(mutation => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(node => {
                        if (node.nodeType === 1) {
                            if (node.tagName === 'SELECT' || node.querySelector?.('select')) {
                                hasSelectChanges = true;
                            }
                        }
                    });
                }
            });
            
            if (hasSelectChanges) {
                //console.log('[Filament Select Fix] New selects detected');
                fixApplied = false;
                setTimeout(fixFilamentSelects, 100);
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
    // Export for debugging
    window.filamentSelectFix = {
        fix: fixFilamentSelects,
        debug: function() {
            const selects = document.querySelectorAll('select[wire\\\\:model], select[x-model]');
            //console.log(`[Filament Select Fix] Found ${selects.length} select elements`);
            
            selects.forEach((select, i) => {
                //console.log(`Select ${i + 1}:`, {
                    element: select,
                    wireModel: select.getAttribute('wire:model'),
                    xModel: select.getAttribute('x-model'),
                    value: select.value,
                    options: Array.from(select.options).map(o => ({ value: o.value, text: o.text }))
                });
            });
        }
    };
    
    //console.log('[Filament Select Fix] Loaded successfully');
})();