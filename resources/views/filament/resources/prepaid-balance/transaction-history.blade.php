@php
    use App\Models\BalanceTransaction;
    use Carbon\Carbon;
    
    $record = $getRecord();
    $transactions = BalanceTransaction::where('company_id', $record->company_id)
        ->latest()
        ->limit(50)
        ->get();
        
    $totalCredits = $transactions->where('type', 'credit')->sum('amount');
    $totalDebits = $transactions->where('type', 'debit')->sum('amount');
@endphp

<div class="space-y-4">
    {{-- Quick Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <div class="text-sm text-green-600 dark:text-green-400">Aufladungen (30 Tage)</div>
            <div class="text-2xl font-bold text-green-700 dark:text-green-300">
                +{{ number_format($record->transactions()->where('type', 'credit')->where('created_at', '>=', now()->subDays(30))->sum('amount'), 2) }}€
            </div>
        </div>
        
        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
            <div class="text-sm text-red-600 dark:text-red-400">Verbrauch (30 Tage)</div>
            <div class="text-2xl font-bold text-red-700 dark:text-red-300">
                -{{ number_format($record->transactions()->where('type', 'debit')->where('created_at', '>=', now()->subDays(30))->sum('amount'), 2) }}€
            </div>
        </div>
        
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
            <div class="text-sm text-blue-600 dark:text-blue-400">Durchschnittl. Verbrauch/Tag</div>
            <div class="text-2xl font-bold text-blue-700 dark:text-blue-300">
                {{ number_format($record->transactions()->where('type', 'debit')->where('created_at', '>=', now()->subDays(30))->sum('amount') / 30, 2) }}€
            </div>
        </div>
    </div>
    
    {{-- Transaction Table --}}
    <div class="overflow-x-auto">
        <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Datum
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Typ
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Beschreibung
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Referenz
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Betrag
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Saldo
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                            {{ $transaction->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            @if($transaction->type === 'credit')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                    <x-heroicon-m-arrow-down-circle class="w-3 h-3 mr-1" />
                                    Aufladung
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                    <x-heroicon-m-arrow-up-circle class="w-3 h-3 mr-1" />
                                    Verbrauch
                                </span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-gray-100">
                            {{ $transaction->description }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            @if($transaction->reference_type && $transaction->reference_id)
                                <span class="text-xs">
                                    {{ class_basename($transaction->reference_type) }} #{{ $transaction->reference_id }}
                                </span>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium">
                            <span class="{{ $transaction->type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }}€
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-gray-100">
                            {{ number_format($transaction->balance_after, 2) }}€
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                            Keine Transaktionen vorhanden
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($transactions->count() >= 50)
        <div class="text-center text-sm text-gray-500 dark:text-gray-400 mt-4">
            Es werden die letzten 50 Transaktionen angezeigt. Für eine vollständige Übersicht nutzen Sie bitte die Export-Funktion.
        </div>
    @endif
</div>