// Alpine Single Instance Enforcer
(function() {
    'use strict';
    
    //console.log('[Alpine Fix] Ensuring single Alpine instance...');
    
    // Store the first Alpine instance we find
    let primaryAlpine = null;
    
    // Override window.Alpine setter to prevent multiple instances
    Object.defineProperty(window, 'Alpine', {
        get: function() {
            return primaryAlpine;
        },
        set: function(value) {
            if (!primaryAlpine) {
                //console.log('[Alpine Fix] Setting primary Alpine instance');
                primaryAlpine = value;
            } else if (value !== primaryAlpine) {
                console.warn('[Alpine Fix] Prevented duplicate Alpine instance');
                // Don't set the new instance, keep the original
            }
        },
        configurable: true
    });
    
    // If Alpine is already loaded, store it
    if (window.Alpine) {
        primaryAlpine = window.Alpine;
        //console.log('[Alpine Fix] Found existing Alpine instance');
    }
})();