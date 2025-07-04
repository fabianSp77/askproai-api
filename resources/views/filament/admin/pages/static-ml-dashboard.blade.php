@php
    // Get stats directly without Livewire
    $stats = [
        'total_calls' => \DB::table('calls')->count(),
        'with_transcript' => \DB::table('calls')->whereNotNull('transcript')->count(),
        'analyzed' => \DB::table('ml_call_predictions')->count(),
    ];
@endphp

<x-filament-panels::page>
    <div class="fi-page-content">
        <h1 class="text-2xl font-bold mb-6">ML Dashboard (Statisch)</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-900 rounded-lg p-6 shadow">
                <div class="text-3xl font-bold text-primary-600">{{ number_format($stats['total_calls']) }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Gesamte Anrufe</div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 rounded-lg p-6 shadow">
                <div class="text-3xl font-bold text-success-600">{{ number_format($stats['with_transcript']) }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Mit Transkript</div>
            </div>
            
            <div class="bg-white dark:bg-gray-900 rounded-lg p-6 shadow">
                <div class="text-3xl font-bold text-warning-600">{{ number_format($stats['analyzed']) }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Analysiert</div>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 shadow">
            <h2 class="text-lg font-semibold mb-3">Status</h2>
            <p class="text-gray-600 dark:text-gray-400">
                Dies ist eine statische Version des ML Dashboards ohne Livewire-Komponenten.
                Die Seite zeigt die aktuellen Statistiken an, aber interaktive Features sind deaktiviert.
            </p>
            
            @if($stats['with_transcript'] >= 10)
                <div class="mt-4 p-4 bg-green-50 dark:bg-green-900/20 rounded">
                    <p class="text-green-800 dark:text-green-200">
                        ✓ Genug Trainingsdaten vorhanden ({{ $stats['with_transcript'] }} Transkripte)
                    </p>
                </div>
            @else
                <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded">
                    <p class="text-yellow-800 dark:text-yellow-200">
                        ⚠ Mindestens 10 Transkripte werden für das Training benötigt (aktuell: {{ $stats['with_transcript'] }})
                    </p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>