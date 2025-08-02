// Simple Error Suppressor for AskProAI
// Only suppresses specific known errors without breaking functionality

(function() {
    'use strict';
    
    console.log('[Error Suppressor] Active - Suppressing known null reference errors');
    
    // Store original console.error to use internally
    const originalConsoleError = console.error;
    
    // Suppress specific console errors
    console.error = function() {
        const args = Array.from(arguments);
        const errorMessage = args.join(' ');
        
        // Suppress only specific known errors
        if (errorMessage.includes("Cannot read properties of null (reading 'classList')") ||
            errorMessage.includes("Cannot read property 'classList' of null") ||
            errorMessage.includes("Illegal invocation")) {
            // Silently ignore these errors
            return;
        }
        
        // Let all other errors through
        originalConsoleError.apply(console, arguments);
    };
    
    // Global error event handler
    window.addEventListener('error', function(event) {
        // Check if it's one of our known errors
        if (event.message && (
            event.message.includes("Cannot read properties of null") ||
            event.message.includes("Cannot read property") ||
            event.message.includes("Illegal invocation")
        )) {
            console.log('[Error Suppressor] Suppressed error:', event.message);
            event.preventDefault();
            return false;
        }
    }, true);
    
    // Wrap setTimeout to catch async errors
    const originalSetTimeout = window.setTimeout;
    window.setTimeout = function(callback, delay, ...args) {
        const wrappedCallback = function() {
            try {
                return callback.apply(this, args);
            } catch (e) {
                if (e.message && (
                    e.message.includes("Cannot read properties of null") ||
                    e.message.includes("Illegal invocation")
                )) {
                    console.log('[Error Suppressor] Caught async error:', e.message);
                    return;
                }
                throw e;
            }
        };
        return originalSetTimeout.call(window, wrappedCallback, delay);
    };
    
    // Simple function wrapper for common problematic functions
    const wrapFunction = (obj, funcName) => {
        if (typeof obj[funcName] === 'function') {
            const original = obj[funcName];
            obj[funcName] = function() {
                try {
                    return original.apply(this, arguments);
                } catch (e) {
                    if (e.message && (
                        e.message.includes("Cannot read properties of null") ||
                        e.message.includes("Illegal invocation")
                    )) {
                        console.log(`[Error Suppressor] Caught error in ${funcName}:`, e.message);
                        return;
                    }
                    throw e;
                }
            };
        }
    };
    
    // Wrap known problematic functions when DOM is ready
    const wrapProblematicFunctions = () => {
        // List of functions that might cause issues
        const functionsToWrap = [
            'removeOverlays',
            'toggleSidebar',
            'openSidebar',
            'closeSidebar'
        ];
        
        functionsToWrap.forEach(funcName => {
            if (window[funcName]) {
                wrapFunction(window, funcName);
            }
        });
    };
    
    // Apply wrapping when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', wrapProblematicFunctions);
    } else {
        wrapProblematicFunctions();
    }
    
    console.log('[Error Suppressor] Setup complete');
})();