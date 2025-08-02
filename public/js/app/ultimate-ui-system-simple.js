// Ultimate UI System - Simplified version for Filament
import Sortable from 'sortablejs';
import Fuse from 'fuse.js';
import hotkeys from 'hotkeys-js';
import { createPopper } from '@popperjs/core';

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
        ];
        
        // Initialize Fuse for fuzzy search
        this.fuse = new Fuse(this.commands, {
            keys: ['title', 'shortcut'],
            threshold: 0.3
        });
    }
    
    open() {
        this.isOpen = true;
        // Dispatch event for Alpine component
        window.dispatchEvent(new CustomEvent('open-command-palette'));
    }
    
    close() {
        this.isOpen = false;
        window.dispatchEvent(new CustomEvent('close-command-palette'));
    }
    
    switchView(view) {
        window.dispatchEvent(new CustomEvent('switch-view', { detail: { view } }));
        this.close();
    }
    
    focusSearch(resource) {
        // Implementation depends on the specific resource
        this.close();
    }
    
    applyFilter(filter) {
        window.dispatchEvent(new CustomEvent('apply-filter', { detail: { filter } }));
        this.close();
    }
}

// Smart Filter System
class SmartFilter {
    constructor() {
        this.naturalLanguagePatterns = [
            { pattern: /today|heute/i, filter: { type: 'date', value: 'today' } },
            { pattern: /yesterday|gestern/i, filter: { type: 'date', value: 'yesterday' } },
            { pattern: /this week|diese woche/i, filter: { type: 'date', value: 'this_week' } },
            { pattern: /last week|letzte woche/i, filter: { type: 'date', value: 'last_week' } },
            { pattern: /this month|diesen monat/i, filter: { type: 'date', value: 'this_month' } },
            { pattern: /positive|positiv/i, filter: { type: 'sentiment', value: 'positive' } },
            { pattern: /negative|negativ/i, filter: { type: 'sentiment', value: 'negative' } },
            { pattern: /no show|nicht erschienen/i, filter: { type: 'status', value: 'no_show' } },
            { pattern: /completed|abgeschlossen/i, filter: { type: 'status', value: 'completed' } },
            { pattern: /scheduled|geplant/i, filter: { type: 'status', value: 'scheduled' } },
        ];
    }
    
    parse(query) {
        const filters = [];
        const lowerQuery = query.toLowerCase();
        
        for (const { pattern, filter } of this.naturalLanguagePatterns) {
            if (pattern.test(lowerQuery)) {
                filters.push(filter);
            }
        }
        
        return filters;
    }
    
    getSuggestions(query) {
        if (query.length < 2) return [];
        
        const suggestions = [];
        const lowerQuery = query.toLowerCase();
        
        if ('heute'.startsWith(lowerQuery) || 'today'.startsWith(lowerQuery)) {
            suggestions.push('heute');
        }
        if ('gestern'.startsWith(lowerQuery) || 'yesterday'.startsWith(lowerQuery)) {
            suggestions.push('gestern');
        }
        if ('diese woche'.startsWith(lowerQuery) || 'this week'.startsWith(lowerQuery)) {
            suggestions.push('diese woche');
        }
        if ('positive'.startsWith(lowerQuery) || 'positiv'.startsWith(lowerQuery)) {
            suggestions.push('positive Anrufe');
        }
        
        return suggestions.slice(0, 5);
    }
}

// Inline Editor System
class InlineEditor {
    constructor() {
        this.activeEditor = null;
        this.originalValue = null;
    }
    
    activate(element, field, recordId) {
        if (this.activeEditor) {
            this.save();
        }
        
        this.activeEditor = element;
        this.originalValue = element.textContent;
        
        element.contentEditable = true;
        element.focus();
        
        // Select all text
        const range = document.createRange();
        range.selectNodeContents(element);
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        
        // Add event listeners
        element.addEventListener('blur', () => this.save());
        element.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.save();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.cancel();
            }
        });
    }
    
    save() {
        if (!this.activeEditor) return;
        
        const newValue = this.activeEditor.textContent;
        const field = this.activeEditor.dataset.field;
        const recordId = this.activeEditor.closest('[data-record-id]')?.dataset.recordId;
        
        if (newValue !== this.originalValue && recordId) {
            window.dispatchEvent(new CustomEvent('inline-edit-save', {
                detail: { field, recordId, value: newValue }
            }));
        }
        
        this.activeEditor.contentEditable = false;
        this.activeEditor = null;
        this.originalValue = null;
    }
    
    cancel() {
        if (!this.activeEditor) return;
        
        this.activeEditor.textContent = this.originalValue;
        this.activeEditor.contentEditable = false;
        this.activeEditor = null;
        this.originalValue = null;
    }
}

// Initialize Ultimate UI System
window.CommandPalette = CommandPalette;
window.SmartFilter = SmartFilter;
window.InlineEditor = InlineEditor;

// Initialize keyboard shortcuts
document.addEventListener('DOMContentLoaded', () => {
    // Command Palette shortcut
    hotkeys('cmd+k, ctrl+k', (e) => {
        e.preventDefault();
        if (window.commandPalette) {
            window.commandPalette.open();
        }
    });
    
    // View switching shortcuts
    hotkeys('cmd+1, ctrl+1', (e) => {
        e.preventDefault();
        window.dispatchEvent(new CustomEvent('switch-view', { detail: { view: 'table' } }));
    });
    
    hotkeys('cmd+2, ctrl+2', (e) => {
        e.preventDefault();
        window.dispatchEvent(new CustomEvent('switch-view', { detail: { view: 'grid' } }));
    });
    
    hotkeys('cmd+3, ctrl+3', (e) => {
        e.preventDefault();
        window.dispatchEvent(new CustomEvent('switch-view', { detail: { view: 'kanban' } }));
    });
    
    hotkeys('cmd+4, ctrl+4', (e) => {
        e.preventDefault();
        window.dispatchEvent(new CustomEvent('switch-view', { detail: { view: 'calendar' } }));
    });
    
    hotkeys('cmd+5, ctrl+5', (e) => {
        e.preventDefault();
        window.dispatchEvent(new CustomEvent('switch-view', { detail: { view: 'timeline' } }));
    });
});

// Export for use in Alpine components
window.ultimateUI = {
    Sortable,
    Fuse,
    createPopper,
    hotkeys
};