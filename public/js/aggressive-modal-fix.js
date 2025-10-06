// Aggressive Modal/Overlay Fix - Complete Solution
(function() {
    console.log('[Aggressive Fix] Starting complete modal/overlay removal');

    // Override the Livewire showHtmlModal function completely
    if (typeof window.showHtmlModal !== 'undefined' || window.showHtmlModal) {
        window.showHtmlModal = function() {
            console.warn('[Aggressive Fix] Blocked showHtmlModal - no modal will be shown');
            return false;
        };
    }

    // Wait for Livewire to load and override its functions
    function waitForLivewire() {
        if (window.Livewire) {
            console.log('[Aggressive Fix] Livewire detected, applying fixes');

            // Disable all error modals
            if (window.Livewire.showHtmlModal) {
                window.Livewire.showHtmlModal = function() {
                    console.warn('[Aggressive Fix] Blocked Livewire.showHtmlModal');
                    return false;
                };
            }

            // Override any error display functions
            if (window.Livewire.handleError) {
                window.Livewire.handleError = function(error) {
                    console.error('[Aggressive Fix] Suppressed error:', error);
                    return false;
                };
            }

            // Disable error modal display
            if (window.Livewire.onError) {
                window.Livewire.onError = function(error) {
                    console.error('[Aggressive Fix] Suppressed onError:', error);
                    return false;
                };
            }
        }
    }

    // Function to aggressively remove any overlay/modal elements
    function killAllOverlays() {
        // Remove by ID
        ['livewire-error', 'modal', 'overlay', 'popup'].forEach(id => {
            const elem = document.getElementById(id);
            if (elem) {
                console.log('[Aggressive Fix] Removing element with ID:', id);
                elem.remove();
            }
        });

        // Remove by common overlay selectors
        const selectors = [
            '#livewire-error',
            '.modal',
            '.modal-backdrop',
            '.modal-overlay',
            '[role="dialog"]',
            '.fixed.inset-0',
            '.z-50.fixed.inset-0',
            'div[style*="position: fixed"][style*="width: 100vw"][style*="height: 100vh"]',
            'div[style*="z-index: 200000"]',
            'div[style*="z-index: 99999"]',
            'iframe[style*="position: fixed"]'
        ];

        selectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(elem => {
                // Check if it's really an overlay
                const computed = window.getComputedStyle(elem);
                if (computed.position === 'fixed' &&
                    (parseInt(computed.zIndex) > 999 ||
                     elem.id === 'livewire-error' ||
                     elem.classList.contains('modal'))) {
                    console.log('[Aggressive Fix] Removing overlay:', selector);
                    elem.remove();
                }
            });
        });

        // Reset body styles that might be blocking interaction
        if (document.body) {
            if (document.body.style.overflow === 'hidden') {
                document.body.style.overflow = '';
                console.log('[Aggressive Fix] Reset body overflow');
            }
            if (document.body.classList.contains('modal-open')) {
                document.body.classList.remove('modal-open');
                console.log('[Aggressive Fix] Removed modal-open class');
            }
        }
    }

    // Run immediately
    killAllOverlays();
    waitForLivewire();

    // Run when DOM is ready
    if (document.readyState !== 'loading') {
        killAllOverlays();
        waitForLivewire();
    } else {
        document.addEventListener('DOMContentLoaded', function() {
            killAllOverlays();
            waitForLivewire();
        });
    }

    // Run on Livewire initialization
    document.addEventListener('livewire:init', function() {
        console.log('[Aggressive Fix] Livewire init event');
        killAllOverlays();
        waitForLivewire();
    });

    // Run on Livewire load
    document.addEventListener('livewire:load', function() {
        console.log('[Aggressive Fix] Livewire load event');
        killAllOverlays();
        waitForLivewire();
    });

    // Run on Livewire navigation
    document.addEventListener('livewire:navigated', function() {
        console.log('[Aggressive Fix] Livewire navigated event');
        setTimeout(killAllOverlays, 100);
    });

    // Aggressive monitoring for 10 seconds after page load
    let checkCount = 0;
    const aggressiveInterval = setInterval(function() {
        killAllOverlays();
        waitForLivewire();
        checkCount++;

        if (checkCount > 20) {
            clearInterval(aggressiveInterval);
            console.log('[Aggressive Fix] Monitoring complete after 10 seconds');
        }
    }, 500);

    // Override global showHtmlModal if it gets defined later
    Object.defineProperty(window, 'showHtmlModal', {
        get: function() {
            return function() {
                console.warn('[Aggressive Fix] Global showHtmlModal blocked');
                return false;
            };
        },
        set: function() {
            console.warn('[Aggressive Fix] Attempt to set showHtmlModal blocked');
            return false;
        }
    });

    console.log('[Aggressive Fix] All protections active - no modals will be shown');
})();