@php
    $sentiment = $getRecord()->analysis['sentiment'] ?? 'neutral';
    $sentimentConfig = match($sentiment) {
        'positive' => [
            'emoji' => '😊',
            'color' => 'success',
            'bg' => 'bg-green-50 dark:bg-green-900/20',
            'text' => 'text-green-700 dark:text-green-300',
            'label' => 'Positiv',
            'animation' => 'animate-bounce-subtle'
        ],
        'negative' => [
            'emoji' => '😞',
            'color' => 'danger',
            'bg' => 'bg-red-50 dark:bg-red-900/20',
            'text' => 'text-red-700 dark:text-red-300',
            'label' => 'Negativ',
            'animation' => 'animate-shake-subtle'
        ],
        'neutral' => [
            'emoji' => '😐',
            'color' => 'gray',
            'bg' => 'bg-gray-50 dark:bg-gray-900/20',
            'text' => 'text-gray-700 dark:text-gray-300',
            'label' => 'Neutral',
            'animation' => ''
        ],
        default => [
            'emoji' => '❓',
            'color' => 'gray',
            'bg' => 'bg-gray-50 dark:bg-gray-900/20',
            'text' => 'text-gray-700 dark:text-gray-300',
            'label' => 'Unbekannt',
            'animation' => ''
        ]
    };
    
    $confidence = $getRecord()->analysis['sentiment_confidence'] ?? 0;
@endphp

<div class="flex flex-col items-center gap-1">
    <div class="{{ $sentimentConfig['bg'] }} {{ $sentimentConfig['animation'] }} rounded-lg p-2 relative group">
        <span class="text-2xl">{{ $sentimentConfig['emoji'] }}</span>
        
        @if($confidence > 0)
            <div class="absolute -bottom-1 left-1/2 transform -translate-x-1/2 flex gap-0.5">
                @for($i = 0; $i < 3; $i++)
                    <div class="w-1 h-1 rounded-full {{ $i < round($confidence * 3) ? 'bg-' . $sentimentConfig['color'] . '-500' : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                @endfor
            </div>
        @endif
        
        <!-- Hover tooltip -->
        <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
            {{ $sentimentConfig['label'] }}
            @if($confidence > 0)
                ({{ round($confidence * 100) }}% sicher)
            @endif
        </div>
    </div>
    
    <span class="text-xs {{ $sentimentConfig['text'] }} font-medium">
        {{ $sentimentConfig['label'] }}
    </span>
</div>

<style>
    @keyframes bounce-subtle {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-2px); }
    }
    
    @keyframes shake-subtle {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-1px); }
        75% { transform: translateX(1px); }
    }
    
    .animate-bounce-subtle {
        animation: bounce-subtle 2s ease-in-out infinite;
    }
    
    .animate-shake-subtle {
        animation: shake-subtle 2s ease-in-out infinite;
    }
</style>