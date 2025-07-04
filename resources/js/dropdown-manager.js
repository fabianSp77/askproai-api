// Unified Dropdown Manager for Filament v3
// Handles all dropdown functionality in a clean, non-conflicting way

class DropdownManager {
    constructor() {
        this.dropdowns = new WeakMap();
        this.activeDropdown = null;
        this.initialized = false;
        this.observer = null;
        
        // Wait for Alpine to be ready
        if (window.Alpine) {
            this.init();
        } else {
            document.addEventListener('alpine:init', () => this.init());
        }
    }
    
    init() {
        if (this.initialized) return;
        this.initialized = true;
        
        // Register Alpine components
        this.registerAlpineComponents();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Setup mutation observer for dynamic content
        this.setupMutationObserver();
        
        // Initialize existing dropdowns
        this.initializeDropdowns();
        
        console.log('DropdownManager initialized');
    }
    
    registerAlpineComponents() {
        // Enhanced dropdown component with proper state management
        Alpine.data('dropdown', (initialOpen = false) => ({
            open: initialOpen,
            closeTimeout: null,
            
            init() {
                // Register this dropdown
                window.dropdownManager.registerDropdown(this.$el, this);
                
                // Handle initial state
                if (this.open) {
                    this.$nextTick(() => this.show());
                }
            },
            
            toggle() {
                this.open ? this.close() : this.show();
            },
            
            show() {
                // Close other dropdowns
                window.dropdownManager.closeAll(this.$el);
                
                this.open = true;
                window.dropdownManager.activeDropdown = this.$el;
                
                // Ensure dropdown is visible
                this.$nextTick(() => {
                    const panel = this.$refs.panel || this.$el.querySelector('[x-ref="panel"]');
                    if (panel) {
                        panel.classList.remove('invisible', 'opacity-0');
                        panel.style.display = '';
                        
                        // Position dropdown if needed
                        this.positionDropdown(panel);
                    }
                });
                
                // Dispatch event
                this.$dispatch('dropdown-opened');
            },
            
            close(force = false) {
                if (!this.open && !force) return;
                
                // Clear any pending close timeout
                if (this.closeTimeout) {
                    clearTimeout(this.closeTimeout);
                    this.closeTimeout = null;
                }
                
                this.open = false;
                
                const panel = this.$refs.panel || this.$el.querySelector('[x-ref="panel"]');
                if (panel) {
                    panel.classList.add('invisible', 'opacity-0');
                }
                
                if (window.dropdownManager.activeDropdown === this.$el) {
                    window.dropdownManager.activeDropdown = null;
                }
                
                // Dispatch event
                this.$dispatch('dropdown-closed');
            },
            
            delayedClose() {
                this.closeTimeout = setTimeout(() => this.close(), 200);
            },
            
            cancelClose() {
                if (this.closeTimeout) {
                    clearTimeout(this.closeTimeout);
                    this.closeTimeout = null;
                }
            },
            
            positionDropdown(panel) {
                const button = this.$refs.button || this.$el.querySelector('[x-ref="button"]');
                if (!button || !panel) return;
                
                const buttonRect = button.getBoundingClientRect();
                const panelRect = panel.getBoundingClientRect();
                const viewportHeight = window.innerHeight;
                const viewportWidth = window.innerWidth;
                
                // Reset styles
                panel.style.position = '';
                panel.style.top = '';
                panel.style.left = '';
                panel.style.right = '';
                panel.style.maxHeight = '';
                
                // Check if dropdown would overflow viewport
                const wouldOverflowBottom = buttonRect.bottom + panelRect.height > viewportHeight;
                const wouldOverflowRight = buttonRect.left + panelRect.width > viewportWidth;
                
                // Apply positioning classes if needed
                if (wouldOverflowBottom && buttonRect.top > panelRect.height) {
                    panel.classList.add('bottom-full', 'mb-2');
                    panel.classList.remove('top-full', 'mt-2');
                }
                
                if (wouldOverflowRight && buttonRect.right > panelRect.width) {
                    panel.classList.add('right-0');
                    panel.classList.remove('left-0');
                }
                
                // Set max height to prevent viewport overflow
                const maxHeight = wouldOverflowBottom 
                    ? buttonRect.top - 20 
                    : viewportHeight - buttonRect.bottom - 20;
                    
                if (panel.scrollHeight > maxHeight) {
                    panel.style.maxHeight = maxHeight + 'px';
                    panel.style.overflowY = 'auto';
                }
            }
        }));
        
        // Branch selector specific component
        Alpine.data('branchSelector', () => ({
            ...Alpine.data('dropdown')(),
            search: '',
            
            get filteredBranches() {
                if (!this.search) return [];
                
                const branches = this.$el.querySelectorAll('[data-branch-name]');
                return Array.from(branches).filter(branch => {
                    const name = branch.dataset.branchName || '';
                    return name.toLowerCase().includes(this.search.toLowerCase());
                });
            }
        }));
    }
    
