// Alpine.js Components Fix for Admin Panel
// console.log('🔧 Loading Alpine Components Fix...');

// Define components globally IMMEDIATELY for inline x-data usage
window.dateFilterDropdownEnhanced = () => ({
    showDateFilter: false,
    selectedPeriod: 'today',
    customStartDate: '',
    customEndDate: '',
    
    toggle() {
        this.showDateFilter = !this.showDateFilter;
    },
    
    selectPeriod(period) {
        this.selectedPeriod = period;
        this.showDateFilter = false;
        if (this.$wire) {
            this.$wire.set('period', period);
        }
    },
    
    applyCustomRange() {
        if (this.customStartDate && this.customEndDate) {
            this.selectedPeriod = 'custom';
            this.showDateFilter = false;
            if (this.$wire) {
                this.$wire.set('startDate', this.customStartDate);
                this.$wire.set('endDate', this.customEndDate);
            }
        }
    }
});

// Company Branch Select Component
window.companyBranchSelect = () => ({
    showDropdown: false,
    selectedCompany: null,
    selectedBranch: null,
    companies: [],
    branches: [],
    
    init() {
        // Initialize with Livewire data if available
        if (this.$wire) {
            this.selectedCompany = this.$wire.selectedCompany;
            this.selectedBranch = this.$wire.selectedBranch;
        }
    },
    
    toggleDropdown() {
        this.showDropdown = !this.showDropdown;
    },
    
    closeDropdown() {
        this.showDropdown = false;
    },
    
    selectCompany(companyId) {
        this.selectedCompany = companyId;
        this.selectedBranch = null;
        if (this.$wire) {
            this.$wire.set('selectedCompany', companyId);
        }
        this.loadBranches(companyId);
    },
    
    selectBranch(branchId) {
        this.selectedBranch = branchId;
        if (this.$wire) {
            this.$wire.set('selectedBranch', branchId);
        }
        this.closeDropdown();
    },
    
    loadBranches(companyId) {
        // Load branches for company
        if (this.$wire && this.$wire.loadBranches) {
            this.$wire.loadBranches(companyId);
        }
    },
    
    getCompactLabel() {
        if (this.selectedBranch && this.selectedCompany) {
            return `Company ${this.selectedCompany} - Branch ${this.selectedBranch}`;
        } else if (this.selectedCompany) {
            return `Company ${this.selectedCompany} - All Branches`;
        }
        return 'All Companies';
    }
});

// Time Range Filter Component
window.timeRangeFilter = () => ({
    showDropdown: false,
    selectedRange: 'today',
    customStart: '',
    customEnd: '',
    
    toggle() {
        this.showDropdown = !this.showDropdown;
    },
    
    selectRange(range) {
        this.selectedRange = range;
        this.showDropdown = false;
        if (this.$wire) {
            this.$wire.set('timeRange', range);
        }
    },
    
    applyCustom() {
        if (this.customStart && this.customEnd) {
            this.selectedRange = 'custom';
            this.showDropdown = false;
            if (this.$wire) {
                this.$wire.set('customStart', this.customStart);
                this.$wire.set('customEnd', this.customEnd);
            }
        }
    },
    
    getRangeLabel() {
        const labels = {
            'today': 'Today',
            'yesterday': 'Yesterday',
            'week': 'This Week',
            'month': 'This Month',
            'custom': 'Custom Range'
        };
        return labels[this.selectedRange] || 'Select Range';
    }
});

// KPI Filters Component
window.kpiFilters = () => ({
    showFilters: false,
    activeFilters: [],
    
    toggle() {
        this.showFilters = !this.showFilters;
    },
    
    toggleFilter(filter) {
        const index = this.activeFilters.indexOf(filter);
        if (index > -1) {
            this.activeFilters.splice(index, 1);
        } else {
            this.activeFilters.push(filter);
        }
        if (this.$wire) {
            this.$wire.set('activeFilters', this.activeFilters);
        }
    },
    
    isActive(filter) {
        return this.activeFilters.includes(filter);
    },
    
    clearFilters() {
        this.activeFilters = [];
        if (this.$wire) {
            this.$wire.set('activeFilters', []);
        }
    }
});

// Also ensure showDateFilter is available globally
window.showDateFilter = false;

// Admin Dropdown Component
window.adminDropdown = () => ({
    open: false,
    
    toggle() {
        this.open = !this.open;
    },
    
    close() {
        this.open = false;
    },
    
    init() {
        // Close on click outside
        this.$watch('open', value => {
            if (value) {
                this.$nextTick(() => {
                    window.addEventListener('click', this.closeHandler = (e) => {
                        if (!this.$el.contains(e.target)) {
                            this.close();
                        }
                    });
                });
            } else {
                window.removeEventListener('click', this.closeHandler);
            }
        });
    }
});

