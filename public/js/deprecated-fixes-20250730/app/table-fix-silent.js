/**
 * Silent Table Fix
 * Fixes table scrolling without console errors
 */

class SilentTableFix {
    constructor() {
        this.initialized = false;
        this.observer = null;
        this.init();
    }
    
    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    setup() {
        try {
            // Check if we're in admin panel with tables
            const hasFilamentTables = document.querySelector('.fi-ta-table, .fi-table');
            if (!hasFilamentTables) {
                return; // No tables, exit silently
            }
            
            this.initialized = true;
            
            // Fix existing tables
            this.fixAllTables();
            
            // Setup observer for new tables
            this.setupObserver();
            
            // Handle Livewire updates
            if (window.Livewire) {
                Livewire.hook('message.processed', () => {
                    setTimeout(() => this.fixAllTables(), 100);
                });
            }
            
            // Handle tab changes
            document.addEventListener('tab-changed', () => {
                setTimeout(() => this.fixAllTables(), 100);
            });
            
        } catch (error) {
            // Silent fail
        }
    }
    
    fixAllTables() {
        try {
            // Find all table containers
            const tableSelectors = [
                '.fi-ta-content',
                '.fi-table-container',
                '.table-wrapper',
                '[wire\\:id] .overflow-x-auto',
                '.fi-ta-table-wrapper'
            ];
            
            let tablesFixed = 0;
            
            tableSelectors.forEach(selector => {
                const containers = document.querySelectorAll(selector);
                containers.forEach(container => {
                    if (this.fixTableContainer(container)) {
                        tablesFixed++;
                    }
                });
            });
            
            // Also fix any overflow-x-auto containers with tables
            const overflowContainers = document.querySelectorAll('.overflow-x-auto');
            overflowContainers.forEach(container => {
                if (container.querySelector('table')) {
                    if (this.fixTableContainer(container)) {
                        tablesFixed++;
                    }
                }
            });
            
        } catch (error) {
            // Silent fail
        }
    }
    
    fixTableContainer(container) {
        try {
            if (!container || container.dataset.tableFixed === 'true') {
                return false;
            }
            
            // Mark as fixed to avoid duplicate processing
            container.dataset.tableFixed = 'true';
            
            // Ensure overflow-x is set
            container.style.overflowX = 'auto';
            container.style.webkitOverflowScrolling = 'touch';
            
            // Find the table
            const table = container.querySelector('table');
            if (!table) return false;
            
            // Ensure table has proper width handling
            table.style.minWidth = 'max-content';
            
            // Add scroll indicators if needed
            this.addScrollIndicators(container);
            
            // Handle responsive visibility
            this.handleResponsiveColumns(table);
            
            return true;
            
        } catch (error) {
            return false;
        }
    }
    
    addScrollIndicators(container) {
        try {
            // Check if container needs horizontal scroll
            const updateScrollIndicators = () => {
                const hasScroll = container.scrollWidth > container.clientWidth;
                const scrollLeft = container.scrollLeft;
                const maxScroll = container.scrollWidth - container.clientWidth;
                
                container.classList.toggle('has-scroll', hasScroll);
                container.classList.toggle('scroll-left', scrollLeft > 0);
                container.classList.toggle('scroll-right', scrollLeft < maxScroll - 1);
            };
            
            // Initial check
            updateScrollIndicators();
            
            // Update on scroll
            container.addEventListener('scroll', updateScrollIndicators);
            
            // Update on resize
            window.addEventListener('resize', updateScrollIndicators);
            
        } catch (error) {
            // Silent fail
        }
    }
    
    handleResponsiveColumns(table) {
        try {
            // Add responsive classes based on viewport
            const updateResponsiveClasses = () => {
                const width = window.innerWidth;
                
                table.classList.toggle('mobile-view', width < 768);
                table.classList.toggle('tablet-view', width >= 768 && width < 1024);
                table.classList.toggle('desktop-view', width >= 1024);
            };
            
            updateResponsiveClasses();
            window.addEventListener('resize', updateResponsiveClasses);
            
        } catch (error) {
            // Silent fail
        }
    }
    
    setupObserver() {
        try {
            // Disconnect existing observer
            if (this.observer) {
                this.observer.disconnect();
            }
            
            // Create new observer
            this.observer = new MutationObserver((mutations) => {
                // Debounce to avoid excessive calls
                if (this.observerTimeout) {
                    clearTimeout(this.observerTimeout);
                }
                
                this.observerTimeout = setTimeout(() => {
                    this.fixAllTables();
                }, 100);
            });
            
            // Observe the body for changes
            this.observer.observe(document.body, {
                childList: true,
                subtree: true,
                attributes: false,
                characterData: false
            });
            
        } catch (error) {
            // Silent fail
        }
    }
    
    // Public API
    refresh() {
        this.fixAllTables();
    }
}

// Initialize only once
if (!window.silentTableFixInitialized) {
    window.silentTableFixInitialized = true;
    window.silentTableFix = new SilentTableFix();
}

// Make available globally
window.SilentTableFix = SilentTableFix;
window.refreshTables = () => window.silentTableFix?.refresh();