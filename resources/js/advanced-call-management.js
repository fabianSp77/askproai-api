/**
 * Advanced Call Management Interactions
 * Comprehensive agent efficiency toolkit for high-volume call centers
 * 
 * Features:
 * - Real-time call status updates
 * - Drag-and-drop priority queue
 * - Voice-to-text note taking
 * - Keyboard shortcuts for power users
 * - Smart search with autocomplete
 * - Customer timeline visualization
 * - Command palette (Cmd/Ctrl + K)
 * - Advanced filtering with saved presets
 */

class AdvancedCallManagement {
    constructor() {
        this.isInitialized = false;
        this.websocket = null;
        this.voiceRecognition = null;
        this.commandPalette = null;
        this.filterPresets = this.loadFilterPresets();
        this.shortcuts = new Map();
        this.dragState = {
            isDragging: false,
            draggedItem: null,
            dropZones: []
        };
        
        this.init();
    }

    init() {
        if (this.isInitialized) return;
        
        this.setupWebSocket();
        this.setupVoiceRecognition();
        this.setupKeyboardShortcuts();
        this.setupCommandPalette();
        this.setupDragAndDrop();
        this.setupSmartSearch();
        this.setupCustomerTimeline();
        this.setupFilterPresets();
        this.setupRealTimeUpdates();
        this.setupPerformanceOptimizations();
        
        this.isInitialized = true;
        console.log('🚀 Advanced Call Management initialized successfully');
    }

    /**
     * Real-time WebSocket Connection
     */
    setupWebSocket() {
        if (typeof window.Echo === 'undefined') {
            console.warn('Echo not available, falling back to polling');
            this.setupPolling();
            return;
        }

        this.websocket = window.Echo.channel('calls')
            .listen('CallCreated', (e) => this.handleCallCreated(e.call))
            .listen('CallUpdated', (e) => this.handleCallUpdated(e.call))
            .listen('CallStatusChanged', (e) => this.handleCallStatusChanged(e.call))
            .listen('QueueUpdated', (e) => this.handleQueueUpdated(e.queue));

        // Connection status monitoring
        window.Echo.connector.pusher.connection.bind('connected', () => {
            this.showNotification('Real-time updates connected', 'success');
        });

        window.Echo.connector.pusher.connection.bind('disconnected', () => {
            this.showNotification('Real-time connection lost', 'warning');
        });
    }

    setupPolling() {
        setInterval(() => {
            if (window.Livewire) {
                window.Livewire.emit('refreshCallData');
            }
        }, 15000); // Poll every 15 seconds as fallback
    }

    /**
     * Voice-to-Text Note Taking
     */
    setupVoiceRecognition() {
        if (!('webkitSpeechRecognition' in window) && !('SpeechRecognition' in window)) {
            console.warn('Speech recognition not supported');
            return;
        }

        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        this.voiceRecognition = new SpeechRecognition();
        
        this.voiceRecognition.continuous = true;
        this.voiceRecognition.interimResults = true;
        this.voiceRecognition.lang = 'de-DE';

        this.voiceRecognition.onresult = (event) => {
            let interimTranscript = '';
            let finalTranscript = '';

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const transcript = event.results[i][0].transcript;
                if (event.results[i].isFinal) {
                    finalTranscript += transcript;
                } else {
                    interimTranscript += transcript;
                }
            }

            this.updateVoiceNote(finalTranscript, interimTranscript);
        };

