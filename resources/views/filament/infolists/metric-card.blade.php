@php
    $record = $getRecord();
    $field = $getName(); // Get the field name to determine metric type
    
    // Extract metric type from field name (e.g., 'sentiment_metric' -> 'sentiment')
    $metricType = str_replace('_metric', '', $field);
    
    // Set default values based on metric type
    $defaults = [
        'sentiment' => ['title' => 'Stimmung', 'icon' => 'heroicon-o-face-smile', 'type' => 'progress'],
        'satisfaction' => ['title' => 'Zufriedenheit', 'icon' => 'heroicon-o-star', 'type' => 'progress'],
        'goal' => ['title' => 'Zielerreichung', 'icon' => 'heroicon-o-trophy', 'type' => 'progress'],
        'urgency' => ['title' => 'Dringlichkeit', 'icon' => 'heroicon-o-bell-alert', 'type' => 'simple'],
    ];
    
    $title = $defaults[$metricType]['title'] ?? 'Metric';
    $icon = $defaults[$metricType]['icon'] ?? null;
    $type = $defaults[$metricType]['type'] ?? 'simple';
    
    // Calculate values based on metric type
    switch($metricType) {
        case 'sentiment':
            $value = $record->mlPrediction ? round($record->mlPrediction->sentiment_score * 100) : null;
            $label = match(true) {
                !$record->mlPrediction => 'Nicht analysiert',
                $record->mlPrediction->sentiment_score > 0.6 => 'Sehr positiv',
                $record->mlPrediction->sentiment_score > 0.3 => 'Positiv',
                $record->mlPrediction->sentiment_score < -0.3 => 'Negativ',
                default => 'Neutral',
            };
            $color = match(true) {
                !$record->mlPrediction => 'gray',
                $record->mlPrediction->sentiment_score > 0.6 => 'green',
                $record->mlPrediction->sentiment_score > 0.3 => 'blue',
                $record->mlPrediction->sentiment_score < -0.3 => 'red',
                default => 'gray',
            };
            break;
            
        case 'satisfaction':
            $value = $record->mlPrediction ? round($record->mlPrediction->satisfaction_score * 100) : null;
            $label = $record->mlPrediction ? round($record->mlPrediction->satisfaction_score * 5, 1) . ' / 5' : 'Nicht bewertet';
            $color = match(true) {
                !$record->mlPrediction => 'gray',
                $record->mlPrediction->satisfaction_score >= 0.8 => 'green',
                $record->mlPrediction->satisfaction_score >= 0.6 => 'yellow',
                default => 'red',
            };
            break;
            
        case 'goal':
            $value = $record->mlPrediction ? round($record->mlPrediction->goal_achievement_score * 100) : null;
            $label = match(true) {
                !$record->mlPrediction => 'Nicht bewertet',
                $record->mlPrediction->goal_achievement_score >= 0.9 => 'Erreicht',
                $record->mlPrediction->goal_achievement_score >= 0.7 => 'Teilweise',
                default => 'Verfehlt',
            };
            $color = match(true) {
                !$record->mlPrediction => 'gray',
                $record->mlPrediction->goal_achievement_score >= 0.9 => 'green',
                $record->mlPrediction->goal_achievement_score >= 0.7 => 'yellow',
                default => 'red',
            };
            break;
            
        case 'urgency':
            $urgency = $record->analysis['urgency'] ?? 'normal';
            $value = match($urgency) {
                'high' => 'Hoch',
                'medium' => 'Mittel',
                'low' => 'Niedrig',
                default => 'Normal',
            };
            $label = '';
            $color = match($urgency) {
                'high' => 'red',
                'medium' => 'yellow',
                'low' => 'blue',
                default => 'gray',
            };
            break;
            
        default:
            $value = null;
            $label = '';
            $color = 'gray';
    }
    
    $colorClasses = match($color) {
        'green' => 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
        'red' => 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
        'blue' => 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
        'yellow' => 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
        'gray' => 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800',
        default => 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800',
    };
    
    $progressColor = match($color) {
        'green' => 'bg-green-500',
        'red' => 'bg-red-500',
        'blue' => 'bg-blue-500',
        'yellow' => 'bg-yellow-500',
        default => 'bg-gray-400',
    };
@endphp

<div class="relative overflow-hidden rounded-xl border {{ $colorClasses }} p-6 transition-all hover:shadow-lg">
    {{-- Background Pattern --}}
    <div class="absolute right-0 top-0 -mt-4 -mr-4 h-24 w-24 rounded-full {{ $colorClasses }} opacity-10"></div>
    
    {{-- Icon --}}
    @if($icon)
        <div class="mb-4">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg {{ $colorClasses }}">
                <x-dynamic-component :component="$icon" class="w-6 h-6" />
            </div>
        </div>
    @endif
    
    {{-- Title --}}
    <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">{{ $title }}</h3>
    
    {{-- Value Display --}}
    @if($type === 'progress' && $value !== null)
        <div class="space-y-3">
            <div class="flex items-baseline gap-2">
                <span class="text-3xl font-bold {{ str_replace('bg-', 'text-', str_replace('/20', '', explode(' ', $colorClasses)[2])) }}">
                    {{ $value }}%
                </span>
                <span class="text-sm {{ str_replace('bg-', 'text-', str_replace('/20', '', explode(' ', $colorClasses)[2])) }}">
                    {{ $label }}
                </span>
            </div>
            
            {{-- Progress Bar --}}
            <div class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div class="{{ $progressColor }} h-full rounded-full transition-all duration-500"
                     style="width: {{ min(100, max(0, $value)) }}%">
                </div>
            </div>
        </div>
    @else
        <div class="flex items-baseline gap-2">
            <span class="text-3xl font-bold {{ str_replace('bg-', 'text-', str_replace('/20', '', explode(' ', $colorClasses)[2])) }}">
                {{ $value ?? '—' }}
            </span>
            @if($label)
                <span class="text-sm {{ str_replace('bg-', 'text-', str_replace('/20', '', explode(' ', $colorClasses)[2])) }}">
                    {{ $label }}
                </span>
            @endif
        </div>
    @endif
</div>