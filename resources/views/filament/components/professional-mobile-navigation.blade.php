@php
    use App\Services\BranchContextManager;
    
    // Get navigation items from Filament
    $navigation = filament()->getNavigation();
    
    // Get current user
    $user = auth()->user();
    
    // Get branch context
    try {
        $branchContext = app(BranchContextManager::class);
        $currentBranch = $branchContext->getCurrentBranch();
        $isAllBranches = $branchContext->isAllBranchesView();
        $branches = $branchContext->getBranchesForUser() ?? collect();
    } catch (\Exception $e) {
        $currentBranch = null;
        $isAllBranches = false;
        $branches = collect();
    }
@endphp

{{-- Professional Mobile Navigation - Only visible on mobile --}}
<div 
    x-data="{ 
        mobileMenuOpen: false,
        branchDropdownOpen: false,
        searchQuery: '',
        isAnimating: false,
        get filteredBranches() {
            if (!this.searchQuery) return @js($branches);
            return @js($branches).filter(branch => 
                branch.name.toLowerCase().includes(this.searchQuery.toLowerCase())
            );
        },
        toggleMenu() {
            if (this.isAnimating) return;
            this.isAnimating = true;
            this.mobileMenuOpen = !this.mobileMenuOpen;
            if (this.mobileMenuOpen) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
                this.branchDropdownOpen = false;
                this.searchQuery = '';
            }
            setTimeout(() => this.isAnimating = false, 300);
        }
    }"
    @keydown.escape.window="if (mobileMenuOpen) { toggleMenu() }"
    class="lg:hidden"
