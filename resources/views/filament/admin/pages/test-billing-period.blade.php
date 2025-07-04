<x-filament-panels::page>
    <div class="space-y-4">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Billing Period Test</h2>
            
            <div class="mb-4">
                <span class="font-semibold">Status:</span> {{ $status }}
            </div>
            
            @if($billingPeriod)
                <div class="mb-4">
                    <span class="font-semibold">Record ID:</span> {{ $billingPeriod->id }}
                </div>
            @endif
            
            <div class="mt-6">
                <h3 class="font-semibold mb-2">Debug Info:</h3>
                <pre class="bg-gray-100 p-4 rounded">{{ json_encode($debugInfo, JSON_PRETTY_PRINT) }}</pre>
            </div>
            
            @if($billingPeriod)
                <div class="mt-6">
                    <a href="{{ route('filament.admin.resources.billing-periods.edit', ['record' => $billingPeriod->id]) }}" 
                       class="inline-flex items-center px-4 py-2 bg-primary-600 text-white rounded hover:bg-primary-700">
                        Try Edit Link
                    </a>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>