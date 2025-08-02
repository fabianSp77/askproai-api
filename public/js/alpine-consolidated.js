/**
 * Alpine.js Consolidated Components
 * Central location for all Alpine.js component definitions
 * This prevents duplicate definitions and ensures consistency
 */

document.addEventListener('alpine:init', () => {
    console.log('[Alpine Consolidated] Initializing Alpine components...');
    
    // ============================
    // 1. DROPDOWN COMPONENT
    // ============================
    Alpine.data('dropdown', () => ({
        open: false,
        
        init() {
            // Close on escape key
            this.$el.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.open) {
                    this.open = false;
                }
            });
        },
        
        toggle() {
            this.open = !this.open;
        },
        
        close() {
            this.open = false;
        }
    }));
    
    // ============================
    // 2. SIDEBAR STORE
    // ============================
    if (!Alpine.store('sidebar')) {
        Alpine.store('sidebar', {
            isOpen: false,
            
            open() {
                this.isOpen = true;
                document.body.classList.add('fi-sidebar-open');
            },
            
            close() {
                this.isOpen = false;
                document.body.classList.remove('fi-sidebar-open');
            },
            
            toggle() {
                this.isOpen ? this.close() : this.open();
            }
        });
    }
    
    // ============================
    // 3. COMPANY BRANCH SELECT
    // ============================
    Alpine.data('companyBranchSelect', () => ({
        showDropdown: false,
        searchQuery: '',
        selectedCompanies: [],
        selectedBranches: [],
        expandedCompanies: [],
        
        init() {
            // Initialize from existing data if available
            if (this.$el.dataset.selectedCompanies) {
                try {
                    this.selectedCompanies = JSON.parse(this.$el.dataset.selectedCompanies);
                } catch (e) {}
            }
            if (this.$el.dataset.selectedBranches) {
                try {
                    this.selectedBranches = JSON.parse(this.$el.dataset.selectedBranches);
                } catch (e) {}
            }
            
            // Close on escape
            this.$el.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.showDropdown) {
                    this.closeDropdown();
                }
            });
        },
        
        toggleDropdown() {
            this.showDropdown = !this.showDropdown;
        },
        
        closeDropdown() {
            this.showDropdown = false;
        },
        
        toggleCompany(companyId) {
            const index = this.selectedCompanies.indexOf(companyId);
            if (index > -1) {
                this.selectedCompanies.splice(index, 1);
                // Remove all branches of this company
                this.selectedBranches = this.selectedBranches.filter(b => !b.startsWith(companyId + '-'));
            } else {
                this.selectedCompanies.push(companyId);
            }
            this.updateFilters();
        },
        
        toggleBranch(companyId, branchId) {
            const branchKey = companyId + '-' + branchId;
            const index = this.selectedBranches.indexOf(branchKey);
            if (index > -1) {
                this.selectedBranches.splice(index, 1);
            } else {
                this.selectedBranches.push(branchKey);
                // Also select the company if not already selected
                if (!this.selectedCompanies.includes(companyId)) {
                    this.selectedCompanies.push(companyId);
                }
            }
            this.updateFilters();
        },
        
        isCompanySelected(companyId) {
            return this.selectedCompanies.includes(companyId);
        },
        
        isBranchSelected(companyId, branchId) {
            return this.selectedBranches.includes(companyId + '-' + branchId);
        },
        
        matchesSearch(text) {
            if (!this.searchQuery) return true;
            return text.toLowerCase().includes(this.searchQuery.toLowerCase());
        },
        
        getCompactLabel() {
            if (this.selectedCompanies.length === 0) {
                return 'Alle Unternehmen';
            } else if (this.selectedCompanies.length === 1) {
                const companyEl = document.querySelector(`[data-company-id="${this.selectedCompanies[0]}"]`);
                return companyEl ? companyEl.textContent.trim() : 'Ausgewählt';
            } else {
                return `${this.selectedCompanies.length} Unternehmen`;
            }
        },
        
        updateFilters() {
            // Emit both Alpine and Livewire events
            this.$dispatch('filters-updated', {
                companies: this.selectedCompanies,
                branches: this.selectedBranches
            });
            
            if (window.Livewire) {
                Livewire.emit('filtersUpdated', {
                    companies: this.selectedCompanies,
                    branches: this.selectedBranches
                });
            }
        }
    }));
    
    // ============================
    // 4. DATE FILTER DROPDOWN
    // ============================
    Alpine.data('dateFilterDropdownEnhanced', () => ({
        showDateFilter: false,
        datePreset: 'today',
        customStartDate: '',
        customEndDate: '',
        
        init() {
            // Set default dates
            const today = new Date().toISOString().split('T')[0];
            this.customStartDate = today;
            this.customEndDate = today;
            
            // Initialize from data attributes if available
            if (this.$el.dataset.datePreset) {
                this.datePreset = this.$el.dataset.datePreset;
            }
        },
        
        toggleDropdown() {
            this.showDateFilter = !this.showDateFilter;
        },
        
        closeDropdown() {
            this.showDateFilter = false;
        },
        
        getDateLabel() {
            const labels = {
                'today': 'Heute',
                'yesterday': 'Gestern',
                'last7days': 'Letzte 7 Tage',
                'last30days': 'Letzte 30 Tage',
                'thisMonth': 'Dieser Monat',
                'lastMonth': 'Letzter Monat',
                'custom': 'Benutzerdefiniert'
            };
            return labels[this.datePreset] || 'Zeitraum wählen';
        },
        
        selectPreset(preset) {
            this.datePreset = preset;
            this.updateDateFilter();
            if (preset !== 'custom') {
                this.closeDropdown();
            }
        },
        
        updateDateFilter() {
            const dates = this.calculateDates();
            
            // Emit both Alpine and Livewire events
            this.$dispatch('date-filter-updated', {
                preset: this.datePreset,
                startDate: dates.start,
                endDate: dates.end
            });
            
            if (window.Livewire) {
                Livewire.emit('dateFilterUpdated', {
                    preset: this.datePreset,
                    startDate: dates.start,
                    endDate: dates.end
                });
            }
        },
        
        calculateDates() {
            const today = new Date();
            let start, end;
            
            switch(this.datePreset) {
                case 'today':
                    start = end = today.toISOString().split('T')[0];
                    break;
                case 'yesterday':
                    const yesterday = new Date(today);
                    yesterday.setDate(yesterday.getDate() - 1);
                    start = end = yesterday.toISOString().split('T')[0];
                    break;
                case 'last7days':
                    const week = new Date(today);
                    week.setDate(week.getDate() - 7);
                    start = week.toISOString().split('T')[0];
                    end = today.toISOString().split('T')[0];
                    break;
                case 'last30days':
                    const month = new Date(today);
                    month.setDate(month.getDate() - 30);
                    start = month.toISOString().split('T')[0];
                    end = today.toISOString().split('T')[0];
                    break;
                case 'thisMonth':
                    start = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                    end = today.toISOString().split('T')[0];
                    break;
                case 'lastMonth':
                    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                    start = lastMonth.toISOString().split('T')[0];
                    end = new Date(today.getFullYear(), today.getMonth(), 0).toISOString().split('T')[0];
                    break;
                case 'custom':
                    start = this.customStartDate;
                    end = this.customEndDate;
                    break;
                default:
                    start = end = today.toISOString().split('T')[0];
            }
            
            return { start, end };
        }
    }));
    
    // ============================
    // 5. SMART DROPDOWN (Enhanced)
    // ============================
    Alpine.data('smartDropdown', () => ({
        open: false,
        position: 'bottom',
        
        init() {
            this.calculatePosition();
            
            // Recalculate on window resize
            window.addEventListener('resize', () => {
                if (this.open) {
                    this.calculatePosition();
                }
            });
        },
        
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.calculatePosition();
            }
        },
        
        close() {
            this.open = false;
        },
        
        calculatePosition() {
            const rect = this.$el.getBoundingClientRect();
            const viewportHeight = window.innerHeight;
            
            // If not enough space below, position above
            if (rect.bottom + 200 > viewportHeight && rect.top > 200) {
                this.position = 'top';
            } else {
                this.position = 'bottom';
            }
        }
    }));
    
    // ============================
    // 6. SIDEBAR TOGGLE
    // ============================
    Alpine.data('sidebarToggle', () => ({
        sidebarOpen: false,
        
        init() {
            // Sync with store
            this.sidebarOpen = Alpine.store('sidebar').isOpen;
            
            // Watch for store changes
            this.$watch('$store.sidebar.isOpen', value => {
                this.sidebarOpen = value;
            });
        },
        
        toggle() {
            Alpine.store('sidebar').toggle();
        }
    }));
    
    // ============================
    // GLOBAL HELPER FUNCTIONS
    // ============================
    
    // These work with Alpine's context when called from x-on:click
    window.toggleDropdown = function() {
        if (this && typeof this.toggle === 'function') {
            this.toggle();
        } else if (this && this.open !== undefined) {
            this.open = !this.open;
        }
    };
    
    window.closeDropdown = function() {
        if (this && typeof this.close === 'function') {
            this.close();
        } else if (this && this.open !== undefined) {
            this.open = false;
        }
    };
    
    window.openDropdown = function() {
        if (this && this.open !== undefined) {
            this.open = true;
        }
    };
    
    // ============================
    // DROPDOWNS STORE (for closing all)
    // ============================
    Alpine.store('dropdowns', {
        closeAll() {
            // Close all Alpine dropdowns
            document.querySelectorAll('[x-data]').forEach(el => {
                if (el._x_dataStack && el._x_dataStack[0]) {
                    const data = el._x_dataStack[0];
                    if ('open' in data) {
                        data.open = false;
                    }
                    if ('showDropdown' in data) {
                        data.showDropdown = false;
                    }
                    if ('showDateFilter' in data) {
                        data.showDateFilter = false;
                    }
                }
            });
        }
    });
    
    console.log('[Alpine Consolidated] All components registered successfully');
});

// ============================
// DOM READY ENHANCEMENTS
// ============================
document.addEventListener('DOMContentLoaded', () => {
    console.log('[Alpine Consolidated] Applying DOM enhancements...');
    
    // Close dropdowns on outside click
    document.addEventListener('click', (e) => {
        // If clicking outside any dropdown
        if (!e.target.closest('[x-data*="dropdown"], [x-data*="Dropdown"]')) {
            Alpine.store('dropdowns').closeAll();
        }
    });
    
    // Keyboard navigation
    document.addEventListener('keydown', (e) => {
        // Close all dropdowns on Escape
        if (e.key === 'Escape') {
            Alpine.store('dropdowns').closeAll();
        }
    });
    
    console.log('[Alpine Consolidated] DOM enhancements applied');
});