    setupEventListeners() {
        // Click outside to close - use capture phase for better compatibility
        document.addEventListener('click', (e) => {
            // Prevent issues with removed DOM elements
            if (!e.target || !e.target.isConnected) return;
            
            if (!this.activeDropdown) return;
            
            const dropdown = this.activeDropdown;
            const dropdownData = this.dropdowns.get(dropdown);
            
            // Check if dropdown still exists in DOM
            if (!dropdown.isConnected) {
                this.activeDropdown = null;
                return;
            }
            
            if (!dropdown.contains(e.target) && dropdownData) {
                // Use Alpine's close method if available
                if (dropdownData && typeof dropdownData.close === 'function') {
                    dropdownData.close();
                } else {
                    this.closeDropdown(dropdown);
                }
            }
        }, true);
        
        // Escape key to close
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.activeDropdown) {
                const dropdownData = this.dropdowns.get(this.activeDropdown);
                if (dropdownData && dropdownData.close) {
                    dropdownData.close();
                } else {
                    this.closeDropdown(this.activeDropdown);
                }
            }
        });
        
        // Handle Livewire navigation
        if (window.Livewire) {
            Livewire.hook('message.processed', () => {
                this.initializeDropdowns();
            });
        }
    }
    
    setupMutationObserver() {
        this.observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        // Check if it's a dropdown or contains dropdowns
                        if (this.isDropdown(node)) {
                            this.initializeDropdown(node);
                        } else if (node.querySelectorAll) {
                            const dropdowns = node.querySelectorAll('[x-data*="dropdown"], .fi-dropdown, [data-dropdown]');
                            dropdowns.forEach(dropdown => this.initializeDropdown(dropdown));
                        }
                    }
                });
            });
        });
        
        this.observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    initializeDropdowns() {
        // Find all dropdowns in the page
        const selectors = [
            '[x-data*="dropdown"]',
            '.fi-dropdown',
            '[data-dropdown]',
            '.branch-selector-dropdown',
            '.fi-user-menu',
            '[x-on\\:click="open = !open"]'
        ];
        
        selectors.forEach(selector => {
            document.querySelectorAll(selector).forEach(dropdown => {
                this.initializeDropdown(dropdown);
            });
        });
    }
    
    initializeDropdown(element) {
        // Skip if already initialized or if it's managed by Alpine
        if (element.dataset.dropdownInitialized || element.__x) {
            return;
        }
        
        element.dataset.dropdownInitialized = 'true';
        
        // For non-Alpine dropdowns, add basic functionality
        const trigger = element.querySelector('[data-dropdown-trigger], button:first-child');
        const panel = element.querySelector('[data-dropdown-panel], [role="menu"], .dropdown-menu');
        
        if (trigger && panel) {
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleDropdown(element, panel);
            });
        }
    }
    
    isDropdown(element) {
        return element.matches('[x-data*="dropdown"], .fi-dropdown, [data-dropdown], .branch-selector-dropdown');
    }
    
    registerDropdown(element, alpineInstance) {
        this.dropdowns.set(element, alpineInstance);
    }
    
    toggleDropdown(element, panel) {
        const isOpen = !panel.classList.contains('hidden') && !panel.classList.contains('invisible');
        
        if (isOpen) {
            this.closeDropdown(element);
        } else {
            this.openDropdown(element, panel);
        }
    }
    
    openDropdown(element, panel) {
        // Close all other dropdowns
        this.closeAll(element);
        
        // Open this dropdown
        panel.classList.remove('hidden', 'invisible', 'opacity-0');
        panel.style.display = '';
        
        this.activeDropdown = element;
        
        // Position if needed
        this.positionDropdown(element, panel);
    }
    
    closeDropdown(element) {
        const panel = element.querySelector('[data-dropdown-panel], [role="menu"], .dropdown-menu');
        if (panel) {
            panel.classList.add('invisible', 'opacity-0');
            // Don't use hidden class as it can conflict with transitions
        }
        
        if (this.activeDropdown === element) {
            this.activeDropdown = null;
        }
    }
    
    closeAll(except = null) {
        // Close Alpine dropdowns
        this.dropdowns.forEach((instance, element) => {
            if (element !== except && instance.close) {
                instance.close();
            }
        });
        
        // Close non-Alpine dropdowns
        document.querySelectorAll('[data-dropdown-initialized]').forEach(dropdown => {
            if (dropdown !== except) {
                this.closeDropdown(dropdown);
            }
        });
        
        this.activeDropdown = null;
    }
    
    positionDropdown(element, panel) {
        // Simple positioning logic
        const rect = element.getBoundingClientRect();
        const panelRect = panel.getBoundingClientRect();
        const viewportHeight = window.innerHeight;
        
        // Check if dropdown would overflow bottom
        if (rect.bottom + panelRect.height > viewportHeight && rect.top > panelRect.height) {
            panel.style.bottom = '100%';
            panel.style.top = 'auto';
        }
    }
    
    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
        
        this.dropdowns = new WeakMap();
        this.activeDropdown = null;
        this.initialized = false;
    }
}

// Initialize and make globally available
window.dropdownManager = new DropdownManager();

// Export for modules
export default DropdownManager;