@extends('portal.layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-semibold text-gray-900">Transaktionshistorie</h1>
            <p class="mt-1 text-sm text-gray-600">
                Übersicht über alle Guthabenaufladungen und Verbrauch
            </p>
        </div>

        <!-- Filters -->
        <div class="bg-white shadow sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:p-6">
                <form method="GET" action="{{ route('business.billing.transactions') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700">Typ</label>
                        <select id="type" name="type" 
                                class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            <option value="">Alle Typen</option>
                            <option value="topup" {{ request('type') == 'topup' ? 'selected' : '' }}>Aufladung</option>
                            <option value="charge" {{ request('type') == 'charge' ? 'selected' : '' }}>Verbrauch</option>
                            <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Anpassung</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700">Von</label>
                        <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}"
                               class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    </div>
                    
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700">Bis</label>
                        <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}"
                               class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                    </div>
                    
                    <div class="flex items-end">
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Filtern
                        </button>
                        <a href="{{ route('business.billing.transactions') }}" 
                           class="ml-2 inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            Zurücksetzen
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Transactions Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <ul class="divide-y divide-gray-200">
                @forelse($transactions as $transaction)
                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        @if($transaction->type === 'topup')
                                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                <svg class="h-6 w-6 text-green-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                                </svg>
                                            </div>
                                        @elseif($transaction->type === 'charge')
                                            <div class="h-10 w-10 rounded-full bg-red-100 flex items-center justify-center">
                                                <svg class="h-6 w-6 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                                                </svg>
                                            </div>
                                        @else
                                            <div class="h-10 w-10 rounded-full bg-gray-100 flex items-center justify-center">
                                                <svg class="h-6 w-6 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            {{ $transaction->description }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $transaction->created_at->format('d.m.Y H:i') }}
                                            @if($transaction->reference_id)
                                                • Ref: {{ $transaction->reference_id }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="text-right mr-4">
                                        <div class="text-sm font-medium {{ $transaction->amount > 0 ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount, 2, ',', '.') }} €
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            Saldo: {{ number_format($transaction->balance_after, 2, ',', '.') }} €
                                        </div>
                                    </div>
                                    @if($transaction->type === 'topup' && $transaction->reference_id)
                                        <a href="{{ route('business.billing.transaction.invoice', $transaction->id) }}" 
                                           class="text-indigo-600 hover:text-indigo-900">
                                            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-4 sm:px-6">
                        <p class="text-sm text-gray-500 text-center">Keine Transaktionen vorhanden</p>
                    </li>
                @endforelse
            </ul>
            
            @if($transactions->hasPages())
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    {{ $transactions->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <!-- Summary -->
        @if($transactions->count() > 0)
        <div class="mt-6 bg-white shadow sm:rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                    Zusammenfassung (gefilterte Ansicht)
                </h3>
                
                @php
                    $summary = [
                        'total_credits' => $transactions->where('type', 'topup')->sum('amount'),
                        'total_debits' => abs($transactions->where('type', 'charge')->sum('amount')),
                        'total_adjustments' => $transactions->where('type', 'adjustment')->sum('amount'),
                    ];
                    $summary['net_change'] = $summary['total_credits'] - $summary['total_debits'] + $summary['total_adjustments'];
                @endphp
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500 uppercase tracking-wide mb-1">Aufladungen</p>
                        <p class="text-2xl font-semibold text-green-600">
                            +{{ number_format($summary['total_credits'], 2, ',', '.') }} €
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500 uppercase tracking-wide mb-1">Verbrauch</p>
                        <p class="text-2xl font-semibold text-red-600">
                            -{{ number_format($summary['total_debits'], 2, ',', '.') }} €
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500 uppercase tracking-wide mb-1">Anpassungen</p>
                        <p class="text-2xl font-semibold {{ $summary['total_adjustments'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $summary['total_adjustments'] > 0 ? '+' : '' }}{{ number_format($summary['total_adjustments'], 2, ',', '.') }} €
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-sm text-gray-500 uppercase tracking-wide mb-1">Netto-Änderung</p>
                        <p class="text-2xl font-semibold {{ $summary['net_change'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $summary['net_change'] > 0 ? '+' : '' }}{{ number_format($summary['net_change'], 2, ',', '.') }} €
                        </p>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection