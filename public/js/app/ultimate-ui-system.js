// Ultimate UI System - Complete implementation
import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import Fuse from 'fuse.js';
import hotkeys from 'hotkeys-js';
import { createPopper } from '@popperjs/core';

// Alpine plugins are already registered in app.js
// Don't register them again here

// Command Palette System
class CommandPalette {
    constructor() {
        this.isOpen = false;
        this.commands = [];
        this.fuse = null;
        this.selectedIndex = 0;
        this.initializeCommands();
    }
    
    initializeCommands() {
        this.commands = [
            // Navigation
            { id: 'nav-calls', title: 'Go to Calls', icon: 'phone', shortcut: 'G C', action: () => window.location.href = '/admin/calls' },
            { id: 'nav-appointments', title: 'Go to Appointments', icon: 'calendar', shortcut: 'G A', action: () => window.location.href = '/admin/appointments' },
            { id: 'nav-customers', title: 'Go to Customers', icon: 'users', shortcut: 'G U', action: () => window.location.href = '/admin/customers' },
            
            // Actions
            { id: 'create-appointment', title: 'Create New Appointment', icon: 'plus', shortcut: 'C A', action: () => window.location.href = '/admin/appointments/create' },
            { id: 'create-customer', title: 'Create New Customer', icon: 'user-plus', shortcut: 'C U', action: () => window.location.href = '/admin/customers/create' },
            
            // Search
            { id: 'search-calls', title: 'Search Calls...', icon: 'search', action: () => this.focusSearch('calls') },
            { id: 'search-appointments', title: 'Search Appointments...', icon: 'search', action: () => this.focusSearch('appointments') },
            { id: 'search-customers', title: 'Search Customers...', icon: 'search', action: () => this.focusSearch('customers') },
            
            // Views
            { id: 'view-table', title: 'Switch to Table View', icon: 'table', shortcut: '1', action: () => this.switchView('table') },
            { id: 'view-grid', title: 'Switch to Grid View', icon: 'grid', shortcut: '2', action: () => this.switchView('grid') },
            { id: 'view-kanban', title: 'Switch to Kanban View', icon: 'kanban', shortcut: '3', action: () => this.switchView('kanban') },
            { id: 'view-calendar', title: 'Switch to Calendar View', icon: 'calendar', shortcut: '4', action: () => this.switchView('calendar') },
            { id: 'view-timeline', title: 'Switch to Timeline View', icon: 'timeline', shortcut: '5', action: () => this.switchView('timeline') },
            
            // Filters
            { id: 'filter-today', title: 'Show Today\'s Records', icon: 'filter', action: () => this.applyFilter('today') },
            { id: 'filter-week', title: 'Show This Week', icon: 'filter', action: () => this.applyFilter('week') },
            { id: 'filter-month', title: 'Show This Month', icon: 'filter', action: () => this.applyFilter('month') },
            
            // Export
            { id: 'export-csv', title: 'Export to CSV', icon: 'download', action: () => this.export('csv') },
            { id: 'export-excel', title: 'Export to Excel', icon: 'download', action: () => this.export('xlsx') },
            { id: 'export-pdf', title: 'Export to PDF', icon: 'download', action: () => this.export('pdf') },
            
            // Settings
            { id: 'toggle-dark', title: 'Toggle Dark Mode', icon: 'moon', shortcut: 'D', action: () => this.toggleDarkMode() },
            { id: 'show-shortcuts', title: 'Show Keyboard Shortcuts', icon: 'keyboard', shortcut: '?', action: () => this.showShortcuts() },
        ];
        
        // Initialize Fuse.js for fuzzy search
        this.fuse = new Fuse(this.commands, {
            keys: ['title'],
            threshold: 0.3,
        });
    }
    
    open() {
        this.isOpen = true;
        this.selectedIndex = 0;
        document.body.style.overflow = 'hidden';
        
        // Create and show modal
        this.createModal();
    }
    
    close() {
        this.isOpen = false;
        document.body.style.overflow = '';
        
        // Remove modal
        const modal = document.getElementById('command-palette-modal');
        if (modal) {
            modal.remove();
        }
    }
    
