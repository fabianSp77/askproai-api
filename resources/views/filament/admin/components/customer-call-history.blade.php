@php
    $call = $getRecord();
    $customer = $call->customer;
    $recentCalls = $customer ? $customer->calls()
        ->where('id', '!=', $call->id)
        ->latest()
        ->limit(5)
        ->get() : collect();
    
    $totalCalls = $customer ? $customer->calls()->count() : 0;
    $avgDuration = $customer ? $customer->calls()->avg('duration') : 0;
    $totalDuration = $customer ? $customer->calls()->sum('duration') : 0;
@endphp

<div class="customer-call-history">
    @if($customer)
        <!-- Stats Summary -->
        <div class="grid grid-cols-3 gap-2 mb-4">
            <div class="text-center p-2 bg-gray-50 dark:bg-gray-800 rounded">
                <div class="text-lg font-semibold text-primary-600">{{ $totalCalls }}</div>
                <div class="text-xs text-gray-500">Total Calls</div>
            </div>
            <div class="text-center p-2 bg-gray-50 dark:bg-gray-800 rounded">
                <div class="text-lg font-semibold text-primary-600">{{ gmdate('i:s', $avgDuration) }}</div>
                <div class="text-xs text-gray-500">Avg Duration</div>
            </div>
            <div class="text-center p-2 bg-gray-50 dark:bg-gray-800 rounded">
                <div class="text-lg font-semibold text-primary-600">{{ gmdate('H:i', $totalDuration) }}</div>
                <div class="text-xs text-gray-500">Total Time</div>
            </div>
        </div>

        <!-- Recent Calls List -->
        @if($recentCalls->count() > 0)
            <div class="space-y-2">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Recent Calls</h4>
                @foreach($recentCalls as $recentCall)
                    <a href="{{ route('filament.admin.resources.ultimate-calls.view', $recentCall) }}" 
                       class="block p-2 bg-gray-50 dark:bg-gray-800 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-sm font-medium">{{ $recentCall->created_at->format('M j, Y') }}</p>
                                <p class="text-xs text-gray-500">{{ $recentCall->created_at->format('g:i A') }} • {{ gmdate('i:s', $recentCall->duration) }}</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if($recentCall->sentiment == 'positive')
                                    <x-heroicon-o-face-smile class="w-4 h-4 text-green-500" />
                                @elseif($recentCall->sentiment == 'negative')
                                    <x-heroicon-o-face-frown class="w-4 h-4 text-red-500" />
                                @else
                                    <x-heroicon-o-minus-circle class="w-4 h-4 text-gray-500" />
                                @endif
                                <x-heroicon-o-chevron-right class="w-4 h-4 text-gray-400" />
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            @if($totalCalls > 5)
                <div class="mt-3 text-center">
                    <a href="{{ route('filament.admin.resources.ultimate-customers.view', $customer) }}" 
                       class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                        View all {{ $totalCalls }} calls →
                    </a>
                </div>
            @endif
        @else
            <p class="text-sm text-gray-500 text-center py-4">No other calls recorded</p>
        @endif
    @else
        <p class="text-sm text-gray-500 text-center py-4">No customer linked to this call</p>
    @endif
</div>