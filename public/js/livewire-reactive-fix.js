/**
 * Livewire Reactive Fix
 * Behebt Probleme mit reactive() Filament-Komponenten
 */
(function() {
    'use strict';
    
    console.log('🔧 Livewire Reactive Fix Active');
    
    // Override Livewire's model update to ensure reactive callbacks fire
    if (window.Livewire) {
        const originalSet = Livewire.hook;
        
        // Hook into Livewire's data binding
        Livewire.hook('element.updated', (el, component) => {
            // Check if this is a reactive form element
            if (el.hasAttribute('wire:model') || el.hasAttribute('wire:model.defer')) {
                const modelName = el.getAttribute('wire:model') || el.getAttribute('wire:model.defer');
                console.log('Reactive element updated:', {
                    model: modelName,
                    value: el.value,
                    type: el.type
                });
                
                // Special handling for radio buttons in toggle groups
                if (el.type === 'radio' && el.checked) {
                    console.log('Radio button selected, ensuring state update');
                    
                    // Find the Filament form container
                    const formContainer = el.closest('[x-data]');
                    if (formContainer && window.Alpine) {
                        const alpineData = Alpine.$data(formContainer);
                        if (alpineData && alpineData.$wire) {
                            console.log('Triggering Alpine/Livewire sync');
                            // Force a sync
                            Alpine.nextTick(() => {
                                if (typeof alpineData.$wire.call === 'function') {
                                    console.log('Calling $refresh to ensure reactive updates');
                                    alpineData.$wire.call('$refresh');
                                }
                            });
                        }
                    }
                }
            }
        });
        
        // Monitor for setup_mode changes specifically
        document.addEventListener('change', function(e) {
            const target = e.target;
            
            // Check if this is the setup_mode radio
            if (target.name === 'data.setup_mode' || target.name === 'setup_mode') {
                console.log('Setup mode changed to:', target.value);
                
                if (target.value === 'edit') {
                    console.log('Edit mode selected, checking for company selector visibility');
                    
                    // Give Livewire time to process
                    setTimeout(() => {
                        const companySelector = document.querySelector('[name="data.selected_company"], [name="selected_company"]');
                        if (companySelector) {
                            console.log('Company selector found:', companySelector);
                            // Make sure it's visible
                            const selectorContainer = companySelector.closest('.filament-forms-field-wrapper');
                            if (selectorContainer) {
                                console.log('Selector container visibility:', {
                                    display: getComputedStyle(selectorContainer).display,
                                    visibility: getComputedStyle(selectorContainer).visibility
                                });
                            }
                        } else {
                            console.warn('Company selector not found after selecting edit mode');
                        }
                    }, 500);
                }
            }
        });
    }
    
    // Ensure Filament's reactive() actually works
    function ensureReactivity() {
        // Find all elements with wire:model that should be reactive
        const reactiveElements = document.querySelectorAll('[wire\\:model][x-on\\:change], [wire\\:model\\.defer][x-on\\:change]');
        
        reactiveElements.forEach(element => {
            console.log('Found reactive element:', {
                model: element.getAttribute('wire:model') || element.getAttribute('wire:model.defer'),
                hasChangeHandler: element.hasAttribute('x-on:change')
            });
        });
    }
    
    // Run on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', ensureReactivity);
    } else {
        ensureReactivity();
    }
    
    // Re-run after Livewire navigation
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            console.log('Checking reactivity after Livewire update');
            ensureReactivity();
        });
    }
})();