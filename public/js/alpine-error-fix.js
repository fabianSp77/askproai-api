// Alpine.js Error Handler Fix
document.addEventListener('alpine:init', () => {
    // Override Alpine's error handler to prevent popups
    Alpine.onError = (error) => {
        console.warn('Alpine.js Error:', error);
        // Silently log instead of showing popup
        return false;
    };
});

// Prevent expression errors from bubbling up
window.addEventListener('error', (event) => {
    if (event.message && event.message.includes('Alpine') || 
        event.message && event.message.includes('Expression')) {
        event.preventDefault();
        console.warn('Suppressed Alpine error:', event.message);
    }
});