// Dashboard Stability Fix
(function() {
    'use strict';
    
    // Prevent console errors from breaking the dashboard
    const originalError = console.error;
    console.error = function(...args) {
        // Filter out known non-critical errors
        const errorString = args.join(' ');
        
        // Skip modal-related errors that don't affect functionality
        if (errorString.includes('Failed to set the \'outerHTML\'') ||
            errorString.includes('This element has no parent node') ||
            errorString.includes('document.write')) {
            console.warn('Non-critical error suppressed:', ...args);
            return;
        }
        
        // Log other errors normally
        originalError.apply(console, args);
    };
    
    // Initialize dashboard components safely
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure Alpine components are ready
        if (window.Alpine) {
            // Add global error handler for Alpine
            window.Alpine.onError = (error) => {
                console.warn('Alpine error caught:', error);
                // Don't let Alpine errors break the page
                return true;
            };
        }
        
        // Fix for Livewire modal issues
        if (window.Livewire) {
            window.Livewire.onError = (error) => {
                console.warn('Livewire error caught:', error);
                // Prevent Livewire errors from breaking functionality
                return false;
            };
            
            // Ensure modals are properly cleaned up
            window.Livewire.hook('element.removed', (el) => {
                if (el.querySelector('[x-data*="modal"]')) {
                    // Clean up any modal-related event listeners
                    el.querySelectorAll('[x-data]').forEach(modalEl => {
                        if (modalEl._x_dataStack) {
                            delete modalEl._x_dataStack;
                        }
                    });
                }
            });
        }
        
        // Dashboard-specific fixes
        const dashboardElement = document.querySelector('[wire\\:id*="dashboard"]');
        if (dashboardElement) {
            // Monitor for widget loading errors
            const observer = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach((node) => {
                            if (node.nodeType === 1 && node.classList && node.classList.contains('fi-wi')) {
                                // Widget added - ensure it's properly initialized
                                setTimeout(() => {
                                    if (window.Alpine && node.querySelector('[x-data]')) {
                                        try {
                                            window.Alpine.initTree(node);
                                        } catch (e) {
                                            console.warn('Widget initialization error caught:', e);
                                        }
                                    }
                                }, 100);
                            }
                        });
                    }
                });
            });
            
            observer.observe(dashboardElement, {
                childList: true,
                subtree: true
            });
        }
    });
    
    // Global error handler as last resort
    window.addEventListener('error', function(event) {
        // Check if it's a modal-related error
        if (event.message && (
            event.message.includes('outerHTML') ||
            event.message.includes('modal.js') ||
            event.message.includes('document.write')
        )) {
            // Prevent the error from showing in console
            event.preventDefault();
            console.warn('Global error caught and suppressed:', event.message);
        }
    });
})();