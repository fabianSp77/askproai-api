@php
    // Get the actual appointment record
    $appointment = $getRecord();

    // Load service relationship if not already loaded
    if (!$appointment->relationLoaded('service')) {
        $appointment->load('service');
    }

    $service = $appointment->service;
    $isComposite = $service && $service->composite === true;

    if (!$isComposite) {
        echo '<div class="text-sm text-gray-500 dark:text-gray-400 italic">Dieser Service ist kein Compound-Service</div>';
        return;
    }

    $phases = $appointment->phases()
        ->where('staff_required', true)
        ->orderBy('sequence_order')
        ->get();

    if ($phases->isEmpty()) {
        echo '<div class="text-sm text-gray-500 dark:text-gray-400 italic">Keine Segmente gefunden</div>';
        return;
    }

    // Calculate total duration and statistics
    $totalDuration = $phases->sum('duration_minutes');
    $syncedCount = $phases->where('calcom_sync_status', 'synced')->count();
    $failedCount = $phases->where('calcom_sync_status', 'failed')->count();
    $pendingCount = $phases->where('calcom_sync_status', 'pending')->count();
@endphp

<div class="space-y-6">
    {{-- Enhanced Summary Card with Inline Statistics --}}
    <div class="p-5 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950 rounded-xl border border-blue-200 dark:border-blue-800 shadow-sm">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <div class="text-lg font-semibold text-blue-900 dark:text-blue-100 flex items-center gap-2 mb-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    {{ $service->name }}
                </div>
                <div class="text-sm text-blue-700 dark:text-blue-300 flex items-center gap-3 flex-wrap">
                    <span>{{ $phases->count() }} Segmente</span>
                    <span class="text-blue-400 dark:text-blue-500">•</span>
                    {{-- Inline sync summary --}}
                    <span class="inline-flex items-center gap-3">
                        <span class="text-green-600 dark:text-green-400 font-medium">✅ {{ $syncedCount }}</span>
                        @if($failedCount > 0)
                            <span class="text-red-600 dark:text-red-400 font-bold animate-pulse">❌ {{ $failedCount }}</span>
                        @endif
                        @if($pendingCount > 0)
                            <span class="text-yellow-600 dark:text-yellow-400 font-medium">⏳ {{ $pendingCount }}</span>
                        @endif
                    </span>
                </div>
            </div>
            <div class="text-right">
                <div class="text-3xl font-bold text-blue-900 dark:text-blue-100">
                    {{ $totalDuration }}
                </div>
                <div class="text-xs text-blue-600 dark:text-blue-400 uppercase tracking-wide font-medium">
                    Minuten gesamt
                </div>
            </div>
        </div>
    </div>

    {{-- Timeline --}}
    <div class="relative" role="list" aria-label="Appointment Phases Timeline">
        @foreach($phases as $index => $phase)
            @php
                $isLast = $index === ($phases->count() - 1);

                // Sync status styling
                $syncConfig = match($phase->calcom_sync_status) {
                    'synced' => [
                        'icon' => '✅',
                        'label' => 'Synchronisiert',
                        'color' => 'text-green-600 dark:text-green-400',
                        'bg' => 'bg-green-50 dark:bg-green-950',
                        'bgStatus' => 'bg-green-100 dark:bg-green-900',
                        'border' => 'border-green-300 dark:border-green-700',
                        'dotBg' => 'bg-green-500',
                        'dotBorder' => 'border-green-300 dark:border-green-600',
                        'lineBg' => 'bg-green-300 dark:bg-green-700'
                    ],
                    'failed' => [
                        'icon' => '❌',
                        'label' => 'Fehler',
                        'color' => 'text-red-600 dark:text-red-400',
                        'bg' => 'bg-red-50 dark:bg-red-950',
                        'bgStatus' => 'bg-red-100 dark:bg-red-900',
                        'border' => 'border-red-300 dark:border-red-700',
                        'dotBg' => 'bg-red-500',
                        'dotBorder' => 'border-red-300 dark:border-red-600',
                        'lineBg' => 'bg-red-300 dark:bg-red-700'
                    ],
                    'pending' => [
                        'icon' => '⏳',
                        'label' => 'Ausstehend',
                        'color' => 'text-yellow-600 dark:text-yellow-400',
                        'bg' => 'bg-yellow-50 dark:bg-yellow-950',
                        'bgStatus' => 'bg-yellow-100 dark:bg-yellow-900',
                        'border' => 'border-yellow-300 dark:border-yellow-700',
                        'dotBg' => 'bg-yellow-500',
                        'dotBorder' => 'border-yellow-300 dark:border-yellow-600',
                        'lineBg' => 'bg-yellow-300 dark:bg-yellow-700'
                    ],
                    default => [
                        'icon' => '❓',
                        'label' => 'Unbekannt',
                        'color' => 'text-gray-500 dark:text-gray-400',
                        'bg' => 'bg-gray-50 dark:bg-gray-900',
                        'bgStatus' => 'bg-gray-100 dark:bg-gray-800',
                        'border' => 'border-gray-300 dark:border-gray-700',
                        'dotBg' => 'bg-gray-400',
                        'dotBorder' => 'border-gray-300 dark:border-gray-600',
                        'lineBg' => 'bg-gray-300 dark:bg-gray-700'
                    ]
                };

                // Use start_time field for phases
                $startTime = $phase->start_time ? \Carbon\Carbon::parse($phase->start_time) : null;
                $endTime = $phase->end_time ? \Carbon\Carbon::parse($phase->end_time) : ($startTime ? $startTime->copy()->addMinutes($phase->duration_minutes) : null);
            @endphp

            <div class="flex gap-3 md:gap-4 pb-6 last:pb-0 relative group" role="listitem" tabindex="0">
                {{-- Timeline Line --}}
                @if(!$isLast)
                    <div class="absolute left-4 md:left-5 top-12 bottom-0 w-0.5 {{ $syncConfig['lineBg'] }} opacity-30 group-hover:opacity-50 transition-opacity"></div>
                @endif

                {{-- Timeline Dot --}}
                <div class="relative z-10 flex-shrink-0">
                    <div class="{{ $syncConfig['dotBg'] }} w-8 h-8 md:w-10 md:h-10 rounded-full flex items-center justify-center border-3 md:border-4 border-white dark:border-gray-950 shadow-lg ring-2 {{ $syncConfig['dotBorder'] }} transition-all group-hover:scale-110">
                        <span class="text-white font-bold text-xs md:text-sm">{{ $index + 1 }}</span>
                    </div>
                </div>

                {{-- Phase Card --}}
                <div class="flex-1 {{ $syncConfig['bg'] }} border-2 {{ $syncConfig['border'] }} rounded-xl shadow-sm hover:shadow-md transition-all overflow-hidden">
                    {{-- Sync Status Bar --}}
                    <div class="px-4 md:px-5 py-3 {{ $syncConfig['bgStatus'] }} border-b-2 {{ $syncConfig['border'] }} flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        {{-- Status Indicator --}}
                        <div class="flex items-center gap-3">
                            <div class="relative">
                                <span class="text-2xl">{{ $syncConfig['icon'] }}</span>
                                @if($phase->calcom_sync_status === 'pending')
                                    <span class="absolute -top-1 -right-1 flex h-3 w-3">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full {{ $syncConfig['dotBg'] }} opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-3 w-3 {{ $syncConfig['dotBg'] }}"></span>
                                    </span>
                                @endif
                            </div>

                            <div>
                                <div class="text-sm font-bold {{ $syncConfig['color'] }} uppercase tracking-wider">
                                    {{ $syncConfig['label'] }}
                                </div>
                                @if($phase->updated_at)
                                    <div class="text-xs text-gray-600 dark:text-gray-400 mt-0.5">
                                        {{ \Carbon\Carbon::parse($phase->updated_at)->diffForHumans() }}
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Quick Actions --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            @if($phase->calcom_booking_uid)
                                <a href="https://app.cal.com/bookings/upcoming"
                                   target="_blank"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm"
                                   title="In Cal.com öffnen">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                    </svg>
                                    Cal.com
                                </a>

                                <button type="button"
                                        onclick="navigator.clipboard.writeText('{{ $phase->calcom_booking_uid }}'); const el = this.querySelector('.copy-text'); const orig = el.textContent; el.textContent = 'Kopiert!'; setTimeout(() => el.textContent = orig, 2000);"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors shadow-sm"
                                        title="Booking UID kopieren">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <span class="copy-text">UID kopieren</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Main Content --}}
                    <div class="p-4 md:p-5">
                        {{-- PRIMARY: Segment Name + Duration --}}
                        <div class="mb-3">
                            <div class="flex items-baseline gap-3 flex-wrap mb-2">
                                <h3 class="text-lg md:text-xl font-bold text-gray-900 dark:text-white">
                                    {{ $phase->segment_name }}
                                </h3>
                                <span class="px-3 py-1 text-sm font-bold bg-gray-900 dark:bg-white text-white dark:text-gray-900 rounded-full shadow-sm">
                                    {{ $phase->duration_minutes }} Min
                                </span>
                            </div>
                        </div>

                        {{-- SECONDARY: Time + Date --}}
                        @if($startTime)
                            <div class="text-base font-semibold text-gray-700 dark:text-gray-200 mb-4 flex flex-wrap items-center gap-2">
                                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <time datetime="{{ $startTime->toIso8601String() }}">
                                    {{ $startTime->format('H:i') }} - {{ $endTime->format('H:i') }} Uhr
                                </time>
                                <span class="hidden sm:inline text-gray-400 dark:text-gray-500">•</span>
                                <span class="text-gray-600 dark:text-gray-400">{{ $startTime->format('d.m.Y') }}</span>
                            </div>
                        @endif

                        {{-- TERTIARY: Technical Details (Collapsed) --}}
                        @if($phase->calcom_booking_id || $phase->calcom_booking_uid)
                            <details class="group/details mt-4" @if($phase->calcom_sync_status === 'failed') open @endif>
                                <summary class="cursor-pointer text-xs font-semibold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 flex items-center gap-2 select-none py-2 px-3 bg-gray-100 dark:bg-gray-800 rounded-lg transition-colors">
                                    <svg class="w-4 h-4 transition-transform group-open/details:rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                    Cal.com Technische Details
                                </summary>
                                <div class="mt-3 pl-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 md:gap-3">
                                        @if($phase->calcom_booking_id)
                                            <div class="bg-white dark:bg-gray-900 rounded-lg p-2.5 md:p-3 border border-gray-200 dark:border-gray-700">
                                                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-1">Booking ID</div>
                                                <div class="text-xs md:text-sm font-mono font-semibold text-gray-900 dark:text-gray-100 break-all">
                                                    {{ $phase->calcom_booking_id }}
                                                </div>
                                            </div>
                                        @endif

                                        @if($phase->calcom_booking_uid)
                                            <div class="bg-white dark:bg-gray-900 rounded-lg p-2.5 md:p-3 border border-gray-200 dark:border-gray-700">
                                                <div class="text-xs text-gray-500 dark:text-gray-400 font-medium mb-1">Booking UID</div>
                                                <div class="text-xs md:text-sm font-mono font-semibold text-gray-900 dark:text-gray-100 break-all">
                                                    {{ $phase->calcom_booking_uid }}
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </details>
                        @endif

                        {{-- Enhanced Error Display --}}
                        @if($phase->sync_error_message)
                            <div class="mt-4 bg-red-50 dark:bg-red-950 border-2 border-red-300 dark:border-red-700 rounded-lg p-4">
                                <div class="flex items-start gap-3 mb-3">
                                    <div class="flex-shrink-0 w-10 h-10 bg-red-100 dark:bg-red-900 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                        </svg>
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-bold text-red-800 dark:text-red-200 mb-1">
                                            Synchronisationsfehler
                                        </h4>
                                        <p class="text-xs text-red-700 dark:text-red-300 leading-relaxed break-words">
                                            {{ $phase->sync_error_message }}
                                        </p>
                                    </div>
                                </div>

                                {{-- Context-aware suggestions --}}
                                <div class="mt-4 pt-4 border-t border-red-200 dark:border-red-800">
                                    <div class="text-xs font-bold text-red-800 dark:text-red-200 mb-2 uppercase tracking-wide">
                                        💡 Lösungsvorschläge:
                                    </div>
                                    <ul class="space-y-2 text-xs text-red-700 dark:text-red-300">
                                        @if(str_contains($phase->sync_error_message, 'Time slot not available') || str_contains($phase->sync_error_message, 'time slot'))
                                            <li class="flex items-start gap-2">
                                                <span class="text-red-500 mt-0.5 font-bold">→</span>
                                                <span>Der Zeitslot ist bereits belegt. Wählen Sie einen anderen Zeitpunkt.</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <span class="text-red-500 mt-0.5 font-bold">→</span>
                                                <span>Prüfen Sie die Verfügbarkeit in Cal.com direkt.</span>
                                            </li>
                                        @elseif(str_contains($phase->sync_error_message, 'Event type') || str_contains($phase->sync_error_message, 'event type') || str_contains($phase->sync_error_message, 'managed event type'))
                                            <li class="flex items-start gap-2">
                                                <span class="text-red-500 mt-0.5 font-bold">→</span>
                                                <span>Event Type ist ein "managed event type" und kann nicht direkt gebucht werden.</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <span class="text-red-500 mt-0.5 font-bold">→</span>
                                                <span>Es muss ein "child event type" verwendet werden.</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <span class="text-red-500 mt-0.5 font-bold">→</span>
                                                <span>Kontaktieren Sie den Administrator zur Event Type Konfiguration.</span>
                                            </li>
                                        @else
                                            <li class="flex items-start gap-2">
                                                <span class="text-red-500 mt-0.5 font-bold">→</span>
                                                <span>Verwenden Sie die "UID kopieren" Funktion um Details zu speichern.</span>
                                            </li>
                                            <li class="flex items-start gap-2">
                                                <span class="text-red-500 mt-0.5 font-bold">→</span>
                                                <span>Bei wiederholten Fehlern kontaktieren Sie den Support.</span>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </div>
                        @endif

                        {{-- Pending Sync Progress --}}
                        @if($phase->calcom_sync_status === 'pending' && !$phase->sync_error_message)
                            <div class="mt-4 bg-yellow-50 dark:bg-yellow-950 border-2 border-yellow-300 dark:border-yellow-700 rounded-lg p-4">
                                <div class="flex items-center gap-3">
                                    <svg class="animate-spin h-5 w-5 text-yellow-600 dark:text-yellow-400 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>

                                    <div class="flex-1">
                                        <div class="text-sm font-bold text-yellow-800 dark:text-yellow-200 mb-1">
                                            Synchronisation läuft...
                                        </div>
                                        <div class="text-xs text-yellow-700 dark:text-yellow-300">
                                            Dieser Termin wird mit Cal.com synchronisiert. Dies kann einige Sekunden dauern.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
