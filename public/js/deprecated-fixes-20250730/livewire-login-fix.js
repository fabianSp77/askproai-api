/**
 * Livewire Login Form Fix
 * Ensures Livewire login form submission works correctly
 */
(function() {
    'use strict';
    
    console.log('[LivewireLoginFix] Starting...');
    
    function fixLoginForm() {
        // Only run on login page
        if (!window.location.pathname.includes('/admin/login')) {
            return;
        }
        
        console.log('[LivewireLoginFix] On login page, applying fixes...');
        
        // Wait for Livewire to be ready
        if (!window.Livewire) {
            console.log('[LivewireLoginFix] Waiting for Livewire...');
            setTimeout(fixLoginForm, 100);
            return;
        }
        
        // Find the form
        const forms = document.querySelectorAll('form[wire\\:submit="authenticate"]');
        
        forms.forEach(form => {
            console.log('[LivewireLoginFix] Found login form');
            
            // Find submit button
            const submitButton = form.querySelector('button[type="submit"]');
            if (submitButton) {
                console.log('[LivewireLoginFix] Found submit button');
                
                // Make sure button is visible and styled
                submitButton.style.cssText = `
                    background-color: rgb(251, 191, 36) !important;
                    color: rgb(0, 0, 0) !important;
                    border: 1px solid rgb(217, 119, 6) !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                    pointer-events: auto !important;
                    cursor: pointer !important;
                `;
                
                // Ensure label is visible
                const label = submitButton.querySelector('.fi-btn-label');
                if (label) {
                    label.style.color = 'rgb(0, 0, 0)';
                    label.style.opacity = '1';
                }
                
                // Remove all existing click handlers
                const newButton = submitButton.cloneNode(true);
                submitButton.parentNode.replaceChild(newButton, submitButton);
                
                // Add single click handler
                newButton.addEventListener('click', function(e) {
                    console.log('[LivewireLoginFix] Submit button clicked');
                    
                    // Check if form is valid
                    const emailInput = form.querySelector('input[type="email"]');
                    const passwordInput = form.querySelector('input[type="password"]');
                    
                    if (!emailInput || !emailInput.value) {
                        console.log('[LivewireLoginFix] Email is empty');
                        return;
                    }
                    
                    if (!passwordInput || !passwordInput.value) {
                        console.log('[LivewireLoginFix] Password is empty');
                        return;
                    }
                    
                    console.log('[LivewireLoginFix] Form data is valid, submitting...');
                    
                    // Let Livewire handle the submission
                    // The wire:submit="authenticate" should work now
                });
                
                // Ensure form can submit via Enter key
                form.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        const target = e.target;
                        if (target.tagName === 'INPUT') {
                            e.preventDefault();
                            newButton.click();
                        }
                    }
                });
            }
        });
        
        // Debug: Check Livewire component
        if (window.Livewire) {
            console.log('[LivewireLoginFix] Livewire components:', Object.keys(window.Livewire.components.componentsById));
            
            // Hook into Livewire lifecycle
            Livewire.hook('message.sent', (message, component) => {
                console.log('[LivewireLoginFix] Livewire message sent:', message.updateQueue);
            });
            
            Livewire.hook('message.failed', (message, component) => {
                console.error('[LivewireLoginFix] Livewire message failed:', message);
            });
            
            Livewire.hook('message.received', (message, component) => {
                console.log('[LivewireLoginFix] Livewire message received:', message);
            });
        }
    }
    
    // Start fixing
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fixLoginForm);
    } else {
        fixLoginForm();
    }
    
    // Also run after Alpine init
    document.addEventListener('alpine:init', () => {
        setTimeout(fixLoginForm, 100);
    });
})();