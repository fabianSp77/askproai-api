// Command Palette - Superhuman-style command interface
export class CommandPalette {
    constructor(options = {}) {
        this.options = options;
        this.isOpen = false;
        this.commands = [];
        this.filteredCommands = [];
        this.selectedIndex = 0;
        this.recentCommands = this.loadRecentCommands();
        
        this.init();
    }
    
    init() {
        // Create palette UI
        this.createPaletteUI();
        
        // Load available commands
        this.loadCommands();
        
        // Setup event listeners
        this.setupEventListeners();
    }
    
    createPaletteUI() {
        // Create overlay
        this.overlay = document.createElement('div');
        this.overlay.className = 'command-palette-overlay';
        this.overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 9999;
            display: none;
            animation: fadeIn 0.15s ease-out;
        `;
        
        // Create palette container
        this.palette = document.createElement('div');
        this.palette.className = 'command-palette';
        this.palette.style.cssText = `
            position: fixed;
            top: 20%;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 600px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: slideDown 0.2s ease-out;
        `;
        
        // Create search input
        this.searchInput = document.createElement('input');
        this.searchInput.type = 'text';
        this.searchInput.className = 'command-palette-search';
        this.searchInput.placeholder = 'Type a command or search...';
        this.searchInput.style.cssText = `
            width: 100%;
            padding: 20px 24px;
            font-size: 18px;
            border: none;
            outline: none;
            background: white;
            font-family: -apple-system, BlinkMacSystemFont, sans-serif;
        `;
        
        // Create results container
        this.resultsContainer = document.createElement('div');
        this.resultsContainer.className = 'command-palette-results';
        this.resultsContainer.style.cssText = `
            max-height: 400px;
            overflow-y: auto;
            border-top: 1px solid #e5e7eb;
        `;
        
        // Create footer
        this.footer = document.createElement('div');
        this.footer.className = 'command-palette-footer';
        this.footer.style.cssText = `
            padding: 12px 24px;
            background: #f9fafb;
            border-top: 1px solid #e5e7eb;
            font-size: 12px;
            color: #6b7280;
            display: flex;
            justify-content: space-between;
        `;
        this.footer.innerHTML = `
            <div>
                <kbd>↑↓</kbd> Navigate
                <kbd>↵</kbd> Select
                <kbd>⌘K</kbd> Open/Close
            </div>
            <div>
                <span class="command-count">0 commands</span>
            </div>
        `;
        
        // Assemble palette
        this.palette.appendChild(this.searchInput);
        this.palette.appendChild(this.resultsContainer);
        this.palette.appendChild(this.footer);
        this.overlay.appendChild(this.palette);
        document.body.appendChild(this.overlay);
    }
    
    loadCommands() {
        // Define available commands
        this.commands = [
            // Navigation
            { id: 'go-calls', label: 'Go to Calls', icon: '📞', category: 'Navigation', action: 'navigate', target: '/admin/calls' },
            { id: 'go-appointments', label: 'Go to Appointments', icon: '📅', category: 'Navigation', action: 'navigate', target: '/admin/appointments' },
            { id: 'go-customers', label: 'Go to Customers', icon: '👥', category: 'Navigation', action: 'navigate', target: '/admin/customers' },
            { id: 'go-dashboard', label: 'Go to Dashboard', icon: '📊', category: 'Navigation', action: 'navigate', target: '/admin' },
            
            // Create actions
            { id: 'create-appointment', label: 'Create New Appointment', icon: '➕', category: 'Create', action: 'create', type: 'appointment' },
            { id: 'create-customer', label: 'Create New Customer', icon: '➕', category: 'Create', action: 'create', type: 'customer' },
            
            // Search actions
            { id: 'search-calls', label: 'Search Calls...', icon: '🔍', category: 'Search', action: 'search', type: 'calls' },
            { id: 'search-customers', label: 'Search Customers...', icon: '🔍', category: 'Search', action: 'search', type: 'customers' },
            { id: 'search-appointments', label: 'Search Appointments...', icon: '🔍', category: 'Search', action: 'search', type: 'appointments' },
            
            // Quick filters
            { id: 'filter-today', label: 'Show Today\'s Items', icon: '📆', category: 'Filter', action: 'filter', filter: 'today' },
            { id: 'filter-week', label: 'Show This Week', icon: '📆', category: 'Filter', action: 'filter', filter: 'week' },
            { id: 'filter-pending', label: 'Show Pending Items', icon: '⏳', category: 'Filter', action: 'filter', filter: 'pending' },
            { id: 'filter-urgent', label: 'Show Urgent Items', icon: '🚨', category: 'Filter', action: 'filter', filter: 'urgent' },
            
            // Export actions
            { id: 'export-csv', label: 'Export to CSV', icon: '📊', category: 'Export', action: 'export', format: 'csv' },
            { id: 'export-excel', label: 'Export to Excel', icon: '📊', category: 'Export', action: 'export', format: 'xlsx' },
            { id: 'export-pdf', label: 'Export to PDF', icon: '📄', category: 'Export', action: 'export', format: 'pdf' },
            
            // View actions
            { id: 'view-table', label: 'Switch to Table View', icon: '📋', category: 'View', action: 'view', view: 'table', shortcut: '⌘1' },
            { id: 'view-grid', label: 'Switch to Grid View', icon: '⊞', category: 'View', action: 'view', view: 'grid', shortcut: '⌘2' },
            { id: 'view-kanban', label: 'Switch to Kanban View', icon: '📌', category: 'View', action: 'view', view: 'kanban', shortcut: '⌘3' },
            { id: 'view-calendar', label: 'Switch to Calendar View', icon: '📅', category: 'View', action: 'view', view: 'calendar', shortcut: '⌘4' },
            { id: 'view-timeline', label: 'Switch to Timeline View', icon: '📈', category: 'View', action: 'view', view: 'timeline', shortcut: '⌘5' },
            
            // Settings & preferences
            { id: 'settings', label: 'Open Settings', icon: '⚙️', category: 'Settings', action: 'navigate', target: '/admin/settings' },
            { id: 'shortcuts', label: 'Keyboard Shortcuts', icon: '⌨️', category: 'Help', action: 'show-shortcuts' },
            { id: 'help', label: 'Help & Documentation', icon: '❓', category: 'Help', action: 'navigate', target: '/help' },
            
            // Advanced actions
            { id: 'bulk-edit', label: 'Bulk Edit Selected', icon: '✏️', category: 'Actions', action: 'bulk-edit', shortcut: '⌘E' },
            { id: 'duplicate', label: 'Duplicate Selected', icon: '📑', category: 'Actions', action: 'duplicate', shortcut: '⌘D' },
            { id: 'archive', label: 'Archive Selected', icon: '📦', category: 'Actions', action: 'archive' },
            
            // AI-powered commands
            { id: 'ai-summary', label: 'AI: Summarize Page', icon: '🤖', category: 'AI', action: 'ai-action', type: 'summary' },
            { id: 'ai-insights', label: 'AI: Generate Insights', icon: '🤖', category: 'AI', action: 'ai-action', type: 'insights' },
            { id: 'ai-predict', label: 'AI: Predict Trends', icon: '🤖', category: 'AI', action: 'ai-action', type: 'predict' }
        ];
        
        // Add dynamic commands based on context
        this.addContextualCommands();
        
        // Sort by recent usage and category
        this.sortCommands();
    }
    
    addContextualCommands() {
        // Get current page context
        const currentPath = window.location.pathname;
        
        if (currentPath.includes('/calls')) {
            this.commands.unshift(
                { id: 'analyze-sentiment', label: 'Analyze Call Sentiment', icon: '😊', category: 'Contextual', action: 'analyze', type: 'sentiment' },
                { id: 'transcribe-all', label: 'Transcribe All Calls', icon: '📝', category: 'Contextual', action: 'bulk-action', type: 'transcribe' }
            );
        }
        
        if (currentPath.includes('/appointments')) {
            this.commands.unshift(
                { id: 'send-reminders', label: 'Send Appointment Reminders', icon: '🔔', category: 'Contextual', action: 'bulk-action', type: 'remind' },
                { id: 'reschedule', label: 'Reschedule Selected', icon: '🔄', category: 'Contextual', action: 'reschedule' }
            );
        }
        
        if (currentPath.includes('/customers')) {
            this.commands.unshift(
                { id: 'merge-duplicates', label: 'Find & Merge Duplicates', icon: '🔗', category: 'Contextual', action: 'merge-duplicates' },
                { id: 'export-contacts', label: 'Export as vCard', icon: '📇', category: 'Contextual', action: 'export', format: 'vcard' }
            );
        }
    }
    
    sortCommands() {
        // Sort by: Recent > Contextual > Category > Alphabetical
        this.commands.sort((a, b) => {
            // Recent commands first
            const aRecent = this.recentCommands.includes(a.id) ? 0 : 1;
            const bRecent = this.recentCommands.includes(b.id) ? 0 : 1;
            if (aRecent !== bRecent) return aRecent - bRecent;
            
            // Then contextual
            const aContextual = a.category === 'Contextual' ? 0 : 1;
            const bContextual = b.category === 'Contextual' ? 0 : 1;
            if (aContextual !== bContextual) return aContextual - bContextual;
            
            // Then by category
            if (a.category !== b.category) {
                return a.category.localeCompare(b.category);
            }
            
            // Finally alphabetical
            return a.label.localeCompare(b.label);
        });
    }
    
    setupEventListeners() {
        // Search input
        this.searchInput.addEventListener('input', (e) => {
            this.filterCommands(e.target.value);
        });
        
        // Keyboard navigation
        this.searchInput.addEventListener('keydown', (e) => {
            switch (e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    this.selectNext();
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    this.selectPrevious();
                    break;
                case 'Enter':
                    e.preventDefault();
                    this.executeSelected();
                    break;
                case 'Escape':
                    this.close();
                    break;
            }
        });
        
        // Click outside to close
        this.overlay.addEventListener('click', (e) => {
            if (e.target === this.overlay) {
                this.close();
            }
        });
        
        // Results click
        this.resultsContainer.addEventListener('click', (e) => {
            const commandEl = e.target.closest('.command-item');
            if (commandEl) {
                const commandId = commandEl.dataset.commandId;
                this.executeCommand(commandId);
            }
        });
        
        // Results hover
        this.resultsContainer.addEventListener('mouseover', (e) => {
            const commandEl = e.target.closest('.command-item');
            if (commandEl) {
                const index = Array.from(this.resultsContainer.children).indexOf(commandEl);
                this.selectedIndex = index;
                this.updateSelection();
            }
        });
    }
    
    open(options = {}) {
        this.isOpen = true;
        this.options = { ...this.options, ...options };
        this.overlay.style.display = 'block';
        this.searchInput.value = '';
        this.searchInput.focus();
        this.filterCommands('');
        
        // Add open class for animations
        requestAnimationFrame(() => {
            this.overlay.classList.add('open');
            this.palette.classList.add('open');
        });
    }
    
    close() {
        this.isOpen = false;
        this.overlay.classList.remove('open');
        this.palette.classList.remove('open');
        
        setTimeout(() => {
            this.overlay.style.display = 'none';
        }, 200);
    }
    
    filterCommands(query) {
        if (!query) {
            this.filteredCommands = this.commands;
        } else {
            const lowerQuery = query.toLowerCase();
            this.filteredCommands = this.commands.filter(cmd => 
                cmd.label.toLowerCase().includes(lowerQuery) ||
                cmd.category.toLowerCase().includes(lowerQuery) ||
                (cmd.keywords && cmd.keywords.some(k => k.toLowerCase().includes(lowerQuery)))
            );
        }
        
        this.selectedIndex = 0;
        this.renderResults();
    }
    
    renderResults() {
        this.resultsContainer.innerHTML = '';
        
        if (this.filteredCommands.length === 0) {
            this.resultsContainer.innerHTML = `
                <div class="no-results" style="padding: 40px; text-align: center; color: #9ca3af;">
                    No commands found. Try a different search.
                </div>
            `;
            return;
        }
        
        // Group by category
        const grouped = this.filteredCommands.reduce((acc, cmd) => {
            if (!acc[cmd.category]) acc[cmd.category] = [];
            acc[cmd.category].push(cmd);
            return acc;
        }, {});
        
        // Render groups
        Object.entries(grouped).forEach(([category, commands]) => {
            // Category header
            const header = document.createElement('div');
            header.className = 'command-category';
            header.style.cssText = `
                padding: 8px 24px;
                font-size: 12px;
                font-weight: 600;
                color: #6b7280;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                background: #f9fafb;
                border-top: 1px solid #e5e7eb;
            `;
            header.textContent = category;
            this.resultsContainer.appendChild(header);
            
            // Commands in category
            commands.forEach((cmd, index) => {
                const item = document.createElement('div');
                item.className = 'command-item';
                item.dataset.commandId = cmd.id;
                item.style.cssText = `
                    padding: 12px 24px;
                    display: flex;
                    align-items: center;
                    cursor: pointer;
                    transition: background 0.1s ease;
                `;
                
                const isRecent = this.recentCommands.includes(cmd.id);
                
                item.innerHTML = `
                    <span class="command-icon" style="margin-right: 12px; font-size: 20px;">${cmd.icon}</span>
                    <span class="command-label" style="flex: 1; font-size: 14px;">${cmd.label}</span>
                    ${isRecent ? '<span style="font-size: 10px; color: #3b82f6; margin-right: 8px;">RECENT</span>' : ''}
                    ${cmd.shortcut ? `<kbd style="font-size: 11px; padding: 2px 6px; background: #f3f4f6; border-radius: 4px; color: #6b7280;">${cmd.shortcut}</kbd>` : ''}
                `;
                
                this.resultsContainer.appendChild(item);
            });
        });
        
        // Update footer count
        this.footer.querySelector('.command-count').textContent = `${this.filteredCommands.length} commands`;
        
        // Update selection
        this.updateSelection();
    }
    
    updateSelection() {
        const items = this.resultsContainer.querySelectorAll('.command-item');
        items.forEach((item, index) => {
            if (index === this.selectedIndex) {
                item.style.background = '#eff6ff';
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.style.background = '';
            }
        });
    }
    
    selectNext() {
        const items = this.resultsContainer.querySelectorAll('.command-item');
        if (this.selectedIndex < items.length - 1) {
            this.selectedIndex++;
            this.updateSelection();
        }
    }
    
    selectPrevious() {
        if (this.selectedIndex > 0) {
            this.selectedIndex--;
            this.updateSelection();
        }
    }
    
    executeSelected() {
        const items = this.resultsContainer.querySelectorAll('.command-item');
        if (items[this.selectedIndex]) {
            const commandId = items[this.selectedIndex].dataset.commandId;
            this.executeCommand(commandId);
        }
    }
    
    executeCommand(commandId) {
        const command = this.commands.find(cmd => cmd.id === commandId);
        if (!command) return;
        
        // Save to recent commands
        this.saveRecentCommand(commandId);
        
        // Close palette
        this.close();
        
        // Execute command
        if (this.options.onSelect) {
            this.options.onSelect(command);
        } else {
            // Default command execution
            switch (command.action) {
                case 'navigate':
                    window.location.href = command.target;
                    break;
                case 'create':
                    window.livewire.emit('openCreateModal', command.type);
                    break;
                case 'search':
                    window.livewire.emit('focusSearch', command.type);
                    break;
                case 'filter':
                    window.livewire.emit('applyQuickFilter', command.filter);
                    break;
                case 'export':
                    window.livewire.emit('exportData', command.format);
                    break;
                case 'view':
                    window.livewire.emit('switchView', command.view);
                    break;
                case 'show-shortcuts':
                    this.showKeyboardShortcuts();
                    break;
                case 'ai-action':
                    window.livewire.emit('executeAIAction', command.type);
                    break;
                default:
                    window.livewire.emit('executeCommand', command);
            }
        }
    }
    
    saveRecentCommand(commandId) {
        // Remove if already exists
        this.recentCommands = this.recentCommands.filter(id => id !== commandId);
        
        // Add to beginning
        this.recentCommands.unshift(commandId);
        
        // Keep only last 5
        this.recentCommands = this.recentCommands.slice(0, 5);
        
        // Save to localStorage
        localStorage.setItem('command-palette-recent', JSON.stringify(this.recentCommands));
    }
    
    loadRecentCommands() {
        const saved = localStorage.getItem('command-palette-recent');
        return saved ? JSON.parse(saved) : [];
    }
    
    showKeyboardShortcuts() {
        // Create shortcuts modal
        const modal = document.createElement('div');
        modal.className = 'keyboard-shortcuts-modal';
        modal.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 32px;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            z-index: 10000;
        `;
        
        modal.innerHTML = `
            <h2 style="margin: 0 0 24px 0; font-size: 24px; font-weight: 600;">Keyboard Shortcuts</h2>
            <div class="shortcuts-grid" style="display: grid; gap: 16px;">
                <div class="shortcut-section">
                    <h3 style="font-size: 14px; font-weight: 600; color: #6b7280; margin-bottom: 12px;">NAVIGATION</h3>
                    <div class="shortcut-item" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Command Palette</span>
                        <kbd>⌘K</kbd>
                    </div>
                    <div class="shortcut-item" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Navigate Down</span>
                        <kbd>J</kbd> or <kbd>↓</kbd>
                    </div>
                    <div class="shortcut-item" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Navigate Up</span>
                        <kbd>K</kbd> or <kbd>↑</kbd>
                    </div>
                </div>
                
                <div class="shortcut-section">
                    <h3 style="font-size: 14px; font-weight: 600; color: #6b7280; margin-bottom: 12px;">VIEWS</h3>
                    <div class="shortcut-item" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Table View</span>
                        <kbd>⌘1</kbd>
                    </div>
                    <div class="shortcut-item" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Grid View</span>
                        <kbd>⌘2</kbd>
                    </div>
                    <div class="shortcut-item" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Kanban View</span>
                        <kbd>⌘3</kbd>
                    </div>
                </div>
                
                <div class="shortcut-section">
                    <h3 style="font-size: 14px; font-weight: 600; color: #6b7280; margin-bottom: 12px;">ACTIONS</h3>
                    <div class="shortcut-item" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Quick Edit</span>
                        <kbd>⌘E</kbd>
                    </div>
                    <div class="shortcut-item" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Duplicate</span>
                        <kbd>⌘D</kbd>
                    </div>
                    <div class="shortcut-item" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                        <span>Select All</span>
                        <kbd>⌘A</kbd>
                    </div>
                </div>
            </div>
            <button onclick="this.parentElement.remove()" style="margin-top: 24px; padding: 8px 16px; background: #3b82f6; color: white; border: none; border-radius: 6px; cursor: pointer;">Close</button>
        `;
        
        document.body.appendChild(modal);
    }
}

// Add CSS for animations
const style = document.createElement('style');
style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideDown {
        from { 
            opacity: 0;
            transform: translateX(-50%) translateY(-20px);
        }
        to { 
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }
    
    .command-palette kbd {
        font-family: ui-monospace, SFMono-Regular, 'SF Mono', Consolas, 'Liberation Mono', Menlo, monospace;
    }
`;
document.head.appendChild(style);