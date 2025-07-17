<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $error->error_code }}: {{ $error->title }} - AskProAI Error Catalog</title>
    <meta name="description" content="{{ $error->description }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <a href="{{ route('errors.index') }}" class="text-indigo-600 hover:text-indigo-900 text-sm mb-2 inline-block">
                            ← Zurück zur Übersicht
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $error->error_code }}: {{ $error->title }}</h1>
                    </div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                        @if($error->severity == 'critical') bg-red-100 text-red-800
                        @elseif($error->severity == 'high') bg-orange-100 text-orange-800
                        @elseif($error->severity == 'medium') bg-yellow-100 text-yellow-800
                        @else bg-green-100 text-green-800
                        @endif">
                        {{ ucfirst($error->severity) }}
                    </span>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Error Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Description -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-3">Beschreibung</h2>
                        <p class="text-gray-700">{{ $error->description }}</p>
                        
                        @if($error->symptoms)
                            <div class="mt-4">
                                <h3 class="text-sm font-medium text-gray-700">Symptome</h3>
                                <p class="mt-1 text-gray-600">{{ $error->symptoms }}</p>
                            </div>
                        @endif
                        
                        @if($error->stack_pattern)
                            <div class="mt-4">
                                <h3 class="text-sm font-medium text-gray-700">Stack Pattern</h3>
                                <code class="mt-1 block bg-gray-100 px-3 py-2 rounded text-sm text-gray-800">{{ $error->stack_pattern }}</code>
                            </div>
                        @endif
                    </div>

                    <!-- Root Causes -->
                    @if($error->root_causes)
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">Mögliche Ursachen</h2>
                            <dl class="space-y-3">
                                @foreach($error->root_causes as $cause => $description)
                                    <div>
                                        <dt class="font-medium text-gray-900">{{ $cause }}</dt>
                                        <dd class="mt-1 text-gray-600">{{ $description }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif

                    <!-- Solutions -->
                    @if($error->solutions->count() > 0)
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Lösungen</h2>
                            <div class="space-y-6">
                                @foreach($error->solutions as $solution)
                                    <div class="border-l-4 border-indigo-500 pl-4" x-data="{ showFeedback: false }">
                                        <div class="flex items-start justify-between">
                                            <h3 class="font-medium text-gray-900">{{ $solution->title }}</h3>
                                            @if($solution->is_automated)
                                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Automatisiert
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <p class="mt-2 text-gray-600">{{ $solution->description }}</p>
                                        
                                        @if($solution->steps)
                                            <div class="mt-3">
                                                <h4 class="text-sm font-medium text-gray-700">Schritte:</h4>
                                                <ol class="mt-2 list-decimal list-inside space-y-1">
                                                    @foreach($solution->steps as $step)
                                                        <li class="text-gray-600">{{ $step }}</li>
                                                    @endforeach
                                                </ol>
                                            </div>
                                        @endif
                                        
                                        @if($solution->code_snippet)
                                            <div class="mt-3">
                                                <h4 class="text-sm font-medium text-gray-700 mb-1">Code:</h4>
                                                <pre class="bg-gray-900 text-gray-100 p-4 rounded-lg overflow-x-auto"><code>{{ $solution->code_snippet }}</code></pre>
                                            </div>
                                        @endif
                                        
                                        @if($solution->automation_script)
                                            <div class="mt-3">
                                                <p class="text-sm text-gray-600">
                                                    <span class="font-medium">Automatisches Fix-Script:</span>
                                                    <code class="bg-gray-100 px-2 py-1 rounded">{{ $solution->automation_script }}</code>
                                                </p>
                                            </div>
                                        @endif
                                        
                                        <!-- Feedback Buttons -->
                                        <div class="mt-4">
                                            <p class="text-sm text-gray-600">War diese Lösung hilfreich?</p>
                                            <div class="mt-2 flex items-center space-x-4">
                                                <button @click="showFeedback = true" 
                                                        onclick="submitFeedback({{ $solution->id }}, true)"
                                                        class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                    👍 Ja
                                                </button>
                                                <button @click="showFeedback = true"
                                                        onclick="submitFeedback({{ $solution->id }}, false)"
                                                        class="inline-flex items-center px-3 py-1 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                    👎 Nein
                                                </button>
                                            </div>
                                            <div x-show="showFeedback" x-transition class="mt-2 text-sm text-green-600">
                                                Danke für Ihr Feedback!
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Prevention Tips -->
                    @if($error->preventionTips->count() > 0)
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-3">Präventionstipps</h2>
                            <ul class="space-y-2">
                                @foreach($error->preventionTips as $tip)
                                    <li class="flex items-start">
                                        <span class="text-indigo-500 mr-2">•</span>
                                        <div>
                                            <span class="text-gray-700">{{ $tip->tip }}</span>
                                            <span class="text-xs text-gray-500 ml-2">({{ $tip->category }})</span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Error Info -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="font-semibold text-gray-900 mb-4">Details</h3>
                        <dl class="space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Kategorie</dt>
                                <dd class="font-medium">{{ $error->category }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Service</dt>
                                <dd class="font-medium">{{ $error->service }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Vorkommen</dt>
                                <dd class="font-medium">{{ $error->occurrence_count ?? 0 }}x</dd>
                            </div>
                            @if($error->last_occurred_at)
                                <div>
                                    <dt class="text-gray-500">Zuletzt aufgetreten</dt>
                                    <dd class="font-medium">{{ $error->last_occurred_at->diffForHumans() }}</dd>
                                </div>
                            @endif
                            @if($error->avg_resolution_time)
                                <div>
                                    <dt class="text-gray-500">Ø Lösungszeit</dt>
                                    <dd class="font-medium">{{ number_format($error->avg_resolution_time, 0) }} Min</dd>
                                </div>
                            @endif
                        </dl>
                    </div>

                    <!-- Tags -->
                    @if($error->tags->count() > 0)
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="font-semibold text-gray-900 mb-3">Tags</h3>
                            <div class="flex flex-wrap gap-2">
                                @foreach($error->tags as $tag)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                          style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                        {{ $tag->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Related Errors -->
                    @if($similarErrors->count() > 0)
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="font-semibold text-gray-900 mb-3">Ähnliche Fehler</h3>
                            <ul class="space-y-2">
                                @foreach($similarErrors as $similar)
                                    <li>
                                        <a href="{{ route('errors.show', $similar->error_code) }}" 
                                           class="text-indigo-600 hover:text-indigo-900 text-sm">
                                            {{ $similar->error_code }}: {{ $similar->title }}
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </div>

    <script>
        function submitFeedback(solutionId, wasHelpful) {
            fetch(`/errors/feedback/${solutionId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    was_helpful: wasHelpful
                })
            })
            .then(response => response.json())
            .then(data => {
                // Feedback submitted
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>