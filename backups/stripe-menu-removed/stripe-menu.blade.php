@php
    $navigationService = app(\App\Services\NavigationService::class);
    $navigation = $navigationService->getNavigation();
    $user = Auth::user();
@endphp

{{-- Provide navigation data to JavaScript --}}
<script>
    window.navigationData = @json($navigation);
</script>

{{-- Mobile Menu Overlay --}}
<div class="stripe-menu-overlay"></div>

{{-- Desktop Navigation Bar --}}
<nav class="stripe-menu" x-data="{ scrolled: false }" 
     @scroll.window="scrolled = (window.pageYOffset > 10)"
     :class="{ 'scrolled': scrolled }">
    <div class="stripe-menu-container">
        {{-- Logo --}}
        <a href="{{ function_exists('filament') && filament() ? filament()->getHomeUrl() : '/admin' }}" class="stripe-menu-logo">
            @if(function_exists('filament') && filament() && filament()->hasBrandLogo())
                <img src="{{ asset(filament()->getBrandLogo()) }}" alt="{{ filament()->getBrandName() }}">
            @else
                <span class="text-xl font-bold">{{ function_exists('filament') && filament() ? filament()->getBrandName() : config('app.name', 'AskProAI') }}</span>
            @endif
        </a>

        {{-- Desktop Navigation Items --}}
        <nav class="stripe-menu-nav">
            @foreach($navigation['main'] as $item)
                @if(isset($item['hasMega']) && $item['hasMega'])
                    <div class="stripe-menu-item" 
                         data-mega-trigger="{{ $item['megaContent'] }}"
                         @class(['active' => $item['active'] ?? false])>
                        @if(isset($item['icon']))
                            <x-dynamic-component :component="$item['icon']" class="w-4 h-4 inline mr-1" />
                        @endif
                        {{ $item['label'] }}
                    </div>
                @else
                    <a href="{{ $item['url'] }}" 
                       class="stripe-menu-item"
                       @class(['active' => $item['active'] ?? false])>
                        @if(isset($item['icon']))
                            <x-dynamic-component :component="$item['icon']" class="w-4 h-4 inline mr-1" />
                        @endif
                        {{ $item['label'] }}
                        @if(isset($item['badge']))
                            <span class="stripe-mega-item-badge">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endif
            @endforeach
        </nav>

        {{-- Actions (Right Side) --}}
        <div class="stripe-menu-actions">
            {{-- Search --}}
            <div class="stripe-search">
                <input type="text" 
                       class="stripe-search-input" 
                       placeholder="Search (⌘K)"
                       @keydown.cmd.k.prevent="$dispatch('open-command-palette')"
                       @keydown.ctrl.k.prevent="$dispatch('open-command-palette')">
                <svg class="stripe-search-icon w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
            </div>

            {{-- Notifications --}}
            @if(function_exists('filament') && filament() && filament()->hasDatabaseNotifications())
                @livewire(Filament\Livewire\DatabaseNotifications::class)
            @endif

            {{-- User Menu --}}
            @if($user && function_exists('filament') && filament())
                <x-filament-panels::user-menu />
            @endif

            {{-- Mobile Menu Toggle --}}
            <div class="stripe-hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </div>
</nav>

