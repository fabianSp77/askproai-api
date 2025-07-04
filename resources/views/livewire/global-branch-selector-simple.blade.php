<div>
    {{-- Desktop Version --}}
    <div x-data="{ open: false }" class="relative hidden sm:block">
        {{-- Branch Selector Button --}}
        <button 
            @click="open = !open"
            @click.away="open = false"
            type="button"
            class="flex items-center gap-x-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition"
        >
            {{-- Branch Icon --}}
            <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
            </svg>
            
            {{-- Current Branch Name --}}
            <span>
                @if($currentBranchId === '')
                    <span class="font-semibold">🏢 Alle Filialen</span>
                @else
                    @php
                        $currentBranch = collect($branches)->firstWhere('id', $currentBranchId);
                    @endphp
                    <span>{{ $currentBranch['name'] ?? 'Filiale wählen' }}</span>
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
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            style="display: none;"
            class="absolute right-0 mt-2 w-56 rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-50"
        >
            <div class="py-1">
                {{-- Form for branch switching --}}
                <form wire:submit.prevent="handleBranchSwitch">
                    {{-- All Branches Option --}}
                    @if($showAllBranchesOption)
                        <button
                            type="submit"
                            wire:click.prevent="switchBranch('')"
                            @click="open = false"
                            @class([
                                'group flex w-full items-center px-4 py-2 text-sm transition text-left',
                                'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' => $currentBranchId === '',
                                'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' => $currentBranchId !== '',
                            ])
                        >
                            <div class="flex-1">
                                <div class="font-semibold">🏢 Alle Filialen</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Zeige Daten aller Filialen</div>
                            </div>
                            @if($currentBranchId === '')
                                <svg class="ml-2 h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </button>
                        
                        @if(count($branches) > 0)
                            <hr class="my-1 border-gray-200 dark:border-gray-700" />
                        @endif
                    @endif

                    {{-- Individual Branches --}}
                    @forelse($branches as $branch)
                        <button
                            type="submit"
                            wire:click.prevent="switchBranch('{{ $branch['id'] }}')"
                            @click="open = false"
                            @class([
                                'group flex w-full items-center px-4 py-2 text-sm transition text-left',
                                'bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300' => $currentBranchId === $branch['id'],
                                'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700' => $currentBranchId !== $branch['id'],
                                'opacity-50 cursor-not-allowed' => !$branch['is_active'],
                            ])
                            @disabled(!$branch['is_active'])
                        >
                            <div class="flex-1">
                                <div class="font-medium flex items-center gap-2">
                                    {{ $branch['name'] }}
                                    @if(!$branch['is_active'])
                                        <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                            Inaktiv
                                        </span>
                                    @endif
                                </div>
                                @if($branch['company_name'])
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $branch['company_name'] }}</div>
                                @endif
                            </div>
                            @if($currentBranchId === $branch['id'])
                                <svg class="ml-2 h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" />
                                </svg>
                            @endif
                        </button>
                    @empty
                        <div class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">
                            @if($isLoading)
                                Lade Filialen...
                            @else
                                Keine Filialen verfügbar
                            @endif
                        </div>
                    @endforelse
                </form>
            </div>
        </div>

        {{-- Loading State --}}
        <div wire:loading wire:target="switchBranch" class="absolute inset-0 flex items-center justify-center bg-white/50 dark:bg-gray-900/50 rounded-lg">
            <svg class="animate-spin h-5 w-5 text-primary-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>

    {{-- Mobile Version --}}
    <div class="sm:hidden">
        <button 
            type="button"
            wire:click="$toggle('showMobileMenu')"
            class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition"
        >
            <svg class="h-5 w-5 text-gray-500 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
            </svg>
        </button>
    </div>

    {{-- Debug info --}}
    @if(config('app.debug'))
        <div class="hidden">
            <pre>
                Branches Count: {{ count($branches) }}
                Current Branch ID: {{ $currentBranchId }}
                Show All Option: {{ $showAllBranchesOption ? 'true' : 'false' }}
                Is Loading: {{ $isLoading ? 'true' : 'false' }}
            </pre>
        </div>
    @endif
</div>