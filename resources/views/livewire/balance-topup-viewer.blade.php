<div class="space-y-6">
    {{-- Header Section with Status --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
                        Aufladung #{{ $topup->id }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Erstellt am {{ $topup->created_at->format('d.m.Y H:i:s') }}
                    </p>
                </div>
                <div>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-{{ $this->statusColor }}-100 text-{{ $this->statusColor }}-800 dark:bg-{{ $this->statusColor }}-900 dark:text-{{ $this->statusColor }}-200">
                        {{ $this->statusLabel }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Aufladungsdetails --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aufladungsdetails</h3>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Betrag</dt>
                    <dd class="mt-1 text-2xl font-semibold text-gray-900 dark:text-white">
                        {{ number_format($topup->amount, 2) }} {{ $topup->currency }}
                    </dd>
                </div>
                
                @if($topup->bonus_amount > 0)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Bonus</dt>
                    <dd class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                        +{{ number_format($topup->bonus_amount, 2) }} {{ $topup->currency }}
                    </dd>
                    @if($topup->bonus_reason)
                    <dd class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                        {{ $topup->bonus_reason }}
                    </dd>
                    @endif
                </div>
                @endif
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Gesamtbetrag</dt>
                    <dd class="mt-1 text-2xl font-semibold text-blue-600 dark:text-blue-400">
                        {{ number_format($topup->getTotalAmount(), 2) }} {{ $topup->currency }}
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Zahlungsmethode</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            {{ $this->paymentMethodLabel }}
                        </span>
                    </dd>
                </div>
                
                @if($topup->paid_at)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Zahlungsdatum</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $topup->paid_at->format('d.m.Y H:i:s') }}
                    </dd>
                </div>
                @endif
                
                @if($topup->initiatedBy)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Initiiert von</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                        {{ $topup->initiatedBy->name }}
                    </dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Tenant-Informationen --}}
    @if($topup->tenant)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Tenant-Informationen</h3>
        </div>
        <div class="p-6">
            <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tenant</dt>
                    <dd class="mt-1">
                        <a href="/admin/tenants/{{ $topup->tenant_id }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                            {{ $topup->tenant->name }}
                        </a>
                    </dd>
                </div>
                
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktuelles Guthaben</dt>
                    <dd class="mt-1">
                        @php
                            $balance = $topup->tenant->balance_cents / 100;
                            $balanceColor = $balance < 10 ? 'red' : ($balance < 50 ? 'yellow' : 'green');
                        @endphp
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $balanceColor }}-100 text-{{ $balanceColor }}-800 dark:bg-{{ $balanceColor }}-900 dark:text-{{ $balanceColor }}-200">
                            {{ number_format($balance, 2) }} €
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>
    @endif

    {{-- Zahlungsinformationen (Stripe IDs) --}}
    @if($topup->stripe_payment_intent_id || $topup->stripe_checkout_session_id || $topup->metadata || $topup->stripe_response)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Zahlungsinformationen</h3>
        </div>
        <div class="p-6 space-y-4">
            @if($topup->stripe_payment_intent_id)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Stripe Payment Intent ID</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono bg-gray-50 dark:bg-gray-900 p-2 rounded">
                    {{ $topup->stripe_payment_intent_id }}
                </dd>
            </div>
            @endif
            
            @if($topup->stripe_checkout_session_id)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Stripe Checkout Session ID</dt>
                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono bg-gray-50 dark:bg-gray-900 p-2 rounded">
                    {{ $topup->stripe_checkout_session_id }}
                </dd>
            </div>
            @endif
            
            @if($topup->metadata && count($topup->metadata) > 0)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Metadaten</dt>
                <dd class="bg-gray-50 dark:bg-gray-900 p-3 rounded">
                    <dl class="space-y-1">
                        @foreach($topup->metadata as $key => $value)
                        <div class="flex">
                            <dt class="text-sm font-medium text-gray-600 dark:text-gray-400 min-w-[150px]">{{ $key }}:</dt>
                            <dd class="text-sm text-gray-900 dark:text-white ml-2">{{ is_array($value) ? json_encode($value) : $value }}</dd>
                        </div>
                        @endforeach
                    </dl>
                </dd>
            </div>
            @endif
            
            @if($topup->stripe_response && count($topup->stripe_response) > 0)
            <div>
                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">Stripe Response</dt>
                <dd class="bg-gray-50 dark:bg-gray-900 p-3 rounded">
                    <pre class="text-xs text-gray-900 dark:text-white overflow-x-auto">{{ json_encode($topup->stripe_response, JSON_PRETTY_PRINT) }}</pre>
                </dd>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- Aktionen --}}
    @if(in_array($topup->status, ['pending', 'processing']))
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white">Aktionen</h3>
        </div>
        <div class="p-6">
            <div class="flex space-x-4">
                <button 
                    wire:click="approveTopup"
                    wire:confirm="Sind Sie sicher, dass Sie diese Aufladung genehmigen möchten?"
                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Genehmigen
                </button>
                
                <button 
                    x-data="{ open: false }"
                    @click="open = true"
                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                >
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Ablehnen
                    
                    <div x-show="open" @click.away="open = false" x-cloak class="fixed inset-0 z-50 overflow-y-auto">
                        <div class="flex items-center justify-center min-h-screen">
                            <div class="fixed inset-0 bg-black opacity-50"></div>
                            <div class="relative bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4">
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Aufladung ablehnen</h3>
                                <input 
                                    type="text" 
                                    x-model="reason" 
                                    placeholder="Grund für Ablehnung"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 dark:bg-gray-700 dark:text-white"
                                >
                                <div class="mt-4 flex justify-end space-x-2">
                                    <button @click="open = false" class="px-4 py-2 text-gray-700 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                                        Abbrechen
                                    </button>
                                    <button 
                                        @click="$wire.rejectTopup(reason); open = false"
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-md"
                                    >
                                        Ablehnen
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </button>
            </div>
        </div>
    </div>
    @endif

    {{-- Systemdaten --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
        <details class="group">
            <summary class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white inline-flex items-center">
                    Systemdaten
                    <svg class="w-5 h-5 ml-2 transition-transform group-open:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </h3>
            </summary>
            <div class="p-6">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Erstellt am</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $topup->created_at->format('d.m.Y H:i:s') }}
                        </dd>
                    </div>
                    
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktualisiert am</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $topup->updated_at->format('d.m.Y H:i:s') }}
                        </dd>
                    </div>
                </dl>
            </div>
        </details>
    </div>
</div>