@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-7xl mx-auto">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Billing Dashboard</h1>

        <!-- Balance Card -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="text-xl font-semibold text-gray-700 mb-2">Current Balance</h2>
                    <p class="text-4xl font-bold text-gray-900">
                        {{ number_format(auth()->user()->tenant->balance_cents / 100, 2) }} €
                    </p>
                    @if(auth()->user()->tenant->balance_cents < 1000)
                        <p class="text-red-600 text-sm mt-2">
                            <svg class="inline w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                            Low balance warning
                        </p>
                    @endif
                </div>
                <div>
                    <a href="{{ route('billing.checkout') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Top Up Balance
                    </a>
                </div>
            </div>
        </div>

        <!-- Pricing Plan -->
        @if(auth()->user()->tenant->pricingPlan)
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Current Pricing Plan</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Plan Name</p>
                    <p class="text-lg font-semibold">{{ auth()->user()->tenant->pricingPlan->name }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Price per Minute</p>
                    <p class="text-lg font-semibold">{{ number_format(auth()->user()->tenant->pricingPlan->price_per_minute_cents / 100, 2) }} €</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Price per API Call</p>
                    <p class="text-lg font-semibold">{{ number_format(auth()->user()->tenant->pricingPlan->price_per_call_cents / 100, 2) }} €</p>
                </div>
            </div>
        </div>
        @endif

        <!-- Usage Statistics -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-4">Usage This Month</h2>
            @php
                $currentMonth = now()->startOfMonth();
                $transactions = auth()->user()->tenant->transactions()
                    ->where('type', 'usage')
                    ->where('created_at', '>=', $currentMonth)
                    ->get();
                $totalUsage = abs($transactions->sum('amount_cents')) / 100;
                $callCount = $transactions->filter(fn($t) => str_contains($t->description, 'API Call'))->count();
                $phoneMinutes = $transactions->filter(fn($t) => str_contains($t->description, 'Phone Call'))
                    ->map(fn($t) => (int) filter_var($t->description, FILTER_SANITIZE_NUMBER_INT))
                    ->sum();
            @endphp
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-600">Total Spent</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($totalUsage, 2) }} €</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">API Calls</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $callCount }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Phone Minutes</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $phoneMinutes }}</p>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-semibold text-gray-700">Recent Transactions</h2>
                <a href="{{ route('billing.transactions') }}" class="text-blue-600 hover:text-blue-800">View All →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach(auth()->user()->tenant->transactions()->latest()->take(10)->get() as $transaction)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $transaction->created_at->format('d.m.Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($transaction->type === 'topup') bg-green-100 text-green-800
                                    @elseif($transaction->type === 'usage') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($transaction->type) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-900">
                                {{ $transaction->description }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium
                                @if($transaction->amount_cents > 0) text-green-600
                                @else text-red-600
                                @endif">
                                {{ $transaction->amount_cents > 0 ? '+' : '' }}{{ number_format($transaction->amount_cents / 100, 2) }} €
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                {{ number_format($transaction->balance_after_cents / 100, 2) }} €
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection