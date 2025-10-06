<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="30">
    <title>📞 Test Checklist - Retell & Cal.com Integration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <style>
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse { animation: pulse 2s infinite; }

        .status-operational { color: #10b981; }
        .status-warning { color: #f59e0b; }
        .status-error { color: #ef4444; }
        .status-info { color: #3b82f6; }
    </style>
</head>
<body class="bg-gray-50" x-data="{
    activeTab: 'status',
    showScenario: null,
    checkedItems: JSON.parse(localStorage.getItem('checkedItems') || '{}'),
    saveChecked(id, checked) {
        this.checkedItems[id] = checked;
        localStorage.setItem('checkedItems', JSON.stringify(this.checkedItems));
    }
}">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center">
                        <h1 class="text-2xl font-bold text-gray-900">
                            📞 Test Checklist
                        </h1>
                        <span class="ml-4 text-sm text-gray-500">
                            Auto-refresh: 30 Sekunden
                        </span>
                    </div>
                    <div class="flex space-x-2">
                        <a href="/admin" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                            ← Admin Panel
                        </a>
                        <button onclick="location.reload()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                            🔄 Aktualisieren
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Tabs -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <button @click="activeTab = 'status'"
                            :class="activeTab === 'status' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-2 px-1 border-b-2 font-medium text-sm">
                        🔍 System Status
                    </button>
                    <button @click="activeTab = 'phones'"
                            :class="activeTab === 'phones' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-2 px-1 border-b-2 font-medium text-sm">
                        📱 Telefonnummern
                    </button>
                    <button @click="activeTab = 'scenarios'"
                            :class="activeTab === 'scenarios' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-2 px-1 border-b-2 font-medium text-sm">
                        🧪 Test Szenarien
                    </button>
                    <button @click="activeTab = 'calls'"
                            :class="activeTab === 'calls' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-2 px-1 border-b-2 font-medium text-sm">
                        📞 Letzte Anrufe
                    </button>
                    <button @click="activeTab = 'quick'"
                            :class="activeTab === 'quick' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                            class="py-2 px-1 border-b-2 font-medium text-sm">
                        ⚡ Quick Actions
                    </button>
                </nav>
            </div>
        </div>

        <!-- Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-6">
            <!-- System Status Tab -->
            <div x-show="activeTab === 'status'" class="space-y-6">
                <!-- Overall Status -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-lg font-semibold mb-4">Gesamt-Status</h2>
                    <div class="flex items-center justify-between p-4 rounded-lg
                        {{ $systemStatus['overall']['status'] === 'operational' ? 'bg-green-50' :
                           ($systemStatus['overall']['status'] === 'warning' ? 'bg-yellow-50' : 'bg-red-50') }}">
                        <div class="flex items-center">
                            <span class="text-3xl mr-3">
                                {{ $systemStatus['overall']['status'] === 'operational' ? '✅' :
                                   ($systemStatus['overall']['status'] === 'warning' ? '⚠️' : '❌') }}
                            </span>
                            <div>
                                <p class="font-semibold text-lg">{{ $systemStatus['overall']['message'] }}</p>
                                <p class="text-sm text-gray-600">Letzte Prüfung: {{ $systemStatus['timestamp'] }}</p>
                            </div>
                        </div>
                        @if($systemStatus['overall']['ready_for_test'])
                            <span class="px-4 py-2 bg-green-100 text-green-800 rounded-full font-medium">
                                ✅ Bereit für Testanruf
                            </span>
                        @else
                            <span class="px-4 py-2 bg-red-100 text-red-800 rounded-full font-medium">
                                ❌ Nicht bereit
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Component Status Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($systemStatus['components'] as $name => $component)
                    <div class="bg-white rounded-lg shadow p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <span class="text-2xl mr-3">{{ $component['icon'] }}</span>
                                <div>
                                    <p class="font-semibold">{{ ucfirst(str_replace('_', ' ', $name)) }}</p>
                                    <p class="text-sm status-{{ $component['status'] }}">
                                        {{ $component['message'] }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        @if(isset($component['details']))
                            <div class="mt-3 pt-3 border-t text-xs text-gray-500">
                                @foreach($component['details'] as $key => $value)
                                    <p>{{ ucfirst(str_replace('_', ' ', $key)) }}: {{ $value }}</p>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endforeach
                </div>

                <!-- Webhook URLs -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">🔗 Webhook URLs</h3>
                    <div class="space-y-2 font-mono text-sm">
                        <div class="p-3 bg-gray-50 rounded">
                            <span class="text-gray-600">Retell Webhook:</span>
                            <span class="ml-2 text-blue-600">{{ url('/webhooks/retell') }}</span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded">
                            <span class="text-gray-600">Cal.com Webhook:</span>
                            <span class="ml-2 text-blue-600">{{ url('/webhooks/calcom') }}</span>
                        </div>
                        <div class="p-3 bg-gray-50 rounded">
                            <span class="text-gray-600">Function Call:</span>
                            <span class="ml-2 text-blue-600">{{ url('/webhooks/retell/function') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Phone Numbers Tab -->
            <div x-show="activeTab === 'phones'" class="space-y-6">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold">📱 Konfigurierte Telefonnummern</h2>
                    </div>
                    <div class="p-6">
                        @if($phoneNumbers->count() > 0)
                            <div class="space-y-4">
                                @foreach($phoneNumbers as $phone)
                                <div class="border rounded-lg p-4 {{ $phone['is_primary'] ? 'border-blue-500 bg-blue-50' : 'border-gray-200' }}">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <p class="text-lg font-semibold">{{ $phone['formatted'] }}</p>
                                            <p class="text-sm text-gray-600">{{ $phone['company'] }} - {{ $phone['branch'] }}</p>
                                            <p class="text-sm text-gray-500 mt-1">Agent: {{ $phone['agent'] }}</p>
                                            <p class="text-xs text-gray-400 font-mono">ID: {{ $phone['agent_id'] }}</p>
                                        </div>
                                        @if($phone['is_primary'])
                                            <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">
                                                Primary
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <p>⚠️ Keine Telefonnummern mit Retell Agent konfiguriert</p>
                                <p class="text-sm mt-2">Bitte konfigurieren Sie mindestens eine Nummer mit einem Retell Agent</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Test Scenarios Tab -->
            <div x-show="activeTab === 'scenarios'" class="space-y-6">
                @foreach($testScenarios as $scenario)
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b flex justify-between items-center cursor-pointer"
                         @click="showScenario = showScenario === {{ $scenario['id'] }} ? null : {{ $scenario['id'] }}">
                        <div>
                            <h3 class="text-lg font-semibold">{{ $scenario['title'] }}</h3>
                            <p class="text-sm text-gray-600">{{ $scenario['description'] }}</p>
                        </div>
                        <div class="flex items-center space-x-3">
                            <input type="checkbox"
                                   :checked="checkedItems['scenario_{{ $scenario['id'] }}']"
                                   @click.stop="saveChecked('scenario_{{ $scenario['id'] }}', $event.target.checked)"
                                   class="h-5 w-5 text-blue-600 rounded">
                            <svg :class="showScenario === {{ $scenario['id'] }} ? 'rotate-180' : ''"
                                 class="w-5 h-5 text-gray-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                    <div x-show="showScenario === {{ $scenario['id'] }}"
                         x-transition
                         class="p-6">
                        <!-- Test Steps -->
                        <div class="mb-6">
                            <h4 class="font-semibold mb-3">📋 Schritte:</h4>
                            <ol class="space-y-2">
                                @foreach($scenario['steps'] as $index => $step)
                                <li class="flex items-start">
                                    <span class="flex-shrink-0 w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-semibold mr-3">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="text-gray-700">{{ $step }}</span>
                                </li>
                                @endforeach
                            </ol>
                        </div>

                        <!-- Test Phrases -->
                        <div class="mb-6">
                            <h4 class="font-semibold mb-3">💬 Test-Phrasen:</h4>
                            <div class="space-y-2">
                                @foreach($scenario['test_phrases'] as $key => $phrase)
                                <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                    <span class="text-sm text-gray-600 w-32">{{ ucfirst($key) }}:</span>
                                    <span class="flex-1 font-mono text-sm select-all">"{{ $phrase }}"</span>
                                    <button onclick="navigator.clipboard.writeText('{{ $phrase }}')"
                                            class="ml-2 px-2 py-1 bg-gray-200 hover:bg-gray-300 rounded text-xs">
                                        📋 Kopieren
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Expected Result -->
                        <div class="p-4 bg-green-50 rounded-lg">
                            <h4 class="font-semibold text-green-800 mb-2">✅ Erwartetes Ergebnis:</h4>
                            <p class="text-green-700">{{ $scenario['expected_result'] }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Recent Calls Tab -->
            <div x-show="activeTab === 'calls'" class="space-y-6">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold">📞 Letzte Anrufe</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Von</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">An</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dauer</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Kunde</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zeitpunkt</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Termin</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($recentCalls as $call)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $call['from'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $call['to'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full
                                            {{ $call['status'] === 'completed' ? 'bg-green-100 text-green-800' :
                                               ($call['status'] === 'ongoing' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') }}">
                                            {{ $call['status'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $call['duration'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $call['customer'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $call['created'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        @if($call['appointment_made'])
                                            <span class="text-green-600">✅ Erstellt</span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        Keine Anrufe gefunden
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Tab -->
            <div x-show="activeTab === 'quick'" class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Test Webhook -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">🔧 Webhook Test</h3>
                        <p class="text-sm text-gray-600 mb-4">Sendet einen Test-Request an den Retell Webhook</p>
                        <button onclick="testWebhook()"
                                class="w-full px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                            🚀 Test Webhook
                        </button>
                        <div id="webhook-result" class="mt-4"></div>
                    </div>

                    <!-- Check Availability -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">📅 Verfügbarkeit prüfen</h3>
                        <p class="text-sm text-gray-600 mb-4">Prüft die Cal.com Verfügbarkeit für morgen</p>
                        <button onclick="checkAvailability()"
                                class="w-full px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600">
                            🔍 Verfügbarkeit prüfen
                        </button>
                        <div id="availability-result" class="mt-4"></div>
                    </div>

                    <!-- Clear Cache -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">🗑️ Cache leeren</h3>
                        <p class="text-sm text-gray-600 mb-4">Löscht alle gecachten Daten</p>
                        <button onclick="clearCache()"
                                class="w-full px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600">
                            🧹 Cache leeren
                        </button>
                        <div id="cache-result" class="mt-4"></div>
                    </div>

                    <!-- View Logs -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold mb-4">📋 Logs anzeigen</h3>
                        <p class="text-sm text-gray-600 mb-4">Öffnet die Laravel Logs</p>
                        <a href="/admin" target="_blank"
                           class="block w-full px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 text-center">
                            📊 Admin Panel öffnen
                        </a>
                    </div>
                </div>

                <!-- Test Numbers -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">☎️ Test-Telefonnummern</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @if($phoneNumbers->count() > 0)
                            @foreach($phoneNumbers->take(2) as $phone)
                            <div class="p-4 bg-blue-50 rounded-lg">
                                <p class="text-2xl font-bold text-blue-900">{{ $phone['formatted'] }}</p>
                                <p class="text-sm text-blue-700">{{ $phone['company'] }}</p>
                                <button onclick="navigator.clipboard.writeText('{{ $phone['number'] }}')"
                                        class="mt-2 px-3 py-1 bg-blue-200 hover:bg-blue-300 rounded text-sm">
                                    📋 Nummer kopieren
                                </button>
                            </div>
                            @endforeach
                        @else
                            <div class="col-span-2 text-center text-gray-500">
                                Keine Test-Nummern konfiguriert
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="mt-12 border-t bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex justify-between items-center text-sm text-gray-500">
                    <div>
                        <p>Retell & Cal.com Integration Test Suite</p>
                        <p class="text-xs mt-1">Version 1.0.0 | Environment: {{ app()->environment() }}</p>
                    </div>
                    <div class="text-right">
                        <p>Letzte Aktualisierung: {{ now()->format('d.m.Y H:i:s') }}</p>
                        <p class="text-xs mt-1">Automatische Aktualisierung alle 30 Sekunden</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <script>
        function testWebhook() {
            const resultDiv = document.getElementById('webhook-result');
            resultDiv.innerHTML = '<div class="text-gray-500">⏳ Teste Webhook...</div>';

            fetch('/test-checklist/test-webhook', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = '<div class="text-green-600">✅ ' + data.message + '</div>';
                    } else {
                        resultDiv.innerHTML = '<div class="text-red-600">❌ ' + data.message + '</div>';
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="text-red-600">❌ Fehler: ' + error.message + '</div>';
                });
        }

        function checkAvailability() {
            const resultDiv = document.getElementById('availability-result');
            resultDiv.innerHTML = '<div class="text-gray-500">⏳ Prüfe Verfügbarkeit...</div>';

            fetch('/test-checklist/check-availability', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = '<div class="text-green-600">✅ ' + data.message + '<br><small>Datum: ' + data.date + ' | Slots: ' + data.slots_count + '</small></div>';
                    } else {
                        resultDiv.innerHTML = '<div class="text-red-600">❌ ' + data.error + '</div>';
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="text-red-600">❌ Fehler: ' + error.message + '</div>';
                });
        }

        function clearCache() {
            const resultDiv = document.getElementById('cache-result');
            resultDiv.innerHTML = '<div class="text-gray-500">⏳ Leere Cache...</div>';

            fetch('/test-checklist/clear-cache', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = '<div class="text-green-600">✅ ' + data.message + '</div>';
                    } else {
                        resultDiv.innerHTML = '<div class="text-red-600">❌ Fehler: ' + data.error + '</div>';
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = '<div class="text-red-600">❌ Fehler: ' + error.message + '</div>';
                });
        }

        // Auto-refresh status every 30 seconds
        setInterval(() => {
            if (document.querySelector('[x-data]').__x.$data.activeTab === 'status') {
                location.reload();
            }
        }, 30000);
    </script>
</body>
</html>