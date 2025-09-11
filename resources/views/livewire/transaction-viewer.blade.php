<div class="space-y-6">
    {{-- Header with Timeline Toggle --}}
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
            Transaktion #{{ $transaction->id }}
        </h2>
        @if(count($relatedTransactions) > 0)
        <button wire:click="toggleTimeline" 
                class="inline-flex items-center px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
            <svg class="h-4 w-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            {{ $showTimeline ? 'Timeline ausblenden' : 'Timeline anzeigen' }}
        </button>
        @endif
    </div>

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
                                $color = $this->getTypeColor($transaction->type);
                                $colorClasses = match($color) {
                                    'success' => 'bg-green-50 text-green-700 ring-green-700/10 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/30',
                                    'danger' => 'bg-red-50 text-red-700 ring-red-700/10 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/30',
                                    'warning' => 'bg-yellow-50 text-yellow-700 ring-yellow-700/10 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/30',
                                    'info' => 'bg-blue-50 text-blue-700 ring-blue-700/10 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30',
                                    'primary' => 'bg-purple-50 text-purple-700 ring-purple-700/10 dark:bg-purple-400/10 dark:text-purple-400 dark:ring-purple-400/30',
                                    default => 'bg-gray-50 text-gray-700 ring-gray-700/10 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $colorClasses }}">
                                {{ $this->getTypeLabel($transaction->type) }}
                            </span>
                        </dd>
                    </div>
                    
                    {{-- Amount --}}
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Betrag</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            <span class="text-lg font-bold {{ $transaction->amount_cents > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $transaction->amount_cents > 0 ? '+' : '' }}{{ $this->getFormattedAmount($transaction->amount_cents) }}
                            </span>
                        </dd>
                    </div>
                    
                    {{-- Balance Before/After --}}
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Saldo-Änderung</dt>
                        <dd class="mt-1 text-sm sm:col-span-2 sm:mt-0">
                            <div class="flex items-center space-x-2">
                                <span class="text-gray-600 dark:text-gray-400">
                                    {{ $this->getFormattedAmount($transaction->balance_before_cents) }}
                                </span>
                                <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                </svg>
                                <span class="font-semibold {{ $transaction->balance_after_cents < 0 ? 'text-red-600 dark:text-red-400' : ($transaction->balance_after_cents < 1000 ? 'text-yellow-600 dark:text-yellow-400' : 'text-green-600 dark:text-green-400') }}">
                                    {{ $this->getFormattedAmount($transaction->balance_after_cents) }}
                                </span>
                                @if($transaction->balance_after_cents < 1000 && $transaction->balance_after_cents >= 0)
                                <span class="ml-2 inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-700 ring-1 ring-inset ring-yellow-700/10 dark:bg-yellow-400/10 dark:text-yellow-400 dark:ring-yellow-400/30">
                                    Niedriger Saldo
                                </span>
                                @elseif($transaction->balance_after_cents < 0)
                                <span class="ml-2 inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-700/10 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/30">
                                    Negativer Saldo
                                </span>
                                @endif
                            </div>
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
                            <a href="{{ route('filament.admin.resources.tenants.view', $transaction->tenant) }}" 
                               class="inline-flex items-center text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                <svg class="h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                                </svg>
                                {{ $transaction->tenant->name }}
                            </a>
                        </dd>
                    </div>
                    @endif
                    
                    {{-- Created At --}}
                    <div class="py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:py-5">
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Datum & Zeit</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white sm:col-span-2 sm:mt-0">
                            <div class="flex items-center">
                                <svg class="h-4 w-4 mr-2 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                                </svg>
                                {{ $transaction->created_at->format('d.m.Y H:i:s') }}
                                <span class="text-gray-500 dark:text-gray-400 text-xs ml-2">
                                    ({{ $transaction->created_at->diffForHumans() }})
                                </span>
                            </div>
                        </dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
    
    {{-- Timeline Section --}}
    @if($showTimeline && count($relatedTransactions) > 0)
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" wire:transition>
        <div class="fi-section-header flex items-center gap-x-3 px-6 py-4">
            <div class="grid flex-1 gap-y-1">
                <h3 class="fi-section-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    Transaktionsverlauf
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Letzte {{ count($relatedTransactions) }} Transaktionen dieses Tenants
                </p>
            </div>
        </div>
        
        <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
            <div class="fi-section-content p-6">
                <div class="flow-root">
                    <ul role="list" class="-mb-8">
                        @foreach($relatedTransactions as $index => $related)
                        <li>
                            <div class="relative pb-8">
                                @if(!$loop->last)
                                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                                @endif
                                <div class="relative flex space-x-3">
                                    <div>
                                        @php
                                            $iconColor = match($related['type']) {
                                                'topup' => 'bg-green-500',
                                                'usage' => 'bg-red-500',
                                                'refund' => 'bg-yellow-500',
                                                'adjustment' => 'bg-blue-500',
                                                'bonus' => 'bg-purple-500',
                                                default => 'bg-gray-500'
                                            };
                                        @endphp
                                        <span class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white dark:ring-gray-900 {{ $iconColor }}">
                                            @if($related['type'] === 'topup')
                                            <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                            </svg>
                                            @elseif($related['type'] === 'usage')
                                            <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15" />
                                            </svg>
                                            @else
                                            <svg class="h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m6-6H6" />
                                            </svg>
                                            @endif
                                        </span>
                                    </div>
                                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                        <div>
                                            <p class="text-sm text-gray-900 dark:text-white">
                                                {{ $related['description'] }}
                                                <span class="font-medium {{ $related['amount_cents'] > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                    {{ $related['amount_cents'] > 0 ? '+' : '' }}{{ number_format($related['amount_cents'] / 100, 2, ',', '.') }} €
                                                </span>
                                            </p>
                                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                Saldo: {{ number_format($related['balance_after_cents'] / 100, 2, ',', '.') }} €
                                            </p>
                                        </div>
                                        <div class="whitespace-nowrap text-right text-xs text-gray-500 dark:text-gray-400">
                                            <time datetime="{{ $related['created_at'] }}">
                                                {{ \Carbon\Carbon::parse($related['created_at'])->format('d.m.Y H:i') }}
                                            </time>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @endif
    
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
                    @if($transaction->call_id && $transaction->call)
                    <a href="{{ route('filament.admin.resources.calls.view', $transaction->call_id) }}" 
                       class="relative flex items-center gap-x-3 rounded-lg border border-gray-200 px-4 py-3 hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5 transition-colors">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 dark:bg-blue-900/20">
                            <svg class="h-5 w-5 text-blue-600 dark:text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Anruf</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                ID: {{ $transaction->call_id }}
                                @if($transaction->call->duration_seconds)
                                • {{ gmdate('i:s', $transaction->call->duration_seconds) }} Min
                                @endif
                            </div>
                        </div>
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                    @endif
                    
                    @if($transaction->appointment_id && $transaction->appointment)
                    <a href="{{ route('filament.admin.resources.appointments.view', $transaction->appointment_id) }}" 
                       class="relative flex items-center gap-x-3 rounded-lg border border-gray-200 px-4 py-3 hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5 transition-colors">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-50 dark:bg-green-900/20">
                            <svg class="h-5 w-5 text-green-600 dark:text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Termin</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                ID: {{ $transaction->appointment_id }}
                                @if($transaction->appointment->starts_at)
                                • {{ \Carbon\Carbon::parse($transaction->appointment->starts_at)->format('d.m.Y') }}
                                @endif
                            </div>
                        </div>
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
                    </a>
                    @endif
                    
                    @if($transaction->topup_id)
                    <a href="{{ route('filament.admin.resources.balance-topups.view', $transaction->topup_id) }}" 
                       class="relative flex items-center gap-x-3 rounded-lg border border-gray-200 px-4 py-3 hover:bg-gray-50 dark:border-white/10 dark:hover:bg-white/5 transition-colors">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-900/20">
                            <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900 dark:text-white">Aufladung</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">ID: {{ $transaction->topup_id }}</div>
                        </div>
                        <svg class="h-4 w-4 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                        </svg>
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
                            <pre class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 text-xs overflow-x-auto font-mono">{{ json_encode($transaction->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        </dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>
    </div>
</div>