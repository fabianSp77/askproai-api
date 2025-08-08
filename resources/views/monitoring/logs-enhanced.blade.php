<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - AskProAI Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        .log-entry { transition: all 0.2s ease; }
        .log-entry:hover { background-color: rgba(59, 130, 246, 0.05); }
        .severity-critical { border-left-color: #ef4444; }
        .severity-error { border-left-color: #f97316; }
        .severity-warning { border-left-color: #f59e0b; }
        .severity-info { border-left-color: #3b82f6; }
        .severity-debug { border-left-color: #6b7280; }
    </style>
</head>
<body class="bg-gray-50" x-data="logsViewer()">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-2xl font-bold text-gray-900">
                            📋 System Logs
                        </h1>
                        <span class="px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                            {{ $logs->total() }} Einträge
                        </span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button @click="toggleAutoRefresh()" 
                                class="px-3 py-1 text-sm rounded"
                                :class="autoRefresh ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700'">
                            Auto-Refresh: <span x-text="autoRefresh ? 'ON' : 'OFF'"></span>
                        </button>
                        <a href="/telescope" class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                            Zurück zum Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="bg-white border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex space-x-8 py-3">
                    <a href="/telescope" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">
                        Dashboard
                    </a>
                    <a href="/telescope/logs" class="text-blue-600 border-b-2 border-blue-600 pb-3 px-1 font-medium text-sm">
                        Logs
                    </a>
                    <a href="/telescope/queries" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">
                        Queries
                    </a>
                    <a href="/horizon" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">
                        Horizon
                    </a>
                </nav>
            </div>
        </div>

        <!-- Filters -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="bg-white rounded-lg shadow-sm p-4 border border-gray-100">
                <form method="GET" action="/telescope/logs" class="flex items-center space-x-4">
                    <!-- Level Filter -->
                    <div class="flex-1 max-w-xs">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                        <select name="level" onchange="this.form.submit()"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Alle Levels</option>
                            <option value="critical" {{ request('level') == 'critical' ? 'selected' : '' }}>
                                🔴 Critical
                            </option>
                            <option value="error" {{ request('level') == 'error' ? 'selected' : '' }}>
                                🟠 Error
                            </option>
                            <option value="warning" {{ request('level') == 'warning' ? 'selected' : '' }}>
                                🟡 Warning
                            </option>
                            <option value="info" {{ request('level') == 'info' ? 'selected' : '' }}>
                                🔵 Info
                            </option>
                            <option value="debug" {{ request('level') == 'debug' ? 'selected' : '' }}>
                                ⚪ Debug
                            </option>
                        </select>
                    </div>

                    <!-- Search -->
                    <div class="flex-1">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Suche</label>
                        <input type="text" name="search" value="{{ request('search') }}"
                               placeholder="Nach Nachricht suchen..."
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>

                    <!-- Time Range -->
                    <div class="flex-1 max-w-xs">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Zeitraum</label>
                        <select name="timeframe" onchange="this.form.submit()"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="1h">Letzte Stunde</option>
                            <option value="24h" selected>Letzte 24 Stunden</option>
                            <option value="7d">Letzte 7 Tage</option>
                            <option value="30d">Letzte 30 Tage</option>
                        </select>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-end space-x-2">
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">
                            Filter
                        </button>
                        <a href="/telescope/logs" 
                           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition">
                            Reset
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">
                                    Level
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                    Zeit
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nachricht
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                    Context
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($logs as $log)
                            <tr class="log-entry hover:bg-gray-50 border-l-4 
                                       severity-{{ $log->level ?? 'info' }}"
                                x-data="{ expanded: false }">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    @php
                                        $levelColors = [
                                            'critical' => 'text-red-600 bg-red-100',
                                            'error' => 'text-orange-600 bg-orange-100',
                                            'warning' => 'text-yellow-600 bg-yellow-100',
                                            'info' => 'text-blue-600 bg-blue-100',
                                            'debug' => 'text-gray-600 bg-gray-100',
                                        ];
                                        $levelColor = $levelColors[$log->level ?? 'info'] ?? 'text-gray-600 bg-gray-100';
                                        $levelIcons = [
                                            'critical' => '🔴',
                                            'error' => '🟠',
                                            'warning' => '🟡',
                                            'info' => '🔵',
                                            'debug' => '⚪',
                                        ];
                                        $levelIcon = $levelIcons[$log->level ?? 'info'] ?? '⚪';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $levelColor }}">
                                        {{ $levelIcon }} {{ strtoupper($log->level ?? 'INFO') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600">
                                    <div class="font-mono text-xs">
                                        {{ \Carbon\Carbon::parse($log->created_at)->format('d.m.Y') }}<br>
                                        {{ \Carbon\Carbon::parse($log->created_at)->format('H:i:s') }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="text-sm text-gray-900">
                                        <div class="line-clamp-2 cursor-pointer" @click="expanded = !expanded">
                                            {{ Str::limit($log->message, 200) }}
                                        </div>
                                        <div x-show="expanded" x-collapse class="mt-2 p-3 bg-gray-50 rounded text-xs font-mono">
                                            {{ $log->message }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($log->context && $log->context != '[]')
                                    <button @click="expanded = !expanded"
                                            class="text-blue-600 hover:text-blue-800 text-sm">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                    @else
                                    <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="mt-2">Keine Logs gefunden</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($logs->hasPages())
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Zeige <span class="font-medium">{{ $logs->firstItem() }}</span> 
                            bis <span class="font-medium">{{ $logs->lastItem() }}</span> 
                            von <span class="font-medium">{{ $logs->total() }}</span> Einträgen
                        </div>
                        <div class="flex space-x-2">
                            {{ $logs->links() }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function logsViewer() {
            return {
                autoRefresh: false,
                refreshInterval: null,
                
                toggleAutoRefresh() {
                    this.autoRefresh = !this.autoRefresh;
                    if (this.autoRefresh) {
                        this.refreshInterval = setInterval(() => {
                            window.location.reload();
                        }, 10000);
                    } else {
                        clearInterval(this.refreshInterval);
                    }
                }
            }
        }
    </script>
</body>
</html>