/**
 * Alpine Missing Components Fix
 * Provides stub implementations for missing Alpine components to prevent errors
 */

(function() {
    'use strict';
    
    // Wait for Alpine to be available
    function waitForAlpine(callback) {
        if (window.Alpine) {
            callback();
        } else {
            setTimeout(() => waitForAlpine(callback), 50);
        }
    }
    
    waitForAlpine(() => {
        // Register missing global Alpine components
        window.Alpine.data('companyIntegrationPortal', () => ({
            searchQuery: '',
            expandedCompanies: [],
            selectedCompany: null,
            selectedBranches: [],
            
            matchesSearch(text) {
                if (!this.searchQuery) return true;
                return text.toLowerCase().includes(this.searchQuery.toLowerCase());
            },
            
            isCompanySelected(companyId) {
                return this.selectedCompany === companyId;
            },
            
            isBranchSelected(branchId) {
                return this.selectedBranches.includes(branchId);
            },
            
            hasSearchResults() {
                return true; // Always show results for now
            },
            
            toggleCompany(companyId) {
                const index = this.expandedCompanies.indexOf(companyId);
                if (index > -1) {
                    this.expandedCompanies.splice(index, 1);
                } else {
                    this.expandedCompanies.push(companyId);
                }
            }
        }));
        
        // Register dropdown component
        window.Alpine.data('dateFilterDropdownEnhanced', () => ({
            showDateFilter: false,
            
            closeDropdown() {
                this.showDateFilter = false;
            },
            
            toggleDropdown() {
                this.showDateFilter = !this.showDateFilter;
            }
        }));
        
        // Make functions globally available for inline Alpine expressions
        window.Alpine.magic('searchQuery', () => '');
        window.Alpine.magic('expandedCompanies', () => []);
        window.Alpine.magic('matchesSearch', () => () => true);
        window.Alpine.magic('isCompanySelected', () => () => false);
        window.Alpine.magic('isBranchSelected', () => () => false);
        window.Alpine.magic('hasSearchResults', () => () => true);
        window.Alpine.magic('closeDropdown', () => () => {});
        window.Alpine.magic('showDateFilter', () => false);
    });
})();