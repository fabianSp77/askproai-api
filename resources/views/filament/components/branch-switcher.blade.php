@php
    try {
        $branchContext = app(\App\Services\BranchContextManager::class);
        $currentBranch = $branchContext->getCurrentBranch();
        $isAllBranches = $branchContext->isAllBranchesView();
        $branches = $branchContext->getBranchesForUser();
    } catch (\Exception $e) {
        // If there's an error (e.g., no company context), gracefully handle it
        $currentBranch = null;
        $isAllBranches = false;
        $branches = collect();
    }
@endphp

@if($branches && $branches->count() > 1)
<x-filament::dropdown placement="bottom-end">
    <x-slot name="trigger">
        <button
            type="button"
            class="fi-user-btn relative flex items-center justify-center rounded-lg outline-none transition duration-75 hover:bg-gray-50 focus-visible:ring-2 focus-visible:ring-primary-600 disabled:pointer-events-none disabled:opacity-70 -ms-2 h-9 w-9 text-sm text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:bg-gray-700 dark:hover:text-gray-400 dark:focus-visible:ring-primary-500"
            title="Filiale wechseln"
        >
            <span class="sr-only">Filiale wechseln</span>
            
            <div class="relative">
                <x-filament::icon 
                    icon="heroicon-o-building-office-2" 
                    class="h-5 w-5"
                />
                @if(!$isAllBranches && $currentBranch)
                    <div class="absolute -bottom-1 -right-1 h-2 w-2 rounded-full bg-primary-600 dark:bg-primary-400"></div>
                @endif
            </div>
        </button>
    </x-slot>

    <x-filament::dropdown.list>
        <x-filament::dropdown.list.item
            :href="url()->current() . '?branch=all'"
            :icon="'heroicon-m-building-office'"
            tag="a"
        >
            <span class="flex items-center gap-2">
                <span>Alle Filialen</span>
                @if($isAllBranches)
                    <x-filament::icon 
                        icon="heroicon-m-check" 
                        class="h-4 w-4 text-success-600 dark:text-success-400"
                    />
                @endif
            </span>
        </x-filament::dropdown.list.item>

        {{-- Divider line --}}
        <hr class="my-1 border-gray-200 dark:border-gray-700" />

        @foreach($branches as $branch)
            <x-filament::dropdown.list.item
                :href="url()->current() . '?branch=' . $branch->id"
                tag="a"
            >
                <span class="flex items-center gap-2">
                    <span>{{ $branch->name }}</span>
                    @if(!$isAllBranches && $currentBranch && $currentBranch->id === $branch->id)
                        <x-filament::icon 
                            icon="heroicon-m-check" 
                            class="h-4 w-4 text-success-600 dark:text-success-400"
                        />
                    @endif
                </span>
            </x-filament::dropdown.list.item>
        @endforeach
    </x-filament::dropdown.list>
</x-filament::dropdown>
@endif