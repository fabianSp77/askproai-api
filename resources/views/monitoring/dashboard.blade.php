<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Monitoring - AskProAI</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <h1 class="text-3xl font-bold text-gray-900">
                        🔭 System Monitoring
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
                    <a href="/telescope" class="text-white font-medium">Dashboard</a>
                    <a href="/telescope/logs" class="text-gray-300 hover:text-white">Logs</a>
                    <a href="/telescope/queries" class="text-gray-300 hover:text-white">Queries</a>
                    <a href="/horizon" class="text-gray-300 hover:text-white">Horizon</a>
                </nav>
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- System Info Box -->
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Server Specs:</strong> 
                            {{ trim($metrics['system']['cpu_cores'] ?? '?') }} CPU Cores | 
                            {{ $metrics['system']['memory']['total'] ?? '?' }} RAM | 
                            {{ $metrics['system']['disk']['total'] ?? '?' }} Disk
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- System Status -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">System Uptime</h3>
                    <p class="text-2xl font-bold text-gray-900">{{ $metrics['system']['uptime'] ?? 'N/A' }}</p>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Load Average</h3>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ number_format($metrics['system']['load'][0] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-gray-500">
                        5m: {{ number_format($metrics['system']['load'][1] ?? 0, 2) }} | 
                        15m: {{ number_format($metrics['system']['load'][2] ?? 0, 2) }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        CPUs: {{ trim($metrics['system']['cpu_cores'] ?? '?') }}
                    </p>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Memory (RAM)</h3>
                    <p class="text-2xl font-bold text-gray-900">{{ $metrics['system']['memory']['used'] ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">
                        von {{ $metrics['system']['memory']['total'] ?? 'N/A' }} 
                        ({{ $metrics['system']['memory']['percentage'] ?? 'N/A' }})
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        Verfügbar: {{ $metrics['system']['memory']['available'] ?? 'N/A' }}
                    </p>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Disk Space</h3>
                    <p class="text-2xl font-bold text-gray-900">{{ $metrics['system']['disk']['free'] ?? 'N/A' }}</p>
                    <p class="text-xs text-gray-500">
                        von {{ $metrics['system']['disk']['total'] ?? 'N/A' }} frei
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        Belegt: {{ $metrics['system']['disk']['used'] ?? 'N/A' }} 
                        ({{ $metrics['system']['disk']['percentage'] ?? 'N/A' }})
                    </p>
                </div>
            </div>

            <!-- Database & Queue -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-900">Database</h2>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Queries heute:</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $metrics['database']['queries_today'] ?? 0 }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Langsame Queries:</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $metrics['database']['slow_queries'] ?? 0 }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-900">Queue</h2>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-3">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Jobs verarbeitet:</dt>
                                <dd class="text-sm font-medium text-gray-900">{{ $metrics['queue']['jobs_processed'] ?? 0 }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-500">Fehlgeschlagene Jobs:</dt>
                                <dd class="text-sm font-medium 
                                    @if(($metrics['queue']['failed_jobs'] ?? 0) > 0) text-red-600 @else text-gray-900 @endif">
                                    {{ $metrics['queue']['failed_jobs'] ?? 0 }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Errors -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b">
                    <h2 class="text-lg font-semibold text-gray-900">Fehler (letzte 24h)</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <p class="text-3xl font-bold 
                                @if(($metrics['errors']['last_24h'] ?? 0) > 10) text-orange-600 
                                @elseif(($metrics['errors']['last_24h'] ?? 0) > 0) text-yellow-600 
                                @else text-green-600 @endif">
                                {{ $metrics['errors']['last_24h'] ?? 0 }}
                            </p>
                            <p class="text-sm text-gray-500">Fehler insgesamt</p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold 
                                @if(($metrics['errors']['critical'] ?? 0) > 0) text-red-600 @else text-green-600 @endif">
                                {{ $metrics['errors']['critical'] ?? 0 }}
                            </p>
                            <p class="text-sm text-gray-500">Kritische Fehler</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>