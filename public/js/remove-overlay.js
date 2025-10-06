// Remove Dashboard Overlay - Aggressive Fix
(function() {
    console.log('[Overlay Remover] Initializing...');

    // Function to remove overlays
    function removeOverlays() {
        // Remove Livewire error modal
        const livewireError = document.getElementById('livewire-error');
        if (livewireError) {
            console.log('[Overlay Remover] Removing Livewire error modal');
            livewireError.remove();
        }

        // Remove any modal overlays
        const overlays = document.querySelectorAll([
            '#livewire-error',
            '.modal-overlay',
            '.modal-backdrop',
            '[role="dialog"]',
            '.fixed.inset-0',
            '.z-50.fixed',
            'div[style*="z-index: 200000"]',
            'div[style*="position: fixed"][style*="width: 100vw"]'
        ].join(','));

        overlays.forEach(overlay => {
            // Check if it's actually an overlay
            const styles = window.getComputedStyle(overlay);
            if (styles.position === 'fixed' &&
                (styles.zIndex > 1000 || overlay.id === 'livewire-error')) {
                console.log('[Overlay Remover] Removing overlay:', overlay.id || overlay.className);
                overlay.remove();
            }
        });

        // Reset body overflow
        if (document.body.style.overflow === 'hidden') {
            document.body.style.overflow = 'visible';
            console.log('[Overlay Remover] Reset body overflow');
        }
    }

    // Remove immediately
    removeOverlays();

    // Remove after DOM ready
    if (document.readyState !== 'loading') {
        removeOverlays();
    } else {
        document.addEventListener('DOMContentLoaded', removeOverlays);
    }

    // Remove after Livewire loads
    document.addEventListener('livewire:load', function() {
        console.log('[Overlay Remover] Livewire loaded, removing overlays');
        removeOverlays();
    });

    // Remove after navigation
    document.addEventListener('livewire:navigated', function() {
        console.log('[Overlay Remover] Navigation detected, removing overlays');
        removeOverlays();
    });

    // Continuous monitoring for first 5 seconds
    let checks = 0;
    const interval = setInterval(() => {
        removeOverlays();
        checks++;
        if (checks > 10) {
            clearInterval(interval);
            console.log('[Overlay Remover] Monitoring complete');
        }
    }, 500);

    // Prevent showHtmlModal from creating new overlays
    if (typeof showHtmlModal !== 'undefined') {
        window.showHtmlModal = function(html) {
            console.warn('[Overlay Remover] Blocked showHtmlModal');
            return;
        };
    }

    console.log('[Overlay Remover] Protection active');
})();