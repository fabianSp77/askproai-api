<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AskProAI Error Catalog - Fehlerdatenbank</title>
    <meta name="description" content="Durchsuchen Sie unsere Fehlerdatenbank für schnelle Lösungen zu häufigen Problemen.">
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
                        <h1 class="text-2xl font-bold text-gray-900">AskProAI Error Catalog</h1>
                        <p class="text-sm text-gray-600 mt-1">Schnelle Lösungen für häufige Probleme</p>
                    </div>
                    <div class="text-sm text-gray-500">
                        <span class="font-medium">{{ $totalErrors }}</span> Fehler dokumentiert
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Search and Filters -->
            <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
                <form method="GET" action="{{ route('errors.index') }}" class="space-y-4">
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700">Suche</label>
                        <input type="text" 
                               name="search" 
                               id="search" 
                               value="{{ request('search') }}"
                               placeholder="Fehlercode, Titel oder Beschreibung..."
                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">Kategorie</label>
                            <select name="category" id="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Alle Kategorien</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>
                                        {{ $cat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="severity" class="block text-sm font-medium text-gray-700">Schweregrad</label>
                            <select name="severity" id="severity" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Alle Schweregrade</option>
                                <option value="critical" {{ request('severity') == 'critical' ? 'selected' : '' }}>Kritisch</option>
                                <option value="high" {{ request('severity') == 'high' ? 'selected' : '' }}>Hoch</option>
                                <option value="medium" {{ request('severity') == 'medium' ? 'selected' : '' }}>Mittel</option>
                                <option value="low" {{ request('severity') == 'low' ? 'selected' : '' }}>Niedrig</option>
                            </select>
                        </div>

                        <div>
                            <label for="tag" class="block text-sm font-medium text-gray-700">Tag</label>
                            <select name="tag" id="tag" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="">Alle Tags</option>
                                @foreach($allTags as $tag)
                                    <option value="{{ $tag->id }}" {{ request('tag') == $tag->id ? 'selected' : '' }}>
                                        {{ $tag->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center space-x-4">
                        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Suchen
                        </button>
                        <a href="{{ route('errors.index') }}" class="text-sm text-gray-500 hover:text-gray-700">
                            Filter zurücksetzen
                        </a>
                    </div>
                </form>
            </div>

            <!-- Error List -->
            <div class="space-y-4">
                @forelse($errors as $error)
                    <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow" x-data="{ expanded: false }">
                        <div class="p-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-3">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded text-xs font-medium
                                            @if($error->severity == 'critical') bg-red-100 text-red-800
                                            @elseif($error->severity == 'high') bg-orange-100 text-orange-800
                                            @elseif($error->severity == 'medium') bg-yellow-100 text-yellow-800
                                            @else bg-green-100 text-green-800
                                            @endif">
                                            {{ ucfirst($error->severity) }}
                                        </span>
                                        <code class="text-sm font-mono text-gray-600">{{ $error->error_code }}</code>
                                        @foreach($error->tags as $tag)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium" 
                                                  style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                                {{ $tag->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                    <h3 class="mt-2 text-lg font-semibold text-gray-900">{{ $error->title }}</h3>
                                    <p class="mt-1 text-sm text-gray-600">{{ $error->description }}</p>
                                    
                                    @if($error->symptoms)
                                        <div class="mt-3">
                                            <span class="text-sm font-medium text-gray-700">Symptome:</span>
                                            <p class="text-sm text-gray-600">{{ $error->symptoms }}</p>
                                        </div>
                                    @endif
                                </div>
                                <button @click="expanded = !expanded" 
                                        class="ml-4 text-gray-400 hover:text-gray-600">
                                    <svg class="h-6 w-6 transform transition-transform" 
                                         :class="expanded ? 'rotate-180' : ''"
                                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Expanded Content -->
                            <div x-show="expanded" 
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="mt-6 border-t pt-6">
                                
                                @if($error->root_causes)
                                    <div class="mb-6">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Mögliche Ursachen:</h4>
                                        <ul class="space-y-1">
                                            @foreach($error->root_causes as $cause => $description)
                                                <li class="text-sm text-gray-600">
                                                    <span class="font-medium">{{ $cause }}:</span> {{ $description }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                @if($error->solutions->count() > 0)
                                    <div class="mb-6">
                                        <h4 class="text-sm font-medium text-gray-700 mb-3">Lösungen:</h4>
                                        <div class="space-y-4">
                                            @foreach($error->solutions as $solution)
                                                <div class="bg-gray-50 rounded-lg p-4">
                                                    <div class="flex items-start justify-between">
                                                        <div class="flex-1">
                                                            <h5 class="font-medium text-gray-900">{{ $solution->title }}</h5>
                                                            <p class="text-sm text-gray-600 mt-1">{{ $solution->description }}</p>
                                                            
                                                            @if($solution->steps)
                                                                <div class="mt-3">
                                                                    <span class="text-sm font-medium text-gray-700">Schritte:</span>
                                                                    <ol class="mt-1 list-decimal list-inside space-y-1">
                                                                        @foreach($solution->steps as $step)
                                                                            <li class="text-sm text-gray-600">{{ $step }}</li>
                                                                        @endforeach
                                                                    </ol>
                                                                </div>
                                                            @endif
                                                            
                                                            @if($solution->code_snippet)
                                                                <div class="mt-3">
                                                                    <span class="text-sm font-medium text-gray-700">Code:</span>
                                                                    <pre class="mt-1 bg-gray-900 text-gray-100 p-3 rounded text-xs overflow-x-auto"><code>{{ $solution->code_snippet }}</code></pre>
                                                                </div>
                                                            @endif
                                                        </div>
                                                        @if($solution->is_automated)
                                                            <span class="ml-3 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                                Automatisiert
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                @if($error->preventionTips->count() > 0)
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Präventionstipps:</h4>
                                        <ul class="space-y-1">
                                            @foreach($error->preventionTips as $tip)
                                                <li class="text-sm text-gray-600">
                                                    • {{ $tip->tip }}
                                                    <span class="text-xs text-gray-500">({{ $tip->category }})</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="mt-6 flex items-center text-xs text-gray-500">
                                    @if($error->occurrence_count > 0)
                                        <span>{{ $error->occurrence_count }} Vorkommen</span>
                                    @endif
                                    @if($error->last_occurred_at)
                                        <span class="mx-2">•</span>
                                        <span>Zuletzt: {{ $error->last_occurred_at->diffForHumans() }}</span>
                                    @endif
                                    @if($error->avg_resolution_time)
                                        <span class="mx-2">•</span>
                                        <span>Ø Lösungszeit: {{ number_format($error->avg_resolution_time, 0) }} Min</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Fehler gefunden</h3>
                        <p class="mt-1 text-sm text-gray-500">Versuchen Sie andere Suchkriterien.</p>
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($errors->hasPages())
                <div class="mt-6">
                    {{ $errors->withQueryString()->links() }}
                </div>
            @endif
        </main>

        <!-- Footer -->
        <footer class="bg-gray-100 mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <p class="text-center text-sm text-gray-500">
                    AskProAI Error Catalog • 
                    <a href="/api/errors" class="hover:text-gray-700">API</a> • 
                    <a href="/admin" class="hover:text-gray-700">Admin Panel</a>
                </p>
            </div>
        </footer>
    </div>
</body>
</html>