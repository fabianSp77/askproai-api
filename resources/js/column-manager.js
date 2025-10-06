// Column Manager for Filament - Works with Filament's Alpine.js
import Sortable from 'sortablejs';

// Register with Alpine when it's available
document.addEventListener('alpine:init', () => {
    Alpine.data('columnManager', (resourceName = 'default', userId = null, initialColumns = []) => ({
        // Data properties
        columns: initialColumns,
        visibleColumns: {},
        resource: resourceName,
        userId: userId,
        isLoading: false,
        sortableInstance: null,

        // Initialization
        init() {
            console.log('ColumnManager: Initializing for resource:', this.resource);
            console.log('ColumnManager: User ID:', this.userId);
            console.log('ColumnManager: Initial columns:', this.columns.length);

            // Initialize visibility map
            this.columns.forEach(col => {
                this.visibleColumns[col.key] = col.visible !== false;
            });

            // Setup sortable
            this.$nextTick(() => {
                this.setupSortable();
            });

            // Load user preferences
            this.loadPreferences();
        },

        // Setup drag and drop
        setupSortable() {
            const listEl = this.$refs.columnList;
            if (!listEl) {
                console.warn('ColumnManager: Column list element not found');
                return;
            }

            console.log('ColumnManager: Setting up Sortable.js');
            this.sortableInstance = new Sortable(listEl, {
                animation: 150,
                handle: '.drag-handle',
                ghostClass: 'opacity-50',
                onEnd: (evt) => {
                    this.handleReorder(evt.oldIndex, evt.newIndex);
                }
            });
        },

        // Handle column reordering
        handleReorder(oldIndex, newIndex) {
            if (oldIndex === newIndex) return;

            console.log(`ColumnManager: Moving column from ${oldIndex} to ${newIndex}`);
            const movedColumn = this.columns.splice(oldIndex, 1)[0];
            this.columns.splice(newIndex, 0, movedColumn);
            this.savePreferences();
        },

        // Toggle column visibility
        toggleVisibility(columnKey) {
            console.log('ColumnManager: Toggling visibility for:', columnKey);
            this.visibleColumns[columnKey] = !this.visibleColumns[columnKey];
            this.savePreferences();
        },

        // Load user preferences
        async loadPreferences() {
            if (!this.userId || !this.resource) return;

            try {
                const response = await fetch(`/api/user-preferences/columns/${this.resource}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    console.log('ColumnManager: Loaded preferences:', data);

                    // Apply saved column order
                    if (data.order && data.order.length > 0) {
                        this.applyOrder(data.order);
                    }

                    // Apply saved visibility
                    if (data.visibility) {
                        Object.assign(this.visibleColumns, data.visibility);
                    }
                }
            } catch (error) {
                console.error('ColumnManager: Failed to load preferences:', error);
            }
        },

        // Apply column order
        applyOrder(orderArray) {
            const orderedColumns = [];

            // First add columns in the saved order
            orderArray.forEach(key => {
                const column = this.columns.find(c => c.key === key);
                if (column) {
                    orderedColumns.push(column);
                }
            });

            // Then add any new columns not in the saved order
            this.columns.forEach(column => {
                if (!orderedColumns.find(c => c.key === column.key)) {
                    orderedColumns.push(column);
                }
            });

            this.columns = orderedColumns;
        },

        // Save user preferences
        async savePreferences() {
            if (!this.userId || !this.resource) return;

            const columnOrder = this.columns.map(c => c.key);
            console.log('ColumnManager: Saving preferences');

            try {
                const response = await fetch('/api/user-preferences/columns/save', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        resource: this.resource,
                        columns: columnOrder,
                        visibility: this.visibleColumns
                    })
                });

                if (response.ok) {
                    console.log('ColumnManager: Preferences saved');
                    this.showNotification('Einstellungen gespeichert');
                } else {
                    console.error('ColumnManager: Failed to save preferences');
                    this.showNotification('Fehler beim Speichern', 'error');
                }
            } catch (error) {
                console.error('ColumnManager: Error saving preferences:', error);
                this.showNotification('Fehler beim Speichern', 'error');
            }
        },

        // Reset to defaults
        async resetToDefaults() {
            if (!confirm('Möchten Sie die Spalten auf die Standardeinstellungen zurücksetzen?')) {
                return;
            }

            try {
                const response = await fetch(`/api/user-preferences/columns/${this.resource}/reset`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    }
                });

                if (response.ok) {
                    console.log('ColumnManager: Reset to defaults');
                    window.location.reload();
                }
            } catch (error) {
                console.error('ColumnManager: Error resetting:', error);
            }
        },

        // Show notification
        showNotification(message, type = 'success') {
            // Use Filament's notification if available
            if (window.$wire && window.$wire.dispatch) {
                window.$wire.dispatch('notify', {
                    type: type,
                    message: message
                });
            } else {
                // Fallback to custom notification
                const notification = document.createElement('div');
                notification.className = `fixed bottom-4 right-4 px-4 py-2 rounded-lg text-white ${
                    type === 'success' ? 'bg-green-500' : 'bg-red-500'
                } z-50`;
                notification.textContent = message;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.remove();
                }, 3000);
            }
        }
    }));
});

console.log('Column Manager module loaded');