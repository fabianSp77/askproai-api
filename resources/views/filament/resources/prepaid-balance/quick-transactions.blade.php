@php
    use Carbon\Carbon;
@endphp

<div class="space-y-4 p-4">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 dark:border-gray-700">
                    <th class="text-left py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Datum</th>
                    <th class="text-left py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Typ</th>
                    <th class="text-left py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Beschreibung</th>
                    <th class="text-right py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Betrag</th>
                    <th class="text-right py-2 px-3 font-medium text-gray-700 dark:text-gray-300">Saldo</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-800">
                        <td class="py-2 px-3 text-gray-600 dark:text-gray-400">
                            {{ $transaction->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="py-2 px-3">
                            @if($transaction->type === 'credit')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-200">
                                    Aufladung
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-200">
                                    Verbrauch
                                </span>
                            @endif
                        </td>
                        <td class="py-2 px-3 text-gray-900 dark:text-gray-100">
                            {{ $transaction->description }}
                        </td>
                        <td class="py-2 px-3 text-right font-medium">
                            <span class="{{ $transaction->type === 'credit' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $transaction->type === 'credit' ? '+' : '-' }}{{ number_format($transaction->amount, 2) }}€
                            </span>
                        </td>
                        <td class="py-2 px-3 text-right font-medium text-gray-900 dark:text-gray-100">
                            {{ number_format($transaction->balance_after, 2) }}€
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="py-8 text-center text-gray-500 dark:text-gray-400">
                            Keine Transaktionen vorhanden
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    @if($transactions->count() >= 50)
        <div class="text-center text-sm text-gray-500 dark:text-gray-400 pt-4 border-t border-gray-200 dark:border-gray-700">
            Es werden nur die letzten 50 Transaktionen angezeigt. 
            <a href="{{ \App\Filament\Admin\Resources\PrepaidBalanceResource::getUrl('view', ['record' => $record]) }}" 
               class="text-primary-600 hover:text-primary-700 dark:text-primary-400">
                Vollständige Ansicht öffnen
            </a>
        </div>
    @endif
</div>