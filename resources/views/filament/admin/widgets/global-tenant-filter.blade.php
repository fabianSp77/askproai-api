<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-center gap-2 mb-4">
            <x-heroicon-o-funnel class="w-5 h-5 text-gray-500 dark:text-gray-400" />
            <h2 class="text-lg font-semibold">Global Filter</h2>
        </div>
        
        <form wire:submit.prevent>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{ $this->form }}
            </div>
        </form>
        
        @if($selectedCompanyId || $selectedBranchId)
            <div class="mt-4 flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                <x-heroicon-o-information-circle class="w-4 h-4" />
                <span>Aktive Filter: 
                    @if($selectedCompanyId)
                        <span class="font-medium">{{ \App\Models\Company::find($selectedCompanyId)?->name }}</span>
                    @endif
                    @if($selectedBranchId)
                        @if($selectedCompanyId) / @endif
                        <span class="font-medium">{{ \App\Models\Branch::find($selectedBranchId)?->name }}</span>
                    @endif
                </span>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>