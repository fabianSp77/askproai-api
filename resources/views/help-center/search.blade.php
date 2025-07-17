@extends('portal.layouts.app')

@section('title', 'Suche - Hilfe-Center')

@section('styles')
<style>
    .search-hero {
        background: linear-gradient(135deg, #3B82F6 0%, #1E40AF 100%);
        color: white;
        padding: 3rem 0;
    }
    
    .search-results-container {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .search-result-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        padding: 1.5rem;
        margin-bottom: 1rem;
        border: 1px solid #E5E7EB;
        transition: all 0.3s ease;
    }
    
    .search-result-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        border-color: #3B82F6;
    }
    
    .search-result-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: #1F2937;
        margin-bottom: 0.5rem;
        display: block;
        text-decoration: none;
    }
    
    .search-result-title:hover {
        color: #3B82F6;
    }
    
    .search-result-category {
        display: inline-flex;
        align-items: center;
        background: #F3F4F6;
        color: #6B7280;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.75rem;
        margin-bottom: 0.75rem;
    }
    
    .search-result-excerpt {
        color: #4B5563;
        line-height: 1.625;
    }
    
    .search-result-excerpt strong {
        background: #FEF3C7;
        padding: 0.125rem 0.25rem;
        border-radius: 0.125rem;
        font-weight: 600;
        color: #92400E;
    }
    
    .no-results {
        text-align: center;
        padding: 4rem 2rem;
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
    }
    
    .no-results-icon {
        width: 4rem;
        height: 4rem;
        margin: 0 auto 1rem;
        color: #9CA3AF;
    }
    
    .search-stats {
        color: #6B7280;
        margin-bottom: 2rem;
        font-size: 0.875rem;
    }
    
    .search-filters {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        padding: 1rem;
        margin-bottom: 2rem;
    }
    
    .filter-button {
        display: inline-flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border: 1px solid #E5E7EB;
        border-radius: 0.5rem;
        background: white;
        color: #374151;
        margin-right: 0.5rem;
        margin-bottom: 0.5rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .filter-button:hover {
        border-color: #3B82F6;
        color: #3B82F6;
    }
    
    .filter-button.active {
        background: #3B82F6;
        color: white;
        border-color: #3B82F6;
    }
    
    .search-suggestions {
        background: #F9FAFB;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-top: 2rem;
    }
    
    .suggestion-link {
        color: #3B82F6;
        text-decoration: none;
        display: inline-block;
        margin: 0.25rem;
    }
    
    .suggestion-link:hover {
        text-decoration: underline;
    }
</style>
@endsection

@section('content')
<div class="search-hero">
    <div class="container mx-auto px-4">
        <div class="text-center">
            <h1 class="text-3xl font-bold mb-4">Suchergebnisse</h1>
            <form action="{{ route('help.search') }}" method="GET" class="max-w-2xl mx-auto">
                <div class="relative">
                    <input type="text" 
                           name="q" 
                           value="{{ $query }}"
                           placeholder="Suchen Sie nach Artikeln, Themen oder Fragen..." 
                           class="w-full px-4 py-3 pl-12 rounded-lg text-gray-800 text-lg shadow-lg"
                           autocomplete="off">
                    <svg class="absolute left-4 top-4 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <div class="search-results-container">
        @if($query)
            <!-- Search Stats -->
            <div class="search-stats">
                @if(count($results) > 0)
                    <p>{{ count($results) }} Ergebnis{{ count($results) !== 1 ? 'se' : '' }} für "<strong>{{ $query }}</strong>"</p>
                @else
                    <p>Keine Ergebnisse für "<strong>{{ $query }}</strong>"</p>
                @endif
            </div>
            
            @if(count($results) > 0)
                <!-- Category Filters -->
                <div class="search-filters" id="category-filters">
                    <p class="text-sm text-gray-600 mb-2">Nach Kategorie filtern:</p>
                    <button class="filter-button active" data-category="all">
                        Alle ({{ count($results) }})
                    </button>
                    @php
                        $categoryCounts = [];
                        foreach($results as $result) {
                            $cat = $result['category'];
                            $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
                        }
                    @endphp
                    @foreach($categoryCounts as $cat => $count)
                    <button class="filter-button" data-category="{{ $cat }}">
                        {{ ucfirst(str_replace('-', ' ', $cat)) }} ({{ $count }})
                    </button>
                    @endforeach
                </div>
                
                <!-- Search Results -->
                <div id="search-results">
                    @foreach($results as $result)
                    <div class="search-result-card" data-category="{{ $result['category'] }}">
                        <span class="search-result-category">
                            {{ ucfirst(str_replace('-', ' ', $result['category'])) }}
                        </span>
                        <a href="{{ $result['url'] }}" class="search-result-title" data-track-click="{{ $query }}">
                            {{ $result['title'] }}
                        </a>
                        <div class="search-result-excerpt">
                            {!! $result['excerpt'] !!}
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <!-- No Results -->
                <div class="no-results">
                    <svg class="no-results-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h2 class="text-xl font-semibold mb-2">Keine Ergebnisse gefunden</h2>
                    <p class="text-gray-600 mb-4">Wir konnten keine Artikel finden, die Ihrer Suche entsprechen.</p>
                    
                    <div class="search-suggestions">
                        <h3 class="font-semibold mb-3">Vorschläge:</h3>
                        <ul class="text-left max-w-md mx-auto text-gray-600">
                            <li class="mb-2">• Überprüfen Sie Ihre Rechtschreibung</li>
                            <li class="mb-2">• Verwenden Sie allgemeinere Suchbegriffe</li>
                            <li class="mb-2">• Versuchen Sie es mit anderen Schlüsselwörtern</li>
                            <li class="mb-2">• Durchsuchen Sie unsere <a href="{{ route('help.index') }}" class="text-blue-600 hover:underline">Kategorien</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Popular Topics -->
                <div class="search-suggestions mt-8">
                    <h3 class="font-semibold mb-3">Beliebte Themen:</h3>
                    <div>
                        <a href="{{ route('help.article', ['category' => 'getting-started', 'topic' => 'first-call']) }}" class="suggestion-link">
                            Ersten Anruf tätigen
                        </a>
                        <a href="{{ route('help.article', ['category' => 'appointments', 'topic' => 'view-appointments']) }}" class="suggestion-link">
                            Termine anzeigen
                        </a>
                        <a href="{{ route('help.article', ['category' => 'account', 'topic' => 'password-change']) }}" class="suggestion-link">
                            Passwort ändern
                        </a>
                        <a href="{{ route('help.article', ['category' => 'billing', 'topic' => 'view-invoices']) }}" class="suggestion-link">
                            Rechnungen einsehen
                        </a>
                        <a href="{{ route('help.article', ['category' => 'troubleshooting', 'topic' => 'common-issues']) }}" class="suggestion-link">
                            Häufige Probleme
                        </a>
                    </div>
                </div>
            @endif
        @else
            <!-- No Query -->
            <div class="text-center py-12">
                <h2 class="text-2xl font-semibold mb-4">Was suchen Sie?</h2>
                <p class="text-gray-600 mb-8">Geben Sie einen Suchbegriff ein, um relevante Hilfeartikel zu finden.</p>
                <a href="{{ route('help.index') }}" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Zurück zum Hilfe-Center
                </a>
            </div>
        @endif
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Category filtering
    const filterButtons = document.querySelectorAll('.filter-button');
    const resultCards = document.querySelectorAll('.search-result-card');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            const category = this.dataset.category;
            
            // Update button states
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            // Filter results
            resultCards.forEach(card => {
                if (category === 'all' || card.dataset.category === category) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    
    // Track search result clicks
    document.querySelectorAll('[data-track-click]').forEach(link => {
        link.addEventListener('click', function() {
            const query = this.dataset.trackClick;
            const url = this.href;
            
            // Send tracking request
            fetch('{{ route('help.track-click') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    query: query,
                    clicked_url: url
                })
            }).catch(error => console.error('Tracking error:', error));
        });
    });
    
    // Auto-focus search input
    const searchInput = document.querySelector('input[name="q"]');
    if (searchInput && !searchInput.value) {
        searchInput.focus();
    }
});
</script>
@endsection