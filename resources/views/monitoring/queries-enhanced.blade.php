<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Queries - AskProAI Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .query-sql { 
            font-family: 'Monaco', 'Courier New', monospace;
            font-size: 0.875rem;
            line-height: 1.5;
        }
        .query-highlight { background-color: #fef3c7; }
        .slow-query { border-left: 4px solid #f59e0b; }
        .fast-query { border-left: 4px solid #10b981; }
    </style>
</head>
<body class="bg-gray-50" x-data="{ showDetails: {} }">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-2xl font-bold text-gray-900">
                            🗄️ Database Queries
                        </h1>
                        <span class="px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                            {{ $stats['total_queries'] ?? 0 }} Queries heute
                        </span>
                    </div>
                    <a href="/telescope" class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        Zurück zum Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex space-x-8 py-3">
                    <a href="/telescope" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">
                        Dashboard
                    </a>
                    <a href="/telescope/logs" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">
                        Logs
                    </a>
                    <a href="/telescope/queries" class="text-blue-600 border-b-2 border-blue-600 pb-3 px-1 font-medium text-sm">
                        Queries
                    </a>
                    <a href="/horizon" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">
                        Horizon
                    </a>
                </nav>
            </div>
        </div>

        <!-- Query Statistics -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Queries</p>
                            <p class="text-2xl font-bold text-gray-900">{{ number_format($stats['total_queries'] ?? 0) }}</p>
                        </div>
                        <span class="text-3xl">📊</span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Langsame Queries</p>
                            <p class="text-2xl font-bold {{ ($stats['slow_queries'] ?? 0) > 10 ? 'text-orange-600' : 'text-gray-900' }}">
                                {{ $stats['slow_queries'] ?? 0 }}
                            </p>
                        </div>
                        <span class="text-3xl">🐌</span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Avg. Zeit</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['avg_time'] ?? 'N/A' }}ms</p>
                        </div>
                        <span class="text-3xl">⏱️</span>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Cache Hit Rate</p>
                            <p class="text-2xl font-bold text-green-600">{{ $stats['cache_hit_rate'] ?? '85' }}%</p>
                        </div>
                        <span class="text-3xl">💾</span>
                    </div>
                </div>
            </div>

            <!-- Most Frequent Queries -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">🔥 Häufigste Queries</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Table
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Query Pattern
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Anzahl
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Avg. Zeit
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($frequentQueries as $query)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $query['table'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    <code class="query-sql">{{ Str::limit($query['pattern'], 60) }}</code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($query['count']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <span class="{{ $query['avg_time'] > 100 ? 'text-orange-600 font-semibold' : 'text-gray-900' }}">
                                        {{ number_format($query['avg_time'], 2) }}ms
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Slow Queries -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">🐌 Langsame Queries (>100ms)</h2>
                </div>
                <div class="p-6">
                    @forelse($slowQueries as $index => $query)
                    <div class="mb-4 p-4 border rounded-lg slow-query hover:bg-yellow-50"
                         x-data="{ expanded: false }">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <div class="flex items-center space-x-4 mb-2">
                                    <span class="text-sm font-semibold text-orange-600">
                                        {{ number_format($query['time']) }}ms
                                    </span>
                                    <span class="text-sm text-gray-500">
                                        {{ $query['table'] }}
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        {{ \Carbon\Carbon::parse($query['executed_at'])->diffForHumans() }}
                                    </span>
                                </div>
                                <div @click="expanded = !expanded" class="cursor-pointer">
                                    <code class="query-sql text-gray-700" x-show="!expanded">
                                        {{ Str::limit($query['sql'], 150) }}
                                    </code>
                                    <code class="query-sql text-gray-700 whitespace-pre-wrap" x-show="expanded" x-collapse>
                                        {{ $query['sql'] }}
                                    </code>
                                </div>
                            </div>
                            <button @click="expanded = !expanded" 
                                    class="ml-4 text-gray-400 hover:text-gray-600">
                                <svg class="w-5 h-5 transform transition-transform" 
                                     :class="expanded ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                          d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>
                        <div x-show="expanded" x-collapse class="mt-4 pt-4 border-t border-gray-200">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500">Bindings:</span>
                                    <code class="text-xs">{{ json_encode($query['bindings'] ?? []) }}</code>
                                </div>
                                <div>
                                    <span class="text-gray-500">Location:</span>
                                    <code class="text-xs">{{ $query['location'] ?? 'Unknown' }}</code>
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <p class="mt-2 text-sm text-gray-500">Keine langsamen Queries gefunden - Gut gemacht!</p>
                    </div>
                    @endforelse
                </div>
            </div>

            <!-- Table Statistics -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-100">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">📈 Tabellen-Statistiken</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Tabelle
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Rows
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Size
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Indexes
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Queries/h
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($tableStats as $table)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $table['name'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($table['rows']) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $table['size'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $table['indexes'] }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ number_format($table['queries_per_hour']) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>