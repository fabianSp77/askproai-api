// Emergency fix for framework loading - Issue #476 - V2
// More conservative approach to avoid conflicts

(function() {
    'use strict';
    
    console.log('🚨 Emergency Framework Fix V2 Loading...');
    
    // Only run our fixes once
    if (window._emergencyFixApplied) {
        console.log('⚠️ Emergency fix already applied, skipping...');
        return;
    }
    window._emergencyFixApplied = true;
    
    // Function to safely fix click handlers without interfering with frameworks
    function fixClickHandlers() {
        console.log('🔧 Applying click handler fixes...');
        
        // Target only elements that are visibly broken
        const problematicSelectors = [
            '.fi-ta-action',
            '.fi-ac-action', 
            '.fi-dropdown-trigger',
            '.fi-login-panel button',
            '[class*="fi-ta-"] button',
            '[class*="fi-ac-"] button'
        ];
        
        problematicSelectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(el => {
                // Only fix if pointer-events is explicitly none
                const computed = getComputedStyle(el);
                if (computed.pointerEvents === 'none') {
                    el.style.pointerEvents = 'auto';
                    el.style.cursor = 'pointer';
                    console.log(`Fixed: ${selector}`);
                }
            });
        });
        
        console.log('✅ Click handler fixes applied');
    }
    
    // Function to remove problematic overlays
    function removeBlockingOverlays() {
        console.log('🔧 Removing blocking overlays...');
        
        // Target specific known problem overlays
        const overlaySelectors = [
            '.fi-sidebar-open::before',
            '.fi-sidebar-open::after',
            '.fi-main-ctn::before',
            '.fi-main-ctn::after'
        ];
        
        // Remove via CSS injection since pseudo-elements can't be targeted directly
        const style = document.createElement('style');
        style.textContent = `
            .fi-sidebar-open::before,
            .fi-sidebar-open::after,
            .fi-main-ctn::before,
            .fi-main-ctn::after {
                display: none !important;
                content: none !important;
            }
        `;
        document.head.appendChild(style);
        
        console.log('✅ Overlay fixes applied');
    }
    
    // Wait for DOM and frameworks
    function waitForFrameworks() {
        // Check if frameworks are loaded
        if (window.Alpine && window.Livewire && document.body) {
            console.log('✅ Frameworks detected');
            
            // Apply fixes after a small delay to ensure everything is rendered
            setTimeout(() => {
                removeBlockingOverlays();
                fixClickHandlers();
                
                // Re-apply fixes when Livewire updates the DOM
                Livewire.hook('message.processed', () => {
                    console.log('📡 Livewire update detected, reapplying fixes...');
                    fixClickHandlers();
                });
            }, 250);
        } else {
            // Keep checking
            setTimeout(waitForFrameworks, 100);
        }
    }
    
    // Start when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', waitForFrameworks);
    } else {
        waitForFrameworks();
    }
})();