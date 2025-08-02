/**
 * Login Page Enhancer for AskProAI
 * 
 * Simple, non-invasive enhancements for the login form
 * No element cloning or complex manipulations
 */

(function() {
    'use strict';
    
    const LoginEnhancer = {
        name: 'LoginEnhancer',
        
        init() {
            // Only run on login page
            if (!window.location.pathname.includes('/login')) {
                return;
            }
            
            console.log('[LoginEnhancer] Enhancing login page...');
            
            // Add visual enhancements
            this.addStyles();
            
            // Enhance form behavior
            this.enhanceForm();
            
            // Add loading states
            this.addLoadingStates();
            
            console.log('[LoginEnhancer] Login page enhanced');
        },
        
        addStyles() {
            // Styles are now handled by login-page-clean.css
            // This method is kept for backward compatibility
        },
        
        enhanceForm() {
            const form = document.querySelector('form[wire\\:submit]');
            if (!form) return;
            
            const submitButton = form.querySelector('button[type="submit"]');
            if (!submitButton) return;
            
            // Ensure form can be submitted with Enter key
            form.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && e.target.tagName === 'INPUT') {
                    e.preventDefault();
                    
                    // Validate form
                    const emailInput = form.querySelector('input[type="email"]');
                    const passwordInput = form.querySelector('input[type="password"]');
                    
                    if (emailInput && emailInput.value && passwordInput && passwordInput.value) {
                        // Trigger form submission through Livewire
                        submitButton.click();
                    }
                }
            });
            
            // Auto-focus email field
            const emailInput = form.querySelector('input[type="email"]');
            if (emailInput && !emailInput.value) {
                emailInput.focus();
            }
        },
        
        addLoadingStates() {
            const form = document.querySelector('form[wire\\:submit]');
            if (!form) return;
            
            const submitButton = form.querySelector('button[type="submit"]');
            if (!submitButton) return;
            
            // Store original button content
            const originalContent = submitButton.innerHTML;
            
            // Listen for Livewire loading states
            if (window.Livewire) {
                let isLoading = false;
                
                Livewire.hook('message.sent', (message, component) => {
                    // Check if this is the login form
                    const wireId = form.closest('[wire\\:id]')?.getAttribute('wire:id');
                    if (component.id === wireId && !isLoading) {
                        isLoading = true;
                        form.classList.add('is-loading');
                        submitButton.disabled = true;
                        submitButton.innerHTML = `
                            <span class="flex items-center justify-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Anmeldung läuft...</span>
                            </span>
                        `;
                    }
                });
                
                Livewire.hook('message.processed', (message, component) => {
                    const wireId = form.closest('[wire\\:id]')?.getAttribute('wire:id');
                    if (component.id === wireId && isLoading) {
                        // Check if there are validation errors
                        const hasErrors = form.querySelector('[wire\\:model].invalid') !== null;
                        
                        if (hasErrors) {
                            // Reset button if there are errors
                            isLoading = false;
                            form.classList.remove('is-loading');
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalContent;
                        }
                        // If no errors, keep loading state (redirect will happen)
                    }
                });
            }
        }
    };
    
    // Register with AskProAI if available
    if (window.AskProAI) {
        window.AskProAI.registerModule('LoginEnhancer', LoginEnhancer);
    } else {
        // Standalone initialization
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => LoginEnhancer.init());
        } else {
            LoginEnhancer.init();
        }
    }
    
    // Export for debugging
    window.LoginEnhancer = LoginEnhancer;
    
})();