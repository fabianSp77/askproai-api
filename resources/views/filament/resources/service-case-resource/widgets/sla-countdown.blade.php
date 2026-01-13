@php
    $record = $getRecord();
    $now = now();

    // Response SLA
    $responseDeadline = $record->sla_response_due_at;
    $responseOverdue = $responseDeadline && $now->greaterThan($responseDeadline);

    // Resolution SLA
    $resolutionDeadline = $record->sla_resolution_due_at;
    $resolutionOverdue = $resolutionDeadline && $now->greaterThan($resolutionDeadline);

    // Calculate remaining time
    $formatRemaining = function($deadline) use ($now) {
        if (!$deadline) return null;

        $diff = $now->diff($deadline);
        $isOverdue = $now->greaterThan($deadline);

        $days = $diff->days;
        $hours = $diff->h;
        $minutes = $diff->i;

        if ($days > 0) {
            return sprintf('%dd %dh', $days, $hours);
        } elseif ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        } else {
            return sprintf('%dm', $minutes);
        }
    };

    // Calculate progress percentage (based on case creation time to deadline)
    $calculateProgress = function($deadline) use ($now, $record) {
        if (!$deadline) return null;
        if ($now->greaterThan($deadline)) return 0; // Overdue = 0%

        $totalDuration = $record->created_at->diffInMinutes($deadline);
        $elapsed = $record->created_at->diffInMinutes($now);

        if ($totalDuration <= 0) return 100;

        $remaining = max(0, 100 - (($elapsed / $totalDuration) * 100));
        return round($remaining);
    };

    $responseRemaining = $formatRemaining($responseDeadline);
    $resolutionRemaining = $formatRemaining($resolutionDeadline);
    $responseProgress = $calculateProgress($responseDeadline);
    $resolutionProgress = $calculateProgress($resolutionDeadline);

    $hasSLA = $responseDeadline || $resolutionDeadline;

    // Progress bar color based on percentage
    $getProgressColor = function($progress, $isOverdue) {
        if ($isOverdue || $progress === 0) return 'bg-red-500';
        if ($progress <= 25) return 'bg-red-500';
        if ($progress <= 50) return 'bg-yellow-500';
        return 'bg-green-500';
    };
@endphp

