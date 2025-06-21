@extends('portal.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumbs -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm">
                <li class="flex items-center">
                    <a href="{{ route('portal.knowledge.index') }}" class="text-gray-600 hover:text-gray-900">
                        Knowledge Base
                    </a>
                    <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                </li>
                @foreach($breadcrumbs as $breadcrumb)
                    @if($breadcrumb['url'])
                        <li class="flex items-center">
                            <a href="{{ $breadcrumb['url'] }}" class="text-gray-600 hover:text-gray-900">
                                {{ $breadcrumb['name'] }}
                            </a>
                            <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        </li>
                    @else
                        <li class="text-gray-900 font-medium">{{ $breadcrumb['name'] }}</li>
                    @endif
                @endforeach
            </ol>
        </nav>

        <!-- Category Header -->
        <div class="bg-white rounded-lg shadow-lg px-8 py-6 mb-8">
            <div class="flex items-center">
                @if($category->icon)
                <div class="text-4xl mr-4">{{ $category->icon }}</div>
                @endif
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ $category->name }}</h1>
                    @if($category->description)
                    <p class="text-lg text-gray-600">{{ $category->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Subcategories -->
                @if($category->children->isNotEmpty())
                <div class="mb-8">
                    <h2 class="text-xl font-semibold mb-4">Subcategories</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($category->children as $child)
                        <a href="{{ route('portal.knowledge.category', $child->slug) }}" 
                           class="block p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
                            <div class="flex items-start">
                                @if($child->icon)
                                <div class="text-2xl mr-3">{{ $child->icon }}</div>
                                @endif
                                <div class="flex-1">
                                    <h3 class="font-semibold text-gray-900 mb-1">{{ $child->name }}</h3>
                                    @if($child->description)
                                    <p class="text-sm text-gray-600 mb-1">{{ $child->description }}</p>
                                    @endif
                                    <p class="text-xs text-gray-500">
                                        {{ $child->documents_count }} {{ Str::plural('article', $child->documents_count) }}
                                    </p>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Documents -->
                <div>
                    <h2 class="text-xl font-semibold mb-4">Articles in {{ $category->name }}</h2>
                    @if($documents->isEmpty())
                        <div class="bg-white rounded-lg shadow px-8 py-12 text-center">
                            <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-gray-600">No articles in this category yet.</p>
                        </div>
                    @else
                        <div class="bg-white rounded-lg shadow divide-y">
                            @foreach($documents as $document)
                            <a href="{{ route('portal.knowledge.show', $document->slug) }}" 
                               class="block px-6 py-4 hover:bg-gray-50">
                                <h3 class="font-medium text-gray-900 mb-1">{{ $document->title }}</h3>
                                <p class="text-sm text-gray-600 line-clamp-2 mb-2">{{ $document->excerpt }}</p>
                                <div class="flex items-center space-x-4 text-xs text-gray-500">
                                    <span>Updated {{ $document->updated_at->diffForHumans() }}</span>
                                    @if($document->tags->isNotEmpty())
                                    <span>•</span>
                                    <div class="flex gap-2">
                                        @foreach($document->tags->take(3) as $tag)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full bg-gray-100 text-gray-700">
                                            {{ $tag->name }}
                                        </span>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                            </a>
                            @endforeach
                        </div>

                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $documents->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Parent Category -->
                @if($category->parent)
                <div class="mb-6">
                    <h3 class="font-semibold mb-3">Parent Category</h3>
                    <a href="{{ route('portal.knowledge.category', $category->parent->slug) }}" 
                       class="block p-4 bg-white rounded-lg shadow hover:shadow-md">
                        <div class="flex items-center">
                            @if($category->parent->icon)
                            <div class="text-2xl mr-3">{{ $category->parent->icon }}</div>
                            @endif
                            <div>
                                <h4 class="font-medium text-gray-900">{{ $category->parent->name }}</h4>
                                <p class="text-sm text-gray-600">← Back to parent</p>
                            </div>
                        </div>
                    </a>
                </div>
                @endif

                <!-- Category Stats -->
                <div class="bg-white rounded-lg shadow p-6 mb-6">
                    <h3 class="font-semibold mb-4">Category Statistics</h3>
                    <dl class="space-y-2">
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Articles:</dt>
                            <dd class="font-medium">{{ $documents->total() }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-600">Subcategories:</dt>
                            <dd class="font-medium">{{ $category->children->count() }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Quick Links -->
                <div class="bg-blue-50 rounded-lg p-6">
                    <h3 class="font-semibold mb-4">Quick Actions</h3>
                    <ul class="space-y-2 text-sm">
                        <li>
                            <a href="{{ route('portal.knowledge.search', ['category' => $category->id]) }}" class="text-blue-600 hover:text-blue-800">
                                Search in this category
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('portal.knowledge.index') }}" class="text-blue-600 hover:text-blue-800">
                                Back to all categories
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection