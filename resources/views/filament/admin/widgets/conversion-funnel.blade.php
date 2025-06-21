<div class="fi-wi-widget">
    <div class="fi-wi-widget-content bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Conversion Funnel</h3>
            <select wire:model.live="selectedRange" class="text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-md">
                @foreach($timeRanges as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        {{-- Funnel Visualization --}}
        <div class="space-y-3">
            @foreach(($funnelData['stages'] ?? []) as $index => $stage)
                <div class="relative">
                    {{-- Stage Info --}}
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center space-x-2">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $stage['name'] }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">({{ number_format($stage['count']) }})</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $stage['percentage'] }}%</span>
                    </div>
                    
                    {{-- Progress Bar --}}
                    <div class="relative">
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-6">
                            <div class="bg-{{ $stage['color'] }}-600 h-6 rounded-full flex items-center justify-end pr-2 transition-all duration-500" 
                                 style="width: {{ $stage['percentage'] }}%">
                                @if($stage['percentage'] > 20)
                                    <span class="text-xs text-white font-medium">{{ number_format($stage['count']) }}</span>
                                @endif
                            </div>
                        </div>
                        @if($stage['percentage'] <= 20 && $stage['count'] > 0)
                            <span class="absolute right-2 top-1 text-xs text-gray-600 dark:text-gray-400 font-medium">
                                {{ number_format($stage['count']) }}
                            </span>
                        @endif
                    </div>
                    
                    {{-- Drop-off Indicator --}}
                    @if(!$loop->last && isset($funnelData['drop_off_analysis'][$index]))
                        @php
                            $dropOff = $funnelData['drop_off_analysis'][$index];
                        @endphp
                        <div class="mt-1 flex items-center space-x-2 text-xs">
                            <x-heroicon-m-arrow-down class="w-3 h-3 text-red-500" />
                            <span class="text-red-600 dark:text-red-400">
                                {{ number_format($dropOff['lost']) }} lost ({{ $dropOff['rate'] }}% drop-off)
                            </span>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Summary Metrics --}}
        <div class="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Overall Conversion</p>
                    <p class="text-2xl font-bold {{ ($funnelData['overall_conversion'] ?? 0) >= 50 ? 'text-green-600 dark:text-green-400' : (($funnelData['overall_conversion'] ?? 0) >= 30 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                        {{ $funnelData['overall_conversion'] ?? 0 }}%
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Completion Rate</p>
                    <p class="text-2xl font-bold {{ ($funnelData['completion_rate'] ?? 0) >= 80 ? 'text-green-600 dark:text-green-400' : (($funnelData['completion_rate'] ?? 0) >= 60 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                        {{ $funnelData['completion_rate'] ?? 0 }}%
                    </p>
                </div>
            </div>
        </div>

        {{-- Insights --}}
        @if(($funnelData['overall_conversion'] ?? 0) < 30)
            <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 rounded-lg">
                <div class="flex items-start space-x-2">
                    <x-heroicon-o-light-bulb class="w-5 h-5 text-amber-600 dark:text-amber-400 mt-0.5" />
                    <div class="text-sm text-amber-800 dark:text-amber-200">
                        <p class="font-medium">Low Conversion Rate Detected</p>
                        <p class="mt-1">Consider reviewing your AI agent's script and booking process to improve conversion.</p>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>