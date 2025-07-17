{{-- Emergency admin portal visibility fix --}}
<style>
    /* CRITICAL: Force everything visible */
    html, body {
        opacity: 1 !important;
        visibility: visible !important;
        display: block !important;
        background: #f9fafb !important;
        overflow: visible !important;
    }
    
    /* Remove ALL x-cloak */
    [x-cloak], 
    [x-cloak]::before, 
    [x-cloak]::after,
    [x-cloak=''],
    [x-cloak='x-cloak'],
    [x-cloak='1'],
    [x-cloak='-lg'],
    [x-cloak='lg'] {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Force all elements visible EXCEPT dropdowns */
    *:not(.fi-dropdown):not(.fi-dropdown-panel):not([x-show]) {
        opacity: 1 !important;
        visibility: visible !important;
    }
    
    /* Allow dropdowns to control their own visibility */
    .fi-dropdown,
    .fi-dropdown-panel,
    [x-show] {
        opacity: inherit !important;
        visibility: inherit !important;
    }
    
    /* Remove loading overlays */
    .loading-overlay,
    .spinner-overlay,
    [wire\:loading],
    .fi-loading-indicator {
        display: none !important;
    }
    
    /* Ensure login panel is visible */
    .fi-simple-page,
    .fi-login-panel,
    .fi-simple-main {
        display: flex !important;
        opacity: 1 !important;
        visibility: visible !important;
        position: relative !important;
        z-index: 1 !important;
    }
    
    /* Remove any blocking overlays */
    .fixed.inset-0:not(.fi-simple-page) {
        display: none !important;
    }
</style>

<script>
    // Immediate visibility fix
    (function() {
        console.log('Emergency admin visibility fix active');
        
        // Remove x-cloak immediately
        document.querySelectorAll('[x-cloak]').forEach(el => {
            el.removeAttribute('x-cloak');
            el.style.removeProperty('display');
        });
        
        // Force body visible
        document.body.style.opacity = '1';
        document.body.style.visibility = 'visible';
        document.body.style.display = 'block';
        
        // Remove any blocking overlays
        document.querySelectorAll('.fixed.inset-0').forEach(el => {
            if (!el.classList.contains('fi-simple-page')) {
                el.style.display = 'none';
            }
        });
    })();
</script>