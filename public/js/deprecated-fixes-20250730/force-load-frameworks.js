/**
 * Force Load Frameworks
 * Emergency script to load Alpine and Livewire from Filament bundles
 */
(function() {
    'use strict';
    
    //console.log('[Force Load Frameworks] Starting emergency framework loading...');
    
    // Check what scripts are available
    const scripts = Array.from(document.scripts);
    //console.log('[Force Load Frameworks] Total scripts found:', scripts.length);
    
    // Look for Filament app.js or other bundle files
    const filamentScripts = scripts.filter(s => 
        s.src && (
            s.src.includes('app.js') || 
            s.src.includes('filament') ||
            s.src.includes('app-') ||
            s.src.includes('vendor.js')
        )
    );
    
    //console.log('[Force Load Frameworks] Filament-related scripts:', filamentScripts.map(s => s.src));
    
    // Check for inline Livewire/Alpine scripts
    const inlineScripts = scripts.filter(s => 
        !s.src && s.textContent && (
            s.textContent.includes('window.Livewire') ||
            s.textContent.includes('window.Alpine') ||
            s.textContent.includes('_alpine') ||
            s.textContent.includes('_livewire')
        )
    );
    
    //console.log('[Force Load Frameworks] Inline scripts with frameworks:', inlineScripts.length);
    
    // Try to find Alpine in common locations
    const possibleAlpineLocations = [
        'window.Alpine',
        'window.alpine',
        'window.AlpineJS',
        'window.alpinejs',
        'window._alpine',
        'window.deferLoadingAlpine'
    ];
    
    possibleAlpineLocations.forEach(location => {
        try {
            const value = eval(location);
            if (value) {
                //console.log(`[Force Load Frameworks] Found at ${location}:`, value);
            }
        } catch (e) {
            // Ignore
        }
    });
    
    // Try to find Livewire
    const possibleLivewireLocations = [
        'window.Livewire',
        'window.livewire',
        'window._livewire'
    ];
    
    possibleLivewireLocations.forEach(location => {
        try {
            const value = eval(location);
            if (value) {
                //console.log(`[Force Load Frameworks] Found at ${location}:`, value);
            }
        } catch (e) {
            // Ignore
        }
    });
    
    // Try to extract Livewire from scripts
    if (!window.Livewire) {
        inlineScripts.forEach(script => {
            if (script.textContent.includes('window.Livewire')) {
                //console.log('[Force Load Frameworks] Attempting to execute Livewire script...');
                try {
                    // Extract just the Livewire assignment
                    const livewireMatch = script.textContent.match(/window\.Livewire\s*=\s*\{[^}]+\}/);
                    if (livewireMatch) {
                        eval(livewireMatch[0]);
                        //console.log('[Force Load Frameworks] Livewire loaded from inline script');
                    }
                } catch (e) {
                    console.error('[Force Load Frameworks] Error loading Livewire:', e);
                }
            }
        });
    }
    
    // Check for deferred Alpine
    if (window.deferLoadingAlpine && typeof window.deferLoadingAlpine === 'function') {
        //console.log('[Force Load Frameworks] Found deferred Alpine, executing...');
        window.deferLoadingAlpine();
    }
    
    // Final check
    setTimeout(() => {
        //console.log('[Force Load Frameworks] Final check:');
        //console.log('  - Alpine:', !!window.Alpine);
        //console.log('  - Livewire:', !!window.Livewire);
        
        if (!window.Alpine && !window.Livewire) {
            console.error('[Force Load Frameworks] CRITICAL: Frameworks still not loaded!');
            //console.log('[Force Load Frameworks] Checking for build files...');
            
            // Look for built JS files
            const links = Array.from(document.querySelectorAll('link[href*=".js"]'));
            const buildFiles = links.filter(l => l.href.includes('/build/'));
            //console.log('[Force Load Frameworks] Build files found:', buildFiles.map(l => l.href));
        }
    }, 1000);
})();