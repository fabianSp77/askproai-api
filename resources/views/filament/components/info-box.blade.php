@php
    $type = $type ?? 'info';
    $colors = match($type) {
        'success' => 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300',
        'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-300',
        'danger' => 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300',
        default => 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-300',
    };
    
    $icon = match($type) {
        'success' => 'heroicon-o-check-circle',
        'warning' => 'heroicon-o-exclamation-triangle',
        'danger' => 'heroicon-o-x-circle',
        default => 'heroicon-o-information-circle',
    };
@endphp

<div class="rounded-lg border p-4 {{ $colors }}">
    <div class="flex">
        <div class="flex-shrink-0">
            <x-dynamic-component :component="$icon" class="h-5 w-5" />
        </div>
        <div class="ml-3 flex-1">
            <p class="text-sm">
                {{ $message }}
            </p>
        </div>
    </div>
</div>