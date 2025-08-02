/**
 * Wizard Dropdown Fix for Filament
 * Fixes dropdown behavior in multi-step wizards and forms
 */

(function() {
    'use strict';
    
    // Fix for wizard step navigation dropdowns
    const fixWizardDropdowns = () => {
        // Fix Alpine.js dropdown state issues
        document.querySelectorAll('[x-data*="wizard"]').forEach(wizard => {
            const dropdowns = wizard.querySelectorAll('[x-data*="dropdown"], [x-data*="select"]');
            
            dropdowns.forEach(dropdown => {
                // Ensure dropdown state is preserved during wizard steps
                if (!dropdown.hasAttribute('data-wizard-fixed')) {
                    dropdown.setAttribute('data-wizard-fixed', 'true');
                    
                    // Preserve dropdown state on step change
                    dropdown.addEventListener('change', function(e) {
                        const value = e.target.value;
                        e.target.setAttribute('data-preserved-value', value);
                    });
                }
            });
        });
    };
    
    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixWizardDropdowns);
    } else {
        fixWizardDropdowns();
    }
    
    // Re-apply fixes after Livewire updates
    if (window.Livewire) {
        window.Livewire.hook('message.processed', (message, component) => {
            setTimeout(fixWizardDropdowns, 50);
        });
        
        // Fix for wizard step transitions
        window.Livewire.hook('element.updated', (el, component) => {
            if (el.hasAttribute('data-preserved-value')) {
                el.value = el.getAttribute('data-preserved-value');
            }
        });
    }
    
    console.log('Wizard Dropdown Fix v3.3.14.0 loaded');
})();