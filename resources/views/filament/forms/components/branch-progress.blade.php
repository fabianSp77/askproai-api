<div class="w-full p-4 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Konfigurationsfortschritt
        </h3>
        <span class="text-2xl font-bold {{ $getRecord()->configuration_progress['percentage'] >= 100 ? 'text-green-600' : 'text-gray-600' }}">
            {{ $getRecord()->configuration_progress['percentage'] }}%
        </span>
    </div>
    
    <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700 mb-4">
        <div class="h-3 rounded-full transition-all duration-500 ease-out
            {{ $getRecord()->configuration_progress['percentage'] >= 100 ? 'bg-green-600' : 
               ($getRecord()->configuration_progress['percentage'] >= 75 ? 'bg-yellow-500' : 'bg-red-500') }}"
            style="width: {{ $getRecord()->configuration_progress['percentage'] }}%">
        </div>
    </div>
    
    <div class="grid grid-cols-2 md:grid-cols-5 gap-2 text-sm">
        @foreach($getRecord()->configuration_progress['steps'] as $step => $completed)
            <div class="flex items-center space-x-1">
                @if($completed)
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                @else
                    <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                @endif
                <span class="{{ $completed ? 'text-gray-700 dark:text-gray-300' : 'text-gray-500 dark:text-gray-400' }}">
                    {{ match($step) {
                        'basic_info' => 'Grunddaten',
                        'contact' => 'Kontakt',
                        'hours' => 'Öffnungszeiten',
                        'retell' => 'KI-Agent',
                        'calendar' => 'Kalender',
                        default => ucfirst($step)
                    } }}
                </span>
            </div>
        @endforeach
    </div>
</div>
