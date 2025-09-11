<div class="space-y-4">
    @if($transaction->call_id)
        <div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
            <h3 class="font-semibold mb-2 text-gray-700 dark:text-gray-300">
                <svg class="inline w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
                Verknüpfter Anruf
            </h3>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Anruf-ID:</dt>
                    <dd class="font-medium">{{ $transaction->call_id }}</dd>
                </div>
                @if($transaction->call)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Dauer:</dt>
                        <dd class="font-medium">{{ gmdate('H:i:s', $transaction->call->duration_sec ?? 0) }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Kunde:</dt>
                        <dd class="font-medium">{{ $transaction->call->customer->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Datum:</dt>
                        <dd class="font-medium">{{ $transaction->call->created_at?->format('d.m.Y H:i') }}</dd>
                    </div>
                @endif
            </dl>
            <div class="mt-3">
                <a href="/admin/calls/{{ $transaction->call_id }}" 
                   class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                    Anruf-Details anzeigen →
                </a>
            </div>
        </div>
    @endif
    
    @if($transaction->appointment_id)
        <div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
            <h3 class="font-semibold mb-2 text-gray-700 dark:text-gray-300">
                <svg class="inline w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Verknüpfter Termin
            </h3>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Termin-ID:</dt>
                    <dd class="font-medium">{{ $transaction->appointment_id }}</dd>
                </div>
                @if($transaction->appointment)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Service:</dt>
                        <dd class="font-medium">{{ $transaction->appointment->service->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Kunde:</dt>
                        <dd class="font-medium">{{ $transaction->appointment->customer->name ?? 'N/A' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Datum:</dt>
                        <dd class="font-medium">{{ $transaction->appointment->start_time?->format('d.m.Y H:i') }}</dd>
                    </div>
                @endif
            </dl>
            <div class="mt-3">
                <a href="/admin/appointments/{{ $transaction->appointment_id }}" 
                   class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                    Termin-Details anzeigen →
                </a>
            </div>
        </div>
    @endif
    
    @if($transaction->topup_id)
        <div class="border rounded-lg p-4 bg-gray-50 dark:bg-gray-800">
            <h3 class="font-semibold mb-2 text-gray-700 dark:text-gray-300">
                <svg class="inline w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                </svg>
                Verknüpfte Aufladung
            </h3>
            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">Aufladungs-ID:</dt>
                    <dd class="font-medium">{{ $transaction->topup_id }}</dd>
                </div>
                @if($transaction->topup)
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Betrag:</dt>
                        <dd class="font-medium">{{ number_format($transaction->topup->amount, 2) }} {{ $transaction->topup->currency }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Zahlungsmethode:</dt>
                        <dd class="font-medium">{{ ucfirst($transaction->topup->payment_method ?? 'N/A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400">Status:</dt>
                        <dd>
                            <span class="px-2 py-1 text-xs rounded-full 
                                {{ $transaction->topup->status === 'succeeded' ? 'bg-green-100 text-green-800' : '' }}
                                {{ $transaction->topup->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                {{ $transaction->topup->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                            ">
                                {{ ucfirst($transaction->topup->status ?? 'N/A') }}
                            </span>
                        </dd>
                    </div>
                @endif
            </dl>
            <div class="mt-3">
                <a href="/admin/balance-topups/{{ $transaction->topup_id }}" 
                   class="text-primary-600 hover:text-primary-700 text-sm font-medium">
                    Aufladungs-Details anzeigen →
                </a>
            </div>
        </div>
    @endif
    
    @if(!$transaction->call_id && !$transaction->appointment_id && !$transaction->topup_id)
        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
            </svg>
            <p class="mt-2">Keine verknüpften Datensätze gefunden</p>
        </div>
    @endif
</div>