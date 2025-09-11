<x-filament-panels::page>
    <div class="bg-white rounded-lg shadow-sm">
        <div class="p-6">
            <h2 class="text-2xl font-bold mb-4">Test View - Call #{{ $record->id }}</h2>
            <p>Customer: {{ $record->customer_name ?? 'Unknown' }}</p>
            <p>Phone: {{ $record->customer_phone ?? 'Unknown' }}</p>
            <p>Date: {{ $record->created_at->format('Y-m-d H:i:s') }}</p>
        </div>
    </div>
</x-filament-panels::page>