/**
 * Bypass ALL event handlers and navigate directly
 */

(function() {
    'use strict';
    
    console.error('🔴 BYPASSING ALL EVENT HANDLERS');
    
    // Override addEventListener to log what's blocking
    const originalAddEventListener = EventTarget.prototype.addEventListener;
    EventTarget.prototype.addEventListener = function(type, listener, options) {
        if (type === 'click') {
            console.warn('Click listener added to:', this);
        }
        return originalAddEventListener.call(this, type, listener, options);
    };
    
    // Force direct navigation on all links
    function hijackAllLinks() {
        document.querySelectorAll('a').forEach(link => {
            const href = link.href || link.getAttribute('href');
            if (href && !link.dataset.hijacked) {
                link.dataset.hijacked = 'true';
                
                // Create wrapper div that captures clicks
                const wrapper = document.createElement('div');
                wrapper.style.cssText = `
                    position: absolute;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    z-index: 999999;
                    cursor: pointer;
                    background: rgba(255,0,0,0.1);
                `;
                
                // Make link relative positioned
                link.style.position = 'relative';
                
                // Add wrapper
                link.appendChild(wrapper);
                
                // Direct click handler
                wrapper.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    console.log('HIJACKED CLICK - Navigating to:', href);
                    window.location.href = href;
                    return false;
                }, true);
            }
        });
        
        // Also handle buttons with wire:navigate
        document.querySelectorAll('button').forEach(btn => {
            const wireNavigate = btn.getAttribute('wire:navigate');
            if (wireNavigate && !btn.dataset.hijacked) {
                btn.dataset.hijacked = 'true';
                
                btn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('HIJACKED BUTTON - Navigating to:', wireNavigate);
                    window.location.href = wireNavigate;
                    return false;
                }, true);
            }
        });
    }
    
    // Run immediately and repeatedly
    hijackAllLinks();
    setInterval(hijackAllLinks, 1000);
    
    // Global click interceptor
    document.addEventListener('mousedown', function(e) {
        const link = e.target.closest('a');
        if (link && link.href) {
            e.preventDefault();
            e.stopPropagation();
            console.log('GLOBAL INTERCEPTOR - Navigating to:', link.href);
            window.location.href = link.href;
            return false;
        }
    }, true);
    
    console.error('All links should now have red overlay - click anywhere on them');
})();