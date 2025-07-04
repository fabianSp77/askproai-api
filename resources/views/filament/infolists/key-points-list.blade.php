@php
    $analysis = $getRecord()->analysis ?? [];
    $keyPoints = $analysis['key_points'] ?? [];
    
    if (empty($keyPoints) && !empty($analysis)) {
        // Extract key points from analysis data
        $keyPoints = [];
        if (!empty($analysis['reason'])) {
            $keyPoints[] = ['icon' => 'phone', 'text' => $analysis['reason']];
        }
        if (!empty($analysis['services_mentioned'])) {
            $keyPoints[] = ['icon' => 'briefcase', 'text' => 'Services: ' . implode(', ', (array)$analysis['services_mentioned'])];
        }
        if (!empty($analysis['urgency']) && $analysis['urgency'] !== 'Normal') {
            $keyPoints[] = ['icon' => 'exclamation', 'text' => 'Dringlichkeit: ' . $analysis['urgency']];
        }
    }
@endphp

@if(count($keyPoints) > 0)
    <div class="space-y-2">
        @foreach($keyPoints as $point)
            <div class="flex items-start gap-2">
                <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    @if(($point['icon'] ?? '') === 'phone')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    @elseif(($point['icon'] ?? '') === 'briefcase')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                    @elseif(($point['icon'] ?? '') === 'exclamation')
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    @else
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    @endif
                </svg>
                <span class="text-sm text-gray-700 dark:text-gray-300">{{ is_array($point) ? ($point['text'] ?? '') : $point }}</span>
            </div>
        @endforeach
    </div>
@else
    <p class="text-sm text-gray-500 dark:text-gray-400 italic">Keine wichtigen Punkte erfasst</p>
@endif