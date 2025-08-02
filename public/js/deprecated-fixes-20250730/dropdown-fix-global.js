// Global Dropdown Fix for Admin Portal
// Handles click-outside behavior for all dropdowns

document.addEventListener('alpine:init', () => {
    // Global store for active dropdown tracking
    Alpine.store('dropdownManager', {
        activeDropdown: null,
        
        open(id) {
            this.activeDropdown = id;
        },
        
        close() {
            this.activeDropdown = null;
        },
        
        isOpen(id) {
            return this.activeDropdown === id;
        },
        
        toggle(id) {
            if (this.isOpen(id)) {
                this.close();
            } else {
                this.open(id);
            }
        }
    });
    
    // Global click handler
    document.addEventListener('click', (e) => {
        const activeDropdown = Alpine.store('dropdownManager').activeDropdown;
        
        if (!activeDropdown) return;
        
        // Check if click is outside any dropdown
        const dropdownEl = document.querySelector(`[data-dropdown-id="${activeDropdown}"]`);
        
        if (dropdownEl && !dropdownEl.contains(e.target)) {
            Alpine.store('dropdownManager').close();
        }
    });
});

// Enhanced company/branch select component
window.companyBranchSelectEnhanced = function() {
    return {
        showDropdown: false,
        searchQuery: '',
        selectedCompany: null,
        selectedBranches: [],
        expandedCompanies: [],
        
        init() {
            // Get Livewire properties
            if (this.$wire) {
                this.selectedCompany = this.$wire.selectedCompany;
                this.selectedBranches = this.$wire.selectedBranches || [];
            }
            
            // Initialize expanded state
            if (this.selectedCompany) {
                this.expandedCompanies.push(this.selectedCompany);
            }
            
            // Watch dropdown state
            this.$watch('showDropdown', value => {
                if (value) {
                    Alpine.store('dropdownManager').open('companyBranch');
                } else {
                    Alpine.store('dropdownManager').close();
                }
            });
            
            // React to global dropdown changes
            Alpine.effect(() => {
                if (!Alpine.store('dropdownManager').isOpen('companyBranch')) {
                    this.showDropdown = false;
                }
            });
        },
        
        toggleDropdown() {
            this.showDropdown = !this.showDropdown;
        },
        
        closeDropdown() {
            this.showDropdown = false;
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
        },
        
        hasSearchResults() {
            if (!this.searchQuery || this.searchQuery.trim() === '') {
                return true;
            }
            
            // Check all company and branch names
            const allElements = document.querySelectorAll('[data-company-id], [data-branch-name]');
            
            for (const el of allElements) {
                if (this.matchesSearch(el.textContent)) {
                    return true;
                }
            }
            
            return false;
        }
    };
};

// Enhanced date filter dropdown component
window.dateFilterDropdownEnhanced = function() {
    return {
        showDateFilter: false,
        
        init() {
            this.$watch('showDateFilter', value => {
                if (value) {
                    Alpine.store('dropdownManager').open('dateFilter');
                } else {
                    Alpine.store('dropdownManager').close();
                }
            });
            
            Alpine.effect(() => {
                if (!Alpine.store('dropdownManager').isOpen('dateFilter')) {
                    this.showDateFilter = false;
                }
            });
        },
        
        toggleDropdown() {
            this.showDateFilter = !this.showDateFilter;
        },
        
        closeDropdown() {
            this.showDateFilter = false;
        }
    };
};

// Fix for @click.away compatibility
document.addEventListener('DOMContentLoaded', () => {
    // Replace @click.away with @click.outside for better reliability
    const elementsWithClickAway = document.querySelectorAll('[\\@click\\.away]');
    
    elementsWithClickAway.forEach(el => {
        const handler = el.getAttribute('@click.away');
        
        if (handler) {
            // Remove old attribute
            el.removeAttribute('@click.away');
            
            // Add new handler
            el.setAttribute('@click.outside', handler);
        }
    });
});

console.log('Dropdown fix loaded successfully');