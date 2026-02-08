{{--
    Premium Invoices List Widget
    Recent invoices with payment score progress bar
--}}
<x-filament-widgets::widget>
    <div class="premium-card">
        @php
            $paymentScore = $this->calculatePaymentScore();
            $invoices = $this->getRecentInvoices();
        @endphp

        {{-- Header with Payment Score --}}
        <div class="mb-4">
            <div class="flex justify-between items-center mb-2">
                <span class="premium-widget-title">RECHNUNGEN</span>
                <span class="text-sm text-white font-medium">{{ $paymentScore['score'] }}%</span>
            </div>

            {{-- Progress Bar --}}
            <div class="premium-progress">
                <div class="premium-progress-bar premium-progress-gradient" style="width: {{ $paymentScore['score'] }}%"></div>
            </div>

            <div class="flex justify-between mt-2 text-xs">
                <span class="premium-text-muted">{{ $paymentScore['paid'] }} von {{ $paymentScore['total'] }} bezahlt</span>
            </div>
        </div>

        {{-- Invoice List --}}
        @if($invoices->isNotEmpty())
            <div class="space-y-0">
                @foreach($invoices as $invoice)
                    <div class="premium-invoice-item">
                        <div class="premium-invoice-info">
                            <div class="premium-invoice-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-zinc-400">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                            </div>
                            <div class="premium-invoice-details">
                                <span class="premium-invoice-number">{{ $invoice['number'] }}</span>
                                <span class="premium-invoice-date">{{ $invoice['date'] }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1">
                            <span class="premium-invoice-amount">{{ $invoice['amount'] }}</span>
                            @include('filament.widgets.premium.components.status-badge', ['status' => $invoice['status']])
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- View All Link --}}
            @if(Route::has('filament.admin.resources.invoices.index'))
                <a href="{{ route('filament.admin.resources.invoices.index') }}" class="premium-view-all">
                    <span>Alle Rechnungen</span>
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </a>
            @endif
        @else
            <div class="text-center py-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3 text-zinc-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                    <polyline points="14 2 14 8 20 8"></polyline>
                </svg>
                <p class="premium-text-muted text-sm">Keine Rechnungen vorhanden</p>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>
