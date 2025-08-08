/**
 * Portal CSRF Token Setup
 * Ensures all AJAX requests include the CSRF token
 */
(function() {
    'use strict';
    
    // Get CSRF token from meta tag
    const token = document.querySelector('meta[name="csrf-token"]');
    
    if (!token) {
        console.error('[CSRF Setup] No CSRF token found in meta tags!');
        return;
    }
    
    const csrfToken = token.getAttribute('content');
    console.log('[CSRF Setup] Token found and configured');
    
    // Configure axios if available
    if (typeof axios !== 'undefined') {
        axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken;
        axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        console.log('[CSRF Setup] Axios configured with CSRF token');
    }
    
    // Configure jQuery AJAX if available
    if (typeof $ !== 'undefined' && $.ajaxSetup) {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        console.log('[CSRF Setup] jQuery AJAX configured with CSRF token');
    }
    
    // Configure native fetch
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        // Skip for external URLs
        if (url.toString().startsWith('http') && !url.toString().includes(window.location.host)) {
            return originalFetch(url, options);
        }
        
        // Ensure headers object exists
        if (!options.headers) {
            options.headers = {};
        }
        
        // Add CSRF token for non-GET requests
        const method = (options.method || 'GET').toUpperCase();
        if (method !== 'GET' && method !== 'HEAD') {
            options.headers['X-CSRF-TOKEN'] = csrfToken;
        }
        
        // Always add X-Requested-With
        options.headers['X-Requested-With'] = 'XMLHttpRequest';
        
        // Add credentials for same-origin requests
        if (!options.credentials) {
            options.credentials = 'same-origin';
        }
        
        return originalFetch(url, options);
    };
    
    console.log('[CSRF Setup] Native fetch configured with CSRF token');
    
    // Helper function for manual AJAX requests
    window.getCSRFToken = function() {
        return csrfToken;
    };
    
    // Add CSRF token to all forms without one
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form:not([method="GET"])');
        forms.forEach(form => {
            if (!form.querySelector('input[name="_token"]')) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = '_token';
                input.value = csrfToken;
                form.appendChild(input);
                console.log('[CSRF Setup] Added token to form:', form.action);
            }
        });
    });
    
})();