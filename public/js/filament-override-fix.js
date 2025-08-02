/**
 * Filament Override Fix
 * Directly fixes Filament's dropdown and link behavior
 */

console.log('[Filament Override] Starting fix...');

// Wait for both Alpine and Livewire to be ready
let attempts = 0;
const maxAttempts = 50;

function waitForFrameworks() {
    attempts++;
    
    if (typeof Alpine !== 'undefined' && typeof Livewire !== 'undefined') {
        console.log('[Filament Override] Frameworks ready, applying fixes...');
        applyFixes();
    } else if (attempts < maxAttempts) {
        setTimeout(waitForFrameworks, 100);
    } else {
        console.error('[Filament Override] Frameworks not loaded after 5 seconds');
    }
}

function applyFixes() {
    // Fix 1: Override Filament's dropdown behavior
    fixDropdowns();
    
    // Fix 2: Force all links to be clickable
    fixLinks();
    
    // Fix 3: Monitor for changes
    setupObserver();
    
    console.log('[Filament Override] All fixes applied!');
}

function fixDropdowns() {
    console.log('[Filament Override] Fixing dropdowns...');
    
    // Find and fix all Filament dropdowns
    const fixDropdown = (dropdown) => {
        const trigger = dropdown.querySelector('[x-on\\:click]');
        if (!trigger) return;
        
        // Get the original click handler
        const originalClick = trigger.getAttribute('x-on:click');
        
        // Remove the original handler
        trigger.removeAttribute('x-on:click');
        
        // Add our own handler
        trigger.addEventListener('click', function(e) {
            // Only prevent default for dropdown triggers, not form elements
            if (!e.target.matches('input, select, textarea, label')) {
                e.preventDefault();
            }
            e.stopPropagation();
            
            // Get Alpine data
            const alpineComponent = Alpine.$data(dropdown);
            if (alpineComponent && typeof alpineComponent.open !== 'undefined') {
                alpineComponent.open = !alpineComponent.open;
                console.log('[Filament Override] Toggled dropdown:', alpineComponent.open);
            }
        });
    };
    
    // Fix all current dropdowns
    document.querySelectorAll('[x-data*="open"]').forEach(fixDropdown);
    
    // Also add a global delegated handler for dynamically added dropdowns
    document.addEventListener('click', function(e) {
        const trigger = e.target.closest('.fi-dropdown-trigger, [x-on\\:click*="toggle"], [x-on\\:click*="open = !open"]');
        if (!trigger) return;
        
        const dropdown = trigger.closest('[x-data]');
        if (!dropdown) return;
        
        // Only prevent default for non-form elements
        if (!e.target.matches('input, select, textarea, label, input[type="radio"], input[type="checkbox"]')) {
            e.preventDefault();
        }
        e.stopPropagation();
        
        // Use Alpine's magic properties to toggle
        if (dropdown.__x) {
            const data = dropdown.__x.$data;
            if (data && typeof data.open !== 'undefined') {
                data.open = !data.open;
                console.log('[Filament Override] Toggled via __x:', data.open);
            }
        }
    }, true); // Use capture to intercept before other handlers
    
    // Close dropdowns on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[x-data*="open"]')) {
            document.querySelectorAll('[x-data*="open"]').forEach(dropdown => {
                if (dropdown.__x) {
                    const data = dropdown.__x.$data;
                    if (data && data.open === true) {
                        data.open = false;
                    }
                }
            });
        }
    });
}

function fixLinks() {
    console.log('[Filament Override] Fixing links...');
    
    // Remove ALL pointer-events: none
    const style = document.createElement('style');
    style.textContent = `
        /* Filament Override - Force clickability */
        * {
            pointer-events: auto !important;
        }
        
        /* Specific overrides for Filament elements */
        .fi-link,
        .fi-btn,
        .fi-dropdown-trigger,
        .fi-dropdown-item,
        .fi-ta-link,
        .fi-ta-action,
        .fi-icon-btn,
        a,
        button,
        [role="button"],
        [role="link"],
        [wire\\:click],
        [x-on\\:click],
        [onclick] {
            pointer-events: auto !important;
            cursor: pointer !important;
            user-select: none !important;
        }
        
        /* Ensure dropdown panels are on top */
        .fi-dropdown-panel {
            z-index: 99999 !important;
            pointer-events: auto !important;
        }
        
        /* Only loading indicators should not capture clicks */
        .fi-loading-indicator {
            pointer-events: none !important;
        }
    `;
    document.head.appendChild(style);
    
    // Also fix inline styles
    document.querySelectorAll('[style*="pointer-events"]').forEach(el => {
        if (el.matches('a, button, [role="button"], .fi-link, .fi-btn')) {
            el.style.pointerEvents = 'auto';
        }
    });
}

function setupObserver() {
    console.log('[Filament Override] Setting up observer...');
    
    const observer = new MutationObserver((mutations) => {
        let needsFix = false;
        
        mutations.forEach(mutation => {
            // Check if dropdowns were added
            if (mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        if (node.matches?.('[x-data*="open"]') || node.querySelector?.('[x-data*="open"]')) {
                            needsFix = true;
                        }
                    }
                });
            }
        });
        
        if (needsFix) {
            setTimeout(() => {
                fixDropdowns();
                fixLinks();
            }, 100);
        }
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
}

// Start the process
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', waitForFrameworks);
} else {
    waitForFrameworks();
}

// Debug helper
window.filamentDebug = function() {
    const info = {
        alpine: typeof Alpine !== 'undefined',
        livewire: typeof Livewire !== 'undefined',
        dropdowns: document.querySelectorAll('[x-data*="open"]').length,
        clickable: document.querySelectorAll('a, button').length,
        blocked: 0
    };
    
    // Check for blocked elements
    document.querySelectorAll('a, button, [role="button"]').forEach(el => {
        if (getComputedStyle(el).pointerEvents === 'none') {
            info.blocked++;
            console.warn('Blocked element:', el);
        }
    });
    
    console.table(info);
    return info;
};