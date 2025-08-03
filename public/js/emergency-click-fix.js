/**
 * EMERGENCY CLICK FIX - Makes everything clickable immediately
 * This is a critical fix for when NOTHING on the page is clickable
 */

console.error('🚨 EMERGENCY CLICK FIX ACTIVATED - Critical UI failure detected');

(function() {
    'use strict';
    
    function forceEverythingClickable() {
        // Remove ALL blocking overlays immediately
        document.querySelectorAll('.fi-sidebar-open::before, .fi-modal-overlay, .fixed.inset-0, [style*="z-index: 9"]').forEach(el => {
            el.style.display = 'none';
            el.style.pointerEvents = 'none';
            el.remove();
        });
        
        // Force remove problematic pseudo-elements
        const style = document.createElement('style');
        style.textContent = `
            /* EMERGENCY OVERRIDE - Remove ALL blocking overlays */
            *::before,
            *::after {
                pointer-events: none !important;
                z-index: -1 !important;
            }
            
            .fi-sidebar-open::before {
                display: none !important;
                content: none !important;
                pointer-events: none !important;
                position: static !important;
                z-index: -9999 !important;
            }
            
            /* Force all elements to be interactive */
            * {
                pointer-events: auto !important;
            }
            
            /* Ensure all clickable elements work */
            a, button, input, select, textarea,
            [role="button"], [role="link"],
            .fi-btn, .fi-link, .fi-dropdown-trigger,
            .fi-sidebar-nav a, .fi-sidebar-nav button,
            .fi-ta-action, .fi-ta-link {
                pointer-events: auto !important;
                cursor: pointer !important;
                position: relative !important;
                z-index: 10 !important;
            }
            
            /* Remove any transform that might block */
            body, main, .fi-main {
                transform: none !important;
                position: relative !important;
            }
        `;
        document.head.appendChild(style);
        
        // Force all elements to be clickable via JavaScript
        document.querySelectorAll('*').forEach(el => {
            const computed = window.getComputedStyle(el);
            if (computed.pointerEvents === 'none') {
                el.style.pointerEvents = 'auto';
            }
        });
        
        // Specific fix for links and buttons
        document.querySelectorAll('a, button, input, select, textarea, [role="button"], [role="link"]').forEach(el => {
            el.style.pointerEvents = 'auto';
            el.style.cursor = 'pointer';
            el.style.position = 'relative';
            el.style.zIndex = '10';
            
            // Remove any disabled state that shouldn't be there
            if (el.hasAttribute('disabled') && !el.dataset.intentionallyDisabled) {
                el.removeAttribute('disabled');
            }
        });
        
        console.warn('Emergency click fix applied - all elements should be clickable now');
    }
    
    // Run immediately
    forceEverythingClickable();
    
    // Run on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', forceEverythingClickable);
    }
    
    // Run again after delays to catch dynamic content
    setTimeout(forceEverythingClickable, 100);
    setTimeout(forceEverythingClickable, 500);
    setTimeout(forceEverythingClickable, 1000);
    
    // Monitor for changes and reapply
    const observer = new MutationObserver(() => {
        forceEverythingClickable();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['style', 'class']
    });
    
    // Expose for manual trigger
    window.forceEverythingClickable = forceEverythingClickable;
    
    console.error('If still not working, run: forceEverythingClickable() in console');
})();