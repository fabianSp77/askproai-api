/**
 * Operations Dashboard Alpine Fix
 * Specific fixes for Alpine.js issues on the Operations Dashboard
 */
(function() {
    'use strict';
    
    console.log('[Operations Dashboard Alpine Fix] Loading...');
    
    // Fix Alpine before it initializes
    document.addEventListener('alpine:init', () => {
        console.log('[Operations Dashboard Alpine Fix] Alpine is initializing...');
        
        // Ensure Alpine has all required features
        if (window.Alpine) {
            // Add missing magic properties if needed
            if (!Alpine.magic('persist')) {
                Alpine.magic('persist', () => {
                    return (value) => {
                        return {
                            init(val) {
                                return val;
                            },
                            set(val) {
                                // Simple persist implementation
                            },
                            as(key) {
                                const storageKey = key;
                                return {
                                    init(val) {
                                        const stored = localStorage.getItem(storageKey);
                                        if (stored !== null) {
                                            try {
                                                return JSON.parse(stored);
                                            } catch (e) {
                                                return stored;
                                            }
                                        }
                                        return val;
                                    },
                                    set(val) {
                                        localStorage.setItem(storageKey, JSON.stringify(val));
                                    }
                                };
                            }
                        };
                    };
                });
            }
            
            // Pre-register the companyBranchSelect component data
            Alpine.data('companyBranchSelect', () => ({
                showDropdown: false,
                searchQuery: '',
                selectedCompany: null,
                selectedBranches: [],
                expandedCompanies: [],
                
                init() {
                    console.log('[Operations Dashboard Alpine Fix] Company branch select initializing...');
                    // Initialize from Livewire if available
                    if (this.$wire) {
                        this.selectedCompany = this.$wire.selectedCompany;
                        this.selectedBranches = this.$wire.selectedBranches || [];
                    }
                    
                    if (this.selectedCompany) {
                        this.expandedCompanies.push(this.selectedCompany);
                    }
                },
                
                matchesSearch(text) {
                    if (!this.searchQuery) return true;
                    return text.toLowerCase().includes(this.searchQuery.toLowerCase());
                },
                
                toggleCompany(companyId) {
                    if (this.selectedCompany === companyId) {
                        this.selectedCompany = null;
                        this.selectedBranches = [];
                    } else {
                        this.selectedCompany = companyId;
                        this.selectedBranches = [];
                        
                        if (!this.expandedCompanies.includes(companyId)) {
                            this.expandedCompanies.push(companyId);
                        }
                    }
                    
                    // Update Livewire
                    if (this.$wire) {
                        this.$wire.set('selectedCompany', this.selectedCompany);
                        this.$wire.set('selectedBranches', this.selectedBranches);
                        this.$wire.refresh();
                    }
                },
                
                toggleBranch(companyId, branchId) {
                    if (this.selectedCompany !== companyId) {
                        this.selectedCompany = companyId;
                    }
                    
                    const index = this.selectedBranches.indexOf(branchId);
                    if (index > -1) {
                        this.selectedBranches.splice(index, 1);
                    } else {
                        this.selectedBranches.push(branchId);
                    }
                    
                    // Update Livewire
                    if (this.$wire) {
                        this.$wire.set('selectedCompany', this.selectedCompany);
                        this.$wire.set('selectedBranches', this.selectedBranches);
                        this.$wire.refresh();
                    }
                },
                
                isCompanySelected(companyId) {
                    return this.selectedCompany === companyId;
                },
                
                isBranchSelected(branchId) {
                    return this.selectedBranches.includes(branchId);
                },
                
                hasSearchResults() {
                    return true; // Simplified for now
                },
                
                getCompactLabel() {
                    if (!this.selectedCompany) {
                        return 'Alle Unternehmen';
                    }
                    
                    const companyEl = document.querySelector(`[data-company-id="${this.selectedCompany}"]`);
                    const companyName = companyEl ? companyEl.textContent.trim() : 'Unternehmen';
                    
                    if (this.selectedBranches.length === 0) {
                        return companyName + ' (Alle Filialen)';
                    } else if (this.selectedBranches.length === 1) {
                        return companyName + ' (1 Filiale)';
                    } else {
                        return companyName + ` (${this.selectedBranches.length} Filialen)`;
                    }
                }
            }));
        }
    });
    
    // Ensure stores are available
    document.addEventListener('alpine:initialized', () => {
        console.log('[Operations Dashboard Alpine Fix] Alpine initialized, ensuring stores...');
        
        if (window.Alpine && !Alpine.store('sidebar')) {
            Alpine.store('sidebar', {
                isOpen: window.matchMedia('(min-width: 1024px)').matches,
                collapsedGroups: [],
                
                open() {
                    this.isOpen = true;
                },
                
                close() {
                    this.isOpen = false;
                },
                
                toggle() {
                    this.isOpen = !this.isOpen;
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
                }
            });
        }
    });
    
    // Fix after Livewire navigation
    document.addEventListener('livewire:navigated', () => {
        console.log('[Operations Dashboard Alpine Fix] Livewire navigated, checking Alpine...');
        
        // Re-initialize Alpine components if needed
        setTimeout(() => {
            const uninitializedComponents = document.querySelectorAll('[x-data]:not([data-alpine-generated-me])');
            if (uninitializedComponents.length > 0 && window.Alpine) {
                console.log(`[Operations Dashboard Alpine Fix] Found ${uninitializedComponents.length} uninitialized components`);
                uninitializedComponents.forEach(el => {
                    try {
                        Alpine.initTree(el);
                    } catch (e) {
                        console.error('[Operations Dashboard Alpine Fix] Error initializing component:', e);
                    }
                });
            }
        }, 100);
    });
    
    console.log('[Operations Dashboard Alpine Fix] Ready');
})();