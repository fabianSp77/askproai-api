/**
 * Admin Panel JavaScript Bundle - Clean Version
 * Simplified, performant, and maintainable
 */

// Import our clean mobile navigation
import '../mobile-navigation-final.js';

console.log('[Admin Bundle] Loading clean admin panel...');

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAdmin);
} else {
    initializeAdmin();
}

function initializeAdmin() {
    console.log('[Admin Bundle] Initializing...');
    
    // Handle dropdowns with event delegation
    handleDropdowns();
    
    // Ensure tables are responsive
    makeTablesResponsive();
    
    // Re-initialize after Livewire updates
    if (window.Livewire) {
        Livewire.hook('message.processed', () => {
            makeTablesResponsive();
        });
    }
    
    console.log('[Admin Bundle] Initialization complete');
}

/**
 * Handle dropdown interactions globally
 */
function handleDropdowns() {
    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        // If not clicking on a dropdown trigger or panel
        if (!e.target.closest('.fi-dropdown-trigger, .fi-dropdown-panel')) {
            // Close all open dropdowns
            document.querySelectorAll('.fi-dropdown-panel[x-show="open"]').forEach(panel => {
                const dropdown = panel.closest('[x-data]');
                if (dropdown && dropdown._x_dataStack) {
                    const data = dropdown._x_dataStack[0];
                    if (data && data.open) {
                        data.open = false;
                    }
                }
            });
        }
    });
}

/**
 * Ensure tables are responsive on all devices
 */
function makeTablesResponsive() {
    document.querySelectorAll('.fi-ta-ctn').forEach(container => {
        // Enable horizontal scrolling
        container.style.overflowX = 'auto';
        container.style.webkitOverflowScrolling = 'touch';
        
        // Add data-label attributes for mobile view
        const table = container.querySelector('table');
        if (table) {
            const headers = table.querySelectorAll('thead th');
            headers.forEach((header, index) => {
                const headerText = header.textContent.trim();
                const cells = table.querySelectorAll(`tbody td:nth-child(${index + 1})`);
                cells.forEach(cell => {
                    cell.setAttribute('data-label', headerText);
                });
            });
        }
    });
}

/**
 * Global admin utilities
 */
window.adminUtils = {
    // Debug helper to check UI state
    debug() {
        const report = {
            mobileNavActive: !!window.mobileNav,
            viewportWidth: window.innerWidth,
            dropdowns: document.querySelectorAll('.fi-dropdown').length,
            tables: document.querySelectorAll('.fi-ta-table').length,
            sidebarVisible: !!document.querySelector('.fi-sidebar.translate-x-0')
        };
        console.table(report);
        return report;
    },
    
    // Force refresh of dynamic components
    refresh() {
        makeTablesResponsive();
        if (window.mobileNav) {
            window.mobileNav.close();
        }
    }
};

// Export for use in other scripts
export { initializeAdmin, handleDropdowns, makeTablesResponsive };