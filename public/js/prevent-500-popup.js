// Prevent 500 Error Popups - Ultimate Defense
(function() {
    console.log('[500 Prevention] Initializing complete protection...');

    // Store original showHtmlModal if it exists
    if (typeof window.showHtmlModal !== 'undefined') {
        const originalShowHtmlModal = window.showHtmlModal;
        window.showHtmlModal = function(html) {
            console.warn('[500 Prevention] Blocked showHtmlModal call');
            // Check if this is a 500 error
            if (html && html.toString().includes('500')) {
                console.error('[500 Prevention] 500 error modal blocked!');
                return; // Don't show it
            }
            return originalShowHtmlModal.apply(this, arguments);
        };
    }

    // Monitor for Livewire error modals
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.id === 'livewire-error' || (node.querySelector && node.querySelector('#livewire-error'))) {
                    console.warn('[500 Prevention] Livewire error modal detected and removed');
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

    // Intercept Livewire error handling
    document.addEventListener('livewire:load', function() {
        if (window.Livewire) {
            // Override Livewire's error handling
            const originalHandleError = window.Livewire.handleError || function() {};
            window.Livewire.handleError = function(error) {
                console.error('[500 Prevention] Livewire error intercepted:', error);
                // Don't call the original if it's a 500 error
                if (error && error.toString().includes('500')) {
                    console.warn('[500 Prevention] 500 error suppressed');
                    return;
                }
                return originalHandleError.apply(this, arguments);
            };

            // Also check for onError hook
            if (window.Livewire.onError) {
                const originalOnError = window.Livewire.onError;
                window.Livewire.onError = function(error) {
                    console.error('[500 Prevention] Livewire onError intercepted:', error);
                    if (error && error.toString().includes('500')) {
                        console.warn('[500 Prevention] 500 error in onError suppressed');
                        return;
                    }
                    return originalOnError.apply(this, arguments);
                };
            }
        }
    });

    // Remove any existing error modals
    setInterval(() => {
        const errorModal = document.getElementById('livewire-error');
        if (errorModal) {
            console.warn('[500 Prevention] Removing existing error modal');
            errorModal.remove();
        }
    }, 1000);

    console.log('[500 Prevention] All protections active');
})();