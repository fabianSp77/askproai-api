<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    <!-- Header with stats -->
    <div class="mb-6 flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                📅 Cal.com Mitarbeiter
            </h3>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Automatisch aus Cal.com abgerufen für: <strong>{{ $service->name }}</strong>
            </p>
        </div>

        <!-- Stats badges -->
        <div class="flex gap-2">
            <div class="rounded-lg bg-blue-100 px-3 py-1 text-sm font-medium text-blue-900 dark:bg-blue-900 dark:text-blue-100">
                {{ $summary['total_hosts'] }} Gesamt
            </div>
            <div class="rounded-lg bg-green-100 px-3 py-1 text-sm font-medium text-green-900 dark:bg-green-900 dark:text-green-100">
                ✅ {{ $summary['mapped_hosts'] }} Verbunden
            </div>
            <div class="rounded-lg bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-900 dark:bg-yellow-900 dark:text-yellow-100">
                ⚠️ {{ $summary['unmapped_hosts'] }} Neu
            </div>
        </div>
    </div>

    <!-- Host list -->
    @if ($summary['total_hosts'] > 0)
        <div class="space-y-3">
            @foreach ($summary['hosts'] as $host)
                <div class="flex flex-col gap-3 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                    <!-- Host info -->
                    <div class="flex items-start justify-between">
                        <div class="flex items-center gap-3">
                            @if ($host['calcom_avatar'])
                                <img src="{{ $host['calcom_avatar'] }}" alt="{{ $host['calcom_name'] }}"
                                    class="h-10 w-10 rounded-full">
                            @else
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-blue-600">
                                    <span class="text-sm font-semibold text-white">
                                        {{ substr($host['calcom_name'], 0, 1) }}
                                    </span>
                                </div>
                            @endif

                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                    {{ $host['calcom_name'] }}
                                </h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    {{ $host['calcom_email'] ?? 'Keine Email' }} •
                                    <span class="italic">{{ $host['calcom_role'] }}</span>
                                </p>
                            </div>
                        </div>

                        <!-- Mapping status -->
                        <div class="flex flex-col items-end gap-1">
                            @if ($host['is_mapped'])
                                <span
                                    class="inline-block rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-900 dark:bg-green-900 dark:text-green-100">
                                    ✅ Verbunden zu: {{ $host['staff_name'] }}
                                </span>
                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                    Zutrauen: {{ $host['mapping_confidence'] }}%
                                </span>
                            @else
                                <span
                                    class="inline-block rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-900 dark:bg-yellow-900 dark:text-yellow-100">
                                    ⚠️ Nicht verbunden
                                </span>
                                <span class="text-xs text-gray-600 dark:text-gray-400">
                                    Bitte manuell zuordnen
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Available services -->
                    @if ($host['is_mapped'] && $host['available_services']->count() > 0)
                        <div class="mt-2 border-t border-gray-200 pt-3 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300">
                                📋 Verfügbar für folgende Dienstleistungen:
                            </p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($host['available_services'] as $svc)
                                    <span
                                        class="inline-block rounded bg-blue-50 px-2 py-1 text-xs font-medium text-blue-900 dark:bg-blue-900/30 dark:text-blue-200">
                                        {{ $svc['name'] }} ({{ $svc['duration'] }}min)
                                    </span>
                                @endforeach
                            </div>

                            <!-- Highlight current service -->
                            @if ($host['is_available_for_service'])
                                <p class="mt-2 text-xs text-green-600 dark:text-green-400">
                                    ✨ <strong>Verfügbar für diese Dienstleistung!</strong>
                                </p>
                            @else
                                <p class="mt-2 text-xs text-yellow-600 dark:text-yellow-400">
                                    ⚠️ <strong>NICHT für diese Dienstleistung konfiguriert</strong> - bitte
                                    hinzufügen
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Auto-sync hint -->
        <div class="mt-4 rounded-lg bg-blue-50 p-3 dark:bg-blue-900/30">
            <p class="text-xs text-blue-900 dark:text-blue-200">
                💡 <strong>Hinweis:</strong> Diese Mitarbeiter werden automatisch von Cal.com abgerufen und sind die
                <strong>wahrheitliche Quelle</strong>. Sie müssen diese zu lokalen Mitarbeitern verbinden, damit die
                automatische Buchung funktioniert.
            </p>
        </div>
    @else
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-6 text-center dark:border-gray-700 dark:bg-gray-800">
            <p class="text-gray-600 dark:text-gray-400">
                @if (!$service->calcom_event_type_id)
                    ⚠️ Diese Dienstleistung ist nicht mit Cal.com verbunden
                @else
                    ℹ️ Keine Mitarbeiter in Cal.com für diese Dienstleistung konfiguriert
                @endif
            </p>
        </div>
    @endif
</div>
