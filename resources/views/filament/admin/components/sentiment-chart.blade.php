@php
    $sentimentScore = $getRecord()->sentiment_score ?? 5;
    $sentiment = $getRecord()->sentiment ?? 'neutral';
    
    $positiveWidth = $sentiment === 'positive' ? $sentimentScore * 10 : 0;
    $neutralWidth = $sentiment === 'neutral' ? $sentimentScore * 10 : 0;
    $negativeWidth = $sentiment === 'negative' ? $sentimentScore * 10 : 0;
@endphp

<div class="sentiment-chart-container">
    <div class="sentiment-bars space-y-3">
        <!-- Positive -->
        <div class="sentiment-bar-row">
            <div class="flex items-center gap-2 w-24">
                <x-heroicon-o-face-smile class="w-5 h-5 text-green-500" />
                <span class="text-sm font-medium">Positive</span>
            </div>
            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                <div class="bg-green-500 h-full rounded-full transition-all duration-1000 ease-out" 
                     style="width: {{ $positiveWidth }}%">
                </div>
            </div>
            <span class="text-sm font-medium w-12 text-right">{{ $sentiment === 'positive' ? $sentimentScore : '0' }}</span>
        </div>

        <!-- Neutral -->
        <div class="sentiment-bar-row">
            <div class="flex items-center gap-2 w-24">
                <x-heroicon-o-minus-circle class="w-5 h-5 text-gray-500" />
                <span class="text-sm font-medium">Neutral</span>
            </div>
            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                <div class="bg-gray-500 h-full rounded-full transition-all duration-1000 ease-out" 
                     style="width: {{ $neutralWidth }}%">
                </div>
            </div>
            <span class="text-sm font-medium w-12 text-right">{{ $sentiment === 'neutral' ? $sentimentScore : '0' }}</span>
        </div>

        <!-- Negative -->
        <div class="sentiment-bar-row">
            <div class="flex items-center gap-2 w-24">
                <x-heroicon-o-face-frown class="w-5 h-5 text-red-500" />
                <span class="text-sm font-medium">Negative</span>
            </div>
            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                <div class="bg-red-500 h-full rounded-full transition-all duration-1000 ease-out" 
                     style="width: {{ $negativeWidth }}%">
                </div>
            </div>
            <span class="text-sm font-medium w-12 text-right">{{ $sentiment === 'negative' ? $sentimentScore : '0' }}</span>
        </div>
    </div>

    <!-- Summary -->
    <div class="mt-4 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Overall sentiment: 
            <span class="font-semibold {{ $sentiment === 'positive' ? 'text-green-600' : ($sentiment === 'negative' ? 'text-red-600' : 'text-gray-600') }}">
                {{ ucfirst($sentiment) }}
            </span>
            with a confidence score of {{ $sentimentScore }}/10
        </p>
    </div>
</div>

<style>
    .sentiment-bar-row {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
</style>