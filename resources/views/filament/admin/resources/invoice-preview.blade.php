<div class="bg-white p-8 max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="flex justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">RECHNUNG</h1>
            <p class="text-gray-600 mt-2">{{ $invoice->invoice_number }}</p>
        </div>
        <div class="text-right">
            @if($invoice->company->logo)
                <img src="{{ $invoice->company->logo }}" alt="{{ $invoice->company->name }}" class="h-16 ml-auto mb-4">
            @else
                <h2 class="text-2xl font-bold text-gray-800">{{ $invoice->company->name }}</h2>
            @endif
        </div>
    </div>

    {{-- Company & Customer Info --}}
    <div class="grid grid-cols-2 gap-8 mb-8">
        {{-- From --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-600 uppercase mb-2">Von</h3>
            <div class="text-gray-800">
                <p class="font-semibold">{{ $invoice->company->name }}</p>
                <p>{{ $invoice->company->address }}</p>
                <p>{{ $invoice->company->postal_code }} {{ $invoice->company->city }}</p>
                @if($invoice->company->tax_id)
                    <p class="mt-2">Steuernummer: {{ $invoice->company->tax_id }}</p>
                @endif
                @if($invoice->company->vat_id)
                    <p>USt-IdNr.: {{ $invoice->company->vat_id }}</p>
                @endif
            </div>
        </div>

        {{-- To --}}
        <div>
            <h3 class="text-sm font-semibold text-gray-600 uppercase mb-2">An</h3>
            <div class="text-gray-800">
                {{-- Use company info since we're billing companies, not individual customers --}}
                <p class="font-semibold">{{ $invoice->company->name }}</p>
                <p>{{ $invoice->company->address ?? '' }}</p>
                <p>{{ $invoice->company->postal_code ?? '' }} {{ $invoice->company->city ?? '' }}</p>
                @if($invoice->company->vat_id && $invoice->company->vat_id !== $invoice->company->vat_id)
                    <p class="mt-2">USt-IdNr.: {{ $invoice->company->vat_id }}</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Invoice Details --}}
    <div class="grid grid-cols-3 gap-4 mb-8 bg-gray-50 p-4 rounded">
        <div>
            <p class="text-sm text-gray-600">Rechnungsdatum</p>
            <p class="font-semibold">{{ $invoice->invoice_date->format('d.m.Y') }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Fälligkeitsdatum</p>
            <p class="font-semibold">{{ $invoice->due_date->format('d.m.Y') }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-600">Zahlungsbedingungen</p>
            <p class="font-semibold">
                @switch($invoice->payment_terms ?? 'net30')
                    @case('due_on_receipt')
                        Sofort fällig
                        @break
                    @case('net15')
                        15 Tage netto
                        @break
                    @case('net30')
                        30 Tage netto
                        @break
                    @case('net60')
                        60 Tage netto
                        @break
                    @default
                        30 Tage netto
                @endswitch
            </p>
        </div>
    </div>

    {{-- Period --}}
    @if($invoice->period_start && $invoice->period_end)
        <div class="mb-6">
            <p class="text-sm text-gray-600">Leistungszeitraum: {{ $invoice->period_start->format('d.m.Y') }} - {{ $invoice->period_end->format('d.m.Y') }}</p>
        </div>
    @endif

    {{-- Line Items --}}
    <table class="w-full mb-8">
        <thead>
            <tr class="border-b-2 border-gray-300">
                <th class="text-left py-2">Beschreibung</th>
                <th class="text-right py-2">Menge</th>
                <th class="text-right py-2">Einheit</th>
                <th class="text-right py-2">Einzelpreis</th>
                <th class="text-right py-2">Steuersatz</th>
                <th class="text-right py-2">Betrag</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoice->flexibleItems ?? [] as $item)
                <tr class="border-b border-gray-200">
                    <td class="py-2">
                        {{ $item->description }}
                        @if($item->period_start && $item->period_end)
                            <br><span class="text-sm text-gray-600">
                                ({{ $item->period_start->format('d.m.Y') }} - {{ $item->period_end->format('d.m.Y') }})
                            </span>
                        @endif
                    </td>
                    <td class="text-right py-2">{{ number_format($item->quantity, 2, ',', '.') }}</td>
                    <td class="text-right py-2">{{ $item->unit }}</td>
                    <td class="text-right py-2">€ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                    <td class="text-right py-2">{{ number_format($item->tax_rate, 0) }}%</td>
                    <td class="text-right py-2">€ {{ number_format($item->amount, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-gray-500">Keine Positionen vorhanden</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totals --}}
    <div class="flex justify-end mb-8">
        <div class="w-64">
            <div class="flex justify-between py-2">
                <span>Zwischensumme:</span>
                <span>€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</span>
            </div>
            @if(!$invoice->company->is_small_business)
                <div class="flex justify-between py-2">
                    <span>MwSt. (19%):</span>
                    <span>€ {{ number_format($invoice->tax_amount, 2, ',', '.') }}</span>
                </div>
            @endif
            <div class="flex justify-between py-2 font-bold text-lg border-t-2 border-gray-300">
                <span>Gesamtbetrag:</span>
                <span>€ {{ number_format($invoice->total, 2, ',', '.') }}</span>
            </div>
        </div>
    </div>

    {{-- Tax Note --}}
    @if($invoice->tax_note)
        <div class="bg-yellow-50 p-4 rounded mb-6">
            <p class="text-sm">{{ $invoice->tax_note }}</p>
        </div>
    @endif

    {{-- Footer --}}
    <div class="border-t pt-6 text-sm text-gray-600">
        <p class="mb-2">Vielen Dank für Ihr Vertrauen in AskProAI.</p>
        
        @if($invoice->company->bank_name)
            <div class="mt-4">
                <p class="font-semibold">Bankverbindung:</p>
                <p>{{ $invoice->company->bank_name }}</p>
                <p>IBAN: {{ $invoice->company->iban }}</p>
                <p>BIC: {{ $invoice->company->bic }}</p>
            </div>
        @endif
    </div>

    {{-- Status Badge --}}
    <div class="absolute top-4 right-4">
        @switch($invoice->status)
            @case('draft')
                <span class="px-3 py-1 bg-gray-100 text-gray-800 rounded-full text-sm font-semibold">ENTWURF</span>
                @break
            @case('paid')
                <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-semibold">BEZAHLT</span>
                @break
            @case('overdue')
                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold">ÜBERFÄLLIG</span>
                @break
            @case('cancelled')
                <span class="px-3 py-1 bg-red-100 text-red-800 rounded-full text-sm font-semibold">STORNIERT</span>
                @break
        @endswitch
    </div>
</div>