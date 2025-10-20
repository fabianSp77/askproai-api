@php
    $record = $getRecord();
    $status = $record->status ?? 'unknown';

    $statusText = match($status) {
        'ongoing', 'in_progress', 'active', 'ringing' => 'LIVE',
        'completed' => 'Completed',
        'missed' => 'Missed',
        'failed' => 'Failed',
        'no_answer' => 'No Answer',
        'busy' => 'Busy',
        'analyzed' => 'Analyzed',
        'call_analyzed' => 'Analyzed',
        default => ucfirst($status ?? 'Unknown')
    };

    $badgeColor = match($status) {
        'ongoing', 'in_progress', 'active', 'ringing' => 'bg-red-100 text-red-800',
        'completed' => 'bg-green-100 text-green-800',
        'missed', 'busy' => 'bg-yellow-100 text-yellow-800',
        'failed', 'no_answer' => 'bg-red-100 text-red-800',
        default => 'bg-gray-100 text-gray-800'
    };

    $isLive = in_array($status, ['ongoing', 'in_progress', 'active', 'ringing']);

    // Booking Status Logic
    $bookingStatus = null;
    $bookingColor = null;
    if ($record->appointment && $record->appointment->starts_at) {
        $bookingStatus = 'Gebucht';
        $bookingColor = 'bg-green-100 text-green-800';
    } elseif ($record->appointmentWishes()->where('status', 'pending')->exists()) {
        $bookingStatus = 'Wunsch';
        $bookingColor = 'bg-yellow-100 text-yellow-800';
    } else {
        $bookingStatus = 'Offen';
        $bookingColor = 'bg-red-100 text-red-800';
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
    <!-- Zeile 1: Status Badge + Booking Status Badge -->
    <div class="flex items-center gap-1 flex-wrap">
        <span class="text-lg {{ $directionColor }}">{{ $directionIcon }}</span>
        <span class="px-2 py-1 rounded-full text-sm font-medium {{ $badgeColor }} {{ $isLive ? 'animate-pulse' : '' }}">
            {{ $statusText }}
        </span>
        @if($bookingStatus)
            <span class="px-2 py-1 rounded-full text-xs font-medium {{ $bookingColor }}">
                {{ $bookingStatus }}
            </span>
        @endif
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
