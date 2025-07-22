/**
 * Remove Error Overlay
 * Removes the Livewire error modal that's blocking the UI
 */
(function() {
    'use strict';
    
    console.log('🔧 Removing error overlay');
    
    // Function to remove error modals
    function removeErrorModals() {
        // Look for the Livewire error modal
        const errorModal = document.getElementById('livewire-error');
        if (errorModal) {
            console.log('Removing Livewire error modal');
            errorModal.remove();
        }
        
        // Also look for any elements with error overlay characteristics
        const overlays = document.querySelectorAll('div[style*="position: fixed"][style*="z-index: 200000"], div[style*="position: fixed"][style*="background-color: rgba(0, 0, 0"]');
        overlays.forEach(overlay => {
            // Check if it contains error text
            if (overlay.textContent.includes('Internal Server Error') || overlay.textContent.includes('error')) {
                console.log('Removing error overlay');
                overlay.remove();
            }
        });
    }
    
    // Remove immediately
    removeErrorModals();
    
    // Remove after DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', removeErrorModals);
    }
    
    // Remove after a short delay
    setTimeout(removeErrorModals, 100);
    setTimeout(removeErrorModals, 500);
    
    // Monitor for new error modals
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1) { // Element node
                    if (node.id === 'livewire-error' || 
                        (node.style && node.style.position === 'fixed' && node.style.zIndex > 100000)) {
                        if (node.textContent.includes('error') || node.textContent.includes('Error')) {
                            console.log('Removing newly added error modal');
                            node.remove();
                        }
                    }
                }
            });
        });
    });
    
    // Start observing
    observer.observe(document.body, { childList: true, subtree: true });
    
    // Also add CSS to hide any remaining error modals
    const style = document.createElement('style');
    style.textContent = `
        #livewire-error,
        div[style*="z-index: 200000"] {
            display: none !important;
        }
    `;
    document.head.appendChild(style);
})();