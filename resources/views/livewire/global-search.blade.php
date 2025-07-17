<div>
    <!-- Search Trigger (always visible) -->
    <div class="relative">
        <button wire:click="open" 
                class="flex items-center space-x-3 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <span class="text-gray-600">Suchen...</span>
            <kbd class="hidden sm:inline-flex px-2 py-1 text-xs text-gray-500 bg-gray-200 rounded">⌘K</kbd>
        </button>
    </div>

    <!-- Global Search Modal -->
    <div x-data="{ 
            open: @entangle('isOpen'),
            selectedIndex: @entangle('selectedIndex')
         }"
         x-show="open"
         x-on:keydown.escape.window="$wire.close()"
         x-on:keydown.cmd.k.window.prevent="$wire.open()"
         x-on:keydown.ctrl.k.window.prevent="$wire.open()"
         x-on:keydown.arrow-up.prevent="$wire.navigateResults('up')"
         x-on:keydown.arrow-down.prevent="$wire.navigateResults('down')"
         x-on:keydown.enter.prevent="$wire.selectHighlighted()"
         x-on:focus-search-input.window="$refs.searchInput.focus()"
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">
         
        <!-- Backdrop -->
        <div x-show="open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
             @click="$wire.close()"></div>

        <!-- Modal -->
        <div class="flex items-start justify-center min-h-screen pt-20 px-4">
            <div x-show="open"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative bg-white rounded-lg shadow-xl max-w-3xl w-full">
                
                <!-- Search Input -->
                <div class="border-b border-gray-200">
                    <div class="flex items-center px-4 py-3">
                        <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input wire:model.live.debounce.300ms="query"
                               x-ref="searchInput"
                               type="text"
                               class="flex-1 text-lg outline-none"
                               placeholder="Suche nach Kunden, Terminen, Anrufen..."
                               autocomplete="off">
                        <button wire:click="close" class="ml-3 text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    
                    <!-- Category Filters -->
                    <div class="flex items-center space-x-2 px-4 pb-3">
                        <button wire:click="selectCategory(null)"
                                class="px-3 py-1 text-sm rounded-full transition-colors {{ !$selectedCategory ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                            Alle
                        </button>
                        @foreach($categories as $key => $category)
                            <button wire:click="selectCategory('{{ $key }}')"
                                    class="flex items-center space-x-1 px-3 py-1 text-sm rounded-full transition-colors {{ $selectedCategory === $key ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}">
                                <x-dynamic-component :component="$category['icon']" class="w-4 h-4" />
                                <span>{{ $category['label'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Results / Suggestions / Recent -->
                <div class="max-h-96 overflow-y-auto">
                    @if(strlen($query) >= 2)
                        @if($results->isEmpty())
                            <div class="p-8 text-center text-gray-500">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <p>Keine Ergebnisse für "{{ $query }}" gefunden</p>
                                <p class="text-sm mt-2">Versuchen Sie eine andere Suche</p>
                            </div>
                        @else
                            <div class="py-2">
                                @foreach($results as $index => $result)
                                    <div wire:click="selectResult({{ $index }})"
                                         class="px-4 py-3 hover:bg-gray-50 cursor-pointer flex items-center justify-between group {{ $selectedIndex === $index ? 'bg-gray-50' : '' }}">
                                        <div class="flex items-center space-x-3">
                                            <div class="flex-shrink-0">
                                                <x-dynamic-component :component="$result['icon']" class="w-5 h-5 text-gray-400" />
                                            </div>
                                            <div>
                                                <div class="font-medium text-gray-900">
                                                    {!! $result['highlight'] !!}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $result['subtitle'] }} · {{ $result['model'] }}
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Quick Actions -->
                                        @if(!empty($result['actions']))
                                            <div class="flex items-center space-x-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                                @foreach($result['actions'] as $action)
                                                    <button wire:click.stop="quickAction({{ $index }}, '{{ $action['action'] }}')"
                                                            class="p-1 text-gray-400 hover:text-gray-600"
                                                            title="{{ $action['label'] }}">
                                                        <x-dynamic-component :component="$action['icon']" class="w-4 h-4" />
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        <!-- Suggestions -->
                        @if($suggestions->isNotEmpty())
                            <div class="border-t border-gray-200 py-2">
                                <div class="px-4 py-2 text-xs font-medium text-gray-500 uppercase">Vorschläge</div>
                                @foreach($suggestions as $suggestion)
                                    <div wire:click="searchRecent('{{ $suggestion }}')"
                                         class="px-4 py-2 hover:bg-gray-50 cursor-pointer text-sm text-gray-700">
                                        {{ $suggestion }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <!-- Recent Searches -->
                        @if($recentSearches->isNotEmpty())
                            <div class="py-2">
                                <div class="px-4 py-2 text-xs font-medium text-gray-500 uppercase">Letzte Suchen</div>
                                @foreach($recentSearches as $recent)
                                    <div wire:click="searchRecent('{{ $recent }}')"
                                         class="px-4 py-2 hover:bg-gray-50 cursor-pointer flex items-center space-x-2 text-sm">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                        <span class="text-gray-700">{{ $recent }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        
                        <!-- Quick Actions -->
                        <div class="py-2 border-t border-gray-200">
                            <div class="px-4 py-2 text-xs font-medium text-gray-500 uppercase">Schnellaktionen</div>
                            <div class="grid grid-cols-2 gap-2 px-4 py-2">
                                <button @click="$wire.query = 'Neuer Termin'; $wire.search()"
                                        class="flex items-center space-x-2 p-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-sm">
                                    <x-heroicon-o-calendar class="w-5 h-5 text-gray-400" />
                                    <span>Neuer Termin</span>
                                </button>
                                <button @click="$wire.query = 'Anrufe heute'; $wire.search()"
                                        class="flex items-center space-x-2 p-3 bg-gray-50 hover:bg-gray-100 rounded-lg text-sm">
                                    <x-heroicon-o-phone class="w-5 h-5 text-gray-400" />
                                    <span>Heutige Anrufe</span>
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-200 px-4 py-3 text-sm text-gray-500 flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <span class="flex items-center space-x-1">
                            <kbd class="px-2 py-1 text-xs bg-gray-100 rounded">↑↓</kbd>
                            <span>Navigate</span>
                        </span>
                        <span class="flex items-center space-x-1">
                            <kbd class="px-2 py-1 text-xs bg-gray-100 rounded">Enter</kbd>
                            <span>Select</span>
                        </span>
                        <span class="flex items-center space-x-1">
                            <kbd class="px-2 py-1 text-xs bg-gray-100 rounded">Esc</kbd>
                            <span>Close</span>
                        </span>
                    </div>
                    <div>
                        Powered by AskProAI Search
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>