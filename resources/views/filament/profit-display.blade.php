<div class="space-y-4">
    {{-- Cost Breakdown --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {{-- Base Cost (only for super admin) --}}
        @if($profitData['type'] === 'total')
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Basiskosten
                </span>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="text-lg font-bold text-gray-900 dark:text-gray-100 truncate">
                {{ number_format($baseCost, 2, ',', '.') }} €
            </div>
        </div>
        @endif

        {{-- Reseller/Customer Cost --}}
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    @if($profitData['type'] === 'total' && $resellerCost > 0)
                        Mandanten-Kosten
                    @else
                        Ihre Kosten
                    @endif
                </span>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div class="text-lg font-bold text-gray-900 dark:text-gray-100 truncate">
                {{ number_format($resellerCost > 0 ? $resellerCost : $customerCost, 2, ',', '.') }} €
            </div>
        </div>

        {{-- Customer Cost (if reseller chain) --}}
        @if($profitData['type'] === 'total' && $resellerCost > 0)
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                    Kundenpreis
                </span>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
            </div>
            <div class="text-lg font-bold text-gray-900 dark:text-gray-100 truncate">
                {{ number_format($customerCost, 2, ',', '.') }} €
            </div>
        </div>
        @endif
    </div>

    {{-- Profit Overview --}}
    <div class="bg-white dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
        <div class="flex items-center justify-between mb-3">
            <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                Profit-Übersicht
            </h4>
            <div @class([
                'text-xl font-bold',
                'text-green-600 dark:text-green-400' => ($profitData['profit'] ?? 0) > 0,
                'text-red-600 dark:text-red-400' => ($profitData['profit'] ?? 0) < 0,
                'text-gray-600 dark:text-gray-400' => ($profitData['profit'] ?? 0) == 0,
            ])>
                @if(($profitData['profit'] ?? 0) > 0)+@elseif(($profitData['profit'] ?? 0) < 0)-@endif
                {{ number_format(abs($profitData['profit'] ?? 0) / 100, 2, ',', '.') }} €
            </div>
        </div>

        {{-- Profit Margin Bar --}}
        @php
            $margin = $profitData['margin'] ?? 0;
            $marginWidth = min(100, max(0, $margin));
            $marginColor = $margin > 30 ? 'bg-green-500' : ($margin > 15 ? 'bg-yellow-500' : 'bg-orange-500');
        @endphp
        <div class="mb-3">
            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                <span>Profit-Marge</span>
                <span class="font-medium">{{ number_format($margin, 1) }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-600 rounded-full h-2 overflow-hidden">
                <div class="{{ $marginColor }} h-2 rounded-full transition-all duration-500"
                     style="width: {{ $marginWidth }}%"></div>
            </div>
        </div>

        {{-- Breakdown for Super Admin --}}
        @if($profitData['type'] === 'total' && isset($profitData['breakdown']))
        <div class="pt-3 border-t border-gray-200 dark:border-gray-600 space-y-2">
            <h5 class="text-xs font-medium text-gray-700 dark:text-gray-300 uppercase mb-2">
                Profit-Verteilung
            </h5>

            {{-- Platform Profit --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-purple-500 rounded-full"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Plattform-Profit
                    </span>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ number_format(($profitData['breakdown']['platform'] ?? 0) / 100, 2, ',', '.') }} €
                </span>
            </div>

            {{-- Reseller Profit --}}
            @if(($profitData['breakdown']['reseller'] ?? 0) > 0)
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Mandanten-Profit
                    </span>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ number_format(($profitData['breakdown']['reseller'] ?? 0) / 100, 2, ',', '.') }} €
                </span>
            </div>
            @endif
        </div>
        @endif

        {{-- Performance Indicators --}}
        <div class="grid grid-cols-3 gap-2 mt-3 pt-3 border-t border-gray-200 dark:border-gray-600">
            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400">ROI</div>
                <div @class([
                    'text-sm font-medium',
                    'text-green-600' => $baseCost > 0 && ($profitData['profit'] ?? 0) / 100 / $baseCost > 0.5,
                    'text-yellow-600' => $baseCost > 0 && ($profitData['profit'] ?? 0) / 100 / $baseCost > 0.2,
                    'text-red-600' => $baseCost > 0 && ($profitData['profit'] ?? 0) / 100 / $baseCost <= 0.2,
                ])>
                    @if($baseCost > 0)
                        {{ number_format((($profitData['profit'] ?? 0) / 100 / $baseCost) * 100, 0) }}%
                    @else
                        N/A
                    @endif
                </div>
            </div>

            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400">Effizienz</div>
                <div class="text-sm font-medium">
                    @if($margin > 30)
                        <span class="text-green-600">⭐⭐⭐</span>
                    @elseif($margin > 15)
                        <span class="text-yellow-600">⭐⭐</span>
                    @else
                        <span class="text-orange-600">⭐</span>
                    @endif
                </div>
            </div>

            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400">Kategorie</div>
                <div @class([
                    'text-sm font-medium',
                    'text-green-600' => $margin > 30,
                    'text-blue-600' => $margin > 15 && $margin <= 30,
                    'text-gray-600' => $margin <= 15,
                ])>
                    @if($margin > 30)
                        Premium
                    @elseif($margin > 15)
                        Standard
                    @else
                        Basis
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>