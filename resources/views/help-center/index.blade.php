@extends('portal.layouts.app')

@section('title', 'Hilfe-Center')

@section('styles')
<style>
    .help-hero {
        background: linear-gradient(135deg, #3B82F6 0%, #1E40AF 100%);
        color: white;
        padding: 5rem 0;
        position: relative;
        overflow: hidden;
    }
    
    .help-hero::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -10%;
        width: 60%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
        transform: rotate(30deg);
    }
    
    .search-container {
        max-width: 600px;
        margin: 2rem auto 0;
        position: relative;
        z-index: 1;
    }
    
    .search-input {
        padding: 1rem 1rem 1rem 3.5rem;
        font-size: 1.125rem;
        border: none;
        border-radius: 0.75rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        transition: all 0.3s ease;
    }
    
    .search-input:focus {
        box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        transform: translateY(-2px);
    }
    
    .search-icon {
        position: absolute;
        left: 1.25rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6B7280;
    }
    
    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 3rem;
    }
    
    .category-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        padding: 2rem;
        transition: all 0.3s ease;
        border: 1px solid #E5E7EB;
    }
    
    .category-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        border-color: #3B82F6;
    }
    
    .category-icon {
        width: 3rem;
        height: 3rem;
        background: #EBF5FF;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 1rem;
        color: #3B82F6;
    }
    
    .popular-articles {
        background: #F9FAFB;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-top: 2rem;
    }
    
    .popular-article {
        background: white;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        transition: all 0.3s ease;
        border: 1px solid #E5E7EB;
    }
    
    .popular-article:hover {
        border-color: #3B82F6;
        transform: translateX(4px);
    }
    
    .popular-number {
        background: #3B82F6;
        color: white;
        width: 2rem;
        height: 2rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        margin-right: 1rem;
        flex-shrink: 0;
    }
    
    .quick-links {
        background: linear-gradient(135deg, #F3F4F6 0%, #E5E7EB 100%);
        border-radius: 0.75rem;
        padding: 2rem;
        margin-top: 3rem;
    }
    
    .quick-link-item {
        display: inline-flex;
        align-items: center;
        background: white;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        margin: 0.25rem;
        transition: all 0.3s ease;
        border: 1px solid #E5E7EB;
    }
    
    .quick-link-item:hover {
        background: #3B82F6;
        color: white;
        border-color: #3B82F6;
    }
    
    .quick-link-item svg {
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.5rem;
    }
    
    .contact-section {
        background: white;
        border: 2px solid #E5E7EB;
        border-radius: 0.75rem;
        padding: 2rem;
        margin-top: 3rem;
        text-align: center;
    }
    
    .contact-section:hover {
        border-color: #3B82F6;
    }
    
    .search-trends {
        margin-top: 1rem;
        text-align: center;
    }
    
    .trend-tag {
        display: inline-block;
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        margin: 0.25rem;
        font-size: 0.875rem;
        backdrop-filter: blur(10px);
        transition: all 0.3s ease;
        cursor: pointer;
    }
    
    .trend-tag:hover {
        background: rgba(255,255,255,0.3);
        transform: scale(1.05);
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 1rem;
        margin: 2rem 0;
    }
    
    .stat-card {
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.2);
        border-radius: 0.5rem;
        padding: 1rem;
        text-align: center;
    }
    
    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        display: block;
    }
    
    .stat-label {
        font-size: 0.875rem;
        opacity: 0.9;
    }
</style>
@endsection