// Table Actions Component
window.tableActions = () => ({
    selectedRows: [],
    selectAll: false,
    
    toggleRow(id) {
        const index = this.selectedRows.indexOf(id);
        if (index > -1) {
            this.selectedRows.splice(index, 1);
        } else {
            this.selectedRows.push(id);
        }
        this.updateSelectAll();
    },
    
    toggleAll(ids) {
        if (this.selectAll) {
            this.selectedRows = [];
            this.selectAll = false;
        } else {
            this.selectedRows = [...ids];
            this.selectAll = true;
        }
    },
    
    updateSelectAll() {
        // Update select all checkbox state
        const allRows = document.querySelectorAll('[data-row-id]');
        this.selectAll = allRows.length > 0 && this.selectedRows.length === allRows.length;
    },
    
    isSelected(id) {
        return this.selectedRows.includes(id);
    },
    
    hasSelection() {
        return this.selectedRows.length > 0;
    },
    
    clearSelection() {
        this.selectedRows = [];
        this.selectAll = false;
    },
    
    getSelectedCount() {
        return this.selectedRows.length;
    }
});

// Register missing Alpine components
document.addEventListener('alpine:init', () => {
    // console.log('📦 Registering Alpine components...');
    
    // Date Filter Dropdown Component
    Alpine.data('dateFilterDropdownEnhanced', () => ({
        showDateFilter: false,
        selectedPeriod: 'today',
        customStartDate: '',
        customEndDate: '',
        
        init() {
            console.log('Date filter dropdown initialized');
        },
        
        toggleDropdown() {
            this.showDateFilter = !this.showDateFilter;
        },
        
        closeDropdown() {
            this.showDateFilter = false;
        },
        
        openDropdown() {
            this.showDateFilter = true;
        },
        
        toggle() {
            this.showDateFilter = !this.showDateFilter;
        },
        
        selectPeriod(period) {
            this.selectedPeriod = period;
            this.showDateFilter = false;
            // Trigger Livewire update if needed
            if (this.$wire) {
                this.$wire.set('period', period);
            }
        },
        
        applyCustomRange() {
            if (this.customStartDate && this.customEndDate) {
                this.selectedPeriod = 'custom';
                this.showDateFilter = false;
                // Trigger Livewire update if needed
                if (this.$wire) {
                    this.$wire.set('startDate', this.customStartDate);
                    this.$wire.set('endDate', this.customEndDate);
                }
            }
        }
    }));
    
    // Company Branch Select Component
    Alpine.data('companyBranchSelect', () => ({
        showDropdown: false,
        searchQuery: '',
        selectedCompany: null,
        selectedBranches: [],
        expandedCompanies: [],
        
        init() {
            console.log('Company branch select initialized');
            // Bind methods to ensure proper context
            this.isCompanySelected = this.isCompanySelected.bind(this);
            this.isBranchSelected = this.isBranchSelected.bind(this);
            this.hasSearchResults = this.hasSearchResults.bind(this);
            this.matchesSearch = this.matchesSearch.bind(this);
        },
        
        toggleDropdown() {
            this.showDropdown = !this.showDropdown;
        },
        
        closeDropdown() {
            this.showDropdown = false;
        },
        
        openDropdown() {
            this.showDropdown = true;
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
            
            // Update Livewire if available
            if (this.$wire) {
                this.$wire.set('selectedCompany', this.selectedCompany);
                this.$wire.set('selectedBranches', this.selectedBranches);
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
            
            // Update Livewire if available
            if (this.$wire) {
                this.$wire.set('selectedCompany', this.selectedCompany);
                this.$wire.set('selectedBranches', this.selectedBranches);
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
            // Basic implementation - can be enhanced based on data
            return true;
        }
    }));
    
    // Time Range Filter Component
    Alpine.data('timeRangeFilter', () => ({
        selectedRange: 'today',
        customStart: '',
        customEnd: '',
        showCustom: false,
        
        init() {
            console.log('Time range filter initialized');
        },
        
        selectRange(range) {
            this.selectedRange = range;
            this.showCustom = range === 'custom';
            
            if (this.$wire && range !== 'custom') {
                this.$wire.set('timeRange', range);
            }
        },
        
        applyCustomRange() {
            if (this.customStart && this.customEnd && this.$wire) {
                this.$wire.set('customStart', this.customStart);
                this.$wire.set('customEnd', this.customEnd);
                this.$wire.set('timeRange', 'custom');
            }
        }
    }));
    
    // KPI Filters Component
    Alpine.data('kpiFilters', () => ({
        activeFilters: [],
        availableFilters: {
            calls: 'Anrufe',
            appointments: 'Termine',
            revenue: 'Umsatz',
            conversion: 'Conversion Rate'
        },
        
        init() {
            console.log('KPI filters initialized');
        },
        
        toggleFilter(filterKey) {
            const index = this.activeFilters.indexOf(filterKey);
            if (index > -1) {
                this.activeFilters.splice(index, 1);
            } else {
                this.activeFilters.push(filterKey);
            }
            
            if (this.$wire) {
                this.$wire.set('activeKpiFilters', this.activeFilters);
            }
        },
        
        isFilterActive(filterKey) {
            return this.activeFilters.includes(filterKey);
        },
        
        clearAllFilters() {
            this.activeFilters = [];
            if (this.$wire) {
                this.$wire.set('activeKpiFilters', []);
            }
        }
    }));
    
    // Admin Dropdown Component (generic)
    Alpine.data('adminDropdown', () => ({
        open: false,
        
        toggle() {
            this.open = !this.open;
        },
        
        close() {
            this.open = false;
        }
    }));
    
    // Table Actions Component
    Alpine.data('tableActions', () => ({
        selectedRows: [],
        
        toggleRow(id) {
            const index = this.selectedRows.indexOf(id);
            if (index > -1) {
                this.selectedRows.splice(index, 1);
            } else {
                this.selectedRows.push(id);
            }
        },
        
        selectAll(ids) {
            this.selectedRows = [...ids];
        },
        
        deselectAll() {
            this.selectedRows = [];
        }
    }));
    
    // console.log('✅ Alpine components registered');
});

// Also expose globally for any inline x-data usage
window.dateFilterDropdownEnhanced = () => ({
    showDateFilter: false,
    selectedPeriod: 'today',
    customStartDate: '',
    customEndDate: '',
    
    toggleDropdown() {
        this.showDateFilter = !this.showDateFilter;
    },
    
    closeDropdown() {
        this.showDateFilter = false;
    },
    
    toggle() {
        this.showDateFilter = !this.showDateFilter;
    },
    
    selectPeriod(period) {
        this.selectedPeriod = period;
        this.showDateFilter = false;
    },
    
    applyCustomRange() {
        if (this.customStartDate && this.customEndDate) {
            this.selectedPeriod = 'custom';
            this.showDateFilter = false;
        }
    }
});

// Expose other components globally as well
window.companyBranchSelect = () => ({
    showDropdown: false,
    searchQuery: '',
    selectedCompany: null,
    selectedBranches: [],
    expandedCompanies: [],
    
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
        console.log('Global fallback: toggleCompany', companyId);
    },
    
    toggleBranch(companyId, branchId) {
        console.log('Global fallback: toggleBranch', companyId, branchId);
    },
    
    isCompanySelected(companyId) {
        return false;
    },
    
    isBranchSelected(branchId) {
        return false;
    },
    
    getCompactLabel() {
        return 'Alle Unternehmen';
    },
    
    hasSearchResults() {
        return true;
    }
});

window.timeRangeFilter = () => ({
    selectedRange: 'today',
    customStart: '',
    customEnd: '',
    showCustom: false,
    
    selectRange(range) {
        this.selectedRange = range;
        this.showCustom = range === 'custom';
    },
    
    applyCustomRange() {
        console.log('Apply custom range:', this.customStart, this.customEnd);
    }
});

window.kpiFilters = () => ({
    activeFilters: [],
    
    toggleFilter(filterKey) {
        const index = this.activeFilters.indexOf(filterKey);
        if (index > -1) {
            this.activeFilters.splice(index, 1);
        } else {
            this.activeFilters.push(filterKey);
        }
    },
    
    isFilterActive(filterKey) {
        return this.activeFilters.includes(filterKey);
    },
    
    clearAllFilters() {
        this.activeFilters = [];
    }
});

// Global utility functions to ensure they're always available
window.showDateFilter = false;
window.hasSearchResults = () => true;
window.isCompanySelected = (companyId) => false;
window.isBranchSelected = (branchId) => false;
window.matchesSearch = (text) => true;
window.toggleCompany = (companyId) => console.log('Global fallback: toggleCompany', companyId);
window.toggleBranch = (companyId, branchId) => console.log('Global fallback: toggleBranch', companyId, branchId);