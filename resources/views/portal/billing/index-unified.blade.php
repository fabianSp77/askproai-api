@extends('portal.layouts.unified')

@section('page-title', 'Abrechnung')

@section('header-actions')
<a href="{{ route('business.billing.topup') }}" class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition duration-200">
    <i class="fas fa-plus-circle mr-2"></i>
    Guthaben aufladen
</a>
@endsection

@section('content')
<div class="p-6">
    <!-- Balance Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Current Balance -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Aktuelles Guthaben</p>
                    <p class="text-3xl font-bold text-gray-800 mt-1">{{ number_format($currentBalance, 2, ',', '.') }} €</p>
                </div>
                <div class="bg-green-100 p-3 rounded-lg">
                    <i class="fas fa-wallet text-green-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Auto Topup Status -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Auto-Aufladung</p>
                    <p class="text-lg font-semibold mt-1">
                        @if($company->auto_topup_enabled ?? false)
                            <span class="text-green-600">Aktiviert</span>
                        @else
                            <span class="text-gray-600">Deaktiviert</span>
                        @endif
                    </p>
                    @if($company->auto_topup_amount ?? 0 > 0)
                    <p class="text-sm text-gray-500">{{ number_format($company->auto_topup_amount, 2, ',', '.') }} € bei {{ number_format($company->auto_topup_threshold ?? 10, 2, ',', '.') }} €</p>
                    @endif
                </div>
                <div class="bg-blue-100 p-3 rounded-lg">
                    <i class="fas fa-sync text-blue-600 text-xl"></i>
                </div>
            </div>
        </div>

        <!-- Next Invoice -->
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium">Nächste Rechnung</p>
                    <p class="text-lg font-semibold mt-1">{{ \Carbon\Carbon::now()->endOfMonth()->format('d.m.Y') }}</p>
                    <p class="text-sm text-gray-500">Geschätzt: {{ number_format(0, 2, ',', '.') }} €</p>
                </div>
                <div class="bg-purple-100 p-3 rounded-lg">
                    <i class="fas fa-file-invoice text-purple-600 text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="p-6 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-gray-800">Letzte Transaktionen</h2>
                <a href="{{ route('business.billing.invoices') }}" class="text-blue-600 hover:text-blue-700 text-sm">
                    Alle anzeigen →
                </a>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Beschreibung</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Typ</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Betrag</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Saldo</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($transactions as $transaction)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $transaction->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            {{ $transaction->description }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $typeColors = [
                                    'topup' => 'bg-green-100 text-green-800',
                                    'charge' => 'bg-red-100 text-red-800',
                                    'refund' => 'bg-blue-100 text-blue-800',
                                    'adjustment' => 'bg-yellow-100 text-yellow-800'
                                ];
                                $typeLabels = [
                                    'topup' => 'Aufladung',
                                    'charge' => 'Verbrauch',
                                    'refund' => 'Erstattung',
                                    'adjustment' => 'Anpassung'
                                ];
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $typeColors[$transaction->type] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $typeLabels[$transaction->type] ?? $transaction->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">
                            @if($transaction->type === 'topup' || $transaction->type === 'refund')
                                <span class="text-green-600">+{{ number_format($transaction->amount, 2, ',', '.') }} €</span>
                            @else
                                <span class="text-red-600">-{{ number_format(abs($transaction->amount), 2, ',', '.') }} €</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                            {{ number_format($transaction->balance_after, 2, ',', '.') }} €
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-receipt text-4xl mb-2"></i>
                                <p>Keine Transaktionen vorhanden</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Topups -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100">
            <h2 class="text-xl font-semibold text-gray-800">Letzte Aufladungen</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Datum</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Betrag</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Zahlungsmethode</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aktionen</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($topups as $topup)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $topup->created_at->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ number_format($topup->amount, 2, ',', '.') }} €
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ ucfirst($topup->payment_method ?? 'Kreditkarte') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusColors = [
                                    'completed' => 'bg-green-100 text-green-800',
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'failed' => 'bg-red-100 text-red-800'
                                ];
                                $statusLabels = [
                                    'completed' => 'Abgeschlossen',
                                    'pending' => 'Ausstehend',
                                    'failed' => 'Fehlgeschlagen'
                                ];
                            @endphp
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $statusColors[$topup->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$topup->status] ?? $topup->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            @if($topup->invoice_url)
                            <a href="{{ $topup->invoice_url }}" target="_blank" class="text-blue-600 hover:text-blue-900">
                                <i class="fas fa-download"></i> Rechnung
                            </a>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-credit-card text-4xl mb-2"></i>
                                <p>Keine Aufladungen vorhanden</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Optional: Implement auto-topup toggle
function toggleAutoTopup() {
    // Implementation here
}
</script>
@endpush
@endsection