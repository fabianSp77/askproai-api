@php
    $record = $getRecord();
    $status = $record->status ?? 'unknown';

    // Check if call is LIVE (excludes 'test' status for test calls)
    $isLive = in_array($status, ['ongoing', 'in_progress', 'active', 'ringing']) && $status !== 'test';

    // Get ALL appointments (not just one) - 🆕 2025-11-24: Composite support
    $appointments = $record->appointments;
    $hasAppointments = $appointments->isNotEmpty();

    // Determine display badge: Show LIVE if active, otherwise show BOOKING STATUS
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

        // Get active and cancelled appointments
        $activeAppointments = $appointments->whereIn('status', ['scheduled', 'confirmed', 'booked', 'pending']);
        $cancelledAppointments = $appointments->where('status', 'cancelled');

        // Booking Status Logic
        // 🆕 PRIORITY 1: Check if appointment was cancelled
        if ($cancelledAppointments->isNotEmpty() && $activeAppointments->isEmpty()) {
            $displayText = 'Storniert';
            $badgeColor = 'warning';  // Filament warning = orange
            $accentColor = '#f97316'; // orange-500
        }
        // PRIORITY 2: Check if appointment is booked
        elseif ($activeAppointments->isNotEmpty()) {
            // 🆕 2025-11-24: Check if ANY appointment is composite
            $hasComposite = $activeAppointments->first(fn($appt) => $appt->service && $appt->service->composite);

            if ($hasComposite) {
                $displayText = '✅ Gebucht (Compound)';  // NEW: Indicator for composite
                $badgeColor = 'success';
                $accentColor = '#22c55e'; // green-500
            } else {
                $displayText = 'Gebucht';
                $badgeColor = 'success';  // Filament success = green
                $accentColor = '#22c55e'; // green-500
            }
        } else {
            // ⚠️ DISABLED: appointment_wishes table doesn't exist in Sept 21 backup
            // Check if there are pending wishes (wrapped in try-catch for missing table)
            $hasPendingWish = false;
            try {
                $hasPendingWish = $record->appointmentWishes()->where('status', 'pending')->exists();
            } catch (\Exception $e) {
                // Silently ignore - appointment_wishes table doesn't exist in Sept 21 backup
            }

            if ($hasPendingWish) {
                $displayText = 'Wunsch';
                $badgeColor = 'warning';  // Filament warning = yellow/orange
                $accentColor = '#f59e0b'; // amber-500
            } else {
                $displayText = 'Offen';
                $badgeColor = 'danger';  // Filament danger = red
                $accentColor = '#64748b'; // slate-500 for accent
            }
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

    // Badge tooltip - shows booking status details (🆕 2025-11-24: Enhanced for composite)
    $badgeTooltip = '';
    if ($isLive) {
        $badgeTooltip = 'Anruf läuft gerade';
    } elseif ($hasAppointments && !$isLive) {
        $tooltipLines = [];

        foreach ($activeAppointments as $appt) {
            $serviceName = $appt->service?->name ?? 'Service';
            $startTime = $appt->starts_at ? \Carbon\Carbon::parse($appt->starts_at)->format('d.m.Y H:i') : 'Zeit unbekannt';

            if ($appt->service && $appt->service->composite) {
                // 🆕 Composite appointment - show segments
                try {
                    $phaseCount = $appt->phases()->where('staff_required', true)->count();
                    $tooltipLines[] = "📦 {$serviceName} ({$phaseCount} Segmente)";
                    $tooltipLines[] = "   ⏰ {$startTime}";

                    // Show first 3 segments
                    $phases = $appt->phases()->where('staff_required', true)->orderBy('sequence_order')->limit(3)->get();
                    foreach ($phases as $phase) {
                        $tooltipLines[] = "   → {$phase->segment_name} ({$phase->duration_minutes}min)";
                    }

                    if ($phaseCount > 3) {
                        $tooltipLines[] = "   ... +" . ($phaseCount - 3) . " weitere";
                    }
                } catch (\Exception $e) {
                    // Fallback if phases loading fails
                    $tooltipLines[] = "📦 {$serviceName}";
                    $tooltipLines[] = "   ⏰ {$startTime}";
                }
            } else {
                // Simple appointment
                $tooltipLines[] = "📅 {$serviceName}";
                $tooltipLines[] = "   ⏰ {$startTime}";
            }

            $tooltipLines[] = ''; // blank line
        }

        // Show cancelled appointments
        foreach ($cancelledAppointments as $appt) {
            $serviceName = $appt->service?->name ?? 'Service';
            $tooltipLines[] = "🚫 {$serviceName} (Storniert)";
            $tooltipLines[] = '';
        }

        $badgeTooltip = implode("\n", array_filter($tooltipLines));
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
    <!-- Zeile 1: Status Badge (Filament Badge Component with Subtle Design) -->
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
