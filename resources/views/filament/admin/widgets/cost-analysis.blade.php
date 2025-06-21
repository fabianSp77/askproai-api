<div class="fi-wi-widget">
    <div class="fi-wi-widget-content bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Cost Analysis</h3>
            <select wire:model.live="period" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md">
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="quarter">This Quarter</option>
            </select>
        </div>

        {{-- Main Metrics --}}
        <div class="space-y-4">
            {{-- Revenue vs Cost --}}
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Revenue</p>
                    <p class="text-2xl font-bold text-green-600 dark:text-green-400">
                        €{{ number_format($metrics['total_revenue'] ?? 0, 0) }}
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Total Costs</p>
                    <p class="text-2xl font-bold text-red-600 dark:text-red-400">
                        €{{ number_format($metrics['total_costs'] ?? 0, 0) }}
                    </p>
                </div>
            </div>

            {{-- Profit Bar --}}
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Net Profit</span>
                    <span class="text-lg font-bold {{ ($metrics['profit'] ?? 0) >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        €{{ number_format(abs($metrics['profit'] ?? 0), 0) }}
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="{{ ($metrics['profit'] ?? 0) >= 0 ? 'bg-green-600' : 'bg-red-600' }} h-2 rounded-full transition-all duration-500" 
                         style="width: {{ min(abs(($metrics['margin'] ?? 0)), 100) }}%"></div>
                </div>
            </div>

            {{-- Key Metrics Grid --}}
            <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Cost per Booking</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            €{{ number_format($metrics['cost_per_booking'] ?? 0, 2) }}
                        </span>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">ROI</span>
                        <span class="text-sm font-semibold {{ ($metrics['roi'] ?? 0) >= 100 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ number_format($metrics['roi'] ?? 0, 1) }}%
                        </span>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">CAC</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            €{{ number_format($metrics['cost_per_customer'] ?? 0, 0) }}
                        </span>
                    </div>
                </div>
                <div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Margin</span>
                        <span class="text-sm font-semibold {{ ($metrics['margin'] ?? 0) >= 30 ? 'text-green-600 dark:text-green-400' : 'text-amber-600 dark:text-amber-400' }}">
                            {{ number_format($metrics['margin'] ?? 0, 1) }}%
                        </span>
                    </div>
                </div>
            </div>

            {{-- Cost Breakdown Chart --}}
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Cost Breakdown</h4>
                <div class="space-y-2">
                    @php
                        $totalBreakdown = array_sum($breakdown);
                        $colors = ['blue', 'green', 'amber', 'gray'];
                    @endphp
                    @foreach(['ai_calls' => 'AI Calls', 'marketing' => 'Marketing', 'platform' => 'Platform', 'other' => 'Other'] as $key => $label)
                        @php
                            $percentage = $totalBreakdown > 0 ? ($breakdown[$key] / $totalBreakdown) * 100 : 0;
                            $color = $colors[array_search($key, array_keys($breakdown))];
                        @endphp
                        <div>
                            <div class="flex items-center justify-between text-sm mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ $label }}</span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    €{{ number_format($breakdown[$key], 0) }} ({{ number_format($percentage, 0) }}%)
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                <div class="bg-{{ $color }}-600 h-1.5 rounded-full" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Efficiency Score --}}
            <div class="mt-4 p-3 rounded-lg {{ ($metrics['roi'] ?? 0) >= 200 ? 'bg-green-50 dark:bg-green-900/20' : (($metrics['roi'] ?? 0) >= 100 ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-amber-50 dark:bg-amber-900/20') }}">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium {{ ($metrics['roi'] ?? 0) >= 200 ? 'text-green-700 dark:text-green-300' : (($metrics['roi'] ?? 0) >= 100 ? 'text-blue-700 dark:text-blue-300' : 'text-amber-700 dark:text-amber-300') }}">
                        Efficiency Score
                    </span>
                    <div class="flex items-center space-x-1">
                        @for($i = 1; $i <= 5; $i++)
                            @if($i <= ceil(($metrics['roi'] ?? 0) / 50))
                                <x-heroicon-s-star class="w-4 h-4 {{ ($metrics['roi'] ?? 0) >= 200 ? 'text-green-600 dark:text-green-400' : (($metrics['roi'] ?? 0) >= 100 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400') }}" />
                            @else
                                <x-heroicon-o-star class="w-4 h-4 text-gray-300 dark:text-gray-600" />
                            @endif
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>