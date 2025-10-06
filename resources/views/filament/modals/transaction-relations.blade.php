<div class="p-4 space-y-4">
    @php
        $trans = $transaction;
    @endphp

    {{-- Transaction Overview --}}
    <div class="bg-gray-50 rounded-lg p-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wider">Transaktions-ID</div>
                <div class="font-semibold text-gray-900">#{{ $trans->id }}</div>
            </div>
            <div>
                <div class="text-xs text-gray-500 uppercase tracking-wider">Betrag</div>
                <div class="font-semibold text-lg">
                    @if($trans->amount < 0)
                        <span class="text-red-600">{{ number_format($trans->amount, 2, ',', '.') }} €</span>
                    @else
                        <span class="text-green-600">+{{ number_format($trans->amount, 2, ',', '.') }} €</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Customer Information --}}
    @if($trans->customer)
        <div class="space-y-2">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Kunde</h3>
            <div class="bg-white border border-gray-200 rounded-lg p-3">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="font-medium text-gray-900">
                            {{ $trans->customer->full_name ?? $trans->customer->name }}
                        </div>
                        @if($trans->customer->email)
                            <div class="text-sm text-gray-500">{{ $trans->customer->email }}</div>
                        @endif
                        @if($trans->customer->phone)
                            <div class="text-sm text-gray-500">{{ $trans->customer->phone }}</div>
                        @endif
                    </div>
                    <div class="text-right">
                        <div class="text-xs text-gray-500">Kunden-Nr.</div>
                        <div class="font-mono text-sm">{{ $trans->customer->customer_number ?? $trans->customer->id }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Related Model Information --}}
    @if($trans->related_type && $trans->related_id)
        <div class="space-y-2">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Verknüpfung</h3>
            <div class="bg-white border border-gray-200 rounded-lg p-3">
                @php
                    $relatedModel = null;
                    if(class_exists($trans->related_type)) {
                        $relatedModel = $trans->related_type::find($trans->related_id);
                    }
                    $modelType = class_basename($trans->related_type);
                @endphp

                @if($relatedModel)
                    @switch($modelType)
                        @case('Appointment')
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-calendar class="w-4 h-4 text-gray-400" />
                                    <span class="font-medium">Termin #{{ $relatedModel->id }}</span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <div>Datum: {{ $relatedModel->appointment_date?->format('d.m.Y') }} {{ $relatedModel->appointment_time }}</div>
                                    @if($relatedModel->service)
                                        <div>Service: {{ $relatedModel->service->name }}</div>
                                    @endif
                                    @if($relatedModel->staff)
                                        <div>Mitarbeiter: {{ $relatedModel->staff->name }}</div>
                                    @endif
                                </div>
                            </div>
                            @break

                        @case('Payment')
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-credit-card class="w-4 h-4 text-gray-400" />
                                    <span class="font-medium">Zahlung #{{ $relatedModel->id }}</span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <div>Methode: {{ ucfirst($relatedModel->method ?? 'N/A') }}</div>
                                    <div>Status: {{ ucfirst($relatedModel->status ?? 'N/A') }}</div>
                                    @if($relatedModel->transaction_id)
                                        <div>Transaktions-ID: {{ $relatedModel->transaction_id }}</div>
                                    @endif
                                </div>
                            </div>
                            @break

                        @case('BalanceTopup')
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-plus-circle class="w-4 h-4 text-gray-400" />
                                    <span class="font-medium">Guthabenaufladung #{{ $relatedModel->id }}</span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <div>Betrag: {{ number_format($relatedModel->amount, 2, ',', '.') }} €</div>
                                    @if($relatedModel->bonus > 0)
                                        <div>Bonus: +{{ number_format($relatedModel->bonus, 2, ',', '.') }} €</div>
                                    @endif
                                    <div>Gesamt: {{ number_format($relatedModel->total_credit, 2, ',', '.') }} €</div>
                                </div>
                            </div>
                            @break

                        @case('Invoice')
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <x-heroicon-o-document-text class="w-4 h-4 text-gray-400" />
                                    <span class="font-medium">Rechnung #{{ $relatedModel->invoice_number ?? $relatedModel->id }}</span>
                                </div>
                                <div class="text-sm text-gray-600">
                                    <div>Datum: {{ $relatedModel->invoice_date?->format('d.m.Y') ?? 'N/A' }}</div>
                                    <div>Status: {{ ucfirst($relatedModel->status ?? 'N/A') }}</div>
                                    <div>Gesamt: {{ number_format($relatedModel->total_amount ?? 0, 2, ',', '.') }} €</div>
                                </div>
                            </div>
                            @break

                        @default
                            <div class="text-sm text-gray-600">
                                <div>Typ: {{ $modelType }}</div>
                                <div>ID: {{ $trans->related_id }}</div>
                            </div>
                    @endswitch
                @else
                    <div class="text-sm text-gray-500">
                        {{ $modelType }} (ID: {{ $trans->related_id }})
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- Transaction Details --}}
    <div class="space-y-2">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Details</h3>
        <div class="bg-white border border-gray-200 rounded-lg p-3 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-gray-500">Typ:</span>
                <span class="font-medium">
                    @switch($trans->type)
                        @case('payment')
                            <span class="text-blue-600">Zahlung</span>
                            @break
                        @case('refund')
                            <span class="text-orange-600">Erstattung</span>
                            @break
                        @case('topup')
                            <span class="text-green-600">Aufladung</span>
                            @break
                        @case('charge')
                            <span class="text-red-600">Belastung</span>
                            @break
                        @default
                            <span>{{ ucfirst($trans->type) }}</span>
                    @endswitch
                </span>
            </div>

            @if($trans->description)
                <div class="pt-2 border-t">
                    <div class="text-xs text-gray-500 uppercase tracking-wider mb-1">Beschreibung</div>
                    <div class="text-sm text-gray-700">{{ $trans->description }}</div>
                </div>
            @endif

            @if($trans->balance_before !== null || $trans->balance_after !== null)
                <div class="pt-2 border-t">
                    <div class="grid grid-cols-2 gap-4">
                        @if($trans->balance_before !== null)
                            <div>
                                <div class="text-xs text-gray-500">Saldo vorher</div>
                                <div class="font-medium">{{ number_format($trans->balance_before, 2, ',', '.') }} €</div>
                            </div>
                        @endif
                        @if($trans->balance_after !== null)
                            <div>
                                <div class="text-xs text-gray-500">Saldo nachher</div>
                                <div class="font-medium">{{ number_format($trans->balance_after, 2, ',', '.') }} €</div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            @if($trans->created_at)
                <div class="pt-2 border-t flex justify-between text-sm">
                    <span class="text-gray-500">Erstellt am:</span>
                    <span class="font-medium">{{ $trans->created_at->format('d.m.Y H:i:s') }}</span>
                </div>
            @endif
        </div>
    </div>
</div>