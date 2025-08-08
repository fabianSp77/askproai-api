// ULTIMATE LOGIN FIX - Issue #508
// Speziell für Livewire/Alpine.js Login-Formulare

console.error('[ULTIMATE-LOGIN-FIX] 🔐 Starting ultimate login fix...');

(function() {
    'use strict';
    
    let fixAttempts = 0;
    const MAX_ATTEMPTS = 10;
    
    function fixLoginForm() {
        fixAttempts++;
        console.error(`[ULTIMATE-LOGIN-FIX] Attempt #${fixAttempts}`);
        
        // 1. Fix Alpine.js password field
        const passwordFields = document.querySelectorAll('input[wire\\:model="data.password"], input[x-bind\\:type*="password"], input#data\\.password');
        
        passwordFields.forEach(field => {
            console.error('[ULTIMATE-LOGIN-FIX] Found password field:', field);
            
            // Ensure it's a password field
            if (!field.type) {
                field.type = 'password';
            }
            
            // Make it fully interactive
            field.style.pointerEvents = 'auto';
            field.style.opacity = '1';
            field.style.visibility = 'visible';
            field.style.display = 'block';
            field.removeAttribute('disabled');
            field.removeAttribute('readonly');
            
            // Ensure proper autocomplete
            if (!field.autocomplete) {
                field.autocomplete = 'current-password';
            }
            
            // Fix Alpine.js binding
            const alpineComponent = field.closest('[x-data]');
            if (alpineComponent && alpineComponent.__x) {
                console.error('[ULTIMATE-LOGIN-FIX] Alpine component found, ensuring password toggle works');
                // Don't break Alpine's type binding
                if (field.hasAttribute('x-bind:type')) {
                    // Keep Alpine binding but ensure field is visible
                    field.style.webkitTextSecurity = 'disc';
                }
            }
        });
        
        // 2. Fix email fields
        const emailFields = document.querySelectorAll('input[wire\\:model="data.email"], input[type="email"], input#data\\.email');
        emailFields.forEach(field => {
            field.style.pointerEvents = 'auto';
            field.style.opacity = '1';
            field.style.visibility = 'visible';
            field.removeAttribute('disabled');
            field.removeAttribute('readonly');
            
            if (!field.type) {
                field.type = 'email';
            }
        });
        
        // 3. Fix submit buttons
        const submitButtons = document.querySelectorAll('button[type="submit"], form button[wire\\:loading\\.attr="disabled"]');
        submitButtons.forEach(button => {
            button.style.pointerEvents = 'auto';
            button.style.cursor = 'pointer';
            button.removeAttribute('disabled');
            
            // Remove any click blockers
            const newButton = button.cloneNode(true);
            button.parentNode.replaceChild(newButton, button);
            
            // Add click logger
            newButton.addEventListener('click', (e) => {
                console.error('[ULTIMATE-LOGIN-FIX] Submit button clicked!');
            }, true);
        });
        
        // 4. Fix Livewire form submission
        const forms = document.querySelectorAll('form[wire\\:submit]');
        forms.forEach(form => {
            console.error('[ULTIMATE-LOGIN-FIX] Found Livewire form:', form.getAttribute('wire:submit'));
            
            // Ensure form is interactive
            form.style.pointerEvents = 'auto';
            form.style.opacity = '1';
            
            // Add manual submit handler as backup
            form.addEventListener('submit', function(e) {
                console.error('[ULTIMATE-LOGIN-FIX] Form submitted!');
                
                // Get form data
                const email = form.querySelector('input[wire\\:model="data.email"]')?.value;
                const password = form.querySelector('input[wire\\:model="data.password"]')?.value;
                const remember = form.querySelector('input[wire\\:model="data.remember"]')?.checked;
                
                console.error('[ULTIMATE-LOGIN-FIX] Form data:', { email, password: '***', remember });
                
                // Let Livewire handle it, but log for debugging
                if (!email || !password) {
                    console.error('[ULTIMATE-LOGIN-FIX] Missing email or password!');
                }
            }, true);
        });
        
        // 5. Fix any blocking overlays
        document.querySelectorAll('.fixed.inset-0, [class*="overlay"]').forEach(overlay => {
            if (!overlay.classList.contains('fi-main')) {
                overlay.style.pointerEvents = 'none';
                overlay.style.display = 'none';
            }
        });
        
        // 6. Ensure all inputs are typeable
        document.querySelectorAll('input, textarea').forEach(input => {
            const style = window.getComputedStyle(input);
            if (style.pointerEvents === 'none') {
                input.style.pointerEvents = 'auto';
            }
        });
        
        // 7. Create status indicator
        if (!document.getElementById('ultimate-login-fix-indicator')) {
            const indicator = document.createElement('div');
            indicator.id = 'ultimate-login-fix-indicator';
            indicator.style.cssText = `
                position: fixed;
                top: 10px;
                right: 10px;
                background: #059669;
                color: white;
                padding: 8px 16px;
                border-radius: 6px;
                font-family: monospace;
                font-size: 12px;
                z-index: 999999;
                box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            `;
            indicator.textContent = '🔐 Login Fix Active';
            document.body.appendChild(indicator);
        }
        
        // 8. Special Livewire fixes
        if (window.Livewire) {
            console.error('[ULTIMATE-LOGIN-FIX] Livewire detected, applying special fixes');
            
            // Hook into Livewire lifecycle
            Livewire.hook('message.sent', (message, component) => {
                console.error('[ULTIMATE-LOGIN-FIX] Livewire message sent:', message);
            });
            
            Livewire.hook('message.received', (message, component) => {
                console.error('[ULTIMATE-LOGIN-FIX] Livewire message received:', message);
            });
            
            Livewire.hook('message.failed', (message, component) => {
                console.error('[ULTIMATE-LOGIN-FIX] Livewire message FAILED:', message);
            });
        }
        
        // 9. Fix Alpine.js password reveal button
        document.querySelectorAll('button[x-on\\:click*="isPasswordRevealed"], button[x-show*="isPasswordRevealed"]').forEach(button => {
            button.style.pointerEvents = 'auto';
            button.style.cursor = 'pointer';
            console.error('[ULTIMATE-LOGIN-FIX] Fixed password reveal button');
        });
        
        // 10. Debug form state
        const passwordField = document.querySelector('input[wire\\:model="data.password"], input#data\\.password');
        const emailField = document.querySelector('input[wire\\:model="data.email"], input#data\\.email');
        
        if (passwordField && emailField) {
            console.error('[ULTIMATE-LOGIN-FIX] Form fields status:');
            console.error('  Email field:', {
                exists: !!emailField,
                visible: window.getComputedStyle(emailField).display !== 'none',
                clickable: window.getComputedStyle(emailField).pointerEvents !== 'none',
                value: emailField.value || 'empty'
            });
            console.error('  Password field:', {
                exists: !!passwordField,
                visible: window.getComputedStyle(passwordField).display !== 'none',
                clickable: window.getComputedStyle(passwordField).pointerEvents !== 'none',
                type: passwordField.type,
                hasValue: passwordField.value.length > 0
            });
        }
    }
    
    // Run fix immediately
    fixLoginForm();
    
    // Run on various events
    document.addEventListener('DOMContentLoaded', fixLoginForm);
    document.addEventListener('livewire:load', fixLoginForm);
    document.addEventListener('alpine:init', fixLoginForm);
    
    // Run periodically for first few seconds
    const interval = setInterval(() => {
        if (fixAttempts >= MAX_ATTEMPTS) {
            clearInterval(interval);
            console.error('[ULTIMATE-LOGIN-FIX] Max attempts reached');
            return;
        }
        fixLoginForm();
    }, 500);
    
    // Global error handler
    window.addEventListener('error', (e) => {
        if (e.message && e.message.includes('password')) {
            console.error('[ULTIMATE-LOGIN-FIX] Password-related error:', e.message);
        }
    });
    
    // Export for debugging
    window.UltimateLoginFix = {
        fixLoginForm,
        getFormData: () => {
            const form = document.querySelector('form[wire\\:submit]');
            if (!form) return null;
            
            return {
                email: form.querySelector('input[wire\\:model="data.email"]')?.value,
                password: form.querySelector('input[wire\\:model="data.password"]')?.value,
                remember: form.querySelector('input[wire\\:model="data.remember"]')?.checked
            };
        },
        submitForm: () => {
            const form = document.querySelector('form[wire\\:submit]');
            if (form) {
                console.error('[ULTIMATE-LOGIN-FIX] Manually submitting form...');
                form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            }
        }
    };
    
    console.error('[ULTIMATE-LOGIN-FIX] 🔐 Ultimate login fix loaded! Use window.UltimateLoginFix for debugging.');
})();