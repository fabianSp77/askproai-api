/**
 * Business Portal Login Fix
 * Ensures the login form works correctly
 */
(function() {
    'use strict';
    
    console.log('[Business Login] Initializing login form fixes...');
    
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('login-form');
        
        if (!loginForm) {
            console.warn('[Business Login] Login form not found');
            return;
        }
        
        console.log('[Business Login] Form found, setting up handlers');
        
        // Ensure form is clickable
        loginForm.style.pointerEvents = 'auto';
        
        // Ensure all inputs are clickable
        const inputs = loginForm.querySelectorAll('input, button');
        inputs.forEach(input => {
            input.style.pointerEvents = 'auto';
            input.style.cursor = input.type === 'submit' || input.type === 'button' ? 'pointer' : 'text';
        });
        
        // Add submit handler
        loginForm.addEventListener('submit', function(e) {
            console.log('[Business Login] Form submitted');
            
            // Get submit button
            const submitButton = loginForm.querySelector('button[type="submit"]');
            if (submitButton) {
                // Disable button to prevent double submit
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="loading">Anmeldung läuft...</span>';
                
                // Re-enable after 5 seconds if form doesn't submit
                setTimeout(() => {
                    submitButton.disabled = false;
                    submitButton.innerHTML = '<span class="absolute left-0 inset-y-0 flex items-center pl-3"><svg class="h-5 w-5 text-indigo-500 group-hover:text-indigo-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" /></svg></span>Anmelden';
                }, 5000);
            }
        });
        
        // Focus on email field
        const emailField = loginForm.querySelector('#email');
        if (emailField) {
            emailField.focus();
        }
        
        // Log form action for debugging
        console.log('[Business Login] Form action:', loginForm.action);
        console.log('[Business Login] Form method:', loginForm.method);
        
        // Check for CSRF token
        const csrfToken = loginForm.querySelector('input[name="_token"]');
        if (csrfToken) {
            console.log('[Business Login] CSRF token present');
        } else {
            console.error('[Business Login] CSRF token missing!');
        }
    });
    
    // Ensure all click events work
    document.addEventListener('click', function(e) {
        if (e.target.matches('#login-form button[type="submit"]')) {
            console.log('[Business Login] Submit button clicked');
        }
    }, true);
    
})();