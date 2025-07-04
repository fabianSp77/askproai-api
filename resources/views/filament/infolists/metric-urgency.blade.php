@php
    $record = $getRecord();
    
    $title = 'Dringlichkeit';
    $icon = 'heroicon-o-bell-alert';
    $type = 'simple';
    
    $urgency = 'normal';
    if (isset($record->analysis) && is_array($record->analysis) && isset($record->analysis['urgency'])) {
        $urgency = $record->analysis['urgency'];
    }
    
    $value = match($urgency) {
        'high' => 'Hoch',
        'medium' => 'Mittel',
        'low' => 'Niedrig',
        default => 'Normal',
    };
    $label = '';
    $color = match($urgency) {
        'high' => 'red',
        'medium' => 'yellow',
        'low' => 'blue',
        default => 'gray',
    };
    
    $colorClasses = match($color) {
        'green' => 'text-green-600 dark:text-green-400 bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800',
        'red' => 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800',
        'blue' => 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800',
        'yellow' => 'text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800',
        'gray' => 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800',
        default => 'text-gray-600 dark:text-gray-400 bg-gray-50 dark:bg-gray-900/20 border-gray-200 dark:border-gray-800',
    };
@endphp

<div class="relative overflow-hidden rounded-xl border {{ $colorClasses }} p-6 transition-all hover:shadow-lg">
    {{-- Background Pattern --}}
    <div class="absolute right-0 top-0 -mt-4 -mr-4 h-24 w-24 rounded-full {{ $colorClasses }} opacity-10"></div>
    
    {{-- Icon --}}
    @if($icon)
        <div class="mb-4">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-lg {{ $colorClasses }}">
                <x-dynamic-component :component="$icon" class="w-6 h-6" />
            </div>
        </div>
    @endif
    
    {{-- Title --}}
    <h3 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">{{ $title }}</h3>
    
    {{-- Value Display --}}
    <div class="flex items-baseline gap-2">
        <span class="text-3xl font-bold {{ str_replace('bg-', 'text-', str_replace('/20', '', explode(' ', $colorClasses)[2])) }}">
            {{ $value }}
        </span>
    </div>
</div>