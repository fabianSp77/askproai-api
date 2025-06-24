@php
    $cost = $getRecord()->cost ?? 0;
    $formattedCost = number_format($cost, 2, ',', '.') . ' €';
    
    // Get recent average cost (would be from a query in real implementation)
    $avgCost = 0.25;
    $costDiff = $cost - $avgCost;
    $costDiffPercent = $avgCost > 0 ? (($costDiff / $avgCost) * 100) : 0;
    
    // Determine cost category
    $category = match(true) {
        $cost === 0 => ['label' => 'Kostenlos', 'color' => 'gray'],
        $cost < 0.10 => ['label' => 'Sehr günstig', 'color' => 'success'],
        $cost < 0.25 => ['label' => 'Günstig', 'color' => 'info'],
        $cost < 0.50 => ['label' => 'Normal', 'color' => 'primary'],
        $cost < 1.00 => ['label' => 'Teuer', 'color' => 'warning'],
        default => ['label' => 'Sehr teuer', 'color' => 'danger']
    };
    
    // Mock historical data for sparkline
    $historicalCosts = [0.15, 0.22, 0.18, 0.30, 0.25, 0.20, $cost];
    $maxHistorical = max($historicalCosts);
    $minHistorical = min($historicalCosts);
@endphp

<div class="flex items-center gap-3">
    <!-- Cost amount -->
    <div class="flex flex-col">
        <span class="text-sm font-semibold text-{{ $category['color'] }}-700 dark:text-{{ $category['color'] }}-300">
            {{ $formattedCost }}
        </span>
        <span class="text-xs text-gray-500 dark:text-gray-400">
            {{ $category['label'] }}
        </span>
    </div>
    
    <!-- Sparkline -->
    <div class="flex-1">
        <svg class="w-16 h-8" viewBox="0 0 64 32" preserveAspectRatio="none">
            <polyline
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                class="text-{{ $category['color'] }}-400"
                points="{{ implode(' ', array_map(function($value, $index) use ($historicalCosts, $maxHistorical, $minHistorical) {
                    $x = ($index / (count($historicalCosts) - 1)) * 64;
                    $y = 32 - (($value - $minHistorical) / ($maxHistorical - $minHistorical)) * 32;
                    return $x . ',' . $y;
                }, $historicalCosts, array_keys($historicalCosts))) }}"
            />
            
            <!-- Current value dot -->
            <circle
                cx="64"
                cy="{{ 32 - (($cost - $minHistorical) / ($maxHistorical - $minHistorical)) * 32 }}"
                r="3"
                fill="currentColor"
                class="text-{{ $category['color'] }}-600 animate-pulse"
            />
        </svg>
    </div>
    
    <!-- Trend indicator -->
    <div class="flex flex-col items-end">
        @if($costDiff > 0.01)
            <div class="flex items-center gap-1 text-red-600 dark:text-red-400">
                <x-heroicon-m-arrow-trending-up class="w-3 h-3" />
                <span class="text-xs font-medium">{{ round(abs($costDiffPercent)) }}%</span>
            </div>
        @elseif($costDiff < -0.01)
            <div class="flex items-center gap-1 text-green-600 dark:text-green-400">
                <x-heroicon-m-arrow-trending-down class="w-3 h-3" />
                <span class="text-xs font-medium">{{ round(abs($costDiffPercent)) }}%</span>
            </div>
        @else
            <div class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                <x-heroicon-m-minus class="w-3 h-3" />
                <span class="text-xs">0%</span>
            </div>
        @endif
        <span class="text-xs text-gray-400 dark:text-gray-600">
            vs. Ø {{ number_format($avgCost, 2, ',', '.') }}€
        </span>
    </div>
</div>