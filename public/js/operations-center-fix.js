// Operations Center Fix - Legacy compatibility file
console.log('🔧 Operations Center Fix Loading...');

// This file ensures backward compatibility
// All components are now defined in alpine-components-fix.js

// Check if components are already loaded
if (typeof window.dateFilterDropdownEnhanced === 'undefined') {
    console.warn('⚠️ Alpine components not loaded yet, defining fallbacks...');
    
    // Fallback definitions
    window.dateFilterDropdownEnhanced = () => ({
        showDateFilter: false,
        selectedPeriod: 'today',
        toggle() { this.showDateFilter = !this.showDateFilter; },
        selectPeriod(period) { 
            this.selectedPeriod = period; 
            this.showDateFilter = false; 
        }
    });
    
    window.companyBranchSelect = () => ({
        showDropdown: false,
        toggleDropdown() { this.showDropdown = !this.showDropdown; },
        closeDropdown() { this.showDropdown = false; },
        getCompactLabel() { return 'All Companies'; }
    });
    
    window.timeRangeFilter = () => ({
        showDropdown: false,
        selectedRange: 'today',
        toggle() { this.showDropdown = !this.showDropdown; },
        selectRange(range) { 
            this.selectedRange = range; 
            this.showDropdown = false; 
        },
        getRangeLabel() { return 'Today'; }
    });
    
    window.kpiFilters = () => ({
        showFilters: false,
        activeFilters: [],
        toggle() { this.showFilters = !this.showFilters; },
        toggleFilter(filter) {
            const index = this.activeFilters.indexOf(filter);
            if (index > -1) {
                this.activeFilters.splice(index, 1);
            } else {
                this.activeFilters.push(filter);
            }
        },
        isActive(filter) { return this.activeFilters.includes(filter); }
    });
}

console.log('✅ Operations Center Fix loaded');