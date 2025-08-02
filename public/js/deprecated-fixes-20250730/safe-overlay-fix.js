// SAFE OVERLAY AND ICON FIX - With null checks
(function() {
    'use strict';
    
    console.log('[Safe Fix] Starting overlay removal...');
    
    // Safe element selector
    const safeQuery = (selector) => {
        try {
            return document.querySelector(selector);
        } catch (e) {
            return null;
        }
    };
    
    const safeQueryAll = (selector) => {
        try {
            return document.querySelectorAll(selector);
        } catch (e) {
            return [];
        }
    };
    
    // Safe class removal
    const safeRemoveClass = (element, className) => {
        if (element && element.classList && typeof element.classList.remove === 'function') {
            element.classList.remove(className);
        }
    };
    
    // Safe style setting
    const safeSetStyle = (element, property, value) => {
        if (element && element.style) {
            try {
                element.style[property] = value;
            } catch (e) {
                // Ignore style errors
            }
        }
    };
    
    // Main fix function
    const applyFixes = () => {
        console.log('[Safe Fix] Applying fixes...');
        
        // Fix body classes
        if (document.body) {
            safeRemoveClass(document.body, 'fi-sidebar-open');
            safeRemoveClass(document.body, 'overflow-hidden');
            safeRemoveClass(document.body, 'overflow-y-hidden');
            safeSetStyle(document.body, 'overflow', 'visible');
            safeSetStyle(document.body, 'position', 'relative');
        }
        
        // Fix html element
        if (document.documentElement) {
            safeRemoveClass(document.documentElement, 'fi-sidebar-open');
        }
        
        // Remove overlay elements
        const overlaySelectors = [
            '[class*="overlay"]',
            '.backdrop',
            '.modal-backdrop',
            '.fi-backdrop'
        ];
        
        overlaySelectors.forEach(selector => {
            const elements = safeQueryAll(selector);
            elements.forEach(el => {
                if (el && el.style) {
                    safeSetStyle(el, 'display', 'none');
                    safeSetStyle(el, 'visibility', 'hidden');
                    safeSetStyle(el, 'opacity', '0');
                }
            });
        });
        
        // Ensure main content is visible
        const mainContent = safeQuery('.fi-main-ctn') || safeQuery('.fi-main') || safeQuery('main');
        if (mainContent) {
            safeSetStyle(mainContent, 'opacity', '1');
            safeSetStyle(mainContent, 'visibility', 'visible');
            safeSetStyle(mainContent, 'position', 'relative');
            safeSetStyle(mainContent, 'zIndex', '1');
        }
        
        // Fix icon sizes
        const icons = safeQueryAll('svg');
        icons.forEach(icon => {
            if (icon && icon.style) {
                // Only fix if not already sized
                if (!icon.style.width || icon.style.width === 'auto') {
                    safeSetStyle(icon, 'width', '1.25rem');
                    safeSetStyle(icon, 'height', '1.25rem');
                }
            }
        });
        
        console.log('[Safe Fix] Fixes applied successfully');
    };
    
    // Apply fixes with multiple attempts
    const attemptFixes = () => {
        // Try immediately
        if (document.body) {
            applyFixes();
        }
        
        // Try again after DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', applyFixes);
        } else {
            // DOM already loaded, apply after a small delay
            setTimeout(applyFixes, 10);
        }
        
        // Final attempt after everything loads
        window.addEventListener('load', applyFixes);
    };
    
    // Start the fix process
    attemptFixes();
    
    // Monitor for dynamic changes (but with safety checks)
    if (window.MutationObserver) {
        let observerActive = true;
        const observer = new MutationObserver(() => {
            if (observerActive && document.body && document.body.classList.contains('fi-sidebar-open')) {
                console.log('[Safe Fix] Sidebar open detected, removing...');
                safeRemoveClass(document.body, 'fi-sidebar-open');
            }
        });
        
        // Start observing when body is available
        const startObserving = () => {
            if (document.body) {
                observer.observe(document.body, {
                    attributes: true,
                    attributeFilter: ['class']
                });
                
                // Stop after 5 seconds to prevent performance issues
                setTimeout(() => {
                    observerActive = false;
                    observer.disconnect();
                    console.log('[Safe Fix] Observer stopped');
                }, 5000);
            } else {
                // Retry after a delay
                setTimeout(startObserving, 100);
            }
        };
        
        startObserving();
    }
})();