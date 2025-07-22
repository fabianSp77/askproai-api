/**
 * Alpine.js Stores Fix for Admin Portal
 * Fixes: Cannot read properties of undefined (reading 'isOpen')
 */

(function() {
    'use strict';

    // Initialize stores immediately to prevent undefined errors
    function initStoresImmediately() {
        // Create a temporary store object if Alpine isn't ready yet
        if (!window.Alpine || !window.Alpine.store) {
            window._alpineStoresTemp = window._alpineStoresTemp || {};
            
            // Create sidebar store
            const collapsedGroups = JSON.parse(window.localStorage.getItem('filament.sidebar.collapsedGroups') || '[]');
            window._alpineStoresTemp.sidebar = {
                isOpen: window.localStorage.getItem('filament.sidebar.isOpen') !== 'false',
                collapsedGroups: collapsedGroups,
                
                open() {
                    this.isOpen = true;
                    window.localStorage.setItem('filament.sidebar.isOpen', 'true');
                },
                
                close() {
                    this.isOpen = false;
                    window.localStorage.setItem('filament.sidebar.isOpen', 'false');
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
                    window.localStorage.setItem('filament.sidebar.collapsedGroups', JSON.stringify(this.collapsedGroups));
                },
                
                collapseGroup(group) {
                    if (!this.groupIsCollapsed(group)) {
                        this.collapsedGroups.push(group);
                        window.localStorage.setItem('filament.sidebar.collapsedGroups', JSON.stringify(this.collapsedGroups));
                    }
                },
                
                expandGroup(group) {
                    this.collapsedGroups = this.collapsedGroups.filter(g => g !== group);
                    window.localStorage.setItem('filament.sidebar.collapsedGroups', JSON.stringify(this.collapsedGroups));
                }
            };
            
            // Create theme store
            const savedTheme = window.localStorage.getItem('theme') || 'system';
            window._alpineStoresTemp.theme = savedTheme;
        }
    }

    // Initialize Alpine stores when Alpine is ready
    function initializeAlpineStores() {
        // Wait for Alpine to be available
        if (typeof window.Alpine === 'undefined' || !window.Alpine.store) {
            setTimeout(initializeAlpineStores, 10);
            return;
        }

        // Check if stores already exist (Filament might have initialized them)
        const existingSidebar = window.Alpine.store('sidebar');
        const existingTheme = window.Alpine.store('theme');
        
        // Initialize sidebar store if it doesn't exist or is incomplete
        if (!existingSidebar || !existingSidebar.groupIsCollapsed) {
            const collapsedGroups = JSON.parse(window.localStorage.getItem('filament.sidebar.collapsedGroups') || '[]');
            
            // Merge with existing store if it exists
            const sidebarStore = {
                isOpen: existingSidebar?.isOpen ?? (window.localStorage.getItem('filament.sidebar.isOpen') !== 'false'),
                collapsedGroups: existingSidebar?.collapsedGroups ?? collapsedGroups,
                
                open() {
                    this.isOpen = true;
                    window.localStorage.setItem('filament.sidebar.isOpen', 'true');
                },
                
                close() {
                    this.isOpen = false;
                    window.localStorage.setItem('filament.sidebar.isOpen', 'false');
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
                    window.localStorage.setItem('filament.sidebar.collapsedGroups', JSON.stringify(this.collapsedGroups));
                },
                
                collapseGroup(group) {
                    if (!this.groupIsCollapsed(group)) {
                        this.collapsedGroups.push(group);
                        window.localStorage.setItem('filament.sidebar.collapsedGroups', JSON.stringify(this.collapsedGroups));
                    }
                },
                
                expandGroup(group) {
                    this.collapsedGroups = this.collapsedGroups.filter(g => g !== group);
                    window.localStorage.setItem('filament.sidebar.collapsedGroups', JSON.stringify(this.collapsedGroups));
                }
            };
            
            // Override existing store or create new one
            window.Alpine.store('sidebar', sidebarStore);
        }

        // Initialize theme store if it doesn't exist
        if (!existingTheme || typeof existingTheme === 'string') {
            const savedTheme = window.localStorage.getItem('theme') || 'system';
            const currentTheme = existingTheme || savedTheme;
            
            window.Alpine.store('theme', currentTheme);
        }
        
        // Clean up temporary stores
        delete window._alpineStoresTemp;
    }

    // Initialize Alpine Components
    function initializeAlpineComponents() {
        if (typeof window.Alpine === 'undefined') {
            setTimeout(initializeAlpineComponents, 10);
            return;
        }

        // Register global Alpine data
        window.Alpine.data('companyBranchSelect', () => ({
            selectedCompany: null,
            selectedBranch: null,
            companies: [],
            branches: [],
            searchQuery: '',
            showDropdown: false,
            expandedCompanies: [],
            
            init() {
                // Initialize data
                this.selectedCompany = this.$wire?.selectedCompany || null;
                this.selectedBranch = this.$wire?.selectedBranch || null;
            },
            
            toggleCompany(companyId) {
                if (this.expandedCompanies.includes(companyId)) {
                    this.expandedCompanies = this.expandedCompanies.filter(id => id !== companyId);
                } else {
                    this.expandedCompanies.push(companyId);
                }
            },
            
            selectBranch(branch) {
                this.selectedBranch = branch;
                this.showDropdown = false;
                if (this.$wire) {
                    this.$wire.selectBranch(branch.id);
                }
            },
            
            getCompactLabel() {
                if (this.selectedBranch) {
                    return this.selectedBranch.name;
                }
                return 'Select Branch';
            }
        }));

        // Register other commonly used Alpine components
        window.Alpine.data('dropdown', () => ({
            open: false,
            toggle() {
                this.open = !this.open;
            },
            close() {
                this.open = false;
            }
        }));

        window.Alpine.data('modal', () => ({
            open: false,
            show() {
                this.open = true;
            },
            hide() {
                this.open = false;
            }
        }));

        window.Alpine.data('tabs', () => ({
            activeTab: null,
            setActiveTab(tab) {
                this.activeTab = tab;
            }
        }));

        // Register utility functions
        window.Alpine.magic('clipboard', () => {
            return (text) => {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text);
                }
            };
        });
    }

    // Try multiple initialization points to ensure stores are available
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initializeAlpineStores();
            initializeAlpineComponents();
        });
    } else {
        initializeAlpineStores();
        initializeAlpineComponents();
    }

    // Also initialize on Alpine init event
    document.addEventListener('alpine:init', function() {
        initializeAlpineStores();
        initializeAlpineComponents();
    });

    // And on Livewire load
    if (window.Livewire) {
        window.Livewire.on('load', function() {
            initializeAlpineStores();
            initializeAlpineComponents();
        });
    }

    // Fallback: Initialize after a short delay
    setTimeout(function() {
        initializeAlpineStores();
        initializeAlpineComponents();
    }, 100);
    
    // Initialize temporary stores immediately
    initStoresImmediately();

})();