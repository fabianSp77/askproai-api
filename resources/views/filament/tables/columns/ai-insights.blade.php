@php
    $analysis = $getRecord()->analysis ?? [];
    $insights = [];
    
    // Extract key insights
    if (isset($analysis['intent'])) {
        $insights[] = [
            'icon' => 'heroicon-m-light-bulb',
            'label' => 'Absicht: ' . ucfirst($analysis['intent']),
            'color' => 'blue'
        ];
    }
    
    if (isset($analysis['urgency']) && $analysis['urgency'] === 'high') {
        $insights[] = [
            'icon' => 'heroicon-m-exclamation-triangle',
            'label' => 'Hohe Dringlichkeit',
            'color' => 'red'
        ];
    }
    
    if (isset($analysis['appointment_requested']) && $analysis['appointment_requested']) {
        $insights[] = [
            'icon' => 'heroicon-m-calendar-days',
            'label' => 'Terminwunsch',
            'color' => 'green'
        ];
    }
    
    if (isset($analysis['keywords']) && is_array($analysis['keywords'])) {
        $topKeywords = array_slice($analysis['keywords'], 0, 3);
        if (!empty($topKeywords)) {
            $insights[] = [
                'icon' => 'heroicon-m-tag',
                'label' => implode(', ', $topKeywords),
                'color' => 'purple'
            ];
        }
    }
    
    // AI-generated summary
    $summary = $analysis['summary'] ?? null;
    $hasSummary = !empty($summary);
    
    // Predictive insights
    $predictions = [];
    if (isset($analysis['churn_risk']) && $analysis['churn_risk'] > 0.7) {
        $predictions[] = ['label' => 'Abwanderungsrisiko', 'value' => round($analysis['churn_risk'] * 100) . '%', 'color' => 'red'];
    }
    if (isset($analysis['satisfaction_score'])) {
        $predictions[] = ['label' => 'Zufriedenheit', 'value' => round($analysis['satisfaction_score'] * 100) . '%', 'color' => 'green'];
    }
@endphp

<div class="space-y-2">
    <!-- Quick insights -->
    @if(count($insights) > 0)
        <div class="flex flex-wrap gap-1">
            @foreach($insights as $insight)
                <div class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-{{ $insight['color'] }}-50 dark:bg-{{ $insight['color'] }}-900/20 group relative">
                    <x-dynamic-component 
                        :component="$insight['icon']" 
                        class="w-3 h-3 text-{{ $insight['color'] }}-600 dark:text-{{ $insight['color'] }}-400"
                    />
                    <span class="text-xs text-{{ $insight['color'] }}-700 dark:text-{{ $insight['color'] }}-300 font-medium">
                        {{ Str::limit($insight['label'], 20) }}
                    </span>
                    
                    <!-- Tooltip for full text -->
                    @if(strlen($insight['label']) > 20)
                        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-1 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none z-10">
                            {{ $insight['label'] }}
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
    
    <!-- AI Summary -->
    @if($hasSummary)
        <div class="relative group">
            <div class="flex items-start gap-2 p-2 rounded-lg bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/10 dark:to-blue-900/10 cursor-help">
                <x-heroicon-m-sparkles class="w-4 h-4 text-purple-600 dark:text-purple-400 flex-shrink-0 mt-0.5 animate-pulse" />
                <p class="text-xs text-gray-700 dark:text-gray-300 line-clamp-2">
                    {{ $summary }}
                </p>
            </div>
            
            <!-- Full summary on hover -->
            <div class="absolute top-full left-0 right-0 mt-1 p-3 bg-white dark:bg-gray-800 rounded-lg shadow-xl border border-gray-200 dark:border-gray-700 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-20">
                <div class="flex items-center gap-2 mb-2">
                    <x-heroicon-m-sparkles class="w-4 h-4 text-purple-600 dark:text-purple-400" />
                    <span class="text-sm font-medium text-gray-900 dark:text-gray-100">KI-Zusammenfassung</span>
                </div>
                <p class="text-sm text-gray-700 dark:text-gray-300">
                    {{ $summary }}
                </p>
            </div>
        </div>
    @endif
    
    <!-- Predictive metrics -->
    @if(count($predictions) > 0)
        <div class="flex items-center gap-3">
            @foreach($predictions as $prediction)
                <div class="flex items-center gap-1">
                    <div class="w-8 h-8 rounded-full bg-{{ $prediction['color'] }}-100 dark:bg-{{ $prediction['color'] }}-900/20 flex items-center justify-center">
                        <span class="text-xs font-bold text-{{ $prediction['color'] }}-700 dark:text-{{ $prediction['color'] }}-300">
                            {{ $prediction['value'] }}
                        </span>
                    </div>
                    <span class="text-xs text-gray-600 dark:text-gray-400">
                        {{ $prediction['label'] }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif
    
    <!-- No insights available -->
    @if(count($insights) === 0 && !$hasSummary && count($predictions) === 0)
        <div class="flex items-center gap-2 text-gray-400 dark:text-gray-600">
            <x-heroicon-m-cpu-chip class="w-4 h-4" />
            <span class="text-xs">Analyse ausstehend...</span>
        </div>
    @endif
</div>