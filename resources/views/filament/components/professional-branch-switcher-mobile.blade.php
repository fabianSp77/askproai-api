@php
    try {
        $branchContext = app(\App\Services\BranchContextManager::class);
        $currentBranch = $branchContext->getCurrentBranch();
        $isAllBranches = $branchContext->isAllBranchesView();
        $branches = $branchContext->getBranchesForUser() ?? collect();
    } catch (\Exception $e) {
        $currentBranch = null;
        $isAllBranches = false;
        $branches = collect();
    }
@endphp

<div x-data="{ 
    open: false, 
    search: '',
    branches: {{ $branches->toJson() }}
}" class="relative">
    {{-- Branch Selector Button --}}
    <button
        @click="open = !open"
        type="button"
        class="w-full flex items-center justify-between px-4 py-3 text-sm font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 rounded-lg transition-colors duration-200 dark:text-gray-300 dark:bg-gray-800 dark:hover:bg-gray-700"
    >
        <div class="flex items-center">
            <x-filament::icon 
                icon="heroicon-o-building-office-2" 
                class="mr-3 h-5 w-5 text-gray-400"
            />
            <div class="text-left">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Aktuelle Filiale') }}</div>
                <div class="font-semibold">
                    @if($isAllBranches)
                        {{ __('Alle Filialen') }}
                    @elseif($currentBranch)
                        {{ $currentBranch->name }}
                    @else
                        {{ __('Filiale wählen') }}
                    @endif
                </div>
            </div>
        </div>
        <x-filament::icon 
            icon="heroicon-m-chevron-down" 
            class="h-5 w-5 text-gray-400 transition-transform duration-200"
            ::class="{ 'rotate-180': open }"
        />
    </button>

    {{-- Dropdown Panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        @click.outside="open = false"
        class="mt-2 w-full rounded-lg bg-white shadow-lg ring-1 ring-black ring-opacity-5 dark:bg-gray-800 dark:ring-white/10"
        style="display: none;"
    >
        {{-- Search (if many branches) --}}
        @if($branches->count() > 5)
        <div class="p-3 border-b border-gray-200 dark:border-gray-700">
            <input
                type="text"
                x-model="search"
                placeholder="{{ __('Filiale suchen...') }}"
                class="w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600"
                @click.stop
            />
        </div>
        @endif

        <div class="max-h-60 overflow-y-auto py-1">
            {{-- All Branches Option --}}
            <a
                href="{{ url()->current() . '?branch=all' }}"
                class="block px-4 py-2.5 text-sm font-medium transition-colors {{ $isAllBranches ? 'bg-primary-100 text-primary-900 dark:bg-primary-900/20 dark:text-primary-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700' }}"
            >
                <div class="flex items-center">
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="mr-3 h-5 w-5" />
                    {{ __('Alle Filialen') }}
                </div>
            </a>

            {{-- Individual Branches --}}
            <template x-for="branch in branches.filter(b => !search || b.name.toLowerCase().includes(search.toLowerCase()))" :key="branch.id">
                <a
                    :href="window.location.pathname + '?branch=' + branch.id"
                    class="block px-4 py-2.5 text-sm font-medium transition-colors"
                    :class="{
                        'bg-primary-100 text-primary-900 dark:bg-primary-900/20 dark:text-primary-300': {{ json_encode($currentBranch?->id) }} === branch.id,
                        'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700': {{ json_encode($currentBranch?->id) }} !== branch.id
                    }"
                >
                    <div class="flex items-center">
                        <x-filament::icon icon="heroicon-o-building-office" class="mr-3 h-5 w-5" />
                        <span x-text="branch.name"></span>
                    </div>
                </a>
            </template>

            {{-- No Results --}}
            <div x-show="branches.filter(b => !search || b.name.toLowerCase().includes(search.toLowerCase())).length === 0" class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400 text-center">
                {{ __('Keine Filialen gefunden') }}
            </div>
        </div>
    </div>
</div>