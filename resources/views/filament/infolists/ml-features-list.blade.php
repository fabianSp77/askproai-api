@php
    $mlPrediction = $getRecord()->mlPrediction;
    $topFeatures = $mlPrediction?->top_features ?? [];
    
    // Get top 5 features
    $features = array_slice($topFeatures, 0, 5);
@endphp

@if(count($features) > 0)
    <div class="space-y-2">
        @foreach($features as $feature => $value)
            @php
                $percentage = abs($value) * 100;
                $isPositive = $value > 0;
                $label = str_replace('_', ' ', ucfirst($feature));
            @endphp
            
            <div class="flex items-center justify-between text-xs">
                <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
                <div class="flex items-center gap-2">
                    <div class="w-24 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                        <div class="h-1.5 rounded-full {{ $isPositive ? 'bg-green-500' : 'bg-red-500' }}" 
                             style="width: {{ min($percentage, 100) }}%"></div>
                    </div>
                    <span class="text-gray-500 dark:text-gray-400 font-mono w-12 text-right">
                        {{ number_format($value, 2) }}
                    </span>
                </div>
            </div>
        @endforeach
    </div>
@else
    <p class="text-xs text-gray-500 dark:text-gray-400 italic">Keine Feature-Analyse verfügbar</p>
@endif