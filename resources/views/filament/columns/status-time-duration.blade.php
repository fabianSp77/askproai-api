@php
    $record = $getRecord();
    $status = $record->status ?? 'unknown';

    // Check if call is LIVE
    $isLive = in_array($status, ['ongoing', 'in_progress', 'active', 'ringing']);

    // Determine display badge: Show LIVE if active, otherwise show BOOKING STATUS
    // Using inline styles directly since Tailwind might not work in ViewColumn context
    if ($isLive) {
        $displayText = 'LIVE';
        $bgColor = '#fee2e2';  // red-100
        $textColor = '#991b1b'; // red-800
        $isPulse = true;
    } else {
        $isPulse = false;
        // Booking Status Logic (more relevant than "Completed")
        if ($record->appointment && $record->appointment->starts_at) {
            $displayText = 'Gebucht';
            $bgColor = '#dcfce7';  // green-100
            $textColor = '#15803d'; // green-800
        } elseif ($record->appointmentWishes()->where('status', 'pending')->exists()) {
            $displayText = 'Wunsch';
            $bgColor = '#fef3c7';  // yellow-100
            $textColor = '#b45309'; // yellow-800
        } else {
            $displayText = 'Offen';
            $bgColor = '#fee2e2';  // red-100
            $textColor = '#991b1b'; // red-800
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

    $tooltipLines = [];
    if($record->created_at) {
        $tooltipLines[] = match($record->direction) {
            'inbound' => 'Eingehend',
            'outbound' => 'Ausgehend',
            default => ''
        };

        $tooltipLines[] = $record->created_at->format('d.m.Y H:i:s');

        if($record->duration_sec) {
            $mins = intval($record->duration_sec / 60);
            $secs = $record->duration_sec % 60;
            $tooltipLines[] = sprintf('%d:%02d Min', $mins, $secs);
        }

        $tooltipLines[] = $record->created_at->diffForHumans();
    }

    $tooltipText = implode("\n", $tooltipLines);
@endphp

<div style="display: flex; flex-direction: column; gap: 0.25rem;" title="{{ $tooltipText }}">
    <!-- Zeile 1: Direction Icon + Status/Booking Badge -->
    <div style="display: flex; align-items: center; gap: 0.25rem;">
        <span style="font-size: 1.125rem; color: {{ $directionColorValue }};">{{ $directionIcon }}</span>
        <span style="padding: 0.25rem 0.5rem; border-radius: 9999px; font-size: 0.875rem; font-weight: 500; background-color: {{ $bgColor }}; color: {{ $textColor }}; {{ $isPulse ? 'animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;' : '' }}">
            {{ $displayText }}
        </span>
    </div>

    <!-- Datum Zeile 2 -->
    @if($record->created_at)
        <div style="font-size: 0.75rem; color: #4b5563;">
            {{ $record->created_at->locale('de')->isoFormat('DD MMMM HH:mm') }} Uhr
        </div>
    @endif

    <!-- Dauer Zeile 3 -->
    @if($record->created_at)
        <div style="font-size: 0.75rem; color: #4b5563;">
            @if($record->duration_sec)
                @php
                    $mins = intval($record->duration_sec / 60);
                    $secs = $record->duration_sec % 60;
                @endphp
                ⏱️ {{ sprintf('%d:%02d', $mins, $secs) }} Min
            @else
                ⏱️ --:-- Min
            @endif
        </div>
    @endif
</div>

<style>
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
</style>
