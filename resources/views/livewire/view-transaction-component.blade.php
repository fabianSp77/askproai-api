<div class="space-y-6">
    {{-- Transaktionsdetails Section --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
            <div class="grid flex-1 gap-y-1">
                <h3 class="fi-section-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Transaktionsdetails
                </h3>
            </div>
        </div>
        
        <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
            <div class="fi-section-content p-6">
                <dl class="divide-y divide-gray-100 dark:divide-white/5">
                    {{-- Transaction ID --}}
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Transaktions-ID</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:col-span-2 sm:mt-0">
                            <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30">
                                {{ $transaction->id }}
                            </span>
                        </dd>
                    </div>
                    
                    {{-- Type --}}
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Typ</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:col-span-2 sm:mt-0">
                            @php
                                $typeColors = [
                                    'topup' => 'bg-green-50 text-green-700 ring-green-700/10 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/30',
                                    'usage' => 'bg-red-50 text-red-700 ring-red-700/10 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/30',
                                    'refund' => 'bg-yellow-50 text-yellow-700 ring-yellow-700/10 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/30',
                                    'adjustment' => 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30',
                                    'bonus' => 'bg-purple-50 text-purple-700 ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/30',
                                    'fee' => 'bg-gray-50 text-gray-700 ring-gray-700/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30',
                                ];
                                $typeLabels = [
                                    'topup' => 'Aufladung',
                                    'usage' => 'Verbrauch',
                                    'refund' => 'Erstattung',
                                    'adjustment' => 'Anpassung',
                                    'bonus' => 'Bonus',
                                    'fee' => 'Gebühr',
                                ];
                                $color = $typeColors[$transaction->type] ?? 'bg-gray-50 text-gray-700 ring-gray-700/10';
                                $label = $typeLabels[$transaction->type] ?? $transaction->type;
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $color }}">
                                {{ $label }}
                            </span>
                        </dd>
                    </div>
                    
                    {{-- Amount --}}
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Betrag</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            <span class="font-bold {{ $transaction->amount_cents > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $transaction->amount_cents > 0 ? '+' : '' }}{{ number_format($transaction->amount_cents / 100, 2) }} €
                            </span>
                        </dd>
                    </div>
                    
                    {{-- Balance Before --}}
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Saldo vorher</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:col-span-2 sm:mt-0">
                            {{ number_format($transaction->balance_before_cents / 100, 2) }} €
                        </dd>
                    </div>
                    
                    {{-- Balance After --}}
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Saldo nachher</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            <span class="font-semibold {{ $transaction->balance_after_cents < 0 ? 'text-red-600 dark:text-red-400' : ($transaction->balance_after_cents < 1000 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400') }}">
                                {{ number_format($transaction->balance_after_cents / 100, 2) }} €
                            </span>
                        </dd>
                    </div>
                    
                    {{-- Description --}}
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Beschreibung</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:col-span-2 sm:mt-0">
                            {{ $transaction->description }}
                        </dd>
                    </div>
                    
                    {{-- Tenant --}}
                    @if($transaction->tenant)
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tenant</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:col-span-2 sm:mt-0">
                            <a href="{{ route('filament.admin.resources.tenants.view', $transaction->tenant) }}" class="text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                {{ $transaction->tenant->name }}
                            </a>
                        </dd>
                    </div>
                    @endif
                    
                    {{-- Created At --}}
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Datum & Zeit</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:col-span-2 sm:mt-0">
                            {{ $transaction->created_at->format('d.m.Y H:i:s') }}
                            <span class="text-gray-500 dark:text-gray-400 text-xs ml-2">
                                ({{ $transaction->created_at->diffForHumans() }})
                            </span>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
    
    {{-- Verknüpfungen Section --}}
    @if($transaction->call_id || $transaction->appointment_id || $transaction->topup_id)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
            <div class="grid flex-1 gap-y-1">
                <h3 class="fi-section-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Verknüpfungen
                </h3>
            </div>
        </div>
        
        <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
            <div class="fi-section-content p-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    @if($transaction->call_id)
                    <a href="{{ route('filament.admin.resources.calls.view', $transaction->call_id) }}" class="flex items-center gap-x-3 rounded-lg border border-gray-200 px-4 py-3 hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                        </svg>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Anruf</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">ID: {{ $transaction->call_id }}</div>
                        </div>
                    </a>
                    @endif
                    
                    @if($transaction->appointment_id)
                    <a href="{{ route('filament.admin.resources.appointments.view', $transaction->appointment_id) }}" class="flex items-center gap-x-3 rounded-lg border border-gray-200 px-4 py-3 hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Termin</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">ID: {{ $transaction->appointment_id }}</div>
                        </div>
                    </a>
                    @endif
                    
                    @if($transaction->topup_id)
                    <a href="{{ route('filament.admin.resources.balance-topups.view', $transaction->topup_id) }}" class="flex items-center gap-x-3 rounded-lg border border-gray-200 px-4 py-3 hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5">
                        <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                        </svg>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Aufladung</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">ID: {{ $transaction->topup_id }}</div>
                        </div>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
    
    {{-- Systemdaten Section --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
            <div class="grid flex-1 gap-y-1">
                <h3 class="fi-section-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Systemdaten
                </h3>
            </div>
        </div>
        
        <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
            <div class="fi-section-content p-6">
                <dl class="divide-y divide-gray-100 dark:divide-white/5">
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Erstellt am</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:col-span-2 sm:mt-0">
                            {{ $transaction->created_at->format('d.m.Y H:i:s') }}
                        </dd>
                    </div>
                    
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktualisiert am</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:col-span-2 sm:mt-0">
                            {{ $transaction->updated_at->format('d.m.Y H:i:s') }}
                        </dd>
                    </div>
                    
                    @if($transaction->metadata && count($transaction->metadata) > 0)
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Metadaten</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:col-span-2 sm:mt-0">
                            <pre class="bg-gray-50 dark:bg-gray-800 rounded p-2 text-xs overflow-x-auto">{{ json_encode($transaction->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>