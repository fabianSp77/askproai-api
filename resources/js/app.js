import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';

// Import cookie consent manager
import './cookie-consent';

// Import Company Integration Portal enhancements
import './company-integration-portal-clean';

// Import unified dropdown manager
import './dropdown-manager';

// Import autocomplete fixer
import './autocomplete-fixer';

// Import Ultimate Portal Interactions
import './ultimate-portal-interactions';

// Import Table Responsive Enhancements
import './table-responsive';

// Import Ultimate UI System - The complete UI/UX implementation
import './ultimate-ui-system';

// Import Retell Configuration Center
import './retell-configuration-center';

// Import Alpine Diagnostic and Fix Script
import './alpine-diagnostic-fix';

// Import Portal Alpine Stabilizer - MUST be before Alpine.start()
import './portal-alpine-stabilizer';

// Dropdown fixes are now handled by minimal-dropdown-fix.css only

window.Alpine = Alpine;

Alpine.plugin(collapse);
Alpine.plugin(persist);
Alpine.plugin(focus);

// Delay Alpine start to ensure all components are registered
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        Alpine.start();
    });
} else {
    // Small delay to ensure everything is loaded
    setTimeout(() => {
        Alpine.start();
    }, 100);
}

// Initialize hotkeys for command palette
import hotkeys from 'hotkeys-js';
window.hotkeys = hotkeys;

// Global command palette shortcut
hotkeys('cmd+k, ctrl+k', function(event) {
    event.preventDefault();
    window.dispatchEvent(new CustomEvent('open-command-palette'));
});
