/**
 * Fix for document.write() violations
 * Prevents document.write() from being used after page load
 */
(function() {
    'use strict';
    
    // Save original document.write
    const originalWrite = document.write;
    const originalWriteln = document.writeln;
    
    // Override document.write to prevent issues
    document.write = function(...args) {
        if (document.readyState === 'loading') {
            // During initial page load, allow document.write
            return originalWrite.apply(document, args);
        } else {
            // After page load, log warning instead
            console.warn('document.write() called after page load. Content:', args);
            
            // Try to append content to body instead
            if (args.length > 0 && typeof args[0] === 'string') {
                try {
                    const div = document.createElement('div');
                    div.innerHTML = args[0];
                    document.body.appendChild(div);
                } catch (e) {
                    console.error('Failed to append content:', e);
                }
            }
        }
    };
    
    document.writeln = function(...args) {
        if (document.readyState === 'loading') {
            return originalWriteln.apply(document, args);
        } else {
            console.warn('document.writeln() called after page load. Content:', args);
            document.write(args.join('') + '\n');
        }
    };
    
    // Log when this fix is active
    console.log('✅ document.write() fix active - violations will be handled gracefully');
})();