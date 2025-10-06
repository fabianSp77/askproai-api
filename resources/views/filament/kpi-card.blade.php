<div class="bg-white dark:bg-gray-800 rounded-lg p-3 sm:p-4 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow min-w-0">
    <div class="flex items-start justify-between gap-2">
        <div class="flex-1 min-w-0">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1 truncate">
                {{ $label }}
            </p>
            <p @class([
                'text-xl sm:text-2xl font-bold truncate',
                'text-green-600 dark:text-green-400' => $color === 'success',
                'text-red-600 dark:text-red-400' => $color === 'danger',
                'text-yellow-600 dark:text-yellow-400' => $color === 'warning',
                'text-blue-600 dark:text-blue-400' => $color === 'primary',
                'text-gray-600 dark:text-gray-400' => $color === 'gray',
            ])>
                {{ $value }}
            </p>
            @if (isset($sublabel))
                <p class="text-xs text-gray-600 dark:text-gray-400 mt-1 truncate">
                    {{ $sublabel }}
                </p>
            @endif
        </div>

        @if(isset($icon))
        <div @class([
            'p-2 rounded-lg',
            'bg-green-50 dark:bg-green-900/20' => $color === 'success',
            'bg-red-50 dark:bg-red-900/20' => $color === 'danger',
            'bg-yellow-50 dark:bg-yellow-900/20' => $color === 'warning',
            'bg-blue-50 dark:bg-blue-900/20' => $color === 'primary',
            'bg-gray-50 dark:bg-gray-900/20' => $color === 'gray',
        ])>
            <x-dynamic-component :component="$icon" @class([
                'w-5 h-5',
                'text-green-600 dark:text-green-400' => $color === 'success',
                'text-red-600 dark:text-red-400' => $color === 'danger',
                'text-yellow-600 dark:text-yellow-400' => $color === 'warning',
                'text-blue-600 dark:text-blue-400' => $color === 'primary',
                'text-gray-600 dark:text-gray-400' => $color === 'gray',
            ]) />
        </div>
        @endif
    </div>
</div>