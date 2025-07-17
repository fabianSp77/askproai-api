// Livewire 404 Popup Fix - Specific fix for 404 error popups
(function() {
    console.log('[Livewire 404 Popup Fix] Initializing...');
    
    // Monitor for Filament modals/popups
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            mutation.addedNodes.forEach(function(node) {
                if (node.nodeType !== 1) return; // Skip non-element nodes
                
                // Check for Filament modals or dialogs
                if (node.matches && (
                    node.matches('[role="dialog"]') || 
                    node.matches('.filament-modal') ||
                    node.matches('[x-data*="modal"]') ||
                    node.classList.contains('fi-modal')
                )) {
                    const text = node.innerText || '';
                    if (text.includes('404') || text.includes('Not Found')) {
                        console.log('[Livewire 404 Popup Fix] Detected and removing 404 popup');
                        node.remove();
                        
                        // Also remove any backdrop
                        const backdrop = document.querySelector('.filament-modal-backdrop, [x-show="isOpen"].fixed.inset-0');
                        if (backdrop) {
                            backdrop.remove();
                        }
                    }
                }
            });
        });
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Override Livewire's HTTP handling
    if (window.Livewire) {
        // Intercept Livewire responses
        const originalRequest = window.Livewire.connection.sendMessage;
        
        window.Livewire.connection.sendMessage = function() {
            const result = originalRequest.apply(this, arguments);
            
            if (result && result.catch) {
                result.catch(error => {
                    console.warn('[Livewire 404 Popup Fix] Caught Livewire error:', error);
                    
                    // Prevent 404 errors from showing popups
                    if (error.response && error.response.status === 404) {
                        console.log('[Livewire 404 Popup Fix] Suppressing 404 error popup');
                        return Promise.resolve({
                            effects: {},
                            serverMemo: {}
                        });
                    }
                    
                    throw error;
                });
            }
            
            return result;
        };
        
        // Also intercept the commit method
        const originalCommit = window.Livewire.commit;
        if (originalCommit) {
            window.Livewire.commit = function() {
                try {
                    return originalCommit.apply(this, arguments);
                } catch (error) {
                    console.warn('[Livewire 404 Popup Fix] Caught commit error:', error);
                    if (error.message && error.message.includes('404')) {
                        console.log('[Livewire 404 Popup Fix] Suppressing 404 in commit');
                        return;
                    }
                    throw error;
                }
            };
        }
    }
    
    // Override window.fetch to intercept 404s
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args).then(response => {
            if (response.status === 404 && args[0] && args[0].includes('livewire')) {
                console.log('[Livewire 404 Popup Fix] Intercepted 404 on:', args[0]);
                
                // Return a fake successful response for Livewire
                return new Response(JSON.stringify({
                    effects: {},
                    serverMemo: {}
                }), {
                    status: 200,
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
            }
            return response;
        });
    };
    
    // Remove any existing 404 popups on page load
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(() => {
            const popups = document.querySelectorAll('[role="dialog"], .filament-modal, .fi-modal');
            popups.forEach(popup => {
                const text = popup.innerText || '';
                if (text.includes('404') || text.includes('Not Found')) {
                    console.log('[Livewire 404 Popup Fix] Removing existing 404 popup on load');
                    popup.remove();
                }
            });
        }, 500);
    });
    
    // Listen for Livewire navigation
    document.addEventListener('livewire:navigated', function() {
        console.log('[Livewire 404 Popup Fix] Page navigated, checking for 404 popups...');
        setTimeout(() => {
            const popups = document.querySelectorAll('[role="dialog"], .filament-modal, .fi-modal');
            popups.forEach(popup => {
                const text = popup.innerText || '';
                if (text.includes('404') || text.includes('Not Found')) {
                    console.log('[Livewire 404 Popup Fix] Removing 404 popup after navigation');
                    popup.remove();
                }
            });
        }, 100);
    });
    
    console.log('[Livewire 404 Popup Fix] Initialized successfully');
})();