        this.voiceRecognition.onerror = (event) => {
            console.error('Speech recognition error:', event.error);
            this.showNotification(`Voice recognition error: ${event.error}`, 'error');
        };
    }

    /**
     * Keyboard Shortcuts for Power Users
     */
    setupKeyboardShortcuts() {
        const shortcuts = {
            // Global shortcuts
            'ctrl+k': () => this.openCommandPalette(),
            'cmd+k': () => this.openCommandPalette(),
            'ctrl+shift+r': () => this.refreshAllData(),
            'cmd+shift+r': () => this.refreshAllData(),
            'ctrl+shift+f': () => this.focusGlobalSearch(),
            'cmd+shift+f': () => this.focusGlobalSearch(),
            
            // Call management shortcuts
            'ctrl+n': () => this.createNewCall(),
            'cmd+n': () => this.createNewCall(),
            'ctrl+shift+n': () => this.startVoiceNote(),
            'cmd+shift+n': () => this.startVoiceNote(),
            'escape': () => this.closeModalsAndPalettes(),
            
            // Navigation shortcuts
            'j': () => this.navigateNext(),
            'k': () => this.navigatePrevious(),
            'enter': () => this.openSelectedCall(),
            'ctrl+1': () => this.switchToTab('active'),
            'ctrl+2': () => this.switchToTab('completed'),
            'ctrl+3': () => this.switchToTab('priority'),
            
            // Filter shortcuts
            'f': () => this.focusFilterBar(),
            'ctrl+shift+p': () => this.openFilterPresets(),
            'cmd+shift+p': () => this.openFilterPresets(),
        };

        this.shortcuts = new Map(Object.entries(shortcuts));
        
        document.addEventListener('keydown', (e) => {
            const key = this.getShortcutKey(e);
            const handler = this.shortcuts.get(key);
            
            if (handler && !this.isInputFocused() && !this.isModalOpen()) {
                e.preventDefault();
                handler();
            }
        });

        // Show keyboard shortcuts help
        this.createShortcutsHelp();
    }

    getShortcutKey(event) {
        const parts = [];
        if (event.ctrlKey) parts.push('ctrl');
        if (event.metaKey) parts.push('cmd');
        if (event.shiftKey) parts.push('shift');
        if (event.altKey) parts.push('alt');
        parts.push(event.key.toLowerCase());
        return parts.join('+');
    }

    /**
     * Command Palette (Spotlight-style)
     */
    setupCommandPalette() {
        this.commandPalette = {
            isOpen: false,
            query: '',
            results: [],
            selectedIndex: 0,
            commands: [
                {
                    id: 'refresh-all',
                    title: 'Refresh All Data',
                    description: 'Reload all call data from server',
                    action: () => this.refreshAllData(),
                    icon: '🔄'
                },
                {
                    id: 'new-call',
                    title: 'New Call Entry',
                    description: 'Create a new call record',
                    action: () => this.createNewCall(),
                    icon: '📞'
                },
                {
                    id: 'voice-note',
                    title: 'Start Voice Note',
                    description: 'Begin voice-to-text note taking',
                    action: () => this.startVoiceNote(),
                    icon: '🎤'
                },
                {
                    id: 'export-data',
                    title: 'Export Call Data',
                    description: 'Export filtered calls to CSV',
                    action: () => this.exportCallData(),
                    icon: '📊'
                },
                {
                    id: 'filter-today',
                    title: 'Filter: Today\'s Calls',
                    description: 'Show only calls from today',
                    action: () => this.applyFilter('today'),
                    icon: '📅'
                },
                {
                    id: 'filter-priority',
                    title: 'Filter: High Priority',
                    description: 'Show only high priority calls',
                    action: () => this.applyFilter('priority'),
                    icon: '⚡'
                },
                {
                    id: 'customer-search',
                    title: 'Search Customers',
                    description: 'Quick customer lookup',
                    action: () => this.focusCustomerSearch(),
                    icon: '👥'
                }
            ]
        };

        this.createCommandPaletteUI();
    }

    createCommandPaletteUI() {
        const paletteHTML = `
            <div id="command-palette" 
                 class="fixed inset-0 z-50 hidden bg-black/50 backdrop-blur-sm"
                 x-data="commandPaletteData()"
                 x-show="isOpen"
                 x-transition.opacity>
                <div class="flex min-h-full items-start justify-center p-4 pt-16">
                    <div class="w-full max-w-xl transform overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-black ring-opacity-5 transition-all"
                         @click.away="close()">
                        <!-- Search Input -->
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <input type="text" 
                                   x-model="query"
                                   x-ref="searchInput"
                                   @input="search()"
                                   @keydown.arrow-down.prevent="selectNext()"
                                   @keydown.arrow-up.prevent="selectPrevious()"
                                   @keydown.enter.prevent="executeCommand()"
                                   @keydown.escape="close()"
                                   class="block w-full border-0 bg-transparent py-4 pl-11 pr-4 text-gray-900 placeholder-gray-500 focus:ring-0 sm:text-sm"
                                   placeholder="Type a command or search..."
                                   autocomplete="off">
                        </div>

                        <!-- Results -->
                        <div class="max-h-96 scroll-py-3 overflow-y-auto">
                            <template x-for="(result, index) in filteredResults" :key="result.id">
                                <div class="flex cursor-pointer select-none items-center px-4 py-3"
                                     :class="selectedIndex === index ? 'bg-indigo-50 text-indigo-900' : 'text-gray-900'"
                                     @click="executeCommand(result)"
                                     @mouseenter="selectedIndex = index">
                                    <span class="mr-3 text-lg" x-text="result.icon"></span>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium" x-text="result.title"></p>
                                        <p class="text-xs text-gray-500" x-text="result.description"></p>
                                    </div>
                                </div>
                            </template>
                            
                            <div x-show="filteredResults.length === 0" class="px-4 py-6 text-center text-sm text-gray-500">
                                No commands found. Try a different search term.
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-between border-t border-gray-100 px-4 py-3 text-xs text-gray-500">
                            <div class="flex items-center space-x-2">
                                <kbd class="rounded border border-gray-300 px-1">↵</kbd>
                                <span>to select</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <kbd class="rounded border border-gray-300 px-1">↑↓</kbd>
                                <span>to navigate</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <kbd class="rounded border border-gray-300 px-1">esc</kbd>
                                <span>to close</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', paletteHTML);

        // Alpine.js data for command palette
        window.commandPaletteData = () => ({
            isOpen: false,
            query: '',
            selectedIndex: 0,
            filteredResults: [],
            
            init() {
                this.filteredResults = window.advancedCallManagement.commandPalette.commands;
            },
            
            open() {
                this.isOpen = true;
                this.query = '';
                this.selectedIndex = 0;
                this.filteredResults = window.advancedCallManagement.commandPalette.commands;
                this.$nextTick(() => this.$refs.searchInput.focus());
            },
            
            close() {
                this.isOpen = false;
                this.query = '';
            },
            
            search() {
                if (!this.query) {
                    this.filteredResults = window.advancedCallManagement.commandPalette.commands;
                    return;
                }
                
                this.filteredResults = window.advancedCallManagement.commandPalette.commands.filter(cmd => 
                    cmd.title.toLowerCase().includes(this.query.toLowerCase()) ||
                    cmd.description.toLowerCase().includes(this.query.toLowerCase())
                );
                this.selectedIndex = 0;
            },
            
            selectNext() {
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.filteredResults.length - 1);
            },
            
            selectPrevious() {
                this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
            },
            
            executeCommand(command = null) {
                const cmd = command || this.filteredResults[this.selectedIndex];
                if (cmd && cmd.action) {
                    cmd.action();
                    this.close();
                }
            }
        });
    }

    /**
     * Drag and Drop Priority Queue Management
     */
    setupDragAndDrop() {
        // Enable drag and drop for call cards
        document.addEventListener('dragstart', (e) => {
            if (e.target.closest('.call-card')) {
                this.dragState.isDragging = true;
                this.dragState.draggedItem = e.target.closest('.call-card');
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/html', e.target.outerHTML);
                
                // Add visual feedback
                e.target.classList.add('dragging');
                this.showDropZones();
            }
        });

        document.addEventListener('dragend', (e) => {
            if (this.dragState.isDragging) {
                this.dragState.isDragging = false;
                this.dragState.draggedItem = null;
                e.target.classList.remove('dragging');
                this.hideDropZones();
            }
        });

        // Create priority zones
        this.createPriorityZones();
    }

    createPriorityZones() {
        const priorityZonesHTML = `
            <div class="priority-zones fixed right-4 top-1/2 -translate-y-1/2 z-40 space-y-2 opacity-0 pointer-events-none transition-opacity duration-200"
                 id="priority-zones">
                <div class="priority-zone high-priority rounded-lg bg-red-100 border-2 border-dashed border-red-300 p-4 text-center text-red-600 shadow-lg"
                     data-priority="high">
                    <div class="text-2xl mb-2">🔥</div>
                    <div class="text-sm font-medium">High Priority</div>
                </div>
                <div class="priority-zone medium-priority rounded-lg bg-yellow-100 border-2 border-dashed border-yellow-300 p-4 text-center text-yellow-600 shadow-lg"
                     data-priority="medium">
                    <div class="text-2xl mb-2">⚡</div>
                    <div class="text-sm font-medium">Medium Priority</div>
                </div>
                <div class="priority-zone low-priority rounded-lg bg-green-100 border-2 border-dashed border-green-300 p-4 text-center text-green-600 shadow-lg"
                     data-priority="low">
                    <div class="text-2xl mb-2">📋</div>
                    <div class="text-sm font-medium">Normal</div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', priorityZonesHTML);

        // Add drop event listeners
        document.querySelectorAll('.priority-zone').forEach(zone => {
            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.dataTransfer.dropEffect = 'move';
                zone.classList.add('drag-over');
            });

            zone.addEventListener('dragleave', (e) => {
                zone.classList.remove('drag-over');
            });

            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                const priority = zone.dataset.priority;
                const callId = this.dragState.draggedItem.dataset.callId;
                
                this.updateCallPriority(callId, priority);
                zone.classList.remove('drag-over');
            });
        });
    }

    showDropZones() {
        const zones = document.getElementById('priority-zones');
        if (zones) {
            zones.classList.remove('opacity-0', 'pointer-events-none');
            zones.classList.add('opacity-100', 'pointer-events-auto');
        }
    }

    hideDropZones() {
        const zones = document.getElementById('priority-zones');
        if (zones) {
            zones.classList.add('opacity-0', 'pointer-events-none');
            zones.classList.remove('opacity-100', 'pointer-events-auto');
        }
    }

    /**
     * Smart Search with Autocomplete
     */
    setupSmartSearch() {
        const searchContainer = document.querySelector('.fi-global-search');
        if (!searchContainer) return;

        // Enhance existing search input
        const searchInput = searchContainer.querySelector('input');
        if (!searchInput) return;

        // Add autocomplete functionality
        let searchTimeout;
        let currentResults = [];

        searchInput.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.performSmartSearch(e.target.value);
            }, 300);
        });

        // Create search results dropdown
        this.createSearchDropdown(searchContainer);
    }

    createSearchDropdown(container) {
        const dropdownHTML = `
            <div class="smart-search-dropdown absolute top-full left-0 right-0 mt-1 bg-white rounded-lg shadow-lg border border-gray-200 z-50 max-h-80 overflow-y-auto hidden">
                <div class="search-results">
                    <!-- Results will be populated here -->
                </div>
                <div class="search-footer p-3 border-t border-gray-100 text-xs text-gray-500">
                    <div class="flex justify-between">
                        <span>Use ↑↓ to navigate, Enter to select</span>
                        <span>Powered by Smart Search</span>
                    </div>
                </div>
            </div>
        `;

        container.classList.add('relative');
        container.insertAdjacentHTML('beforeend', dropdownHTML);
    }

    async performSmartSearch(query) {
        if (!query || query.length < 2) {
            this.hideSearchResults();
            return;
        }

        try {
            const response = await fetch(`/admin/api/smart-search?q=${encodeURIComponent(query)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const results = await response.json();
            this.displaySearchResults(results);
        } catch (error) {
            console.error('Smart search error:', error);
        }
    }

    displaySearchResults(results) {
        const dropdown = document.querySelector('.smart-search-dropdown');
        const resultsContainer = dropdown.querySelector('.search-results');
        
        if (!results.length) {
            resultsContainer.innerHTML = '<div class="p-3 text-gray-500 text-center">No results found</div>';
        } else {
            resultsContainer.innerHTML = results.map((result, index) => `
                <div class="search-result-item p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0"
                     data-index="${index}" data-url="${result.url}">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 mr-3">
                            <span class="text-lg">${this.getResultIcon(result.type)}</span>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900">${result.title}</div>
                            <div class="text-xs text-gray-500">${result.subtitle}</div>
                        </div>
                        <div class="flex-shrink-0">
                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                ${result.type}
                            </span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        dropdown.classList.remove('hidden');

        // Add click handlers
        resultsContainer.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', () => {
                window.location.href = item.dataset.url;
            });
        });
    }

    getResultIcon(type) {
        const icons = {
            call: '📞',
            customer: '👤',
            appointment: '📅',
            staff: '👥',
            service: '🛠️',
            branch: '🏢'
        };
        return icons[type] || '📄';
    }

    hideSearchResults() {
        const dropdown = document.querySelector('.smart-search-dropdown');
        if (dropdown) {
            dropdown.classList.add('hidden');
        }
    }

    /**
     * Customer Timeline Visualization
     */
    setupCustomerTimeline() {
        // Add timeline to customer detail views
        const customerViews = document.querySelectorAll('[data-customer-timeline]');
        
        customerViews.forEach(view => {
            this.createCustomerTimeline(view);
        });
    }

    createCustomerTimeline(container) {
        const customerId = container.dataset.customerId;
        if (!customerId) return;

        const timelineHTML = `
            <div class="customer-timeline mt-6" x-data="customerTimelineData(${customerId})">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Customer Timeline</h3>
                
                <div class="relative">
                    <!-- Timeline line -->
                    <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>
                    
                    <!-- Timeline events -->
                    <template x-for="event in events" :key="event.id">
                        <div class="relative flex items-start space-x-4 pb-6">
                            <div class="relative flex items-center justify-center w-8 h-8 bg-white border-2 border-gray-200 rounded-full"
                                 :class="getEventStyle(event.type)">
                                <span class="text-sm" x-text="getEventIcon(event.type)"></span>
                            </div>
                            
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-sm font-medium text-gray-900" x-text="event.title"></h4>
                                    <time class="text-xs text-gray-500" x-text="formatDate(event.created_at)"></time>
                                </div>
                                <p class="text-sm text-gray-600 mt-1" x-text="event.description"></p>
                                
                                <div x-show="event.details" class="mt-2">
                                    <details class="text-xs text-gray-500">
                                        <summary class="cursor-pointer">Show details</summary>
                                        <div class="mt-2 p-2 bg-gray-50 rounded" x-text="event.details"></div>
                                    </details>
                                </div>
                            </div>
                        </div>
                    </template>
                    
                    <div x-show="loading" class="text-center py-4">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', timelineHTML);

        // Alpine.js data for timeline
        window.customerTimelineData = (customerId) => ({
            events: [],
            loading: true,
            
            async init() {
                await this.loadTimeline();
            },
            
            async loadTimeline() {
                try {
                    const response = await fetch(`/admin/api/customer/${customerId}/timeline`);
                    this.events = await response.json();
                } catch (error) {
                    console.error('Failed to load timeline:', error);
                } finally {
                    this.loading = false;
                }
            },
            
            getEventIcon(type) {
                const icons = {
                    call: '📞',
                    appointment: '📅',
                    note: '📝',
                    email: '📧',
                    sms: '💬'
                };
                return icons[type] || '📄';
            },
            
            getEventStyle(type) {
                const styles = {
                    call: 'border-blue-500 text-blue-600',
                    appointment: 'border-green-500 text-green-600',
                    note: 'border-yellow-500 text-yellow-600',
                    email: 'border-purple-500 text-purple-600',
                    sms: 'border-pink-500 text-pink-600'
                };
                return styles[type] || 'border-gray-300 text-gray-500';
            },
            
            formatDate(dateString) {
                return new Date(dateString).toLocaleDateString('de-DE', {
                    day: '2-digit',
                    month: 'short',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }
        });
    }

    /**
     * Advanced Filter Presets
     */
    setupFilterPresets() {
        this.createFilterPresetsUI();
    }

    createFilterPresetsUI() {
        const presetsHTML = `
            <div class="filter-presets-container mb-4" x-data="filterPresetsData()">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-700">Quick Filters</h3>
                    <button type="button" 
                            @click="openPresetManager()"
                            class="text-xs text-blue-600 hover:text-blue-800">
                        Manage Presets
                    </button>
                </div>
                
                <div class="flex flex-wrap gap-2">
                    <template x-for="preset in presets" :key="preset.id">
                        <button type="button"
                                @click="applyPreset(preset)"
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                :class="activePreset?.id === preset.id ? 'bg-blue-100 text-blue-800 ring-1 ring-blue-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'">
                            <span x-text="preset.icon" class="mr-1"></span>
                            <span x-text="preset.name"></span>
                            <span x-show="preset.count" 
                                  x-text="'(' + preset.count + ')'"
                                  class="ml-1 text-gray-500"></span>
                        </button>
                    </template>
                    
                    <button type="button"
                            @click="clearFilters()"
                            x-show="activePreset"
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs text-gray-500 hover:text-gray-700">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;

        const filtersContainer = document.querySelector('.fi-ta-filters') || 
                                document.querySelector('.filament-tables-filters') ||
                                document.querySelector('[data-filters-container]');
        
        if (filtersContainer) {
            filtersContainer.insertAdjacentHTML('afterbegin', presetsHTML);
        }

        // Alpine.js data for filter presets
        window.filterPresetsData = () => ({
            presets: [
                { id: 'today', name: 'Today', icon: '📅', filters: { time_range: 'today' } },
                { id: 'priority', name: 'High Priority', icon: '🔥', filters: { priority: 'high' } },
                { id: 'missed', name: 'Missed Calls', icon: '❌', filters: { status: 'missed' } },
                { id: 'appointments', name: 'With Appointments', icon: '✅', filters: { appointment_made: true } },
                { id: 'long-calls', name: 'Long Calls', icon: '⏱️', filters: { min_duration: 300 } },
                { id: 'new-customers', name: 'New Customers', icon: '🆕', filters: { new_customer: true } }
            ],
            activePreset: null,
            
            init() {
                this.loadPresetCounts();
                this.loadSavedPresets();
            },
            
            applyPreset(preset) {
                this.activePreset = preset;
                
                // Apply filters via Livewire
                if (window.Livewire) {
                    window.Livewire.emit('applyFilterPreset', preset.filters);
                }
                
                // Save active preset
                localStorage.setItem('activeFilterPreset', JSON.stringify(preset));
            },
            
            clearFilters() {
                this.activePreset = null;
                localStorage.removeItem('activeFilterPreset');
                
                if (window.Livewire) {
                    window.Livewire.emit('clearFilters');
                }
            },
            
            async loadPresetCounts() {
                try {
                    const response = await fetch('/admin/api/filter-preset-counts');
                    const counts = await response.json();
                    
                    this.presets.forEach(preset => {
                        preset.count = counts[preset.id] || 0;
                    });
                } catch (error) {
                    console.error('Failed to load preset counts:', error);
                }
            },
            
            loadSavedPresets() {
                const saved = localStorage.getItem('filterPresets');
                if (saved) {
                    const customPresets = JSON.parse(saved);
                    this.presets.push(...customPresets);
                }
                
                const activePreset = localStorage.getItem('activeFilterPreset');
                if (activePreset) {
                    this.activePreset = JSON.parse(activePreset);
                }
            },
            
            openPresetManager() {
                // TODO: Implement preset manager modal
                console.log('Opening preset manager...');
            }
        });
    }

    loadFilterPresets() {
        const saved = localStorage.getItem('advancedCallManagement.filterPresets');
        return saved ? JSON.parse(saved) : [];
    }

    saveFilterPresets() {
        localStorage.setItem('advancedCallManagement.filterPresets', JSON.stringify(this.filterPresets));
    }

    /**
     * Real-time Updates
     */
    setupRealTimeUpdates() {
        // Visual indicators for real-time updates
        this.createRealTimeIndicator();
        
        // Update counters and badges in real-time
        this.setupRealTimeCounters();
    }

    createRealTimeIndicator() {
        const indicatorHTML = `
            <div class="real-time-indicator fixed top-4 right-4 z-50" x-data="realTimeIndicatorData()">
                <div class="flex items-center space-x-2 bg-white rounded-lg shadow-lg px-3 py-2 border"
                     :class="connected ? 'border-green-200' : 'border-red-200'">
                    <div class="flex items-center">
                        <div class="w-2 h-2 rounded-full"
                             :class="connected ? 'bg-green-400 animate-pulse' : 'bg-red-400'"></div>
                        <span class="ml-2 text-xs font-medium text-gray-700">
                            <span x-text="connected ? 'Live' : 'Offline'"></span>
                        </span>
                    </div>
                    
                    <div x-show="updateCount > 0" class="text-xs text-gray-500">
                        <span x-text="updateCount"></span> updates
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', indicatorHTML);

        window.realTimeIndicatorData = () => ({
            connected: false,
            updateCount: 0,
            
            init() {
                this.checkConnection();
                setInterval(() => this.checkConnection(), 5000);
            },
            
            checkConnection() {
                if (window.Echo && window.Echo.connector) {
                    this.connected = window.Echo.connector.pusher.connection.state === 'connected';
                }
            },
            
            incrementUpdates() {
                this.updateCount++;
                setTimeout(() => { this.updateCount = Math.max(0, this.updateCount - 1); }, 5000);
            }
        });
    }

    setupRealTimeCounters() {
        // Update navigation badges in real-time
        setInterval(() => {
            this.updateNavigationBadges();
        }, 30000);
    }

    /**
     * Performance Optimizations
     */
    setupPerformanceOptimizations() {
        // Virtual scrolling for large call lists
        this.setupVirtualScrolling();
        
        // Lazy loading of call details
        this.setupLazyLoading();
        
        // Debounced search
        this.setupDebouncedSearch();
    }

    setupVirtualScrolling() {
        const callTables = document.querySelectorAll('.fi-ta-table');
        
        callTables.forEach(table => {
            // Only enable for tables with many rows
            const rows = table.querySelectorAll('tbody tr');
            if (rows.length > 50) {
                this.enableVirtualScrolling(table);
            }
        });
    }

    enableVirtualScrolling(table) {
        // Implementation would go here
        console.log('Virtual scrolling enabled for table with many rows');
    }

    setupLazyLoading() {
        // Lazy load call recordings and transcripts
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadCallDetails(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        });

        document.querySelectorAll('[data-lazy-load]').forEach(el => {
            observer.observe(el);
        });
    }

    async loadCallDetails(element) {
        const callId = element.dataset.callId;
        if (!callId) return;

        try {
            const response = await fetch(`/admin/api/calls/${callId}/details`);
            const details = await response.json();
            
            // Update the element with loaded data
            element.innerHTML = this.renderCallDetails(details);
        } catch (error) {
            console.error('Failed to load call details:', error);
        }
    }

    /**
     * Event Handlers
     */
    handleCallCreated(call) {
        this.showNotification(`New call from ${call.from_number}`, 'info');
        this.updateCallDisplay(call);
        this.playNotificationSound();
    }

    handleCallUpdated(call) {
        this.updateCallDisplay(call);
    }

    handleCallStatusChanged(call) {
        this.updateCallStatus(call);
        
        if (call.status === 'completed') {
            this.showNotification(`Call completed: ${call.from_number}`, 'success');
        }
    }

    handleQueueUpdated(queue) {
        this.updateQueueDisplay(queue);
    }

    /**
     * Utility Methods
     */
    openCommandPalette() {
        const palette = document.querySelector('#command-palette');
        if (palette && window.Alpine) {
            Alpine.store('commandPalette').open();
        }
    }

    startVoiceNote() {
        if (!this.voiceRecognition) {
            this.showNotification('Voice recognition not available', 'error');
            return;
        }

        const activeNoteField = document.querySelector('textarea[x-data*="voiceNote"], .voice-note-active');
        if (activeNoteField) {
            this.voiceRecognition.start();
            this.showNotification('Voice note started - speak now', 'info');
        } else {
            this.showNotification('Please focus on a note field first', 'warning');
        }
    }

    updateVoiceNote(finalText, interimText) {
        const activeField = document.querySelector('.voice-note-active');
        if (activeField) {
            activeField.value = finalText + interimText;
            activeField.dispatchEvent(new Event('input'));
        }
    }

    showNotification(message, type = 'info') {
        if (window.Livewire) {
            window.Livewire.emit('notify', { message, type });
        } else {
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }

    playNotificationSound() {
        const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+DyvmwhCjeR2e/RfC0FK3zN8dyNOgcZZ7rx5pZhFQxDr+L2smgbBzSS1/LNeSYFJHfG8N2QQAoUXrTp66hVFApGn+Dyvmwh');
        audio.volume = 0.3;
        audio.play().catch(() => {});
    }

    async updateCallPriority(callId, priority) {
        try {
            const response = await fetch(`/admin/api/calls/${callId}/priority`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({ priority })
            });

            if (response.ok) {
                this.showNotification(`Call priority updated to ${priority}`, 'success');
                
                // Update UI
                const callCard = document.querySelector(`[data-call-id="${callId}"]`);
                if (callCard) {
                    callCard.dataset.priority = priority;
                    this.updateCallCard(callCard, { priority });
                }
            } else {
                throw new Error('Failed to update priority');
            }
        } catch (error) {
            this.showNotification('Failed to update call priority', 'error');
            console.error(error);
        }
    }

    updateCallDisplay(call) {
        const callCard = document.querySelector(`[data-call-id="${call.id}"]`);
        if (callCard) {
            this.updateCallCard(callCard, call);
        }
    }

    updateCallCard(card, data) {
        // Add visual update indicator
        card.classList.add('animate-pulse');
        setTimeout(() => card.classList.remove('animate-pulse'), 1000);

        // Update priority indicator if provided
        if (data.priority) {
            const priorityIndicator = card.querySelector('.priority-indicator');
            if (priorityIndicator) {
                priorityIndicator.className = `priority-indicator priority-${data.priority}`;
                priorityIndicator.textContent = this.getPriorityEmoji(data.priority);
            }
        }

        // Update status if provided
        if (data.status) {
            const statusBadge = card.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.textContent = data.status;
                statusBadge.className = `status-badge status-${data.status}`;
            }
        }
    }

    getPriorityEmoji(priority) {
        const emojis = {
            high: '🔥',
            medium: '⚡',
            low: '📋'
        };
        return emojis[priority] || '';
    }

    updateCallStatus(call) {
        this.updateCallDisplay(call);
    }

    updateQueueDisplay(queue) {
        // Update queue visualization if present
        const queueWidget = document.querySelector('.queue-widget');
        if (queueWidget && window.Livewire) {
            window.Livewire.emit('refreshQueue');
        }
    }

    updateNavigationBadges() {
        // Update call count badges in navigation
        if (window.Livewire) {
            window.Livewire.emit('updateNavigationBadges');
        }
    }

    isInputFocused() {
        const active = document.activeElement;
        return active && (
            active.tagName === 'INPUT' || 
            active.tagName === 'TEXTAREA' || 
            active.contentEditable === 'true'
        );
    }

    isModalOpen() {
        return document.querySelector('.fi-modal-open') !== null;
    }

    createShortcutsHelp() {
        const helpHTML = `
            <div class="keyboard-shortcuts-help fixed bottom-4 left-4 z-40" x-data="{ open: false }">
                <button @click="open = !open" 
                        class="bg-gray-800 text-white p-2 rounded-full shadow-lg hover:bg-gray-700 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </button>

                <div x-show="open" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     @click.away="open = false"
                     class="absolute bottom-full left-0 mb-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 p-4">
                    
                    <h3 class="font-medium text-gray-900 mb-3">Keyboard Shortcuts</h3>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Open Command Palette</span>
                            <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl+K</kbd>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Start Voice Note</span>
                            <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl+Shift+N</kbd>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Refresh Data</span>
                            <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl+Shift+R</kbd>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Focus Search</span>
                            <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">Ctrl+Shift+F</kbd>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Navigate Next/Previous</span>
                            <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">J / K</kbd>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Focus Filters</span>
                            <kbd class="px-2 py-1 bg-gray-100 rounded text-xs">F</kbd>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', helpHTML);
    }

    // Additional utility methods for the interface
    refreshAllData() {
        if (window.Livewire) {
            window.Livewire.emit('refresh');
            this.showNotification('Data refreshed', 'success');
        }
    }

    createNewCall() {
        const createUrl = window.location.pathname.includes('/calls') 
            ? window.location.pathname + '/create'
            : '/admin/calls/create';
        window.location.href = createUrl;
    }

    focusGlobalSearch() {
        const searchInput = document.querySelector('.fi-global-search input, input[type="search"]');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }

    closeModalsAndPalettes() {
        // Close command palette
        if (window.Alpine && window.Alpine.store('commandPalette')) {
            window.Alpine.store('commandPalette').close();
        }

        // Close any open modals
        const closeButtons = document.querySelectorAll('[x-on\\:click*="close"], .modal-close');
        closeButtons.forEach(btn => btn.click());
    }

    // Navigation methods
    navigateNext() {
        // Implementation for navigating to next call
        console.log('Navigate next');
    }

    navigatePrevious() {
        // Implementation for navigating to previous call
        console.log('Navigate previous');
    }

    openSelectedCall() {
        // Implementation for opening currently selected call
        console.log('Open selected call');
    }

    switchToTab(tab) {
        // Implementation for tab switching
        console.log('Switch to tab:', tab);
    }

    focusFilterBar() {
        const filterInput = document.querySelector('.fi-ta-filters input');
        if (filterInput) {
            filterInput.focus();
        }
    }

    openFilterPresets() {
        // Implementation for opening filter presets
        console.log('Open filter presets');
    }

    applyFilter(filterName) {
        if (window.Livewire) {
            window.Livewire.emit('applyQuickFilter', filterName);
        }
    }

    focusCustomerSearch() {
        const searchInput = document.querySelector('input[placeholder*="customer"], input[placeholder*="Customer"]');
        if (searchInput) {
            searchInput.focus();
        }
    }

    exportCallData() {
        // Implementation for data export
        window.location.href = '/admin/calls/export';
    }

    renderCallDetails(details) {
        // Implementation for rendering call details
        return `<div class="call-details">${JSON.stringify(details)}</div>`;
    }
}

// Initialize the advanced call management system
window.advancedCallManagement = null;

function initializeAdvancedCallManagement() {
    if (!window.advancedCallManagement) {
        window.advancedCallManagement = new AdvancedCallManagement();
    }
}

// Multiple initialization strategies
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeAdvancedCallManagement);
} else {
    initializeAdvancedCallManagement();
}

// Alpine.js integration
document.addEventListener('alpine:init', () => {
    initializeAdvancedCallManagement();
});

// Export for use in other modules
window.AdvancedCallManagement = AdvancedCallManagement;

console.log('🚀 Advanced Call Management module loaded successfully');