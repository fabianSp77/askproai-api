@extends('portal.layouts.app')

@section('title', 'Help Center Analytics Dashboard')

@section('styles')
<style>
    .analytics-card {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        padding: 1.5rem;
        border: 1px solid #E5E7EB;
    }
    
    .stat-card {
        text-align: center;
        padding: 2rem;
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: #1F2937;
        margin-bottom: 0.5rem;
    }
    
    .stat-label {
        color: #6B7280;
        font-size: 0.875rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    
    .trend-positive {
        color: #10B981;
    }
    
    .trend-negative {
        color: #EF4444;
    }
    
    .analytics-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .analytics-table th {
        text-align: left;
        padding: 0.75rem;
        border-bottom: 1px solid #E5E7EB;
        font-weight: 600;
        color: #6B7280;
        font-size: 0.875rem;
    }
    
    .analytics-table td {
        padding: 0.75rem;
        border-bottom: 1px solid #F3F4F6;
    }
    
    .analytics-table tr:hover {
        background: #F9FAFB;
    }
    
    .popularity-bar {
        background: #E5E7EB;
        height: 0.5rem;
        border-radius: 0.25rem;
        overflow: hidden;
    }
    
    .popularity-fill {
        height: 100%;
        background: #3B82F6;
        transition: width 0.3s ease;
    }
    
    .feedback-comment {
        background: #F9FAFB;
        border-left: 3px solid #3B82F6;
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 0.375rem;
    }
    
    .search-query-tag {
        display: inline-block;
        background: #F3F4F6;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.875rem;
        margin: 0.25rem;
    }
    
    .time-filter {
        display: inline-flex;
        background: #F3F4F6;
        border-radius: 0.5rem;
        padding: 0.25rem;
    }
    
    .time-filter-button {
        padding: 0.5rem 1rem;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: #6B7280;
        transition: all 0.2s;
    }
    
    .time-filter-button.active {
        background: white;
        color: #3B82F6;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
</style>
@endsection

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Help Center Analytics</h1>
        <p class="text-gray-600">Insights und Metriken für das Hilfe-Center</p>
    </div>
    
    <!-- Time Filter -->
    <div class="mb-6 flex justify-between items-center">
        <div class="time-filter">
            <a href="?days=7" class="time-filter-button @if($days == 7) active @endif">7 Tage</a>
            <a href="?days=30" class="time-filter-button @if($days == 30) active @endif">30 Tage</a>
            <a href="?days=90" class="time-filter-button @if($days == 90) active @endif">90 Tage</a>
        </div>
    </div>
    
    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="analytics-card stat-card">
            <div class="stat-number">{{ number_format($totalViews) }}</div>
            <div class="stat-label">Gesamtaufrufe</div>
        </div>
        
        <div class="analytics-card stat-card">
            <div class="stat-number">{{ number_format($uniqueVisitors) }}</div>
            <div class="stat-label">Unique Visitors</div>
        </div>
        
        <div class="analytics-card stat-card">
            <div class="stat-number">{{ number_format($totalSearches) }}</div>
            <div class="stat-label">Suchanfragen</div>
        </div>
        
        <div class="analytics-card stat-card">
            <div class="stat-number">{{ $searchConversionRate }}%</div>
            <div class="stat-label">Search → Click Rate</div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Popular Articles -->
        <div class="analytics-card">
            <h2 class="text-xl font-semibold mb-4">Beliebteste Artikel</h2>
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Artikel</th>
                        <th class="text-center">Aufrufe</th>
                        <th>Trend</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($popularArticles as $article)
                    <tr>
                        <td>
                            <a href="{{ route('help.article', ['category' => $article->category, 'topic' => $article->topic]) }}" 
                               class="text-blue-600 hover:text-blue-700">
                                {{ $article->title }}
                            </a>
                            <div class="text-xs text-gray-500">{{ ucfirst(str_replace('-', ' ', $article->category)) }}</div>
                        </td>
                        <td class="text-center font-semibold">{{ number_format($article->view_count) }}</td>
                        <td>
                            <div class="popularity-bar w-20">
                                <div class="popularity-fill" style="width: {{ min(100, $article->view_count / max(1, $popularArticles[0]->view_count ?? 1) * 100) }}%"></div>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Popular Search Queries -->
        <div class="analytics-card">
            <h2 class="text-xl font-semibold mb-4">Häufigste Suchbegriffe</h2>
            <table class="analytics-table">
                <thead>
                    <tr>
                        <th>Suchbegriff</th>
                        <th class="text-center">Anzahl</th>
                        <th class="text-center">Ergebnisse</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($searchQueries as $query)
                    <tr>
                        <td>
                            <a href="{{ route('help.search', ['q' => $query->query]) }}" 
                               class="text-blue-600 hover:text-blue-700">
                                {{ $query->query }}
                            </a>
                        </td>
                        <td class="text-center font-semibold">{{ $query->count }}</td>
                        <td class="text-center">
                            <span class="@if($query->avg_results_count == 0) text-red-600 @else text-green-600 @endif">
                                {{ round($query->avg_results_count) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- No Results Queries -->
    @if(count($noResultQueries) > 0)
    <div class="analytics-card mb-8">
        <h2 class="text-xl font-semibold mb-4">Suchanfragen ohne Ergebnisse</h2>
        <p class="text-gray-600 mb-4">Diese Suchbegriffe lieferten keine Ergebnisse. Erwägen Sie, Artikel zu diesen Themen zu erstellen.</p>
        <div>
            @foreach($noResultQueries as $query)
            <span class="search-query-tag">{{ $query->query }} ({{ $query->count }}x)</span>
            @endforeach
        </div>
    </div>
    @endif
    
    <!-- Least Helpful Articles -->
    @if(count($leastHelpful) > 0)
    <div class="analytics-card mb-8">
        <h2 class="text-xl font-semibold mb-4">Artikel mit negativem Feedback</h2>
        <table class="analytics-table">
            <thead>
                <tr>
                    <th>Artikel</th>
                    <th class="text-center">Hilfreich</th>
                    <th class="text-center">Nicht hilfreich</th>
                    <th class="text-center">Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leastHelpful as $article)
                <tr>
                    <td>
                        <a href="{{ route('help.article', ['category' => $article->category, 'topic' => $article->topic]) }}" 
                           class="text-blue-600 hover:text-blue-700">
                            {{ $article->topic }}
                        </a>
                        <div class="text-xs text-gray-500">{{ ucfirst(str_replace('-', ' ', $article->category)) }}</div>
                    </td>
                    <td class="text-center text-green-600">{{ $article->helpful_count }}</td>
                    <td class="text-center text-red-600">{{ $article->not_helpful_count }}</td>
                    <td class="text-center">
                        <span class="@if($article->helpful_percentage < 50) text-red-600 font-semibold @endif">
                            {{ round($article->helpful_percentage) }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
    
    <!-- Recent Comments -->
    @if(count($recentComments) > 0)
    <div class="analytics-card">
        <h2 class="text-xl font-semibold mb-4">Neueste Kommentare</h2>
        @foreach($recentComments as $comment)
        <div class="feedback-comment">
            <div class="flex justify-between items-start mb-2">
                <a href="{{ route('help.article', ['category' => $comment->category, 'topic' => $comment->topic]) }}" 
                   class="font-semibold text-blue-600 hover:text-blue-700">
                    {{ $comment->topic }}
                </a>
                <span class="text-xs text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
            </div>
            <p class="text-gray-700">{{ $comment->comment }}</p>
            <div class="mt-2">
                <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full @if($comment->helpful) bg-green-100 text-green-800 @else bg-red-100 text-red-800 @endif">
                    @if($comment->helpful) Hilfreich @else Nicht hilfreich @endif
                </span>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>

<!-- Chart placeholder for future implementation -->
<script>
// Future implementation: Add charts for view trends and search trends
// Using Chart.js or similar library
</script>
@endsection