/**
 * Business Portal Login Handler
 * Enhanced login functionality
 */
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('login-form');
        if (!loginForm) return;
        
        // Debug mode
        const debug = true;
        
        if (debug) {
            console.log('[Login] Form found, action:', loginForm.action);
            console.log('[Login] CSRF Token:', document.querySelector('meta[name="csrf-token"]')?.content);
        }
        
        // Ensure form uses POST method
        loginForm.method = 'POST';
        
        // Handle form submission
        loginForm.addEventListener('submit', function(e) {
            if (debug) {
                console.log('[Login] Form submitting...');
                console.log('[Login] Email:', loginForm.email.value);
                console.log('[Login] Action URL:', loginForm.action);
            }
            
            // Visual feedback
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.classList.add('opacity-75');
                submitBtn.disabled = true;
                
                // Add loading spinner
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = `
                    <svg class="animate-spin h-5 w-5 mx-auto text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                `;
                
                // Reset after timeout
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('opacity-75');
                    submitBtn.innerHTML = originalText;
                }, 10000);
            }
        });
        
        // Add Enter key support
        loginForm.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && e.target.type !== 'submit') {
                e.preventDefault();
                loginForm.querySelector('button[type="submit"]')?.click();
            }
        });
        
        // Focus first input
        const firstInput = loginForm.querySelector('input[type="email"], input[type="text"]');
        if (firstInput) {
            firstInput.focus();
        }
        
        // Add CSS animation for spinner
        if (!document.getElementById('login-spinner-styles')) {
            const style = document.createElement('style');
            style.id = 'login-spinner-styles';
            style.textContent = `
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                .animate-spin {
                    animation: spin 1s linear infinite;
                }
            `;
            document.head.appendChild(style);
        }
    });
})();