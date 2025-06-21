{{-- Universal KPI Widget Template --}}
<x-filament-widgets::widget>
    <x-filament::section>
        {{-- Widget Header --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2">
                @if($icon)
                    <x-dynamic-component :component="$icon" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                @endif
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    {{ $title }}
                </h2>
            </div>
            
            {{-- Optional: Refresh Indicator --}}
            @if($config['auto_refresh'] ?? false)
                <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                    <x-heroicon-o-arrow-path class="w-3 h-3 animate-spin" />
                    <span>Live</span>
                </div>
            @endif
        </div>

        {{-- Error State --}}
        @if(!$hasData && $errorMessage)
            <div class="p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                <div class="flex items-center gap-3">
                    <x-heroicon-o-information-circle class="w-5 h-5 text-gray-400" />
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $errorMessage }}
                    </p>
                </div>
                @if(isset($error_details))
                    <details class="mt-2">
                        <summary class="text-xs text-gray-500 cursor-pointer">Debug Info</summary>
                        <pre class="mt-1 text-xs text-gray-500">{{ $error_details }}</pre>
                    </details>
                @endif
            </div>
        @else
            {{-- KPI Grid --}}
            <div class="grid gap-4 
                @if(($config['columns'] ?? 3) == 2) grid-cols-1 sm:grid-cols-2
                @elseif(($config['columns'] ?? 3) == 3) grid-cols-1 sm:grid-cols-2 lg:grid-cols-3
                @elseif(($config['columns'] ?? 3) == 4) grid-cols-2 sm:grid-cols-2 lg:grid-cols-4
                @else grid-cols-1 sm:grid-cols-2 lg:grid-cols-3
                @endif">
                
                @foreach($kpis as $kpi)
                    <div class="relative p-4 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow duration-200"
                         @if($kpi['tooltip']) 
                         x-data="{ showTooltip: false }"
                         @mouseenter="showTooltip = true"
                         @mouseleave="showTooltip = false"
                         @endif>
                        
                        {{-- KPI Label --}}
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-400">
                                {{ $kpi['label'] }}
                            </span>
                            @if($kpi['icon'])
                                <x-dynamic-component 
                                    :component="$kpi['icon']" 
                                    class="w-4 h-4 text-{{ $kpi['color'] }}-500"
                                />
                            @endif
                        </div>
                        
                        {{-- KPI Value --}}
                        <div class="flex items-baseline justify-between">
                            <span class="text-2xl font-bold text-gray-900 dark:text-white">
                                {{ $kpi['value'] }}
                            </span>
                            
                            {{-- Trend Indicator --}}
                            @if($config['show_trends'] ?? true)
                                <div class="flex items-center gap-1">
                                    @if($kpi['trend'] === 'up')
                                        <x-heroicon-m-arrow-trending-up 
                                            class="w-4 h-4 text-{{ $kpi['color'] }}-500"
                                        />
                                    @elseif($kpi['trend'] === 'down')
                                        <x-heroicon-m-arrow-trending-down 
                                            class="w-4 h-4 text-{{ $kpi['color'] }}-500"
                                        />
                                    @else
                                        <x-heroicon-m-minus 
                                            class="w-4 h-4 text-gray-400"
                                        />
                                    @endif
                                    
                                    <span class="text-sm font-medium text-{{ $kpi['color'] }}-600 dark:text-{{ $kpi['color'] }}-400">
                                        @if($kpi['is_percentage'] && $kpi['change'] != 0)
                                            {{ $kpi['change'] > 0 ? '+' : '' }}{{ $kpi['change'] }}%
                                        @elseif($kpi['change'] != 0)
                                            {{ $kpi['change'] > 0 ? '+' : '' }}{{ $kpi['change'] }}
                                        @else
                                            0
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>
                        
                        {{-- Previous Value (subtle) --}}
                        @if(isset($kpi['previous']) && $kpi['previous'] != 0)
                            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Vorperiode: 
                                @if($kpi['is_currency'])
                                    {{ number_format($kpi['previous'], 0, ',', '.') }}€
                                @elseif($kpi['is_percentage'])
                                    {{ number_format($kpi['previous'], 1, ',', '.') }}%
                                @else
                                    {{ number_format($kpi['previous'], 0, ',', '.') }}
                                @endif
                            </div>
                        @endif
                        
                        {{-- Tooltip --}}
                        @if($kpi['tooltip'] && $config['show_tooltips'] ?? true)
                            <div x-show="showTooltip"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100"
                                 x-transition:leave="transition ease-in duration-150"
                                 x-transition:leave-start="opacity-100 transform scale-100"
                                 x-transition:leave-end="opacity-0 transform scale-95"
                                 class="absolute z-50 bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-lg whitespace-pre-line max-w-xs"
                                 style="display: none;">
                                {{ $kpi['tooltip'] }}
                                <div class="absolute top-full left-1/2 transform -translate-x-1/2 -mt-1">
                                    <div class="w-0 h-0 border-l-4 border-r-4 border-t-4 border-transparent border-t-gray-900"></div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>

{{-- Mobile Optimierungen --}}
<style>
    @media (max-width: 640px) {
        .grid-cols-2 {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }
</style>