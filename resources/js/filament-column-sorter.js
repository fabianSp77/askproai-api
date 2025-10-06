import Sortable from 'sortablejs';

// Wait for Alpine to be available
if (!window.Alpine) {
    console.warn('Alpine.js not yet available, waiting for it to load...');
}

// Register the columnSorter component
const registerColumnSorter = () => {
    if (!window.Alpine) {
        console.error('Alpine.js is not available!');
        return;
    }

    console.log('Registering columnSorter with Alpine.js');

    window.Alpine.data('columnSorter', (resource, userId, initialColumns = []) => ({
        columns: initialColumns,
        visibleColumns: {},
        resource: resource,
        userId: userId,
        isLoading: false,

        init() {
            console.log('Column Sorter initialized');
            console.log('Resource:', this.resource);
            console.log('User ID:', this.userId);
            console.log('Initial columns count:', this.columns.length);
            console.log('Initial columns:', JSON.stringify(this.columns));

            // If columns were passed from PHP, use them
            if (this.columns.length > 0) {
                // Initialize visibility object from passed columns
                this.columns.forEach(col => {
                    this.visibleColumns[col.key] = col.visible !== false;
                });
                console.log('Initialized', this.columns.length, 'columns from PHP');
                console.log('Visibility map:', this.visibleColumns);
            } else {
                console.log('No columns passed from PHP, loading from DOM...');
                // Fallback: Try to load from DOM or use defaults
                this.loadAvailableColumns();
            }

            // Then load saved preferences (this will override the defaults)
            this.loadColumnPreferences();

            // Initialize Sortable after the DOM is ready
            this.$nextTick(() => {
                console.log('Setting up Sortable...');
                if (this.$refs.columnList) {
                    console.log('Found columnList element, initializing Sortable');
                    new Sortable(this.$refs.columnList, {
                        animation: 150,
                        handle: '.column-drag-handle',
                        ghostClass: 'opacity-50',
                        chosenClass: 'bg-primary-50',
                        dragClass: 'cursor-move',
                        onEnd: (evt) => {
                            console.log('Column reordered from', evt.oldIndex, 'to', evt.newIndex);
                            this.reorderColumns(evt.oldIndex, evt.newIndex);
                            this.saveColumnOrder();
                        }
                    });
                } else {
                    console.error('Column list element not found! $refs:', this.$refs);
                }
            });
        },

        async loadAvailableColumns() {
            console.log('Loading available columns from DOM');
            // Get columns from the table directly - they're already on the page
            const tableColumns = document.querySelectorAll('.fi-ta-table thead th[wire\\:key]');
            const columns = [];

            tableColumns.forEach(th => {
                const label = th.querySelector('.fi-ta-header-cell-label')?.textContent?.trim();
                if (label) {
                    const key = th.getAttribute('wire:key')?.split('.').pop() || label.toLowerCase().replace(/\s+/g, '_');
                    columns.push({
                        key: key,
                        label: label,
                        visible: true
                    });
                }
            });

            // If we found columns from the page, use them
            if (columns.length > 0) {
                console.log('Found', columns.length, 'columns from DOM');
                this.columns = columns;
            } else {
                // Fallback: Define default columns for companies resource
                if (this.resource === 'companies') {
                    console.log('Using fallback columns for companies resource');
                    this.columns = [
                        { key: 'id', label: 'ID', visible: true },
                        { key: 'logo', label: 'Logo', visible: true },
                        { key: 'name', label: 'Name', visible: true },
                        { key: 'industry', label: 'Branche', visible: true },
                        { key: 'company_type', label: 'Typ', visible: true },
                        { key: 'branches_count', label: 'Filialen', visible: true },
                        { key: 'staff_count', label: 'Mitarbeiter', visible: true },
                        { key: 'balance', label: 'Guthaben', visible: true },
                        { key: 'is_active', label: 'Aktiv', visible: true },
                        { key: 'created_at', label: 'Erstellt', visible: true },
                    ];
                }
            }

            // Initialize visibility object
            this.columns.forEach(col => {
                this.visibleColumns[col.key] = col.visible !== false;
            });
        },

        async loadColumnPreferences() {
            console.log('Loading column preferences for resource:', this.resource);
            try {
                const response = await fetch(`/api/user-preferences/columns/${this.resource}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    }
                });

                if (response.ok) {
                    const data = await response.json();
                    console.log('Loaded preferences:', data);
                    if (data.order && data.order.length > 0) {
                        this.applyColumnOrder(data.order);
                    }
                    if (data.visibility) {
                        this.visibleColumns = { ...this.visibleColumns, ...data.visibility };
                    }
                } else {
                    console.warn('Failed to load column preferences:', response.status);
                }
            } catch (error) {
                console.error('Failed to load column preferences:', error);
            }
        },

        reorderColumns(oldIndex, newIndex) {
            const movedColumn = this.columns.splice(oldIndex, 1)[0];
            this.columns.splice(newIndex, 0, movedColumn);
        },

        applyColumnOrder(order) {
            console.log('Applying column order:', order);
            // Reorder columns based on saved order
            const orderedColumns = [];

            order.forEach(columnKey => {
                const column = this.columns.find(c => c.key === columnKey);
                if (column) {
                    orderedColumns.push(column);
                }
            });

            // Add any new columns that aren't in the saved order
            this.columns.forEach(column => {
                if (!orderedColumns.find(c => c.key === column.key)) {
                    orderedColumns.push(column);
                }
            });

            this.columns = orderedColumns;
        },

        async saveColumnOrder() {
            const columnOrder = this.columns.map(c => c.key);
            console.log('Saving column order:', columnOrder);

            try {
                this.isLoading = true;

                const response = await fetch('/api/user-preferences/columns/save', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        resource: this.resource,
                        columns: columnOrder,
                        visibility: this.visibleColumns,
                    })
                });

                if (response.ok) {
                    console.log('Column order saved successfully');
                    // Emit event to refresh the table with new order
                    this.$dispatch('columns-reordered', { order: columnOrder });

                    // Show success notification
                    this.showNotification('Spaltenreihenfolge gespeichert');
                } else {
                    console.error('Failed to save column order:', response.status);
                    this.showNotification('Fehler beim Speichern', 'error');
                }
            } catch (error) {
                console.error('Failed to save column order:', error);
                this.showNotification('Fehler beim Speichern', 'error');
            } finally {
                this.isLoading = false;
            }
        },

        toggleColumnVisibility(columnKey) {
            console.log('Toggling visibility for column:', columnKey);
            this.visibleColumns[columnKey] = !this.visibleColumns[columnKey];
            this.saveColumnOrder();
        },

        async resetColumns() {
            if (!confirm('Möchten Sie die Spalten auf die Standardeinstellungen zurücksetzen?')) {
                return;
            }

            try {
                const response = await fetch(`/api/user-preferences/columns/${this.resource}/reset`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json',
                    }
                });

                if (response.ok) {
                    console.log('Columns reset successfully');
                    window.location.reload();
                } else {
                    console.error('Failed to reset columns:', response.status);
                    this.showNotification('Fehler beim Zurücksetzen', 'error');
                }
            } catch (error) {
                console.error('Failed to reset columns:', error);
                this.showNotification('Fehler beim Zurücksetzen', 'error');
            }
        },

        showNotification(message, type = 'success') {
            console.log('Showing notification:', message, type);
            // Create a simple notification
            const notification = document.createElement('div');
            notification.className = `fixed bottom-4 right-4 px-4 py-2 rounded-lg text-white ${type === 'success' ? 'bg-green-500' : 'bg-red-500'} z-50`;
            notification.textContent = message;
            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
    }));
};

// Try to register immediately if Alpine is available
if (window.Alpine && window.Alpine.data) {
    console.log('Alpine.js already available, registering columnSorter immediately');
    registerColumnSorter();
} else {
    // Otherwise wait for Alpine to be initialized
    document.addEventListener('alpine:init', () => {
        console.log('alpine:init event fired, registering columnSorter');
        registerColumnSorter();
    });

    // Fallback: Also try when DOMContentLoaded fires
    document.addEventListener('DOMContentLoaded', () => {
        if (window.Alpine && window.Alpine.data && !window.Alpine.data('columnSorter')) {
            console.log('DOMContentLoaded: Registering columnSorter');
            registerColumnSorter();
        }
    });
}

// Export for debugging
window.debugColumnSorter = () => {
    console.log('Alpine available:', !!window.Alpine);
    console.log('Alpine.data available:', !!(window.Alpine && window.Alpine.data));
    console.log('columnSorter registered:', !!(window.Alpine && window.Alpine.data && window.Alpine.$data));

    // Try to get all Alpine components
    if (window.Alpine && window.Alpine.$data) {
        document.querySelectorAll('[x-data*="columnSorter"]').forEach((el, index) => {
            console.log(`Component ${index}:`, el);
            const data = Alpine.$data(el);
            console.log(`  Data:`, data);
        });
    }
};