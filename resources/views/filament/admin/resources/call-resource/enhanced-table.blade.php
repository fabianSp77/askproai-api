{{-- Enhanced Call Resource Table with Modern UX --}}
<div class="modern-call-table-container" x-data="modernCallTable()">
    {{-- Loading State --}}
    <div x-show="loading" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center">
        <div class="bg-white rounded-2xl p-8 shadow-2xl">
            <div class="flex items-center space-x-4">
                <div class="w-8 h-8 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin"></div>
                <span class="text-lg font-medium text-enterprise-primary">Lade Anrufe...</span>
            </div>
        </div>
    </div>

    {{-- Enhanced Filters Row --}}
    <div class="bg-white rounded-2xl border border-enterprise-light shadow-soft mb-6 p-6">
        <div class="flex flex-col lg:flex-row lg:items-center gap-4">
            {{-- Search Input --}}
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" 
                       x-model="searchQuery"
                       @input.debounce.300ms="performSearch()"
                       class="w-full pl-12 pr-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary-500 focus:border-transparent text-sm"
                       placeholder="Durchsuche Anrufe, Kunden, Telefonnummern...">
                
                {{-- Clear Search --}}
                <button x-show="searchQuery.length > 0"
                        @click="clearSearch()"
                        class="absolute inset-y-0 right-0 pr-4 flex items-center">
                    <svg class="h-4 w-4 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Quick Filter Chips --}}
            <div class="flex flex-wrap gap-2">
                <template x-for="filter in quickFilters" :key="filter.key">
                    <button @click="toggleFilter(filter.key)"
                            :class="activeFilters.includes(filter.key) ? 'filter-chip active' : 'filter-chip'"
                            class="inline-flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="filter.icon"/>
                        </svg>
                        <span x-text="filter.label"></span>
                    </button>
                </template>
            </div>

            {{-- Advanced Filters Toggle --}}
            <button @click="showAdvancedFilters = !showAdvancedFilters"
                    :class="showAdvancedFilters ? 'bg-primary-100 text-primary-800' : 'bg-gray-100 text-gray-700'"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4"/>
                </svg>
                Erweiterte Filter
            </button>
        </div>

        {{-- Advanced Filters Panel --}}
        <div x-show="showAdvancedFilters" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="mt-4 pt-4 border-t border-gray-200">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Date Range --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Zeitraum</label>
                    <select x-model="dateRange" 
                            @change="applyFilters()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Alle</option>
                        <option value="today">Heute</option>
                        <option value="yesterday">Gestern</option>
                        <option value="this_week">Diese Woche</option>
                        <option value="last_week">Letzte Woche</option>
                        <option value="this_month">Dieser Monat</option>
                    </select>
                </div>

                {{-- Call Status --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                    <select x-model="callStatus" 
                            @change="applyFilters()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Alle Status</option>
                        <option value="completed">Beendet</option>
                        <option value="ongoing">Laufend</option>
                        <option value="error">Fehler</option>
                    </select>
                </div>

                {{-- Duration Range --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dauer</label>
                    <select x-model="durationRange" 
                            @change="applyFilters()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Alle Dauern</option>
                        <option value="short">< 1 Min</option>
                        <option value="medium">1-5 Min</option>
                        <option value="long">> 5 Min</option>
                    </select>
                </div>

                {{-- Sentiment --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stimmung</label>
                    <select x-model="sentiment" 
                            @change="applyFilters()"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">Alle</option>
                        <option value="positive">Positiv</option>
                        <option value="neutral">Neutral</option>
                        <option value="negative">Negativ</option>
                    </select>
                </div>
            </div>

            {{-- Filter Actions --}}
            <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">
                <div class="flex items-center space-x-2 text-sm text-gray-600">
                    <span>Aktive Filter:</span>
                    <span x-text="getActiveFilterCount()" class="font-semibold"></span>
                </div>
                
                <div class="flex space-x-2">
                    <button @click="clearAllFilters()" 
                            class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800 transition-colors">
                        Alle zurücksetzen
                    </button>
                    <button @click="saveFilterPreset()" 
                            class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm font-medium hover:bg-primary-600 transition-colors">
                        Filter speichern
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Results Summary --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-4">
            <span class="text-sm text-enterprise-secondary">
                <span class="font-semibold" x-text="totalResults"></span> Anrufe gefunden
            </span>
            
            {{-- Live Update Indicator --}}
            <div x-show="realTimeEnabled" class="flex items-center space-x-2">
                <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                <span class="text-xs text-green-600 font-medium">Live Updates</span>
            </div>
        </div>

        {{-- View Options --}}
        <div class="flex items-center space-x-2">
            {{-- Density Toggle --}}
            <div class="flex bg-gray-100 rounded-lg p-1">
                <button @click="viewDensity = 'compact'"
                        :class="viewDensity === 'compact' ? 'bg-white shadow-sm' : ''"
                        class="px-3 py-1 text-xs font-medium rounded-md transition-all"
                        title="Kompakte Ansicht">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 4h18v2H3V4zm0 7h18v2H3v-2zm0 7h18v2H3v-2z"/>
                    </svg>
                </button>
                <button @click="viewDensity = 'comfortable'"
                        :class="viewDensity === 'comfortable' ? 'bg-white shadow-sm' : ''"
                        class="px-3 py-1 text-xs font-medium rounded-md transition-all"
                        title="Komfortable Ansicht">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 4h18v4H3V4zm0 8h18v4H3v-4zm0 8h18v4H3v-4z"/>
                    </svg>
                </button>
                <button @click="viewDensity = 'spacious'"
                        :class="viewDensity === 'spacious' ? 'bg-white shadow-sm' : ''"
                        class="px-3 py-1 text-xs font-medium rounded-md transition-all"
                        title="Geräumige Ansicht">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M3 4h18v6H3V4zm0 10h18v6H3v-6z"/>
                    </svg>
                </button>
            </div>

            {{-- Export Button --}}
            <button @click="exportCalls()" 
                    class="call-action-btn bg-gray-500 text-white">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Export
            </button>
        </div>
    </div>

    {{-- Enhanced Table Container --}}
    <div class="bg-white rounded-2xl border border-enterprise-light shadow-soft overflow-hidden">
        {{-- Table Header --}}
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                {{-- Bulk Actions --}}
                <div class="flex items-center space-x-4" x-show="selectedCalls.length > 0">
                    <span class="text-sm text-gray-600">
                        <span x-text="selectedCalls.length"></span> ausgewählt
                    </span>
                    
                    <div class="flex space-x-2">
                        <button @click="bulkExport()" 
                                class="px-3 py-1 bg-blue-500 text-white rounded-lg text-xs font-medium">
                            Exportieren
                        </button>
                        <button @click="bulkAddNote()" 
                                class="px-3 py-1 bg-green-500 text-white rounded-lg text-xs font-medium">
                            Notiz hinzufügen
                        </button>
                        <button @click="clearSelection()" 
                                class="px-3 py-1 bg-gray-500 text-white rounded-lg text-xs font-medium">
                            Auswahl aufheben
                        </button>
                    </div>
                </div>

                {{-- Column Visibility Toggle --}}
                <div class="relative" x-show="selectedCalls.length === 0">
                    <button @click="showColumnMenu = !showColumnMenu"
                            class="p-2 text-gray-500 hover:text-gray-700 rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <div x-show="showColumnMenu" 
                         @click.away="showColumnMenu = false"
                         x-transition
                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-10">
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase tracking-wide border-b border-gray-200">
                            Spalten anzeigen
                        </div>
                        <template x-for="column in availableColumns" :key="column.key">
                            <label class="flex items-center px-3 py-2 hover:bg-gray-50 cursor-pointer">
                                <input type="checkbox" 
                                       :checked="visibleColumns.includes(column.key)"
                                       @change="toggleColumn(column.key)"
                                       class="mr-3 h-4 w-4 text-primary-600 border-gray-300 rounded">
                                <span class="text-sm" x-text="column.label"></span>
                            </label>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table Content --}}
        <div class="overflow-x-auto">
            {{ $this->table }}
        </div>

        {{-- Empty State --}}
        <div x-show="totalResults === 0 && !loading" 
             class="call-empty-state py-16">
            <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                      d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Keine Anrufe gefunden</h3>
            <p class="text-gray-500 max-w-md text-center">
                Keine Anrufe entsprechen den aktuellen Filterkriterien. 
                Versuchen Sie, die Filter anzupassen oder zu entfernen.
            </p>
            <button @click="clearAllFilters()" 
                    class="mt-4 call-action-btn bg-primary-500 text-white">
                Alle Filter zurücksetzen
            </button>
        </div>
    </div>

    {{-- Floating Action Button (Mobile) --}}
    <div class="fixed bottom-6 right-6 lg:hidden" x-show="!showAdvancedFilters">
        <button @click="showMobileActions = !showMobileActions"
                class="w-14 h-14 bg-primary-500 text-white rounded-full shadow-lg flex items-center justify-center hover:bg-primary-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
        </button>

        {{-- Mobile Action Menu --}}
        <div x-show="showMobileActions" 
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100"
             class="absolute bottom-16 right-0 bg-white rounded-2xl shadow-xl border border-gray-200 p-2 min-w-48">
            <button @click="performSearch(); showMobileActions = false" 
                    class="w-full text-left px-4 py-3 rounded-xl hover:bg-gray-50 flex items-center">
                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                Suchen
            </button>
            <button @click="showAdvancedFilters = true; showMobileActions = false" 
                    class="w-full text-left px-4 py-3 rounded-xl hover:bg-gray-50 flex items-center">
                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                </svg>
                Filter
            </button>
            <button @click="exportCalls(); showMobileActions = false" 
                    class="w-full text-left px-4 py-3 rounded-xl hover:bg-gray-50 flex items-center">
                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Exportieren
            </button>
        </div>
    </div>
</div>

<script>
function modernCallTable() {
    return {
        // State
        loading: false,
        searchQuery: '',
        showAdvancedFilters: false,
        showColumnMenu: false,
        showMobileActions: false,
        realTimeEnabled: true,
        totalResults: 0,
        selectedCalls: [],
        
        // Filters
        activeFilters: [],
        dateRange: '',
        callStatus: '',
        durationRange: '',
        sentiment: '',
        
        // View Settings
        viewDensity: 'comfortable',
        visibleColumns: ['time', 'customer', 'number', 'duration', 'status', 'actions'],
        
        // Quick Filters
        quickFilters: [
            { key: 'today', label: 'Heute', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
            { key: 'appointments', label: 'Mit Termin', icon: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
            { key: 'positive', label: 'Positiv', icon: 'M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
            { key: 'long', label: 'Lange Gespräche', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' }
        ],
        
        // Available Columns
        availableColumns: [
            { key: 'time', label: 'Zeit' },
            { key: 'customer', label: 'Kunde' },
            { key: 'number', label: 'Telefonnummer' },
            { key: 'duration', label: 'Dauer' },
            { key: 'status', label: 'Status' },
            { key: 'sentiment', label: 'Stimmung' },
            { key: 'conversion', label: 'Conversion' },
            { key: 'actions', label: 'Aktionen' }
        ],

        // Methods
        performSearch() {
            this.loading = true;
            // Simulate API call
            setTimeout(() => {
                console.log('Searching for:', this.searchQuery);
                this.loading = false;
            }, 500);
        },

        clearSearch() {
            this.searchQuery = '';
            this.performSearch();
        },

        toggleFilter(filterKey) {
            const index = this.activeFilters.indexOf(filterKey);
            if (index > -1) {
                this.activeFilters.splice(index, 1);
            } else {
                this.activeFilters.push(filterKey);
            }
            this.applyFilters();
        },

        applyFilters() {
            this.loading = true;
            console.log('Applying filters:', {
                active: this.activeFilters,
                dateRange: this.dateRange,
                callStatus: this.callStatus,
                durationRange: this.durationRange,
                sentiment: this.sentiment
            });
            
            // Simulate API call
            setTimeout(() => {
                this.loading = false;
            }, 300);
        },

        clearAllFilters() {
            this.activeFilters = [];
            this.dateRange = '';
            this.callStatus = '';
            this.durationRange = '';
            this.sentiment = '';
            this.searchQuery = '';
            this.applyFilters();
        },

        getActiveFilterCount() {
            let count = this.activeFilters.length;
            if (this.dateRange) count++;
            if (this.callStatus) count++;
            if (this.durationRange) count++;
            if (this.sentiment) count++;
            return count;
        },

        toggleColumn(columnKey) {
            const index = this.visibleColumns.indexOf(columnKey);
            if (index > -1) {
                this.visibleColumns.splice(index, 1);
            } else {
                this.visibleColumns.push(columnKey);
            }
        },

        exportCalls() {
            console.log('Exporting calls with current filters');
            // Implement export functionality
        },

        bulkExport() {
            console.log('Bulk exporting selected calls:', this.selectedCalls);
        },

        bulkAddNote() {
            console.log('Adding note to selected calls:', this.selectedCalls);
        },

        clearSelection() {
            this.selectedCalls = [];
        },

        saveFilterPreset() {
            const preset = {
                activeFilters: this.activeFilters,
                dateRange: this.dateRange,
                callStatus: this.callStatus,
                durationRange: this.durationRange,
                sentiment: this.sentiment
            };
            
            // Save to localStorage or backend
            localStorage.setItem('callFilterPreset', JSON.stringify(preset));
            console.log('Filter preset saved:', preset);
        }
    }
}
</script>