<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Simple Header --}}
        <div class="bg-gradient-to-r from-primary-600 to-primary-700 text-white rounded-lg p-6">
            <h1 class="text-2xl font-bold mb-2">Documentation Hub</h1>
            <p class="text-white/90">All essential documentation in one place - no popups, just content!</p>
        </div>
        
        {{-- Search Bar --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4">
            <div class="relative">
                <x-heroicon-o-magnifying-glass class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                <input type="text"
                       wire:model.live.debounce.300ms="search"
                       placeholder="Search documentation..."
                       class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700">
            </div>
        </div>
        
        {{-- Documents Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($this->getFilteredDocuments() as $doc)
                <a href="{{ $doc['url'] }}" 
                   target="_blank"
                   class="block bg-white dark:bg-gray-800 rounded-lg shadow-sm hover:shadow-md transition-shadow p-6">
                    <div class="flex items-start gap-4">
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                {{ $doc['title'] }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $doc['description'] }}
                            </p>
                            <div class="mt-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $doc['color'] }}-100 text-{{ $doc['color'] }}-800 dark:bg-{{ $doc['color'] }}-900/20 dark:text-{{ $doc['color'] }}-300">
                                    {{ ucfirst($doc['category']) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
        
        @if(count($this->getFilteredDocuments()) === 0)
            <div class="text-center py-12">
                <x-heroicon-o-document-magnifying-glass class="mx-auto h-12 w-12 text-gray-400" />
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No documents found</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Try adjusting your search terms
                </p>
            </div>
        @endif
    </div>
</x-filament-panels::page>