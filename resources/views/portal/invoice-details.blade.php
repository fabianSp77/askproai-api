@extends('portal.layouts.app')

@section('title', 'Rechnung ' . $invoice->invoice_number)

@section('content')
<div class="py-12">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
                <!-- Header -->
                <div class="flex justify-between items-start mb-8">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Rechnung</h1>
                        <p class="text-gray-600">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium
                            @if($invoice->status == 'paid') bg-green-100 text-green-800
                            @elseif($invoice->status == 'open') bg-yellow-100 text-yellow-800
                            @elseif($invoice->status == 'overdue') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </div>
                </div>

                <!-- Company & Customer Info -->
                <div class="grid grid-cols-2 gap-8 mb-8">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-2">Von</h3>
                        <p class="text-gray-600">
                            {{ $invoice->company->name }}<br>
                            @if($invoice->branch)
                                {{ $invoice->branch->name }}<br>
                                {{ $invoice->branch->address }}<br>
                                {{ $invoice->branch->postal_code }} {{ $invoice->branch->city }}
                            @endif
                        </p>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 uppercase tracking-wide mb-2">An</h3>
                        <p class="text-gray-600">
                            @php
                                $customerDetails = json_decode($invoice->customer_details, true) ?? [];
                            @endphp
                            {{ $customerDetails['name'] ?? $customer->name }}<br>
                            {{ $customerDetails['email'] ?? $customer->email }}<br>
                            {{ $customerDetails['phone'] ?? $customer->phone }}
                        </p>
                    </div>
                </div>

                <!-- Invoice Details -->
                <div class="grid grid-cols-2 gap-4 mb-8">
                    <div>
                        <p class="text-sm text-gray-600">Rechnungsdatum</p>
                        <p class="text-gray-900">{{ $invoice->invoice_date->format('d.m.Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Fälligkeitsdatum</p>
                        <p class="text-gray-900">{{ $invoice->due_date->format('d.m.Y') }}</p>
                    </div>
                    @if($invoice->period_start && $invoice->period_end)
                    <div class="col-span-2">
                        <p class="text-sm text-gray-600">Leistungszeitraum</p>
                        <p class="text-gray-900">
                            {{ $invoice->period_start->format('d.m.Y') }} - {{ $invoice->period_end->format('d.m.Y') }}
                        </p>
                    </div>
                    @endif
                </div>

                <!-- Line Items -->
                @if($invoice->items->count() > 0 || $invoice->flexibleItems->count() > 0)
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Positionen</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider py-3">
                                    Beschreibung
                                </th>
                                <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider py-3">
                                    Menge
                                </th>
                                <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider py-3">
                                    Einzelpreis
                                </th>
                                <th class="text-right text-xs font-medium text-gray-500 uppercase tracking-wider py-3">
                                    Gesamt
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($invoice->items as $item)
                            <tr>
                                <td class="py-3 text-sm text-gray-900">{{ $item->description }}</td>
                                <td class="py-3 text-sm text-gray-900 text-right">{{ $item->quantity }}</td>
                                <td class="py-3 text-sm text-gray-900 text-right">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                                <td class="py-3 text-sm text-gray-900 text-right">{{ number_format($item->total, 2, ',', '.') }} €</td>
                            </tr>
                            @endforeach
                            @foreach($invoice->flexibleItems as $item)
                            <tr>
                                <td class="py-3 text-sm text-gray-900">{{ $item->description }}</td>
                                <td class="py-3 text-sm text-gray-900 text-right">{{ $item->quantity }}</td>
                                <td class="py-3 text-sm text-gray-900 text-right">{{ number_format($item->unit_price, 2, ',', '.') }} €</td>
                                <td class="py-3 text-sm text-gray-900 text-right">{{ number_format($item->total_amount, 2, ',', '.') }} €</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <!-- Totals -->
                <div class="border-t pt-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-600">Zwischensumme</span>
                        <span class="text-gray-900">{{ number_format($invoice->subtotal, 2, ',', '.') }} €</span>
                    </div>
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-gray-600">MwSt. (19%)</span>
                        <span class="text-gray-900">{{ number_format($invoice->tax_amount, 2, ',', '.') }} €</span>
                    </div>
                    <div class="flex justify-between text-lg font-semibold">
                        <span>Gesamtbetrag</span>
                        <span>{{ number_format($invoice->total, 2, ',', '.') }} €</span>
                    </div>
                </div>

                <!-- Actions -->
                <div class="mt-8 flex justify-end space-x-4">
                    @if($invoice->pdf_url)
                    <a href="{{ route('portal.invoices.download', $invoice) }}" 
                       class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        PDF herunterladen
                    </a>
                    @endif
                    <a href="{{ route('portal.invoices') }}" 
                       class="inline-flex items-center px-4 py-2 bg-gray-300 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-400 active:bg-gray-500 focus:outline-none focus:border-gray-500 focus:ring ring-gray-200 disabled:opacity-25 transition ease-in-out duration-150">
                        Zurück zur Übersicht
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection