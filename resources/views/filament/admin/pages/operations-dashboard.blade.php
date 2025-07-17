<x-filament-panels::page>
    {{-- Compact Filter Bar --}}
    <div class="mb-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
            <div class="flex flex-wrap items-center gap-3">
                {{-- Company & Branch Filter --}}
                <div class="flex-1 min-w-[200px]" x-data="companyBranchSelect()">
                    <div class="relative">
                        <button 
                            @click="showDropdown = !showDropdown"
                            @click.away="showDropdown = false"
                            type="button"
                            class="relative w-full px-3 py-2 text-left bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md hover:border-gray-400 dark:hover:border-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-150"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 min-w-0">
                                    <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                    </svg>
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-200 truncate" x-text="getCompactLabel()"></span>
                                </div>
                                <svg class="w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0" :class="{'rotate-180': showDropdown}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </div>
                        </button>
                    
                        {{-- Modern Dropdown with better design --}}
                        <div 
                            x-show="showDropdown"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="absolute z-50 mt-2 w-full bg-white dark:bg-gray-800 shadow-xl rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
                            style="max-height: 420px"
                        >
                            {{-- Search Header --}}
                            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                                <div class="relative">
                                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                    </svg>
                                    <input 
                                        type="text"
                                        x-model="searchQuery"
                                        placeholder="Suche Unternehmen oder Filiale..."
                                        class="w-full pl-10 pr-4 py-2 text-sm bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-colors"
                                        @click.stop
                                    >
                                </div>
                            </div>
                        
                            {{-- Companies and Branches List with Modern Design --}}
                            <div class="overflow-y-auto" style="max-height: 320px">
                                @php
                                    $companies = \App\Models\Company::where('is_active', true)
                                        ->with(['branches' => fn($q) => $q->where('active', true)])
                                        ->orderBy('name')
                                        ->get();
                                @endphp
                                
                                @foreach($companies as $company)
                                    <div x-show="matchesSearch('{{ $company->name }}')" class="border-b border-gray-100 dark:border-gray-700 last:border-0">
                                        {{-- Company Header with Modern Style --}}
                                        <div 
                                            class="relative flex items-center px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer transition-colors duration-150"
                                            @click="toggleCompany({{ $company->id }})"
                                        >
                                            <div class="flex-1 flex items-center gap-3">
                                                <div class="flex-shrink-0">
                                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                                    </svg>
                                                </div>
                                                <div class="min-w-0">
                                                    <span data-company-id="{{ $company->id }}" class="text-sm font-medium text-gray-900 dark:text-white">
                                                        {{ $company->name }}
                                                    </span>
                                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ $company->branches->count() }} {{ $company->branches->count() === 1 ? 'Filiale' : 'Filialen' }}
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button 
                                                    @click.stop="expandedCompanies.includes({{ $company->id }}) ? expandedCompanies.splice(expandedCompanies.indexOf({{ $company->id }}), 1) : expandedCompanies.push({{ $company->id }})"
                                                    class="p-1 hover:bg-gray-200 dark:hover:bg-gray-600 rounded transition-colors"
                                                >
                                                    <svg class="w-4 h-4 text-gray-400 transition-transform duration-200" 
                                                         :class="{'rotate-180': expandedCompanies.includes({{ $company->id }})}" 
                                                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                    </svg>
                                                </button>
                                                <label class="relative inline-flex items-center cursor-pointer">
                                                    <input 
                                                        type="checkbox"
                                                        :checked="isCompanySelected({{ $company->id }})"
                                                        @change="toggleCompany({{ $company->id }})"
                                                        class="sr-only peer"
                                                    >
                                                    <div class="w-9 h-5 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-primary-300 dark:peer-focus:ring-primary-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-gray-600 peer-checked:bg-primary-600"></div>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        {{-- Branches with Indented Design --}}
                                        @if($company->branches->count() > 0)
                                            <div 
                                                x-show="expandedCompanies.includes({{ $company->id }})"
                                                x-transition:enter="transition ease-out duration-200"
                                                x-transition:enter-start="opacity-0"
                                                x-transition:enter-end="opacity-100"
                                                x-transition:leave="transition ease-in duration-150"
                                                x-transition:leave-start="opacity-100"
                                                x-transition:leave-end="opacity-0"
                                                class="bg-gray-50 dark:bg-gray-900/50"
                                            >
                                                @foreach($company->branches as $branch)
                                                    <div 
                                                        x-show="matchesSearch('{{ $branch->name }}')"
                                                        class="flex items-center pl-12 pr-4 py-2.5 hover:bg-gray-100 dark:hover:bg-gray-800/50 cursor-pointer transition-colors duration-150"
                                                        @click="toggleBranch({{ $company->id }}, '{{ $branch->id }}')"
                                                    >
                                                        <div class="flex-1 flex items-center gap-3">
                                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                            </svg>
                                                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $branch->name }}</span>
                                                        </div>
                                                        <input 
                                                            type="checkbox"
                                                            :checked="isBranchSelected('{{ $branch->id }}')"
                                                            @click.stop="toggleBranch({{ $company->id }}, '{{ $branch->id }}')"
                                                            class="h-4 w-4 text-primary-600 rounded border-gray-300 focus:ring-primary-500 focus:ring-2"
                                                        >
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                                
                                {{-- No Results Message --}}
                                <div x-show="!hasSearchResults()" class="px-4 py-8 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Keine Ergebnisse gefunden</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                {{-- Date Filter Dropdown --}}
                <div class="relative" x-data="{ showDateFilter: false }">
                    <button 
                        @click="showDateFilter = !showDateFilter"
                        @click.away="showDateFilter = false"
                        type="button"
                        class="px-3 py-2 text-sm font-medium bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-md hover:border-gray-400 dark:hover:border-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-150 flex items-center gap-2"
                    >
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-gray-700 dark:text-gray-200">{{ $this->getDateRangeLabel() }}</span>
                        <svg class="w-4 h-4 text-gray-400 -mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    
                    {{-- Date Options Dropdown --}}
                    <div 
                        x-show="showDateFilter"
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        class="absolute z-10 mt-1 w-48 bg-white dark:bg-gray-800 shadow-lg rounded-md border border-gray-200 dark:border-gray-700 py-1"
                    >
                        @foreach(['today' => 'Heute', 'yesterday' => 'Gestern', 'last7days' => 'Letzte 7 Tage', 'last30days' => 'Letzte 30 Tage', 'thisMonth' => 'Dieser Monat', 'lastMonth' => 'Letzter Monat', 'thisYear' => 'Dieses Jahr'] as $key => $label)
                            <button 
                                wire:click="setDateFilter('{{ $key }}')"
                                @click="showDateFilter = false"
                                class="w-full px-4 py-2 text-sm text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors {{ $dateFilter === $key ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-medium' : 'text-gray-700 dark:text-gray-300' }}"
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                        <div class="border-t border-gray-200 dark:border-gray-700 my-1"></div>
                        <button 
                            wire:click="setDateFilter('custom')"
                            @click="showDateFilter = false"
                            class="w-full px-4 py-2 text-sm text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors flex items-center gap-2 {{ $dateFilter === 'custom' ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-medium' : 'text-gray-700 dark:text-gray-300' }}"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Benutzerdefiniert
                        </button>
                    </div>
                </div>
                
                {{-- Action Buttons --}}
                <div class="flex items-center gap-2 ml-auto">
                    <button 
                        wire:click="exportData"
                        class="px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors flex items-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span class="hidden sm:inline">Export</span>
                    </button>
                    
                    <button 
                        wire:click="resetAllFilters"
                        class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                        title="Filter zurücksetzen"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        {{-- Custom Date Picker Modal (shown when custom is selected) --}}
        @if($showDatePicker)
            <div class="mt-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Von:</label>
                        <input type="date" 
                               wire:model="startDate"
                               class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Bis:</label>
                        <input type="date" 
                               wire:model="endDate"
                               class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                    </div>
                    <div class="flex items-center gap-2 ml-auto">
                        <button 
                            wire:click="applyCustomDateRange"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 transition-colors duration-200"
                        >
                            Anwenden
                        </button>
                        <button 
                            wire:click="$set('showDatePicker', false)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700 transition-colors duration-200"
                        >
                            Abbrechen
                        </button>
                    </div>
                </div>
            </div>
        @endif
        
    </div>
    
    {{-- Widgets --}}
    <x-filament-widgets::widgets
        :columns="$this->getColumns()"
        :data="[
            'filters' => [
                'dateFilter' => $this->dateFilter,
                'branchFilter' => empty($this->selectedBranches) ? 'all' : implode(',', $this->selectedBranches),
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ]
        ]"
        :widgets="$this->getVisibleWidgets()"
    />
    
    
    @push('scripts')
    <script>
        // Global fallbacks for all required functions
        window.hasSearchResults = function() {
            console.log('[Global Fallback] hasSearchResults called');
            return true; // Default behavior
        };
        
        window.isCompanySelected = function(companyId) {
            console.log('[Global Fallback] isCompanySelected called for:', companyId);
            return false; // Default behavior
        };
        
        window.isBranchSelected = function(branchId) {
            console.log('[Global Fallback] isBranchSelected called for:', branchId);
            return false; // Default behavior
        };
        
        window.matchesSearch = function(text) {
            console.log('[Global Fallback] matchesSearch called for:', text);
            return true; // Show all by default
        };
        
        window.toggleCompany = function(companyId) {
            console.log('[Global Fallback] toggleCompany called for:', companyId);
        };
        
        window.toggleBranch = function(companyId, branchId) {
            console.log('[Global Fallback] toggleBranch called for:', companyId, branchId);
        };
        
        function companyBranchSelect() {
            return {
                showDropdown: false,
                searchQuery: '',
                selectedCompany: @entangle('selectedCompany'),
                selectedBranches: @entangle('selectedBranches'),
                expandedCompanies: [],
                
                init() {
                    // Initialize with current selections
                    if (this.selectedCompany) {
                        this.expandedCompanies.push(this.selectedCompany);
                    }
                    
                    // Ensure all methods are available in the component scope
                    // This helps with Alpine's reactivity system
                    this.isCompanySelected = this.isCompanySelected.bind(this);
                    this.isBranchSelected = this.isBranchSelected.bind(this);
                    this.hasSearchResults = this.hasSearchResults.bind(this);
                    this.matchesSearch = this.matchesSearch.bind(this);
                },
                
                matchesSearch(text) {
                    if (!this.searchQuery) return true;
                    return text.toLowerCase().includes(this.searchQuery.toLowerCase());
                },
                
                toggleCompany(companyId) {
                    if (this.selectedCompany === companyId) {
                        // Deselect company and all its branches
                        this.selectedCompany = null;
                        this.selectedBranches = [];
                    } else {
                        // Select new company, reset branches
                        this.selectedCompany = companyId;
                        this.selectedBranches = [];
                        
                        // Expand to show branches
                        if (!this.expandedCompanies.includes(companyId)) {
                            this.expandedCompanies.push(companyId);
                        }
                    }
                    
                    // Update backend
                    @this.set('selectedCompany', this.selectedCompany);
                    @this.set('selectedBranches', this.selectedBranches);
                    @this.refresh();
                },
                
                toggleBranch(companyId, branchId) {
                    // Auto-select company if not selected
                    if (this.selectedCompany !== companyId) {
                        this.selectedCompany = companyId;
                    }
                    
                    // Toggle branch selection
                    const index = this.selectedBranches.indexOf(branchId);
                    if (index > -1) {
                        this.selectedBranches.splice(index, 1);
                    } else {
                        this.selectedBranches.push(branchId);
                    }
                    
                    // Update backend
                    @this.set('selectedCompany', this.selectedCompany);
                    @this.set('selectedBranches', this.selectedBranches);
                    @this.refresh();
                },
                
                isCompanySelected(companyId) {
                    return this.selectedCompany === companyId;
                },
                
                isBranchSelected(branchId) {
                    return this.selectedBranches.includes(branchId);
                },
                
                getSelectionLabel() {
                    if (!this.selectedCompany) {
                        return 'Alle Unternehmen';
                    }
                    
                    // Get company name
                    const companyEl = document.querySelector(`[data-company-id="${this.selectedCompany}"]`);
                    return companyEl ? companyEl.textContent.trim() : 'Unternehmen';
                },
                
                getSelectionDetails() {
                    if (!this.selectedCompany) {
                        return 'Zeige Daten aller Unternehmen';
                    }
                    
                    if (this.selectedBranches.length === 0) {
                        return 'Alle Filialen ausgewählt';
                    } else if (this.selectedBranches.length === 1) {
                        return '1 Filiale ausgewählt';
                    } else {
                        return `${this.selectedBranches.length} Filialen ausgewählt`;
                    }
                },
                
                resetFilters() {
                    this.selectedCompany = null;
                    this.selectedBranches = [];
                    this.searchQuery = '';
                    @this.set('selectedCompany', null);
                    @this.set('selectedBranches', []);
                    @this.refresh();
                },
                
                getCompactLabel() {
                    if (!this.selectedCompany) {
                        return 'Alle Unternehmen';
                    }
                    
                    // Get company name
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
                        return true; // No search, show all
                    }
                    
                    // Check if any company or branch matches the search
                    const companies = @json($this->getCompaniesWithBranches());
                    
                    if (!companies || !Array.isArray(companies)) {
                        return true; // Show all if no data
                    }
                    
                    for (const company of companies) {
                        if (company && company.name && this.matchesSearch(company.name)) {
                            return true;
                        }
                        
                        if (company && company.branches && Array.isArray(company.branches)) {
                            for (const branch of company.branches) {
                                if (branch && branch.name && this.matchesSearch(branch.name)) {
                                    return true;
                                }
                            }
                        }
                    }
                    
                    return false;
                },
                
                getCurrentDateRange() {
                    // This would be populated from the backend
                    return 'Letzte 7 Tage';
                }
            }
        }
    </script>
    @endpush

</x-filament-panels::page>