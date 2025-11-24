@php
    $record = $getRecord();
    $appointments = $record->appointments ?? collect();
@endphp

@if($appointments->isEmpty())
    <div class="text-sm text-gray-500 dark:text-gray-400 italic">
        Keine Termine für diesen Anruf vorhanden
    </div>
@else
    @foreach($appointments as $appointment)
        @php
            $isComposite = $appointment->service && $appointment->service->composite;
            $isCancelled = $appointment->status === 'cancelled';

            // Status badge configuration
            $statusConfig = match($appointment->status) {
                'confirmed', 'scheduled', 'booked' => ['label' => 'Bestätigt', 'color' => 'success', 'icon' => '✓'],
                'pending' => ['label' => 'Ausstehend', 'color' => 'warning', 'icon' => '⏳'],
                'cancelled' => ['label' => 'Storniert', 'color' => 'danger', 'icon' => '🚫'],
                'completed' => ['label' => 'Abgeschlossen', 'color' => 'gray', 'icon' => '✓'],
                default => ['label' => $appointment->status, 'color' => 'gray', 'icon' => '?']
            };

            $bgColor = $isCancelled ? 'bg-red-50 dark:bg-red-900/10 border-red-200 dark:border-red-800' : 'bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700';
        @endphp

        <div class="mb-4 p-4 border rounded-lg {{ $bgColor }}">
            {{-- Header: Service Name + Status Badge --}}
            <div class="flex items-start justify-between mb-3">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        @if($isComposite)
                            <span class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                📦 {{ $appointment->service->name }}
                            </span>
                            @php
                                $phaseCount = $appointment->phases()->where('staff_required', true)->count();
                            @endphp
                            <span class="text-xs px-2 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full font-medium">
                                {{ $phaseCount }} Segmente
                            </span>
                        @else
                            <span class="text-base font-semibold text-gray-900 dark:text-gray-100">
                                {{ $appointment->service?->name ?? 'Service' }}
                            </span>
                        @endif
                    </div>

                    {{-- Staff and Time --}}
                    <div class="text-sm text-gray-600 dark:text-gray-400 flex items-center gap-3">
                        <span class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            {{ $appointment->staff?->name ?? 'Nicht zugewiesen' }}
                        </span>

                        @if($appointment->starts_at)
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ \Carbon\Carbon::parse($appointment->starts_at)->format('d.m.Y H:i') }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Status Badge --}}
                <span class="px-3 py-1 text-xs font-medium rounded-full
                    @if($statusConfig['color'] === 'success') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @elseif($statusConfig['color'] === 'warning') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                    @elseif($statusConfig['color'] === 'danger') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                    @else bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200
                    @endif">
                    {{ $statusConfig['icon'] }} {{ $statusConfig['label'] }}
                </span>
            </div>

            {{-- Composite: Show Segments --}}
            @if($isComposite)
                @php
                    $phases = $appointment->phases()
                        ->where('staff_required', true)
                        ->orderBy('sequence_order')
                        ->get();
                @endphp

                @if($phases->isNotEmpty())
                    <div class="mt-4 space-y-2">
                        <div class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wide mb-3">
                            📋 Segmente ({{ $phases->count() }})
                        </div>

                        @foreach($phases as $phase)
                            @php
                                $syncIcon = match($phase->calcom_sync_status) {
                                    'synced' => '✅',
                                    'failed' => '❌',
                                    'pending' => '⏳',
                                    default => '❓'
                                };

                                // Status-based background colors with FULL opacity for readability
                                $segmentBg = match($phase->calcom_sync_status) {
                                    'synced' => 'bg-green-50 dark:bg-green-950',
                                    'failed' => 'bg-red-50 dark:bg-red-950',
                                    'pending' => 'bg-yellow-50 dark:bg-yellow-950',
                                    default => 'bg-gray-100 dark:bg-gray-800'
                                };

                                $segmentBorder = match($phase->calcom_sync_status) {
                                    'synced' => 'border-green-200 dark:border-green-700',
                                    'failed' => 'border-red-200 dark:border-red-700',
                                    'pending' => 'border-yellow-200 dark:border-yellow-700',
                                    default => 'border-gray-300 dark:border-gray-600'
                                };
                            @endphp

                            <div class="flex items-center justify-between gap-4 py-3 px-4 {{ $segmentBg }} rounded-lg border-2 {{ $segmentBorder }} hover:shadow-sm transition-shadow">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <span class="text-xl flex-shrink-0">{{ $syncIcon }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-semibold text-gray-900 dark:text-white mb-0.5">
                                            {{ $phase->segment_name }}
                                        </div>
                                        @if($phase->starts_at)
                                            <div class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                                {{ \Carbon\Carbon::parse($phase->starts_at)->format('H:i') }} -
                                                {{ \Carbon\Carbon::parse($phase->starts_at)->addMinutes($phase->duration_minutes)->format('H:i') }} Uhr
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <div class="text-right flex-shrink-0">
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ $phase->duration_minutes }} min
                                    </div>
                                    @if($phase->calcom_event_id)
                                        <div class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                            #{{ $phase->calcom_event_id }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            @else
                {{-- Standard Appointment: Show basic info --}}
                <div class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                    @if($appointment->duration_minutes)
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Dauer: {{ $appointment->duration_minutes }} Minuten</span>
                        </div>
                    @endif

                    @if($appointment->calcom_event_id)
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                Cal.com Event ID: {{ $appointment->calcom_event_id }}
                            </span>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Cal.com Sync Status --}}
            @if($appointment->calcom_sync_status)
                <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-2 text-xs">
                        <span class="text-gray-500 dark:text-gray-400">Cal.com Status:</span>
                        @php
                            $syncStatusConfig = match($appointment->calcom_sync_status) {
                                'synced' => ['label' => 'Synchronisiert', 'color' => 'text-green-600 dark:text-green-400', 'icon' => '✅'],
                                'failed' => ['label' => 'Fehler', 'color' => 'text-red-600 dark:text-red-400', 'icon' => '❌'],
                                'pending' => ['label' => 'Ausstehend', 'color' => 'text-yellow-600 dark:text-yellow-400', 'icon' => '⏳'],
                                default => ['label' => $appointment->calcom_sync_status, 'color' => 'text-gray-500 dark:text-gray-400', 'icon' => '❓']
                            };
                        @endphp
                        <span class="{{ $syncStatusConfig['color'] }} font-medium">
                            {{ $syncStatusConfig['icon'] }} {{ $syncStatusConfig['label'] }}
                        </span>

                        @if($appointment->calcom_sync_error)
                            <span class="text-red-600 dark:text-red-400" title="{{ $appointment->calcom_sync_error }}">
                                (Fehlerdetails vorhanden)
                            </span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- Link to Appointment Detail --}}
            <div class="mt-3 pt-3 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('filament.admin.resources.appointments.view', ['record' => $appointment->id]) }}"
                   class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 hover:underline font-medium inline-flex items-center gap-1"
                   target="_blank">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                    </svg>
                    Termin #{{ $appointment->id }} öffnen
                </a>
            </div>
        </div>
    @endforeach
@endif
