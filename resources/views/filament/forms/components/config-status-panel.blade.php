{{-- Service Output Configuration - Status Panel --}}
{{-- Zeigt den aktuellen Konfigurations-Status auf einen Blick --}}

@php
    $outputType = $getState()['output_type'] ?? 'email';
    $isActive = $getState()['is_active'] ?? true;

    // E-Mail Status
    $emailEnabled = in_array($outputType, ['email', 'hybrid']);
    $recipients = $getState()['email_recipients'] ?? [];
    $recipientCount = is_array($recipients) ? count($recipients) : 0;
    $includeSummary = $getState()['include_summary'] ?? true;
    $includeTranscript = $getState()['include_transcript'] ?? true;
    $audioOption = $getState()['email_audio_option'] ?? 'none';

    // Webhook Status
    $webhookEnabled = in_array($outputType, ['webhook', 'hybrid']);
    $webhookUrl = $getState()['webhook_url'] ?? '';
    $webhookActive = $getState()['webhook_enabled'] ?? true;
    $webhookTranscript = $getState()['webhook_include_transcript'] ?? false;

    // Timing Status
    $waitForEnrichment = $getState()['wait_for_enrichment'] ?? false;
    $enrichmentTimeout = $getState()['enrichment_timeout_seconds'] ?? 180;

    // Status-Berechnung
    $emailStatus = 'inactive';
    $emailMessage = 'Nicht aktiviert';
    if ($emailEnabled) {
        if ($recipientCount > 0) {
            $emailStatus = 'success';
            $features = [];
            if ($recipientCount > 0) $features[] = $recipientCount . ' Empfanger';
            if ($includeSummary) $features[] = 'Zusammenfassung';
            if ($includeTranscript) $features[] = 'Transkript';
            if ($audioOption !== 'none') $features[] = 'Audio';
            $emailMessage = implode(' · ', $features);
        } else {
            $emailStatus = 'warning';
            $emailMessage = 'Keine Empfanger konfiguriert';
        }
    }

    $webhookStatus = 'inactive';
    $webhookMessage = 'Nicht aktiviert';
    if ($webhookEnabled) {
        if (!empty($webhookUrl)) {
            if ($webhookActive) {
                $webhookStatus = 'success';
                $features = ['URL gesetzt', 'Aktiv'];
                if ($webhookTranscript) $features[] = 'Transkript';
                $webhookMessage = implode(' · ', $features);
            } else {
                $webhookStatus = 'warning';
                $webhookMessage = 'URL gesetzt · Pausiert';
            }
        } else {
            $webhookStatus = 'danger';
            $webhookMessage = 'URL fehlt!';
        }
    }

    $timingStatus = $waitForEnrichment ? 'success' : 'info';
    $timingMessage = $waitForEnrichment
        ? "Wartet auf Enrichment ({$enrichmentTimeout}s Timeout)"
        : 'Sofortige Zustellung';

    $statusColors = [
        'success' => 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-700 dark:text-green-300',
        'warning' => 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300',
        'danger' => 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-700 dark:text-red-300',
        'info' => 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300',
        'inactive' => 'bg-gray-50 dark:bg-gray-800/50 border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400',
    ];

    $statusIcons = [
        'success' => '<svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
        'warning' => '<svg class="w-5 h-5 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
        'danger' => '<svg class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
        'info' => '<svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>',
        'inactive' => '<svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>',
    ];
@endphp

<div class="rounded-xl border {{ $isActive ? 'border-gray-200 dark:border-gray-700' : 'border-red-200 dark:border-red-800 bg-red-50/50 dark:bg-red-900/10' }} overflow-hidden">
    {{-- Header --}}
    <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100 flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                </svg>
                Konfigurations-Status
            </h3>
            @if(!$isActive)
                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                    Deaktiviert
                </span>
            @endif
        </div>
    </div>

    {{-- Status Items --}}
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        {{-- E-Mail Status --}}
        <div class="px-4 py-3 flex items-center gap-4">
            <div class="flex-shrink-0">
                {!! $statusIcons[$emailStatus] !!}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">E-Mail</span>
                    @if($emailEnabled)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $outputType === 'hybrid' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' }}">
                            {{ $outputType === 'hybrid' ? 'Hybrid' : 'Aktiv' }}
                        </span>
                    @endif
                </div>
                <p class="text-sm {{ $emailStatus === 'inactive' ? 'text-gray-400 dark:text-gray-500' : 'text-gray-600 dark:text-gray-300' }}">
                    {{ $emailMessage }}
                </p>
            </div>
        </div>

        {{-- Webhook Status --}}
        <div class="px-4 py-3 flex items-center gap-4">
            <div class="flex-shrink-0">
                {!! $statusIcons[$webhookStatus] !!}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Webhook</span>
                    @if($webhookEnabled)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $outputType === 'hybrid' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-200' }}">
                            {{ $outputType === 'hybrid' ? 'Hybrid' : 'Aktiv' }}
                        </span>
                    @endif
                </div>
                <p class="text-sm {{ $webhookStatus === 'inactive' ? 'text-gray-400 dark:text-gray-500' : 'text-gray-600 dark:text-gray-300' }}">
                    {{ $webhookMessage }}
                </p>
            </div>
        </div>

        {{-- Timing Status --}}
        <div class="px-4 py-3 flex items-center gap-4">
            <div class="flex-shrink-0">
                {!! $statusIcons[$timingStatus] !!}
            </div>
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">Timing</span>
                    @if($waitForEnrichment)
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Delivery Gate
                        </span>
                    @endif
                </div>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    {{ $timingMessage }}
                </p>
            </div>
        </div>
    </div>

    {{-- Hilfe-Text --}}
    <div class="px-4 py-2 bg-gray-50 dark:bg-gray-800/30 border-t border-gray-200 dark:border-gray-700">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Konfiguriere die Details in den Tabs unten. Anderungen werden nach dem Speichern aktiv.
        </p>
    </div>
</div>
