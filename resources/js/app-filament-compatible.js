import './bootstrap';

// Import cookie consent manager
import './cookie-consent';

// Import Filament v3 compatibility fixes FIRST
import './filament-v3-fixes';

// DO NOT import Alpine.js here - Filament/Livewire v3 already includes it
// Remove these imports:
// import Alpine from 'alpinejs';
// import collapse from '@alpinejs/collapse';
// import persist from '@alpinejs/persist';
// import focus from '@alpinejs/focus';

// Wait for Alpine to be available from Filament/Livewire
document.addEventListener('DOMContentLoaded', function() {
    // Check if Alpine is available
    if (window.Alpine) {
        console.log('Alpine.js is available from Filament/Livewire');
        
        // Register any custom Alpine components here
        // But DO NOT call Alpine.start() - Filament handles this
    } else {
        console.warn('Alpine.js not found - this is expected in Filament admin pages');
    }
});

// Import other enhancements that don't conflict with Alpine
// Commented out potentially conflicting imports:
// import './company-integration-portal-clean';
// import './dropdown-manager'; // This conflicts with Filament dropdowns
// import './ultimate-portal-interactions';
// import './ultimate-ui-system';

// Safe imports that don't interfere with Alpine/Livewire
import './autocomplete-fixer';
import './table-responsive';

// Retell Configuration Center - should be safe
import './retell-configuration-center';

// Initialize hotkeys for command palette
import hotkeys from 'hotkeys-js';
window.hotkeys = hotkeys;

// Global command palette shortcut
hotkeys('cmd+k, ctrl+k', function(event) {
    event.preventDefault();
    window.dispatchEvent(new CustomEvent('open-command-palette'));
});

// Debug helper
window.debugFilament = function() {
    console.log('=== Filament Debug Info ===');
    console.log('Alpine available:', !!window.Alpine);
    console.log('Livewire available:', !!window.Livewire);
    console.log('Filament Alpine:', !!window.FilamentAlpine);
    
    if (window.Alpine) {
        console.log('Alpine version:', window.Alpine.version);
    }
    
    if (window.Livewire) {
        console.log('Livewire components:', Object.keys(window.Livewire.components.componentsById).length);
    }
    
    // Check for dropdowns
    const dropdowns = document.querySelectorAll('[x-data*="select"], [x-data*="dropdown"], .fi-dropdown');
    console.log('Dropdowns found:', dropdowns.length);
    
    // Check for errors
    const errors = document.querySelectorAll('.error, [wire\\:error]');
    console.log('Errors found:', errors.length);
};