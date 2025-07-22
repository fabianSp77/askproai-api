/**
 * Demo Mode Initialization Script
 * 
 * This script initializes the demo mode for the Business Portal.
 * It sets up the authentication state in localStorage and window globals
 * to bypass normal authentication checks.
 */

(function() {
    'use strict';
    
    // Set demo mode flag
    window.__DEMO_MODE__ = true;
    localStorage.setItem('demo_mode', 'true');
    
    // Get demo auth data from the page (if available)
    const appElement = document.getElementById('app');
    if (appElement) {
        const authData = appElement.dataset.auth;
        if (authData) {
            try {
                const auth = JSON.parse(authData);
                if (auth.user) {
                    // Store user data in localStorage
                    localStorage.setItem('portal_user', JSON.stringify(auth.user));
                    localStorage.setItem('portal_auth_token', 'demo_token_' + Date.now());
                    
                    // Set window auth state
                    window.__PORTAL_AUTH__ = {
                        user: auth.user,
                        isAuthenticated: true,
                        isDemo: true
                    };
                    
                    console.log('Demo mode initialized with user:', auth.user.email);
                }
            } catch (e) {
                console.error('Failed to parse auth data:', e);
            }
        }
    }
    
    // Override fetch to add demo headers
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        // Add demo mode header
        options.headers = options.headers || {};
        if (typeof options.headers === 'object' && !(options.headers instanceof Headers)) {
            options.headers['X-Demo-Mode'] = 'true';
        } else if (options.headers instanceof Headers) {
            options.headers.append('X-Demo-Mode', 'true');
        }
        
        return originalFetch.call(this, url, options);
    };
    
    // Override XMLHttpRequest to add demo headers
    const originalXHROpen = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function() {
        const xhr = this;
        const originalSetRequestHeader = xhr.setRequestHeader;
        
        xhr.setRequestHeader = function(header, value) {
            originalSetRequestHeader.call(xhr, header, value);
            if (header !== 'X-Demo-Mode') {
                originalSetRequestHeader.call(xhr, 'X-Demo-Mode', 'true');
            }
        };
        
        return originalXHROpen.apply(xhr, arguments);
    };
    
    // Prevent auth redirects in demo mode
    const checkInterval = setInterval(function() {
        if (window.location.pathname === '/business/login' && window.__DEMO_MODE__) {
            console.log('Preventing redirect to login in demo mode');
            window.location.href = '/business';
            clearInterval(checkInterval);
        }
    }, 100);
    
    // Clear interval after 5 seconds
    setTimeout(function() {
        clearInterval(checkInterval);
    }, 5000);
    
})();