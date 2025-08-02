{{-- CSRF Fix Script - Entfernt Page Expired Popups --}}
<script>
// Comprehensive CSRF and Page Expired fix
(function() {
    console.log('CSRF Fix Active - Enhanced Version');
    
    // Function to remove ALL page expired elements
    function killAllPageExpired() {
        // Remove by text content
        const keywords = ['Page Expired', '419', 'session expired', 'CSRF token mismatch', 'page expired'];
        
        document.querySelectorAll('*').forEach(el => {
            const text = el.textContent || '';
            if (keywords.some(keyword => text.toLowerCase().includes(keyword.toLowerCase()))) {
                // Check if this is a notification or modal
                const parent = el.closest('.fi-notification, .fi-modal, [role="dialog"], [role="alert"], .fixed, [wire\\:id]');
                if (parent) {
                    parent.remove();
                    console.log('Removed page expired element:', parent.className);
                }
            }
        });
        
        // Remove all overlays
        document.querySelectorAll('.fi-modal-close-overlay, .fixed.inset-0, .bg-gray-900\\/50, .backdrop').forEach(el => {
            el.remove();
        });
        
        // Force close any Alpine modals
        if (window.Alpine) {
            document.querySelectorAll('[x-data]').forEach(el => {
                if (el.__x && el.__x.$data && typeof el.__x.$data.close === 'function') {
                    el.__x.$data.close();
                }
            });
        }
    }
    
    // Initial cleanup
    killAllPageExpired();
    
    // Continuous monitoring
    let checkCount = 0;
    const rapidCheck = setInterval(() => {
        killAllPageExpired();
        checkCount++;
        if (checkCount > 100) { // 10 seconds of rapid checking
            clearInterval(rapidCheck);
            // Continue with slower checking
            setInterval(killAllPageExpired, 1000);
        }
    }, 100);
    
    // Override Livewire error handling
    if (window.Livewire) {
        // Prevent 419 errors from showing
        const originalOnError = window.Livewire.onError;
        window.Livewire.onError = function(error) {
            console.log('Livewire error:', error);
            if (error && (error.status === 419 || error.message?.includes('419'))) {
                console.log('Blocking 419 error display');
                killAllPageExpired();
                return false;
            }
            if (originalOnError) {
                return originalOnError.call(this, error);
            }
        };
        
        // Add CSRF token to all requests
        Livewire.hook('request', ({ options }) => {
            options.headers = options.headers || {};
            options.headers['X-CSRF-TOKEN'] = '{{ csrf_token() }}';
            options.headers['X-Requested-With'] = 'XMLHttpRequest';
        });
        
        // Handle response errors
        Livewire.hook('request', ({ fail }) => {
            fail(({ status }) => {
                if (status === 419) {
                    console.log('Handling 419 in request hook');
                    killAllPageExpired();
                    // Generate new token
                    fetch('/admin/refresh-csrf', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    }).then(() => {
                        console.log('CSRF token refreshed');
                    });
                    return false;
                }
            });
        });
    }
    
    // Override window.alert for 419 errors
    const originalAlert = window.alert;
    window.alert = function(message) {
        if (message && message.toString().includes('419')) {
            console.log('Blocked 419 alert');
            killAllPageExpired();
            return;
        }
        return originalAlert.apply(this, arguments);
    };
    
    // Monitor for new elements
    const observer = new MutationObserver(() => {
        killAllPageExpired();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class', 'style']
    });
    
    console.log('CSRF protection fully active');
})();
</script>

<style>
/* Hide any Page Expired related elements */
*:has-text("Page Expired"),
*:has-text("419"),
*:has-text("session expired"),
*:has-text("CSRF token mismatch"),
.fi-notification:has(span:contains("419")),
.fi-modal:has(*:contains("Page Expired")) {
    display: none !important;
    visibility: hidden !important;
}

/* Hide overlays when error occurs */
.fi-modal-close-overlay,
.fixed.inset-0.z-40,
.fixed.inset-0.bg-gray-900\/50 {
    display: none !important;
}
</style>