<x-filament-widgets::widget>
    <x-filament::card>
        <div class="space-y-6">
            {{-- Header --}}
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Customer Journey Funnel
                </h2>
                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Gesamt: {{ number_format($totalCustomers) }} Kunden
                </div>
            </div>

            {{-- Funnel Visualization --}}
            <div class="space-y-3">
                @foreach($funnelData as $stage)
                    <div class="relative">
                        {{-- Stage Bar --}}
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">{{ $stage['icon'] }}</span>
                            <div class="flex-1">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ $stage['label'] }}
                                    </span>
                                    <span class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ number_format($stage['count']) }} ({{ $stage['percentage'] }}%)
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6 overflow-hidden">
                                    <div
                                        class="h-full rounded-full transition-all duration-500 flex items-center justify-center text-xs font-medium text-white"
                                        style="width: {{ $stage['width'] }}%; background-color: {{ $stage['color'] }};"
                                    >
                                        @if($stage['count'] > 0)
                                            {{ $stage['percentage'] }}%
                                        @endif
                                    </div>
                                </div>
                                @if(isset($timeframes[$stage['stage']]) && $timeframes[$stage['stage']] > 0)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        ⏱ Durchschnittlich {{ $timeframes[$stage['stage']] }} Tage in dieser Phase
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Conversion Rates --}}
            @if(count($conversionRates) > 0)
                <div class="border-t pt-4 dark:border-gray-700">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                        Konversionsraten zwischen Phasen
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach($conversionRates as $conversion)
                            <div class="text-center p-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $conversion['from'] }} → {{ $conversion['to'] }}
                                </div>
                                <div @class([
                                    'text-lg font-bold mt-1',
                                    'text-green-600 dark:text-green-400' => $conversion['color'] === 'success',
                                    'text-yellow-600 dark:text-yellow-400' => $conversion['color'] === 'warning',
                                    'text-red-600 dark:text-red-400' => $conversion['color'] === 'danger',
                                ])>
                                    {{ $conversion['rate'] }}%
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Insights --}}
            <div class="border-t pt-4 dark:border-gray-700">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @php
                        $leadToCustomerRate = 0;
                        foreach($funnelData as $stage) {
                            if($stage['stage'] === 'lead' && $stage['count'] > 0) {
                                $customerCount = 0;
                                foreach($funnelData as $s) {
                                    if(in_array($s['stage'], ['customer', 'regular', 'vip'])) {
                                        $customerCount += $s['count'];
                                    }
                                }
                                $leadToCustomerRate = round(($customerCount / $stage['count']) * 100, 1);
                            }
                        }
                    @endphp

                    <div class="text-center">
                        <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                            {{ $leadToCustomerRate }}%
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Lead → Kunde Konversion
                        </div>
                    </div>

                    @php
                        $atRiskCount = 0;
                        $churnedCount = 0;
                        // These would come from the actual data
                    @endphp

                    <div class="text-center">
                        <div class="text-2xl font-bold text-warning-600 dark:text-warning-400">
                            {{ \App\Models\Customer::where('journey_status', 'at_risk')->count() }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            Gefährdete Kunden
                        </div>
                    </div>

                    <div class="text-center">
                        <div class="text-2xl font-bold text-success-600 dark:text-success-400">
                            {{ \App\Models\Customer::where('journey_status', 'vip')->count() }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">
                            VIP Kunden
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-filament::card>
</x-filament-widgets::widget>