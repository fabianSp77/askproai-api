/**
 * Livewire Error Handler
 * Prevents repeated failed requests and provides better error handling
 */
(function() {
    'use strict';
    
    console.log('⚡ Livewire Error Handler Active');
    
    // Track failed requests to prevent repeats
    const failedRequests = new Map();
    const maxRetries = 1;
    
    // Wait for Livewire
    function waitForLivewire(callback) {
        if (window.Livewire) {
            callback();
        } else {
            setTimeout(() => waitForLivewire(callback), 100);
        }
    }
    
    waitForLivewire(() => {
        // Intercept Livewire requests
        Livewire.hook('request', ({ url, payload, options, succeed, fail }) => {
            const requestKey = `${url}-${JSON.stringify(payload)}`;
            const failCount = failedRequests.get(requestKey) || 0;
            
            // If this request has failed too many times, skip it
            if (failCount >= maxRetries) {
                console.warn(`Skipping request after ${failCount} failures:`, url);
                fail({ status: 0, response: { message: 'Request skipped due to repeated failures' } });
                return;
            }
            
            // Override the fail callback to track failures
            const originalFail = fail;
            fail = (response) => {
                failedRequests.set(requestKey, failCount + 1);
                
                // Log the error details
                console.error('Livewire request failed:', {
                    url,
                    status: response.status,
                    payload,
                    response: response.response
                });
                
                // If it's a 500 error, show a user-friendly message
                if (response.status === 500) {
                    console.error('Server error detected. This may be a configuration issue.');
                    
                    // Check if it's the __mountParamsContainer component
                    if (payload.fingerprint && payload.fingerprint.name === '__mountParamsContainer') {
                        console.warn('MountParamsContainer error - this is likely a Filament widget issue');
                        // Don't show error modal for this specific component
                        return;
                    }
                }
                
                // Call original fail handler
                if (originalFail) {
                    originalFail(response);
                }
            };
        });
        
        // Clear failed requests cache periodically
        setInterval(() => {
            failedRequests.clear();
            console.log('Cleared failed requests cache');
        }, 60000); // Every minute
        
        // Also check for specific problematic components
        document.addEventListener('DOMContentLoaded', () => {
            // Find components that might be causing issues
            const components = document.querySelectorAll('[wire\\:id]');
            components.forEach(el => {
                const componentId = el.getAttribute('wire:id');
                try {
                    const component = Livewire.find(componentId);
                    if (component && component.fingerprint && component.fingerprint.name === '__mountParamsContainer') {
                        console.warn('Found problematic MountParamsContainer component:', componentId);
                        // You could disable it here if needed
                    }
                } catch (e) {
                    console.warn('Error checking component:', componentId, e);
                }
            });
        });
    });
})();