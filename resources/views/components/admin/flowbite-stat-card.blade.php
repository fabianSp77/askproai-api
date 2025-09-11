@props([
    'title' => '',
    'value' => '',
    'change' => null,
    'changeType' => 'increase', // 'increase', 'decrease', 'neutral'
    'icon' => null,
    'iconColor' => 'blue',
    'link' => null,
    'linkText' => 'View details'
])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            @if($icon)
                <div class="flex-shrink-0">
                    <div class="p-3 bg-{{ $iconColor }}-100 dark:bg-{{ $iconColor }}-900 rounded-lg">
                        {!! $icon !!}
                    </div>
                </div>
            @endif
            <div class="{{ $icon ? 'ml-4' : '' }}">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">{{ $title }}</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $value }}</p>
            </div>
        </div>
        
        @if($change)
            <div class="flex items-center">
                @if($changeType === 'increase')
                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span class="ml-1 text-sm font-medium text-green-500">{{ $change }}</span>
                @elseif($changeType === 'decrease')
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0v-8m0 8l-8-8-4 4-6-6"></path>
                    </svg>
                    <span class="ml-1 text-sm font-medium text-red-500">{{ $change }}</span>
                @else
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>
                    </svg>
                    <span class="ml-1 text-sm font-medium text-gray-500">{{ $change }}</span>
                @endif
            </div>
        @endif
    </div>
    
    @if($link)
        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ $link }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 flex items-center">
                {{ $linkText }}
                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </a>
        </div>
    @endif
</div>