@extends('portal.layouts.app')

@section('title', 'Wissensdatenbank')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Hero Section -->
    <div class="bg-gradient-to-r from-blue-600 to-indigo-700 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <h1 class="text-4xl font-bold mb-4">Knowledge Base</h1>
            <p class="text-xl opacity-90 mb-8">Find answers, guides, and documentation for AskProAI</p>
            
            <!-- Search Bar -->
            <form action="{{ route('portal.knowledge.search') }}" method="GET" class="max-w-2xl">
                <div class="relative">
                    <input type="text" 
                           name="q" 
                           placeholder="Search documentation..." 
                           class="w-full px-6 py-4 rounded-lg text-gray-900 shadow-lg focus:outline-none focus:ring-2 focus:ring-blue-400"
                           value="{{ request('q') }}">
                    <button type="submit" class="absolute right-2 top-2 p-2 text-gray-600 hover:text-gray-800">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Categories -->
                <div class="mb-12">
                    <h2 class="text-2xl font-semibold mb-6">Browse by Category</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($categories as $category)
                        <a href="{{ route('portal.knowledge.category', $category->slug) }}" 
                           class="block p-6 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
                            <div class="flex items-start">
                                @if($category->icon)
                                <div class="flex-shrink-0 text-3xl mr-4">{{ $category->icon }}</div>
                                @endif
                                <div class="flex-1">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ $category->name }}</h3>
                                    @if($category->description)
                                    <p class="text-gray-600 text-sm mb-2">{{ $category->description }}</p>
                                    @endif
                                    <p class="text-sm text-gray-500">
                                        {{ $category->documents_count }} {{ Str::plural('article', $category->documents_count) }}
                                    </p>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>

                <!-- Recent Documents -->
                <div class="mb-12">
                    <h2 class="text-2xl font-semibold mb-6">Recently Updated</h2>
                    <div class="bg-white rounded-lg shadow">
                        @foreach($recentDocuments as $document)
                        <a href="{{ route('portal.knowledge.show', $document->slug) }}" 
                           class="block px-6 py-4 hover:bg-gray-50 {{ !$loop->last ? 'border-b' : '' }}">
                            <h3 class="font-medium text-gray-900 mb-1">{{ $document->title }}</h3>
                            <p class="text-sm text-gray-600 line-clamp-2">{{ $document->excerpt }}</p>
                            <p class="text-xs text-gray-500 mt-2">
                                Updated {{ $document->updated_at->diffForHumans() }}
                            </p>
                        </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Popular Articles -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Popular Articles</h3>
                    <div class="bg-white rounded-lg shadow">
                        @foreach($popularDocuments as $document)
                        <a href="{{ route('portal.knowledge.show', $document->slug) }}" 
                           class="block px-4 py-3 hover:bg-gray-50 {{ !$loop->last ? 'border-b' : '' }}">
                            <h4 class="font-medium text-gray-900 text-sm mb-1">{{ $document->title }}</h4>
                            <p class="text-xs text-gray-500">
                                {{ number_format($document->view_count) }} views
                            </p>
                        </a>
                        @endforeach
                    </div>
                </div>

                <!-- Popular Tags -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Popular Tags</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($popularTags as $tag)
                        <a href="{{ route('portal.knowledge.tag', $tag->slug) }}" 
                           class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-700 hover:bg-gray-200">
                            {{ $tag->name }}
                            <span class="ml-1 text-xs text-gray-500">({{ $tag->documents_count }})</span>
                        </a>
                        @endforeach
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="bg-blue-50 rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Need Help?</h3>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <a href="#" class="text-blue-600 hover:text-blue-800">Contact Support</a>
                        </li>
                        <li>
                            <a href="#" class="text-blue-600 hover:text-blue-800">Video Tutorials</a>
                        </li>
                        <li>
                            <a href="#" class="text-blue-600 hover:text-blue-800">API Documentation</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection