{{-- Modern Call Resource List View with Enhanced UX --}}
<x-filament-panels::page>
    {{-- Real-time updates indicator --}}
    <div class="fixed top-4 right-4 z-50" x-data="{ show: false }" x-show="show" x-transition>
        <div class="bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2">
            <svg class="w-4 h-4 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="3"/>
            </svg>
            <span class="text-sm font-medium">Live Updates Active</span>
        </div>
    </div>

    {{-- Modern Header with Actions --}}
    <div class="mb-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-enterprise-primary">
                    Anrufe Management
                </h1>
                <p class="text-enterprise-secondary mt-1">
                    Überwachen und verwalten Sie alle eingehenden Anrufe in Echtzeit
                </p>
            </div>
            
            {{-- Quick Actions --}}
            <div class="flex items-center space-x-3">
                <div class="flex items-center space-x-2 bg-white rounded-xl px-4 py-2 border border-enterprise-light">
                    <div class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></div>
                    <span class="text-sm text-enterprise-secondary">
                        <span class="font-semibold" id="live-call-count">0</span> aktive Anrufe
                    </span>
                </div>
                
                {{ $this->fetchCallsAction }}
                
                <button class="call-action-btn bg-primary-500 text-white px-4 py-2" 
                        onclick="toggleRealTimeUpdates()">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Live Updates
                </button>
            </div>
        </div>
    </div>

    {{-- Call Statistics Cards --}}
    <div class="call-metrics-grid mb-8">
        <div class="metric-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-enterprise-muted">Heute</p>
                    <p class="metric-value" id="calls-today">{{ $this->getCachedTodayCount() }}</p>
                    <p class="text-xs text-green-600 flex items-center mt-1">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7 14l3-3 3 3 5-5-1.5-1.5L12 12l-3-3-3 3z"/>
                        </svg>
                        +12% seit gestern
                    </p>
                </div>
                <div class="w-12 h-12 bg-primary-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="metric-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-enterprise-muted">Terminquote</p>
                    <p class="metric-value">{{ $this->getConversionRate() }}%</p>
                    <p class="text-xs text-green-600 flex items-center mt-1">
                        <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M7 14l3-3 3 3 5-5-1.5-1.5L12 12l-3-3-3 3z"/>
                        </svg>
                        +5% diese Woche
                    </p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="metric-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-enterprise-muted">Avg. Dauer</p>
                    <p class="metric-value">{{ $this->getAverageDuration() }}</p>
                    <p class="text-xs text-enterprise-muted mt-1">Durchschnitt</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="metric-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-enterprise-muted">Sentiment</p>
                    <p class="metric-value text-green-600">{{ $this->getPositiveSentimentRate() }}%</p>
                    <p class="text-xs text-green-600 mt-1">Positive Gespräche</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- Smart Filters --}}
    <div class="call-filters mb-6">
        <div class="flex flex-wrap gap-3">
            <div class="filter-chip active" data-filter="all">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Alle Anrufe
            </div>
            <div class="filter-chip" data-filter="today">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Heute
            </div>
            <div class="filter-chip" data-filter="appointments">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Mit Termin
            </div>
            <div class="filter-chip" data-filter="positive">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Positiv
            </div>
            <div class="filter-chip" data-filter="long">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Lange Gespräche
            </div>
        </div>
    </div>

    {{-- Header Widgets --}}
    @if($this->hasHeaderWidgets())
        <div class="mb-8">
            <x-filament-widgets::widgets
                :widgets="$this->getHeaderWidgets()"
                :columns="$this->getHeaderWidgetsColumns()"
            />
        </div>
    @endif

    {{-- Main Content Area --}}
    <div class="bg-white rounded-2xl border border-enterprise-light shadow-soft">
        {{-- Search and Tabs --}}
        <div class="p-6 border-b border-enterprise-light">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                {{-- Search --}}
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text" 
                           class="block w-full pl-10 pr-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-primary-500 focus:border-transparent"
                           placeholder="Durchsuche Anrufe, Kunden, Nummern..."
                           x-model="search">
                </div>

                {{-- Quick Stats --}}
                <div class="flex items-center space-x-4 text-sm text-enterprise-secondary">
                    <span class="flex items-center">
                        <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                        <span id="filtered-count">{{ $this->getTable()->getRecords()->count() }}</span> Anrufe
                    </span>
                    <span class="text-enterprise-muted">|</span>
                    <span>Letzte Aktualisierung: <span id="last-updated">vor wenigen Sekunden</span></span>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="mt-6">
                <div class="border-b border-enterprise-light">
                    <nav class="-mb-px flex space-x-8">
                        @foreach($this->getTabs() as $tabKey => $tab)
                            <button class="tab-button {{ $loop->first ? 'active' : '' }}" 
                                    data-tab="{{ $tabKey }}">
                                @if($tab->getIcon())
                                    <x-filament::icon :icon="$tab->getIcon()" class="w-4 h-4 mr-2" />
                                @endif
                                {{ $tab->getLabel() }}
                                @if($tab->getBadge())
                                    <span class="ml-2 bg-enterprise-border-light text-enterprise-text-secondary px-2 py-1 rounded-full text-xs">
                                        {{ $tab->getBadge() }}
                                    </span>
                                @endif
                            </button>
                        @endforeach
                    </nav>
                </div>
            </div>
        </div>

        {{-- Table Container --}}
        <div class="calls-container">
            {{ $this->table }}
        </div>
    </div>

    {{-- Loading State --}}
    <div class="loading-overlay hidden fixed inset-0 bg-black/20 backdrop-blur-sm z-50 flex items-center justify-center"
         x-show="loading" 
         x-transition.opacity>
        <div class="bg-white rounded-2xl p-8 shadow-2xl">
            <div class="flex items-center space-x-4">
                <div class="w-8 h-8 border-4 border-primary-200 border-t-primary-600 rounded-full animate-spin"></div>
                <span class="text-lg font-medium text-enterprise-primary">Lade Anrufe...</span>
            </div>
        </div>
    </div>

    {{-- Real-time Updates Script --}}
    <script>
        let realTimeEnabled = {{ $realTimeEnabled ? 'true' : 'false' }};
        let autoRefreshInterval = {{ $autoRefreshInterval ?? 30000 }};
        let refreshTimer;

        function toggleRealTimeUpdates() {
            realTimeEnabled = !realTimeEnabled;
            
            if (realTimeEnabled) {
                startRealTimeUpdates();
                showNotification('Real-time Updates aktiviert', 'success');
            } else {
                stopRealTimeUpdates();
                showNotification('Real-time Updates deaktiviert', 'info');
            }
        }

        function startRealTimeUpdates() {
            refreshTimer = setInterval(() => {
                updateLastRefreshTime();
                // Trigger Livewire refresh or WebSocket update
                @this.refresh();
            }, autoRefreshInterval);
        }

        function stopRealTimeUpdates() {
            if (refreshTimer) {
                clearInterval(refreshTimer);
            }
        }

        function updateLastRefreshTime() {
            const lastUpdated = document.getElementById('last-updated');
            if (lastUpdated) {
                lastUpdated.textContent = 'vor wenigen Sekunden';
            }
        }

        function showNotification(message, type = 'info') {
            // Implementation for notification system
            console.log(`[${type.toUpperCase()}] ${message}`);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            if (realTimeEnabled) {
                startRealTimeUpdates();
            }
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            stopRealTimeUpdates();
        });
    </script>

    {{-- Additional Styles --}}
    <style>
        .tab-button {
            @apply flex items-center px-1 py-4 border-b-2 border-transparent text-sm font-medium text-enterprise-secondary 
                   hover:text-enterprise-primary hover:border-enterprise-border-default transition-all duration-200;
        }
        
        .tab-button.active {
            @apply text-primary-600 border-primary-600;
        }
        
        .tab-button:hover {
            @apply text-primary-500;
        }
    </style>
</x-filament-panels::page>