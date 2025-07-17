@extends('layouts.app')

@section('title', $error->error_code . ' - ' . $error->title)

@section('content')
<div class="min-h-screen bg-gray-50">
    <!-- Breadcrumb -->
    <div class="bg-white border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <nav class="flex" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li>
                        <a href="{{ route('errors.index') }}" class="text-gray-500 hover:text-gray-700">
                            Error Catalog
                        </a>
                    </li>
                    <li>
                        <svg class="flex-shrink-0 h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                        </svg>
                    </li>
                    <li class="text-gray-900 font-medium">{{ $error->error_code }}</li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:grid lg:grid-cols-3 lg:gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Error Header -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-3 mb-3">
                                <span class="inline-flex items-center px-3 py-1 rounded text-sm font-medium bg-gray-100 text-gray-800">
                                    {{ $error->error_code }}
                                </span>
                                <span class="inline-flex items-center px-3 py-1 rounded text-sm font-medium
                                    {{ $error->severity == 'critical' ? 'bg-red-100 text-red-800' : '' }}
                                    {{ $error->severity == 'high' ? 'bg-orange-100 text-orange-800' : '' }}
                                    {{ $error->severity == 'medium' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $error->severity == 'low' ? 'bg-green-100 text-green-800' : '' }}">
                                    {{ ucfirst($error->severity) }} Priority
                                </span>
                                @if($error->service)
                                    <span class="inline-flex items-center px-3 py-1 rounded text-sm font-medium bg-blue-100 text-blue-800">
                                        {{ $error->service }}
                                    </span>
                                @endif
                            </div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ $error->title }}</h1>
                        </div>
                    </div>
                    
                    <div class="prose max-w-none">
                        {!! $error->description !!}
                    </div>
                    
                    @if($error->symptoms)
                        <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <h3 class="text-sm font-medium text-yellow-800 mb-2">Symptoms</h3>
                            <p class="text-sm text-yellow-700">{{ $error->symptoms }}</p>
                        </div>
                    @endif
                    
                    <!-- Tags -->
                    @if($error->tags->count() > 0)
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach($error->tags as $tag)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                      style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                    {{ $tag->name }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Root Causes -->
                @if(is_array($error->root_causes) && count($error->root_causes) > 0)
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Root Causes</h2>
                        <ul class="space-y-3">
                            @foreach($error->root_causes as $cause => $description)
                                <li class="flex items-start">
                                    <svg class="flex-shrink-0 h-5 w-5 text-red-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-900">{{ $cause }}</p>
                                        <p class="text-sm text-gray-600">{{ $description }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Solutions -->
                @if($error->solutions->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Solutions</h2>
                        <div class="space-y-6">
                            @foreach($error->solutions as $solution)
                                <div class="border rounded-lg p-4" id="solution-{{ $solution->id }}">
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center gap-3">
                                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-800 text-sm font-medium">
                                                {{ $solution->order }}
                                            </span>
                                            <h3 class="text-md font-medium text-gray-900">{{ $solution->title }}</h3>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ ucfirst($solution->type) }}
                                            </span>
                                            @if($solution->is_automated)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                    Automated
                                                </span>
                                            @endif
                                        </div>
                                        @if($solution->success_rate !== null)
                                            <div class="text-sm text-gray-500">
                                                Success rate: 
                                                <span class="font-medium {{ $solution->success_rate >= 80 ? 'text-green-600' : ($solution->success_rate >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                                    {{ number_format($solution->success_rate, 1) }}%
                                                </span>
                                            </div>
                                        @endif
                                    </div>
                                    
                                    <div class="prose prose-sm max-w-none mb-4">
                                        {!! $solution->description !!}
                                    </div>
                                    
                                    @if(is_array($solution->steps) && count($solution->steps) > 0)
                                        <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                            <h4 class="text-sm font-medium text-gray-900 mb-3">Steps to resolve:</h4>
                                            <ol class="space-y-2">
                                                @foreach($solution->getFormattedSteps() as $step)
                                                    <li class="flex items-start">
                                                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 text-blue-800 text-xs font-medium flex items-center justify-center mr-3">
                                                            {{ $step['number'] }}
                                                        </span>
                                                        <span class="text-sm text-gray-700">{{ $step['text'] }}</span>
                                                    </li>
                                                @endforeach
                                            </ol>
                                        </div>
                                    @endif
                                    
                                    @if($solution->code_snippet)
                                        <div class="mb-4">
                                            <h4 class="text-sm font-medium text-gray-900 mb-2">Code/Command:</h4>
                                            <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto text-sm"><code>{{ $solution->code_snippet }}</code></pre>
                                        </div>
                                    @endif
                                    
                                    <!-- Solution Feedback -->
                                    <div class="flex items-center gap-4 pt-4 border-t">
                                        <span class="text-sm text-gray-600">Was this solution helpful?</span>
                                        <button onclick="submitFeedback({{ $solution->id }}, true)" 
                                                class="inline-flex items-center px-3 py-1 border border-gray-300 rounded text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                                            </svg>
                                            Yes
                                        </button>
                                        <button onclick="submitFeedback({{ $solution->id }}, false)" 
                                                class="inline-flex items-center px-3 py-1 border border-gray-300 rounded text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            <svg class="w-4 h-4 mr-1 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"></path>
                                            </svg>
                                            No
                                        </button>
                                        <div id="feedback-response-{{ $solution->id }}" class="text-sm"></div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Prevention Tips -->
                @if($error->preventionTips->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-4">Prevention Tips</h2>
                        <div class="space-y-3">
                            @foreach($error->preventionTips as $tip)
                                <div class="flex items-start">
                                    <svg class="flex-shrink-0 h-5 w-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                    <div class="ml-3">
                                        <p class="text-sm text-gray-700">{{ $tip->tip }}</p>
                                        <span class="text-xs text-gray-500">{{ ucfirst($tip->category) }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="mt-8 lg:mt-0">
                <!-- Error Stats -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Statistics</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-gray-600">Occurrences</dt>
                            <dd class="text-2xl font-semibold text-gray-900">{{ number_format($error->occurrence_count) }}</dd>
                        </div>
                        @if($error->last_occurred_at)
                            <div>
                                <dt class="text-sm text-gray-600">Last Occurred</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $error->last_occurred_at->format('M j, Y g:i A') }}</dd>
                                <dd class="text-xs text-gray-500">{{ $error->last_occurred_at->diffForHumans() }}</dd>
                            </div>
                        @endif
                        @if($error->avg_resolution_time)
                            <div>
                                <dt class="text-sm text-gray-600">Avg Resolution Time</dt>
                                <dd class="text-lg font-medium text-gray-900">{{ number_format($error->avg_resolution_time, 0) }} minutes</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                <!-- Related Errors -->
                @if($relatedErrors->count() > 0 || $similarErrors->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Related Errors</h3>
                        <ul class="space-y-3">
                            @foreach($relatedErrors->merge($similarErrors)->unique('id')->take(5) as $related)
                                <li>
                                    <a href="{{ route('errors.show', $related->error_code) }}" 
                                       class="block hover:bg-gray-50 -mx-2 px-2 py-2 rounded">
                                        <div class="text-sm font-medium text-gray-900">{{ $related->error_code }}</div>
                                        <div class="text-sm text-gray-600">{{ $related->title }}</div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function submitFeedback(solutionId, wasHelpful) {
    fetch(`/errors/feedback/${solutionId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            was_helpful: wasHelpful
        })
    })
    .then(response => response.json())
    .then(data => {
        const responseDiv = document.getElementById(`feedback-response-${solutionId}`);
        responseDiv.innerHTML = `<span class="text-green-600">${data.message}</span>`;
        setTimeout(() => {
            responseDiv.innerHTML = '';
        }, 3000);
    })
    .catch(error => {
        console.error('Error submitting feedback:', error);
    });
}
</script>
@endpush
@endsection