<div class="text-center">
    <div class="relative inline-flex items-center justify-center">
        <!-- Circular Progress -->
        <svg class="w-32 h-32 transform -rotate-90">
            <circle
                cx="64"
                cy="64"
                r="56"
                stroke="currentColor"
                stroke-width="12"
                fill="none"
                class="text-gray-200 dark:text-gray-700"
            />
            <circle
                cx="64"
                cy="64"
                r="56"
                stroke="currentColor"
                stroke-width="12"
                fill="none"
                stroke-dasharray="{{ 352 * ($score / 100) }} 352"
                class="{{ $sentiment === 'positive' ? 'text-green-500' : ($sentiment === 'negative' ? 'text-red-500' : 'text-yellow-500') }}"
                stroke-linecap="round"
            />
        </svg>
        
        <!-- Center Content -->
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            <span class="text-3xl">
                {{ $sentiment === 'positive' ? '😊' : ($sentiment === 'negative' ? '😞' : '😐') }}
            </span>
            <span class="text-lg font-bold mt-1">{{ $score }}%</span>
        </div>
    </div>
    
    <div class="mt-3">
        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Stimmung:</span>
        <span class="ml-1 text-sm font-bold {{ $sentiment === 'positive' ? 'text-green-600 dark:text-green-400' : ($sentiment === 'negative' ? 'text-red-600 dark:text-red-400' : 'text-yellow-600 dark:text-yellow-400') }}">
            {{ $sentiment === 'positive' ? 'Positiv' : ($sentiment === 'negative' ? 'Negativ' : 'Neutral') }}
        </span>
    </div>
</div>