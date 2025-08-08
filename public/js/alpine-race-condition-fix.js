/**
 * Alpine.js Race Condition Fix
 * Prevents flash of unstyled content and interaction issues
 * Before Alpine components are fully initialized
 */

// Mark Alpine as loading
document.documentElement.classList.add('alpine-loading');

// Wait for Alpine to be available
function waitForAlpine() {
    if (typeof Alpine !== 'undefined') {
        initializeAlpineFix();
    } else {
        setTimeout(waitForAlpine, 10);
    }
}

function initializeAlpineFix() {
    // Before Alpine starts
    document.addEventListener('alpine:init', () => {
        console.log('Alpine.js initializing...');
        
        // Mark all x-data elements as not ready
        document.querySelectorAll('[x-data]').forEach(el => {
            el.removeAttribute('data-alpine-ready');
        });
    });
    
    // When Alpine has finished initializing components
    document.addEventListener('alpine:initialized', () => {
        console.log('Alpine.js initialized');
        
        // Mark document as Alpine-ready
        document.documentElement.classList.remove('alpine-loading');
        document.documentElement.classList.add('alpine-ready');
        
        // Mark all x-data elements as ready
        document.querySelectorAll('[x-data]').forEach(el => {
            el.setAttribute('data-alpine-ready', '');
        });
        
        // Remove x-cloak attributes
        document.querySelectorAll('[x-cloak]').forEach(el => {
            el.removeAttribute('x-cloak');
        });
    });
    
    // Handle individual components
    Alpine.directive('ready', (el) => {
        el.setAttribute('data-alpine-ready', '');
    });
    
    // Start Alpine
    if (!Alpine.store) {
        Alpine.start();
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', waitForAlpine);
} else {
    waitForAlpine();
}