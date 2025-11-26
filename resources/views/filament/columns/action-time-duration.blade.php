@php
    $record = $getRecord();
    $status = $record->status ?? 'unknown';

    // Check if call is LIVE
    $isLive = in_array($status, ['ongoing', 'in_progress', 'active', 'ringing']);

    // 🆕 2025-11-25: Check if THIS CALL performed a reschedule
    $thisCallPerformedReschedule = false;
    $rescheduleDetails = null;
    if ($record->retell_call_id) {
        try {
            $rescheduleDetails = \App\Models\AppointmentModification::where('modification_type', 'reschedule')
                ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                ->with('appointment.service')
                ->first();
            $thisCallPerformedReschedule = $rescheduleDetails !== null;
        } catch (\Exception $e) {
            // Silently ignore
        }
    }

    // 🔧 FIX 2025-11-25: Initialize variables BEFORE if/else to prevent undefined variable errors
    // These need to be available for tooltip generation regardless of $isLive status
    $hasActiveAppointments = false;
    $hasCancelledAppointments = false;
    $performedCancellations = false;

    // Determine ACTION badge (what happened in this call)
    if ($isLive) {
        // Calculate elapsed time for LIVE calls
        $elapsedSeconds = $record->created_at ? now()->diffInSeconds($record->created_at) : 0;
        $elapsedMins = intval($elapsedSeconds / 60);
        $elapsedSecs = $elapsedSeconds % 60;

        $displayText = sprintf('LIVE %d:%02d', $elapsedMins, $elapsedSecs);
        $badgeColor = 'danger';  // Filament danger = red
        $accentColor = '#ef4444'; // red-500
        $isPulse = true;
    } else {
        $isPulse = false;

        // Check what action was performed
        $hasActiveAppointments = $record->appointments()
            ->whereIn('status', ['scheduled', 'confirmed', 'booked', 'pending'])
            ->exists();

        $hasCancelledAppointments = $record->appointments()
            ->where('status', 'cancelled')
            ->exists();

        if ($record->retell_call_id) {
            $performedCancellations = \App\Models\AppointmentModification::query()
                ->where('modification_type', 'cancel')
                ->whereJsonContains('metadata->call_id', $record->retell_call_id)
                ->exists();
        }

        // 🆕 PRIORITY 0: Check if THIS call performed a reschedule (highest priority)
        if ($thisCallPerformedReschedule) {
            $displayText = '🔄 Verschoben';
            $badgeColor = 'success';  // Green like "Gebucht" - reschedule is still a success
            $accentColor = '#f59e0b'; // amber-500 border to indicate reschedule
        }
        // Determine badge based on action
        elseif ($hasActiveAppointments) {
            // Check for composite services
            $hasComposite = $record->appointments()
                ->whereIn('status', ['scheduled', 'confirmed', 'booked', 'pending'])
                ->whereHas('service', fn($q) => $q->where('composite', true))
                ->exists();

            if ($hasComposite) {
                $displayText = '✅ Gebucht (Compound)';
            } else {
                $displayText = '✅ Gebucht';
            }
            $badgeColor = 'success';  // Filament success = green
            $accentColor = '#22c55e'; // green-500

            if ($hasCancelledAppointments) {
                $displayText .= ' (storniert)';
                $badgeColor = 'warning';  // Filament warning = orange
                $accentColor = '#f97316'; // orange-500
            }
        } elseif ($performedCancellations || $hasCancelledAppointments) {
            $displayText = 'Storniert';
            $badgeColor = 'warning';  // Filament warning = orange
            $accentColor = '#f97316'; // orange-500
        } else {
            $displayText = 'Offen';
            $badgeColor = 'danger';  // Filament danger = red
            $accentColor = '#64748b'; // slate-500 for accent
        }
    }

    // Direction icon
    $directionIcon = match($record->direction) {
        'inbound' => '↓',
        'outbound' => '↑',
        default => ''
    };

    $directionColorValue = match($record->direction) {
        'inbound' => '#16a34a',    // green-600
        'outbound' => '#2563eb',   // blue-600
        default => '#4b5563'       // gray-600
    };

    // Badge tooltip - shows appointment details
    $badgeTooltip = '';
    if ($thisCallPerformedReschedule && $rescheduleDetails) {
        // 🆕 2025-11-25: Show reschedule details
        $metadata = $rescheduleDetails->metadata ?? [];
        $lines = ['🔄 Termin verschoben:'];

        if (isset($metadata['original_time'])) {
            $lines[] = '❌ Von: ' . \Carbon\Carbon::parse($metadata['original_time'])->format('d.m.Y H:i');
        }
        if (isset($metadata['new_time'])) {
            $lines[] = '✅ Auf: ' . \Carbon\Carbon::parse($metadata['new_time'])->format('d.m.Y H:i');
        }

        $badgeTooltip = implode("\n", $lines);
    } elseif ($hasActiveAppointments) {
        $appointments = $record->appointments()->whereIn('status', ['scheduled', 'confirmed', 'booked', 'pending'])->get();
        $lines = ['Gebuchte Termine:'];
        foreach ($appointments as $appt) {
            if ($appt->starts_at) {
                $lines[] = '• ' . \Carbon\Carbon::parse($appt->starts_at)->format('d.m.Y H:i') . ' Uhr';
            }
        }
        $badgeTooltip = implode("\n", $lines);
    } elseif ($performedCancellations || $hasCancelledAppointments) {
        $badgeTooltip = 'Termin wurde storniert';
    } elseif ($isLive) {
        $badgeTooltip = 'Anruf läuft gerade';
    } else {
        $badgeTooltip = 'Kein Termin gebucht';
    }

    // Date tooltip - shows full datetime
    $dateTooltip = '';
    if ($record->created_at) {
        $directionText = match($record->direction) {
            'inbound' => '📞 Eingehend',
            'outbound' => '📲 Ausgehend',
            default => ''
        };
        $dateTooltip = $directionText . "\n" . $record->created_at->format('d.m.Y H:i:s') . "\n" . $record->created_at->diffForHumans();
    }

    // Duration tooltip - shows quality info
    $durationTooltip = '';
    if ($record->duration_sec) {
        $mins = intval($record->duration_sec / 60);
        $secs = $record->duration_sec % 60;
        $durationTooltip = sprintf('Gesprächsdauer: %d:%02d Min', $mins, $secs);

        if ($record->duration_sec < 120) {
            $durationTooltip .= "\nKurzes Gespräch (< 2 Min)";
        } elseif ($record->duration_sec > 600) {
            $durationTooltip .= "\nAusführliches Gespräch (> 10 Min)";
        } else {
            $durationTooltip .= "\nNormales Gespräch (2-10 Min)";
        }
    } else {
        $durationTooltip = 'Noch keine Dauer erfasst';
    }
