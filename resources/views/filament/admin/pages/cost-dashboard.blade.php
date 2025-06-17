<x-filament-panels::page>
    @php
        $data = $this->dashboardData ?? [
            'totalCalls' => 0,
            'totalMinutes' => 0,
            'totalCost' => 0,
            'totalRevenue' => 0,
            'totalMargin' => 0,
            'marginPercentage' => 0,
            'avgCostPerCall' => 0,
            'avgRevenuePerCall' => 0,
            'avgCostPerMinute' => 0,
            'avgRevenuePerMinute' => 0,
            'callsWithPricing' => 0,
            'callsWithoutPricing' => 0,
            'dailyData' => collect(),
        ];
        $totalCalls = $data['totalCalls'];
        $totalMinutes = $data['totalMinutes'];
        $totalCost = $data['totalCost'];
        $totalRevenue = $data['totalRevenue'];
        $totalMargin = $data['totalMargin'];
        $marginPercentage = $data['marginPercentage'];
        $avgCostPerCall = $data['avgCostPerCall'];
        $avgRevenuePerCall = $data['avgRevenuePerCall'];
        $avgCostPerMinute = $data['avgCostPerMinute'];
        $avgRevenuePerMinute = $data['avgRevenuePerMinute'];
        $callsWithPricing = $data['callsWithPricing'];
        $callsWithoutPricing = $data['callsWithoutPricing'];
        $dailyData = $data['dailyData'];
    @endphp
    <div class="fi-page-content space-y-6">
        {{-- Filter Form --}}
        <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{ $this->form }}
        </div>

        {{-- Overview Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Total Calls Card --}}
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-50 dark:bg-primary-400/10">
                            <x-heroicon-o-phone class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Anrufe gesamt</p>
                        <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($totalCalls, 0, ',', '.') }}
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ number_format($totalMinutes, 0, ',', '.') }} Minuten
                        </p>
                    </div>
                </div>
            </div>

            {{-- Total Cost Card --}}
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-danger-50 dark:bg-danger-400/10">
                            <x-heroicon-o-arrow-trending-down class="h-5 w-5 text-danger-600 dark:text-danger-400" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Unsere Kosten</p>
                        <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($totalCost, 2, ',', '.') }} €
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            ⌀ {{ number_format($avgCostPerMinute, 2, ',', '.') }} €/Min
                        </p>
                    </div>
                </div>
            </div>

            {{-- Total Revenue Card --}}
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-success-50 dark:bg-success-400/10">
                            <x-heroicon-o-arrow-trending-up class="h-5 w-5 text-success-600 dark:text-success-400" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kundenpreis</p>
                        <p class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ number_format($totalRevenue, 2, ',', '.') }} €
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            ⌀ {{ number_format($avgRevenuePerMinute, 2, ',', '.') }} €/Min
                        </p>
                    </div>
                </div>
            </div>

            {{-- Margin Card --}}
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center gap-x-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $totalMargin >= 0 ? 'bg-success-50 dark:bg-success-400/10' : 'bg-danger-50 dark:bg-danger-400/10' }}">
                            <x-heroicon-o-chart-bar class="h-5 w-5 {{ $totalMargin >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}" />
                        </div>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Marge</p>
                        <p class="text-3xl font-semibold tracking-tight {{ $totalMargin >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                            {{ number_format($totalMargin, 2, ',', '.') }} €
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            {{ number_format($marginPercentage, 1, ',', '.') }}% Gewinnspanne
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Additional Metrics --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            {{-- Average per Call --}}
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-4">Durchschnitt pro Anruf</h3>
                <dl class="space-y-2">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Kosten:</dt>
                        <dd class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ number_format($avgCostPerCall, 2, ',', '.') }} €
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Erlös:</dt>
                        <dd class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ number_format($avgRevenuePerCall, 2, ',', '.') }} €
                        </dd>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Gewinn:</dt>
                        <dd class="text-sm font-semibold {{ ($avgRevenuePerCall - $avgCostPerCall) >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                            {{ number_format($avgRevenuePerCall - $avgCostPerCall, 2, ',', '.') }} €
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Pricing Coverage --}}
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-4">Preismodell-Abdeckung</h3>
                <dl class="space-y-2">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Mit Preismodell:</dt>
                        <dd class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ number_format($callsWithPricing, 0, ',', '.') }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Ohne Preismodell:</dt>
                        <dd class="text-sm font-medium text-warning-600 dark:text-warning-400">
                            {{ number_format($callsWithoutPricing, 0, ',', '.') }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">Abdeckung:</dt>
                        <dd class="text-sm font-semibold text-gray-950 dark:text-white">
                            @if($totalCalls > 0)
                                {{ number_format(($callsWithPricing / $totalCalls) * 100, 1, ',', '.') }}%
                            @else
                                0%
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- ROI Summary --}}
            <div class="fi-stats-card rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <h3 class="text-base font-semibold text-gray-950 dark:text-white mb-4">ROI Übersicht</h3>
                <dl class="space-y-2">
                    <div class="flex items-center justify-between">
                        <dt class="text-sm text-gray-600 dark:text-gray-400">Umsatz-Faktor:</dt>
                        <dd class="text-sm font-medium text-gray-950 dark:text-white">
                            @if($totalCost > 0)
                                {{ number_format($totalRevenue / $totalCost, 2, ',', '.') }}x
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div class="flex items-center justify-between pt-2 border-t border-gray-200 dark:border-gray-700">
                        <dt class="text-sm font-medium text-gray-600 dark:text-gray-400">ROI:</dt>
                        <dd class="text-sm font-semibold {{ $totalCost > 0 && (($totalRevenue - $totalCost) / $totalCost * 100) > 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                            @if($totalCost > 0)
                                {{ number_format(($totalRevenue - $totalCost) / $totalCost * 100, 1, ',', '.') }}%
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Daily Chart --}}
        @if($dailyData->isNotEmpty())
            <div class="fi-table-container rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-table-header flex items-center justify-between px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                        Tägliche Entwicklung
                    </h3>
                </div>
                
                <div class="fi-table-wrapper overflow-x-auto">
                    <table class="fi-table w-full text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-900/50">
                            <tr>
                                <th class="px-6 py-3 text-left font-medium text-gray-700 dark:text-gray-300">Datum</th>
                                <th class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Anrufe</th>
                                <th class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Minuten</th>
                                <th class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Kosten</th>
                                <th class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Erlös</th>
                                <th class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Marge</th>
                                <th class="px-6 py-3 text-right font-medium text-gray-700 dark:text-gray-300">Marge %</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($dailyData as $day)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50 transition-colors">
                                    <td class="px-6 py-4 text-gray-900 dark:text-gray-100">
                                        {{ \Carbon\Carbon::parse($day->date)->format('d.m.Y') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-900 dark:text-gray-100">
                                        {{ number_format($day->calls, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-900 dark:text-gray-100">
                                        {{ number_format($day->duration / 60, 0, ',', '.') }}
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-900 dark:text-gray-100">
                                        {{ number_format($day->cost, 2, ',', '.') }} €
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-900 dark:text-gray-100">
                                        {{ number_format($day->revenue, 2, ',', '.') }} €
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium {{ $day->margin >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                        {{ number_format($day->margin, 2, ',', '.') }} €
                                    </td>
                                    <td class="px-6 py-4 text-right font-medium {{ $day->margin >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                        @if($day->revenue > 0)
                                            {{ number_format(($day->margin / $day->revenue) * 100, 1, ',', '.') }}%
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 dark:bg-gray-900/50 font-semibold">
                            <tr>
                                <td class="px-6 py-4 text-gray-900 dark:text-gray-100">Gesamt</td>
                                <td class="px-6 py-4 text-right text-gray-900 dark:text-gray-100">
                                    {{ number_format($totalCalls, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-gray-900 dark:text-gray-100">
                                    {{ number_format($totalMinutes, 0, ',', '.') }}
                                </td>
                                <td class="px-6 py-4 text-right text-gray-900 dark:text-gray-100">
                                    {{ number_format($totalCost, 2, ',', '.') }} €
                                </td>
                                <td class="px-6 py-4 text-right text-gray-900 dark:text-gray-100">
                                    {{ number_format($totalRevenue, 2, ',', '.') }} €
                                </td>
                                <td class="px-6 py-4 text-right {{ $totalMargin >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                    {{ number_format($totalMargin, 2, ',', '.') }} €
                                </td>
                                <td class="px-6 py-4 text-right {{ $totalMargin >= 0 ? 'text-success-600 dark:text-success-400' : 'text-danger-600 dark:text-danger-400' }}">
                                    {{ number_format($marginPercentage, 1, ',', '.') }}%
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @else
            <div class="fi-empty-state-container rounded-xl bg-white p-12 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="mx-auto max-w-md text-center">
                    <x-heroicon-o-chart-bar class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" />
                    <h4 class="mt-3 text-base font-semibold text-gray-950 dark:text-white">
                        Keine Daten vorhanden
                    </h4>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Keine Daten für den ausgewählten Zeitraum gefunden.
                    </p>
                </div>
            </div>
        @endif

        {{-- Warnings --}}
        @if($callsWithoutPricing > 0)
            <div class="mt-6 rounded-lg bg-warning-50 p-4 ring-1 ring-warning-200 dark:bg-warning-400/10 dark:ring-warning-400/30">
                <div class="flex gap-3">
                    <x-heroicon-s-exclamation-triangle class="h-5 w-5 flex-shrink-0 text-warning-600 dark:text-warning-400" />
                    <div class="text-sm">
                        <p class="font-medium text-warning-800 dark:text-warning-200">
                            Fehlende Preismodelle
                        </p>
                        <p class="mt-1 text-warning-700 dark:text-warning-300">
                            {{ number_format($callsWithoutPricing, 0, ',', '.') }} Anrufe haben kein zugeordnetes Preismodell. 
                            Dies kann zu ungenauen Umsatz- und Margenberechnungen führen.
                        </p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>