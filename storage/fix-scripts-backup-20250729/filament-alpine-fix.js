/**
 * Filament Alpine.js Fix
 * Ensures Alpine stores are properly initialized
 */

(function() {
    'use strict';
    
    // Wait for Alpine to be ready
    function waitForAlpine(callback) {
        if (window.Alpine) {
            callback();
        } else {
            requestAnimationFrame(() => waitForAlpine(callback));
        }
    }
    
    // Initialize stores when Alpine is ready
    waitForAlpine(function() {
        // Check if Alpine has been started
        if (window.Alpine.version) {
            initializeStores();
        } else {
            // Listen for Alpine init
            document.addEventListener('alpine:init', function() {
                initializeStores();
            });
        }
    });
    
    function initializeStores() {
        // Initialize sidebar store if it doesn't exist
        if (!window.Alpine.store('sidebar')) {
            // Get persisted values
            const isOpen = localStorage.getItem('filament.sidebar.isOpen') !== 'false';
            const collapsedGroups = JSON.parse(localStorage.getItem('filament.sidebar.collapsedGroups') || '[]');
            
            window.Alpine.store('sidebar', {
                isOpen: isOpen,
                collapsedGroups: collapsedGroups,
                
                open() {
                    this.isOpen = true;
                    localStorage.setItem('filament.sidebar.isOpen', 'true');
                },
                
                close() {
                    this.isOpen = false;
                    localStorage.setItem('filament.sidebar.isOpen', 'false');
                },
                
                toggle() {
                    this.isOpen ? this.close() : this.open();
                },
                
                groupIsCollapsed(group) {
                    return this.collapsedGroups.includes(group);
                },
                
                toggleCollapsedGroup(group) {
                    if (this.groupIsCollapsed(group)) {
                        this.collapsedGroups = this.collapsedGroups.filter(g => g !== group);
                    } else {
                        this.collapsedGroups.push(group);
                    }
                    localStorage.setItem('filament.sidebar.collapsedGroups', JSON.stringify(this.collapsedGroups));
                },
                
                collapseGroup(group) {
                    if (!this.groupIsCollapsed(group)) {
                        this.collapsedGroups.push(group);
                        localStorage.setItem('filament.sidebar.collapsedGroups', JSON.stringify(this.collapsedGroups));
                    }
                },
                
                expandGroup(group) {
                    this.collapsedGroups = this.collapsedGroups.filter(g => g !== group);
                    localStorage.setItem('filament.sidebar.collapsedGroups', JSON.stringify(this.collapsedGroups));
                }
            });
        }
        
        // Initialize theme store if needed
        if (!window.Alpine.store('theme')) {
            const theme = localStorage.getItem('theme') || 'system';
            window.Alpine.store('theme', theme);
        }
    }
    
    // Also hook into Livewire for SPA navigation
    if (window.Livewire) {
        window.Livewire.hook('commit', ({ component, commit, respond }) => {
            respond(() => {
                // Re-initialize stores after navigation
                waitForAlpine(initializeStores);
            });
        });
    }
})();