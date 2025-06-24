// Inline Editor - Click to edit any cell
export class InlineEditor {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            saveDelay: 1000,
            showSaveIndicator: true,
            enableUndo: true,
            ...options
        };
        
        this.editingCell = null;
        this.originalValue = null;
        this.saveTimeout = null;
        this.undoStack = [];
        this.redoStack = [];
        
        this.init();
    }
    
    init() {
        // Add edit indicators
        this.addEditIndicators();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Create save indicator
        if (this.options.showSaveIndicator) {
            this.createSaveIndicator();
        }
        
        // Setup keyboard shortcuts
        this.setupKeyboardShortcuts();
    }
    
    addEditIndicators() {
        // Add edit class to editable cells
        const editableCells = this.container.querySelectorAll('[data-field]');
        editableCells.forEach(cell => {
            cell.classList.add('inline-editable');
            cell.setAttribute('title', 'Double-click to edit');
            
            // Add edit icon on hover
            const editIcon = document.createElement('span');
            editIcon.className = 'inline-edit-icon';
            editIcon.innerHTML = '✏️';
            editIcon.style.cssText = `
                position: absolute;
                right: 8px;
                top: 50%;
                transform: translateY(-50%);
                opacity: 0;
                transition: opacity 0.2s;
                font-size: 12px;
                pointer-events: none;
            `;
            
            cell.style.position = 'relative';
            cell.appendChild(editIcon);
        });
    }
    
    setupEventListeners() {
        // Double click to edit
        this.container.addEventListener('dblclick', (e) => {
            const cell = e.target.closest('[data-field]');
            if (cell && !this.editingCell) {
                this.startEdit(cell);
            }
        });
        
        // Single click with modifier key
        this.container.addEventListener('click', (e) => {
            if ((e.metaKey || e.ctrlKey) && !this.editingCell) {
                const cell = e.target.closest('[data-field]');
                if (cell) {
                    e.preventDefault();
                    this.startEdit(cell);
                }
            }
        });
        
        // Click outside to save
        document.addEventListener('click', (e) => {
            if (this.editingCell && !this.editingCell.contains(e.target)) {
                this.saveEdit();
            }
        });
        
        // Tab navigation
        this.container.addEventListener('keydown', (e) => {
            if (e.key === 'Tab' && this.editingCell) {
                e.preventDefault();
                this.saveAndNavigate(e.shiftKey ? 'previous' : 'next');
            }
        });
    }
    
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (this.editingCell) {
                switch (e.key) {
                    case 'Enter':
                        if (!e.shiftKey) {
                            e.preventDefault();
                            this.saveEdit();
                        }
                        break;
                    case 'Escape':
                        e.preventDefault();
                        this.cancelEdit();
                        break;
                }
            }
            
            // Global undo/redo
            if ((e.metaKey || e.ctrlKey) && !this.editingCell) {
                switch (e.key) {
                    case 'z':
                        if (e.shiftKey) {
                            e.preventDefault();
                            this.redo();
                        } else {
                            e.preventDefault();
                            this.undo();
                        }
                        break;
                    case 'y':
                        e.preventDefault();
                        this.redo();
                        break;
                }
            }
        });
    }
    
    createSaveIndicator() {
        this.saveIndicator = document.createElement('div');
        this.saveIndicator.className = 'inline-edit-save-indicator';
        this.saveIndicator.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: #10b981;
            color: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
            z-index: 9999;
            pointer-events: none;
            display: flex;
            align-items: center;
            gap: 8px;
        `;
        
        const icon = document.createElement('span');
        icon.innerHTML = '✓';
        this.saveIndicator.appendChild(icon);
        
        const text = document.createElement('span');
        text.textContent = 'Saved';
        this.saveIndicator.appendChild(text);
        
        document.body.appendChild(this.saveIndicator);
    }
    
    startEdit(cell) {
        if (this.editingCell) {
            this.saveEdit();
        }
        
        this.editingCell = cell;
        this.originalValue = cell.textContent.trim();
        
        const field = cell.dataset.field;
        const recordId = cell.closest('tr').dataset.recordId;
        const inputType = this.getInputType(field);
        
        // Create input element
        const input = this.createInput(inputType, this.originalValue);
        
        // Replace cell content with input
        cell.innerHTML = '';
        cell.appendChild(input);
        
        // Focus and select
        input.focus();
        if (input.select) {
            input.select();
        }
        
        // Add editing class
        cell.classList.add('editing');
        
        // Setup input events
        this.setupInputEvents(input);
    }
    
    getInputType(field) {
        // Determine input type based on field name
        const fieldTypes = {
            email: 'email',
            phone: 'tel',
            url: 'url',
            website: 'url',
            price: 'number',
            amount: 'number',
            quantity: 'number',
            date: 'date',
            time: 'time',
            datetime: 'datetime-local',
            notes: 'textarea',
            description: 'textarea',
            bio: 'textarea'
        };
        
        for (const [key, type] of Object.entries(fieldTypes)) {
            if (field.toLowerCase().includes(key)) {
                return type;
            }
        }
        
        return 'text';
    }
    
    createInput(type, value) {
        let input;
        
        if (type === 'textarea') {
            input = document.createElement('textarea');
            input.rows = 3;
            input.style.cssText = `
                width: 100%;
                padding: 8px;
                border: 2px solid #3b82f6;
                border-radius: 6px;
                font-size: inherit;
                font-family: inherit;
                resize: vertical;
                min-height: 60px;
            `;
        } else if (type === 'select') {
            input = document.createElement('select');
            // Add options based on field
            input.style.cssText = `
                width: 100%;
                padding: 8px;
                border: 2px solid #3b82f6;
                border-radius: 6px;
                font-size: inherit;
                font-family: inherit;
            `;
        } else {
            input = document.createElement('input');
            input.type = type;
            input.style.cssText = `
                width: 100%;
                padding: 8px;
                border: 2px solid #3b82f6;
                border-radius: 6px;
                font-size: inherit;
                font-family: inherit;
            `;
        }
        
        input.value = value;
        input.className = 'inline-edit-input';
        
        return input;
    }
    
    setupInputEvents(input) {
        // Auto-save on input with debounce
        let saveTimeout;
        input.addEventListener('input', () => {
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                this.saveEdit(false); // Don't blur
            }, this.options.saveDelay);
        });
        
        // Handle special keys
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey && input.tagName !== 'TEXTAREA') {
                e.preventDefault();
                this.saveEdit();
            } else if (e.key === 'Escape') {
                e.preventDefault();
                this.cancelEdit();
            }
        });
        
        // Prevent form submission
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter' && input.tagName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });
    }
    
    saveEdit(shouldBlur = true) {
        if (!this.editingCell) return;
        
        const input = this.editingCell.querySelector('.inline-edit-input');
        if (!input) return;
        
        const newValue = input.value.trim();
        const field = this.editingCell.dataset.field;
        const recordId = this.editingCell.closest('tr').dataset.recordId;
        
        // Check if value changed
        if (newValue !== this.originalValue) {
            // Add to undo stack
            if (this.options.enableUndo) {
                this.undoStack.push({
                    recordId,
                    field,
                    oldValue: this.originalValue,
                    newValue: newValue,
                    timestamp: Date.now()
                });
                
                // Clear redo stack
                this.redoStack = [];
            }
            
            // Update cell
            this.editingCell.textContent = newValue;
            
            // Send update to server
            this.sendUpdate(recordId, field, newValue);
            
            // Show save indicator
            this.showSaveIndicator();
        } else {
            // No change, just restore
            this.editingCell.textContent = this.originalValue;
        }
        
        // Clean up
        this.editingCell.classList.remove('editing');
        this.editingCell = null;
        this.originalValue = null;
        
        if (shouldBlur) {
            input.blur();
        }
    }
    
    cancelEdit() {
        if (!this.editingCell) return;
        
        // Restore original value
        this.editingCell.textContent = this.originalValue;
        this.editingCell.classList.remove('editing');
        
        // Clean up
        this.editingCell = null;
        this.originalValue = null;
    }
    
    saveAndNavigate(direction) {
        this.saveEdit(false);
        
        // Find next editable cell
        const editableCells = Array.from(this.container.querySelectorAll('[data-field]'));
        const currentIndex = editableCells.indexOf(this.editingCell);
        
        let nextIndex;
        if (direction === 'next') {
            nextIndex = currentIndex + 1;
            if (nextIndex >= editableCells.length) nextIndex = 0;
        } else {
            nextIndex = currentIndex - 1;
            if (nextIndex < 0) nextIndex = editableCells.length - 1;
        }
        
        const nextCell = editableCells[nextIndex];
        if (nextCell) {
            this.startEdit(nextCell);
        }
    }
    
    sendUpdate(recordId, field, value) {
        // Use Livewire to update
        if (window.Livewire) {
            window.Livewire.emit('updateField', {
                recordId,
                field,
                value
            });
        }
        
        // Or use fetch API
        fetch('/api/inline-update', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
            },
            body: JSON.stringify({
                recordId,
                field,
                value
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Field updated successfully');
            } else {
                // Revert on error
                this.showError('Failed to save changes');
                // Revert the change
                const cell = this.container.querySelector(`tr[data-record-id="${recordId}"] [data-field="${field}"]`);
                if (cell) {
                    cell.textContent = this.originalValue;
                }
            }
        })
        .catch(error => {
            console.error('Update failed:', error);
            this.showError('Failed to save changes');
        });
    }
    
    showSaveIndicator() {
        if (!this.saveIndicator) return;
        
        // Show indicator
        this.saveIndicator.style.opacity = '1';
        this.saveIndicator.style.transform = 'translateY(0)';
        
        // Hide after delay
        clearTimeout(this.saveTimeout);
        this.saveTimeout = setTimeout(() => {
            this.saveIndicator.style.opacity = '0';
            this.saveIndicator.style.transform = 'translateY(-20px)';
        }, 2000);
    }
    
    showError(message) {
        // Create error indicator
        const errorIndicator = document.createElement('div');
        errorIndicator.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 12px 20px;
            background: #ef4444;
            color: white;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            z-index: 9999;
        `;
        errorIndicator.textContent = message;
        
        document.body.appendChild(errorIndicator);
        
        // Remove after delay
        setTimeout(() => {
            errorIndicator.remove();
        }, 3000);
    }
    
    undo() {
        if (this.undoStack.length === 0) return;
        
        const action = this.undoStack.pop();
        
        // Apply undo
        const cell = this.container.querySelector(
            `tr[data-record-id="${action.recordId}"] [data-field="${action.field}"]`
        );
        
        if (cell) {
            cell.textContent = action.oldValue;
            this.sendUpdate(action.recordId, action.field, action.oldValue);
            
            // Add to redo stack
            this.redoStack.push(action);
            
            // Flash cell
            this.flashCell(cell, '#fbbf24');
        }
    }
    
    redo() {
        if (this.redoStack.length === 0) return;
        
        const action = this.redoStack.pop();
        
        // Apply redo
        const cell = this.container.querySelector(
            `tr[data-record-id="${action.recordId}"] [data-field="${action.field}"]`
        );
        
        if (cell) {
            cell.textContent = action.newValue;
            this.sendUpdate(action.recordId, action.field, action.newValue);
            
            // Add back to undo stack
            this.undoStack.push(action);
            
            // Flash cell
            this.flashCell(cell, '#10b981');
        }
    }
    
    flashCell(cell, color) {
        const originalBg = cell.style.backgroundColor;
        cell.style.backgroundColor = color;
        cell.style.transition = 'background-color 0.3s ease';
        
        setTimeout(() => {
            cell.style.backgroundColor = originalBg;
        }, 300);
    }
}

// Add CSS for inline editing
const style = document.createElement('style');
style.textContent = `
    .inline-editable {
        cursor: text;
        position: relative;
        transition: background-color 0.2s ease;
    }
    
    .inline-editable:hover {
        background-color: rgba(59, 130, 246, 0.05);
    }
    
    .inline-editable:hover .inline-edit-icon {
        opacity: 0.5 !important;
    }
    
    .inline-editable.editing {
        padding: 0 !important;
        background-color: transparent !important;
    }
    
    .inline-edit-input:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
`;
document.head.appendChild(style);