{{-- Mega Menu --}}
<div class="stripe-mega-menu">
    @foreach($navigation['mega'] as $key => $section)
        <div class="stripe-mega-content" data-mega-content="{{ $key }}">
            <div class="grid grid-cols-{{ count($section['columns']) }} gap-8">
                @foreach($section['columns'] as $column)
                    <div class="stripe-mega-column">
                        <h3 class="stripe-mega-column-title">{{ $column['title'] }}</h3>
                        @foreach($column['items'] as $item)
                            <a href="{{ $item['url'] }}" class="stripe-mega-item">
                                @if(isset($item['icon']))
                                    <x-dynamic-component :component="$item['icon']" class="stripe-mega-item-icon" />
                                @endif
                                <div class="stripe-mega-item-content">
                                    <span class="stripe-mega-item-label">
                                        {{ $item['label'] }}
                                        @if(isset($item['badge']))
                                            <span class="stripe-mega-item-badge">{{ $item['badge'] }}</span>
                                        @endif
                                    </span>
                                    <span class="stripe-mega-item-description">
                                        {{ $item['description'] }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endforeach
                
                @if(isset($section['featured']))
                    <div class="stripe-mega-featured">
                        <h3 class="stripe-mega-featured-title">{{ $section['featured']['title'] }}</h3>
                        @foreach($section['featured']['items'] as $featured)
                            <p class="stripe-mega-featured-description">{{ $featured['description'] }}</p>
                            <a href="{{ $featured['url'] }}" class="stripe-mega-featured-link">
                                Learn more 
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endforeach
</div>

{{-- Mobile Menu --}}
<aside class="stripe-mobile-menu" data-mobile-menu="true">
    <div class="stripe-mobile-menu-header">
        <a href="{{ function_exists('filament') && filament() ? filament()->getHomeUrl() : '/admin' }}">
            @if(function_exists('filament') && filament() && filament()->hasBrandLogo())
                <img src="{{ asset(filament()->getBrandLogo()) }}" alt="{{ filament()->getBrandName() }}" class="h-8">
            @else
                <span class="text-xl font-bold">{{ function_exists('filament') && filament() ? filament()->getBrandName() : config('app.name', 'AskProAI') }}</span>
            @endif
        </a>
    </div>

    <nav class="stripe-mobile-menu-nav">
        {{-- Primary Navigation --}}
        <div class="mb-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Main</h4>
            @foreach($navigation['mobile']['primary'] as $item)
                <a href="{{ $item['url'] }}" class="stripe-mobile-menu-item">
                    @if(isset($item['icon']))
                        <x-dynamic-component :component="$item['icon']" class="stripe-mobile-menu-item-icon" />
                    @endif
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>

        {{-- Secondary Navigation --}}
        <div class="mb-6">
            <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">More</h4>
            @foreach($navigation['mobile']['secondary'] as $item)
                <a href="{{ $item['url'] }}" class="stripe-mobile-menu-item">
                    @if(isset($item['icon']))
                        <x-dynamic-component :component="$item['icon']" class="stripe-mobile-menu-item-icon" />
                    @endif
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>

        {{-- User Section --}}
        @if($user)
            <div class="pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center px-4 py-3">
                    <img class="h-10 w-10 rounded-full" 
                         src="{{ $navigation['user']['profile']['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($user->name) }}" 
                         alt="{{ $user->name }}">
                    <div class="ml-3">
                        <p class="text-sm font-medium">{{ $user->name }}</p>
                        <p class="text-xs text-gray-500">{{ $user->email }}</p>
                    </div>
                </div>
                
                @foreach($navigation['user']['menu'] as $item)
                    @if($item === 'divider')
                        <hr class="my-2 border-gray-200 dark:border-gray-700">
                    @else
                        <a href="{{ $item['url'] }}" class="stripe-mobile-menu-item">
                            @if(isset($item['icon']))
                                <x-dynamic-component :component="$item['icon']" class="stripe-mobile-menu-item-icon" />
                            @endif
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </nav>
</aside>

{{-- Mobile Menu Overlay --}}
<div class="stripe-menu-overlay"></div>

{{-- Command Palette --}}
<div class="stripe-command-palette" x-data="commandPalette" @open-command-palette.window="open()">
    <div class="stripe-command-dialog">
        <input type="text" 
               class="stripe-command-input" 
               placeholder="Search for anything..."
               x-model="search"
               @input="performSearch()"
               @keydown.escape="close()"
               @keydown.arrow-down.prevent="selectNext()"
               @keydown.arrow-up.prevent="selectPrevious()"
               @keydown.enter.prevent="executeSelected()">
        
        <div class="stripe-command-results">
            <template x-for="(result, index) in results" :key="result.id">
                <div class="stripe-command-result" 
                     :class="{ 'active': selectedIndex === index }"
                     @click="execute(result)"
                     @mouseenter="selectedIndex = index">
                    <x-dynamic-component :component="result.icon" class="stripe-command-result-icon" x-if="result.icon" />
                    <div class="stripe-command-result-content">
                        <div class="stripe-command-result-label" x-text="result.label"></div>
                        <div class="stripe-command-result-description" x-text="result.description"></div>
                    </div>
                </div>
            </template>
            
            <div x-show="search && results.length === 0" class="p-8 text-center text-gray-500">
                No results found for "<span x-text="search"></span>"
            </div>
        </div>
    </div>
</div>

{{-- Initialize JavaScript --}}
@push('scripts')
    @vite('resources/js/stripe-menu.js')
    <script>
        // Command Palette Alpine component
        document.addEventListener('alpine:init', () => {
            Alpine.data('commandPalette', () => ({
                open: false,
                search: '',
                results: [],
                selectedIndex: 0,
                searchableItems: @json($navigation['search'] ?? []),
                
                open() {
                    this.open = true;
                    this.$nextTick(() => {
                        this.$el.querySelector('.stripe-command-input').focus();
                    });
                },
                
                close() {
                    this.open = false;
                    this.search = '';
                    this.results = [];
                    this.selectedIndex = 0;
                },
                
                performSearch() {
                    if (!this.search) {
                        this.results = this.searchableItems.slice(0, 8);
                        return;
                    }
                    
                    const query = this.search.toLowerCase();
                    this.results = this.searchableItems
                        .filter(item => {
                            return item.label.toLowerCase().includes(query) ||
                                   item.description.toLowerCase().includes(query) ||
                                   (item.keywords && item.keywords.some(k => k.includes(query)));
                        })
                        .slice(0, 8);
                    
                    this.selectedIndex = 0;
                },
                
                selectNext() {
                    this.selectedIndex = Math.min(this.selectedIndex + 1, this.results.length - 1);
                },
                
                selectPrevious() {
                    this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                },
                
                executeSelected() {
                    if (this.results[this.selectedIndex]) {
                        this.execute(this.results[this.selectedIndex]);
                    }
                },
                
                execute(result) {
                    window.location.href = result.url;
                    this.close();
                }
            }));
        });
    </script>
@endpush

@push('styles')
    @vite('resources/css/stripe-menu.css')
@endpush