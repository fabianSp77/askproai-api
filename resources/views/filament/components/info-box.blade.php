@php
    $iconClass = match($type) {
        'info' => 'heroicon-o-information-circle',
        'success' => 'heroicon-o-check-circle',
        'warning' => 'heroicon-o-exclamation-triangle',
        'error' => 'heroicon-o-x-circle',
        default => 'heroicon-o-information-circle'
    };
    
    $colorClasses = match($type) {
        'info' => 'bg-blue-50 border-blue-200 dark:bg-blue-900/20 dark:border-blue-800',
        'success' => 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800',
        'warning' => 'bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800',
        'error' => 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800',
        default => 'bg-gray-50 border-gray-200 dark:bg-gray-900/20 dark:border-gray-800'
    };
    
    $iconColorClasses = match($type) {
        'info' => 'text-blue-600 dark:text-blue-400',
        'success' => 'text-green-600 dark:text-green-400',
        'warning' => 'text-yellow-600 dark:text-yellow-400',
        'error' => 'text-red-600 dark:text-red-400',
        default => 'text-gray-600 dark:text-gray-400'
    };
    
    $textColorClasses = match($type) {
        'info' => 'text-blue-800 dark:text-blue-200',
        'success' => 'text-green-800 dark:text-green-200',
        'warning' => 'text-yellow-800 dark:text-yellow-200',
        'error' => 'text-red-800 dark:text-red-200',
        default => 'text-gray-800 dark:text-gray-200'
    };
@endphp

<div class="rounded-lg border p-4 {{ $colorClasses }}">
    <div class="flex items-start space-x-3">
        <div class="flex-shrink-0">
            <x-dynamic-component :component="$iconClass" class="w-5 h-5 {{ $iconColorClasses }}" />
        </div>
        <div class="flex-1">
            @if(isset($title) && $title)
                <h4 class="text-sm font-semibold {{ $textColorClasses }} mb-1">
                    {{ $title }}
                </h4>
            @endif
            <p class="text-sm {{ $textColorClasses }}">
                {{ $message }}
            </p>
            @if(isset($items) && is_array($items))
                <ul class="mt-2 space-y-1">
                    @foreach($items as $item)
                        <li class="text-sm {{ $textColorClasses }} flex items-start">
                            <span class="mr-2">•</span>
                            <span>{{ $item }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>