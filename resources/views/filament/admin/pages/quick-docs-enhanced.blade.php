@vite(['resources/css/filament/admin/quick-docs-enhanced.css'])

<x-filament-panels::page>
    {{-- Keyboard shortcut listener --}}
    <div x-data="quickDocsEnhanced" 
         x-init="init()"
         @keydown.window="handleKeydown($event)"
         class="relative">
        
        {{-- Command Palette removed - was not working properly --}}
        
        {{-- Onboarding Tour removed - was not working properly --}}
        
        <div class="space-y-8">
            {{-- Enhanced Header with Search --}}
            <div class="bg-gradient-to-br from-primary-600 via-primary-500 to-purple-600 text-white rounded-2xl p-8 relative overflow-hidden">
                {{-- Background pattern --}}
                <div class="absolute inset-0 opacity-10">
                    <svg class="w-full h-full" xmlns="http://www.w3.org/2000/svg">
                        <defs>
                            <pattern id="docs-pattern" x="0" y="0" width="40" height="40" patternUnits="userSpaceOnUse">
                                <circle cx="20" cy="20" r="1" fill="currentColor" />
                            </pattern>
                        </defs>
                        <rect width="100%" height="100%" fill="url(#docs-pattern)" />
                    </svg>
                </div>
                
                <div class="relative z-10">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                                <x-heroicon-o-sparkles class="w-8 h-8" />
                                Documentation Hub
                            </h1>
                            <p class="text-white/90 text-lg">
                                Everything you need to master AskProAI - now with AI-powered search and insights
                            </p>
                        </div>
                        
                        {{-- Quick Stats --}}
                        <div class="hidden lg:flex items-center gap-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold">{{ count($documents) }}</div>
                                <div class="text-sm text-white/70">Documents</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold">{{ collect($documents)->sum('views') }}</div>
                                <div class="text-sm text-white/70">Total Views</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold">{{ round(collect($documents)->avg('rating'), 1) }}</div>
                                <div class="text-sm text-white/70">Avg Rating</div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Enhanced Search Bar --}}
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <x-heroicon-o-magnifying-glass class="h-5 w-5 text-gray-400" />
                        </div>
                        <input type="text"
                               wire:model.live.debounce.300ms="search"
                               placeholder="Search documentation, commands, or ask a question..."
                               class="w-full pl-12 pr-4 py-4 bg-white/10 backdrop-blur-sm border border-white/20 rounded-xl text-white placeholder-white/60 focus:bg-white/20 focus:border-white/40 focus:ring-0 transition-all"
                               @focus="searchFocused = true">
                        
                        {{-- Search shortcuts removed --}}
                    </div>
                    
                    {{-- Quick Filters --}}
                    <div class="flex flex-wrap gap-2 mt-4">
                        <button wire:click="$set('selectedCategories', [])"
                                class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-medium transition-colors {{ empty($selectedCategories) ? 'ring-2 ring-white' : '' }}">
                            All Categories
                        </button>
                        @foreach(['critical', 'process', 'technical', 'reference'] as $category)
                            <button wire:click="$toggle('selectedCategories', '{{ $category }}')"
                                    class="px-4 py-2 bg-white/20 hover:bg-white/30 rounded-lg text-sm font-medium transition-colors {{ in_array($category, $selectedCategories) ? 'ring-2 ring-white' : '' }}">
                                {{ ucfirst($category) }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
            
            {{-- View Mode & Sort Controls --}}
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div class="flex items-center gap-4">
                    {{-- View Mode Toggle --}}
                    <div class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                        <button wire:click="$set('viewMode', 'grid')"
                                class="p-2 rounded {{ $viewMode === 'grid' ? 'bg-white dark:bg-gray-700 shadow' : '' }} transition-all">
                            <x-heroicon-o-squares-2x2 class="w-5 h-5" />
                        </button>
                        <button wire:click="$set('viewMode', 'list')"
                                class="p-2 rounded {{ $viewMode === 'list' ? 'bg-white dark:bg-gray-700 shadow' : '' }} transition-all">
                            <x-heroicon-o-list-bullet class="w-5 h-5" />
                        </button>
                        <button wire:click="$set('viewMode', 'compact')"
                                class="p-2 rounded {{ $viewMode === 'compact' ? 'bg-white dark:bg-gray-700 shadow' : '' }} transition-all">
                            <x-heroicon-o-bars-3 class="w-5 h-5" />
                        </button>
                    </div>
                    
                    {{-- Difficulty Filter --}}
                    <select wire:model.change="selectedDifficulty"
                            class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg">
                        <option value="">All Levels</option>
                        <option value="beginner">Beginner</option>
                        <option value="intermediate">Intermediate</option>
                        <option value="advanced">Advanced</option>
                    </select>
                </div>
                
                {{-- Sort Options --}}
                <select wire:model.change="sortBy"
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg">
                    <option value="relevance">Most Relevant</option>
                    <option value="newest">Newest First</option>
                    <option value="popular">Most Popular</option>
                    <option value="rating">Highest Rated</option>
                    <option value="reading_time">Reading Time</option>
                </select>
            </div>
            
            {{-- Recently Viewed Section --}}
            @if(count($recentlyViewed) > 0)
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-6">
                <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                    <x-heroicon-o-clock class="w-5 h-5" />
                    Continue Reading
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
                    @foreach($recentlyViewed as $docId)
                        @php
                            $doc = collect($documents)->firstWhere('id', $docId);
                        @endphp
                        @if($doc)
                        <a href="{{ $doc['url'] }}" 
                           wire:click="trackDocumentView('{{ $doc['id'] }}')"
                           class="group bg-white dark:bg-gray-800 rounded-lg p-4 hover:shadow-md transition-all">
                            <h4 class="font-medium text-sm group-hover:text-primary-600 line-clamp-2">
                                {{ $doc['title'] }}
                            </h4>
                            <div class="mt-2">
                                @if(isset($readingProgress[$docId]))
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                    <div class="bg-primary-600 h-1.5 rounded-full transition-all"
                                         style="width: {{ $readingProgress[$docId] }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 mt-1">{{ $readingProgress[$docId] }}% complete</span>
                                @endif
                            </div>
                        </a>
                        @endif
                    @endforeach
                </div>
            </div>
            @endif
            
            {{-- Main Content Area --}}
            <div class="grid grid-cols-12 gap-8">
                {{-- Main Documents Grid --}}
                <div class="col-span-12 lg:col-span-9">
                    {{-- Grid View --}}
                    @if($viewMode === 'grid')
                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($documents as $doc)
                        <div x-data="{ 
                                hover: false, 
                                favorite: @js(in_array($doc['id'], $favorites)),
                                showPreview: false 
                             }"
                             @mouseenter="hover = true"
                             @mouseleave="hover = false"
                             class="group relative bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden"
                             :class="{ 'ring-2 ring-primary-500': favorite }">
                            
                            {{-- Category Badge --}}
                            <div class="absolute top-4 left-4 z-10">
                                <span class="px-3 py-1 text-xs font-medium rounded-full 
                                    @if($doc['category'] === 'critical') bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300
                                    @elseif($doc['category'] === 'process') bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300
                                    @elseif($doc['category'] === 'technical') bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300
                                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                    @endif">
                                    {{ ucfirst($doc['category']) }}
                                </span>
                            </div>
                            
                            {{-- Favorite Button --}}
                            <button @click.prevent="favorite = !favorite; $wire.toggleFavorite('{{ $doc['id'] }}')"
                                    class="absolute top-4 right-4 z-10 p-2 bg-white/80 dark:bg-gray-700/80 backdrop-blur-sm rounded-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                <x-heroicon-s-star class="w-5 h-5 transition-colors"
                                                   ::class="favorite ? 'text-yellow-500' : 'text-gray-400'" />
                            </button>
                            
                            <a href="{{ $doc['url'] }}" 
                               wire:click="trackDocumentView('{{ $doc['id'] }}')"
                               class="block p-6 pt-16">
                                {{-- Icon & Title --}}
                                <div class="flex items-start gap-4 mb-4">
                                    <div class="flex-shrink-0 w-12 h-12 bg-{{ $doc['color'] }}-100 dark:bg-{{ $doc['color'] }}-900/30 rounded-xl flex items-center justify-center">
                                        @if($doc['icon'] === 'rocket-launch')
                                            <x-heroicon-o-rocket-launch class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                        @elseif($doc['icon'] === 'fire')
                                            <x-heroicon-o-fire class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                        @elseif($doc['icon'] === 'magnifying-glass')
                                            <x-heroicon-o-magnifying-glass class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                        @elseif($doc['icon'] === 'phone')
                                            <x-heroicon-o-phone class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                        @elseif($doc['icon'] === 'chart-bar')
                                            <x-heroicon-o-chart-bar class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                        @elseif($doc['icon'] === 'heart')
                                            <x-heroicon-o-heart class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                        @elseif($doc['icon'] === 'book-open')
                                            <x-heroicon-o-book-open class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                        @elseif($doc['icon'] === 'cube')
                                            <x-heroicon-o-cube class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                        @elseif($doc['icon'] === 'key')
                                            <x-heroicon-o-key class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                        @elseif($doc['icon'] === 'shield-check')
                                            <x-heroicon-o-shield-check class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                        @elseif($doc['icon'] === 'server')
                                            <x-heroicon-o-server class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                        @else
                                            <x-heroicon-o-document-text class="w-6 h-6 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white group-hover:text-{{ $doc['color'] }}-600 dark:group-hover:text-{{ $doc['color'] }}-400 transition-colors line-clamp-2">
                                            {{ $doc['title'] }}
                                        </h3>
                                    </div>
                                </div>
                                
                                {{-- Description --}}
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                                    {{ $doc['description'] }}
                                </p>
                                
                                {{-- AI Summary (hidden by default, shown on hover) --}}
                                <div class="mb-4 overflow-hidden transition-all duration-300"
                                     :style="hover ? 'max-height: 100px' : 'max-height: 0'">
                                    <p class="text-xs text-gray-500 dark:text-gray-500 italic">
                                        {{ $doc['aiSummary'] }}
                                    </p>
                                </div>
                                
                                {{-- Meta Information --}}
                                <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 mb-4">
                                    <div class="flex items-center gap-4">
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-m-clock class="w-4 h-4" />
                                            {{ $doc['readingTime'] }} min
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-m-eye class="w-4 h-4" />
                                            {{ number_format($doc['views']) }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-m-star class="w-4 h-4 text-yellow-500" />
                                            {{ $doc['rating'] }}
                                        </span>
                                    </div>
                                    <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                                        {{ ucfirst($doc['difficulty']) }}
                                    </span>
                                </div>
                                
                                {{-- Features --}}
                                @if(count($doc['features']) > 0)
                                <div class="space-y-2 mb-4">
                                    @foreach(array_slice($doc['features'], 0, 2) as $feature)
                                    <div class="flex items-start gap-2 text-xs text-gray-600 dark:text-gray-400">
                                        <x-heroicon-m-check-circle class="w-4 h-4 text-green-500 flex-shrink-0 mt-0.5" />
                                        <span>{{ $feature }}</span>
                                    </div>
                                    @endforeach
                                    @if(count($doc['features']) > 2)
                                    <div class="text-xs text-gray-500">
                                        +{{ count($doc['features']) - 2 }} more features
                                    </div>
                                    @endif
                                </div>
                                @endif
                                
                                {{-- Tags --}}
                                <div class="flex flex-wrap gap-1">
                                    @foreach($doc['tags'] as $tag)
                                    <span class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">
                                        #{{ $tag }}
                                    </span>
                                    @endforeach
                                </div>
                                
                                {{-- Interactive Badges --}}
                                <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    @if($doc['interactive'])
                                    <span class="flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400">
                                        <x-heroicon-m-cursor-arrow-rays class="w-4 h-4" />
                                        Interactive
                                    </span>
                                    @endif
                                    @if($doc['hasVideo'])
                                    <span class="flex items-center gap-1 text-xs text-purple-600 dark:text-purple-400">
                                        <x-heroicon-m-video-camera class="w-4 h-4" />
                                        Video
                                    </span>
                                    @endif
                                    <span class="ml-auto text-xs text-gray-500">
                                        v{{ $doc['version'] }}
                                    </span>
                                </div>
                            </a>
                            
                            {{-- Quick Actions Bar --}}
                            <div class="absolute bottom-0 left-0 right-0 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700 px-4 py-2 flex items-center justify-between opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="shareDocument('{{ $doc['id'] }}')"
                                        class="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                        title="Share">
                                    <x-heroicon-m-share class="w-4 h-4" />
                                </button>
                                <button wire:click="exportDocument('{{ $doc['id'] }}')"
                                        class="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                        title="Export to PDF">
                                    <x-heroicon-m-arrow-down-tray class="w-4 h-4" />
                                </button>
                                <button @click="showPreview = true"
                                        class="p-2 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition-colors"
                                        title="Quick Preview">
                                    <x-heroicon-m-eye class="w-4 h-4" />
                                </button>
                            </div>
                            
                            {{-- Reading Progress Indicator --}}
                            @if(isset($readingProgress[$doc['id']]))
                            <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700">
                                <div class="h-full bg-primary-600 transition-all duration-300"
                                     style="width: {{ $readingProgress[$doc['id']] }}%"></div>
                            </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                    @endif
                    
                    {{-- List View --}}
                    @if($viewMode === 'list')
                    <div class="space-y-4">
                        @foreach($documents as $doc)
                        <div x-data="{ favorite: @js(in_array($doc['id'], $favorites)) }"
                             class="group bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-lg transition-all p-6"
                             :class="{ 'ring-2 ring-primary-500': favorite }">
                            <div class="flex items-start gap-6">
                                {{-- Icon --}}
                                <div class="flex-shrink-0 w-16 h-16 bg-{{ $doc['color'] }}-100 dark:bg-{{ $doc['color'] }}-900/30 rounded-xl flex items-center justify-center">
                                    @if($doc['icon'] === 'rocket-launch')
                                        <x-heroicon-o-rocket-launch class="w-8 h-8 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                    @elseif($doc['icon'] === 'fire')
                                        <x-heroicon-o-fire class="w-8 h-8 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                    @else
                                        <x-heroicon-o-document-text class="w-8 h-8 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                    @endif
                                </div>
                                
                                {{-- Content --}}
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-2">
                                        <div>
                                            <a href="{{ $doc['url'] }}" 
                                               wire:click="trackDocumentView('{{ $doc['id'] }}')"
                                               class="text-xl font-semibold text-gray-900 dark:text-white hover:text-{{ $doc['color'] }}-600 dark:hover:text-{{ $doc['color'] }}-400 transition-colors">
                                                {{ $doc['title'] }}
                                            </a>
                                            <div class="flex items-center gap-4 mt-1 text-sm text-gray-500 dark:text-gray-400">
                                                <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 rounded text-xs">
                                                    {{ ucfirst($doc['category']) }}
                                                </span>
                                                <span>{{ ucfirst($doc['difficulty']) }}</span>
                                                <span>{{ $doc['readingTime'] }} min read</span>
                                                <span>v{{ $doc['version'] }}</span>
                                            </div>
                                        </div>
                                        
                                        {{-- Actions --}}
                                        <div class="flex items-center gap-2">
                                            <button @click="favorite = !favorite; $wire.toggleFavorite('{{ $doc['id'] }}')"
                                                    class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                                <x-heroicon-s-star class="w-5 h-5"
                                                                   ::class="favorite ? 'text-yellow-500' : 'text-gray-400'" />
                                            </button>
                                            <a href="{{ $doc['url'] }}" 
                                               class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                                <x-heroicon-m-arrow-top-right-on-square class="w-5 h-5" />
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <p class="text-gray-600 dark:text-gray-400 mb-3">
                                        {{ $doc['aiSummary'] }}
                                    </p>
                                    
                                    {{-- Features Grid --}}
                                    <div class="grid grid-cols-2 gap-2 mb-3">
                                        @foreach($doc['features'] as $feature)
                                        <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                                            <x-heroicon-m-check-circle class="w-4 h-4 text-green-500 flex-shrink-0" />
                                            <span>{{ $feature }}</span>
                                        </div>
                                        @endforeach
                                    </div>
                                    
                                    {{-- Bottom Info --}}
                                    <div class="flex items-center justify-between pt-3 border-t border-gray-200 dark:border-gray-700">
                                        <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                                            <span class="flex items-center gap-1">
                                                <x-heroicon-m-eye class="w-4 h-4" />
                                                {{ number_format($doc['views']) }} views
                                            </span>
                                            <span class="flex items-center gap-1">
                                                <x-heroicon-m-star class="w-4 h-4 text-yellow-500" />
                                                {{ $doc['rating'] }}
                                            </span>
                                            <span>Updated {{ \Carbon\Carbon::parse($doc['lastUpdated'])->diffForHumans() }}</span>
                                        </div>
                                        
                                        <div class="flex items-center gap-2">
                                            @if($doc['interactive'])
                                            <span class="px-2 py-1 bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs rounded">
                                                Interactive
                                            </span>
                                            @endif
                                            @if($doc['hasVideo'])
                                            <span class="px-2 py-1 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 text-xs rounded">
                                                Video
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    
                    {{-- Compact View --}}
                    @if($viewMode === 'compact')
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Document</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Level</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rating</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($documents as $doc)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ $doc['url'] }}" 
                                           wire:click="trackDocumentView('{{ $doc['id'] }}')"
                                           class="flex items-center gap-3 group">
                                            <div class="flex-shrink-0 w-8 h-8 bg-{{ $doc['color'] }}-100 dark:bg-{{ $doc['color'] }}-900/30 rounded-lg flex items-center justify-center">
                                                @if($doc['icon'] === 'rocket-launch')
                                                    <x-heroicon-o-rocket-launch class="w-4 h-4 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                                @else
                                                    <x-heroicon-o-document-text class="w-4 h-4 text-{{ $doc['color'] }}-600 dark:text-{{ $doc['color'] }}-400" />
                                                @endif
                                            </div>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400">
                                                    {{ $doc['title'] }}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ Str::limit($doc['description'], 50) }}
                                                </div>
                                            </div>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full 
                                            @if($doc['category'] === 'critical') bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300
                                            @elseif($doc['category'] === 'process') bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300
                                            @elseif($doc['category'] === 'technical') bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300
                                            @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300
                                            @endif">
                                            {{ ucfirst($doc['category']) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ ucfirst($doc['difficulty']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $doc['readingTime'] }} min
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-1 text-sm">
                                            <x-heroicon-m-star class="w-4 h-4 text-yellow-500" />
                                            {{ $doc['rating'] }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center gap-2">
                                            <button wire:click="toggleFavorite('{{ $doc['id'] }}')"
                                                    class="text-gray-400 hover:text-yellow-500">
                                                @if(in_array($doc['id'], $favorites))
                                                    <x-heroicon-s-star class="w-5 h-5 text-yellow-500" />
                                                @else
                                                    <x-heroicon-o-star class="w-5 h-5" />
                                                @endif
                                            </button>
                                            <a href="{{ $doc['url'] }}" 
                                               class="text-primary-600 hover:text-primary-900">
                                                View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
                
                {{-- Sidebar --}}
                <div class="col-span-12 lg:col-span-3 space-y-6">
                    {{-- Quick Stats Widget --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <x-heroicon-o-chart-pie class="w-5 h-5" />
                            Your Progress
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span>Documents Read</span>
                                    <span class="font-medium">{{ count($recentlyViewed) }} / {{ count($documents) }}</span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                    <div class="bg-primary-600 h-2 rounded-full transition-all"
                                         style="width: {{ (count($recentlyViewed) / count($documents)) * 100 }}%"></div>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span>Favorites</span>
                                    <span class="font-medium">{{ count($favorites) }}</span>
                                </div>
                            </div>
                            
                            <div>
                                <div class="flex items-center justify-between text-sm mb-1">
                                    <span>Total Reading Time</span>
                                    <span class="font-medium">{{ collect($recentlyViewed)->sum(fn($id) => collect($documents)->firstWhere('id', $id)['readingTime'] ?? 0) }} min</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Popular Documents --}}
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <x-heroicon-o-fire class="w-5 h-5 text-orange-500" />
                            Trending Now
                        </h3>
                        <div class="space-y-3">
                            @foreach($popularDocs as $index => $doc)
                            <a href="{{ $doc['url'] }}" 
                               wire:click="trackDocumentView('{{ $doc['id'] }}')"
                               class="flex items-start gap-3 group">
                                <span class="flex-shrink-0 w-6 h-6 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center text-xs font-medium">
                                    {{ $index + 1 }}
                                </span>
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-gray-900 dark:text-white group-hover:text-primary-600 dark:group-hover:text-primary-400 line-clamp-2">
                                        {{ $doc['title'] }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ number_format($doc['views']) }} views • {{ $doc['readingTime'] }} min
                                    </p>
                                </div>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    
                    {{-- Related Documents --}}
                    @if(count($relatedDocs) > 0)
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm p-6">
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <x-heroicon-o-link class="w-5 h-5" />
                            Related Documents
                        </h3>
                        <div class="space-y-3">
                            @foreach($relatedDocs as $doc)
                            <a href="{{ $doc['url'] }}" 
                               wire:click="trackDocumentView('{{ $doc['id'] }}')"
                               class="block p-3 bg-gray-50 dark:bg-gray-900 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2">
                                    {{ $doc['title'] }}
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ ucfirst($doc['category']) }} • {{ $doc['readingTime'] }} min
                                </p>
                            </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    {{-- Help & Support --}}
                    <div class="bg-gradient-to-br from-primary-50 to-primary-100 dark:from-primary-900/20 dark:to-primary-800/20 rounded-xl p-6">
                        <h3 class="text-lg font-semibold mb-2 text-primary-900 dark:text-primary-100">
                            Need Help?
                        </h3>
                        <p class="text-sm text-primary-700 dark:text-primary-300 mb-4">
                            Can't find what you're looking for? Our team is here to help.
                        </p>
                        <button class="w-full bg-primary-600 hover:bg-primary-700 text-white rounded-lg py-2 px-4 font-medium transition-colors">
                            Contact Support
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- JavaScript for Enhanced Interactions --}}
    @push('scripts')
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('quickDocsEnhanced', () => ({
            searchFocused: false,
            
            init() {
                this.initKeyboardShortcuts();
                this.initPrefetch();
            },
            
            handleKeydown(event) {
                // Search focus
                if (event.key === '/' && !this.searchFocused) {
                    event.preventDefault();
                    document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="search"]').focus();
                }
            },
            
            
            initKeyboardShortcuts() {
                // Additional keyboard shortcuts
                document.addEventListener('keydown', (e) => {
                    
                    // Quick navigation
                    if (e.key === 'g' && !e.ctrlKey && !e.metaKey) {
                        setTimeout(() => {
                            document.addEventListener('keydown', (e2) => {
                                if (e2.key === 'h') window.location.href = '/admin'; // Go Home
                                if (e2.key === 'd') window.location.href = '/admin/docs-enhanced'; // Go Docs
                            }, { once: true });
                        }, 0);
                    }
                });
            },
            
            initPrefetch() {
                // Prefetch documents on hover
                document.querySelectorAll('a[href*="/mkdocs/"]').forEach(link => {
                    link.addEventListener('mouseenter', () => {
                        const prefetchLink = document.createElement('link');
                        prefetchLink.rel = 'prefetch';
                        prefetchLink.href = link.href;
                        document.head.appendChild(prefetchLink);
                    });
                });
            }
        }));
    });
    
    // Copy to clipboard functionality
    window.addEventListener('copyToClipboard', event => {
        navigator.clipboard.writeText(event.detail.text).then(() => {
            // Show notification
            window.$wireui.notify({
                title: 'Success!',
                description: event.detail.message,
                icon: 'success'
            });
        });
    });
    
    // Export to PDF functionality
    window.addEventListener('exportToPdf', event => {
        // Implementation would depend on your PDF generation service
        console.log('Exporting to PDF:', event.detail);
        window.$wireui.notify({
            title: 'Export Started',
            description: 'Your PDF is being generated...',
            icon: 'info'
        });
    });
    </script>
    @endpush
    
    {{-- Custom Styles --}}
    @push('styles')
    <style>
        /* Custom scrollbar for better UX */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            @apply bg-gray-100 dark:bg-gray-800 rounded-full;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            @apply bg-gray-400 dark:bg-gray-600 rounded-full;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            @apply bg-gray-500 dark:bg-gray-500;
        }
        
        /* Smooth transitions for interactive elements */
        .doc-card-enter {
            animation: docCardEnter 0.3s ease-out;
        }
        
        @keyframes docCardEnter {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Gradient text effect */
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Glow effect on hover */
        .glow-hover:hover {
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
        }
    </style>
    @endpush
</x-filament-panels::page>