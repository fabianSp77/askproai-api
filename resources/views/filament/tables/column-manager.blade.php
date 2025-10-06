@props([
    'columns' => [],
    'resource' => 'default',
])

<div
    x-data="columnSorter('{{ $resource }}', {{ auth()->id() }})"
    x-init="columns = {{ Js::from($columns) }}"
    class="relative"
>
    <x-filament::dropdown
        placement="bottom-end"
        width="md"
        class="fi-ta-col-toggle-enhanced"
    >
        <x-slot name="trigger">
            <x-filament::icon-button
                icon="heroicon-m-view-columns"
                size="sm"
                tooltip="Spalten verwalten"
                label="Spalten"
            />
        </x-slot>

        <div class="p-4">
            {{-- Header with Reset Button --}}
            <div class="flex items-center justify-between mb-4 pb-2 border-b">
                <h4 class="text-base font-semibold text-gray-950 dark:text-white">
                    Spalten verwalten
                </h4>
                <button
                    @click="resetColumns"
                    type="button"
                    class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
                    x-show="!isLoading"
                >
                    Zurücksetzen
                </button>
            </div>

            {{-- Instructions --}}
            <div class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                <p class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                    </svg>
                    Ziehen Sie die Spalten zum Sortieren
                </p>
            </div>

            {{-- Column List --}}
            <div
                x-ref="columnList"
                class="space-y-1 max-h-96 overflow-y-auto"
            >
                <template x-for="(column, index) in columns" :key="column.key">
                    <div
                        :data-column="column.key"
                        class="flex items-center gap-2 p-2 bg-gray-50 dark:bg-gray-800 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
                    >
                        {{-- Drag Handle --}}
                        <div class="column-drag-handle cursor-move p-1">
                            <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M7 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM7 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM7 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM7 14a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM7 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM17 2a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM17 6a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM17 10a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM17 14a2 2 0 1 1-4 0 2 2 0 0 1 4 0zM17 18a2 2 0 1 1-4 0 2 2 0 0 1 4 0z"/>
                            </svg>
                        </div>

                        {{-- Column Label --}}
                        <label class="flex-1 flex items-center cursor-pointer select-none">
                            <input
                                type="checkbox"
                                :checked="visibleColumns[column.key] !== false"
                                @change="toggleColumnVisibility(column.key)"
                                class="mr-2 rounded border-gray-300 text-primary-600 focus:ring-primary-500 dark:bg-gray-900 dark:border-gray-600"
                            >
                            <span
                                class="text-sm font-medium"
                                :class="{ 'text-gray-900 dark:text-gray-100': visibleColumns[column.key] !== false, 'text-gray-400 dark:text-gray-500': visibleColumns[column.key] === false }"
                                x-text="column.label"
                            ></span>
                        </label>

                        {{-- Hidden by Default Badge --}}
                        <template x-if="column.hiddenByDefault">
                            <span class="text-xs px-1.5 py-0.5 bg-gray-200 dark:bg-gray-600 text-gray-600 dark:text-gray-300 rounded">
                                versteckt
                            </span>
                        </template>
                    </div>
                </template>
            </div>

            {{-- Loading Indicator --}}
            <div x-show="isLoading" class="absolute inset-0 bg-white/50 dark:bg-gray-900/50 flex items-center justify-center rounded-lg">
                <svg class="animate-spin h-6 w-6 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
            </div>
        </div>
    </x-filament::dropdown>
</div>