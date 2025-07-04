// Responsive Zoom Handler for AskProAI Admin
(function() {
    'use strict';
    
    let lastZoom = 1;
    let debounceTimer;
    
    function getZoomLevel() {
        return Math.round(window.devicePixelRatio * 100) / 100;
    }
    
    function handleZoomChange() {
        const currentZoom = getZoomLevel();
        
        if (Math.abs(currentZoom - lastZoom) > 0.01) {
            lastZoom = currentZoom;
            
            // Only log significant zoom changes in development
            if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                console.debug('Zoom level changed:', currentZoom);
            }
            
            // Update CSS variable for zoom-aware styling
            document.documentElement.style.setProperty('--zoom-level', currentZoom);
            
            // Adjust content width based on zoom
            const contentElements = document.querySelectorAll('.filament-page, .filament-main-content');
            contentElements.forEach(el => {
                if (currentZoom < 0.9) {
                    el.style.maxWidth = '100%';
                    el.style.margin = '0 auto';
                } else {
                    el.style.maxWidth = '';
                    el.style.margin = '';
                }
            });
            
            // Dispatch custom event
            window.dispatchEvent(new CustomEvent('zoomchange', { 
                detail: { zoom: currentZoom } 
            }));
        }
    }
    
    // Check zoom on load
    handleZoomChange();
    
    // Monitor zoom changes
    window.addEventListener('resize', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(handleZoomChange, 150);
    });
    
    // Also check on pixel ratio change (for some browsers)
    if (window.matchMedia) {
        const mediaQuery = window.matchMedia(`(resolution: ${window.devicePixelRatio}dppx)`);
        mediaQuery.addListener(handleZoomChange);
    }
})();