@endphp

<div style="display: flex; flex-direction: column; gap: 0.25rem;">
    <!-- Zeile 1: Action Badge (Filament Badge Component with Subtle Design) -->
    <div style="display: flex; align-items: center;" title="{{ $badgeTooltip }}">
        <x-filament::badge
            :color="$badgeColor"
            class="ereignis-badge {{ $isPulse ? 'ereignis-badge-pulse' : '' }}"
            :style="'border-left: 3px solid ' . $accentColor . '; transition: border-left-width 0.2s ease; cursor: pointer;'"
            tabindex="0"
            onmouseover="this.style.borderLeftWidth='5px'"
            onmouseout="this.style.borderLeftWidth='3px'"
        >
            {{ $displayText }}
        </x-filament::badge>
    </div>

    <!-- Datum Zeile 2 (Extended Relative dates with weekday names) -->
    @if($record->created_at)
        <div style="font-size: 0.75rem; color: #4b5563; cursor: help;" title="{{ $dateTooltip }}">
            @php
                $daysDiff = now()->diffInDays($record->created_at);
                $dateDisplay = match(true) {
                    $record->created_at->isToday() => 'Heute ' . $record->created_at->format('H:i'),
                    $record->created_at->isYesterday() => 'Gestern ' . $record->created_at->format('H:i'),
                    // Show weekday name for dates within past 7 days
                    $daysDiff <= 7 => $record->created_at->locale('de')->isoFormat('dd') . ' ' . $record->created_at->format('H:i'),
                    // Default: short format
                    default => $record->created_at->format('d. M H:i')
                };
            @endphp
            {{ $dateDisplay }}
        </div>
    @endif

    <!-- Dauer Zeile 3 (Color-coded by length) -->
    @if($record->created_at)
        @php
            // Determine duration color based on call length
            $durationColor = '#9ca3af'; // gray-400 default
            if ($record->duration_sec) {
                $mins = intval($record->duration_sec / 60);
                $secs = $record->duration_sec % 60;

                // Color-code: Short=red, Medium=amber, Long=green
                if ($record->duration_sec < 120) {
                    $durationColor = '#dc2626'; // red-600 (< 2 min - likely unsuccessful)
                } elseif ($record->duration_sec < 600) {
                    $durationColor = '#f59e0b'; // amber-500 (2-10 min - conversation)
                } else {
                    $durationColor = '#16a34a'; // green-600 (> 10 min - thorough)
                }
            }
        @endphp
        <div style="font-size: 0.75rem; color: {{ $durationColor }}; font-weight: 500; cursor: help;" title="{{ $durationTooltip }}">
            @if($record->duration_sec)
                {{ sprintf('%d:%02d', $mins, $secs) }}
            @else
                --:--
            @endif
        </div>
    @endif
</div>

<style>
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }

    .ereignis-badge-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }

    .ereignis-badge:focus-visible {
        outline: 2px solid #3b82f6;
        outline-offset: 2px;
        border-left-width: 5px;
    }
</style>
