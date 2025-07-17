/**
 * Alpine Sidebar Store Fix
 * Initializes the missing sidebar store for Filament v3
 */
(function() {
    'use strict';
    
    console.log('[Alpine Sidebar Fix] Initializing sidebar store...');
    
    // Wait for Alpine to be available
    function waitForAlpine() {
        if (window.Alpine) {
            initializeSidebarStore();
        } else {
            setTimeout(waitForAlpine, 50);
        }
    }
    
    function initializeSidebarStore() {
        // Check if store already exists
        if (window.Alpine.store('sidebar')) {
            console.log('[Alpine Sidebar Fix] Sidebar store already exists');
            return;
        }
        
        console.log('[Alpine Sidebar Fix] Creating sidebar store...');
        
        // Initialize the sidebar store with Filament's expected structure
        Alpine.store('sidebar', {
            isOpen: window.matchMedia('(min-width: 1024px)').matches,
            
            // Track collapsed groups
            collapsedGroups: Alpine.$persist([]).as('filament.sidebar.collapsedGroups'),
            
            // Open sidebar
            open() {
                this.isOpen = true;
            },
            
            // Close sidebar
            close() {
                this.isOpen = false;
            },
            
            // Toggle sidebar
            toggle() {
                this.isOpen = !this.isOpen;
            },
            
            // Check if a group is collapsed
            groupIsCollapsed(group) {
                return this.collapsedGroups.includes(group);
            },
            
            // Toggle collapsed state of a group
            toggleCollapsedGroup(group) {
                if (this.groupIsCollapsed(group)) {
                    this.collapsedGroups = this.collapsedGroups.filter(g => g !== group);
                } else {
                    this.collapsedGroups.push(group);
                }
            },
            
            // Collapse a group
            collapseGroup(group) {
                if (!this.groupIsCollapsed(group)) {
                    this.collapsedGroups.push(group);
                }
            },
            
            // Expand a group
            expandGroup(group) {
                this.collapsedGroups = this.collapsedGroups.filter(g => g !== group);
            }
        });
        
        // Also initialize theme store if missing
        if (!window.Alpine.store('theme')) {
            Alpine.store('theme', 'system');
        }
        
        // Handle responsive behavior
        const mediaQuery = window.matchMedia('(min-width: 1024px)');
        mediaQuery.addEventListener('change', (e) => {
            if (Alpine.store('sidebar')) {
                Alpine.store('sidebar').isOpen = e.matches;
            }
        });
        
        console.log('[Alpine Sidebar Fix] Sidebar store initialized successfully');
        
        // Dispatch event to notify other scripts
        window.dispatchEvent(new Event('alpine-sidebar-ready'));
    }
    
    // Start the process
    waitForAlpine();
    
    // Also listen for Alpine init event
    document.addEventListener('alpine:init', () => {
        console.log('[Alpine Sidebar Fix] Alpine init event detected');
        initializeSidebarStore();
    });
    
    // Listen for Livewire navigation
    document.addEventListener('livewire:navigated', () => {
        console.log('[Alpine Sidebar Fix] Livewire navigation detected, checking sidebar store...');
        setTimeout(() => {
            if (window.Alpine && !window.Alpine.store('sidebar')) {
                initializeSidebarStore();
            }
        }, 100);
    });
    
    // Public API for debugging
    window.alpineSidebarFix = {
        status() {
            console.log('=== Alpine Sidebar Fix Status ===');
            console.log('Alpine available:', !!window.Alpine);
            console.log('Sidebar store exists:', !!(window.Alpine && window.Alpine.store('sidebar')));
            if (window.Alpine && window.Alpine.store('sidebar')) {
                console.log('Sidebar is open:', Alpine.store('sidebar').isOpen);
                console.log('Collapsed groups:', Alpine.store('sidebar').collapsedGroups);
            }
        },
        
        reinit() {
            if (window.Alpine) {
                initializeSidebarStore();
            }
        }
    };
    
})();