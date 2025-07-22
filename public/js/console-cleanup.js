/**
 * Console Cleanup Script
 * Reduces console noise by filtering non-critical messages
 */
(function() {
    'use strict';
    
    // Messages to suppress (not errors, just noise)
    const suppressPatterns = [
        /CSRF Fix Active/,
        /CSRF protection fully active/,
        /Avoid using document\.write/,
        /\[Violation\]/,
        /Livewire error modal attempt/
    ];
    
    // Save original console methods
    const originalConsole = {
        log: console.log,
        warn: console.warn,
        error: console.error,
        info: console.info
    };
    
    // Helper to check if message should be suppressed
    function shouldSuppress(args) {
        const message = args.map(arg => String(arg)).join(' ');
        return suppressPatterns.some(pattern => pattern.test(message));
    }
    
    // Override console methods to filter messages
    console.log = function(...args) {
        if (!shouldSuppress(args)) {
            originalConsole.log.apply(console, args);
        }
    };
    
    console.info = function(...args) {
        if (!shouldSuppress(args)) {
            originalConsole.info.apply(console, args);
        }
    };
    
    // For warnings, convert violations to debug level
    console.warn = function(...args) {
        const message = args.map(arg => String(arg)).join(' ');
        
        // Convert document.write violations to debug messages
        if (message.includes('Avoid using document.write')) {
            // Log as debug info if needed
            if (window.DEBUG_MODE) {
                originalConsole.log('[Violation]', ...args);
            }
        } else if (!shouldSuppress(args)) {
            originalConsole.warn.apply(console, args);
        }
    };
    
    // Never suppress errors
    console.error = function(...args) {
        originalConsole.error.apply(console, args);
    };
    
    // Provide a way to see all messages if needed
    window.showAllConsoleLogs = function() {
        console.log = originalConsole.log;
        console.warn = originalConsole.warn;
        console.error = originalConsole.error;
        console.info = originalConsole.info;
        console.log('Console filtering disabled. All messages will now be shown.');
    };
    
    // Log that cleanup is active
    originalConsole.log('🧹 Console cleanup active. Use showAllConsoleLogs() to see all messages.');
})();