<div class="space-y-4" role="region" aria-label="SLA Status">
    {{-- No SLA configured state --}}
    @if(!$hasSLA)
        <div class="text-center py-6 text-gray-500 dark:text-gray-400">
            <x-heroicon-o-shield-exclamation class="w-10 h-10 mx-auto mb-2 opacity-50" />
            <p class="text-sm font-medium">Keine SLA definiert</p>
            <p class="text-xs mt-1">Service Level Agreements werden in der Kategorie konfiguriert</p>
        </div>
    @else
    {{-- Response SLA --}}
    <div @class([
        'p-4 rounded-lg border-2 transition-all',
        'bg-red-50 dark:bg-red-950 border-red-300 dark:border-red-700 sla-overdue' => $responseOverdue,
        'bg-green-50 dark:bg-green-950 border-green-300 dark:border-green-700' => !$responseOverdue && $responseDeadline,
        'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700' => !$responseDeadline,
    ])
        role="article"
        aria-label="Response SLA: {{ $responseOverdue ? 'Uberfällig' : ($responseRemaining ?? 'Nicht definiert') }}"
    >
        <div class="flex items-center justify-between mb-2">
            <span @class([
                'text-sm font-bold uppercase tracking-wide',
                'text-red-700 dark:text-red-300' => $responseOverdue,
                'text-green-700 dark:text-green-300' => !$responseOverdue && $responseDeadline,
                'text-gray-500 dark:text-gray-400' => !$responseDeadline,
            ])>
                Response
            </span>
            @if($responseDeadline)
                @if($responseOverdue)
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400 animate-pulse" aria-hidden="true" />
                @else
                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-600 dark:text-green-400" aria-hidden="true" />
                @endif
            @else
                <x-heroicon-o-minus-circle class="w-5 h-5 text-gray-400" aria-hidden="true" />
            @endif
        </div>

        @if($responseDeadline)
            <div @class([
                'text-xl font-bold',
                'text-red-900 dark:text-red-100' => $responseOverdue,
                'text-green-900 dark:text-green-100' => !$responseOverdue,
            ])>
                @if($responseOverdue)
                    <span class="inline-flex items-center gap-1">
                        <span class="text-red-600">-</span>{{ $responseRemaining }}
                    </span>
                    <span class="text-sm font-normal ml-1">uberfällig</span>
                @else
                    {{ $responseRemaining }}
                    <span class="text-sm font-normal ml-1">verbleibend</span>
                @endif
            </div>

            {{-- Progress Bar --}}
            <div class="mt-3">
                <div class="h-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden" role="progressbar" aria-valuenow="{{ $responseProgress }}" aria-valuemin="0" aria-valuemax="100">
                    <div
                        class="{{ $getProgressColor($responseProgress, $responseOverdue) }} h-full rounded-full transition-all duration-500 {{ $responseProgress <= 10 && !$responseOverdue ? 'animate-pulse' : '' }}"
                        style="width: {{ max($responseProgress, 2) }}%"
                    ></div>
                </div>
                <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ $responseProgress }}% verbleibend</span>
                    <span>{{ $responseDeadline->format('d.m. H:i') }}</span>
                </div>
            </div>
        @else
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Nicht definiert
            </div>
        @endif
    </div>

    {{-- Resolution SLA --}}
    <div @class([
        'p-4 rounded-lg border-2 transition-all',
        'bg-red-50 dark:bg-red-950 border-red-300 dark:border-red-700 sla-overdue' => $resolutionOverdue,
        'bg-blue-50 dark:bg-blue-950 border-blue-300 dark:border-blue-700' => !$resolutionOverdue && $resolutionDeadline,
        'bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700' => !$resolutionDeadline,
    ])
        role="article"
        aria-label="Resolution SLA: {{ $resolutionOverdue ? 'Uberfällig' : ($resolutionRemaining ?? 'Nicht definiert') }}"
    >
        <div class="flex items-center justify-between mb-2">
            <span @class([
                'text-sm font-bold uppercase tracking-wide',
                'text-red-700 dark:text-red-300' => $resolutionOverdue,
                'text-blue-700 dark:text-blue-300' => !$resolutionOverdue && $resolutionDeadline,
                'text-gray-500 dark:text-gray-400' => !$resolutionDeadline,
            ])>
                Resolution
            </span>
            @if($resolutionDeadline)
                @if($resolutionOverdue)
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-600 dark:text-red-400 animate-pulse" aria-hidden="true" />
                @else
                    <x-heroicon-o-clock class="w-5 h-5 text-blue-600 dark:text-blue-400" aria-hidden="true" />
                @endif
            @else
                <x-heroicon-o-minus-circle class="w-5 h-5 text-gray-400" aria-hidden="true" />
            @endif
        </div>

        @if($resolutionDeadline)
            <div @class([
                'text-xl font-bold',
                'text-red-900 dark:text-red-100' => $resolutionOverdue,
                'text-blue-900 dark:text-blue-100' => !$resolutionOverdue,
            ])>
                @if($resolutionOverdue)
                    <span class="inline-flex items-center gap-1">
                        <span class="text-red-600">-</span>{{ $resolutionRemaining }}
                    </span>
                    <span class="text-sm font-normal ml-1">uberfällig</span>
                @else
                    {{ $resolutionRemaining }}
                    <span class="text-sm font-normal ml-1">verbleibend</span>
                @endif
            </div>

            {{-- Progress Bar --}}
            <div class="mt-3">
                <div class="h-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden" role="progressbar" aria-valuenow="{{ $resolutionProgress }}" aria-valuemin="0" aria-valuemax="100">
                    <div
                        class="{{ $getProgressColor($resolutionProgress, $resolutionOverdue) }} h-full rounded-full transition-all duration-500 {{ $resolutionProgress <= 10 && !$resolutionOverdue ? 'animate-pulse' : '' }}"
                        style="width: {{ max($resolutionProgress, 2) }}%"
                    ></div>
                </div>
                <div class="flex justify-between mt-1 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ $resolutionProgress }}% verbleibend</span>
                    <span>{{ $resolutionDeadline->format('d.m. H:i') }}</span>
                </div>
            </div>
        @else
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Nicht definiert
            </div>
        @endif
    </div>
    @endif
</div>
