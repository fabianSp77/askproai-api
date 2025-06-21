<x-filament-panels::page>
    <form wire:submit.prevent="save">
        {{ $this->form }}

        <div class="mt-6 flex gap-3">
            @foreach($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>

    @if($this->thresholdStatus && $this->companyData['is_small_business'])
        <div class="mt-8">
            <x-filament::section>
                <x-slot name="heading">
                    Kleinunternehmer-Status Überwachung
                </x-slot>

                <div class="space-y-4">
                    @php
                        $currentPercentage = $this->thresholdStatus['current_revenue'] > 0 
                            ? ($this->thresholdStatus['current_revenue'] / 22000) * 100 
                            : 0;
                        $progressColor = $currentPercentage >= 90 ? 'danger' : ($currentPercentage >= 80 ? 'warning' : 'primary');
                    @endphp

                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span>Umsatz aktuelles Jahr</span>
                            <span class="font-semibold">{{ number_format($this->thresholdStatus['current_revenue'], 2, ',', '.') }} € von 22.000 €</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-{{ $progressColor }}-600 h-3 rounded-full transition-all duration-300"
                                 style="width: {{ min($currentPercentage, 100) }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ round($currentPercentage, 1) }}% der Kleinunternehmergrenze</p>
                    </div>

                    @if($currentPercentage >= 80)
                        <div class="fi-fo-field-wrp-helper-text p-4 bg-warning-50 dark:bg-warning-900/10 rounded-lg border border-warning-300 dark:border-warning-500">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="w-5 h-5 text-warning-600 dark:text-warning-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-warning-800 dark:text-warning-200">
                                        Achtung: Annäherung an Kleinunternehmergrenze
                                    </h3>
                                    <div class="mt-2 text-sm text-warning-700 dark:text-warning-300">
                                        <p>Sie haben bereits {{ round($currentPercentage, 1) }}% der jährlichen Umsatzgrenze von 22.000 € erreicht.</p>
                                        <p class="mt-2">Verbleibender Umsatz: <strong>{{ number_format(22000 - $this->thresholdStatus['current_revenue'], 2, ',', '.') }} €</strong></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4 mt-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900">Vorjahresumsatz</h4>
                            <p class="text-2xl font-bold text-gray-900 mt-1">
                                {{ number_format($this->thresholdStatus['previous_revenue'], 2, ',', '.') }} €
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Grenze: 50.000 €</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900">Prognose Jahresende</h4>
                            @php
                                $daysInYear = now()->isLeapYear() ? 366 : 365;
                                $daysPassed = now()->dayOfYear;
                                $projectedRevenue = $daysPassed > 0 
                                    ? ($this->thresholdStatus['current_revenue'] / $daysPassed) * $daysInYear 
                                    : 0;
                            @endphp
                            <p class="text-2xl font-bold {{ $projectedRevenue > 22000 ? 'text-danger-600' : 'text-gray-900' }} mt-1">
                                {{ number_format($projectedRevenue, 2, ',', '.') }} €
                            </p>
                            <p class="text-xs text-gray-500 mt-1">Basierend auf {{ $daysPassed }} Tagen</p>
                        </div>
                    </div>
                </div>
            </x-filament::section>
        </div>
    @endif

    @if(count($this->taxRates) > 0)
        <div class="mt-8">
            <x-filament::section>
                <x-slot name="heading">
                    Aktive Steuersätze
                </x-slot>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b">
                                <th class="text-left py-2">Bezeichnung</th>
                                <th class="text-right py-2">Steuersatz</th>
                                <th class="text-center py-2">Standard</th>
                                <th class="text-center py-2">Stripe ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($this->taxRates as $rate)
                                <tr class="border-b">
                                    <td class="py-2">{{ $rate['name'] }}</td>
                                    <td class="text-right py-2">{{ number_format($rate['rate'], 1, ',', '.') }}%</td>
                                    <td class="text-center py-2">
                                        @if($rate['is_default'] ?? false)
                                            <x-filament::badge color="success">Standard</x-filament::badge>
                                        @endif
                                    </td>
                                    <td class="text-center py-2 text-xs text-gray-500">
                                        {{ $rate['stripe_tax_rate_id'] ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>