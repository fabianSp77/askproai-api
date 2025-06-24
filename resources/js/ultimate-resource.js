// Ultimate Resource JavaScript Module
// Provides advanced interactions for the best-in-class UI/UX

import Alpine from 'alpinejs';
import hotkeys from 'hotkeys-js';
import { VirtualScroller } from './virtual-scroller';
import { CommandPalette } from './command-palette';
import { InlineEditor } from './inline-editor';
import { SmartFilter } from './smart-filter';

// Register Alpine components
document.addEventListener('alpine:init', () => {
    
    // Main Ultimate Table Component
    Alpine.data('ultimateTable', () => ({
        currentView: 'table',
        selectedRecords: [],
        isMultiSelecting: false,
        lastSelectedIndex: null,
        virtualScroller: null,
        commandPalette: null,
        inlineEditor: null,
        smartFilter: null,
        
        init() {
            // Initialize virtual scrolling if enabled
            if (this.$el.dataset.virtualScroll === 'true') {
                this.virtualScroller = new VirtualScroller(this.$el);
            }
            
            // Initialize command palette
            this.commandPalette = new CommandPalette();
            
            // Initialize inline editor if enabled
            if (this.$el.dataset.inlineEdit === 'true') {
                this.inlineEditor = new InlineEditor(this.$el);
            }
            
            // Initialize smart filter
            this.smartFilter = new SmartFilter();
            
            // Setup keyboard shortcuts
            this.setupKeyboardShortcuts();
            
            // Setup drag and drop
            this.setupDragAndDrop();
            
            // Setup hover previews
            this.setupHoverPreviews();
            
            // Enable multi-select with shift/cmd
            this.setupMultiSelect();
        },
        
        setupKeyboardShortcuts() {
            // Command palette
            hotkeys('cmd+k, ctrl+k', (e) => {
                e.preventDefault();
                this.openCommandPalette();
            });
            
            // View switching
            hotkeys('cmd+1, ctrl+1', () => this.switchView('table'));
            hotkeys('cmd+2, ctrl+2', () => this.switchView('grid'));
            hotkeys('cmd+3, ctrl+3', () => this.switchView('kanban'));
            hotkeys('cmd+4, ctrl+4', () => this.switchView('calendar'));
            hotkeys('cmd+5, ctrl+5', () => this.switchView('timeline'));
            
            // Navigation
            hotkeys('j, down', () => this.selectNext());
            hotkeys('k, up', () => this.selectPrevious());
            hotkeys('enter', () => this.openSelected());
            hotkeys('cmd+enter, ctrl+enter', () => this.editSelected());
            
            // Actions
            hotkeys('cmd+a, ctrl+a', (e) => {
                e.preventDefault();
                this.selectAll();
            });
            hotkeys('escape', () => this.clearSelection());
            hotkeys('delete, backspace', () => this.deleteSelected());
            
            // Quick actions
            hotkeys('cmd+e, ctrl+e', () => this.quickEdit());
            hotkeys('cmd+d, ctrl+d', () => this.duplicateSelected());
            hotkeys('cmd+/, ctrl+/', () => this.showKeyboardShortcuts());
        },
        
        setupDragAndDrop() {
            const draggables = this.$el.querySelectorAll('[draggable="true"]');
            
            draggables.forEach(draggable => {
                draggable.addEventListener('dragstart', (e) => {
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/html', e.target.innerHTML);
                    e.target.classList.add('dragging');
                });
                
                draggable.addEventListener('dragend', (e) => {
                    e.target.classList.remove('dragging');
                });
            });
            
            // Setup drop zones
            const dropZones = this.$el.querySelectorAll('.drop-zone');
            dropZones.forEach(zone => {
                zone.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    zone.classList.add('drag-over');
                });
                
                zone.addEventListener('dragleave', () => {
                    zone.classList.remove('drag-over');
                });
                
                zone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    zone.classList.remove('drag-over');
                    this.handleDrop(e, zone);
                });
            });
        },
        
        setupHoverPreviews() {
            let hoverTimeout;
            const previewDelay = 500;
            
            this.$el.addEventListener('mouseover', (e) => {
                const previewTarget = e.target.closest('[data-preview]');
                if (!previewTarget) return;
                
                hoverTimeout = setTimeout(() => {
                    this.showPreview(previewTarget);
                }, previewDelay);
            });
            
            this.$el.addEventListener('mouseout', (e) => {
                clearTimeout(hoverTimeout);
                const previewTarget = e.target.closest('[data-preview]');
                if (previewTarget) {
                    this.hidePreview();
                }
            });
        },
        
        setupMultiSelect() {
            this.$el.addEventListener('click', (e) => {
                const row = e.target.closest('tr[data-record-id]');
                if (!row) return;
                
                const recordId = row.dataset.recordId;
                const index = Array.from(row.parentElement.children).indexOf(row);
                
                if (e.shiftKey && this.lastSelectedIndex !== null) {
                    // Range select
                    this.selectRange(this.lastSelectedIndex, index);
                } else if (e.metaKey || e.ctrlKey) {
                    // Multi select
                    this.toggleSelect(recordId);
                } else {
                    // Single select
                    this.selectSingle(recordId);
                }
                
                this.lastSelectedIndex = index;
            });
        },
        
        switchView(view) {
            this.currentView = view;
            this.$wire.switchView(view);
            
            // Animate view transition
            this.$el.classList.add('view-transitioning');
            setTimeout(() => {
                this.$el.classList.remove('view-transitioning');
            }, 300);
        },
        
        openCommandPalette() {
            this.commandPalette.open({
                onSelect: (command) => {
                    this.executeCommand(command);
                }
            });
        },
        
        selectNext() {
            const rows = this.$el.querySelectorAll('tr[data-record-id]');
            const currentIndex = Array.from(rows).findIndex(row => 
                row.classList.contains('selected')
            );
            
            if (currentIndex < rows.length - 1) {
                this.selectSingle(rows[currentIndex + 1].dataset.recordId);
                rows[currentIndex + 1].scrollIntoView({ block: 'center' });
            }
        },
        
        selectPrevious() {
            const rows = this.$el.querySelectorAll('tr[data-record-id]');
            const currentIndex = Array.from(rows).findIndex(row => 
                row.classList.contains('selected')
            );
            
            if (currentIndex > 0) {
                this.selectSingle(rows[currentIndex - 1].dataset.recordId);
                rows[currentIndex - 1].scrollIntoView({ block: 'center' });
            }
        },
        
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
            const rows = this.$el.querySelectorAll('tr[data-record-id]');
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
            const rows = this.$el.querySelectorAll('tr[data-record-id]');
            this.selectedRecords = Array.from(rows).map(row => row.dataset.recordId);
            this.highlightSelected();
        },
        
        clearSelection() {
            this.selectedRecords = [];
            this.highlightSelected();
        },
        
        highlightSelected() {
            const rows = this.$el.querySelectorAll('tr[data-record-id]');
            rows.forEach(row => {
                if (this.selectedRecords.includes(row.dataset.recordId)) {
                    row.classList.add('selected', 'bg-primary-50', 'dark:bg-primary-900/20');
                } else {
                    row.classList.remove('selected', 'bg-primary-50', 'dark:bg-primary-900/20');
                }
            });
            
            // Update selection count
            this.$dispatch('selection-changed', { count: this.selectedRecords.length });
        },
        
        openSelected() {
            if (this.selectedRecords.length === 1) {
                this.$wire.openRecord(this.selectedRecords[0]);
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
        
        quickEdit() {
            if (this.selectedRecords.length > 0) {
                this.$wire.openQuickEdit(this.selectedRecords);
            }
        },
        
        duplicateSelected() {
            if (this.selectedRecords.length > 0) {
                this.$wire.duplicateRecords(this.selectedRecords);
            }
        },
        
        showKeyboardShortcuts() {
            this.$dispatch('show-keyboard-shortcuts');
        },
        
        handleDrop(e, zone) {
            const recordId = e.dataTransfer.getData('recordId');
            const newStatus = zone.dataset.status;
            
            if (recordId && newStatus) {
                this.$wire.updateRecordStatus(recordId, newStatus);
            }
        },
        
        showPreview(element) {
            const recordId = element.dataset.preview;
            const preview = document.createElement('div');
            preview.className = 'preview-tooltip';
            preview.innerHTML = '<div class="loading">Loading preview...</div>';
            
            document.body.appendChild(preview);
            
            // Position preview
            const rect = element.getBoundingClientRect();
            preview.style.top = `${rect.bottom + 10}px`;
            preview.style.left = `${rect.left}px`;
            
            // Fetch preview data
            fetch(`/api/preview/${recordId}`)
                .then(res => res.json())
                .then(data => {
                    preview.innerHTML = this.renderPreview(data);
                });
        },
        
        hidePreview() {
            const preview = document.querySelector('.preview-tooltip');
            if (preview) {
                preview.remove();
            }
        },
        
        renderPreview(data) {
            // Override in specific resources
            return `<div class="preview-content">${JSON.stringify(data)}</div>`;
        },
        
        executeCommand(command) {
            switch (command.action) {
                case 'create':
                    this.$wire.createNew();
                    break;
                case 'search':
                    this.$wire.search(command.query);
                    break;
                case 'filter':
                    this.$wire.applyFilter(command.filter);
                    break;
                case 'export':
                    this.$wire.export(command.format);
                    break;
                default:
                    this.$wire.executeCommand(command);
            }
        }
    }));
    
    // View Switcher Component
    Alpine.data('viewSwitcher', () => ({
        currentView: Alpine.$persist('table').as('ultimate-view'),
        
        switchView(view) {
            this.currentView = view;
            this.$dispatch('view-changed', { view });
            
            // Close modal
            this.$dispatch('close-modal');
            
            // Reload with new view
            window.livewire.emit('switchView', view);
        }
    }));
    
    // Smart Filter Component
    Alpine.data('smartFilter', () => ({
        query: '',
        filters: [],
        suggestions: [],
        
        parseQuery() {
            // Natural language processing
            const query = this.query.toLowerCase();
            
            // Clear existing suggestions
            this.suggestions = [];
            
            // Date patterns
            if (query.includes('today') || query.includes('yesterday') || query.includes('last week')) {
                this.suggestions.push('created today', 'modified yesterday', 'from last week');
            }
            
            // Status patterns
            if (query.includes('pending') || query.includes('completed') || query.includes('active')) {
                this.suggestions.push('status is pending', 'status is completed', 'is active');
            }
            
            // Sentiment patterns
            if (query.includes('positive') || query.includes('negative') || query.includes('happy')) {
                this.suggestions.push('positive sentiment', 'negative feedback', 'happy customers');
            }
            
            // Parse and create filters
            this.createFiltersFromQuery();
        },
        
        createFiltersFromQuery() {
            const query = this.query.toLowerCase();
            const newFilters = [];
            
            // Date filters
            if (query.includes('today')) {
                newFilters.push({
                    id: 'date-today',
                    field: 'created_at',
                    operator: 'date',
                    value: 'today',
                    label: 'Created today'
                });
            }
            
            if (query.includes('last week')) {
                newFilters.push({
                    id: 'date-week',
                    field: 'created_at',
                    operator: 'between',
                    value: ['last_week_start', 'last_week_end'],
                    label: 'From last week'
                });
            }
            
            // Sentiment filter
            if (query.includes('positive')) {
                newFilters.push({
                    id: 'sentiment-positive',
                    field: 'sentiment',
                    operator: '=',
                    value: 'positive',
                    label: 'Positive sentiment'
                });
            }
            
            this.filters = newFilters;
        },
        
        applySuggestion(suggestion) {
            this.query = suggestion;
            this.parseQuery();
        },
        
        removeFilter(filterId) {
            this.filters = this.filters.filter(f => f.id !== filterId);
        },
        
        applyFilters() {
            this.$wire.applySmartFilters(this.filters);
            this.$dispatch('close-modal');
        }
    }));
});

// Export for use in other modules
export { Alpine };