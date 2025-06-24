@php
    $duration = $getRecord()->duration_sec ?? 0;
    $minutes = floor($duration / 60);
    $seconds = $duration % 60;
    $formattedDuration = sprintf('%d:%02d', $minutes, $seconds);
    
    // Define duration categories
    $category = match(true) {
        $duration < 30 => ['label' => 'Sehr kurz', 'color' => 'gray', 'width' => 10],
        $duration < 60 => ['label' => 'Kurz', 'color' => 'yellow', 'width' => 20],
        $duration < 180 => ['label' => 'Normal', 'color' => 'blue', 'width' => 50],
        $duration < 300 => ['label' => 'Lang', 'color' => 'green', 'width' => 75],
        default => ['label' => 'Sehr lang', 'color' => 'purple', 'width' => 100]
    };
    
    // Calculate percentage (max 10 minutes for visualization)
    $maxDuration = 600; // 10 minutes
    $percentage = min(($duration / $maxDuration) * 100, 100);
    
    // Get average duration for comparison
    $avgDuration = 120; // This would come from a query in real implementation
    $comparisonPercent = (($duration - $avgDuration) / $avgDuration) * 100;
@endphp

<div class="w-full">
    <div class="flex items-center justify-between mb-1">
        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $formattedDuration }}
        </span>
        <span class="text-xs text-{{ $category['color'] }}-600 dark:text-{{ $category['color'] }}-400">
            {{ $category['label'] }}
        </span>
    </div>
    
    <div class="relative">
        <!-- Background bar -->
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
            <!-- Duration bar -->
            <div class="h-full rounded-full bg-gradient-to-r from-{{ $category['color'] }}-400 to-{{ $category['color'] }}-600 transition-all duration-500 ease-out"
                 style="width: {{ $percentage }}%">
            </div>
        </div>
        
        <!-- Average marker -->
        <div class="absolute top-0 h-2 w-0.5 bg-gray-600 dark:bg-gray-400"
             style="left: {{ ($avgDuration / $maxDuration) * 100 }}%"
             title="Durchschnitt: {{ floor($avgDuration / 60) }}:{{ str_pad($avgDuration % 60, 2, '0', STR_PAD_LEFT) }}">
        </div>
    </div>
    
    <!-- Comparison to average -->
    <div class="flex items-center gap-1 mt-1">
        @if($comparisonPercent > 10)
            <x-heroicon-m-arrow-trending-up class="w-3 h-3 text-red-500" />
            <span class="text-xs text-red-600 dark:text-red-400">
                {{ round(abs($comparisonPercent)) }}% über Durchschnitt
            </span>
        @elseif($comparisonPercent < -10)
            <x-heroicon-m-arrow-trending-down class="w-3 h-3 text-green-500" />
            <span class="text-xs text-green-600 dark:text-green-400">
                {{ round(abs($comparisonPercent)) }}% unter Durchschnitt
            </span>
        @else
            <x-heroicon-m-minus class="w-3 h-3 text-gray-400" />
            <span class="text-xs text-gray-500 dark:text-gray-400">
                Im Durchschnitt
            </span>
        @endif
    </div>
</div>