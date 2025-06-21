<x-filament-widgets::widget>
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
        {{-- Header with modern gradient --}}
        <div class="relative p-6 {{ $this->getRoiGradientClass($roi['summary']['roi_percentage']) }}">
            <div class="absolute inset-0 bg-gradient-to-br from-black/10 to-transparent"></div>
            <div class="relative">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-white/90">Return on Investment</h3>
                        <p class="text-sm text-white/70 mt-1">{{ $periodLabel }}</p>
                    </div>
                    <div class="text-right">
                        <div class="text-5xl font-bold text-white">{{ number_format($roi['summary']['roi_percentage'], 1, ',', '.') }}%</div>
                        <div class="text-sm text-white/70 mt-1">ROI</div>
                    </div>
                </div>
                
                {{-- Mini Trend Indicator --}}
                @if(count($trend) > 1)
                    <div class="mt-6 flex items-end gap-1" style="height: 40px;">
                        @foreach(array_slice($trend, -7) as $day)
                            <div class="flex-1 bg-white/20 rounded-t hover:bg-white/30 transition-colors relative group">
                                <div class="absolute bottom-0 w-full bg-white/50 rounded-t transition-all" 
                                     style="height: {{ min(max($day['roi'] / 2, 5), 100) }}%"></div>
                                <div class="opacity-0 group-hover:opacity-100 absolute -top-8 left-1/2 -translate-x-1/2 bg-black/80 text-white text-xs px-2 py-1 rounded whitespace-nowrap">
                                    {{ $day['date'] }}: {{ $day['roi'] }}%
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
        
        {{-- Key Metrics Grid with icons --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 p-6 bg-gray-50 dark:bg-gray-900/50">
            {{-- Revenue --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 rounded-lg bg-green-100 dark:bg-green-900/20">
                        <x-heroicon-o-currency-euro class="w-5 h-5 text-green-600 dark:text-green-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Umsatz</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($roi['summary']['total_revenue'], 2, ',', '.') }}€
                </div>
            </div>
            
            {{-- Costs --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 rounded-lg bg-red-100 dark:bg-red-900/20">
                        <x-heroicon-o-phone class="w-5 h-5 text-red-600 dark:text-red-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Kosten</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($roi['summary']['total_costs'], 2, ',', '.') }}€
                </div>
            </div>
            
            {{-- Profit --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 rounded-lg bg-blue-100 dark:bg-blue-900/20">
                        <x-heroicon-o-banknotes class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Gewinn</span>
                </div>
                <div class="text-2xl font-bold {{ $roi['summary']['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($roi['summary']['net_profit'], 2, ',', '.') }}€
                </div>
            </div>
            
            {{-- Cost per Booking --}}
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-100 dark:border-gray-700">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 rounded-lg bg-purple-100 dark:bg-purple-900/20">
                        <x-heroicon-o-calculator class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                    </div>
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-400">€/Termin</span>
                </div>
                <div class="text-2xl font-bold text-gray-900 dark:text-white">
                    {{ number_format($costPerBooking, 2, ',', '.') }}€
                </div>
            </div>
        </div>
        
        {{-- Business Hours Analysis with modern cards --}}
        <div class="p-6">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Geschäftszeiten-Analyse</h4>
            
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                {{-- Business Hours Card --}}
                <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 p-5 border border-blue-200 dark:border-blue-800">
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-blue-200 dark:bg-blue-800">
                                    <x-heroicon-o-sun class="w-5 h-5 text-blue-700 dark:text-blue-300" />
                                </div>
                                <div>
                                    <span class="font-semibold text-blue-900 dark:text-blue-100">
                                        Geschäftszeiten
                                    </span>
                                    <p class="text-xs text-blue-700 dark:text-blue-300">
                                        {{ $roi['business_hours_analysis']['business_hours_range']['start'] }}-{{ $roi['business_hours_analysis']['business_hours_range']['end'] }} Uhr
                                    </p>
                                </div>
                            </div>
                            <span class="text-2xl font-bold {{ $this->getRoiTextColorClass($roi['business_hours_analysis']['business_hours']['roi']) }}">
                                {{ number_format($roi['business_hours_analysis']['business_hours']['roi'], 1, ',', '.') }}%
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-blue-700 dark:text-blue-300">Umsatz</span>
                                <p class="font-semibold text-blue-900 dark:text-blue-100">
                                    {{ number_format($roi['business_hours_analysis']['business_hours']['revenue'], 2, ',', '.') }}€
                                </p>
                            </div>
                            <div>
                                <span class="text-blue-700 dark:text-blue-300">Kosten</span>
                                <p class="font-semibold text-blue-900 dark:text-blue-100">
                                    {{ number_format($roi['business_hours_analysis']['business_hours']['costs'], 2, ',', '.') }}€
                                </p>
                            </div>
                            <div>
                                <span class="text-blue-700 dark:text-blue-300">Anrufe</span>
                                <p class="font-semibold text-blue-900 dark:text-blue-100">
                                    {{ $roi['business_hours_analysis']['business_hours']['calls'] }}
                                </p>
                            </div>
                            <div>
                                <span class="text-blue-700 dark:text-blue-300">Termine</span>
                                <p class="font-semibold text-blue-900 dark:text-blue-100">
                                    {{ $roi['business_hours_analysis']['business_hours']['bookings'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Decorative Background Element --}}
                    <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-blue-200 dark:bg-blue-700 rounded-full opacity-10"></div>
                </div>
                
                {{-- After Hours Card --}}
                <div class="relative overflow-hidden rounded-xl bg-gradient-to-br from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 p-5 border border-indigo-200 dark:border-indigo-800">
                    <div class="relative z-10">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-indigo-200 dark:bg-indigo-800">
                                    <x-heroicon-o-moon class="w-5 h-5 text-indigo-700 dark:text-indigo-300" />
                                </div>
                                <div>
                                    <span class="font-semibold text-indigo-900 dark:text-indigo-100">
                                        Außerhalb Geschäftszeiten
                                    </span>
                                    <p class="text-xs text-indigo-700 dark:text-indigo-300">
                                        24/7 Service
                                    </p>
                                </div>
                            </div>
                            <span class="text-2xl font-bold {{ $this->getRoiTextColorClass($roi['business_hours_analysis']['after_hours']['roi']) }}">
                                {{ number_format($roi['business_hours_analysis']['after_hours']['roi'], 1, ',', '.') }}%
                            </span>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-indigo-700 dark:text-indigo-300">Umsatz</span>
                                <p class="font-semibold text-indigo-900 dark:text-indigo-100">
                                    {{ number_format($roi['business_hours_analysis']['after_hours']['revenue'], 2, ',', '.') }}€
                                </p>
                            </div>
                            <div>
                                <span class="text-indigo-700 dark:text-indigo-300">Kosten</span>
                                <p class="font-semibold text-indigo-900 dark:text-indigo-100">
                                    {{ number_format($roi['business_hours_analysis']['after_hours']['costs'], 2, ',', '.') }}€
                                </p>
                            </div>
                            <div>
                                <span class="text-indigo-700 dark:text-indigo-300">Anrufe</span>
                                <p class="font-semibold text-indigo-900 dark:text-indigo-100">
                                    {{ $roi['business_hours_analysis']['after_hours']['calls'] }}
                                </p>
                            </div>
                            <div>
                                <span class="text-indigo-700 dark:text-indigo-300">Termine</span>
                                <p class="font-semibold text-indigo-900 dark:text-indigo-100">
                                    {{ $roi['business_hours_analysis']['after_hours']['bookings'] }}
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Decorative Background Element --}}
                    <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-indigo-200 dark:bg-indigo-700 rounded-full opacity-10"></div>
                </div>
            </div>
        </div>
    </div>
</x-filament-widgets::widget>