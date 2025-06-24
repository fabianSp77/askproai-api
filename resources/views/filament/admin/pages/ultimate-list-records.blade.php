<x-filament-panels::page>
    @vite(['resources/js/ultimate-ui-system-simple.js', 'resources/css/filament/admin/ultimate-theme.css'])
    
    <div 
        x-data="{
            currentView: @js($currentView ?? 'table'),
            selectedRecords: [],
            smartFilterQuery: '',
            smartFilterSuggestions: [],
            
            init() {
                // Initialize Ultimate UI components
                if (window.CommandPalette) {
                    window.commandPalette = new CommandPalette();
                }
                if (window.SmartFilter) {
                    window.smartFilter = new SmartFilter();
                }
                if (window.InlineEditor) {
                    window.inlineEditor = new InlineEditor();
                }
                
                // Keyboard shortcuts
                document.addEventListener('keydown', (e) => {
                    if (e.metaKey || e.ctrlKey) {
                        switch(e.key) {
                            case 'k':
                                e.preventDefault();
                                this.openCommandPalette();
                                break;
                            case '1':
                                e.preventDefault();
                                this.switchView('table');
                                break;
                            case '2':
                                e.preventDefault();
                                this.switchView('grid');
                                break;
                            case '3':
                                e.preventDefault();
                                this.switchView('kanban');
                                break;
                            case '4':
                                e.preventDefault();
                                this.switchView('calendar');
                                break;
                            case '5':
                                e.preventDefault();
                                this.switchView('timeline');
                                break;
                        }
                    }
                });
            },
            
            switchView(view) {
                this.currentView = view;
                $wire.dispatch('switch-view', { view: view });
            },
            
            openCommandPalette() {
                if (window.commandPalette) {
                    window.commandPalette.open();
                }
            },
            
            parseSmartFilter() {
                if (window.smartFilter) {
                    this.smartFilterSuggestions = window.smartFilter.getSuggestions(this.smartFilterQuery);
                }
            },
            
            applySmartFilters() {
                if (window.smartFilter) {
                    const filters = window.smartFilter.parse(this.smartFilterQuery);
                    $wire.dispatch('apply-smart-filters', { filters: filters });
                }
            },
            
            applySuggestion(suggestion) {
                this.smartFilterQuery = suggestion;
                this.applySmartFilters();
            },
            
            toggleSelect(recordId) {
                const index = this.selectedRecords.indexOf(recordId);
                if (index > -1) {
                    this.selectedRecords.splice(index, 1);
                } else {
                    this.selectedRecords.push(recordId);
                }
            },
            
            showKeyboardShortcuts() {
                // Implemented by keyboardShortcuts component
            }
        }"
        x-init="init"
        class="fi-resource-ultimate"
        data-virtual-scroll="true"
        data-inline-edit="true"
    >
        {{-- Command Palette Placeholder --}}
        <div id="command-palette-container"></div>
        
        {{-- Smart Filter Bar --}}
        <div class="mb-6">
            <div class="smart-filter-input relative">
                <svg class="smart-filter-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
                <input 
                    type="text"
                    x-model="smartFilterQuery"
                    x-ref="smartFilterInput"
                    @input="parseSmartFilter"
                    @keydown.enter="applySmartFilters"
                    placeholder="Suche nach 'heute', 'positive Anrufe', 'letzte Woche', 'ohne Termin'..."
                    class="fi-input block w-full border-gray-300 rounded-lg shadow-sm pl-12 pr-4"
                >
                <div 
                    x-show="smartFilterSuggestions.length > 0"
                    x-transition
                    class="smart-filter-suggestions"
                >
                    <template x-for="suggestion in smartFilterSuggestions" :key="suggestion">
                        <div 
                            @click="applySuggestion(suggestion)"
                            class="smart-filter-suggestion"
                            x-text="suggestion"
                        ></div>
                    </template>
                </div>
            </div>
        </div>
        
        {{-- View Switcher --}}
        <div x-data="{ currentView: $wire.entangle('currentView') }" class="fi-ta-view-switcher">
            <style>
                .fi-ta-view-switcher {
                    position: sticky;
                    top: 0;
                    z-index: 40;
                    background: rgba(255, 255, 255, 0.8);
                    backdrop-filter: blur(12px);
                    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
                    margin: -1.5rem -1.5rem 1.5rem;
                    padding: 1rem 1.5rem;
                }
                
                .dark .fi-ta-view-switcher {
                    background: rgba(31, 41, 55, 0.8);
                    border-bottom-color: rgba(255, 255, 255, 0.1);
                }
                
                .view-tabs {
                    display: flex;
                    gap: 0.5rem;
                    align-items: center;
                }
                
                .view-tab {
                    position: relative;
                    padding: 0.5rem 1rem;
                    border-radius: 0.5rem;
                    font-size: 0.875rem;
                    font-weight: 500;
                    color: rgb(107, 114, 128);
                    transition: all 0.2s;
                    cursor: pointer;
                    user-select: none;
                }
                
                .view-tab:hover {
                    background: rgba(0, 0, 0, 0.05);
                    color: rgb(31, 41, 55);
                }
                
                .dark .view-tab:hover {
                    background: rgba(255, 255, 255, 0.1);
                    color: rgb(243, 244, 246);
                }
                
                .view-tab.active {
                    background: rgb(59, 130, 246);
                    color: white;
                    box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.3);
                }
                
                .view-tab-icon {
                    width: 1.25rem;
                    height: 1.25rem;
                    margin-right: 0.5rem;
                    display: inline-block;
                    vertical-align: middle;
                }
                
                .keyboard-hint {
                    margin-left: auto;
                    font-size: 0.75rem;
                    color: rgb(156, 163, 175);
                    display: flex;
                    align-items: center;
                    gap: 0.25rem;
                }
                
                .kbd {
                    padding: 0.125rem 0.375rem;
                    background: rgba(0, 0, 0, 0.1);
                    border-radius: 0.25rem;
                    font-family: monospace;
                    font-size: 0.75rem;
                }
                
                .dark .kbd {
                    background: rgba(255, 255, 255, 0.1);
                }
            </style>
            
            <div class="view-tabs">
                <button 
                    @click="switchView('table')"
                    :class="currentView === 'table' ? 'active' : ''"
                    class="view-tab"
                    title="Table View (⌘1)"
                >
                    <svg class="view-tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    Table
                </button>
                
                <button 
                    @click="switchView('grid')"
                    :class="currentView === 'grid' ? 'active' : ''"
                    class="view-tab"
                    title="Grid View (⌘2)"
                >
                    <svg class="view-tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                    </svg>
                    Grid
                </button>
                
                <button 
                    @click="switchView('kanban')"
                    :class="currentView === 'kanban' ? 'active' : ''"
                    class="view-tab"
                    title="Kanban View (⌘3)"
                >
                    <svg class="view-tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2" />
                    </svg>
                    Kanban
                </button>
                
                <button 
                    @click="switchView('calendar')"
                    :class="currentView === 'calendar' ? 'active' : ''"
                    class="view-tab"
                    title="Calendar View (⌘4)"
                >
                    <svg class="view-tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Calendar
                </button>
                
                <button 
                    @click="switchView('timeline')"
                    :class="currentView === 'timeline' ? 'active' : ''"
                    class="view-tab"
                    title="Timeline View (⌘5)"
                >
                    <svg class="view-tab-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Timeline
                </button>
                
                <div class="keyboard-hint">
                    <span>Press</span>
                    <kbd class="kbd">⌘K</kbd>
                    <span>for command palette</span>
                </div>
            </div>
        </div>
        
        {{-- Main Content Area --}}
        <div class="fi-ta-ctn">
            <template x-if="currentView === 'table'">
                <div>
                    {{ $this->table }}
                </div>
            </template>
            
            <template x-if="currentView === 'grid'">
                <div class="ultimate-grid">
                    @php
                        $records = method_exists($this, 'getTableRecords') ? $this->getTableRecords() : collect();
                        $records = $records ?: collect();
                    @endphp
                    @if($records->count() > 0)
                    @foreach($records as $record)
                        <div 
                            class="ultimate-grid-item"
                            :class="{ 'ultimate-grid-item-selected': selectedRecords.includes('{{ $record->id }}') }"
                            @click="toggleSelect('{{ $record->id }}')"
                            data-record-id="{{ $record->id }}"
                        >
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-primary-100 flex items-center justify-center">
                                        @if($record->customer)
                                            <span class="text-primary-700 font-semibold">
                                                {{ substr($record->customer->name, 0, 1) }}
                                            </span>
                                        @else
                                            <svg class="w-5 h-5 text-primary-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div>
                                        <h3 class="font-semibold text-gray-900 dark:text-white">
                                            {{ $record->customer?->name ?? 'Unbekannter Anrufer' }}
                                        </h3>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $record->from_number }}
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    @if($record->analysis['sentiment'] ?? null)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            {{ match($record->analysis['sentiment']) {
                                                'positive' => 'bg-green-100 text-green-800',
                                                'negative' => 'bg-red-100 text-red-800',
                                                default => 'bg-gray-100 text-gray-800'
                                            } }}">
                                            {{ match($record->analysis['sentiment']) {
                                                'positive' => '😊',
                                                'negative' => '😞',
                                                default => '😐'
                                            } }}
                                        </span>
                                    @endif
                                    
                                    @if($record->appointment_id)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <svg class="w-3 h-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            Termin
                                        </span>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                <div class="flex items-center gap-4">
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ $record->start_timestamp?->format('d.m.Y H:i') ?? $record->created_at->format('d.m.Y H:i') }}
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ gmdate('i:s', $record->duration_sec ?? 0) }}
                                    </span>
                                </div>
                            </div>
                            
                            @if($record->analysis['summary'] ?? null)
                                <p class="text-sm text-gray-700 dark:text-gray-300 line-clamp-3">
                                    {{ $record->analysis['summary'] }}
                                </p>
                            @endif
                            
                            <div class="mt-4 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    @if($record->audio_url)
                                        <button 
                                            @click.stop="$wire.mountTableAction('play_recording', '{{ $record->id }}')"
                                            class="text-gray-400 hover:text-primary-600 transition"
                                        >
                                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    @endif
                                    
                                    <button 
                                        @click.stop="$wire.mountTableAction('share', '{{ $record->id }}')"
                                        class="text-gray-400 hover:text-primary-600 transition"
                                    >
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m9.032 4.026a9.001 9.001 0 010-5.284m-9.032 4.026A8.963 8.963 0 016 12c0-1.18.23-2.305.644-3.342m7.072 6.684a9.001 9.001 0 01-5.432 0m7.072 0c.886-.404 1.692-.938 2.396-1.584M6.284 8.658c.704-.646 1.51-1.18 2.396-1.584m8.036 0A8.963 8.963 0 0120 12c0 1.18-.23 2.305-.644 3.342m-2.64-5.284a9.001 9.001 0 010 5.284" />
                                        </svg>
                                    </button>
                                </div>
                                
                                <button 
                                    @click.stop="$wire.mountTableAction('view', '{{ $record->id }}')"
                                    class="text-primary-600 hover:text-primary-700 font-medium text-sm"
                                >
                                    Details →
                                </button>
                            </div>
                        </div>
                    @endforeach
                    @endif
                </div>
            </template>
            
            <template x-if="currentView === 'kanban'">
                <div class="ultimate-kanban">
                    {{-- Kanban columns for call status --}}
                    @php
                        $statuses = [
                            'pending' => ['label' => 'Ausstehend', 'color' => 'gray'],
                            'in_progress' => ['label' => 'In Bearbeitung', 'color' => 'blue'],
                            'completed' => ['label' => 'Abgeschlossen', 'color' => 'green'],
                            'failed' => ['label' => 'Fehlgeschlagen', 'color' => 'red'],
                        ];
                        $records = method_exists($this, 'getTableRecords') ? $this->getTableRecords() : collect();
                        $records = $records ?: collect();
                    @endphp
                    
                    @if($records->count() > 0)
                    @foreach($statuses as $status => $config)
                        <div class="ultimate-kanban-column" data-status="{{ $status }}">
                            <div class="ultimate-kanban-header">
                                <h3 class="ultimate-kanban-title">{{ $config['label'] }}</h3>
                                <span class="ultimate-kanban-count">
                                    {{ $records->where('call_status', $status)->count() }}
                                </span>
                            </div>
                            
                            <div class="ultimate-kanban-items">
                                @foreach($records->where('call_status', $status) as $record)
                                    <div 
                                        class="ultimate-kanban-item"
                                        data-record-id="{{ $record->id }}"
                                        draggable="true"
                                    >
                                        <div class="flex items-start justify-between mb-2">
                                            <h4 class="font-medium text-gray-900 dark:text-white">
                                                {{ $record->customer?->name ?? 'Unbekannt' }}
                                            </h4>
                                            @if($record->analysis['sentiment'] ?? null)
                                                <span class="text-lg">
                                                    {{ match($record->analysis['sentiment']) {
                                                        'positive' => '😊',
                                                        'negative' => '😞',
                                                        default => '😐'
                                                    } }}
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                            {{ $record->from_number }}
                                        </p>
                                        
                                        <div class="flex items-center gap-2 text-xs text-gray-500">
                                            <span>{{ $record->start_timestamp?->format('d.m. H:i') }}</span>
                                            <span>•</span>
                                            <span>{{ gmdate('i:s', $record->duration_sec ?? 0) }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    @endif
                </div>
            </template>
            
            <template x-if="currentView === 'calendar'">
                <div class="ultimate-calendar">
                    {{-- Calendar implementation --}}
                    <div class="text-center py-12 text-gray-500">
                        Calendar view coming soon...
                    </div>
                </div>
            </template>
            
            <template x-if="currentView === 'timeline'">
                <div class="ultimate-timeline">
                    @php
                        $records = method_exists($this, 'getTableRecords') ? $this->getTableRecords() : collect();
                        $records = $records ?: collect();
                    @endphp
                    @if($records->count() > 0)
                    @foreach($records as $record)
                        <div class="ultimate-timeline-item">
                            <div class="ultimate-timeline-marker"></div>
                            <div class="ultimate-timeline-content">
                                <div class="flex items-start justify-between mb-2">
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">
                                            {{ $record->customer?->name ?? 'Unbekannter Anrufer' }}
                                        </h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ $record->from_number }}
                                        </p>
                                    </div>
                                    <time class="text-sm text-gray-500">
                                        {{ $record->start_timestamp?->format('d.m.Y H:i') }}
                                    </time>
                                </div>
                                
                                @if($record->analysis['summary'] ?? null)
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-3">
                                        {{ $record->analysis['summary'] }}
                                    </p>
                                @endif
                                
                                <div class="flex items-center gap-3">
                                    <span class="text-sm text-gray-500">
                                        Dauer: {{ gmdate('i:s', $record->duration_sec ?? 0) }}
                                    </span>
                                    
                                    @if($record->appointment_id)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">
                                            Termin gebucht
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                    @endif
                </div>
            </template>
        </div>
        
        {{-- Keyboard Shortcuts Modal --}}
        <div 
            x-data="{
                isOpen: false,
                shortcuts: [
                    {
                        category: 'Navigation',
                        items: [
                            { keys: ['⌘', 'K'], description: 'Open Command Palette' },
                            { keys: ['⌘', '1'], description: 'Table View' },
                            { keys: ['⌘', '2'], description: 'Grid View' },
                            { keys: ['⌘', '3'], description: 'Kanban View' },
                            { keys: ['⌘', '4'], description: 'Calendar View' },
                            { keys: ['⌘', '5'], description: 'Timeline View' },
                        ]
                    },
                    {
                        category: 'Actions',
                        items: [
                            { keys: ['⌘', 'N'], description: 'New Record' },
                            { keys: ['⌘', 'S'], description: 'Save' },
                            { keys: ['⌘', 'F'], description: 'Search/Filter' },
                            { keys: ['ESC'], description: 'Cancel/Close' },
                        ]
                    },
                    {
                        category: 'Selection',
                        items: [
                            { keys: ['Space'], description: 'Select/Deselect' },
                            { keys: ['⌘', 'A'], description: 'Select All' },
                            { keys: ['⌘', 'D'], description: 'Deselect All' },
                        ]
                    }
                ]
            }"
            x-show="isOpen"
            @show-keyboard-shortcuts.window="isOpen = true"
            x-transition
            @keydown.escape.window="isOpen = false"
            class="fixed inset-0 z-50 overflow-y-auto"
            style="display: none;"
        >
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div 
                    x-show="isOpen"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 transition-opacity"
                    @click="isOpen = false"
                >
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>
                
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                
                <div 
                    x-show="isOpen"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full keyboard-shortcuts-modal"
                >
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">
                                    Keyboard Shortcuts
                                </h3>
                                
                                <div class="mt-2">
                                    <template x-for="section in shortcuts" :key="section.category">
                                        <div class="mb-6">
                                            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3" x-text="section.category"></h4>
                                            <div class="keyboard-shortcuts-grid">
                                                <template x-for="shortcut in section.items" :key="shortcut.description">
                                                    <div class="keyboard-shortcut-item">
                                                        <span class="text-sm text-gray-600 dark:text-gray-400" x-text="shortcut.description"></span>
                                                        <div class="keyboard-shortcut-keys">
                                                            <template x-for="key in shortcut.keys" :key="key">
                                                                <kbd class="keyboard-shortcut-key" x-text="key"></kbd>
                                                            </template>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 dark:bg-gray-900 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button 
                            type="button" 
                            @click="isOpen = false"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-primary-600 text-base font-medium text-white hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:ml-3 sm:w-auto sm:text-sm"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>