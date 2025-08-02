// Initialize Alpine.js store for mobile sidebar
document.addEventListener('alpine:init', () => {
    // Check if Filament already has a sidebar store
    if (!Alpine.store('sidebar')) {
        Alpine.store('sidebar', {
            isOpen: false,
            collapsedGroups: Alpine.$persist([]).as('collapsedGroups'),
            
            open() {
                this.isOpen = true;
                document.body.classList.add('overflow-hidden');
            },
            
            close() {
                this.isOpen = false;
                document.body.classList.remove('overflow-hidden');
            },
            
            toggle() {
                this.isOpen ? this.close() : this.open();
            },
            
            groupIsCollapsed(group) {
                return this.collapsedGroups.includes(group);
            },
            
            collapseGroup(group) {
                if (!this.collapsedGroups.includes(group)) {
                    this.collapsedGroups.push(group);
                }
            },
            
            expandGroup(group) {
                this.collapsedGroups = this.collapsedGroups.filter(g => g !== group);
            },
            
            toggleGroup(group) {
                if (this.groupIsCollapsed(group)) {
                    this.expandGroup(group);
                } else {
                    this.collapseGroup(group);
                }
            }
        });
    } else {
        // Extend existing Filament sidebar store with mobile functionality
        const existingStore = Alpine.store('sidebar');
        
        // Add mobile-specific properties if they don't exist
        if (typeof existingStore.isOpen === 'undefined') {
            existingStore.isOpen = false;
        }
        
        // Add mobile methods if they don't exist
        if (typeof existingStore.open !== 'function') {
            existingStore.open = function() {
                this.isOpen = true;
                document.body.classList.add('overflow-hidden');
            };
        }
        
        if (typeof existingStore.close !== 'function') {
            existingStore.close = function() {
                this.isOpen = false;
                document.body.classList.remove('overflow-hidden');
            };
        }
        
        if (typeof existingStore.toggle !== 'function') {
            existingStore.toggle = function() {
                this.isOpen ? this.close() : this.open();
            };
        }
        
        // Ensure group methods exist for Filament compatibility
        if (typeof existingStore.groupIsCollapsed !== 'function') {
            existingStore.collapsedGroups = Alpine.$persist([]).as('collapsedGroups');
            
            existingStore.groupIsCollapsed = function(group) {
                return this.collapsedGroups.includes(group);
            };
            
            existingStore.collapseGroup = function(group) {
                if (!this.collapsedGroups.includes(group)) {
                    this.collapsedGroups.push(group);
                }
            };
            
            existingStore.expandGroup = function(group) {
                this.collapsedGroups = this.collapsedGroups.filter(g => g !== group);
            };
            
            existingStore.toggleGroup = function(group) {
                if (this.groupIsCollapsed(group)) {
                    this.expandGroup(group);
                } else {
                    this.collapseGroup(group);
                }
            };
        }
    }
    
    // Close sidebar on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && Alpine.store('sidebar').isOpen) {
            Alpine.store('sidebar').close();
        }
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        const sidebar = document.querySelector('.fi-sidebar');
        const toggle = document.querySelector('.fi-sidebar-toggle');
        
        if (sidebar && toggle && Alpine.store('sidebar').isOpen) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                Alpine.store('sidebar').close();
            }
        }
    });
});