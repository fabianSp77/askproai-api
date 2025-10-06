@props([
    'resource' => 'default',
    'columns' => [],
])

<div
    x-data="{
        columns: @js($columns),
        visibleColumns: {},
        resource: '{{ $resource }}',

        init() {
            console.log('Column Manager initialized with', this.columns.length, 'columns');
            this.columns.forEach(col => {
                this.visibleColumns[col.key] = col.visible !== false;
            });
        },

        toggleVisibility(key) {
            this.visibleColumns[key] = !this.visibleColumns[key];
        },

        saveChanges() {
            fetch('/api/user-preferences/columns/save', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    resource: this.resource,
                    columns: this.columns.map(c => c.key),
                    visibility: this.visibleColumns
                })
            }).then(() => {
                window.location.reload();
            });
        },

        resetColumns() {
            if (!confirm('Spalten auf Standardeinstellungen zurücksetzen?')) return;
            window.location.reload();
        }
    }"
>
    {{-- Column List with improved styling --}}
    <div class="fi-modal-content">
        <div class="space-y-2 max-h-[28rem] overflow-y-auto p-1">
            <template x-for="column in columns" :key="column.key">
                <div class="fi-ta-col-wrp">
                    <label class="flex items-center gap-x-3 rounded-lg px-3 py-2.5 transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                        <input
                            type="checkbox"
                            :checked="visibleColumns[column.key]"
                            @change="toggleVisibility(column.key)"
                            class="fi-checkbox-input rounded border-gray-300 text-primary-600 shadow-sm outline-none ring-0 focus:ring-2 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:checked:border-primary-500 dark:checked:bg-primary-500"
                        />
                        <div class="flex-1">
                            <span x-text="column.label" class="text-sm font-medium text-gray-950 dark:text-white"></span>
                            <span x-text="' (' + column.key + ')'" class="text-xs text-gray-500 dark:text-gray-400"></span>
                        </div>
                    </label>
                </div>
            </template>
        </div>

        {{-- Show message if no columns --}}
        <div x-show="columns.length === 0" class="flex items-center justify-center py-8">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Keine Spalten gefunden
            </p>
        </div>
    </div>

    {{-- Footer Actions --}}
    <div class="fi-modal-footer mt-6">
        <div class="fi-modal-footer-actions flex flex-wrap items-center gap-3">
            {{-- Reset button (left aligned) --}}
            <button
                @click="resetColumns()"
                type="button"
                class="fi-link fi-link-size-sm fi-link-color-gray text-sm font-semibold text-gray-600 hover:text-gray-500 dark:text-gray-400 dark:hover:text-gray-300"
            >
                Zurücksetzen
            </button>

            {{-- Spacer --}}
            <div class="flex-1"></div>

            {{-- Cancel button --}}
            <button
                @click="$dispatch('close-modal', { id: 'manageColumns' })"
                type="button"
                class="fi-btn fi-btn-size-md fi-btn-color-gray relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-btn-outlined px-3 py-2 text-sm gap-1.5 ring-1 text-gray-950 ring-gray-300 hover:bg-gray-50 dark:text-white dark:ring-white/20 dark:hover:bg-white/5"
            >
                Abbrechen
            </button>

            {{-- Save button --}}
            <button
                @click="saveChanges()"
                type="button"
                class="fi-btn fi-btn-size-md fi-btn-color-primary relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg px-3 py-2 text-sm gap-1.5 bg-primary-600 text-white hover:bg-primary-500 focus-visible:ring-primary-500/50 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus-visible:ring-primary-400/50"
            >
                Speichern
            </button>
        </div>
    </div>
</div>