/**
 * Improved Portal Login Form Handler
 * Ensures login form submission works correctly with CSRF token
 */

(function() {
    'use strict';
    
    // Setup CSRF token for all Ajax requests
    function setupCSRF() {
        const token = document.querySelector('meta[name="csrf-token"]');
        if (token) {
            // For jQuery Ajax
            if (typeof $ !== 'undefined') {
                $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': token.content
                    }
                });
            }
            
            // For Axios
            if (typeof axios !== 'undefined') {
                axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
            }
            
            // For native fetch
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                let [url, config] = args;
                config = config || {};
                config.headers = config.headers || {};
                
                // Only add CSRF token for same-origin requests
                if (url.startsWith('/') || url.includes(window.location.host)) {
                    config.headers['X-CSRF-TOKEN'] = token.content;
                }
                
                return originalFetch(url, config);
            };
        }
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setupCSRF();
            initLoginForm();
        });
    } else {
        setupCSRF();
        initLoginForm();
    }
    
    function initLoginForm() {
        console.log('[PortalLogin] Initializing improved login form handler...');
        
        // Find the login form
        const loginForm = document.querySelector('form[action*="/business/login"]');
        
        if (loginForm) {
            console.log('[PortalLogin] Login form found');
            
            // Ensure CSRF token is present
            const csrfInput = loginForm.querySelector('input[name="_token"]');
            if (!csrfInput) {
                console.error('[PortalLogin] CSRF token missing!');
                const token = document.querySelector('meta[name="csrf-token"]');
                if (token) {
                    const newInput = document.createElement('input');
                    newInput.type = 'hidden';
                    newInput.name = '_token';
                    newInput.value = token.content;
                    loginForm.appendChild(newInput);
                    console.log('[PortalLogin] CSRF token added to form');
                }
            }
            
            // Add form submit handler for validation only
            loginForm.addEventListener('submit', function(e) {
                console.log('[PortalLogin] Form submit event triggered');
                
                const submitButton = this.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    const originalText = submitButton.textContent;
                    submitButton.textContent = 'Anmeldung läuft...';
                    
                    // Re-enable button after a timeout in case of error
                    setTimeout(() => {
                        if (submitButton.disabled) {
                            submitButton.disabled = false;
                            submitButton.textContent = originalText;
                        }
                    }, 10000); // 10 seconds timeout
                }
                
                // Allow normal form submission
                return true;
            });
            
        } else {
            console.warn('[PortalLogin] Login form not found');
        }
    }
    
    // Expose debug function
    window.debugPortalLogin = function() {
        const form = document.querySelector('form[action*="/business/login"]');
        console.log('Form found:', !!form);
        if (form) {
            console.log('Form action:', form.action);
            console.log('Form method:', form.method);
            const csrfInput = form.querySelector('input[name="_token"]');
            console.log('CSRF token:', csrfInput ? csrfInput.value : 'NOT FOUND');
            console.log('Meta CSRF:', document.querySelector('meta[name="csrf-token"]')?.content);
        }
        
        // Check session cookie
        console.log('Cookies:', document.cookie);
        console.log('Portal session cookie:', document.cookie.includes('askproai_portal_session'));
    };
})();