@section('content')
<div class="help-hero">
    <div class="container mx-auto px-4">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Wie können wir Ihnen helfen?</h1>
            <p class="text-xl opacity-90 mb-8">Finden Sie schnell Antworten auf Ihre Fragen</p>
            
            <div class="search-container">
                <form action="{{ route('help.search') }}" method="GET" class="relative">
                    <input type="text" 
                           name="q" 
                           placeholder="Suchen Sie nach Artikeln, Themen oder Fragen..." 
                           class="search-input w-full text-gray-800"
                           autocomplete="off"
                           id="help-search-input">
                    <svg class="search-icon w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </form>
                
                @if(count($searchTrends) > 0)
                <div class="search-trends">
                    <p class="text-sm opacity-80 mb-2">Beliebte Suchbegriffe:</p>
                    @foreach(array_slice($searchTrends, 0, 5) as $trend)
                    <a href="{{ route('help.search', ['q' => $trend->query]) }}" class="trend-tag">
                        {{ $trend->query }}
                    </a>
                    @endforeach
                </div>
                @endif
            </div>
            
            <div class="stats-grid max-w-2xl mx-auto mt-8">
                <div class="stat-card">
                    <span class="stat-number">{{ count($categories) }}</span>
                    <span class="stat-label">Kategorien</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">{{ array_sum(array_map(fn($cat) => count($cat['articles']), $categories)) }}</span>
                    <span class="stat-label">Artikel</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">{{ number_format($totalViews) }}</span>
                    <span class="stat-label">Aufrufe (30 Tage)</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Verfügbar</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container mx-auto px-4 py-8">
    <!-- Popular Articles Section -->
    @if(count($popularArticles) > 0)
    <div class="max-w-4xl mx-auto">
        <h2 class="text-2xl font-bold mb-4">Beliebte Artikel</h2>
        <div class="popular-articles">
            @foreach($popularArticles as $index => $article)
            <a href="{{ $article['url'] }}" class="popular-article text-decoration-none">
                <div class="popular-number">{{ $index + 1 }}</div>
                <div class="flex-1">
                    <h3 class="font-semibold text-gray-800 mb-1">{{ $article['title'] }}</h3>
                    <p class="text-sm text-gray-600">{{ $article['excerpt'] }}</p>
                </div>
                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
            @endforeach
        </div>
    </div>
    @endif
    
    <!-- Categories Grid -->
    <div class="max-w-6xl mx-auto mt-12">
        <h2 class="text-2xl font-bold mb-6 text-center">Alle Hilfethemen</h2>
        <div class="category-grid">
            @foreach($categories as $categoryKey => $category)
            <div class="category-card">
                <div class="category-icon">
                    @switch($category['icon'])
                        @case('lightning-bolt')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            @break
                        @case('calendar')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            @break
                        @case('user')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            @break
                        @case('credit-card')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            @break
                        @case('exclamation-circle')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            @break
                        @case('question-mark-circle')
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            @break
                    @endswitch
                </div>
                <h3 class="text-xl font-semibold mb-2">{{ $category['name'] }}</h3>
                <p class="text-gray-600 mb-4">{{ count($category['articles']) }} Artikel</p>
                
                @if(count($category['articles']) > 0)
                <ul class="space-y-2">
                    @foreach(array_slice($category['articles'], 0, 3) as $article)
                    <li>
                        <a href="{{ $article['url'] }}" class="text-blue-600 hover:text-blue-700 text-sm flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                            {{ $article['title'] }}
                        </a>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    
    <!-- Quick Links -->
    <div class="max-w-4xl mx-auto">
        <div class="quick-links">
            <h3 class="text-xl font-semibold mb-4 text-center">Schnellzugriff</h3>
            <div class="text-center">
                <a href="{{ route('help.article', ['category' => 'getting-started', 'topic' => 'first-call']) }}" class="quick-link-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    Ersten Anruf tätigen
                </a>
                <a href="{{ route('help.article', ['category' => 'appointments', 'topic' => 'view-appointments']) }}" class="quick-link-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                    </svg>
                    Termine anzeigen
                </a>
                <a href="{{ route('help.article', ['category' => 'account', 'topic' => 'profile-edit']) }}" class="quick-link-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Profil bearbeiten
                </a>
                <a href="{{ route('help.article', ['category' => 'billing', 'topic' => 'payment-methods']) }}" class="quick-link-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    Zahlungsmethoden
                </a>
                <a href="{{ route('help.article', ['category' => 'troubleshooting', 'topic' => 'common-issues']) }}" class="quick-link-item">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                    Häufige Probleme
                </a>
            </div>
        </div>
    </div>
    
    <!-- Contact Support -->
    <div class="max-w-4xl mx-auto">
        <div class="contact-section">
            <svg class="w-12 h-12 mx-auto mb-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
            </svg>
            <h3 class="text-xl font-semibold mb-2">Brauchen Sie persönliche Hilfe?</h3>
            <p class="text-gray-600 mb-4">Unser Support-Team ist für Sie da!</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="mailto:support@askproai.de" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    </svg>
                    E-Mail schreiben
                </a>
                <a href="tel:+4989215453990" class="inline-flex items-center justify-center px-6 py-3 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                    089 2154 5399-0
                </a>
            </div>
            <p class="text-sm text-gray-500 mt-4">Support-Zeiten: Mo-Fr 9:00 - 18:00 Uhr</p>
        </div>
    </div>
</div>

<!-- Floating Help Widget -->
<div id="help-widget" class="fixed bottom-6 right-6 z-50">
    <button id="help-widget-toggle" class="bg-blue-600 text-white rounded-full w-14 h-14 shadow-lg hover:bg-blue-700 transition-all hover:scale-110 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
    </button>
    
    <div id="help-widget-content" class="hidden absolute bottom-16 right-0 bg-white rounded-lg shadow-xl w-80 border border-gray-200">
        <div class="p-4 border-b border-gray-200">
            <h4 class="font-semibold text-gray-800">Schnelle Hilfe</h4>
        </div>
        <div class="p-4">
            <form action="{{ route('help.search') }}" method="GET" class="mb-4">
                <input type="text" name="q" placeholder="Was suchen Sie?" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </form>
            <div class="space-y-2">
                <a href="{{ route('help.article', ['category' => 'getting-started', 'topic' => 'first-call']) }}" class="block text-sm text-blue-600 hover:text-blue-700">
                    → Wie buche ich einen Termin?
                </a>
                <a href="{{ route('help.article', ['category' => 'appointments', 'topic' => 'cancel-reschedule']) }}" class="block text-sm text-blue-600 hover:text-blue-700">
                    → Termin absagen oder verschieben
                </a>
                <a href="{{ route('help.article', ['category' => 'account', 'topic' => 'password-change']) }}" class="block text-sm text-blue-600 hover:text-blue-700">
                    → Passwort ändern
                </a>
            </div>
            <a href="{{ route('help.index') }}" class="block mt-4 text-center text-sm text-gray-600 hover:text-gray-800">
                Alle Hilfethemen anzeigen →
            </a>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Help widget toggle
    const widgetToggle = document.getElementById('help-widget-toggle');
    const widgetContent = document.getElementById('help-widget-content');
    
    widgetToggle.addEventListener('click', function() {
        widgetContent.classList.toggle('hidden');
    });
    
    // Close widget when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('#help-widget')) {
            widgetContent.classList.add('hidden');
        }
    });
    
    // Search autocomplete (enhanced)
    const searchInput = document.getElementById('help-search-input');
    let searchTimeout;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        const query = this.value.trim();
        
        if (query.length > 2) {
            searchTimeout = setTimeout(() => {
                // You could add autocomplete suggestions here
                console.log('Search for:', query);
            }, 300);
        }
    });
    
    // Track search trends clicks
    document.querySelectorAll('.trend-tag').forEach(tag => {
        tag.addEventListener('click', function(e) {
            // Track trend click for analytics
            console.log('Trend clicked:', this.textContent.trim());
        });
    });
});
</script>
@endsection