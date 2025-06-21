{{-- Compact Global Filter Widget --}}
<div class="fi-wi-global-filter w-full mb-4">
    <div class="bg-white dark:bg-gray-900 shadow-sm ring-1 ring-gray-950/5 dark:ring-white/10 rounded-xl p-4">
        @include('filament.admin.widgets.partials.filter-controls')
        
        {{-- Custom Date Range (shown when period = custom) --}}
        @if($showDatePicker)
            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Von:</label>
                        <input 
                            type="date"
                            wire:model="globalFilters.date_from"
                            class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Bis:</label>
                        <input 
                            type="date"
                            wire:model="globalFilters.date_to"
                            class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                        >
                    </div>
                    <button
                        wire:click="applyDateRange"
                        type="button"
                        class="px-4 py-2 text-sm font-medium text-white bg-primary-600 rounded-md hover:bg-primary-700 transition-colors duration-200"
                    >
                        Anwenden
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Push Partial View --}}
@push('filter-controls-partial')
<script>
    // Placeholder for filter controls partial
</script>
@endpush