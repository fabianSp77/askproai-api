import './bootstrap';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';

// Import cookie consent manager
import './cookie-consent';

// Import sidebar fix for black screen issue
import './sidebar-fix';

// Import unified dropdown manager - ONLY ONE dropdown solution
import './dropdown-manager';

// Import Table Responsive Enhancements
import './table-responsive';

// Import Ultimate UI System
import './ultimate-ui-system';

// Import Retell Configuration Center
import './retell-configuration-center';

// Import Menu State Manager
import './menu-state-manager';
import './menu-state-integration';

// Import Admin Tooltips System
import './admin-tooltips';

window.Alpine = Alpine;

Alpine.plugin(collapse);
Alpine.plugin(persist);
Alpine.plugin(focus);

// Single Alpine.start() call
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        Alpine.start();
    });
} else {
    Alpine.start();
}

// Initialize hotkeys for command palette
import hotkeys from 'hotkeys-js';
window.hotkeys = hotkeys;

// Global command palette shortcut
hotkeys('cmd+k, ctrl+k', function(event) {
    event.preventDefault();
    window.dispatchEvent(new CustomEvent('open-command-palette'));
});