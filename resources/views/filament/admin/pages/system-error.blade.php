<x-filament-panels::page>
    <div class="bg-danger-50 border border-danger-200 rounded-lg p-6">
        <h2 class="text-danger-800 text-xl font-bold mb-4">System Error</h2>
        <p class="text-danger-700 mb-4">Ein Fehler ist aufgetreten. Bitte prüfen Sie die Logs.</p>
        
        @if($debug && isset($error))
            <div class="bg-white rounded-lg p-4 border border-danger-200">
                <pre class="text-sm text-gray-800 whitespace-pre-wrap">{{ $error }}</pre>
            </div>
        @endif
        
        <div class="mt-4">
            <p class="text-sm text-gray-600">Zeitstempel: {{ now()->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</x-filament-panels::page>