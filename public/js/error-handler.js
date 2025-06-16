// Global error handler for debugging redirects
(function() {
    // Store original pushState
    const originalPushState = history.pushState;
    const originalReplaceState = history.replaceState;
    
    // Override pushState to log navigation
    history.pushState = function() {
        console.warn('Navigation detected via pushState:', arguments[2]);
        return originalPushState.apply(history, arguments);
    };
    
    // Override replaceState to log navigation
    history.replaceState = function() {
        console.warn('Navigation detected via replaceState:', arguments[2]);
        return originalReplaceState.apply(history, arguments);
    };
    
    // Listen for Livewire navigation
    if (window.Livewire) {
        window.Livewire.on('navigating', (event) => {
            console.warn('Livewire navigation detected:', event.detail);
        });
        
        // Hook into Livewire errors
        window.Livewire.onError((error) => {
            console.error('Livewire Error:', error);
            
            // Send error to backend
            fetch('/api/log-frontend-error', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    error: error.toString(),
                    url: window.location.href,
                    component: error.component || 'unknown'
                })
            });
            
            // Prevent default redirect behavior
            return false;
        });
    }
    
    // Global error handler
    window.addEventListener('error', function(event) {
        console.error('Global error:', event.error);
        
        // Check if error might cause redirect
        if (event.error && event.error.message && event.error.message.includes('Livewire')) {
            event.preventDefault();
            console.error('Prevented potential redirect due to Livewire error');
        }
    });
    
    // Monitor for unexpected page changes
    let lastUrl = window.location.href;
    const checkForRedirect = () => {
        if (window.location.href !== lastUrl) {
            console.warn('Unexpected URL change detected:', {
                from: lastUrl,
                to: window.location.href
            });
            lastUrl = window.location.href;
        }
    };
    
    // Check every 500ms
    setInterval(checkForRedirect, 500);
})();