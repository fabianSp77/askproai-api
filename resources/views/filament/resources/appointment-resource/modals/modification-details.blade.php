<div class="space-y-4">
    {{-- Modification Header --}}
    <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                @switch($modification->modification_type)
                    @case('create')
                        ✅ Termin erstellt
                        @break
                    @case('reschedule')
                        🔄 Termin umgebucht
                        @break
                    @case('cancel')
                        ❌ Termin storniert
                        @break
                    @default
                        {{ ucfirst($modification->modification_type) }}
                @endswitch
            </h3>
            <p class="text-sm text-gray-700 dark:text-gray-300 mt-1">
                {{ $modification->created_at->format('d.m.Y H:i:s') }} Uhr
                <span class="text-gray-600 dark:text-gray-300" aria-hidden="true">•</span>
                {{ $modification->created_at->diffForHumans() }}
            </p>
        </div>

        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium
            @if($modification->within_policy) bg-success-100 text-success-700 dark:bg-success-700 dark:text-success-100
            @else bg-warning-100 text-warning-700 dark:bg-warning-700 dark:text-warning-100
            @endif">
            {{ $modification->within_policy ? '✅ Richtlinienkonform' : '⚠️ Außerhalb Richtlinien' }}
        </span>
    </div>

    {{-- Details Grid --}}
    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Who --}}
        <div class="border-l-4 border-primary-500 pl-4">
            <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Durchgeführt von</dt>
            <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                @switch($modification->modified_by_type)
                    @case('System')
                        🤖 System
                        @break
                    @case('Customer')
                        👤 Kunde
                        @break
                    @case('Admin')
                        👨‍💼 Administrator
                        @break
                    @case('Staff')
                        👥 Mitarbeiter
                        @break
                    @default
                        {{ $modification->modified_by_type ?? 'Unbekannt' }}
                @endswitch
            </dd>
        </div>

        {{-- Fee --}}
        @if($modification->fee_charged > 0)
            <div class="border-l-4 border-danger-500 pl-4">
                <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Gebühr</dt>
                <dd class="mt-1 text-sm font-semibold text-danger-600 dark:text-danger-400">
                    {{ number_format($modification->fee_charged, 2) }} €
                </dd>
            </div>
        @endif

        {{-- Reason --}}
        @if($modification->reason)
            <div class="border-l-4 border-info-500 pl-4 col-span-full">
                <dt class="text-sm font-medium text-gray-700 dark:text-gray-300">Grund</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                    {{ $modification->reason }}
                </dd>
            </div>
        @endif
    </dl>

    {{-- Policy Rule Breakdown (Enhanced Section - 2025-10-11) --}}
    @if($modification->within_policy !== null)
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">📋 Richtlinienprüfung</h4>

            <div class="space-y-3">
                {{-- Summary Badge --}}
                <div class="flex items-center gap-3 p-3 rounded-lg
                    {{ $modification->within_policy ? 'bg-success-50 dark:bg-success-900/20' : 'bg-warning-50 dark:bg-warning-900/20' }}">
                    <span class="text-2xl">
                        {{ $modification->within_policy ? '✅' : '⚠️' }}
                    </span>
                    <div>
                        <div class="font-semibold text-gray-900 dark:text-white">
                            {{ $modification->within_policy ? 'Alle Regeln erfüllt' : 'Regelverstoß festgestellt' }}
                        </div>
                        @php
                            $metadata = $modification->metadata ?? [];
                            $rulesChecked = 0;
                            $rulesPassed = 0;

                            // Count rules
                            if (isset($metadata['hours_notice'])) {
                                $rulesChecked++;
                                if ($metadata['hours_notice'] >= ($metadata['policy_required'] ?? 0)) $rulesPassed++;
                            }
                            if (isset($metadata['quota_used'])) {
                                $rulesChecked++;
                                if (($metadata['quota_used'] ?? 0) <= ($metadata['quota_max'] ?? PHP_INT_MAX)) $rulesPassed++;
                            }
                            $rulesChecked++; // Fee always checked
                            if ($modification->fee_charged == 0) $rulesPassed++;
                        @endphp
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            {{ $rulesPassed }} von {{ $rulesChecked }} Regeln eingehalten
                        </div>
                    </div>
                </div>

                {{-- Detailed Rule List --}}
                <div class="space-y-2">
                    {{-- Rule 1: Hours Notice --}}
                    @if(isset($metadata['hours_notice']) && isset($metadata['policy_required']))
                        @php
                            $hours = round($metadata['hours_notice'], 1);
                            $required = $metadata['policy_required'];
                            $passed = $hours >= $required;
                            $diff = round(abs($hours - $required), 1);
                        @endphp
                        <div class="flex items-start gap-3 p-2 rounded
                            {{ $passed ? 'bg-success-50 dark:bg-success-900/10' : 'bg-danger-50 dark:bg-danger-900/10' }}">
                            <span class="text-lg">{{ $passed ? '✅' : '❌' }}</span>
                            <div class="flex-1">
                                <div class="font-medium text-sm text-gray-900 dark:text-white">Vorwarnzeit</div>
                                <div class="text-xs text-gray-700 dark:text-gray-300">
                                    Gegeben: <strong>{{ $hours }} Stunden</strong> | Erforderlich: {{ $required }} Stunden
                                    @if($passed)
                                        <br><span class="text-success-600">+{{ $diff }}h Puffer</span>
                                    @else
                                        <br><span class="text-danger-600">-{{ $diff }}h zu kurz</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Rule 2: Monthly Quota --}}
                    @if(isset($metadata['quota_used']) && isset($metadata['quota_max']))
                        @php
                            $used = $metadata['quota_used'];
                            $max = $metadata['quota_max'];
                            $passed = $used <= $max;
                            $remaining = $max - $used;
                        @endphp
                        <div class="flex items-start gap-3 p-2 rounded
                            {{ $passed ? 'bg-success-50 dark:bg-success-900/10' : 'bg-danger-50 dark:bg-danger-900/10' }}">
                            <span class="text-lg">{{ $passed ? '✅' : '❌' }}</span>
                            <div class="flex-1">
                                <div class="font-medium text-sm text-gray-900 dark:text-white">Monatliches Limit</div>
                                <div class="text-xs text-gray-700 dark:text-gray-300">
                                    Verwendet: <strong>{{ $used }} von {{ $max }}</strong>
                                    @if($passed)
                                        <br><span class="text-success-600">{{ $remaining }} verbleibend</span>
                                    @else
                                        <br><span class="text-danger-600">{{ abs($remaining) }} überschritten</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Rule 3: Fee --}}
                    <div class="flex items-start gap-3 p-2 rounded bg-gray-50 dark:bg-gray-900/10">
                        <span class="text-lg">{{ $modification->fee_charged == 0 ? '✅' : '⚠️' }}</span>
                        <div class="flex-1">
                            <div class="font-medium text-sm text-gray-900 dark:text-white">Gebührenregelung</div>
                            <div class="text-xs text-gray-700 dark:text-gray-300">
                                @if($modification->fee_charged == 0)
                                    <strong>Gebührenfrei</strong> - Keine Kosten
                                @else
                                    Gebühr: <strong class="text-danger-600">{{ number_format($modification->fee_charged, 2) }} €</strong>
                                    <br><span class="text-gray-600 dark:text-gray-400">
                                        {{ isset($metadata['hours_notice']) && $metadata['hours_notice'] < 24 ? 'Kurzfristige Änderung' : 'Gemäß Richtlinie' }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Metadata Details (if available) --}}
    @if($modification->metadata && !empty($modification->metadata))
        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">📋 Weitere Informationen</h4>

            <div class="space-y-2">
                {{-- Reschedule specific metadata --}}
                @if($modification->modification_type === 'reschedule')
                    @if(isset($modification->metadata['original_time']) && isset($modification->metadata['new_time']))
                        <div class="flex items-center gap-2 text-sm">
                            <span class="text-gray-700 dark:text-gray-300">Zeitänderung:</span>
                            <span class="font-medium">
                                {{ \Carbon\Carbon::parse($modification->metadata['original_time'])->format('H:i') }} Uhr
                            </span>
                            <x-heroicon-o-arrow-right class="w-4 h-4 text-gray-600 dark:text-gray-300" aria-hidden="true"/>
                            <span class="font-medium text-primary-600">
                                {{ \Carbon\Carbon::parse($modification->metadata['new_time'])->format('H:i') }} Uhr
                            </span>
                        </div>
                    @endif

                    @if(isset($modification->metadata['calcom_synced']))
                        <div class="flex items-center gap-2 text-sm">
                            <span class="text-gray-700 dark:text-gray-300">Kalendersystem-Synchronisation:</span>
                            <span class="{{ $modification->metadata['calcom_synced'] ? 'text-success-600' : 'text-warning-600' }}">
                                {{ $modification->metadata['calcom_synced'] ? '✅ Erfolgreich' : '⚠️ Fehlgeschlagen' }}
                            </span>
                        </div>
                    @endif
                @endif

                {{-- Cancel specific metadata --}}
                @if($modification->modification_type === 'cancel' && isset($modification->metadata['hours_notice']))
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-gray-700 dark:text-gray-300">Vorwarnzeit:</span>
                        <span class="font-medium">
                            {{ round($modification->metadata['hours_notice'], 1) }} Stunden
                        </span>
                        @if(isset($modification->metadata['policy_required']))
                            <span class="text-gray-600 dark:text-gray-300">
                                (erforderlich: {{ $modification->metadata['policy_required'] }}h)
                            </span>
                        @endif
                    </div>
                @endif

                {{-- Call Link --}}
                @if(isset($modification->metadata['call_id']))
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-gray-700 dark:text-gray-300">Verknüpfter Anruf:</span>
                        <a href="{{ route('filament.admin.resources.calls.view', ['record' => $modification->metadata['call_id']]) }}"
                           class="text-primary-600 hover:underline font-medium"
                           target="_blank">
                            📞 Call #{{ $modification->metadata['call_id'] }}
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- Raw metadata (collapsible for technical details) --}}
        <details class="mt-4">
            <summary class="cursor-pointer text-sm text-gray-700 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100">
                Technische Details anzeigen
            </summary>
            <pre class="mt-2 p-3 bg-gray-900 text-gray-100 rounded text-xs overflow-auto max-h-64">{{ json_encode($modification->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
        </details>
    @endif

    {{-- Appointment reference --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <a href="{{ route('filament.admin.resources.appointments.view', ['record' => $modification->appointment_id]) }}"
           class="inline-flex items-center gap-2 text-sm text-primary-600 hover:underline"
           target="_blank">
            <x-heroicon-o-calendar class="w-4 h-4"/>
            Zum Termin #{{ $modification->appointment_id }}
        </a>
    </div>
</div>
