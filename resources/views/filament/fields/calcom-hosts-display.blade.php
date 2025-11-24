@php
    $resolver = new \App\Services\CalcomServiceHostsResolver();
    $summary = $resolver->getHostsSummary($record);
@endphp

<div class="space-y-4">
    <!-- Summary stats -->
    @if ($summary['total_hosts'] > 0)
        <div class="grid grid-cols-4 gap-3">
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-center dark:border-blue-900 dark:bg-blue-900/30">
                <div class="text-2xl font-bold text-blue-900 dark:text-blue-200">
                    {{ $summary['total_hosts'] }}
                </div>
                <div class="text-xs font-medium text-blue-700 dark:text-blue-300">
                    Gesamt Hosts
                </div>
            </div>

            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-center dark:border-green-900 dark:bg-green-900/30">
                <div class="text-2xl font-bold text-green-900 dark:text-green-200">
                    {{ $summary['mapped_hosts'] }}
                </div>
                <div class="text-xs font-medium text-green-700 dark:text-green-300">
                    ✅ Verbunden
                </div>
            </div>

            <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 text-center dark:border-yellow-900 dark:bg-yellow-900/30">
                <div class="text-2xl font-bold text-yellow-900 dark:text-yellow-200">
                    {{ $summary['unmapped_hosts'] }}
                </div>
                <div class="text-xs font-medium text-yellow-700 dark:text-yellow-300">
                    ⚠️ Neu
                </div>
            </div>

            <div class="rounded-lg border border-purple-200 bg-purple-50 p-3 text-center dark:border-purple-900 dark:bg-purple-900/30">
                <div class="text-2xl font-bold text-purple-900 dark:text-purple-200">
                    {{ $summary['available_for_service'] }}
                </div>
                <div class="text-xs font-medium text-purple-700 dark:text-purple-300">
                    📋 Für Service
                </div>
            </div>
        </div>

        <!-- Hosts list -->
        <div class="space-y-3 border-t border-gray-200 pt-4 dark:border-gray-700">
            @foreach ($summary['hosts'] as $host)
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-4">
                        <!-- Host info -->
                        <div class="flex items-start gap-3 flex-1">
                            @if ($host['calcom_avatar'])
                                <img src="{{ $host['calcom_avatar'] }}" alt="{{ $host['calcom_name'] }}"
                                    class="h-12 w-12 rounded-full flex-shrink-0">
                            @else
                                <div
                                    class="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-blue-400 to-blue-600 flex-shrink-0">
                                    <span class="text-sm font-bold text-white">
                                        {{ substr($host['calcom_name'], 0, 1) }}
                                    </span>
                                </div>
                            @endif

                            <div class="flex-1">
                                <h4 class="font-semibold text-gray-900 dark:text-white">
                                    {{ $host['calcom_name'] }}
                                </h4>
                                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                    📧 {{ $host['calcom_email'] ?? 'Keine Email' }}
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500">
                                    👤 @{{ $host['calcom_username'] ?? 'N/A' }} • {{ $host['calcom_role'] }}
                                </p>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="flex flex-col gap-2 text-right">
                            @if ($host['is_mapped'])
                                <span
                                    class="inline-block rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-900 dark:bg-green-900 dark:text-green-100">
                                    ✅ Verbunden
                                </span>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    → <strong>{{ $host['staff_name'] }}</strong>
                                </p>
                                <p class="text-xs text-gray-500 dark:text-gray-500">
                                    Zutrauen: {{ $host['mapping_confidence'] }}%
                                </p>
                            @else
                                <span
                                    class="inline-block rounded-full bg-yellow-100 px-3 py-1 text-xs font-semibold text-yellow-900 dark:bg-yellow-900 dark:text-yellow-100">
                                    ⚠️ Nicht verbunden
                                </span>
                                <p class="text-xs text-gray-600 dark:text-gray-400">
                                    Bitte zuordnen
                                </p>
                            @endif
                        </div>
                    </div>

                    <!-- Available services -->
                    @if ($host['is_mapped'] && $host['available_services']->count() > 0)
                        <div class="mt-3 border-t border-gray-200 pt-3 dark:border-gray-700">
                            <p class="text-xs font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                📋 Verfügbar für:
                            </p>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($host['available_services'] as $svc)
                                    <span
                                        class="inline-block rounded bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-900 dark:bg-blue-900/30 dark:text-blue-200">
                                        {{ $svc['name'] }} <span class="text-gray-600 dark:text-gray-400">({{ $svc['duration'] }}min)</span>
                                    </span>
                                @endforeach
                            </div>

                            <!-- Highlight current service -->
                            @if ($host['is_available_for_service'])
                                <p class="mt-2 text-xs text-green-600 dark:text-green-400 font-semibold">
                                    ✨ <strong>VERFÜGBAR für diese Dienstleistung</strong>
                                </p>
                            @else
                                <p class="mt-2 text-xs text-yellow-600 dark:text-yellow-400 font-semibold">
                                    ⚠️ <strong>NICHT für diese Dienstleistung konfiguriert</strong>
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @else
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 text-center dark:border-blue-900 dark:bg-blue-900/30">
            <p class="text-sm text-blue-900 dark:text-blue-200">
                @if (!$record->calcom_event_type_id)
                    ℹ️ <strong>Diese Dienstleistung ist nicht mit Cal.com verbunden.</strong>
                    <br>
                    <span class="text-xs">Bitte tragen Sie eine Cal.com Event Type ID ein, um Mitarbeiter anzuzeigen.</span>
                @else
                    ℹ️ <strong>Keine Mitarbeiter in Cal.com für diese Dienstleistung.</strong>
                @endif
            </p>
        </div>
    @endif

    <!-- Info Box -->
    <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-900 dark:bg-blue-900/30">
        <p class="text-xs text-blue-900 dark:text-blue-200 leading-relaxed">
            💡 <strong>Wie das funktioniert:</strong><br>
            Diese Mitarbeiter werden automatisch von Cal.com abgerufen. Sie sind die "wahrheitliche Quelle". Der
            Administrator muss diese zu lokalen Mitarbeitern verbinden (via CalcomHostMapping), damit die
            automatische Buchung und Verfügbarkeitsprüfung funktioniert.
        </p>
    </div>
</div>
