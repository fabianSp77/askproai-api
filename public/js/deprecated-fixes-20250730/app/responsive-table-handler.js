/**
 * Responsive Table Handler for Filament Tables
 * Adds data-label attributes for mobile card view
 */

class ResponsiveTableHandler {
    constructor() {
        this.tables = new Map();
        this.breakpoint = 768;
        this.init();
    }

    init() {
        this.processAllTables();
        this.setupObserver();
        this.setupResizeHandler();
        this.setupLivewireHooks();
    }

    processAllTables() {
        const tables = document.querySelectorAll('.fi-ta-table');
        tables.forEach(table => this.processTable(table));
    }

    processTable(table) {
        // Skip if already processed
        if (this.tables.has(table)) return;

        // Get headers
        const headers = Array.from(table.querySelectorAll('thead th'));
        const headerTexts = headers.map(th => {
            // Try to get clean text without icons
            const textNode = th.querySelector('.fi-ta-header-cell-label span') || th;
            return textNode.textContent.trim();
        });

        // Add data-label to each cell
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (headerTexts[index]) {
                    cell.setAttribute('data-label', headerTexts[index]);
                }
            });
        });

        // Mark as processed
        this.tables.set(table, true);
        table.classList.add('responsive-ready');
    }

    setupObserver() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        // Check if it's a table or contains tables
                        if (node.classList?.contains('fi-ta-table')) {
                            this.processTable(node);
                        } else if (node.querySelector?.('.fi-ta-table')) {
                            node.querySelectorAll('.fi-ta-table').forEach(table => {
                                this.processTable(table);
                            });
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    setupResizeHandler() {
        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                this.handleResize();
            }, 150);
        });
    }

    handleResize() {
        const isMobile = window.innerWidth < this.breakpoint;
        document.body.classList.toggle('fi-mobile-view', isMobile);
    }

    setupLivewireHooks() {
        if (window.Livewire) {
            // Livewire 3 hooks
            Livewire.hook('commit', ({ component, commit, respond }) => {
                setTimeout(() => this.processAllTables(), 100);
            });
        }
    }

    // Public API for manual processing
    refresh() {
        this.tables.clear();
        this.processAllTables();
    }
}

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.responsiveTableHandler = new ResponsiveTableHandler();
    });
} else {
    window.responsiveTableHandler = new ResponsiveTableHandler();
}

// Add helper function to force mobile view for testing
window.forceMobileView = (enable = true) => {
    if (enable) {
        document.body.classList.add('fi-mobile-view');
        console.log('Mobile view enabled');
    } else {
        document.body.classList.remove('fi-mobile-view');
        console.log('Mobile view disabled');
    }
};