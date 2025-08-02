<x-filament-panels::page>
    <div class="p-4 bg-white rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Test Minimal Dashboard</h2>
        <p>If you can see this, the basic page setup works!</p>
        <p>Test Data: {{ $testData }}</p>
        <p>Current Time: {{ now()->format('H:i:s') }}</p>
    </div>
</x-filament-panels::page>