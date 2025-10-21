@props([
    'resource' => 'default',
    'columns' => [],
])

{{-- Debug output --}}
@if(config('app.debug'))
<div class="mb-4 p-2 bg-yellow-100 dark:bg-yellow-900 rounded text-xs">
    <div>Debug Info:</div>
    <div>Resource: {{ $resource }}</div>
    <div>Columns Count: {{ count($columns) }}</div>
    <div>User ID: {{ auth()->id() }}</div>
    <pre class="mt-2 text-xs">{{ json_encode($columns, JSON_PRETTY_PRINT) }}</pre>
</div>
@endif

<div
    x-data="columnSorter('{{ $resource }}', {{ auth()->id() }}, {{ Js::from($columns) }})"
    x-init="console.log('Component initialized with', columns.length, 'columns')"
    class="p-4"
>
    {{-- Debug Alpine State --}}
    <div x-show="false" x-text="JSON.stringify({columns: columns.length, visible: Object.keys(visibleColumns).length})"></div>

    {{-- Loading State --}}
    <div x-show="isLoading" class="flex items-center justify-center py-8">
        <svg class="animate-spin h-8 w-8 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
    </div>

    {{-- Content --}}
    <div x-show="!isLoading">
        {{-- Instructions --}}
        <div class="mb-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                </svg>
                <span>Ziehen Sie die Spalten mit dem Handle, um sie neu anzuordnen</span>
            </div>
            <div class="flex items-center gap-2 mt-2 text-sm text-gray-600 dark:text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <span>Klicken Sie auf die Checkboxen, um Spalten ein-/auszublenden</span>
            </div>
        </div>

        {{-- Reset Button --}}
        <div class="flex justify-end mb-3">
            <button
                @click="resetColumns"
                type="button"
                class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors"
            >
                Auf Standard zurücksetzen
            </button>
        </div>

        {{-- Column List --}}
        <div
            x-ref="columnList"
            class="space-y-2 max-h-[400px] overflow-y-auto pr-2"
        >
            {{-- Show a message if no columns --}}
            <div x-show="columns.length === 0" class="text-center py-8 text-gray-500">
                <div>Keine Spalten geladen.</div>
                <div class="text-xs mt-2">Bitte überprüfen Sie die Browser-Konsole für Fehler.</div>
            </div>

            <template x-for="(column, index) in columns" :key="column.key">
                <div
                    :data-column="column.key"
                    class="flex items-center gap-3 p-3 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-primary-300 dark:hover:border-primary-600 transition-all"
                >
                    {{-- Drag Handle --}}
                    <div class="column-drag-handle cursor-move p-1 hover:bg-gray-100 dark:hover:bg-gray-800 rounded">
                        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM7 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM7 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM7 14a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM7 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM17 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM17 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM17 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM17 14a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM17 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/>
                        </svg>
                    </div>

                    {{-- Checkbox and Label --}}
                    <label class="flex-1 flex items-center cursor-pointer select-none">
                        <input
                            type="checkbox"
                            :checked="visibleColumns[column.key] !== false"
                            @change="toggleColumnVisibility(column.key)"
                            class="mr-3 h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600"
                        >
                        <span
                            class="text-sm font-medium transition-colors"
                            :class="{
                                'text-gray-900 dark:text-gray-100': visibleColumns[column.key] !== false,
                                'text-gray-400 dark:text-gray-500': visibleColumns[column.key] === false
                            }"
                            x-text="column.label"
                        ></span>
                    </label>

                    {{-- Index Badge --}}
                    <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 rounded-md font-mono" x-text="index + 1"></span>
                </div>
            </template>
        </div>

        {{-- Apply Button --}}
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <button
                @click="saveColumnOrder(); $dispatch('close-modal')"
                type="button"
                class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors font-medium"
            >
                Änderungen anwenden
            </button>
        </div>
    </div>
</div>