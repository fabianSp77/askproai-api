<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cost Alerts - AskProAI Monitoring</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .alert-card { transition: all 0.2s ease; }
        .alert-card:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); }
        .severity-critical { border-left: 4px solid #ef4444; }
        .severity-warning { border-left: 4px solid #f59e0b; }
        .severity-info { border-left: 4px solid #3b82f6; }
    </style>
</head>
<body class="bg-gray-50" x-data="costAlertsData()">
    <div class="min-h-screen">
        <!-- Header -->
        <div class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <h1 class="text-2xl font-bold text-gray-900">
                            💰 Cost Alerts Dashboard
                        </h1>
                        <span class="px-3 py-1 text-xs font-medium bg-gray-100 text-gray-800 rounded-full">
                            <span x-text="totalAlerts"></span> Aktive Alerts
                        </span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button @click="refreshData()" 
                                class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                            🔄 Refresh
                        </button>
                        <a href="/telescope" class="text-sm bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700">
                            Zurück zu Telescope
                        </a>
                        <a href="/admin" class="text-sm bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                            Admin Panel
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
                    <a href="/telescope/logs" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">
                        Logs
                    </a>
                    <a href="/telescope/queries" class="text-gray-500 hover:text-gray-700 pb-3 px-1 font-medium text-sm">
                        Queries
                    </a>
                    <a href="/telescope/cost-alerts" class="text-blue-600 border-b-2 border-blue-600 pb-3 px-1 font-medium text-sm">
                        Cost Alerts
                    </a>
                </nav>
            </div>
        </div>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Active Alerts -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Aktive Alerts</p>
                            <p class="text-2xl font-bold text-red-600">{{ $alerts->count() }}</p>
                        </div>
                        <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">🚨</span>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        @if($alerts->count() > 0)
                            <span class="text-red-600">⚠️ Aktion erforderlich</span>
                        @else
                            <span class="text-green-600">✅ Alles OK</span>
                        @endif
                    </div>
                </div>

                <!-- Total Budget -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Gesamt Budget</p>
                            <p class="text-2xl font-bold text-gray-900">€{{ number_format($totalBudget, 2) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">💶</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $budgetUsage }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $budgetUsage }}% verwendet</p>
                    </div>
                </div>

                <!-- Current Spend -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Aktuelle Ausgaben</p>
                            <p class="text-2xl font-bold text-gray-900">€{{ number_format($currentSpend, 2) }}</p>
                        </div>
                        <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">📊</span>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        Diesen Monat
                    </div>
                </div>

                <!-- Companies Monitored -->
                <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Überwachte Firmen</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $companies->count() }}</p>
                        </div>
                        <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                            <span class="text-2xl">🏢</span>
                        </div>
                    </div>
                    <div class="mt-4 text-sm text-gray-500">
                        Mit Prepaid Balance
                    </div>
                </div>
            </div>

            <!-- Alert History Table -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-900">Alert Historie</h2>
                        <div class="flex space-x-2">
                            <select class="px-3 py-1 border border-gray-300 rounded-lg text-sm">
                                <option>Alle Typen</option>
                                <option>Low Balance</option>
                                <option>Zero Balance</option>
                                <option>Usage Spike</option>
                                <option>Budget Exceeded</option>
                            </select>
                            <select class="px-3 py-1 border border-gray-300 rounded-lg text-sm">
                                <option>Letzte 24h</option>
                                <option>Letzte Woche</option>
                                <option>Letzter Monat</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Zeit
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Firma
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Alert Typ
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Details
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Aktionen
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($alerts as $alert)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $alert->created_at->format('d.m.Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $alert->company->name ?? 'N/A' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $typeColors = [
                                            'low_balance' => 'bg-yellow-100 text-yellow-800',
                                            'zero_balance' => 'bg-red-100 text-red-800',
                                            'usage_spike' => 'bg-blue-100 text-blue-800',
                                            'budget_exceeded' => 'bg-orange-100 text-orange-800',
                                            'cost_anomaly' => 'bg-purple-100 text-purple-800',
                                        ];
                                        $color = $typeColors[$alert->alert_type] ?? 'bg-gray-100 text-gray-800';
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $color }}">
                                        {{ str_replace('_', ' ', ucfirst($alert->alert_type)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    @if($alert->data)
                                        Balance: €{{ number_format($alert->data['balance'] ?? 0, 2) }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($alert->acknowledged_at)
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                            Acknowledged
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                            Pending
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if(!$alert->acknowledged_at)
                                        <button @click="acknowledgeAlert({{ $alert->id }})"
                                                class="text-blue-600 hover:text-blue-800 font-medium">
                                            Acknowledge
                                        </button>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    <div>
                                        <span class="text-4xl">🎉</span>
                                        <p class="mt-2">Keine aktiven Alerts - Alles läuft gut!</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Companies Overview -->
            <div class="mt-8 bg-white rounded-xl shadow-sm border border-gray-100">
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-900">Firmen-Übersicht</h2>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($companies as $company)
                        <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between">
                                <h3 class="font-medium text-gray-900">{{ $company->name }}</h3>
                                @if($company->prepaidBalance)
                                    @php
                                        $balance = $company->prepaidBalance->getEffectiveBalance();
                                        $statusColor = $balance > 50 ? 'text-green-600' : ($balance > 20 ? 'text-yellow-600' : 'text-red-600');
                                    @endphp
                                    <span class="{{ $statusColor }} font-bold">
                                        €{{ number_format($balance, 2) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </div>
                            @if($company->prepaidBalance)
                                <div class="mt-2">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        @php
                                            $percentage = min(100, ($balance / 100) * 100);
                                            $barColor = $balance > 50 ? 'bg-green-500' : ($balance > 20 ? 'bg-yellow-500' : 'bg-red-500');
                                        @endphp
                                        <div class="{{ $barColor }} h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                                    </div>
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function costAlertsData() {
        return {
            totalAlerts: {{ $alerts->count() }},
            
            refreshData() {
                window.location.reload();
            },
            
            async acknowledgeAlert(alertId) {
                try {
                    const response = await fetch(`/telescope/cost-alerts/api/alerts/${alertId}/acknowledge`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        }
                    });
                    
                    if (response.ok) {
                        window.location.reload();
                    }
                } catch (error) {
                    console.error('Error acknowledging alert:', error);
                }
            }
        }
    }
    
    // Auto-refresh every 60 seconds
    setInterval(() => {
        window.location.reload();
    }, 60000);
    </script>
</body>
</html>