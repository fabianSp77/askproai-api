@extends('portal.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg px-8 py-6 mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">
                        Articles tagged with "{{ $tag->name }}"
                    </h1>
                    <p class="text-gray-600">
                        {{ $documents->total() }} {{ Str::plural('article', $documents->total()) }} found
                    </p>
                </div>
                <div class="flex items-center">
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-lg font-medium"
                          style="{{ $tag->color_style }}">
                        {{ $tag->name }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Documents List -->
        <div class="bg-white rounded-lg shadow">
            @if($documents->isEmpty())
                <div class="px-8 py-12 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                    </svg>
                    <p class="text-gray-600">No articles found with this tag.</p>
                </div>
            @else
                <div class="divide-y">
                    @foreach($documents as $document)
                    <a href="{{ route('portal.knowledge.show', $document->slug) }}" 
                       class="block px-8 py-6 hover:bg-gray-50">
                        <h2 class="text-lg font-medium text-gray-900 mb-2">{{ $document->title }}</h2>
                        
                        @if($document->category)
                        <p class="text-sm text-blue-600 mb-2">{{ $document->category->name }}</p>
                        @endif
                        
                        <p class="text-gray-600 line-clamp-2 mb-3">{{ $document->excerpt }}</p>
                        
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4 text-sm text-gray-500">
                                <span>Updated {{ $document->updated_at->diffForHumans() }}</span>
                                <span>•</span>
                                <span>{{ $document->view_count }} views</span>
                            </div>
                            
                            @if($document->tags->count() > 1)
                            <div class="flex gap-2">
                                @foreach($document->tags->where('id', '!=', $tag->id)->take(3) as $otherTag)
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-700">
                                    {{ $otherTag->name }}
                                </span>
                                @endforeach
                                @if($document->tags->count() > 4)
                                <span class="text-xs text-gray-500">+{{ $document->tags->count() - 4 }} more</span>
                                @endif
                            </div>
                            @endif
                        </div>
                    </a>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="px-8 py-4 border-t">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>

        <!-- Back to Knowledge Base -->
        <div class="mt-8 text-center">
            <a href="{{ route('portal.knowledge.index') }}" class="text-blue-600 hover:text-blue-800">
                ← Back to Knowledge Base
            </a>
        </div>
    </div>
</div>
@endsection