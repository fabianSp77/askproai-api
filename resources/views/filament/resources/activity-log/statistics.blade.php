<div class="space-y-4 p-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
            <div class="text-sm text-gray-500 dark:text-gray-400">Heute</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['today'] }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
            <div class="text-sm text-gray-500 dark:text-gray-400">Diese Woche</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['week'] }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
            <div class="text-sm text-gray-500 dark:text-gray-400">Diesen Monat</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['month'] }}</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow">
            <div class="text-sm text-gray-500 dark:text-gray-400">Ø Antwortzeit</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['avg_response'] }} ms</div>
        </div>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4 shadow">
            <div class="text-sm text-red-600 dark:text-red-400">Kritische Ereignisse</div>
            <div class="text-2xl font-bold text-red-700 dark:text-red-300">{{ $stats['critical'] }}</div>
        </div>

        <div class="bg-orange-50 dark:bg-orange-900/20 rounded-lg p-4 shadow">
            <div class="text-sm text-orange-600 dark:text-orange-400">Fehler</div>
            <div class="text-2xl font-bold text-orange-700 dark:text-orange-300">{{ $stats['errors'] }}</div>
        </div>

        <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-4 shadow">
            <div class="text-sm text-yellow-600 dark:text-yellow-400">Langsame Anfragen</div>
            <div class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ $stats['slow_requests'] }}</div>
        </div>

        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 shadow">
            <div class="text-sm text-blue-600 dark:text-blue-400">Performance</div>
            <div class="text-xl font-bold text-blue-700 dark:text-blue-300">
                @if($stats['avg_response'] < 100)
                    <span class="text-green-600">Exzellent</span>
                @elseif($stats['avg_response'] < 500)
                    <span class="text-blue-600">Gut</span>
                @elseif($stats['avg_response'] < 1000)
                    <span class="text-yellow-600">Mittel</span>
                @else
                    <span class="text-red-600">Langsam</span>
                @endif
            </div>
        </div>
    </div>
</div>