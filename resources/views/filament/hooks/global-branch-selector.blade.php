{{-- Branch Selector without Livewire --}}
<div x-data="{
    open: false,
    currentBranchId: '{{ session('current_branch_id', '') }}',
    branches: {{ json_encode(app(\App\Services\BranchContextManager::class)->getBranchesForUser()->map(function($branch) {
        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'company_name' => $branch->company->name ?? '',
            'is_active' => $branch->active,
        ];
    })->toArray()) }},
    switchBranch(branchId) {
        // Create a form and submit it
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '{{ route('admin.branch.switch') }}';
        
        const csrfToken = document.createElement('input');
        csrfToken.type = 'hidden';
        csrfToken.name = '_token';
        csrfToken.value = '{{ csrf_token() }}';
        form.appendChild(csrfToken);
        
        const branchInput = document.createElement('input');
        branchInput.type = 'hidden';
        branchInput.name = 'branch_id';
        branchInput.value = branchId;
        form.appendChild(branchInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}" class="relative">
    {{-- Branch Selector Button --}}
    <button 
        @click="open = !open"
        type="button"
        class="flex items-center gap-x-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition"
    >
        {{-- Branch Icon --}}
        <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
        </svg>
        
        {{-- Current Branch Name --}}
        <span>
            @php
                $branchContext = app(\App\Services\BranchContextManager::class);
                $currentBranch = $branchContext->getCurrentBranch();
                $isAllBranches = $branchContext->isAllBranchesView();
            @endphp
            @if($isAllBranches)
                <span class="font-semibold">🏢 Alle Filialen</span>
            @elseif($currentBranch)
                {{ $currentBranch->name }}
            @else
                Filiale wählen
            @endif
        </span>
        
        {{-- Dropdown Arrow --}}
        <svg class="h-4 w-4 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    {{-- Dropdown Menu --}}
    <div 
        x-show="open"
        @click.outside="open = false"
        @keyup.escape.window="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        x-cloak
        class="absolute right-0 mt-2 w-56 rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-[99999]"
    >
        <div class="py-1">
            {{-- All Branches Option --}}
            @if($branchContext->getBranchesForUser()->count() > 1)
                <button
                    type="button"
                    @click="switchBranch(''); open = false"
                    class="group flex w-full items-center px-4 py-2 text-sm transition text-left {{ $isAllBranches ? 'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' }}"
                >
                    <div class="flex-1">
                        <div class="font-semibold">🏢 Alle Filialen</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Zeige Daten aller Filialen</div>
                    </div>
                    @if($isAllBranches)
                        <svg class="ml-2 h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                        </svg>
                    @endif
                </button>
                
                <hr class="my-1 border-gray-200 dark:border-gray-700" />
            @endif

            {{-- Individual Branches --}}
            <template x-for="branch in branches" :key="branch.id">
                <button
                    type="button"
                    @click="switchBranch(branch.id); open = false"
                    :class="{
                        'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300': currentBranchId === branch.id,
                        'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700': currentBranchId !== branch.id,
                        'opacity-50 cursor-not-allowed': !branch.is_active
                    }"
                    class="group flex w-full items-center px-4 py-2 text-sm transition text-left"
                    :disabled="!branch.is_active"
                >
                    <div class="flex-1">
                        <div class="font-medium flex items-center gap-2">
                            <span x-text="branch.name"></span>
                            <span x-show="!branch.is_active" class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                Inaktiv
                            </span>
                        </div>
                        <div x-show="branch.company_name" class="text-xs text-gray-500 dark:text-gray-400" x-text="branch.company_name"></div>
                    </div>
                    <svg x-show="currentBranchId === branch.id" class="ml-2 h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                    </svg>
                </button>
            </template>
        </div>
    </div>
</div>