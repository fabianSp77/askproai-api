@php
    use App\Services\CostCalculator;

    $record = $getRecord();
    $user = auth()->user();
    $calculator = app(CostCalculator::class);
    $profitData = $calculator->getDisplayProfit($record, $user);

    // Get role-based visibility
    $canViewProfits = $user && ($user->hasRole(['super-admin', 'super_admin', 'Super Admin']) ||
                                 $user->hasRole(['reseller_admin', 'reseller_owner', 'reseller_support']));

    // Format currency
    $formatCurrency = function($amount) {
        return number_format($amount, 2, ',', '.') . ' €';
    };

    // Get costs
    $baseCost = $record->base_cost ?? 0;
    $resellerCost = $record->reseller_cost ?? 0;
    $customerCost = $record->customer_cost ?? $record->cost ?? 0;
@endphp

@if($canViewProfits && $profitData['type'] !== 'none')
<div class="profit-section bg-gradient-to-r from-purple-50 to-indigo-50 dark:from-gray-800 dark:to-gray-700 rounded-lg p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <span class="text-2xl">💰</span>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                Kosten & Profit-Analyse
            </h3>
        </div>
        <div class="text-sm text-gray-600 dark:text-gray-400">
            @if($profitData['type'] === 'total')
                <span class="inline-flex items-center px-2 py-1 rounded-full bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200">
                    👑 Plattform-Übersicht
                </span>
            @elseif($profitData['type'] === 'reseller')
                <span class="inline-flex items-center px-2 py-1 rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                    🏢 Mandanten-Ansicht
                </span>
            @endif
        </div>
    </div>

    {{-- Cost Breakdown --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- Base Cost (only for super admin) --}}
        @if($profitData['type'] === 'total')
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Basiskosten</span>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ $formatCurrency($baseCost) }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Infrastruktur + API
            </div>
        </div>
        @endif

        {{-- Reseller/Customer Cost --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    @if($profitData['type'] === 'total' && $resellerCost > 0)
                        Mandanten-Kosten
                    @else
                        Ihre Kosten
                    @endif
                </span>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ $formatCurrency($resellerCost > 0 ? $resellerCost : $customerCost) }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                @if($profitData['type'] === 'total' && $resellerCost > 0)
                    Was Mandant zahlt
                @else
                    Berechnete Kosten
                @endif
            </div>
        </div>

        {{-- Customer Cost (if reseller chain) --}}
        @if($profitData['type'] === 'total' && $resellerCost > 0)
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm text-gray-600 dark:text-gray-400">Kundenpreis</span>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
            </div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                {{ $formatCurrency($customerCost) }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Endkundenpreis
            </div>
        </div>
        @endif
    </div>

    {{-- Profit Display --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm">
        <div class="flex items-center justify-between mb-4">
            <h4 class="font-medium text-gray-900 dark:text-gray-100">Profit-Übersicht</h4>
            <div class="text-2xl font-bold
                @if($profitData['profit'] > 0) text-green-600 dark:text-green-400
                @elseif($profitData['profit'] < 0) text-red-600 dark:text-red-400
                @else text-gray-600 dark:text-gray-400
                @endif">
                @if($profitData['profit'] > 0)+@elseif($profitData['profit'] < 0)-@endif
                {{ $formatCurrency(abs($profitData['profit'])) }}
            </div>
        </div>

        {{-- Profit Bar --}}
        <div class="mb-4">
            <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-1">
                <span>Profit-Marge</span>
                <span class="font-medium">{{ number_format($profitData['margin'], 1) }}%</span>
            </div>
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3">
                <div class="h-3 rounded-full transition-all duration-300
                    @if($profitData['margin'] > 30) bg-green-500
                    @elseif($profitData['margin'] > 15) bg-yellow-500
                    @elseif($profitData['margin'] > 0) bg-orange-500
                    @else bg-red-500
                    @endif"
                    style="width: {{ min(100, max(0, $profitData['margin'])) }}%"></div>
            </div>
        </div>

        {{-- Breakdown for Super Admin --}}
        @if($profitData['type'] === 'total' && isset($profitData['breakdown']))
        <div class="border-t border-gray-200 dark:border-gray-600 pt-4 space-y-3">
            <h5 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Profit-Verteilung</h5>

            {{-- Platform Profit --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-purple-500 rounded-full"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Plattform-Profit</span>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $formatCurrency($profitData['breakdown']['platform']) }}
                </span>
            </div>

            {{-- Reseller Profit --}}
            @if($profitData['breakdown']['reseller'] > 0)
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-blue-500 rounded-full"></div>
                    <span class="text-sm text-gray-600 dark:text-gray-400">Mandanten-Profit</span>
                </div>
                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    {{ $formatCurrency($profitData['breakdown']['reseller']) }}
                </span>
            </div>
            @endif

            {{-- Total --}}
            <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-600">
                <div class="flex items-center gap-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Gesamt-Profit</span>
                </div>
                <span class="text-base font-bold text-gray-900 dark:text-gray-100">
                    {{ $formatCurrency($profitData['breakdown']['total']) }}
                </span>
            </div>
        </div>
        @endif

        {{-- Performance Indicators --}}
        <div class="grid grid-cols-3 gap-2 mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400">ROI</div>
                <div class="text-sm font-medium
                    @if($baseCost > 0 && $profitData['profit'] / $baseCost > 0.5) text-green-600
                    @elseif($baseCost > 0 && $profitData['profit'] / $baseCost > 0.2) text-yellow-600
                    @else text-red-600
                    @endif">
                    @if($baseCost > 0)
                        {{ number_format(($profitData['profit'] / $baseCost) * 100, 0) }}%
                    @else
                        N/A
                    @endif
                </div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400">Effizienz</div>
                <div class="text-sm font-medium">
                    @if($profitData['margin'] > 30)
                        <span class="text-green-600">⭐⭐⭐</span>
                    @elseif($profitData['margin'] > 15)
                        <span class="text-yellow-600">⭐⭐</span>
                    @else
                        <span class="text-orange-600">⭐</span>
                    @endif
                </div>
            </div>
            <div class="text-center">
                <div class="text-xs text-gray-500 dark:text-gray-400">Kategorie</div>
                <div class="text-sm font-medium">
                    @if($profitData['margin'] > 30)
                        <span class="text-green-600">Premium</span>
                    @elseif($profitData['margin'] > 15)
                        <span class="text-blue-600">Standard</span>
                    @else
                        <span class="text-gray-600">Basis</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Call Duration Impact --}}
    @if($record->duration_sec > 0)
    <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
            </svg>
            <div class="flex-1">
                <h5 class="text-sm font-medium text-blue-900 dark:text-blue-200 mb-1">
                    Kosten-Effizienz-Analyse
                </h5>
                <div class="text-xs text-blue-700 dark:text-blue-300 space-y-1">
                    <div>Anrufdauer: {{ gmdate('H:i:s', $record->duration_sec) }}</div>
                    <div>Kosten pro Minute: {{ $formatCurrency($customerCost / max(1, $record->duration_sec / 60)) }}</div>
                    <div>Profit pro Minute: {{ $formatCurrency($profitData['profit'] / max(1, $record->duration_sec / 60)) }}</div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
/* Animate profit bar on load */
@keyframes fillBar {
    from { width: 0; }
}

.profit-section .rounded-full > div {
    animation: fillBar 1s ease-out;
}

/* Hover effects */
.profit-section .bg-white:hover,
.profit-section .dark\:bg-gray-800:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    transition: all 0.2s ease;
}
</style>

@elseif($customerCost > 0)
{{-- Simple cost display for customers --}}
<div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="text-lg">💶</span>
            <span class="text-sm text-gray-600 dark:text-gray-400">Anrufkosten</span>
        </div>
        <div class="text-xl font-bold text-gray-900 dark:text-gray-100">
            {{ $formatCurrency($customerCost) }}
        </div>
    </div>
</div>
@endif