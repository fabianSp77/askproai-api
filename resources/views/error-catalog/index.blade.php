@extends('layouts.app')

@section('title', 'Error Catalog - AskProAI Help Center')

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-3xl font-bold text-gray-900">Error Catalog</h1>
                    <p class="mt-2 text-gray-600">Find solutions to common errors and issues</p>
                </div>
                <div class="mt-4 flex md:mt-0 md:ml-4">
                    <span class="text-sm text-gray-500">
                        {{ $errors->total() }} errors documented
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:grid lg:grid-cols-4 lg:gap-8">
            <!-- Filters Sidebar -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Filters</h3>
                    
                    <form method="GET" action="{{ route('errors.index') }}" id="filterForm">
                        <!-- Search -->
                        <div class="mb-6">
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                            <input type="text" 
                                   name="search" 
                                   id="search"
                                   value="{{ $search }}"
                                   placeholder="Error code or description..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <!-- Category Filter -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Categories</option>
                                @foreach($categories as $key => $label)
                                    <option value="{{ $key }}" {{ $category == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Service Filter -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Service</label>
                            <select name="service" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Services</option>
                                @foreach($services as $key => $label)
                                    <option value="{{ $key }}" {{ $service == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Severity Filter -->
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Severity</label>
                            <select name="severity" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="">All Severities</option>
                                @foreach($severities as $key => $label)
                                    <option value="{{ $key }}" {{ $severity == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Popular Tags -->
                        @if($popularTags->count() > 0)
                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Popular Tags</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($popularTags as $popularTag)
                                    <a href="{{ route('errors.index', ['tag' => $popularTag->slug]) }}"
                                       class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $tag == $popularTag->slug ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800 hover:bg-gray-200' }}">
                                        {{ $popularTag->name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            Apply Filters
                        </button>
                        
                        @if($search || $category || $service || $severity || $tag)
                            <a href="{{ route('errors.index') }}" class="block text-center mt-2 text-sm text-gray-600 hover:text-gray-900">
                                Clear Filters
                            </a>
                        @endif
                    </form>
                </div>
            </div>

            <!-- Results -->
            <div class="mt-8 lg:mt-0 lg:col-span-3">
                <!-- Sort Options -->
                <div class="bg-white rounded-lg shadow px-6 py-4 mb-6">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            Showing {{ $errors->firstItem() ?? 0 }} - {{ $errors->lastItem() ?? 0 }} of {{ $errors->total() }} results
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-600">Sort by:</label>
                            <select id="sortSelect" class="text-sm px-3 py-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                <option value="occurrence_count" {{ $sortBy == 'occurrence_count' ? 'selected' : '' }}>
                                    Most Common
                                </option>
                                <option value="last_occurred_at" {{ $sortBy == 'last_occurred_at' ? 'selected' : '' }}>
                                    Most Recent
                                </option>
                                <option value="avg_resolution_time" {{ $sortBy == 'avg_resolution_time' ? 'selected' : '' }}>
                                    Resolution Time
                                </option>
                                <option value="severity" {{ $sortBy == 'severity' ? 'selected' : '' }}>
                                    Severity
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Error List -->
                <div class="space-y-4">
                    @forelse($errors as $error)
                        <div class="bg-white rounded-lg shadow hover:shadow-md transition-shadow">
                            <a href="{{ route('errors.show', $error->error_code) }}" class="block p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ $error->error_code }}
                                            </span>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium
                                                {{ $error->severity == 'critical' ? 'bg-red-100 text-red-800' : '' }}
                                                {{ $error->severity == 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                                                {{ $error->severity == 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                                {{ $error->severity == 'low' ? 'bg-green-100 text-green-800' : '' }}">
                                                {{ ucfirst($error->severity) }}
                                            </span>
                                            @if($error->service)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $services[$error->service] ?? $error->service }}
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <h3 class="text-lg font-medium text-gray-900 mb-1">
                                            {{ $error->title }}
                                        </h3>
                                        
                                        <p class="text-gray-600 text-sm line-clamp-2">
                                            {{ strip_tags($error->description) }}
                                        </p>
                                        
                                        <div class="mt-3 flex items-center gap-4 text-xs text-gray-500">
                                            <span>{{ $error->occurrence_count }} occurrences</span>
                                            @if($error->avg_resolution_time)
                                                <span>Avg resolution: {{ number_format($error->avg_resolution_time, 0) }} min</span>
                                            @endif
                                            @if($error->last_occurred_at)
                                                <span>Last seen: {{ $error->last_occurred_at->diffForHumans() }}</span>
                                            @endif
                                            <span>{{ $error->solutions_count }} solutions</span>
                                        </div>
                                    </div>
                                    
                                    <div class="ml-4">
                                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </div>
                                </div>
                            </a>
                        </div>
                    @empty
                        <div class="bg-white rounded-lg shadow p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No errors found</h3>
                            <p class="mt-1 text-sm text-gray-500">Try adjusting your search or filter criteria.</p>
                        </div>
                    @endforelse
                </div>

                <!-- Pagination -->
                @if($errors->hasPages())
                    <div class="mt-8">
                        {{ $errors->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-submit form on filter change
    const filterForm = document.getElementById('filterForm');
    const selects = filterForm.querySelectorAll('select:not(#sortSelect)');
    
    selects.forEach(select => {
        select.addEventListener('change', () => {
            filterForm.submit();
        });
    });
    
    // Handle sort change
    const sortSelect = document.getElementById('sortSelect');
    sortSelect.addEventListener('change', (e) => {
        const url = new URL(window.location);
        url.searchParams.set('sort', e.target.value);
        window.location = url;
    });
    
    // Search autocomplete (optional)
    const searchInput = document.getElementById('search');
    let searchTimeout;
    
    searchInput.addEventListener('input', (e) => {
        clearTimeout(searchTimeout);
        const query = e.target.value;
        
        if (query.length >= 2) {
            searchTimeout = setTimeout(() => {
                // Implement autocomplete if needed
                console.log('Search for:', query);
            }, 300);
        }
    });
});
</script>
@endpush
@endsection