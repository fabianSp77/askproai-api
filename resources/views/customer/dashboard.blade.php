@extends('layouts.customer')

@section('title', 'Dashboard')

@section('content')
<div class="min-h-screen bg-gray-50">
    {{-- Header with Balance Display --}}
    <div class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Willkommen, {{ Auth::user()->name }}</h1>
                    <p class="text-sm text-gray-600">{{ Auth::user()->tenant->name }}</p>
                </div>
                
                {{-- Real-time Balance Widget --}}
                <livewire:customer.balance-widget />
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Quick Actions --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {{-- Top-up Card --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Guthaben aufladen</h3>
                    <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <p class="text-gray-600 mb-4">Laden Sie Ihr Guthaben schnell und sicher auf</p>
                <livewire:customer.quick-topup />
            </div>

            {{-- Recent Calls Card --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Letzte Anrufe</h3>
                    <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                    </svg>
                </div>
                <div class="space-y-2">
                    @forelse($recentCalls as $call)
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">{{ $call->created_at->format('d.m. H:i') }}</span>
                            <span class="font-medium">{{ number_format($call->cost_cents / 100, 2, ',', '.') }} €</span>
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">Keine Anrufe vorhanden</p>
                    @endforelse
                </div>
                <a href="{{ route('customer.calls') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium mt-4 inline-block">Alle anzeigen →</a>
            </div>

            {{-- Auto Top-up Settings Card --}}
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Auto-Aufladung</h3>
                    <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                <livewire:customer.auto-topup-settings />
            </div>
        </div>

        {{-- Main Content Area --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- Transaction History --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-900">Transaktionsverlauf</h2>
                    </div>
                    <div class="p-6">
                        <livewire:customer.transaction-history />
                    </div>
                </div>
            </div>

            {{-- Usage Statistics --}}
            <div>
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-900">Nutzungsstatistiken</h2>
                    </div>
                    <div class="p-6">
                        <livewire:customer.usage-stats />
                    </div>
                </div>

                {{-- Quick Links --}}
                <div class="bg-white rounded-lg shadow mt-6">
                    <div class="px-6 py-4 border-b">
                        <h2 class="text-lg font-semibold text-gray-900">Schnellzugriff</h2>
                    </div>
                    <div class="p-6">
                        <ul class="space-y-3">
                            <li>
                                <a href="{{ route('customer.invoices') }}" class="flex items-center text-gray-700 hover:text-blue-600">
                                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Rechnungen
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('customer.payment-methods') }}" class="flex items-center text-gray-700 hover:text-blue-600">
                                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                    Zahlungsmethoden
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('customer.api-keys') }}" class="flex items-center text-gray-700 hover:text-blue-600">
                                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                    </svg>
                                    API-Schlüssel
                                </a>
                            </li>
                            <li>
                                <a href="{{ route('customer.support') }}" class="flex items-center text-gray-700 hover:text-blue-600">
                                    <svg class="h-5 w-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                    Support
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- SSE Connection Script --}}
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Server-Sent Events for real-time updates
        if (typeof(EventSource) !== "undefined") {
            const source = new EventSource('{{ route('customer.balance.stream') }}');
            
            source.addEventListener('balance-update', function(e) {
                const data = JSON.parse(e.data);
                // Dispatch Livewire event for balance update
                Livewire.emit('balanceUpdated', data);
            });
            
            source.addEventListener('transaction-created', function(e) {
                const data = JSON.parse(e.data);
                // Show notification for new transaction
                Livewire.emit('transactionCreated', data);
            });
            
            source.onerror = function() {
                console.log('SSE connection error, will retry...');
            };
        }
    });
</script>
@endpush
@endsection