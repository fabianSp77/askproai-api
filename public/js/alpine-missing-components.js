/**
 * Alpine.js Missing Components Fix
 * Defines all missing Alpine components for the Operations Dashboard
 */

document.addEventListener('alpine:init', () => {
    // Company/Branch Select Component
    Alpine.data('companyBranchSelect', () => ({
        showDropdown: false,
        searchQuery: '',
        expandedCompanies: [],
        selectedCompany: null,
        selectedBranch: null,
        
        init() {
            console.log('✅ Company/Branch Select initialized');
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
            const index = this.expandedCompanies.indexOf(companyId);
            if (index > -1) {
                this.expandedCompanies.splice(index, 1);
            } else {
                this.expandedCompanies.push(companyId);
            }
        },
        
        isCompanySelected(companyId) {
            return this.selectedCompany === companyId;
        },
        
        isBranchSelected(branchId) {
            return this.selectedBranch === branchId;
        },
        
        selectCompany(companyId) {
            this.selectedCompany = companyId;
            this.selectedBranch = null;
        },
        
        selectBranch(branchId, companyId) {
            this.selectedBranch = branchId;
            this.selectedCompany = companyId;
        },
        
        getCompactLabel() {
            if (this.selectedBranch) {
                const branchEl = document.querySelector(`[data-branch-id="${this.selectedBranch}"]`);
                if (branchEl) return branchEl.textContent.trim();
            }
            if (this.selectedCompany) {
                const companyEl = document.querySelector(`[data-company-id="${this.selectedCompany}"]`);
                if (companyEl) return companyEl.textContent.trim();
            }
            return 'Alle Unternehmen';
        },
        
        hasSearchResults() {
            return true; // Simplified for now
        }
    }));
    
    // Date Filter Dropdown Component
    Alpine.data('dateFilterDropdownEnhanced', () => ({
        showDropdown: false,
        selectedPeriod: 'today',
        customDateFrom: '',
        customDateTo: '',
        
        init() {
            console.log('✅ Date Filter Dropdown initialized');
        },
        
        toggleDropdown() {
            this.showDropdown = !this.showDropdown;
        },
        
        closeDropdown() {
            this.showDropdown = false;
        },
        
        selectPeriod(period) {
            this.selectedPeriod = period;
            this.closeDropdown();
            // Trigger filter update
            this.updateFilter();
        },
        
        updateFilter() {
            console.log('Date filter updated:', this.selectedPeriod);
            // This would typically trigger a Livewire update
        },
        
        getPeriodLabel() {
            const labels = {
                'today': 'Heute',
                'yesterday': 'Gestern',
                'week': 'Diese Woche',
                'month': 'Dieser Monat',
                'custom': 'Benutzerdefiniert'
            };
            return labels[this.selectedPeriod] || 'Zeitraum wählen';
        }
    }));
    
    // Simple dropdown for other uses
    Alpine.data('dropdown', () => ({
        open: false,
        
        toggle() {
            this.open = !this.open;
        },
        
        close() {
            this.open = false;
        }
    }));
});

// Also define as global functions for backward compatibility
window.companyBranchSelect = function() {
    return Alpine.data('companyBranchSelect')();
};

window.dateFilterDropdownEnhanced = function() {
    return Alpine.data('dateFilterDropdownEnhanced')();
};

// Define global helper functions that might be used
window.matchesSearch = function(text, query) {
    if (!query) return true;
    return text.toLowerCase().includes(query.toLowerCase());
};

window.closeDropdown = function() {
    console.log('closeDropdown called');
};

window.isCompanySelected = function(id) {
    return false;
};

window.isBranchSelected = function(id) {
    return false;
};

// Add missing showDateFilter global
window.showDateFilter = false;

// Add other potentially missing globals
window.expandedCompanies = [];
window.searchQuery = '';
window.dateFilterDropdown = false;
window.hasSearchResults = function() { return true; };

// Log that components are loaded
console.log('✅ Alpine missing components loaded');