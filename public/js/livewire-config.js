/**
 * Livewire Configuration
 * Sets up Livewire configuration before it loads
 */
(function() {
    'use strict';
    
    //console.log('[Livewire Config] Setting up Livewire configuration...');
    
    // Set Livewire configuration
    window.livewireScriptConfig = {
        csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
        uri: '/livewire',
        progressBar: true,
        reactive: true
    };
    
    //console.log('[Livewire Config] Configuration set:', window.livewireScriptConfig);
})();