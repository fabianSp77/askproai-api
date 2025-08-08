<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Queries - AskProAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <h1 class="text-3xl font-bold text-gray-900">
                        🔍 Database Queries
                    </h1>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-500">
                            {{ auth()->user()->email }}
                        </span>
                        <a href="/admin" class="text-sm bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Zurück zum Admin
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation -->
        <div class="bg-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex space-x-8 py-4">
                    <a href="/telescope" class="text-gray-300 hover:text-white">Dashboard</a>
                    <a href="/telescope/logs" class="text-gray-300 hover:text-white">Logs</a>
                    <a href="/telescope/queries" class="text-white font-medium">Queries</a>
                    <a href="/horizon" class="text-gray-300 hover:text-white">Horizon</a>
                </nav>
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @if(empty($queries))
                <!-- No Queries Info -->
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Query-Tracking ist derzeit nicht aktiv. Um Queries zu tracken, aktivieren Sie das Debug-Logging in der .env Datei.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Alternative: Show recent queries from logs table -->
                @php
                    $recentQueries = \DB::table('logs')
                        ->where('message', 'like', '%query%')
                        ->orWhere('message', 'like', '%SELECT%')
                        ->orWhere('message', 'like', '%INSERT%')
                        ->orWhere('message', 'like', '%UPDATE%')
                        ->orWhere('message', 'like', '%DELETE%')
                        ->orderBy('created_at', 'desc')
                        ->limit(50)
                        ->get();
                @endphp

                @if($recentQueries->isNotEmpty())
                    <div class="bg-white rounded-lg shadow mb-6 p-4">
                        <h2 class="text-lg font-semibold text-gray-900 mb-2">Query-bezogene Log-Einträge</h2>
                        <p class="text-sm text-gray-600">Zeigt Log-Einträge die SQL-Queries enthalten könnten</p>
                    </div>

                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Zeit
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Query / Nachricht
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($recentQueries as $query)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($query->created_at)->format('H:i:s') }}
                                            </td>
                                            <td class="px-6 py-4">
                                                <code class="text-xs bg-gray-100 p-2 rounded block overflow-x-auto">
                                                    {{ $query->message }}
                                                </code>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @else
                    <div class="bg-white rounded-lg shadow p-8 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M12 12h.01M12 12h.01M12 12h.01M12 12h.01M12 12h.01M12 12h.01M12 12h.01M12 12h.01M12 12h.01"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">Keine Queries gefunden</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Es wurden keine Query-bezogenen Log-Einträge gefunden.
                        </p>
                    </div>
                @endif
            @else
                <!-- Show Cached Queries -->
                <div class="bg-white rounded-lg shadow mb-6 p-4">
                    <h2 class="text-lg font-semibold text-gray-900">Letzte Queries</h2>
                </div>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Zeit
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Dauer (ms)
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Query
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($queries as $query)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $query['time'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ ($query['duration'] ?? 0) > 100 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                                {{ $query['duration'] ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <code class="text-xs bg-gray-100 p-2 rounded block overflow-x-auto">
                                                {{ $query['sql'] ?? 'N/A' }}
                                            </code>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Info -->
            <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Tipp:</strong> Für detailliertes Query-Monitoring empfehlen wir die Installation von Laravel Debugbar oder Telescope nach Behebung der Composer-Konflikte.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>