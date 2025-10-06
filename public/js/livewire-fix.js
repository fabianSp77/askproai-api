// Livewire document.write() Fix - Version 2.0
// This fixes the modal.js:36 error that causes 500 popup after login

(function() {
    console.log('[Livewire Fix] Initializing document.write override...');
    
    // Store original document.write (just in case)
    const originalWrite = document.write;
    
    // Override document.write globally
    document.write = function(content) {
        console.warn('[Livewire Fix] Blocked document.write() call - using safe DOM manipulation instead');
        
        // If we're in an iframe, handle differently
        if (window.frameElement) {
            document.documentElement.innerHTML = content;
        } else {
            // For main document, append to body
            const temp = document.createElement('div');
            temp.innerHTML = content;
            while (temp.firstChild) {
                document.body.appendChild(temp.firstChild);
            }
        }
    };
    
    // Also override writeln
    document.writeln = document.write;
    
    // Monitor for modal errors
    window.addEventListener('error', function(e) {
        if (e.message && e.message.includes('document.write')) {
            console.error('[Livewire Fix] Caught document.write error:', e.message);
            e.preventDefault();
            return false;
        }
    });
    
    console.log('[Livewire Fix] Protection active - document.write() calls will be safely handled');
})();