    createModal() {
        const modal = document.createElement('div');
        modal.id = 'command-palette-modal';
        modal.className = 'command-palette-overlay';
        modal.innerHTML = `
            <div class="command-palette" @click.stop>
                <input 
                    type="text" 
                    class="command-palette-input" 
                    placeholder="Type a command or search..."
                    x-model="search"
                    @input="filterCommands"
                    @keydown.down.prevent="selectNext"
                    @keydown.up.prevent="selectPrevious"
                    @keydown.enter.prevent="executeSelected"
                    @keydown.escape="close"
                    x-ref="commandInput"
                >
                <div class="command-palette-results" x-ref="results">
                    <template x-for="(command, index) in filteredCommands" :key="command.id">
                        <div 
                            class="command-palette-item"
                            :class="{ 'active': index === selectedIndex }"
                            @click="execute(command)"
                            @mouseover="selectedIndex = index"
                        >
                            <svg class="command-palette-item-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <!-- Dynamic icon based on command.icon -->
                            </svg>
                            <div class="command-palette-item-content">
                                <div class="command-palette-item-title" x-text="command.title"></div>
                            </div>
                            <div class="command-palette-item-shortcut" x-show="command.shortcut">
                                <template x-for="key in command.shortcut.split(' ')">
                                    <kbd x-text="key"></kbd>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Initialize Alpine component
        Alpine.data('commandPalette', () => ({
            search: '',
            filteredCommands: this.commands,
            selectedIndex: this.selectedIndex,
            
            filterCommands() {
                if (this.search.trim() === '') {
                    this.filteredCommands = this.commands;
                } else {
                    const results = this.fuse.search(this.search);
                    this.filteredCommands = results.map(r => r.item);
                }
                this.selectedIndex = 0;
            },
            
            selectNext() {
                this.selectedIndex = Math.min(this.selectedIndex + 1, this.filteredCommands.length - 1);
                this.scrollToSelected();
            },
            
            selectPrevious() {
                this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                this.scrollToSelected();
            },
            
            executeSelected() {
                if (this.filteredCommands[this.selectedIndex]) {
                    this.execute(this.filteredCommands[this.selectedIndex]);
                }
            },
            
            execute(command) {
                command.action();
                this.close();
            },
            
            close() {
                window.commandPalette.close();
            },
            
            scrollToSelected() {
                const items = this.$refs.results.querySelectorAll('.command-palette-item');
                if (items[this.selectedIndex]) {
                    items[this.selectedIndex].scrollIntoView({ block: 'nearest' });
                }
            }
        }));
        
        // Focus input
        setTimeout(() => {
            const input = modal.querySelector('input');
            if (input) input.focus();
        }, 100);
        
        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.close();
            }
        });
    }
    
    switchView(view) {
        window.livewire.emit('switchView', view);
    }
    
    applyFilter(filter) {
        window.livewire.emit('applySmartFilter', filter);
    }
    
    export(format) {
        window.livewire.emit('exportData', format);
    }
    
    toggleDarkMode() {
        document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', document.documentElement.classList.contains('dark') ? 'dark' : 'light');
    }
    
    showShortcuts() {
        window.dispatchEvent(new CustomEvent('show-keyboard-shortcuts'));
    }
    
    focusSearch(resource) {
        const searchInput = document.querySelector(`[data-resource="${resource}"] input[type="search"]`);
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
}

// Smart Filter System
class SmartFilter {
    constructor() {
        this.naturalLanguagePatterns = [
            // Date patterns
            { pattern: /today/i, filter: { field: 'created_at', operator: 'date', value: 'today' } },
            { pattern: /yesterday/i, filter: { field: 'created_at', operator: 'date', value: 'yesterday' } },
            { pattern: /this week/i, filter: { field: 'created_at', operator: 'between', value: ['week_start', 'week_end'] } },
            { pattern: /last week/i, filter: { field: 'created_at', operator: 'between', value: ['last_week_start', 'last_week_end'] } },
            { pattern: /this month/i, filter: { field: 'created_at', operator: 'between', value: ['month_start', 'month_end'] } },
            { pattern: /last month/i, filter: { field: 'created_at', operator: 'between', value: ['last_month_start', 'last_month_end'] } },
            
            // Status patterns
            { pattern: /pending/i, filter: { field: 'status', operator: '=', value: 'pending' } },
            { pattern: /completed/i, filter: { field: 'status', operator: '=', value: 'completed' } },
            { pattern: /cancelled/i, filter: { field: 'status', operator: '=', value: 'cancelled' } },
            { pattern: /active/i, filter: { field: 'status', operator: '=', value: 'active' } },
            
            // Sentiment patterns
            { pattern: /positive/i, filter: { field: 'sentiment', operator: '=', value: 'positive' } },
            { pattern: /negative/i, filter: { field: 'sentiment', operator: '=', value: 'negative' } },
            { pattern: /neutral/i, filter: { field: 'sentiment', operator: '=', value: 'neutral' } },
            
            // Urgency patterns
            { pattern: /urgent/i, filter: { field: 'urgency', operator: '=', value: 'high' } },
            { pattern: /high priority/i, filter: { field: 'priority', operator: '=', value: 'high' } },
            
            // Customer patterns
            { pattern: /new customers?/i, filter: { field: 'is_new', operator: '=', value: true } },
            { pattern: /vip/i, filter: { field: 'customer_type', operator: '=', value: 'vip' } },
            
            // Call patterns
            { pattern: /long calls?/i, filter: { field: 'duration_sec', operator: '>', value: 300 } },
            { pattern: /short calls?/i, filter: { field: 'duration_sec', operator: '<', value: 60 } },
            { pattern: /missed/i, filter: { field: 'call_status', operator: '=', value: 'missed' } },
            
            // Appointment patterns
            { pattern: /upcoming/i, filter: { field: 'starts_at', operator: '>', value: 'now' } },
            { pattern: /past/i, filter: { field: 'starts_at', operator: '<', value: 'now' } },
            { pattern: /no ?shows?/i, filter: { field: 'status', operator: '=', value: 'no_show' } },
        ];
    }
    
    parse(query) {
        const filters = [];
        const lowercaseQuery = query.toLowerCase();
        
        for (const pattern of this.naturalLanguagePatterns) {
            if (pattern.pattern.test(lowercaseQuery)) {
                filters.push({
                    ...pattern.filter,
                    id: `filter-${Date.now()}-${Math.random()}`,
                    label: this.generateLabel(pattern.filter)
                });
            }
        }
        
        return filters;
    }
    
    generateLabel(filter) {
        const fieldLabels = {
            created_at: 'Created',
            status: 'Status',
            sentiment: 'Sentiment',
            urgency: 'Urgency',
            duration_sec: 'Duration',
            starts_at: 'Starts',
        };
        
        const operatorLabels = {
            '=': 'is',
            '>': 'greater than',
            '<': 'less than',
            'between': 'between',
            'date': 'on',
        };
        
        const field = fieldLabels[filter.field] || filter.field;
        const operator = operatorLabels[filter.operator] || filter.operator;
        const value = Array.isArray(filter.value) ? filter.value.join(' and ') : filter.value;
        
        return `${field} ${operator} ${value}`;
    }
}

// Inline Editor System
class InlineEditor {
    constructor() {
        this.activeCell = null;
        this.originalValue = null;
    }
    
    enable(cell, field, recordId) {
        if (this.activeCell) {
            this.cancel();
        }
        
        this.activeCell = cell;
        this.originalValue = cell.textContent.trim();
        
        const input = this.createInput(field, this.originalValue);
        
        cell.classList.add('editing');
        cell.innerHTML = '';
        cell.appendChild(input);
        
        input.focus();
        input.select();
        
        // Event listeners
        input.addEventListener('blur', () => this.save(field, recordId));
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.save(field, recordId);
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.cancel();
            }
        });
    }
    
    createInput(field, value) {
        const fieldTypes = {
            email: 'email',
            phone: 'tel',
            number: 'number',
            date: 'date',
            time: 'time',
            datetime: 'datetime-local',
        };
        
        const input = document.createElement('input');
        input.type = fieldTypes[field] || 'text';
        input.value = value;
        input.className = 'inline-editor-input';
        
        return input;
    }
    
    save(field, recordId) {
        if (!this.activeCell) return;
        
        const input = this.activeCell.querySelector('input');
        const newValue = input.value.trim();
        
        if (newValue !== this.originalValue) {
            // Save via Livewire
            window.livewire.emit('updateField', {
                recordId,
                field,
                value: newValue
            });
            
            // Show loading state
            this.activeCell.classList.add('ultimate-loading');
        }
        
        // Restore cell
        this.activeCell.classList.remove('editing');
        this.activeCell.textContent = newValue || this.originalValue;
        
        this.activeCell = null;
        this.originalValue = null;
    }
    
    cancel() {
        if (!this.activeCell) return;
        
        this.activeCell.classList.remove('editing');
        this.activeCell.textContent = this.originalValue;
        
        this.activeCell = null;
        this.originalValue = null;
    }
}

// Initialize Alpine components
document.addEventListener('alpine:init', () => {
    // Main Ultimate Resource Component
    Alpine.data('ultimateResource', () => ({
        // View system
        currentView: Alpine.$persist('table').as('ultimate-view'),
        availableViews: ['table', 'grid', 'kanban', 'calendar', 'timeline'],
        
        // Selection system
        selectedRecords: Alpine.$persist([]).as('selected-records'),
        isMultiSelecting: false,
        lastSelectedIndex: null,
        
        // Command palette
        commandPalette: null,
        
        // Smart filter
        smartFilter: null,
        smartFilterQuery: '',
        smartFilterSuggestions: [],
        
        // Inline editor
        inlineEditor: null,
        
        // Drag and drop
        draggedItem: null,
        
        // Real-time updates
        updateInterval: null,
        
        init() {
            // Initialize systems
            this.commandPalette = new CommandPalette();
            this.smartFilter = new SmartFilter();
            this.inlineEditor = new InlineEditor();
            
            // Setup keyboard shortcuts
            this.setupKeyboardShortcuts();
            
            // Setup drag and drop for kanban view
            if (this.currentView === 'kanban') {
                this.setupKanbanDragDrop();
            }
            
            // Setup real-time updates
            this.startRealTimeUpdates();
            
            // Setup multi-select
            this.setupMultiSelect();
            
            // Listen for Livewire events
            this.setupLivewireListeners();
            
            // Make command palette globally accessible
            window.commandPalette = this.commandPalette;
        },
        
        setupKeyboardShortcuts() {
            // Command palette
            hotkeys('cmd+k, ctrl+k', (e) => {
                e.preventDefault();
                this.commandPalette.open();
            });
            
            // View switching
            hotkeys('cmd+1, ctrl+1', () => this.switchView('table'));
            hotkeys('cmd+2, ctrl+2', () => this.switchView('grid'));
            hotkeys('cmd+3, ctrl+3', () => this.switchView('kanban'));
            hotkeys('cmd+4, ctrl+4', () => this.switchView('calendar'));
            hotkeys('cmd+5, ctrl+5', () => this.switchView('timeline'));
            
            // Navigation
            hotkeys('j', () => this.selectNext());
            hotkeys('k', () => this.selectPrevious());
            hotkeys('enter', () => this.openSelected());
            hotkeys('e', () => this.editSelected());
            
            // Selection
            hotkeys('cmd+a, ctrl+a', (e) => {
                e.preventDefault();
                this.selectAll();
            });
            hotkeys('escape', () => this.clearSelection());
            
            // Actions
            hotkeys('cmd+d, ctrl+d', (e) => {
                e.preventDefault();
                this.duplicateSelected();
            });
            hotkeys('delete, backspace', () => this.deleteSelected());
            
            // Smart filter
            hotkeys('/', (e) => {
                e.preventDefault();
                this.focusSmartFilter();
            });
            
            // Help
            hotkeys('?', () => this.showKeyboardShortcuts());
        },
        
        setupMultiSelect() {
            this.$el.addEventListener('click', (e) => {
                const row = e.target.closest('[data-record-id]');
                if (!row) return;
                
                const recordId = row.dataset.recordId;
                const index = Array.from(row.parentElement.children).indexOf(row);
                
                if (e.shiftKey && this.lastSelectedIndex !== null) {
                    this.selectRange(this.lastSelectedIndex, index);
                } else if (e.metaKey || e.ctrlKey) {
                    this.toggleSelect(recordId);
                } else {
                    this.selectSingle(recordId);
                }
                
                this.lastSelectedIndex = index;
            });
        },
        
        setupKanbanDragDrop() {
            const columns = this.$el.querySelectorAll('.ultimate-kanban-column');
            
            columns.forEach(column => {
                const itemsContainer = column.querySelector('.ultimate-kanban-items');
                
                new Sortable(itemsContainer, {
                    group: 'kanban',
                    animation: 150,
                    ghostClass: 'dragging',
                    dragClass: 'dragging',
                    handle: '.ultimate-kanban-item',
                    
                    onStart: (evt) => {
                        this.draggedItem = evt.item.dataset.recordId;
                    },
                    
                    onEnd: (evt) => {
                        const recordId = this.draggedItem;
                        const newStatus = evt.to.dataset.status;
                        const newIndex = evt.newIndex;
                        
                        if (recordId && newStatus) {
                            this.$wire.updateRecordStatus(recordId, newStatus, newIndex);
                        }
                        
                        this.draggedItem = null;
                    }
                });
            });
        },
        
        setupLivewireListeners() {
            Livewire.on('recordUpdated', (data) => {
                // Update UI after inline edit
                const cell = document.querySelector(`[data-record-id="${data.recordId}"] [data-field="${data.field}"]`);
                if (cell) {
                    cell.classList.remove('ultimate-loading');
                    cell.textContent = data.value;
                }
            });
            
            Livewire.on('viewChanged', (view) => {
                this.currentView = view;
                
                // Reinitialize view-specific features
                if (view === 'kanban') {
                    this.$nextTick(() => this.setupKanbanDragDrop());
                }
            });
        },
        
        startRealTimeUpdates() {
            // Poll for updates every 10 seconds
            this.updateInterval = setInterval(() => {
                if (document.visibilityState === 'visible') {
                    this.$wire.refresh();
                }
            }, 10000);
            
            // Clean up on destroy
            this.$cleanup(() => {
                if (this.updateInterval) {
                    clearInterval(this.updateInterval);
                }
            });
        },
        
        switchView(view) {
            if (!this.availableViews.includes(view)) return;
            
            this.currentView = view;
            this.$wire.switchView(view);
            
            // Animate transition
            this.$el.classList.add('view-transitioning');
            setTimeout(() => {
                this.$el.classList.remove('view-transitioning');
            }, 300);
        },
        
        // Selection methods
        selectSingle(recordId) {
            this.clearSelection();
            this.selectedRecords = [recordId];
            this.highlightSelected();
        },
        
        toggleSelect(recordId) {
            const index = this.selectedRecords.indexOf(recordId);
            if (index > -1) {
                this.selectedRecords.splice(index, 1);
            } else {
                this.selectedRecords.push(recordId);
            }
            this.highlightSelected();
        },
        
        selectRange(startIndex, endIndex) {
            const rows = this.$el.querySelectorAll('[data-record-id]');
            const start = Math.min(startIndex, endIndex);
            const end = Math.max(startIndex, endIndex);
            
            this.selectedRecords = [];
            for (let i = start; i <= end; i++) {
                if (rows[i]) {
                    this.selectedRecords.push(rows[i].dataset.recordId);
                }
            }
            this.highlightSelected();
        },
        
        selectAll() {
            const rows = this.$el.querySelectorAll('[data-record-id]');
            this.selectedRecords = Array.from(rows).map(row => row.dataset.recordId);
            this.highlightSelected();
        },
        
        clearSelection() {
            this.selectedRecords = [];
            this.highlightSelected();
        },
        
        highlightSelected() {
            const rows = this.$el.querySelectorAll('[data-record-id]');
            rows.forEach(row => {
                if (this.selectedRecords.includes(row.dataset.recordId)) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            });
            
            // Update selection count
            this.$dispatch('selection-changed', { count: this.selectedRecords.length });
        },
        
        selectNext() {
            const rows = this.$el.querySelectorAll('[data-record-id]');
            const currentIndex = Array.from(rows).findIndex(row => 
                row.classList.contains('selected')
            );
            
            if (currentIndex < rows.length - 1) {
                this.selectSingle(rows[currentIndex + 1].dataset.recordId);
                rows[currentIndex + 1].scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        },
        
        selectPrevious() {
            const rows = this.$el.querySelectorAll('[data-record-id]');
            const currentIndex = Array.from(rows).findIndex(row => 
                row.classList.contains('selected')
            );
            
            if (currentIndex > 0) {
                this.selectSingle(rows[currentIndex - 1].dataset.recordId);
                rows[currentIndex - 1].scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        },
        
        // Actions
        openSelected() {
            if (this.selectedRecords.length === 1) {
                this.$wire.viewRecord(this.selectedRecords[0]);
            }
        },
        
        editSelected() {
            if (this.selectedRecords.length === 1) {
                this.$wire.editRecord(this.selectedRecords[0]);
            }
        },
        
        deleteSelected() {
            if (this.selectedRecords.length > 0) {
                if (confirm(`Delete ${this.selectedRecords.length} record(s)?`)) {
                    this.$wire.deleteRecords(this.selectedRecords);
                }
            }
        },
        
        duplicateSelected() {
            if (this.selectedRecords.length > 0) {
                this.$wire.duplicateRecords(this.selectedRecords);
            }
        },
        
        // Smart filter
        focusSmartFilter() {
            const input = this.$refs.smartFilterInput;
            if (input) {
                input.focus();
                input.select();
            }
        },
        
        parseSmartFilter() {
            const filters = this.smartFilter.parse(this.smartFilterQuery);
            this.$wire.applySmartFilters(filters);
        },
        
        // Inline editing
        enableInlineEdit(cell, field, recordId) {
            if (!this.inlineEditor) return;
            this.inlineEditor.enable(cell, field, recordId);
        },
        
        // Keyboard shortcuts modal
        showKeyboardShortcuts() {
            this.$dispatch('show-keyboard-shortcuts');
        }
    }));
    
    // Keyboard Shortcuts Modal Component
    Alpine.data('keyboardShortcuts', () => ({
        isOpen: false,
        
        shortcuts: [
            { category: 'Navigation', items: [
                { keys: ['⌘', 'K'], description: 'Open command palette' },
                { keys: ['G', 'C'], description: 'Go to Calls' },
                { keys: ['G', 'A'], description: 'Go to Appointments' },
                { keys: ['G', 'U'], description: 'Go to Customers' },
                { keys: ['J'], description: 'Select next item' },
                { keys: ['K'], description: 'Select previous item' },
            ]},
            { category: 'Views', items: [
                { keys: ['⌘', '1'], description: 'Table view' },
                { keys: ['⌘', '2'], description: 'Grid view' },
                { keys: ['⌘', '3'], description: 'Kanban view' },
                { keys: ['⌘', '4'], description: 'Calendar view' },
                { keys: ['⌘', '5'], description: 'Timeline view' },
            ]},
            { category: 'Selection', items: [
                { keys: ['⌘', 'A'], description: 'Select all' },
                { keys: ['Shift', 'Click'], description: 'Range select' },
                { keys: ['⌘', 'Click'], description: 'Multi select' },
                { keys: ['Esc'], description: 'Clear selection' },
            ]},
            { category: 'Actions', items: [
                { keys: ['Enter'], description: 'Open selected' },
                { keys: ['E'], description: 'Edit selected' },
                { keys: ['⌘', 'D'], description: 'Duplicate selected' },
                { keys: ['Delete'], description: 'Delete selected' },
                { keys: ['/'], description: 'Focus search' },
                { keys: ['?'], description: 'Show this help' },
            ]},
        ],
        
        init() {
            window.addEventListener('show-keyboard-shortcuts', () => {
                this.isOpen = true;
            });
        }
    }));
});

// Export for external use
export { CommandPalette, SmartFilter, InlineEditor };