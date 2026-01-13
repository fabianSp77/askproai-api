<div class="space-y-6">
    {{-- Header --}}
    <div class="flex justify-between items-start">
        <div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $record->partnerCompany->name }}</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $record->partnerCompany->partner_billing_address ?? 'Keine Adresse hinterlegt' }}</p>
        </div>
        <div class="text-right">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                {{ $record->getStatusLabel() }}
            </span>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                {{ $record->billing_period_start->format('d.m.Y') }} - {{ $record->billing_period_end->format('d.m.Y') }}
            </p>
        </div>
    </div>

    {{-- Line Items --}}
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Beschreibung
                    </th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Unternehmen
                    </th>
                    <th scope="col" class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        Betrag
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($record->items as $item)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                            {{ $item->description }}
                            @if($item->description_detail)
                                <span class="text-gray-500 dark:text-gray-400">({{ $item->description_detail }})</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ $item->company?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">
                            {{ number_format($item->amount, 2, ',', '.') }} €
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                            Keine Positionen vorhanden
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Totals --}}
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
        <div class="flex justify-between text-sm">
            <span class="text-gray-500 dark:text-gray-400">Zwischensumme (netto)</span>
            <span class="text-gray-900 dark:text-white">{{ number_format($record->subtotal, 2, ',', '.') }} €</span>
        </div>
        @if($record->discount_cents > 0)
            <div class="flex justify-between text-sm text-green-600 dark:text-green-400">
                <span>
                    Rabatt
                    @if($record->discount_description)
                        <span class="text-gray-500 dark:text-gray-400">({{ $record->discount_description }})</span>
                    @endif
                </span>
                <span>-{{ number_format($record->discount_cents / 100, 2, ',', '.') }} €</span>
            </div>
        @endif
        <div class="flex justify-between text-sm">
            <span class="text-gray-500 dark:text-gray-400">MwSt. ({{ $record->tax_rate }}%)</span>
            <span class="text-gray-900 dark:text-white">{{ number_format($record->tax, 2, ',', '.') }} €</span>
        </div>
        <div class="flex justify-between text-lg font-bold border-t border-gray-200 dark:border-gray-700 pt-2 mt-2">
            <span class="text-gray-900 dark:text-white">Gesamtbetrag (brutto)</span>
            <span class="text-primary-600 dark:text-primary-400">{{ number_format($record->total, 2, ',', '.') }} €</span>
        </div>
    </div>

    {{-- Info --}}
    <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
        Rechnungsnummer: {{ $record->invoice_number }} | Erstellt am: {{ $record->created_at->format('d.m.Y H:i') }}
    </div>
</div>
