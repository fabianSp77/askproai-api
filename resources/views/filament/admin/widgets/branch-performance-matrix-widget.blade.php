<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-xl bg-white dark:bg-gray-800 shadow-sm">
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Filial-Performance Matrix
                </h3>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>Ø ROI: {{ $avgRoi }}%</span>
                    <span>•</span>
                    <span>Ø Konversion: {{ $avgConversion }}%</span>
                </div>
            </div>
        </div>
        
        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="text-left border-b border-gray-200 dark:border-gray-700">
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Filiale
                        </th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <button 
                                wire:click="sortBy('conversion')"
                                class="flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200"
                            >
                                Conv%
                                @if($sortBy === 'conversion')
                                    <x-heroicon-o-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-3 h-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <button 
                                wire:click="sortBy('calls')"
                                class="flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200"
                            >
                                ⌀Call
                                @if($sortBy === 'calls')
                                    <x-heroicon-o-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-3 h-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            €/Term
                        </th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <button 
                                wire:click="sortBy('revenue')"
                                class="flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200"
                            >
                                Umsatz
                                @if($sortBy === 'revenue')
                                    <x-heroicon-o-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-3 h-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <button 
                                wire:click="sortBy('roi')"
                                class="flex items-center gap-1 hover:text-gray-700 dark:hover:text-gray-200"
                            >
                                ROI%
                                @if($sortBy === 'roi')
                                    <x-heroicon-o-chevron-{{ $sortDirection === 'asc' ? 'up' : 'down' }} class="w-3 h-3" />
                                @endif
                            </button>
                        </th>
                        <th class="px-6 py-3 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($branches as $index => $branch)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                            {{-- Branch Name --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    @if($index === 0 && $sortBy === 'roi')
                                        <x-heroicon-o-trophy class="w-4 h-4 text-yellow-500" />
                                    @endif
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $branch['branch_name'] }}
                                    </span>
                                </div>
                            </td>
                            
                            {{-- Conversion Rate --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium {{ $branch['conversion_rate'] >= $avgConversion ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $branch['conversion_rate'] }}%
                                    </span>
                                    <div class="w-16 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full {{ $branch['conversion_rate'] >= 40 ? 'bg-green-500' : ($branch['conversion_rate'] >= 25 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                                             style="width: {{ min($branch['conversion_rate'], 100) }}%"></div>
                                    </div>
                                </div>
                            </td>
                            
                            {{-- Average Call Time --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    {{ $branch['calls'] }} Anrufe
                                </span>
                            </td>
                            
                            {{-- Cost per Booking --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $costPerBooking = $branch['bookings'] > 0 
                                        ? round($branch['costs'] / $branch['bookings'], 2)
                                        : 0;
                                @endphp
                                <span class="text-sm font-medium {{ $costPerBooking <= 2 ? 'text-green-600' : ($costPerBooking <= 4 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ number_format($costPerBooking, 2, ',', '.') }}€
                                </span>
                            </td>
                            
                            {{-- Revenue --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ number_format($branch['revenue'], 0, ',', '.') }}€
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ number_format($branch['costs'], 0, ',', '.') }}€ Kosten
                                    </div>
                                </div>
                            </td>
                            
                            {{-- ROI --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    <span class="text-lg font-bold {{ $branch['roi_percentage'] >= 100 ? 'text-green-600' : ($branch['roi_percentage'] >= 50 ? 'text-yellow-600' : ($branch['roi_percentage'] >= 0 ? 'text-orange-600' : 'text-red-600')) }}">
                                        {{ number_format($branch['roi_percentage'], 1, ',', '.') }}%
                                    </span>
                                    @if($branch['roi_percentage'] > $avgRoi)
                                        <x-heroicon-o-arrow-up class="w-3 h-3 text-green-500" />
                                    @elseif($branch['roi_percentage'] < $avgRoi)
                                        <x-heroicon-o-arrow-down class="w-3 h-3 text-red-500" />
                                    @endif
                                </div>
                            </td>
                            
                            {{-- Status --}}
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $status = $this->getStatusBadge($branch['roi_percentage']);
                                @endphp
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-{{ $status['color'] }}-100 text-{{ $status['color'] }}-800 dark:bg-{{ $status['color'] }}-900/20 dark:text-{{ $status['color'] }}-200">
                                    <x-dynamic-component :component="$status['icon']" class="w-3 h-3" />
                                    {{ $status['label'] }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                Keine Filialdaten verfügbar
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                
                {{-- Company Total Row --}}
                @if(count($branches) > 1)
                    <tfoot>
                        <tr class="bg-gray-50 dark:bg-gray-900/50 border-t-2 border-gray-300 dark:border-gray-600">
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    Gesamt
                                </span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $companyTotal['call_metrics']['total_calls'] > 0 
                                        ? round(($companyTotal['call_metrics']['calls_with_bookings'] / $companyTotal['call_metrics']['total_calls']) * 100, 1)
                                        : 0 }}%
                                </span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $companyTotal['call_metrics']['total_calls'] }}
                                </span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $companyTotal['call_metrics']['calls_with_bookings'] > 0 
                                        ? number_format($companyTotal['call_metrics']['total_cost'] / $companyTotal['call_metrics']['calls_with_bookings'], 2, ',', '.')
                                        : '0,00' }}€
                                </span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-bold text-gray-900 dark:text-white">
                                        {{ number_format($companyTotal['summary']['total_revenue'], 0, ',', '.') }}€
                                    </div>
                                    <div class="text-xs text-gray-500 font-medium">
                                        {{ number_format($companyTotal['summary']['total_costs'], 0, ',', '.') }}€
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                <span class="text-lg font-bold {{ $companyTotal['summary']['roi_percentage'] >= 100 ? 'text-green-600' : ($companyTotal['summary']['roi_percentage'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                                    {{ number_format($companyTotal['summary']['roi_percentage'], 1, ',', '.') }}%
                                </span>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap">
                                {{-- Empty for alignment --}}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
        
        {{-- Performance Summary --}}
        @if($topPerformer && $bottomPerformer && $topPerformer['branch_id'] !== $bottomPerformer['branch_id'])
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-arrow-trending-up class="w-5 h-5 text-green-500" />
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Top Performer:</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white ml-2">
                                {{ $topPerformer['branch_name'] }} ({{ number_format($topPerformer['roi_percentage'], 1, ',', '.') }}% ROI)
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <x-heroicon-o-arrow-trending-down class="w-5 h-5 text-red-500" />
                        <div>
                            <span class="text-sm text-gray-600 dark:text-gray-400">Handlungsbedarf:</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white ml-2">
                                {{ $bottomPerformer['branch_name'] }} ({{ number_format($bottomPerformer['roi_percentage'], 1, ',', '.') }}% ROI)
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>