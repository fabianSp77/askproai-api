@extends('portal.layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Breadcrumbs -->
        <nav class="mb-8">
            <ol class="flex items-center space-x-2 text-sm">
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

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-3">
                <article class="bg-white rounded-lg shadow-lg overflow-hidden">
                    <div class="px-8 py-6 border-b">
                        <h1 class="text-3xl font-bold text-gray-900 mb-4">{{ $document->title }}</h1>
                        
                        <div class="flex items-center space-x-4 text-sm text-gray-500">
                            <span>Updated {{ $document->updated_at->format('F j, Y') }}</span>
                            <span>•</span>
                            <span>{{ $document->metadata['reading_time'] ?? 5 }} min read</span>
                            <span>•</span>
                            <span>{{ number_format($document->view_count) }} views</span>
                        </div>

                        <!-- Tags -->
                        @if($document->tags->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach($document->tags as $tag)
                            <a href="{{ route('portal.knowledge.tag', $tag->slug) }}" 
                               class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-gray-100 text-gray-700 hover:bg-gray-200">
                                {{ $tag->name }}
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>

                    <div class="px-8 py-6">
                        <!-- Article Content -->
                        <div class="prose prose-lg max-w-none">
                            {!! $document->content !!}
                        </div>

                        <!-- Feedback Section -->
                        <div class="mt-12 pt-8 border-t">
                            <div class="text-center">
                                <h3 class="text-lg font-medium mb-4">Was this article helpful?</h3>
                                <div class="flex justify-center space-x-4" id="feedback-buttons">
                                    <button onclick="submitFeedback(true)" 
                                            class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                                        </svg>
                                        Yes ({{ $feedbackStats['helpful'] }})
                                    </button>
                                    <button onclick="submitFeedback(false)" 
                                            class="inline-flex items-center px-6 py-3 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        <svg class="w-5 h-5 mr-2 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                                        </svg>
                                        No ({{ $feedbackStats['not_helpful'] }})
                                    </button>
                                </div>
                                <div id="feedback-message" class="mt-4 text-sm text-gray-600 hidden"></div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- Related Articles -->
                @if($document->relatedDocuments->isNotEmpty())
                <div class="mt-8 bg-white rounded-lg shadow-lg px-8 py-6">
                    <h2 class="text-xl font-semibold mb-4">Related Articles</h2>
                    <div class="space-y-3">
                        @foreach($document->relatedDocuments as $related)
                        <a href="{{ route('portal.knowledge.show', $related->slug) }}" 
                           class="block hover:bg-gray-50 -mx-2 px-2 py-2 rounded">
                            <h3 class="font-medium text-gray-900">{{ $related->title }}</h3>
                            <p class="text-sm text-gray-600 line-clamp-2">{{ $related->excerpt }}</p>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Table of Contents (if available) -->
                <div class="bg-white rounded-lg shadow-lg px-6 py-4 sticky top-4">
                    <h3 class="font-semibold mb-4">On this page</h3>
                    <nav id="table-of-contents" class="text-sm space-y-2">
                        <!-- TOC will be generated by JavaScript -->
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Generate table of contents
document.addEventListener('DOMContentLoaded', function() {
    const content = document.querySelector('.prose');
    const toc = document.getElementById('table-of-contents');
    const headings = content.querySelectorAll('h2, h3');
    
    if (headings.length > 0) {
        const tocList = document.createElement('ul');
        tocList.className = 'space-y-2';
        
        headings.forEach(function(heading) {
            const li = document.createElement('li');
            const a = document.createElement('a');
            
            // Create ID for heading if it doesn't have one
            if (!heading.id) {
                heading.id = heading.textContent.toLowerCase().replace(/\s+/g, '-').replace(/[^\w-]/g, '');
            }
            
            a.href = '#' + heading.id;
            a.textContent = heading.textContent;
            a.className = heading.tagName === 'H2' ? 'text-gray-700 hover:text-blue-600' : 'ml-4 text-gray-600 hover:text-blue-600';
            
            li.appendChild(a);
            tocList.appendChild(li);
        });
        
        toc.appendChild(tocList);
    } else {
        toc.innerHTML = '<p class="text-gray-500">No sections available</p>';
    }
});

// Feedback submission
function submitFeedback(helpful) {
    fetch('{{ route('portal.knowledge.feedback', $document->slug) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ helpful: helpful })
    })
    .then(response => response.json())
    .then(data => {
        const buttons = document.getElementById('feedback-buttons');
        const message = document.getElementById('feedback-message');
        
        buttons.style.display = 'none';
        message.textContent = data.message;
        message.classList.remove('hidden');
    })
    .catch(error => {
        console.error('Error:', error);
    });
}
</script>
@endpush
@endsection