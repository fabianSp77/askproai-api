<x-filament-widgets::widget>
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Telefonnummern & Integration Status --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Telefonnummern --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-phone class="w-5 h-5" />
                        <span>Telefonnummern</span>
                    </div>
                </x-slot>
                
                @if($phoneNumbers->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">Keine Telefonnummern vorhanden</p>
                @else
                    <ul class="space-y-3">
                        @foreach($phoneNumbers as $phone)
                            <li class="flex items-start gap-3">
                                <x-heroicon-o-phone class="w-4 h-4 text-gray-400 mt-0.5" />
                                <div class="flex-1">
                                    <div class="font-medium">{{ $phone->number }}</div>
                                    <div class="text-xs text-gray-500">
                                        {{ ucfirst($phone->type) }}
                                        @if($phone->branch)
                                            • {{ $phone->branch->name }}
                                        @endif
                                    </div>
                                </div>
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium {{ $phone->is_active ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400' : 'bg-gray-50 text-gray-600 dark:bg-gray-800 dark:text-gray-400' }}">
                                    {{ $phone->is_active ? 'Aktiv' : 'Inaktiv' }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-filament::section>

            {{-- Integration Status --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-link class="w-5 h-5" />
                        <span>Integrationen</span>
                    </div>
                </x-slot>
                
                <ul class="space-y-3">
                    <li class="flex items-center justify-between">
                        <span class="text-sm">Retell.ai</span>
                        @if($integrationStatus['retell'])
                            <span class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                <x-heroicon-m-check-circle class="w-4 h-4" />
                                Verbunden
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                <x-heroicon-m-x-circle class="w-4 h-4" />
                                Nicht verbunden
                            </span>
                        @endif
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-sm">Cal.com</span>
                        @if($integrationStatus['calcom'])
                            <span class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                <x-heroicon-m-check-circle class="w-4 h-4" />
                                Verbunden
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                <x-heroicon-m-x-circle class="w-4 h-4" />
                                Nicht verbunden
                            </span>
                        @endif
                    </li>
                    <li class="flex items-center justify-between">
                        <span class="text-sm">Stripe</span>
                        @if($integrationStatus['stripe'])
                            <span class="inline-flex items-center gap-1 text-xs text-green-600 dark:text-green-400">
                                <x-heroicon-m-check-circle class="w-4 h-4" />
                                Verbunden
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                                <x-heroicon-m-x-circle class="w-4 h-4" />
                                Nicht verbunden
                            </span>
                        @endif
                    </li>
                </ul>
            </x-filament::section>

            {{-- Monatsstatistiken --}}
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-chart-bar class="w-5 h-5" />
                        <span>{{ now()->format('F Y') }}</span>
                    </div>
                </x-slot>
                
                <dl class="space-y-3">
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Anrufe</dt>
                        <dd class="text-sm font-medium">{{ number_format($monthlyStats['total_calls'], 0, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Minuten</dt>
                        <dd class="text-sm font-medium">{{ number_format($monthlyStats['total_minutes'], 1, ',', '.') }}</dd>
                    </div>
                    <div class="flex justify-between items-center">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Unique Anrufer</dt>
                        <dd class="text-sm font-medium">{{ number_format($monthlyStats['unique_callers'], 0, ',', '.') }}</dd>
                    </div>
                </dl>
            </x-filament::section>
        </div>

        {{-- Letzte Anrufe --}}
        <div class="lg:col-span-1">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-phone-arrow-down-left class="w-5 h-5" />
                        <span>Letzte Anrufe</span>
                    </div>
                </x-slot>
                
                @if($recentCalls->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">Keine Anrufe vorhanden</p>
                @else
                    <ul class="space-y-3">
                        @foreach($recentCalls as $call)
                            <li class="border-b border-gray-100 dark:border-gray-800 pb-3 last:border-0 last:pb-0">
                                <div class="flex justify-between items-start">
                                    <div class="space-y-1">
                                        <div class="text-sm font-medium">
                                            {{ $call->from_number ?: 'Anonym' }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            → {{ $call->to_number }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $call->created_at->format('d.m.Y H:i') }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium">
                                            {{ gmdate('i:s', $call->duration_sec ?? 0) }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ round(($call->duration_sec ?? 0) / 60 * 0.42, 2) }} €
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-filament::section>
        </div>

        {{-- Letzte Transaktionen --}}
        <div class="lg:col-span-1">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-banknotes class="w-5 h-5" />
                        <span>Letzte Transaktionen</span>
                    </div>
                </x-slot>
                
                @if($recentTransactions->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">Keine Transaktionen vorhanden</p>
                @else
                    <ul class="space-y-3">
                        @foreach($recentTransactions as $transaction)
                            <li class="border-b border-gray-100 dark:border-gray-800 pb-3 last:border-0 last:pb-0">
                                <div class="flex justify-between items-start">
                                    <div class="space-y-1">
                                        <div class="text-sm font-medium">
                                            {{ $transaction->description }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ $transaction->created_at->format('d.m.Y H:i') }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-medium {{ $transaction->type === 'topup' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $transaction->type === 'topup' ? '+' : '-' }}{{ number_format(abs($transaction->amount), 2, ',', '.') }} €
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Saldo: {{ number_format($transaction->balance_after, 2, ',', '.') }} €
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament-widgets::widget>