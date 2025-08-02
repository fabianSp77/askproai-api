{{-- EMERGENCY GLOBAL FIX FOR BLACK OVERLAY AND ICON ISSUES --}}
<style>
    /* CRITICAL: Remove ALL overlays with maximum specificity */
    body::before,
    body::after,
    html::before,
    html::after,
    .fi-body::before,
    .fi-body::after,
    .fi-sidebar-open::before,
    .fi-sidebar-open::after,
    body.fi-sidebar-open::before,
    body.fi-sidebar-open::after,
    .fi-main::before,
    .fi-main::after,
    .fi-main-ctn::before,
    .fi-main-ctn::after,
    *[class*="overlay"]::before,
    *[class*="overlay"]::after,
    .fi-backdrop,
    .backdrop,
    .modal-backdrop,
    .fixed.inset-0.bg-gray-950,
    .fixed.inset-0.bg-black,
    [x-show*="backdrop"],
    [x-data*="backdrop"] {
        display: none !important;
        opacity: 0 !important;
        visibility: hidden !important;
        z-index: -9999 !important;
        pointer-events: none !important;
        background: transparent !important;
    }
    
    /* Force main content to be visible */
    body,
    html,
    .fi-body,
    .fi-main,
    .fi-main-ctn,
    main,
    #app,
    .fi-panel-admin {
        opacity: 1 !important;
        visibility: visible !important;
        position: relative !important;
        z-index: 1 !important;
        overflow: visible !important;
    }
    
    /* Remove any dark background overlays */
    body * {
        background-color: transparent !important;
    }
    
    body,
    .fi-body {
        background-color: rgb(249 250 251) !important;
    }
    
    .dark body,
    .dark .fi-body {
        background-color: rgb(17 24 39) !important;
    }
    
    .fi-main-ctn {
        background-color: rgb(249 250 251) !important;
    }
    
    .dark .fi-main-ctn {
        background-color: rgb(17 24 39) !important;
    }
    
    /* Fix ALL icon sizes with maximum specificity */
    svg,
    .fi-icon svg,
    .fi-sidebar-item-icon svg,
    .fi-icon-btn svg,
    .fi-btn svg,
    .fi-link svg,
    button svg,
    a svg,
    [class*="icon"] svg,
    [class*="Icon"] svg,
    .heroicon,
    .heroicon svg,
    .w-5.h-5 svg,
    .w-6.h-6 svg,
    *:not(.fi-logo) svg {
        width: 1.25rem !important;
        height: 1.25rem !important;
        max-width: 1.25rem !important;
        max-height: 1.25rem !important;
        min-width: 1.25rem !important;
        min-height: 1.25rem !important;
    }
    
    /* Special cases for specific icon containers */
    .fi-topbar svg,
    .fi-sidebar svg,
    .fi-btn-icon svg {
        width: 1.25rem !important;
        height: 1.25rem !important;
    }
    
    /* Logo exception */
    .fi-logo svg,
    .fi-brand svg,
    img[alt*="logo" i],
    img[alt*="brand" i] {
        width: auto !important;
        height: auto !important;
        max-width: 200px !important;
        max-height: 50px !important;
    }
    
    /* Remove any transforms that might affect icons */
    svg,
    [class*="icon"] {
        transform: none !important;
        scale: 1 !important;
    }
    
    /* Ensure sidebar is visible and not blocked */
    .fi-sidebar,
    .fi-sidebar-nav {
        opacity: 1 !important;
        visibility: visible !important;
        z-index: 40 !important;
        position: relative !important;
    }
    
    /* Remove any blur effects */
    * {
        filter: none !important;
        -webkit-filter: none !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
    
    /* Ensure all interactive elements are clickable */
    button,
    a,
    input,
    select,
    textarea,
    [role="button"],
    [onclick],
    .cursor-pointer {
        pointer-events: auto !important;
        cursor: pointer !important;
        position: relative !important;
        z-index: 10 !important;
    }
    
    /* Remove any fixed overlays */
    .fixed.inset-0:not(.fi-sidebar) {
        display: none !important;
    }
    
    /* Ensure modals work but remove their backdrops */
    .fi-modal {
        z-index: 50 !important;
    }
    
    .fi-modal-backdrop,
    .fi-modal-overlay {
        display: none !important;
    }
    
    /* Force remove any remaining overlay classes */
    [class*="bg-gray-950/50"],
    [class*="bg-black/50"],
    [class*="bg-gray-900/50"],
    [class*="opacity-50"],
    [class*="opacity-75"] {
        background-color: transparent !important;
        opacity: 1 !important;
    }
    
    /* Ensure transitions don't cause visibility issues */
    * {
        transition-duration: 0s !important;
        animation-duration: 0s !important;
    }
</style>

{{-- EMERGENCY JAVASCRIPT FIX --}}
<script>
    // Run immediately
    (function() {
        // Remove all overlays
        const removeOverlays = () => {
            // Find and remove any overlay elements
            const overlays = document.querySelectorAll(
                '.fi-sidebar-open::before, [class*="overlay"], .backdrop, .modal-backdrop, .fixed.inset-0.bg-gray-950, .fixed.inset-0.bg-black'
            );
            overlays.forEach(el => {
                el.style.display = 'none';
                el.style.opacity = '0';
                el.style.visibility = 'hidden';
                el.remove();
            });
            
            // Remove overlay classes from body
            if (document.body && document.body.classList) {
                document.body.classList.remove('fi-sidebar-open');
            }
            if (document.body && document.body.style) {
                document.body.style.overflow = 'visible';
            }
            
            // Fix all SVG icons
            const svgs = document.querySelectorAll('svg');
            svgs.forEach(svg => {
                if (!svg.closest('.fi-logo') && !svg.closest('.fi-brand')) {
                    svg.style.width = '1.25rem';
                    svg.style.height = '1.25rem';
                    svg.style.maxWidth = '1.25rem';
                    svg.style.maxHeight = '1.25rem';
                }
            });
            
            // Ensure main content is visible
            const mainContent = document.querySelector('.fi-main-ctn');
            if (mainContent) {
                mainContent.style.opacity = '1';
                mainContent.style.visibility = 'visible';
            }
        };
        
        // Run immediately
        removeOverlays();
        
        // Run on DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', removeOverlays);
        }
        
        // Run periodically to catch dynamically added elements
        setInterval(removeOverlays, 100);
        
        // Override any functions that might create overlays
        if (window.Alpine) {
            window.Alpine.magic('overlayDisabled', () => true);
        }
    })();
</script>