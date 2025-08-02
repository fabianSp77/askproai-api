@extends('portal.layouts.unified')

@section('page-title', 'Abrechnung')

@section('content')
<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-2xl font-bold leading-7 text-gray-900 sm:text-3xl sm:truncate">
                    Abrechnung & Guthaben
                </h2>
            </div>
        </div>

        <!-- Balance Card -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Aktuelles Guthaben</h3>
            </div>
            <div class="px-6 py-8 text-center">
                <div class="text-5xl font-bold text-gray-900">
                    {{ number_format($data['balance'], 2, ',', '.') }} €
                </div>
                <p class="text-gray-500 mt-2">Verfügbares Guthaben</p>
                <a href="{{ route('business.billing.topup') }}" 
                   class="mt-6 inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus-circle mr-2"></i>
                    Guthaben aufladen
                </a>
            </div>
        </div>

        <!-- Auto-Topup Status -->
        <div class="bg-white shadow rounded-lg mb-6 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">Automatische Aufladung</h3>
                    <p class="text-gray-500 text-sm mt-1">
                        @if($data['auto_topup_enabled'])
                            Aktiv - Aufladung von {{ $data['auto_topup_amount'] }}€ bei Guthaben unter {{ $data['auto_topup_threshold'] }}€
                        @else
                            Deaktiviert
                        @endif
                    </p>
                </div>
                <div>
                    @if($data['auto_topup_enabled'])
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i>
                            Aktiv
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                            <i class="fas fa-times-circle mr-1"></i>
                            Inaktiv
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Letzte Transaktionen</h3>
                <a href="{{ route('business.billing.invoices') }}" class="text-blue-600 hover:text-blue-800 text-sm">
                    Alle anzeigen →
                </a>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse($transactions as $transaction)
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-900">
                                    @if($transaction->type == 'topup')
                                        Guthabenaufladung
                                    @elseif($transaction->type == 'charge')
                                        Anrufgebühr
                                    @else
                                        {{ ucfirst($transaction->type) }}
                                    @endif
                                </p>
                                <p class="text-sm text-gray-500">
                                    {{ $transaction->created_at->format('d.m.Y H:i') }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium {{ $transaction->type == 'topup' ? 'text-green-600' : 'text-gray-900' }}">
                                    {{ $transaction->type == 'topup' ? '+' : '-' }}{{ number_format(abs($transaction->amount), 2, ',', '.') }} €
                                </p>
                                @if($transaction->invoice_url)
                                    <a href="{{ $transaction->invoice_url }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-800">
                                        Rechnung
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-gray-500">
                        <p>Keine Transaktionen vorhanden</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection