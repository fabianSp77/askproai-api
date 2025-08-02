// EMERGENCY OVERLAY AND ICON FIX
// This runs immediately when loaded

(function() {
    'use strict';
    
    // Critical function to remove all overlays
    const removeAllOverlays = () => {
        // Check if body exists before accessing
        if (!document.body) return;
        
        // Remove overlay classes from body
        document.body.classList.remove('fi-sidebar-open', 'overflow-hidden', 'overflow-y-hidden');
        document.body.style.overflow = 'visible';
        document.body.style.position = 'relative';
        
        // Remove all potential overlay elements
        const overlaySelectors = [
            '.fi-sidebar-open::before',
            '.fi-sidebar-open::after',
            '[class*="overlay"]',
            '.backdrop',
            '.modal-backdrop',
            '.fixed.inset-0.bg-gray-950',
            '.fixed.inset-0.bg-black',
            '.fixed.inset-0[class*="bg-"]',
            '[x-show*="backdrop"]',
            '.fi-backdrop'
        ];
        
        overlaySelectors.forEach(selector => {
            try {
                document.querySelectorAll(selector).forEach(el => {
                    el.style.display = 'none';
                    el.style.opacity = '0';
                    el.style.visibility = 'hidden';
                    el.remove();
                });
            } catch (e) {
                // Continue even if selector fails
            }
        });
        
        // Force main content to be visible
        const mainContent = document.querySelector('.fi-main-ctn, .fi-main, main');
        if (mainContent) {
            mainContent.style.opacity = '1';
            mainContent.style.visibility = 'visible';
            mainContent.style.position = 'relative';
            mainContent.style.zIndex = '1';
        }
    };
    
    // Critical function to fix all icons
    const fixAllIcons = () => {
        document.querySelectorAll('svg').forEach(svg => {
            // Skip logos
            if (svg.closest('.fi-logo, .fi-brand, [class*="logo"], [class*="brand"]')) {
                return;
            }
            
            // Force icon size
            svg.style.cssText = 'width: 1.25rem !important; height: 1.25rem !important; max-width: 1.25rem !important; max-height: 1.25rem !important;';
            
            // Also fix parent containers
            const parent = svg.parentElement;
            if (parent && (parent.classList.contains('fi-icon') || parent.classList.contains('fi-icon-btn'))) {
                parent.style.cssText = 'width: auto !important; height: auto !important;';
            }
        });
    };
    
    // Inject critical styles immediately
    const injectCriticalStyles = () => {
        const style = document.createElement('style');
        style.innerHTML = `
            /* CRITICAL EMERGENCY FIXES */
            body.fi-sidebar-open::before,
            body.fi-sidebar-open::after,
            .fi-sidebar-open::before,
            .fi-sidebar-open::after {
                display: none !important;
            }
            
            body {
                overflow: visible !important;
                position: relative !important;
            }
            
            .fi-main-ctn {
                opacity: 1 !important;
                visibility: visible !important;
            }
            
            svg:not(.fi-logo svg):not(.fi-brand svg) {
                width: 1.25rem !important;
                height: 1.25rem !important;
            }
        `;
        document.head.insertBefore(style, document.head.firstChild);
    };
    
    // Run fixes immediately
    injectCriticalStyles();
    removeAllOverlays();
    fixAllIcons();
    
    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            removeAllOverlays();
            fixAllIcons();
        });
    }
    
    // Run on window load
    window.addEventListener('load', () => {
        removeAllOverlays();
        fixAllIcons();
    });
    
    // Monitor for changes and fix continuously
    let observer = null;
    try {
        observer = new MutationObserver(() => {
            removeAllOverlays();
            fixAllIcons();
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'style']
        });
    } catch (e) {
        // Fallback to interval if MutationObserver fails
        setInterval(() => {
            removeAllOverlays();
            fixAllIcons();
        }, 100);
    }
    
    // Override sidebar toggle if it exists
    if (window.Alpine) {
        window.Alpine.store('sidebar', {
            isOpen: false,
            open() { this.isOpen = false; },
            close() { this.isOpen = false; },
            toggle() { this.isOpen = false; }
        });
    }
    
    // Prevent any sidebar open events
    document.addEventListener('click', (e) => {
        if (e.target.matches('[x-on\\:click*="sidebar"], [onclick*="sidebar"]')) {
            e.stopPropagation();
            e.preventDefault();
            removeAllOverlays();
        }
    }, true);
    
})();