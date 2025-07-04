@php
    $record = $getRecord();
    $progress = $record ? $record->configuration_progress : ['percentage' => 0, 'details' => []];
    $percentage = $progress['percentage'] ?? 0;
@endphp

<div class="w-full p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Konfigurationsfortschritt
        </h3>
        <span class="text-2xl font-bold {{ $percentage >= 100 ? 'text-green-600' : 'text-gray-600' }}">
            {{ $percentage }}%
        </span>
    </div>
    
    <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700 mb-4">
        <div class="h-3 rounded-full transition-all duration-500 ease-out
            {{ $percentage >= 100 ? 'bg-green-600' : 
               ($percentage >= 75 ? 'bg-yellow-500' : 'bg-red-500') }}"
            style="width: {{ $percentage }}%">
        </div>
    </div>
    
    @if(isset($progress['details']) && count($progress['details']) > 0)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-sm">
            @foreach($progress['details'] as $key => $section)
                <div class="flex items-center space-x-1">
                    @if(isset($section['completed']) && $section['completed'])
                        <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    @else
                        <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    @endif
                    <span class="{{ (isset($section['completed']) && $section['completed']) ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $section['label'] ?? ucfirst(str_replace('_', ' ', $key)) }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif
</div>
