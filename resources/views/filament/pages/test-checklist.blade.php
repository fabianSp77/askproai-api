<x-filament-panels::page>
    <div class="space-y-6">
        <!-- System Status Overview -->
        <div class="fi-section">
            <div class="fi-section-header">
                <h2 class="fi-section-heading">System Status</h2>
                <div class="flex gap-2">
                    <x-filament::button wire:click="refreshData" size="sm">
                        🔄 Aktualisieren
                    </x-filament::button>
                    <a href="/test-checklist" target="_blank">
                        <x-filament::button size="sm" color="gray">
                            🔗 Vollansicht öffnen
                        </x-filament::button>
                    </a>
                </div>
            </div>

            <div class="fi-section-content">
                <!-- Overall Status -->
                <div class="p-4 rounded-lg mb-4 {{ $systemStatus['overall']['status'] === 'operational' ? 'bg-success-50' : ($systemStatus['overall']['status'] === 'warning' ? 'bg-warning-50' : 'bg-danger-50') }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-lg font-semibold">{{ $systemStatus['overall']['message'] }}</p>
                            <p class="text-sm text-gray-600">Letzte Prüfung: {{ $systemStatus['timestamp'] }}</p>
                        </div>
                        @if($systemStatus['overall']['ready_for_test'])
                            <span class="px-4 py-2 bg-success-100 text-success-800 rounded-full font-medium">
                                ✅ Bereit für Testanruf
                            </span>
                        @else
                            <span class="px-4 py-2 bg-danger-100 text-danger-800 rounded-full font-medium">
                                ❌ Nicht bereit
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Component Status Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($systemStatus['components'] as $name => $component)
                    <div class="fi-card p-4">
                        <div class="flex items-center">
                            <span class="text-2xl mr-3">{{ $component['icon'] }}</span>
                            <div>
                                <p class="font-semibold">{{ ucfirst(str_replace('_', ' ', $name)) }}</p>
                                <p class="text-sm text-gray-600">{{ $component['message'] }}</p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Phone Numbers -->
        <div class="fi-section">
            <div class="fi-section-header">
                <h2 class="fi-section-heading">📱 Test Telefonnummern</h2>
            </div>
            <div class="fi-section-content">
                @if(count($phoneNumbers) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($phoneNumbers as $phone)
                        <div class="fi-card p-4 {{ $phone['is_primary'] ? 'ring-2 ring-primary-500' : '' }}">
                            <p class="text-lg font-semibold">{{ $phone['formatted'] }}</p>
                            <p class="text-sm text-gray-600">{{ $phone['company'] }} - {{ $phone['branch'] }}</p>
                            <p class="text-sm text-gray-500 mt-1">Agent: {{ $phone['agent'] }}</p>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 text-gray-500">
                        <p>⚠️ Keine Telefonnummern mit Retell Agent konfiguriert</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Test Scenarios -->
        <div class="fi-section">
            <div class="fi-section-header">
                <h2 class="fi-section-heading">🧪 Test Szenarien</h2>
            </div>
            <div class="fi-section-content space-y-4">
                @foreach($testScenarios as $scenario)
                <div class="fi-card p-4">
                    <h3 class="font-semibold">{{ $scenario['title'] }}</h3>
                    <p class="text-sm text-gray-600 mb-2">{{ $scenario['description'] }}</p>
                    <div class="space-y-1">
                        @foreach($scenario['test_phrases'] as $phrase)
                        <div class="flex items-center gap-2 p-2 bg-gray-50 rounded text-sm">
                            <span class="font-mono">"{{ $phrase }}"</span>
                            <button onclick="navigator.clipboard.writeText('{{ $phrase }}')" class="ml-auto text-gray-500 hover:text-gray-700">
                                📋
                            </button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Recent Calls -->
        <div class="fi-section">
            <div class="fi-section-header">
                <h2 class="fi-section-heading">📞 Letzte Anrufe</h2>
            </div>
            <div class="fi-section-content">
                <div class="fi-table-container">
                    <table class="fi-table">
                        <thead>
                            <tr>
                                <th>Von</th>
                                <th>An</th>
                                <th>Status</th>
                                <th>Dauer</th>
                                <th>Kunde</th>
                                <th>Zeitpunkt</th>
                                <th>Termin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentCalls as $call)
                            <tr>
                                <td>{{ $call['from'] }}</td>
                                <td>{{ $call['to'] }}</td>
                                <td>
                                    <span class="px-2 py-1 text-xs rounded-full {{ $call['status'] === 'completed' ? 'bg-success-100 text-success-700' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $call['status'] }}
                                    </span>
                                </td>
                                <td>{{ $call['duration'] }}</td>
                                <td>{{ $call['customer'] }}</td>
                                <td>{{ $call['created'] }}</td>
                                <td>
                                    @if($call['appointment_made'])
                                        <span class="text-success-600">✅</span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-gray-500">
                                    Keine Anrufe gefunden
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="fi-section">
            <div class="fi-section-header">
                <h2 class="fi-section-heading">🔗 Webhook URLs</h2>
            </div>
            <div class="fi-section-content space-y-2">
                <div class="p-3 bg-gray-50 rounded font-mono text-sm">
                    <span class="text-gray-600">Retell:</span>
                    <span class="ml-2 text-primary-600">{{ url('/webhooks/retell') }}</span>
                </div>
                <div class="p-3 bg-gray-50 rounded font-mono text-sm">
                    <span class="text-gray-600">Cal.com:</span>
                    <span class="ml-2 text-primary-600">{{ url('/webhooks/calcom') }}</span>
                </div>
                <div class="p-3 bg-gray-50 rounded font-mono text-sm">
                    <span class="text-gray-600">Function:</span>
                    <span class="ml-2 text-primary-600">{{ url('/webhooks/retell/function') }}</span>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>