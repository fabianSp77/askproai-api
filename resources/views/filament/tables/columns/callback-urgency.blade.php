@php
    $record = $getRecord();
    $isOverdue = $record->is_overdue;
    $priority = $record->priority;

    // Determine urgency level (0 = most urgent, 3 = least urgent)
    $urgencyLevel = match(true) {
        $isOverdue && $priority === 'urgent' => 0,
        $isOverdue => 1,
        $priority === 'urgent' => 2,
        $priority === 'high' => 3,
        default => 4,
    };

    $color = match($urgencyLevel) {
        0, 1 => 'danger',
        2 => 'warning',
        3 => 'warning',
        default => 'gray',
    };

    $icon = match($urgencyLevel) {
        0 => 'heroicon-o-fire',
        1 => 'heroicon-o-exclamation-triangle',
        2 => 'heroicon-o-exclamation-triangle',
        3 => 'heroicon-o-arrow-up-circle',
        default => 'heroicon-o-minus-circle',
    };

    $pulse = in_array($urgencyLevel, [0, 1]); // Pulse animation for critical

    $tooltip = match($urgencyLevel) {
        0 => 'KRITISCH: Überfällig & Dringend',
        1 => 'ÜBERFÄLLIG',
        2 => 'Dringend',
        3 => 'Hohe Priorität',
        default => 'Normale Priorität',
    };
@endphp

<div class="flex items-center justify-center" title="{{ $tooltip }}">
    <div class="relative inline-flex">
        @if($pulse)
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-{{ $color }}-400 opacity-75"></span>
        @endif
        <div class="relative inline-flex items-center justify-center">
            <x-filament::icon
                :icon="$icon"
                @class([
                    'h-6 w-6',
                    'text-danger-500' => $color === 'danger',
                    'text-warning-500' => $color === 'warning',
                    'text-gray-400' => $color === 'gray',
                ])
            />
        </div>
    </div>
</div>
