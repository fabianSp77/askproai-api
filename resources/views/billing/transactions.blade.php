@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Transaction History</h1>
            <a href="{{ route('billing.index') }}" class="text-blue-600 hover:text-blue-800">← Back to Billing</a>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <form method="GET" action="{{ route('billing.transactions') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                    <select name="type" class="w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">All Types</option>
                        <option value="topup" {{ request('type') === 'topup' ? 'selected' : '' }}>Top Up</option>
                        <option value="usage" {{ request('type') === 'usage' ? 'selected' : '' }}>Usage</option>
                        <option value="refund" {{ request('type') === 'refund' ? 'selected' : '' }}>Refund</option>
                        <option value="adjustment" {{ request('type') === 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                        Filter
                    </button>
                    <a href="{{ route('billing.transactions') }}" class="ml-2 px-4 py-2 bg-gray-200 text-gray-700 font-semibold rounded-lg hover:bg-gray-300 transition">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            @php
                $query = auth()->user()->tenant->transactions();
                
                if (request('type')) {
                    $query->where('type', request('type'));
                }
                if (request('from')) {
                    $query->where('created_at', '>=', request('from') . ' 00:00:00');
                }
                if (request('to')) {
                    $query->where('created_at', '<=', request('to') . ' 23:59:59');
                }
                
                $transactions = $query->orderBy('created_at', 'desc')->paginate(20);
            @endphp

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance Before</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance After</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($transactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                #{{ $transaction->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $transaction->created_at->format('d.m.Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($transaction->type === 'topup') bg-green-100 text-green-800
                                    @elseif($transaction->type === 'usage') bg-blue-100 text-blue-800
                                    @elseif($transaction->type === 'refund') bg-yellow-100 text-yellow-800
                                    @elseif($transaction->type === 'adjustment') bg-purple-100 text-purple-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($transaction->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ $transaction->description }}
                                @if($transaction->metadata)
                                    <button onclick="showMetadata({{ json_encode($transaction->metadata) }})" class="ml-2 text-blue-600 hover:text-blue-800">
                                        <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </button>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium
                                @if($transaction->amount_cents > 0) text-green-600
                                @else text-red-600
                                @endif">
                                {{ $transaction->amount_cents > 0 ? '+' : '' }}{{ number_format($transaction->amount_cents / 100, 2) }} €
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600">
                                {{ number_format($transaction->balance_before_cents / 100, 2) }} €
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900">
                                {{ number_format($transaction->balance_after_cents / 100, 2) }} €
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                No transactions found for the selected criteria.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($transactions->hasPages())
            <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                {{ $transactions->withQueryString()->links() }}
            </div>
            @endif
        </div>

        <!-- Summary -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6">
            <h3 class="text-lg font-semibold text-gray-700 mb-4">Summary</h3>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                @php
                    $summary = [
                        'topups' => $transactions->where('type', 'topup')->sum('amount_cents') / 100,
                        'usage' => abs($transactions->where('type', 'usage')->sum('amount_cents')) / 100,
                        'refunds' => $transactions->where('type', 'refund')->sum('amount_cents') / 100,
                        'count' => $transactions->count()
                    ];
                @endphp
                <div>
                    <p class="text-sm text-gray-600">Total Top Ups</p>
                    <p class="text-xl font-bold text-green-600">+{{ number_format($summary['topups'], 2) }} €</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Total Usage</p>
                    <p class="text-xl font-bold text-red-600">-{{ number_format($summary['usage'], 2) }} €</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Total Refunds</p>
                    <p class="text-xl font-bold text-yellow-600">+{{ number_format($summary['refunds'], 2) }} €</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Transactions</p>
                    <p class="text-xl font-bold text-gray-900">{{ $summary['count'] }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function showMetadata(metadata) {
    alert(JSON.stringify(metadata, null, 2));
}
</script>
@endsection