>
    {{-- Professional Burger Button --}}
    <button
        @click="toggleMenu()"
        type="button"
        class="professional-burger-btn relative inline-flex items-center justify-center p-2.5 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100/80 dark:text-gray-300 dark:hover:text-white dark:hover:bg-gray-700/50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 dark:focus:ring-offset-gray-900"
        :aria-expanded="mobileMenuOpen"
        aria-label="{{ __('Hauptmenü') }}"
    >
        <span class="sr-only">{{ __('Hauptmenü öffnen') }}</span>
        
        {{-- Animated Burger Icon --}}
        <div class="burger-icon w-6 h-5 relative">
            <span 
                class="burger-line absolute left-0 w-full h-0.5 bg-current rounded-full transition-all duration-300 ease-out"
                :class="{
                    'top-2 rotate-45': mobileMenuOpen,
                    'top-0': !mobileMenuOpen
                }"
            ></span>
            <span 
                class="burger-line absolute left-0 top-2 w-full h-0.5 bg-current rounded-full transition-all duration-300 ease-out"
                :class="{
                    'opacity-0 scale-x-0': mobileMenuOpen,
                    'opacity-100 scale-x-100': !mobileMenuOpen
                }"
            ></span>
            <span 
                class="burger-line absolute left-0 w-full h-0.5 bg-current rounded-full transition-all duration-300 ease-out"
                :class="{
                    'top-2 -rotate-45': mobileMenuOpen,
                    'top-4': !mobileMenuOpen
                }"
            ></span>
        </div>
    </button>

    {{-- Mobile Menu Backdrop --}}
    <div
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="toggleMenu()"
        class="fixed inset-0 z-40 bg-gray-900/50 backdrop-blur-sm lg:hidden"
        style="display: none;"
    ></div>

    {{-- Professional Slide-in Menu Panel --}}
    <div
        x-show="mobileMenuOpen"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-50 w-80 max-w-[85vw] bg-white dark:bg-gray-900 shadow-2xl lg:hidden"
        style="display: none;"
        @click.stop
    >
        <div class="h-full flex flex-col">
            {{-- Header with User Info --}}
            <div class="flex-shrink-0 px-6 pt-6 pb-4 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        @if($user)
                            <x-filament::avatar
                                :src="filament()->getUserAvatarUrl($user)"
                                :alt="filament()->getUserName($user)"
                                class="h-12 w-12 ring-2 ring-white dark:ring-gray-800"
                            />
                            <div>
                                <div class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ filament()->getUserName($user) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $user->email }}
                                </div>
                            </div>
                        @endif
                    </div>
                    <button
                        @click="toggleMenu()"
                        type="button"
                        class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:text-gray-200 dark:hover:bg-gray-800 transition-colors duration-200"
                    >
                        <x-filament::icon icon="heroicon-o-x-mark" class="h-5 w-5" />
                    </button>
                </div>
            </div>

            {{-- Branch Switcher --}}
            <div class="flex-shrink-0 px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <button
                    @click="branchDropdownOpen = !branchDropdownOpen"
                    type="button"
                    class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors duration-200"
                    :aria-expanded="branchDropdownOpen"
                >
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-lg">
                            <x-filament::icon icon="heroicon-o-building-office-2" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        </div>
                        <div class="text-left">
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('Aktuelle Filiale') }}</div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
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
                        ::class="{ 'rotate-180': branchDropdownOpen }"
                    />
                </button>

                {{-- Branch Dropdown --}}
                <div
                    x-show="branchDropdownOpen"
                    x-collapse
                    class="mt-2 space-y-1"
                >
                    {{-- Search Field --}}
                    @if($branches->count() > 5)
                    <div class="px-2 pb-2">
                        <input
                            type="text"
                            x-model="searchQuery"
                            placeholder="{{ __('Suchen...') }}"
                            class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-800 dark:text-white"
                            @click.stop
                        />
                    </div>
                    @endif

                    <div class="max-h-64 overflow-y-auto professional-scrollbar space-y-1">
                        {{-- All Branches Option --}}
                        <a
                            href="{{ url()->current() . '?branch=all' }}"
                            class="branch-item flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150 {{ $isAllBranches ? 'bg-primary-100 text-primary-900 dark:bg-primary-900/30 dark:text-primary-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                            @click="toggleMenu()"
                        >
                            <x-filament::icon icon="heroicon-o-squares-2x2" class="mr-2 h-4 w-4" />
                            {{ __('Alle Filialen') }}
                        </a>

                        {{-- Individual Branches --}}
                        <template x-for="branch in filteredBranches" :key="branch.id">
                            <a
                                :href="window.location.pathname + '?branch=' + branch.id"
                                class="branch-item flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-150"
                                :class="{
                                    'bg-primary-100 text-primary-900 dark:bg-primary-900/30 dark:text-primary-300': @js($currentBranch?->id) === branch.id,
                                    'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800': @js($currentBranch?->id) !== branch.id
                                }"
                                @click="toggleMenu()"
                            >
                                <x-filament::icon icon="heroicon-o-building-office" class="mr-2 h-4 w-4" />
                                <span x-text="branch.name"></span>
                            </a>
                        </template>

                        {{-- No Results --}}
                        <template x-if="filteredBranches.length === 0">
                            <div class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                                {{ __('Keine Filialen gefunden') }}
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Navigation Links --}}
            <nav class="flex-1 overflow-y-auto px-6 py-4 professional-scrollbar">
                <div class="space-y-1">
                    @foreach($navigation as $group)
                        @if($group->getItems())
                            <div class="pt-4 first:pt-0">
                                @if($group->getLabel())
                                    <div class="px-3 pb-2 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        {{ $group->getLabel() }}
                                    </div>
                                @endif
                                
                                @foreach($group->getItems() as $item)
                                    @if($item->isVisible())
                                        <a
                                            href="{{ $item->getUrl() }}"
                                            class="nav-item group flex items-center px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150 {{ $item->isActive() ? 'bg-primary-100 text-primary-900 dark:bg-primary-900/30 dark:text-primary-300' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}"
                                            @click="toggleMenu()"
                                        >
                                            @if($item->getIcon())
                                                <x-filament::icon 
                                                    :icon="$item->getIcon()" 
                                                    class="mr-3 h-5 w-5 {{ $item->isActive() ? 'text-primary-600 dark:text-primary-400' : 'text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300' }}" 
                                                />
                                            @endif
                                            <span class="flex-1">{{ $item->getLabel() }}</span>
                                            @if($item->getBadge())
                                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $item->getBadgeColor() === 'danger' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' }}">
                                                    {{ $item->getBadge() }}
                                                </span>
                                            @endif
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    @endforeach
                </div>
            </nav>

            {{-- Footer Actions --}}
            <div class="flex-shrink-0 px-6 py-4 border-t border-gray-200 dark:border-gray-700 space-y-2">
                @if(filament()->hasProfile())
                <a
                    href="{{ filament()->getProfileUrl() }}"
                    class="flex items-center px-4 py-3 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800 transition-colors duration-150"
                    @click="toggleMenu()"
                >
                    <x-filament::icon icon="heroicon-o-user-circle" class="mr-3 h-5 w-5 text-gray-400" />
                    {{ __('filament-panels::layout.actions.profile.label') }}
                </a>
                @endif

                <form method="POST" action="{{ filament()->getLogoutUrl() }}">
                    @csrf
                    <button
                        type="submit"
                        class="w-full flex items-center px-4 py-3 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/10 transition-colors duration-150"
                    >
                        <x-filament::icon icon="heroicon-o-arrow-right-on-rectangle" class="mr-3 h-5 w-5" />
                        {{ __('filament-panels::layout.actions.logout.label') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
    /* Professional Scrollbar */
    .professional-scrollbar {
        scrollbar-width: thin;
        scrollbar-color: rgba(156, 163, 175, 0.3) transparent;
    }
    
    .professional-scrollbar::-webkit-scrollbar {
        width: 6px;
    }
    
    .professional-scrollbar::-webkit-scrollbar-track {
        background: transparent;
    }
    
    .professional-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(156, 163, 175, 0.3);
        border-radius: 3px;
    }
    
    .professional-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: rgba(156, 163, 175, 0.5);
    }
    
    /* Dark mode scrollbar */
    .dark .professional-scrollbar {
        scrollbar-color: rgba(75, 85, 99, 0.5) transparent;
    }
    
    .dark .professional-scrollbar::-webkit-scrollbar-thumb {
        background-color: rgba(75, 85, 99, 0.5);
    }
    
    .dark .professional-scrollbar::-webkit-scrollbar-thumb:hover {
        background-color: rgba(75, 85, 99, 0.7);
    }
</style>