// Livewire 404 Fix
(function() {
    //console.log('[Livewire 404 Fix] Starting...');
    
    // Override Livewire error handling
    if (window.Livewire) {
        const originalHandleResponse = window.Livewire.connection.handleResponse;
        
        window.Livewire.connection.handleResponse = function(response) {
            //console.log('[Livewire 404 Fix] Response status:', response.status);
            
            // Intercept 404 errors
            if (response.status === 404) {
                console.warn('[Livewire 404 Fix] Intercepted 404 error, attempting recovery...');
                
                // Try to find the component and refresh it
                const component = window.Livewire.find(response.component?.id);
                if (component) {
                    //console.log('[Livewire 404 Fix] Refreshing component:', component.id);
                    component.$refresh();
                    return;
                }
                
                // Don't show the popup for 404s
                return;
            }
            
            // Call original handler for other responses
            return originalHandleResponse.call(this, response);
        };
        
        // Override fetch to add logging
        const originalFetch = window.fetch;
        window.fetch = function(...args) {
            const [url, options] = args;
            
            if (url && url.includes('livewire')) {
                //console.log('[Livewire 404 Fix] Livewire request:', url, options?.method || 'GET');
            }
            
            return originalFetch.apply(this, args).then(response => {
                if (url && url.includes('livewire') && !response.ok) {
                    console.error('[Livewire 404 Fix] Livewire request failed:', response.status, response.statusText);
                }
                return response;
            });
        };
        
        // Ensure update URI is correct
        if (window.livewireScriptConfig) {
            //console.log('[Livewire 404 Fix] Current update URI:', window.livewireScriptConfig.uri);
            // Force correct URI
            window.livewireScriptConfig.uri = '/livewire/update';
        }
    }
    
    // Remove any 404 modals on page load
    setTimeout(() => {
        const errorModals = document.querySelectorAll('[role="dialog"], .filament-modal');
        errorModals.forEach(modal => {
            const text = modal.innerText || '';
            if (text.includes('404') || text.includes('Not Found')) {
                //console.log('[Livewire 404 Fix] Removing 404 modal');
                modal.remove();
            }
        });
    }, 1000);
    
    //console.log('[Livewire 404 Fix] Fix applied');
})();