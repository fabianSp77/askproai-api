<div class="space-y-4">
    @php
        // Get stats from component if not directly passed
        $stats = $stats ?? ($component->usageStats ?? null);
    @endphp

    {{-- Debug Info --}}
    @if(config('app.debug'))
        <div class="bg-gray-100 p-2 rounded text-xs">
            <strong>Debug:</strong> Stats loaded: {{ isset($stats) ? 'Yes' : 'No' }}
            @if(isset($stats))
                | Has pricing: {{ $stats['has_pricing'] ?? 'N/A' }}
                | Total calls: {{ $stats['usage']['total_calls'] ?? 'N/A' }}
            @endif
        </div>
    @endif

    @if(isset($stats['error']))
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
            <p class="font-semibold">Fehler</p>
            <p>{{ $stats['error'] }}</p>
        </div>
    @elseif(isset($stats['has_pricing']) && !$stats['has_pricing'])
        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded">
            <p class="font-semibold">Kein Preismodell</p>
            <p>Für das ausgewählte Unternehmen wurde kein aktives Preismodell gefunden.</p>
        </div>
    @else
        <!-- Pricing Model Info -->
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="font-semibold text-gray-900 mb-2">Aktives Preismodell</h3>
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Grundgebühr:</span>
                    <span class="font-medium">€ {{ number_format($stats['pricing']['monthly_base_fee'] ?? 0, 2, ',', '.') }}/Monat</span>
                </div>
                <div>
                    <span class="text-gray-600">Inklusiv-Minuten:</span>
                    <span class="font-medium">{{ number_format($stats['pricing']['included_minutes'] ?? 0, 0, ',', '.') }} Min.</span>
                </div>
                <div>
                    <span class="text-gray-600">Minutenpreis:</span>
                    <span class="font-medium">€ {{ number_format($stats['pricing']['price_per_minute'] ?? 0, 4, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- Usage Statistics -->
        <div class="bg-blue-50 p-4 rounded-lg">
            <h3 class="font-semibold text-gray-900 mb-2">Nutzungsstatistik</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Anzahl Anrufe:</span>
                    <span class="font-medium">{{ number_format($stats['usage']['total_calls'] ?? 0, 0, ',', '.') }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Gesamtminuten:</span>
                    <span class="font-medium">{{ number_format($stats['usage']['total_minutes'] ?? 0, 1, ',', '.') }} Min.</span>
                </div>
                <div>
                    <span class="text-gray-600">Inklusiv genutzt:</span>
                    <span class="font-medium">{{ number_format($stats['usage']['included_minutes_used'] ?? 0, 1, ',', '.') }} Min.</span>
                </div>
                <div>
                    <span class="text-gray-600">Berechenbare Minuten:</span>
                    <span class="font-medium text-red-600">{{ number_format($stats['usage']['billable_minutes'] ?? 0, 1, ',', '.') }} Min.</span>
                </div>
            </div>
        </div>

        <!-- Cost Preview -->
        <div class="bg-green-50 p-4 rounded-lg">
            <h3 class="font-semibold text-gray-900 mb-2">Kostenvorschau</h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-gray-600">Grundgebühr:</span>
                    <span class="font-medium">€ {{ number_format($stats['pricing']['monthly_base_fee'] ?? 0, 2, ',', '.') }}</span>
                </div>
                @if(($stats['usage']['billable_minutes'] ?? 0) > 0)
                <div class="flex justify-between">
                    <span class="text-gray-600">Zusätzliche Minuten ({{ number_format($stats['usage']['billable_minutes'] ?? 0, 1, ',', '.') }} × € {{ number_format($stats['pricing']['price_per_minute'] ?? 0, 4, ',', '.') }}):</span>
                    <span class="font-medium">€ {{ number_format(($stats['usage']['billable_minutes'] ?? 0) * ($stats['pricing']['price_per_minute'] ?? 0), 2, ',', '.') }}</span>
                </div>
                @endif
                <div class="border-t pt-2 flex justify-between font-semibold">
                    <span>Zwischensumme (netto):</span>
                    <span>€ {{ number_format(
                        ($stats['pricing']['monthly_base_fee'] ?? 0) + 
                        (($stats['usage']['billable_minutes'] ?? 0) * ($stats['pricing']['price_per_minute'] ?? 0)), 
                        2, ',', '.'
                    ) }}</span>
                </div>
            </div>
        </div>

        <!-- Daily Breakdown -->
        @if(isset($stats['calls_by_day']) && count($stats['calls_by_day']) > 0)
        <div class="bg-gray-50 p-4 rounded-lg">
            <h3 class="font-semibold text-gray-900 mb-2">Tägliche Aufschlüsselung</h3>
            <div class="max-h-48 overflow-y-auto">
                <table class="min-w-full text-sm">
                    <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left">Datum</th>
                            <th class="px-3 py-2 text-right">Anrufe</th>
                            <th class="px-3 py-2 text-right">Minuten</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($stats['calls_by_day'] as $date => $dayStats)
                        <tr>
                            <td class="px-3 py-2">{{ \Carbon\Carbon::parse($date)->format('d.m.Y') }}</td>
                            <td class="px-3 py-2 text-right">{{ $dayStats['count'] }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($dayStats['minutes'], 1, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        <div class="bg-amber-50 border border-amber-200 p-3 rounded text-sm">
            <p class="text-amber-800">
                <strong>Hinweis:</strong> Dies ist eine Vorschau basierend auf den aktuellen Daten. 
                Die finale Rechnung kann abweichen, falls noch Anrufe für diesen Zeitraum verarbeitet werden.
            </p>
        </div>
    @endif
</div>