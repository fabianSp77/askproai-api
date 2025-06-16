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
                class="{{ $score >= 70 ? 'text-green-500' : ($score >= 40 ? 'text-yellow-500' : 'text-red-500') }}"
                stroke-linecap="round"
            />
        </svg>
        
        <!-- Center Content -->
        <div class="absolute inset-0 flex flex-col items-center justify-center">
            @if($hasAppointment)
                <span class="text-3xl">✅</span>
                <span class="text-sm font-bold text-green-600 dark:text-green-400">Gebucht!</span>
            @else
                <span class="text-2xl font-bold">{{ $score }}%</span>
                <span class="text-xs text-gray-500">Chance</span>
            @endif
        </div>
    </div>
    
    <div class="mt-3">
        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Conversion:</span>
        <span class="ml-1 text-sm font-bold {{ $score >= 70 ? 'text-green-600 dark:text-green-400' : ($score >= 40 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
            {{ $score >= 70 ? 'Sehr hoch' : ($score >= 40 ? 'Mittel' : 'Niedrig') }}
        </span>
    </div>
</div>