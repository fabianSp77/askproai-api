import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';

// Import cookie consent manager
import './cookie-consent';

// Import Company Integration Portal enhancements
import './company-integration-portal-clean';

// Import Alpine dropdown fixes
import './alpine-dropdown-fix';

// Import Ultimate Portal Interactions
import './ultimate-portal-interactions';

// Import Table Responsive Enhancements
import './table-responsive';

// Import Ultimate UI System - The complete UI/UX implementation
import './ultimate-ui-system';

// Import Retell Configuration Center
import './retell-configuration-center';

window.Alpine = Alpine;

Alpine.plugin(collapse);
Alpine.plugin(persist);
Alpine.plugin(focus);
Alpine.start();

// Initialize hotkeys for command palette
import hotkeys from 'hotkeys-js';
window.hotkeys = hotkeys;

// Global command palette shortcut
hotkeys('cmd+k, ctrl+k', function(event) {
    event.preventDefault();
    window.dispatchEvent(new CustomEvent('open-command-palette'));
});
