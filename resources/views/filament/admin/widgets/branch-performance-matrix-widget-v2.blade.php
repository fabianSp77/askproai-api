<x-filament-widgets::widget>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Filial-Performance
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                        Vergleich aller Standorte
                    </p>
                </div>
                <div class="flex items-center gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-green-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">Ø ROI: <span class="font-semibold">{{ $avgRoi }}%</span></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full bg-blue-500"></div>
                        <span class="text-gray-600 dark:text-gray-400">Ø Conv: <span class="font-semibold">{{ $avgConversion }}%</span></span>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Table with better spacing and visual hierarchy --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left bg-gray-50 dark:bg-gray-900/50">
                        <th class="px-6 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider">
                            Filiale
                        </th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider text-center">
                            <button 
                                wire:click="sortBy('roi')"
                                class="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-gray-200 transition-colors {{ $sortBy === 'roi' ? 'text-gray-900 dark:text-gray-200' : '' }}"
                            >
                                ROI
                                @if($sortBy === 'roi')
                                    <x-heroicon-o-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-3 h-3" />
                                @else
                                    <x-heroicon-o-chevron-up-down class="w-3 h-3 opacity-30" />
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider text-center">
                            <button 
                                wire:click="sortBy('conversion')"
                                class="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-gray-200 transition-colors {{ $sortBy === 'conversion' ? 'text-gray-900 dark:text-gray-200' : '' }}"
                            >
                                Konversion
                                @if($sortBy === 'conversion')
                                    <x-heroicon-o-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-3 h-3" />
                                @else
                                    <x-heroicon-o-chevron-up-down class="w-3 h-3 opacity-30" />
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider text-center">
                            Anrufe
                        </th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider text-right">
                            <button 
                                wire:click="sortBy('revenue')"
                                class="inline-flex items-center gap-1 hover:text-gray-900 dark:hover:text-gray-200 transition-colors {{ $sortBy === 'revenue' ? 'text-gray-900 dark:text-gray-200' : '' }}"
                            >
                                Umsatz
                                @if($sortBy === 'revenue')
                                    <x-heroicon-o-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-3 h-3" />
                                @else
                                    <x-heroicon-o-chevron-up-down class="w-3 h-3 opacity-30" />
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-xs font-semibold text-gray-600 dark:text-gray-400 uppercase tracking-wider text-center">
                            Trend
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($branches as $index => $branch)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors group">
                            {{-- Branch Name with Trophy for #1 --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    @if($index === 0 && $sortBy === 'roi')
                                        <div class="p-1.5 rounded-lg bg-yellow-100 dark:bg-yellow-900/20">
                                            <x-heroicon-o-trophy class="w-4 h-4 text-yellow-600 dark:text-yellow-400" />
                                        </div>
                                    @else
                                        <div class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-xs font-semibold text-gray-600 dark:text-gray-400">
                                            {{ $index + 1 }}
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                            {{ $branch['branch_name'] }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            
                            {{-- ROI with Visual Indicator --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="inline-flex items-center gap-2">
                                    <div class="relative w-16 h-16">
                                        {{-- Background Circle --}}
                                        <svg class="w-16 h-16 transform -rotate-90">
                                            <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none" 
                                                    class="text-gray-200 dark:text-gray-700"></circle>
                                            <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none" 
                                                    class="{{ $branch['roi_percentage'] >= 100 ? 'text-green-500' : ($branch['roi_percentage'] >= 50 ? 'text-yellow-500' : ($branch['roi_percentage'] >= 0 ? 'text-orange-500' : 'text-red-500')) }}"
                                                    stroke-dasharray="{{ min(max($branch['roi_percentage'], 0), 200) * 0.88 }} 176"
                                                    stroke-linecap="round"></circle>
                                        </svg>
                                        <div class="absolute inset-0 flex items-center justify-center">
                                            <span class="text-sm font-bold {{ $branch['roi_percentage'] >= 100 ? 'text-green-600' : ($branch['roi_percentage'] >= 50 ? 'text-yellow-600' : ($branch['roi_percentage'] >= 0 ? 'text-orange-600' : 'text-red-600')) }}">
                                                {{ number_format($branch['roi_percentage'], 0) }}%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            {{-- Conversion Rate with Progress Bar --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm font-semibold {{ $branch['conversion_rate'] >= $avgConversion ? 'text-green-600' : 'text-gray-700 dark:text-gray-300' }}">
                                            {{ $branch['conversion_rate'] }}%
                                        </span>
                                        <span class="text-xs text-gray-500">
                                            {{ $branch['bookings'] }}/{{ $branch['calls'] }}
                                        </span>
                                    </div>
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 {{ $branch['conversion_rate'] >= 40 ? 'bg-green-500' : ($branch['conversion_rate'] >= 25 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                             style="width: {{ min($branch['conversion_rate'], 100) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            
                            {{-- Calls with Icon --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="inline-flex items-center gap-2">
                                    <x-heroicon-o-phone class="w-4 h-4 text-gray-400" />
                                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ $branch['calls'] }}
                                    </span>
                                </div>
                            </td>
                            
                            {{-- Revenue with Costs --}}
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="space-y-1">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ number_format($branch['revenue'], 0, ',', '.') }}€
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        -{{ number_format($branch['costs'], 0, ',', '.') }}€
                                    </p>
                                </div>
                            </td>
                            
                            {{-- Trend Indicator --}}
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if($branch['roi_percentage'] > $avgRoi)
                                    <div class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-green-100 dark:bg-green-900/20">
                                        <x-heroicon-o-arrow-trending-up class="w-4 h-4 text-green-600 dark:text-green-400" />
                                        <span class="text-xs font-medium text-green-700 dark:text-green-300">
                                            +{{ number_format($branch['roi_percentage'] - $avgRoi, 1) }}%
                                        </span>
                                    </div>
                                @elseif($branch['roi_percentage'] < $avgRoi)
                                    <div class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-red-100 dark:bg-red-900/20">
                                        <x-heroicon-o-arrow-trending-down class="w-4 h-4 text-red-600 dark:text-red-400" />
                                        <span class="text-xs font-medium text-red-700 dark:text-red-300">
                                            {{ number_format($branch['roi_percentage'] - $avgRoi, 1) }}%
                                        </span>
                                    </div>
                                @else
                                    <div class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-gray-100 dark:bg-gray-800">
                                        <x-heroicon-o-minus class="w-4 h-4 text-gray-500" />
                                        <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                            Ø
                                        </span>
                                    </div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center">
                                    <x-heroicon-o-building-office class="w-12 h-12 text-gray-400 mb-3" />
                                    <p class="text-gray-500">Keine Filialdaten verfügbar</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                {{-- Company Total Row --}}
                @if(count($branches) > 1)
                    <tfoot>
                        <tr class="bg-gray-50 dark:bg-gray-900/50 border-t-2 border-gray-200 dark:border-gray-700">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                                    <x-heroicon-o-building-office-2 class="w-5 h-5 text-gray-500" />
                                    Gesamt ({{ count($branches) }} Filialen)
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-lg font-bold {{ $companyTotal['summary']['roi_percentage'] >= 100 ? 'text-green-600' : ($companyTotal['summary']['roi_percentage'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ number_format($companyTotal['summary']['roi_percentage'], 1, ',', '.') }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $companyTotal['call_metrics']['total_calls'] > 0 
                                        ? round(($companyTotal['call_metrics']['calls_with_bookings'] / $companyTotal['call_metrics']['total_calls']) * 100, 1)
                                        : 0 }}%
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $companyTotal['call_metrics']['total_calls'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <div class="space-y-1">
                                    <p class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ number_format($companyTotal['summary']['total_revenue'], 0, ',', '.') }}€
                                    </p>
                                    <p class="text-xs text-gray-500 font-medium">
                                        -{{ number_format($companyTotal['summary']['total_costs'], 0, ',', '.') }}€
                                    </p>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-gray-100 dark:bg-gray-800">
                                    <x-heroicon-o-chart-bar class="w-4 h-4 text-gray-500" />
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                        Summe
                                    </span>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-filament-widgets::widget>