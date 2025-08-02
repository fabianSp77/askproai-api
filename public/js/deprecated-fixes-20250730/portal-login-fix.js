/**
 * Portal Login Form Handler
 * Ensures login form submission works correctly
 */

(function() {
    'use strict';
    
    // Wait for DOM to be fully loaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initLoginForm);
    } else {
        initLoginForm();
    }
    
    function initLoginForm() {
        console.log('[PortalLogin] Initializing login form handler...');
        
        // Find the login form
        const loginForm = document.querySelector('form[action*="/business/login"]');
        
        if (loginForm) {
            console.log('[PortalLogin] Login form found');
            
            // Remove any existing submit handlers that might interfere
            const newForm = loginForm.cloneNode(true);
            loginForm.parentNode.replaceChild(newForm, loginForm);
            
            // Get the submit button
            const submitButton = newForm.querySelector('button[type="submit"]');
            
            if (submitButton) {
                // Remove any click handlers from button
                const newButton = submitButton.cloneNode(true);
                submitButton.parentNode.replaceChild(newButton, submitButton);
                
                // Add simple click handler
                newButton.addEventListener('click', function(e) {
                    console.log('[PortalLogin] Submit button clicked');
                    
                    // Let the form handle submission
                    const form = this.closest('form');
                    if (form && form.checkValidity()) {
                        console.log('[PortalLogin] Form is valid, submitting...');
                        
                        // Show loading state
                        this.disabled = true;
                        const originalText = this.textContent;
                        this.textContent = 'Anmeldung läuft...';
                        
                        // Submit the form
                        form.submit();
                    }
                });
            }
            
            // Also handle form submit event
            newForm.addEventListener('submit', function(e) {
                console.log('[PortalLogin] Form submit event triggered');
                // Allow normal submission
                return true;
            });
            
        } else {
            console.warn('[PortalLogin] Login form not found');
        }
    }
    
    // Expose function globally for debugging
    window.debugPortalLogin = function() {
        const form = document.querySelector('form[action*="/business/login"]');
        console.log('Form found:', !!form);
        if (form) {
            console.log('Form action:', form.action);
            console.log('Form method:', form.method);
            console.log('CSRF token:', form.querySelector('input[name="_token"]')?.value);
        }
    };
})();