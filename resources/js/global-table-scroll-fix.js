/**
 * Global Table Scroll Fix for ALL Filament Tables
 * Ensures horizontal scrolling works on all admin panel tables
 */

console.log('[Global Table Fix] Initializing for all Filament tables...');

class GlobalTableScrollFix {
    constructor() {
        this.fixedTables = new Set();
        this.observer = null;
        this.init();
    }

    init() {
        // Fix existing tables
        this.fixAllTables();
        
        // Setup observer for new tables
        this.setupObserver();
        
        // Listen for Livewire updates
        this.setupLivewireHooks();
        
        // Run periodically as backup
        this.setupPeriodicCheck();
    }

    fixAllTables() {
        const tables = document.querySelectorAll('.fi-ta-content');
        console.log(`[Global Table Fix] Found ${tables.length} tables to fix`);
        
        tables.forEach((container, index) => {
            if (!this.fixedTables.has(container)) {
                this.fixTable(container, index);
            }
        });
    }

    fixTable(container, index) {
        // Check if table needs scrolling
        const table = container.querySelector('table');
        if (!table) return;
        
        // Clear any conflicting styles
        container.style.removeProperty('overflow');
        container.style.removeProperty('overflow-x');
        container.style.removeProperty('overflow-y');
        
        // Apply the fix
        container.style.overflowX = 'auto';
        container.style.overflowY = 'visible';
        container.style.maxWidth = '100%';
        container.style.width = '100%';
        container.style.display = 'block';
        container.style.webkitOverflowScrolling = 'touch';
        
        // Allow table to expand
        table.style.width = 'auto';
        table.style.minWidth = 'max-content';
        table.style.tableLayout = 'auto';
        
        // Mark as fixed
        this.fixedTables.add(container);
        container.setAttribute('data-scroll-fixed', 'true');
        
        // Check if scrolling is needed
        const needsScroll = table.scrollWidth > container.clientWidth;
        if (needsScroll) {
            container.setAttribute('data-has-overflow', 'true');
            console.log(`[Global Table Fix] Table ${index + 1} needs scrolling (${table.scrollWidth}px > ${container.clientWidth}px)`);
        }
        
        console.log(`[Global Table Fix] Fixed table ${index + 1}`);
    }

    setupObserver() {
        this.observer = new MutationObserver((mutations) => {
            let shouldFix = false;
            
            mutations.forEach(mutation => {
                // Check if new tables were added
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) {
                        if (node.classList?.contains('fi-ta-content') || 
                            node.querySelector?.('.fi-ta-content')) {
                            shouldFix = true;
                        }
                    }
                });
            });
            
            if (shouldFix) {
                setTimeout(() => this.fixAllTables(), 100);
            }
        });
        
        // Start observing
        this.observer.observe(document.body, {
            childList: true,
            subtree: true
        });
        
        console.log('[Global Table Fix] Observer setup complete');
    }

    setupLivewireHooks() {
        if (window.Livewire) {
            // Livewire 3 hooks
            Livewire.hook('commit', ({ component, commit, respond }) => {
                setTimeout(() => this.fixAllTables(), 50);
            });
            
            // Legacy Livewire 2 hooks (if needed)
            if (Livewire.hook) {
                Livewire.hook('message.processed', () => {
                    setTimeout(() => this.fixAllTables(), 50);
                });
            }
            
            console.log('[Global Table Fix] Livewire hooks setup complete');
        }
    }

    setupPeriodicCheck() {
        // Check every 2 seconds for the first 20 seconds
        let checks = 0;
        const interval = setInterval(() => {
            this.fixAllTables();
            checks++;
            
            if (checks >= 10) {
                clearInterval(interval);
                console.log('[Global Table Fix] Periodic checks complete');
            }
        }, 2000);
    }

    // Public method to manually trigger fix
    fix() {
        this.fixAllTables();
    }
}

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.globalTableFix = new GlobalTableScrollFix();
    });
} else {
    window.globalTableFix = new GlobalTableScrollFix();
}

// Also fix on window load
window.addEventListener('load', () => {
    if (window.globalTableFix) {
        window.globalTableFix.fix();
    }
});

// Export for manual use
window.fixAllTables = () => {
    if (window.globalTableFix) {
        window.globalTableFix.fix();
    } else {
        new GlobalTableScrollFix();
    }
};

console.log('[Global Table Fix] Script loaded - use window.fixAllTables() to manually trigger');