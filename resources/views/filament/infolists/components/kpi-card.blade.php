@php
    try {
        $record = $getRecord();
        // In Filament ViewEntry context, viewData is passed differently
        $type = $type ?? 'default';
        $icon = $icon ?? 'heroicon-o-chart-bar';
        $title = $title ?? 'Metrik';
        $gradient = $gradient ?? 'from-blue-400 to-blue-600';
    } catch (\Exception $e) {
        $record = null;
        $type = 'default';
        $icon = 'heroicon-o-chart-bar';
        $title = 'Metrik';
        $gradient = 'from-blue-400 to-blue-600';
    }
    
    // Get values based on type
    $value = null;
    $label = null;
    $percentage = null;
    $trend = null;
    
    switch($type) {
        case 'sentiment':
            if ($record->mlPrediction) {
                $score = $record->mlPrediction->sentiment_score ?? 0;
                $percentage = round(($score + 1) * 50); // Convert -1 to 1 scale to 0-100
                $value = $percentage . '%';
                $label = match(true) {
                    $score > 0.6 => 'Sehr positiv',
                    $score > 0.3 => 'Positiv',
                    $score > 0 => 'Leicht positiv',
                    $score > -0.3 => 'Neutral',
                    $score > -0.6 => 'Negativ',
                    default => 'Sehr negativ',
                };
                $gradient = match(true) {
                    $score > 0.3 => 'from-emerald-400 to-green-600',
                    $score > -0.3 => 'from-blue-400 to-indigo-600',
                    default => 'from-red-400 to-rose-600',
                };
            } else {
                $value = '—';
                $label = 'Nicht analysiert';
                $percentage = 0;
            }
            break;
            
        case 'satisfaction':
            if ($record->mlPrediction) {
                $score = $record->mlPrediction->satisfaction_score ?? 0;
                $percentage = round($score * 100);
                $value = $percentage . '%';
                $label = round($score * 5, 1) . ' / 5 Sterne';
                $gradient = match(true) {
                    $score >= 0.8 => 'from-amber-400 to-yellow-600',
                    $score >= 0.6 => 'from-orange-400 to-amber-600',
                    default => 'from-gray-400 to-gray-600',
                };
            } else {
                $value = '—';
                $label = 'Nicht bewertet';
                $percentage = 0;
            }
            break;
            
        case 'goal':
            if ($record->mlPrediction) {
                $score = $record->mlPrediction->goal_achievement_score ?? 0;
                $percentage = round($score * 100);
                $value = $percentage . '%';
                $label = match(true) {
                    $score >= 0.9 => 'Ziel erreicht',
                    $score >= 0.7 => 'Teilweise erreicht',
                    default => 'Ziel verfehlt',
                };
                $gradient = match(true) {
                    $score >= 0.9 => 'from-purple-400 to-indigo-600',
                    $score >= 0.7 => 'from-blue-400 to-purple-600',
                    default => 'from-gray-400 to-gray-600',
                };
            } else {
                $value = '—';
                $label = 'Nicht bewertet';
                $percentage = 0;
            }
            break;
            
        case 'urgency':
            if ($record->mlPrediction) {
                $score = $record->mlPrediction->urgency_score ?? 0;
                $percentage = round($score * 100);
                $value = match(true) {
                    $score >= 0.8 => 'Hoch',
                    $score >= 0.5 => 'Mittel',
                    default => 'Niedrig',
                };
                $label = $percentage . '% Dringlichkeit';
                $gradient = match(true) {
                    $score >= 0.8 => 'from-red-400 to-pink-600',
                    $score >= 0.5 => 'from-amber-400 to-orange-600',
                    default => 'from-gray-400 to-gray-600',
                };
            } else {
                $value = '—';
                $label = 'Nicht bewertet';
                $percentage = 0;
            }
            break;
    }
@endphp

<div class="relative h-full p-6">
    {{-- Background Gradient --}}
    <div class="absolute inset-0 bg-gradient-to-br {{ $gradient }} opacity-10 rounded-2xl"></div>
    
    {{-- Content --}}
    <div class="relative z-10">
        {{-- Icon and Title --}}
        <div class="flex items-start justify-between mb-4">
            <div class="p-3 bg-gradient-to-br {{ $gradient }} rounded-xl text-white shadow-lg">
                <x-dynamic-component :component="$icon" class="w-6 h-6" />
            </div>
            @if($percentage !== null && $type !== 'urgency')
                <div class="relative w-16 h-16">
                    <svg class="w-16 h-16 transform -rotate-90">
                        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="6" fill="none" class="text-gray-200 dark:text-gray-700" />
                        <circle cx="32" cy="32" r="28" stroke="url(#gradient-{{ $type }})" stroke-width="6" fill="none" 
                                stroke-dasharray="{{ 176 * $percentage / 100 }} 176" 
                                class="transition-all duration-1000 ease-out" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-sm font-bold text-gray-700 dark:text-gray-300">{{ $percentage }}%</span>
                    </div>
                    <svg width="0" height="0">
                        <defs>
                            <linearGradient id="gradient-{{ $type }}" x1="0%" y1="0%" x2="100%" y2="100%">
                                <stop offset="0%" class="text-{{ explode(' ', str_replace(['from-', 'to-'], '', $gradient))[0] }} stop-color" />
                                <stop offset="100%" class="text-{{ explode(' ', str_replace(['from-', 'to-'], '', $gradient))[1] }} stop-color" />
                            </linearGradient>
                        </defs>
                    </svg>
                </div>
            @endif
        </div>
        
        {{-- Title --}}
        <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $title }}</h3>
        
        {{-- Value --}}
        <div class="text-2xl font-bold text-gray-900 dark:text-white mb-1">
            {{ $value }}
        </div>
        
        {{-- Label --}}
        @if($label)
            <div class="text-sm text-gray-500 dark:text-gray-400">
                {{ $label }}
            </div>
        @endif
    </div>
</div>