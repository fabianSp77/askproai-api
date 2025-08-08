/**
 * ULTIMATE FORCE CLICK - Nuclear option when nothing else works
 */

console.error('🔴🔴🔴 ULTIMATE FORCE CLICK - NUCLEAR OPTION ACTIVATED 🔴🔴🔴');

// Immediate execution - don't wait for anything
(function forceClicksNow() {
    // Create style element with highest priority overrides
    const criticalStyle = document.createElement('style');
    criticalStyle.id = 'ultimate-force-click-styles';
    criticalStyle.textContent = `
        /* NUCLEAR OVERRIDE - MAXIMUM PRIORITY */
        html, body, body * {
            pointer-events: auto !important;
            user-select: auto !important;
            -webkit-user-select: auto !important;
            cursor: auto !important;
        }
        
        /* Remove ALL overlays and pseudo-elements */
        *::before,
        *::after {
            display: none !important;
            content: none !important;
            pointer-events: none !important;
            position: absolute !important;
            z-index: -999999 !important;
            width: 0 !important;
            height: 0 !important;
        }
        
        /* Force remove specific problematic overlays */
        .fi-sidebar-open::before,
        .fi-sidebar-open::after,
        .fi-modal-overlay,
        .fixed.inset-0,
        [class*="overlay"],
        [style*="pointer-events: none"],
        [style*="z-index: 9"] {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
            z-index: -999999 !important;
            position: absolute !important;
            width: 0 !important;
            height: 0 !important;
        }
        
        /* FORCE all interactive elements */
        a, button, input, select, textarea,
        [role="button"], [role="link"], [onclick], [href],
        .fi-btn, .fi-link, .fi-dropdown-trigger,
        .fi-sidebar-nav a, .fi-sidebar-nav button,
        .fi-ta-action, .fi-ta-link,
        [wire\\:click], [x-on\\:click], [@click] {
            pointer-events: auto !important;
            cursor: pointer !important;
            user-select: auto !important;
            position: relative !important;
            z-index: 999999 !important;
            opacity: 1 !important;
            visibility: visible !important;
            display: inline-block !important;
        }
        
        /* Remove ALL transforms and filters */
        html, body, main, div {
            transform: none !important;
            filter: none !important;
        }
        
        /* Debug - add red border to all clickable elements */
        a, button, [role="button"], [onclick] {
            outline: 2px solid red !important;
        }
    `;
    
    // Insert at the very end of head to override everything
    if (document.head) {
        document.head.appendChild(criticalStyle);
    } else {
        document.documentElement.appendChild(criticalStyle);
    }
    
    // Force remove all blocking elements via JavaScript
    function destroyBlockers() {
        // Remove overlay elements
        const overlays = document.querySelectorAll('.fi-modal-overlay, .fixed.inset-0, [class*="overlay"]');
        overlays.forEach(el => {
            el.remove();
            console.warn('Removed blocking element:', el.className);
        });
        
        // Force all elements to be clickable
        const allElements = document.querySelectorAll('*');
        allElements.forEach(el => {
            if (el.style.pointerEvents === 'none') {
                el.style.pointerEvents = 'auto';
            }
        });
        
        // Specifically fix all links and buttons
        const clickables = document.querySelectorAll('a, button, input, select, textarea, [role="button"], [onclick], [href]');
        clickables.forEach(el => {
            el.style.pointerEvents = 'auto';
            el.style.cursor = 'pointer';
            el.style.position = 'relative';
            el.style.zIndex = '999999';
            el.style.userSelect = 'auto';
            el.style.webkitUserSelect = 'auto';
            
            // Remove disabled if not intentional
            if (el.hasAttribute('disabled') && !el.classList.contains('intentionally-disabled')) {
                el.removeAttribute('disabled');
            }
            
            // Add click handler to verify
            if (!el.dataset.forceClickAdded) {
                el.dataset.forceClickAdded = 'true';
                el.addEventListener('click', function(e) {
                    console.log('CLICK DETECTED on:', this);
                }, true);
            }
        });
        
        console.error(`ULTIMATE FORCE: Made ${clickables.length} elements clickable`);
    }
    
    // Run immediately
    destroyBlockers();
    
    // Run multiple times to catch everything
    let runs = 0;
    const interval = setInterval(() => {
        destroyBlockers();
        runs++;
        if (runs > 10) {
            clearInterval(interval);
            console.error('ULTIMATE FORCE: Completed 10 runs');
        }
    }, 200);
    
    // Also run on various events
    ['DOMContentLoaded', 'load', 'readystatechange'].forEach(event => {
        window.addEventListener(event, destroyBlockers);
        document.addEventListener(event, destroyBlockers);
    });
    
    // Expose global function
    window.ultimateForceClick = destroyBlockers;
    
    console.error('🔴 If STILL not working, type: ultimateForceClick()');
    console.error('🔴 Red borders = clickable elements');
})();