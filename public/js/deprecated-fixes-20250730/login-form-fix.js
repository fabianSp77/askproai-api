/**
 * Login Form Fix - Ensures form submission works correctly
 */
(function() {
    'use strict';
    
    console.log('[LoginFormFix] Starting...');
    
    function ensureFormSubmission() {
        // Find the login form
        const forms = document.querySelectorAll('form[wire\\:submit="authenticate"], #form');
        
        forms.forEach(form => {
            if (!form.dataset.loginFormFixed) {
                form.dataset.loginFormFixed = 'true';
                
                console.log('[LoginFormFix] Processing form:', form);
                
                // Find the submit button
                const submitButton = form.querySelector('button[type="submit"]');
                
                if (submitButton) {
                    // Remove all existing handlers that might interfere
                    const newButton = submitButton.cloneNode(true);
                    submitButton.parentNode.replaceChild(newButton, submitButton);
                    
                    // Ensure button is visible and styled
                    newButton.style.cssText = `
                        background-color: rgb(251, 191, 36) !important;
                        color: rgb(0, 0, 0) !important;
                        border: 1px solid rgb(217, 119, 6) !important;
                        opacity: 1 !important;
                        visibility: visible !important;
                        pointer-events: auto !important;
                        cursor: pointer !important;
                    `;
                    
                    // Ensure label is visible
                    const label = newButton.querySelector('.fi-btn-label');
                    if (label) {
                        label.style.color = 'rgb(0, 0, 0)';
                        label.style.opacity = '1';
                    }
                    
                    console.log('[LoginFormFix] Submit button ready');
                    
                    // For debugging: log when form tries to submit
                    form.addEventListener('submit', function(e) {
                        console.log('[LoginFormFix] Form submit event triggered');
                    }, true);
                    
                    // Ensure Livewire can process the form
                    if (window.Livewire) {
                        console.log('[LoginFormFix] Livewire detected');
                        
                        // Make sure wire:submit is preserved
                        const wireSubmit = form.getAttribute('wire:submit');
                        if (wireSubmit) {
                            console.log('[LoginFormFix] wire:submit value:', wireSubmit);
                        }
                    }
                }
            }
        });
    }
    
    // Wait for everything to load
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', init);
            return;
        }
        
        // Wait for Livewire
        if (!window.Livewire) {
            console.log('[LoginFormFix] Waiting for Livewire...');
            setTimeout(init, 100);
            return;
        }
        
        // Apply fixes
        ensureFormSubmission();
        
        // Monitor for changes
        const observer = new MutationObserver(() => {
            ensureFormSubmission();
        });
        
        if (document.body) {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }
    
    init();
})();