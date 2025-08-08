<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - AskProAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <h1 class="text-3xl font-bold text-gray-900">
                        📋 System Logs
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
                    <a href="/telescope/logs" class="text-white font-medium">Logs</a>
                    <a href="/telescope/queries" class="text-gray-300 hover:text-white">Queries</a>
                    <a href="/horizon" class="text-gray-300 hover:text-white">Horizon</a>
                </nav>
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow mb-6 p-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Letzte {{ $logs->count() }} Log-Einträge</h2>
                    <button onclick="location.reload()" class="text-sm bg-gray-500 text-white px-3 py-1 rounded hover:bg-gray-600">
                        Aktualisieren
                    </button>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Zeit
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Level
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nachricht
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Context
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($logs as $log)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ \Carbon\Carbon::parse($log->created_at ?? now())->format('d.m.Y H:i:s') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $level = $log->level ?? 'info';
                                            $colors = [
                                                'debug' => 'bg-gray-100 text-gray-800',
                                                'info' => 'bg-blue-100 text-blue-800',
                                                'notice' => 'bg-indigo-100 text-indigo-800',
                                                'warning' => 'bg-yellow-100 text-yellow-800',
                                                'error' => 'bg-red-100 text-red-800',
                                                'critical' => 'bg-red-600 text-white',
                                                'alert' => 'bg-orange-600 text-white',
                                                'emergency' => 'bg-red-900 text-white',
                                            ];
                                            $colorClass = $colors[$level] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $colorClass }}">
                                            {{ strtoupper($level) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-900">
                                        <div class="max-w-md truncate" title="{{ $log->message ?? '' }}">
                                            {{ $log->message ?? 'N/A' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500">
                                        @if(isset($log->context))
                                            <details class="cursor-pointer">
                                                <summary class="text-blue-600 hover:text-blue-800">Details anzeigen</summary>
                                                <pre class="mt-2 text-xs bg-gray-100 p-2 rounded overflow-x-auto">{{ json_encode(json_decode($log->context ?? '{}'), JSON_PRETTY_PRINT) }}</pre>
                                            </details>
                                        @else
                                            -
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-4 text-center text-gray-500">
                                        Keine Log-Einträge gefunden
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Auto-Refresh Notice -->
            <div class="mt-4 text-center text-sm text-gray-500">
                Diese Seite zeigt die letzten 100 Log-Einträge. Klicken Sie auf "Aktualisieren" für neue Einträge.
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>