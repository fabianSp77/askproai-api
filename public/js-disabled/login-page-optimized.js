/**
 * Login Page Optimizer for AskProAI
 * Consolidated and optimized login functionality
 * Date: 2025-08-02
 */

(function() {
    'use strict';
    
    // console.log('🔐 Login Page Optimizer Loading...');
    
    const LoginOptimizer = {
        config: {
            autoFocus: true,
            enhancedValidation: true,
            loadingStates: true,
            accessibilityFeatures: true,
            mobileOptimizations: true
        },
        
        init() {
            // Only run on login pages
            if (!this.isLoginPage()) {
                // console.log('Not a login page, skipping optimization');
                return;
            }
            
            // console.log('Optimizing login page...');
            
            // Core optimizations
            this.fixMobileInputs();
            this.enhanceAccessibility();
            this.addValidationHelpers();
            this.setupLoadingStates();
            this.improveErrorHandling();
            
            // Performance optimizations
            this.preloadAssets();
            this.optimizeAnimations();
            
            // console.log('✅ Login page optimized');
        },
        
        isLoginPage() {
            const path = window.location.pathname;
            return path.includes('/login') || 
                   path.includes('/admin/login') || 
                   path.includes('/portal/login');
        },
        
        fixMobileInputs() {
            if (!this.config.mobileOptimizations) return;
            
            const inputs = document.querySelectorAll('input[type="email"], input[type="password"]');
            
            inputs.forEach(input => {
                // Fix iOS input issues
                input.style.fontSize = '16px'; // Prevents zoom on iOS
                input.setAttribute('autocapitalize', 'off');
                input.setAttribute('autocorrect', 'off');
                
                // Ensure inputs are tappable
                input.style.minHeight = '44px';
                input.style.touchAction = 'manipulation';
                
                // Fix Android keyboard issues
                input.addEventListener('focus', () => {
                    // Ensure keyboard doesn't cover input
                    setTimeout(() => {
                        input.scrollIntoView({ 
                            behavior: 'smooth', 
                            block: 'center' 
                        });
                    }, 300);
                });
            });
        },
        
        enhanceAccessibility() {
            if (!this.config.accessibilityFeatures) return;
            
            const form = document.querySelector('form');
            if (!form) return;
            
            // Add ARIA labels
            const emailInput = form.querySelector('input[type="email"]');
            const passwordInput = form.querySelector('input[type="password"]');
            const submitButton = form.querySelector('button[type="submit"]');
            
            if (emailInput) {
                emailInput.setAttribute('aria-label', 'E-Mail-Adresse');
                emailInput.setAttribute('aria-required', 'true');
            }
            
            if (passwordInput) {
                passwordInput.setAttribute('aria-label', 'Passwort');
                passwordInput.setAttribute('aria-required', 'true');
            }
            
            if (submitButton) {
                submitButton.setAttribute('aria-label', 'Anmelden');
            }
            
            // Add skip link for keyboard users
            const skipLink = document.createElement('a');
            skipLink.href = '#form';
            skipLink.className = 'sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 bg-white p-2 rounded shadow-lg';
            skipLink.textContent = 'Zum Login-Formular springen';
            document.body.insertBefore(skipLink, document.body.firstChild);
            
            // Auto-focus management
            if (this.config.autoFocus && emailInput && !emailInput.value) {
                // Delay focus to ensure page is fully loaded
                setTimeout(() => emailInput.focus(), 100);
            }
        },
        
        addValidationHelpers() {
            if (!this.config.enhancedValidation) return;
            
            const form = document.querySelector('form');
            if (!form) return;
            
            const emailInput = form.querySelector('input[type="email"]');
            const passwordInput = form.querySelector('input[type="password"]');
            
            // Email validation helper
            if (emailInput) {
                const emailHelper = document.createElement('div');
                emailHelper.className = 'text-sm text-gray-600 mt-1 hidden';
                emailHelper.setAttribute('role', 'status');
                emailHelper.setAttribute('aria-live', 'polite');
                emailInput.parentNode.appendChild(emailHelper);
                
                emailInput.addEventListener('blur', () => {
                    const value = emailInput.value.trim();
                    if (value && !this.isValidEmail(value)) {
                        emailHelper.textContent = 'Bitte geben Sie eine gültige E-Mail-Adresse ein';
                        emailHelper.classList.remove('hidden');
                        emailHelper.classList.add('text-red-600');
                    } else {
                        emailHelper.classList.add('hidden');
                    }
                });
            }
            
            // Password caps lock warning
            if (passwordInput) {
                const capsWarning = document.createElement('div');
                capsWarning.className = 'text-sm text-amber-600 mt-1 hidden flex items-center';
                capsWarning.setAttribute('role', 'alert');
                capsWarning.innerHTML = `
                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <span>Feststelltaste ist aktiviert</span>
                `;
                passwordInput.parentNode.appendChild(capsWarning);
                
                passwordInput.addEventListener('keyup', (e) => {
                    // Check if event has getModifierState method
                    if (e.getModifierState && typeof e.getModifierState === 'function') {
                        if (e.getModifierState('CapsLock')) {
                            capsWarning.classList.remove('hidden');
                        } else {
                            capsWarning.classList.add('hidden');
                        }
                    }
                });
            }
        },
        
        setupLoadingStates() {
            if (!this.config.loadingStates) return;
            
            const form = document.querySelector('form');
            if (!form) return;
            
            const submitButton = form.querySelector('button[type="submit"]');
            if (!submitButton) return;
            
            // Store original button content
            const originalContent = submitButton.innerHTML;
            const originalClasses = submitButton.className;
            
            // Enhanced loading state with Livewire integration
            if (window.Livewire) {
                let isSubmitting = false;
                
                Livewire.hook('message.sent', (message, component) => {
                    const wireId = form.closest('[wire\\:id]')?.getAttribute('wire:id');
                    if (component.id === wireId && !isSubmitting) {
                        isSubmitting = true;
                        
                        // Update button state
                        submitButton.disabled = true;
                        submitButton.className = originalClasses + ' opacity-75 cursor-not-allowed';
                        submitButton.innerHTML = `
                            <span class="flex items-center justify-center">
                                <svg class="animate-spin -ml-1 mr-3 h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Anmeldung läuft...</span>
                            </span>
                        `;
                        
                        // Add loading class to form
                        form.classList.add('is-submitting');
                    }
                });
                
                Livewire.hook('message.processed', (message, component) => {
                    const wireId = form.closest('[wire\\:id]')?.getAttribute('wire:id');
                    if (component.id === wireId && isSubmitting) {
                        // Check for validation errors
                        const hasErrors = form.querySelector('.fi-fo-field-wrapper-error') || 
                                        form.querySelector('[wire\\:model].is-invalid');
                        
                        if (hasErrors) {
                            // Reset on error
                            isSubmitting = false;
                            submitButton.disabled = false;
                            submitButton.className = originalClasses;
                            submitButton.innerHTML = originalContent;
                            form.classList.remove('is-submitting');
                        }
                        // Keep loading state if successful (redirect incoming)
                    }
                });
            }
            
            // Fallback for non-Livewire forms
            form.addEventListener('submit', (e) => {
                if (!window.Livewire) {
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
        },
        
        improveErrorHandling() {
            // Monitor for error messages and enhance them
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            // Check for Filament error messages
                            const errorMessages = node.querySelectorAll('.fi-fo-field-wrapper-error-message');
                            errorMessages.forEach(this.enhanceErrorMessage.bind(this));
                        }
                    });
                });
            });
            
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        },
        
        enhanceErrorMessage(errorElement) {
            const text = errorElement.textContent.trim();
            
            // Enhance generic error messages
            const enhancements = {
                'These credentials do not match our records.': 
                    'Die eingegebenen Anmeldedaten sind nicht korrekt. Bitte überprüfen Sie E-Mail und Passwort.',
                'The email field is required.': 
                    'Bitte geben Sie Ihre E-Mail-Adresse ein.',
                'The password field is required.': 
                    'Bitte geben Sie Ihr Passwort ein.',
                'Too many login attempts.': 
                    'Zu viele Anmeldeversuche. Bitte versuchen Sie es in einigen Minuten erneut.'
            };
            
            if (enhancements[text]) {
                errorElement.textContent = enhancements[text];
            }
            
            // Add icon to error messages
            if (!errorElement.querySelector('svg')) {
                const icon = document.createElement('span');
                icon.innerHTML = `
                    <svg class="inline-block w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                `;
                errorElement.insertBefore(icon.firstChild, errorElement.firstChild);
            }
        },
        
        preloadAssets() {
            // Preload critical fonts
            const fontPreload = document.createElement('link');
            fontPreload.rel = 'preload';
            fontPreload.as = 'font';
            fontPreload.type = 'font/woff2';
            fontPreload.href = '/fonts/inter-var.woff2';
            fontPreload.crossOrigin = 'anonymous';
            document.head.appendChild(fontPreload);
        },
        
        optimizeAnimations() {
            // Reduce motion for users who prefer it
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                document.documentElement.style.setProperty('--transition-normal', '0ms');
                document.documentElement.style.setProperty('--transition-fast', '0ms');
                document.documentElement.style.setProperty('--transition-slow', '0ms');
            }
        },
        
        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => LoginOptimizer.init());
    } else {
        LoginOptimizer.init();
    }
    
    // Export for debugging
    window.LoginOptimizer = LoginOptimizer;
    
})();