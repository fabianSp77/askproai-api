@extends('portal.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Search Header -->
        <div class="bg-white rounded-lg shadow-lg px-8 py-6 mb-8">
            <h1 class="text-2xl font-bold mb-4">Search Knowledge Base</h1>
            
            <form action="{{ route('portal.knowledge.search') }}" method="GET" class="space-y-4">
                <!-- Search Input -->
                <div>
                    <label for="q" class="sr-only">Search</label>
                    <div class="relative">
                        <input type="text" 
                               id="q"
                               name="q" 
                               placeholder="Enter your search query..." 
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               value="{{ $query }}">
                        <button type="submit" class="absolute right-2 top-2 p-2 text-gray-600 hover:text-gray-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Category Filter -->
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category" 
                                id="category"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">All Categories</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ $categoryId == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tags Filter -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach($allTags as $tag)
                                <label class="inline-flex items-center">
                                    <input type="checkbox" 
                                           name="tags[]" 
                                           value="{{ $tag->id }}"
                                           {{ in_array($tag->id, $tags) ? 'checked' : '' }}
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">{{ $tag->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Search Results -->
        <div class="bg-white rounded-lg shadow-lg">
            <div class="px-8 py-6 border-b">
                <h2 class="text-lg font-semibold">
                    @if($query)
                        Search results for "{{ $query }}"
                    @else
                        All Documents
                    @endif
                    <span class="text-gray-500">({{ $documents->count() }} results)</span>
                </h2>
            </div>

            @if($documents->isEmpty())
                <div class="px-8 py-12 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No results found</h3>
                    <p class="text-gray-600">Try adjusting your search terms or filters.</p>
                </div>
            @else
                <div class="divide-y">
                    @foreach($documents as $document)
                    <a href="{{ route('portal.knowledge.show', $document->slug) }}" 
                       class="block px-8 py-6 hover:bg-gray-50">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ $document->title }}</h3>
                        
                        @if($document->category)
                        <p class="text-sm text-blue-600 mb-2">{{ $document->category->name }}</p>
                        @endif
                        
                        <p class="text-gray-600 line-clamp-2 mb-3">{{ $document->excerpt }}</p>
                        
                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <span>Updated {{ $document->updated_at->diffForHumans() }}</span>
                            @if($document->tags->isNotEmpty())
                            <span>•</span>
                            <div class="flex gap-2">
                                @foreach($document->tags->take(3) as $tag)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                    {{ $tag->name }}
                                </span>
                                @endforeach
                                @if($document->tags->count() > 3)
                                <span class="text-xs text-gray-500">+{{ $document->tags->count() - 3 }} more</span>
                                @endif
                            </div>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection