@php
    $status = $getRecord()->call_status;
    $statusConfig = match($status) {
        'in_progress' => [
            'label' => 'Läuft',
            'color' => 'warning',
            'icon' => 'heroicon-m-phone',
            'progress' => 50,
            'animated' => true
        ],
        'completed' => [
            'label' => 'Abgeschlossen',
            'color' => 'success',
            'icon' => 'heroicon-m-check-circle',
            'progress' => 100,
            'animated' => false
        ],
        'failed' => [
            'label' => 'Fehlgeschlagen',
            'color' => 'danger',
            'icon' => 'heroicon-m-x-circle',
            'progress' => 100,
            'animated' => false
        ],
        'analyzed' => [
            'label' => 'Analysiert',
            'color' => 'info',
            'icon' => 'heroicon-m-chart-bar',
            'progress' => 100,
            'animated' => false
        ],
        default => [
            'label' => $status,
            'color' => 'gray',
            'icon' => 'heroicon-m-question-mark-circle',
            'progress' => 0,
            'animated' => false
        ]
    };
    
    $hasTranscript = !empty($getRecord()->transcript);
    $hasAnalysis = !empty($getRecord()->analysis);
    $hasRecording = !empty($getRecord()->audio_url);
    
    // Calculate actual progress
    $steps = [
        'started' => true,
        'recorded' => $hasRecording,
        'transcribed' => $hasTranscript,
        'analyzed' => $hasAnalysis
    ];
    
    $completedSteps = count(array_filter($steps));
    $totalSteps = count($steps);
    $actualProgress = ($completedSteps / $totalSteps) * 100;
@endphp

<div class="flex items-center gap-3 w-full">
    <div class="flex-shrink-0">
        <div class="w-8 h-8 rounded-full bg-{{ $statusConfig['color'] }}-100 dark:bg-{{ $statusConfig['color'] }}-900/20 flex items-center justify-center">
            <x-dynamic-component 
                :component="$statusConfig['icon']" 
                class="w-4 h-4 text-{{ $statusConfig['color'] }}-600 dark:text-{{ $statusConfig['color'] }}-400 {{ $statusConfig['animated'] ? 'animate-pulse' : '' }}"
            />
        </div>
    </div>
    
    <div class="flex-1 min-w-0">
        <div class="flex items-center justify-between mb-1">
            <span class="text-sm font-medium text-{{ $statusConfig['color'] }}-700 dark:text-{{ $statusConfig['color'] }}-300">
                {{ $statusConfig['label'] }}
            </span>
            <span class="text-xs text-gray-500 dark:text-gray-400">
                {{ round($actualProgress) }}%
            </span>
        </div>
        
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
            <div class="bg-{{ $statusConfig['color'] }}-600 h-full rounded-full transition-all duration-500 ease-out relative"
                 style="width: {{ $actualProgress }}%">
                @if($statusConfig['animated'])
                    <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/20 to-transparent animate-shimmer"></div>
                @endif
            </div>
        </div>
        
        <div class="flex items-center gap-2 mt-1">
            @foreach($steps as $step => $completed)
                <div class="flex items-center gap-1">
                    @if($completed)
                        <svg class="w-3 h-3 text-{{ $statusConfig['color'] }}-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    @else
                        <svg class="w-3 h-3 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <circle cx="12" cy="12" r="10" stroke-width="2"/>
                        </svg>
                    @endif
                    <span class="text-xs {{ $completed ? 'text-gray-600 dark:text-gray-400' : 'text-gray-400 dark:text-gray-600' }}">
                        {{ ucfirst($step) }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    
    .animate-shimmer {
        animation: shimmer 2s infinite;
    }
</style>