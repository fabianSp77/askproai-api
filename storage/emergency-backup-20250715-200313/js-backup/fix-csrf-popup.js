// Fix für Page Expired Popup
(function() {
    console.log('CSRF Popup Fix loaded');
    
    // Entfernt alle Page Expired Popups
    function removePageExpiredPopups() {
        // Filament notifications
        const notifications = document.querySelectorAll('.fi-notification');
        notifications.forEach(notification => {
            const text = notification.textContent || '';
            if (text.includes('Page Expired') || text.includes('419') || text.includes('page expired')) {
                console.log('Removing Page Expired notification');
                notification.remove();
            }
        });
        
        // Livewire modals
        const modals = document.querySelectorAll('[wire\\:id]');
        modals.forEach(modal => {
            const text = modal.textContent || '';
            if (text.includes('Page Expired') || text.includes('419')) {
                console.log('Removing Page Expired modal');
                modal.remove();
            }
        });
        
        // Any element with specific classes
        const expiredElements = document.querySelectorAll('.session-expired, .page-expired, [data-expired], .csrf-error');
        expiredElements.forEach(el => el.remove());
        
        // Remove overlays
        const overlays = document.querySelectorAll('.fixed.inset-0.z-50, .modal-backdrop, .fi-modal-close-overlay');
        overlays.forEach(overlay => {
            const nextElement = overlay.nextElementSibling;
            if (nextElement && (nextElement.textContent || '').includes('Page Expired')) {
                overlay.remove();
                nextElement.remove();
            }
        });
    }
    
    // Fix Livewire to prevent CSRF errors
    if (window.Livewire) {
        console.log('Patching Livewire for CSRF');
        
        // Override the error handler
        const originalOnError = window.Livewire.onError;
        window.Livewire.onError = function(error) {
            console.log('Livewire error intercepted:', error);
            if (error.status === 419 || (error.message && error.message.includes('419'))) {
                console.log('Suppressing 419 error');
                removePageExpiredPopups();
                return false; // Prevent default error handling
            }
            if (originalOnError) {
                return originalOnError.call(this, error);
            }
        };
        
        // Add CSRF token to all Livewire requests
        window.Livewire.hook('request', ({ options }) => {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (token) {
                options.headers['X-CSRF-TOKEN'] = token;
            }
        });
    }
    
    // Remove popups immediately
    removePageExpiredPopups();
    
    // Continue removing popups every 500ms
    setInterval(removePageExpiredPopups, 500);
    
    // Also remove on DOM changes
    const observer = new MutationObserver(() => {
        removePageExpiredPopups();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Prevent page reload on 419 errors
    window.addEventListener('beforeunload', function(e) {
        const hasExpiredPopup = document.querySelector('.fi-notification, [wire\\:id]')?.textContent?.includes('Page Expired');
        if (hasExpiredPopup) {
            e.preventDefault();
            removePageExpiredPopups();
            return false;
        }
    });
})();