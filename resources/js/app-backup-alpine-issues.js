import './bootstrap';

// CRITICAL: Import Alpine dropdown fixes IMMEDIATELY - before Alpine itself!
import './simple-dropdown-fix';
import './alpine-dropdown-fix-immediate';
import './fix-alpine-dropdowns-global';
import './alpine-dropdown-comprehensive-fix'; // Comprehensive fix for ALL dropdowns

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import persist from '@alpinejs/persist';
import focus from '@alpinejs/focus';

// Import cookie consent manager
import './cookie-consent';

// CRITICAL: Import sidebar fix for black screen issue
import './sidebar-fix';

// Import Company Integration Portal enhancements
import './company-integration-portal-clean';

// Import unified dropdown manager
import './dropdown-manager';

// Import autocomplete fixer
import './autocomplete-fixer';

// Import Ultimate Portal Interactions
import './ultimate-portal-interactions';

// Import dropdown functions fix BEFORE other components
import './fix-dropdown-functions';

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

// Import Menu State Manager for improved navigation
import './menu-state-manager';
import './menu-state-integration';

// Import Admin Tooltips System
import './admin-tooltips';

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
