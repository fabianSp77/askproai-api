@php
    $record = $getRecord();
    $status = $record->status ?? 'unknown';

    // Check if call is LIVE
    $isLive = in_array($status, ['ongoing', 'in_progress', 'active', 'ringing']);

    // Determine display badge: Show LIVE if active, otherwise show BOOKING STATUS
    if ($isLive) {
        $displayText = 'LIVE';
        $displayColor = 'bg-red-100 text-red-800 animate-pulse';
    } else {
        // Booking Status Logic (more relevant than "Completed")
        if ($record->appointment && $record->appointment->starts_at) {
            $displayText = 'Gebucht';
            $displayColor = 'bg-green-100 text-green-800';
        } elseif ($record->appointmentWishes()->where('status', 'pending')->exists()) {
            $displayText = 'Wunsch';
            $displayColor = 'bg-yellow-100 text-yellow-800';
        } else {
            $displayText = 'Offen';
            $displayColor = 'bg-red-100 text-red-800';
        }
    }

    // Direction icon
    $directionIcon = match($record->direction) {
        'inbound' => '↓',
        'outbound' => '↑',
        default => ''
    };

    $directionColor = match($record->direction) {
        'inbound' => 'text-green-600',
        'outbound' => 'text-blue-600',
        default => 'text-gray-600'
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

<div class="space-y-1" title="{{ $tooltipText }}">
    <!-- Zeile 1: Direction Icon + Status/Booking Badge (single badge, more relevant) -->
    <div class="flex items-center gap-1">
        <span class="text-lg {{ $directionColor }}">{{ $directionIcon }}</span>
        <span class="px-2 py-1 rounded-full text-sm font-medium {{ $displayColor }}">
            {{ $displayText }}
        </span>
    </div>

    <!-- Datum Zeile 1 -->
    @if($record->created_at)
        <div class="text-xs text-gray-600">
            {{ $record->created_at->locale('de')->isoFormat('DD MMMM HH:mm') }} Uhr
        </div>
    @endif

    <!-- Dauer Zeile 3 -->
    @if($record->created_at)
        <div class="text-xs text-gray-600">
            @if($record->duration_sec)
                @php
                    $mins = intval($record->duration_sec / 60);
                    $secs = $record->duration_sec % 60;
                @endphp
                {{ sprintf('%d:%02d', $mins, $secs) }}
            @else
                --:--
            @endif
        </div>
    @endif
</div>
