/**
 * Livewire Modal Fix
 * Prevents error modals from repeatedly trying to display
 */
(function() {
    'use strict';
    
    console.log('🛡️ Livewire Modal Fix Active');
    
    // Wait for modal.js to be loaded
    let checkInterval = setInterval(() => {
        // Look for the modal error function in the global scope
        if (window.Go || window.LivewireModal || (window.modal && window.modal.showError)) {
            clearInterval(checkInterval);
            interceptModalErrors();
        }
    }, 100);
    
    // Stop checking after 5 seconds
    setTimeout(() => clearInterval(checkInterval), 5000);
    
    function interceptModalErrors() {
        // Try to find and override the error modal function
        const possibleTargets = [
            window.Go,
            window.showErrorModal,
            window.modal?.showError,
            window.LivewireModal?.showError
        ];
        
        possibleTargets.forEach(target => {
            if (typeof target === 'function') {
                const original = target;
                // Create a wrapper that prevents the modal
                const wrapper = function(...args) {
                    console.warn('Intercepted error modal attempt:', args);
                    // Don't call the original function
                    return false;
                };
                
                // Try to replace the function
                try {
                    if (target.name === 'Go') {
                        window.Go = wrapper;
                    } else if (window.modal && window.modal.showError === target) {
                        window.modal.showError = wrapper;
                    }
                } catch (e) {
                    console.warn('Could not override modal function:', e);
                }
            }
        });
    }
    
    // Also intercept Livewire's error handling
    document.addEventListener('DOMContentLoaded', () => {
        if (window.Livewire) {
            // Override Livewire's error modal display
            const originalHandleError = window.Livewire.handleError;
            if (originalHandleError) {
                window.Livewire.handleError = function(error) {
                    console.error('Livewire Error (modal suppressed):', error);
                    // Don't show the modal
                    return false;
                };
            }
            
            // Hook into Livewire's error events
            Livewire.hook('request', ({ fail }) => {
                fail(({ status, response }) => {
                    console.error('Livewire Request Failed:', { status, response });
                    // Prevent default error modal
                    return false;
                });
            });
        }
    });
    
    // Nuclear option: prevent any element with id "livewire-error" from being created
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1 && node.id === 'livewire-error') {
                    console.warn('Removing Livewire error modal from DOM');
                    node.remove();
                }
            });
        });
    });
    
    // Start observing when DOM is ready
    if (document.body) {
        observer.observe(document.body, { childList: true, subtree: true });
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            observer.observe(document.body, { childList: true, subtree: true });
        });
    }
})();