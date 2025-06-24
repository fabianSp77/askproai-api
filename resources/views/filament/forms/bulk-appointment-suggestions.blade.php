<div class="space-y-4">
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                    KI-Terminvorschläge
                </h3>
                <div class="mt-2 text-sm text-blue-700 dark:text-blue-300">
                    <p>Basierend auf den Anrufinhalten wurden folgende Termine vorgeschlagen:</p>
                </div>
            </div>
        </div>
    </div>

    <div class="space-y-3">
        @php
            // Mock data for demonstration
            $suggestions = [
                [
                    'customer' => 'Max Mustermann',
                    'date' => now()->addDays(2)->format('d.m.Y'),
                    'time' => '14:00',
                    'service' => 'Beratungsgespräch',
                    'confidence' => 95
                ],
                [
                    'customer' => 'Anna Schmidt',
                    'date' => now()->addDays(3)->format('d.m.Y'),
                    'time' => '10:00',
                    'service' => 'Erstberatung',
                    'confidence' => 87
                ],
                [
                    'customer' => 'Peter Weber',
                    'date' => now()->addDays(1)->format('d.m.Y'),
                    'time' => '16:30',
                    'service' => 'Nachbesprechung',
                    'confidence' => 78
                ]
            ];
        @endphp

        @foreach($suggestions as $suggestion)
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $suggestion['customer'] }}
                        </h4>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            {{ $suggestion['service'] }} - {{ $suggestion['date'] }} um {{ $suggestion['time'] }} Uhr
                        </p>
                    </div>
                    <div class="ml-4">
                        <div class="flex items-center">
                            <span class="text-xs text-gray-500 dark:text-gray-400 mr-2">Konfidenz:</span>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $suggestion['confidence'] >= 90 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 
                                   ($suggestion['confidence'] >= 80 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300' : 
                                   'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300') }}">
                                {{ $suggestion['confidence'] }}%
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3 flex items-center gap-2">
                    <label class="flex items-center">
                        <input type="checkbox" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500" checked>
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Termin erstellen</span>
                    </label>
                </div>
            </div>
        @endforeach
    </div>

    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
        <div class="flex items-start">
            <svg class="h-4 w-4 text-gray-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <p class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                Die KI hat die Anrufinhalte analysiert und mögliche Terminwünsche erkannt. 
                Überprüfen Sie die Vorschläge und passen Sie diese bei Bedarf an.
            </p>
        </div>
    </div>
</div>