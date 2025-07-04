@php
    // Safely get branch context
    try {
        $branchContext = app(\App\Services\BranchContextManager::class);
        $currentBranch = $currentBranch ?? $branchContext->getCurrentBranch();
        $isAllBranches = $isAllBranches ?? $branchContext->isAllBranchesView();
        $branches = $branches ?? $branchContext->getBranchesForUser();
    } catch (\Exception $e) {
        $currentBranch = null;
        $isAllBranches = false;
        $branches = collect();
    }
@endphp

{{-- Mobile Navigation Component for Filament --}}
<div 
    x-data="{ 
        mobileMenuOpen: false,
        branchDropdownOpen: false,
        accountDropdownOpen: false,
        search: '',
        branches: @js($branches),
        currentBranch: @js($currentBranch),
        isAllBranches: @js($isAllBranches),
        get filteredBranches() {
            if (!this.search) return this.branches;
            return this.branches.filter(branch => 
                branch.name.toLowerCase().includes(this.search.toLowerCase())
            );
        }
    }"
    @keydown.escape.window="mobileMenuOpen = false; branchDropdownOpen = false; accountDropdownOpen = false"
    class="lg:hidden"
>
    {{-- Mobile Menu Button (Burger) --}}
    <button
        @click="mobileMenuOpen = !mobileMenuOpen"
        type="button"
        class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 dark:text-gray-300 dark:hover:text-gray-200 dark:hover:bg-gray-700"
        :aria-expanded="mobileMenuOpen"
        aria-label="{{ __('Hauptmenü öffnen') }}"
    >
        <span class="sr-only">{{ __('Hauptmenü öffnen') }}</span>
        
        {{-- Animated Burger Icon --}}
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path 
                x-show="!mobileMenuOpen" 
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M4 6h16M4 12h16M4 18h16"
            />
            <path 
                x-show="mobileMenuOpen" 
                x-cloak
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M6 18L18 6M6 6l12 12"
            />
        </svg>
    </button>

    {{-- Mobile Menu Panel --}}
    <div
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        x-cloak
        class="absolute top-16 inset-x-0 z-50 transform shadow-lg"
        @click.outside="mobileMenuOpen = false"
    >
        <div class="rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 bg-white dark:bg-gray-800 divide-y divide-gray-100 dark:divide-gray-700">
            {{-- User Info Section --}}
            <div class="px-5 pt-5 pb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        @if($user = auth()->user())
                            <div class="flex-shrink-0">
                                <x-filament::avatar
                                    :src="filament()->getUserAvatarUrl($user)"
                                    :alt="filament()->getUserName($user)"
                                    class="h-10 w-10"
                                />
                            </div>
                            <div class="ml-3">
                                <div class="text-base font-medium text-gray-800 dark:text-gray-200">
                                    {{ filament()->getUserName($user) }}
                                </div>
                                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                    {{ $user->email }}
                                </div>
                            </div>
                        @endif
                    </div>
                    <button
                        @click="mobileMenuOpen = false"
                        type="button"
                        class="rounded-md bg-white dark:bg-gray-800 p-2 inline-flex items-center justify-center text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500"
                    >
                        <span class="sr-only">{{ __('Menü schließen') }}</span>
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-6 w-6" />
                    </button>
                </div>
            </div>

            {{-- Branch Switcher Section --}}
            <div class="px-5 py-4">
                <button
                    @click="branchDropdownOpen = !branchDropdownOpen"
                    type="button"
                    class="w-full flex items-center justify-between px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:text-gray-100 dark:hover:bg-gray-700/50"
                    :aria-expanded="branchDropdownOpen"
                >
                    <span class="flex items-center">
                        <x-filament::icon icon="heroicon-o-building-office-2" class="mr-3 h-5 w-5" />
                        <span>
                            @if($isAllBranches)
                                {{ __('Alle Filialen') }}
                            @elseif($currentBranch)
                                {{ $currentBranch->name }}
                            @else
                                {{ __('Filiale wählen') }}
                            @endif
                        </span>
                    </span>
                    <x-filament::icon 
                        icon="heroicon-m-chevron-down" 
                        class="ml-2 h-5 w-5 transition-transform duration-200"
                        ::class="{ 'rotate-180': branchDropdownOpen }"
                    />
                </button>

                {{-- Branch Dropdown Content --}}
                <div
                    x-show="branchDropdownOpen"
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    x-cloak
                    class="mt-2 space-y-1"
                >
                    {{-- Search (if many branches) --}}
                    @if($branches && $branches->count() > 5)
                    <div class="px-3 pb-2">
                        <input
                            type="text"
                            x-model="search"
                            placeholder="{{ __('Filiale suchen...') }}"
                            class="w-full px-3 py-2 text-sm border-gray-300 rounded-md focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-700 dark:border-gray-600"
                            @click.stop
                        />
                    </div>
                    @endif

                    {{-- All Branches Option --}}
                    <a
                        href="{{ url()->current() . '?branch=all' }}"
                        class="block px-3 py-2 rounded-md text-base font-medium {{ $isAllBranches ? 'bg-primary-100 text-primary-900 dark:bg-primary-900/20 dark:text-primary-300' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50' }}"
                        @click="mobileMenuOpen = false"
                    >
                        {{ __('Alle Filialen') }}
                    </a>

                    {{-- Individual Branches --}}
                    <template x-for="branch in filteredBranches" :key="branch.id">
                        <a
                            :href="window.location.pathname + '?branch=' + branch.id"
                            class="block px-3 py-2 rounded-md text-base font-medium"
                            :class="{
                                'bg-primary-100 text-primary-900 dark:bg-primary-900/20 dark:text-primary-300': currentBranch && currentBranch.id === branch.id,
                                'text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50': !(currentBranch && currentBranch.id === branch.id)
                            }"
                            @click="mobileMenuOpen = false"
                            x-text="branch.name"
                        ></a>
                    </template>

                    {{-- No Results --}}
                    <template x-if="filteredBranches.length === 0">
                        <div class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('Keine Filialen gefunden') }}
                        </div>
                    </template>
                </div>
            </div>

            {{-- Navigation Links --}}
            <nav class="px-5 py-4 space-y-1">
                @foreach($navigation ?? [] as $item)
                    @if($item->isVisible())
                        <a
                            href="{{ $item->getUrl() }}"
                            class="flex items-center px-3 py-2 rounded-md text-base font-medium {{ $item->isActive() ? 'bg-primary-100 text-primary-900 dark:bg-primary-900/20 dark:text-primary-300' : 'text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50' }}"
                            @click="mobileMenuOpen = false"
                        >
                            @if($item->getIcon())
                                <x-filament::icon :icon="$item->getIcon()" class="mr-3 h-5 w-5" />
                            @endif
                            {{ $item->getLabel() }}
                            @if($item->getBadge())
                                <span class="ml-auto inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 dark:bg-primary-800 dark:text-primary-100">
                                    {{ $item->getBadge() }}
                                </span>
                            @endif
                        </a>
                    @endif
                @endforeach
            </nav>

            {{-- Account Actions --}}
            <div class="px-5 py-4 space-y-1">
                <a
                    href="{{ filament()->getProfileUrl() }}"
                    class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50"
                    @click="mobileMenuOpen = false"
                >
                    <x-filament::icon icon="heroicon-o-user-circle" class="inline-block mr-3 h-5 w-5" />
                    {{ __('Profil') }}
                </a>
                
                <form method="POST" action="{{ filament()->getLogoutUrl() }}" class="block">
                    @csrf
                    <button
                        type="submit"
                        class="w-full text-left px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-gray-900 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-700/50"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" class="inline-block mr-3 h-5 w-5" />
                        {{ __('Abmelden') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>