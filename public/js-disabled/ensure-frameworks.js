/**
 * Ensure Frameworks - Makes sure Alpine and Livewire are loaded
 */
(function() {
    'use strict';
    
    //console.log('[Ensure Frameworks] Checking Alpine and Livewire...');
    
    // Check if Alpine is loaded
    if (typeof window.Alpine === 'undefined') {
        console.error('[Ensure Frameworks] Alpine.js is NOT loaded! This is critical.');
        //console.log('[Ensure Frameworks] Attempting to load Alpine manually...');
        
        // Try to find Alpine in window.filamentData
        if (window.filamentData && window.filamentData.alpine) {
            window.Alpine = window.filamentData.alpine;
            //console.log('[Ensure Frameworks] Found Alpine in filamentData');
        }
    } else {
        //console.log('[Ensure Frameworks] Alpine.js is loaded:', window.Alpine.version || 'version unknown');
    }
    
    // Check if Livewire is loaded
    if (typeof window.Livewire === 'undefined') {
        console.error('[Ensure Frameworks] Livewire is NOT loaded! This is critical.');
        
        // Check for Livewire in different locations
        if (window.livewire) {
            window.Livewire = window.livewire;
            //console.log('[Ensure Frameworks] Found Livewire as window.livewire');
        }
    } else {
        //console.log('[Ensure Frameworks] Livewire is loaded');
    }
    
    // If Alpine exists but not started, start it
    if (window.Alpine && !window.Alpine.version) {
        //console.log('[Ensure Frameworks] Starting Alpine...');
        try {
            window.Alpine.start();
        } catch (e) {
            console.error('[Ensure Frameworks] Error starting Alpine:', e);
        }
    }
    
    // Debug: List all loaded scripts
    //console.log('[Ensure Frameworks] Loaded scripts:');
    const scripts = Array.from(document.scripts);
    const alpineScript = scripts.find(s => s.src && s.src.includes('alpine'));
    const livewireScript = scripts.find(s => s.src && s.src.includes('livewire'));
    
    if (alpineScript) {
        //console.log('  Alpine script:', alpineScript.src);
    } else {
        console.warn('  No Alpine script found!');
    }
    
    if (livewireScript) {
        //console.log('  Livewire script:', livewireScript.src);
    } else {
        console.warn('  No Livewire script found!');
    }
    
    // Check Filament assets
    const filamentScripts = scripts.filter(s => s.src && s.src.includes('filament'));
    //console.log(`  Filament scripts: ${filamentScripts.length} found`);
    
    // Emergency: Try to load from CDN if not found
    if (!window.Alpine && !alpineScript) {
        //console.log('[Ensure Frameworks] EMERGENCY: Loading Alpine from CDN...');
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js';
        script.defer = true;
        script.onload = function() {
            //console.log('[Ensure Frameworks] Alpine loaded from CDN');
            if (window.Alpine) {
                window.Alpine.start();
            }
        };
        document.head.appendChild(script);
    }
})();