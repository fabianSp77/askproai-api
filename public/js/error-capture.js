// Error Capture System - Catches ALL JavaScript errors
(function() {
    console.log('[Error Capture] System initialized');

    let errorCount = 0;
    let errors = [];

    // Capture all unhandled errors
    window.addEventListener('error', function(e) {
        errorCount++;
        const errorInfo = {
            message: e.message,
            filename: e.filename,
            line: e.lineno,
            col: e.colno,
            error: e.error ? e.error.stack : 'No stack trace',
            timestamp: new Date().toISOString()
        };

        errors.push(errorInfo);

        console.error('[Error Capture] Error #' + errorCount + ':', errorInfo);

        // Check if this is a 500 error or modal.js error
        if (e.message && (e.message.includes('500') || e.message.includes('modal') || e.filename && e.filename.includes('modal'))) {
            console.error('[Error Capture] CRITICAL: Modal/500 error detected!', errorInfo);

            // Try to prevent the error popup
            if (e.preventDefault) {
                e.preventDefault();
            }
            return false;
        }
    }, true);

    // Capture unhandled promise rejections
    window.addEventListener('unhandledrejection', function(e) {
        errorCount++;
        console.error('[Error Capture] Unhandled Promise Rejection:', e.reason);
        errors.push({
            type: 'promise',
            reason: e.reason,
            timestamp: new Date().toISOString()
        });

        // Check for 500 errors in promise rejections
        if (e.reason && (e.reason.toString().includes('500') || e.reason.toString().includes('modal'))) {
            console.error('[Error Capture] CRITICAL: 500 error in promise!');
            e.preventDefault();
            return false;
        }
    });

    // Override console.error to catch all errors
    const originalError = console.error;
    console.error = function() {
        originalError.apply(console, arguments);

        const errorStr = Array.from(arguments).join(' ');
        if (errorStr.includes('500') || errorStr.includes('modal')) {
            console.warn('[Error Capture] 500/modal error logged:', errorStr);
        }
    };

    // Expose error list for debugging
    window.getCapturedErrors = function() {
        return errors;
    };

    // Monitor AJAX requests for 500 errors
    const originalFetch = window.fetch;
    window.fetch = function(...args) {
        return originalFetch.apply(this, args).then(response => {
            if (response.status === 500) {
                console.error('[Error Capture] 500 response from:', args[0]);
                console.error('Response status:', response.status);
                console.error('Response headers:', response.headers);
            }
            return response;
        }).catch(error => {
            console.error('[Error Capture] Fetch error:', error);
            throw error;
        });
    };

    // Monitor XMLHttpRequest for 500 errors
    const originalOpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url) {
        this.addEventListener('load', function() {
            if (this.status === 500) {
                console.error('[Error Capture] XHR 500 response from:', url);
                console.error('Response:', this.responseText);
            }
        });
        return originalOpen.apply(this, arguments);
    };

    console.log('[Error Capture] All error handlers installed');
})();