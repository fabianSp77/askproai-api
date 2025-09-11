/**
 * Flowbite Preview Component Initialization
 * Standalone version for component preview pages
 */

(function() {
    'use strict';
    
    const DEBUG = true;
    
    function log(msg, data) {
        if (!DEBUG) return;
        console.log(`[Preview Init] ${msg}`, data || '');
    }
    
    function initFlowbitePreview() {
        log('Initializing preview components');
        
        // Wait for both Alpine and Flowbite
        let attempts = 0;
        const maxAttempts = 50;
        
        function tryInit() {
            attempts++;
            
            const alpineReady = typeof Alpine !== 'undefined';
            const flowbiteReady = typeof initFlowbite !== 'undefined';
            
            if (alpineReady && flowbiteReady) {
                // Initialize Alpine if not started
                if (!Alpine.version) {
                    Alpine.start();
                    log('Alpine started');
                }
                
                // Initialize Flowbite
                initFlowbite();
                log('Flowbite initialized');
                
                // Count components
                const stats = {
                    alpine: document.querySelectorAll('[x-data]').length,
                    modals: document.querySelectorAll('[data-modal-toggle]').length,
                    dropdowns: document.querySelectorAll('[data-dropdown-toggle]').length,
                    tooltips: document.querySelectorAll('[data-tooltip-target]').length,
                    tabs: document.querySelectorAll('[data-tabs-toggle]').length,
                    accordions: document.querySelectorAll('[data-accordion-target]').length
                };
                
                log('Component stats:', stats);
                
                // Dispatch ready event
                window.dispatchEvent(new CustomEvent('flowbite-preview-ready', { detail: stats }));
                
            } else if (attempts < maxAttempts) {
                log(`Waiting for libraries (attempt ${attempts}/${maxAttempts})`, {
                    alpine: alpineReady,
                    flowbite: flowbiteReady
                });
                setTimeout(tryInit, 100);
            } else {
                console.error('Failed to initialize preview after maximum attempts');
            }
        }
        
        tryInit();
    }
    
    // Start initialization
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFlowbitePreview);
    } else {
        initFlowbitePreview();
    }
    
    // Also try on window load
    window.addEventListener('load', function() {
        setTimeout(initFlowbitePreview, 100);
    });
    
    // Expose for manual triggering
    window.reinitPreview = initFlowbitePreview;
})();