@props(['customerData', 'field', 'type' => 'default'])

@php
    $value = $customerData[$field] ?? null;
    if (!$value) return;
    
    // Determine badge color based on type
    $colorClass = match($type) {
        'urgency' => match(strtolower($value)) {
            'high', 'hoch' => 'bg-red-100 text-red-800',
            'medium', 'mittel' => 'bg-yellow-100 text-yellow-800',  
            'low', 'niedrig' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800'
        },
        'status' => match(strtolower($value)) {
            'new', 'neu' => 'bg-blue-100 text-blue-800',
            'in_progress', 'in bearbeitung' => 'bg-yellow-100 text-yellow-800',
            'completed', 'abgeschlossen' => 'bg-green-100 text-green-800',
            'cancelled', 'abgebrochen' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        },
        default => 'bg-gray-100 text-gray-800'
    };
    
    // Translate value if needed
    $displayValue = match(strtolower($value)) {
        'high' => 'Hoch',
        'medium' => 'Mittel',
        'low' => 'Niedrig',
        'hoch' => 'Hoch',
        'mittel' => 'Mittel',
        'niedrig' => 'Niedrig',
        default => ucfirst($value)
    };
@endphp

<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $colorClass }}">
    @if($type === 'urgency')
        @switch(strtolower($value))
            @case('high')
            @case('hoch')
                <svg class="-ml-0.5 mr-1.5 h-2 w-2" fill="currentColor" viewBox="0 0 8 8">
                    <circle cx="4" cy="4" r="3" />
                </svg>
                @break
            @case('medium')
            @case('mittel')
                <svg class="-ml-0.5 mr-1.5 h-2 w-2" fill="currentColor" viewBox="0 0 8 8">
                    <path d="M4 0L0 4l4 4 4-4z" />
                </svg>
                @break
            @case('low')
            @case('niedrig')
                <svg class="-ml-0.5 mr-1.5 h-2 w-2" fill="currentColor" viewBox="0 0 8 8">
                    <rect x="2" y="2" width="4" height="4" />
                </svg>
                @break
        @endswitch
    @endif
    {{ $displayValue }}
</span>