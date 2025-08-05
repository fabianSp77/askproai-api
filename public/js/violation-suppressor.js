// Violation Suppressor - Must run as early as possible
(function() {
    'use strict';
    
    // First, override document.write to prevent the violations from happening
    try {
        // Store original functions
        const originalWrite = document.write;
        const originalWriteln = document.writeln;
        
        // Create safe no-op functions
        const noOp = function() { return; };
        
        // Try to override using defineProperty (most robust)
        try {
            Object.defineProperty(document, 'write', {
                value: noOp,
                writable: false,
                configurable: false
            });
            
            Object.defineProperty(document, 'writeln', {
                value: noOp,
                writable: false,
                configurable: false
            });
        } catch (e) {
            // Fallback: simple assignment
            document.write = noOp;
            document.writeln = noOp;
        }
    } catch (e) {
        // If all else fails, at least log that we tried
        console.log('Could not override document.write');
    }
    
    // Store original console methods
    const originalConsole = {
        log: console.log,
        warn: console.warn,
        error: console.error,
        info: console.info,
        debug: console.debug
    };
    
    // Create a flag to track if we're in a violation message
    let inViolation = false;
    
    // Override console methods to filter violations
    Object.keys(originalConsole).forEach(method => {
        console[method] = function(...args) {
            // Convert arguments to check for violations
            const message = args.map(arg => String(arg)).join(' ');
            
            // Check if this is a violation
            if (message.includes('[Violation]') && 
                (message.includes('document.write') || 
                 message.includes('Avoid using document.write'))) {
                // Skip this message
                return;
            }
            
            // Otherwise, call the original method
            originalConsole[method].apply(console, args);
        };
    });
    
    // Additional attempt to suppress violations at the browser level
    if (window.console && console.log.toString().indexOf('[native code]') === -1) {
        // Console has already been wrapped, let's wrap the wrapper
        const currentLog = console.log;
        console.log = function(...args) {
            const msg = args.join(' ');
            if (!msg.includes('[Violation]')) {
                currentLog.apply(console, args);
            }
        };
    }
})();