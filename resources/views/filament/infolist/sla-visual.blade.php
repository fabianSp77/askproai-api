@php
    $record = $getRecord();
    $responseHours = $record->sla_response_hours;
    $resolutionHours = $record->sla_resolution_hours;

    // Calculate visual proportions (max 100% for the bar)
    $maxHours = max($responseHours ?? 0, $resolutionHours ?? 0, 1);
    $responsePercent = $responseHours ? round(($responseHours / $maxHours) * 100) : 0;
    $resolutionPercent = $resolutionHours ? round(($resolutionHours / $maxHours) * 100) : 0;
@endphp

{{-- 📊 SLA Visual Timeline --}}
<div class="mt-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700"
     role="img"
     aria-label="SLA Timeline: Reaktion {{ $responseHours }}h, Lösung {{ $resolutionHours }}h">

    <div class="text-xs text-gray-500 dark:text-gray-400 mb-2 font-medium">SLA Timeline</div>

    <div class="relative h-8 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
        {{-- Response Time Bar --}}
        @if($responseHours)
            <div class="absolute top-0 left-0 h-full bg-blue-500 dark:bg-blue-600 transition-all duration-500"
                 style="width: {{ $responsePercent }}%"
                 title="Reaktionszeit: {{ $responseHours }}h">
            </div>
        @endif

        {{-- Resolution Time Bar (overlays response) --}}
        @if($resolutionHours)
            <div class="absolute top-0 left-0 h-full bg-green-500/50 dark:bg-green-600/50 transition-all duration-500"
                 style="width: {{ $resolutionPercent }}%"
                 title="Lösungszeit: {{ $resolutionHours }}h">
            </div>
        @endif

        {{-- Labels inside bar --}}
        <div class="absolute inset-0 flex items-center justify-between px-3 text-xs font-medium text-white">
            @if($responseHours)
                <span class="drop-shadow">{{ $responseHours }}h Reaktion</span>
            @else
                <span></span>
            @endif

            @if($resolutionHours)
                <span class="drop-shadow">{{ $resolutionHours }}h Lösung</span>
            @endif
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex items-center gap-4 mt-2 text-xs text-gray-600 dark:text-gray-400">
        <div class="flex items-center gap-1">
            <div class="w-3 h-3 bg-blue-500 rounded"></div>
            <span>Reaktionszeit</span>
        </div>
        <div class="flex items-center gap-1">
            <div class="w-3 h-3 bg-green-500 rounded"></div>
            <span>Lösungszeit</span>
        </div>
    </div>
</div>
