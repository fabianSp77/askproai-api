{{-- Customer Funnel Widget --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    🔄 Lead-Funnel
                </h2>
            </div>
            
            <div class="space-y-3">
                @foreach($this->getFunnelData() as $index => $stage)
                    <div class="relative">
                        {{-- Stage Bar --}}
                        <div class="relative overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800">
                            {{-- Progress Fill --}}
                            <div class="absolute inset-y-0 left-0 bg-{{ $stage['color'] }}-100 dark:bg-{{ $stage['color'] }}-900/20 transition-all duration-500"
                                 style="width: {{ $stage['percentage'] }}%">
                            </div>
                            
                            {{-- Content --}}
                            <div class="relative flex items-center justify-between p-3">
                                <div class="flex items-center gap-3">
                                    <x-dynamic-component 
                                        :component="$stage['icon']" 
                                        class="w-5 h-5 text-{{ $stage['color'] }}-600 dark:text-{{ $stage['color'] }}-400"
                                    />
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $stage['label'] }}
                                        </p>
                                        @if(isset($stage['conversion_from_previous']) && $index > 0)
                                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $stage['conversion_from_previous'] }}% Conversion
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <p class="text-lg font-bold text-gray-900 dark:text-white">
                                        {{ number_format($stage['value'], 0, ',', '.') }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $stage['percentage'] }}%
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Connector Arrow (except for last item) --}}
                        @if(!$loop->last)
                            <div class="flex justify-center -my-1 relative z-10">
                                <div class="w-0 h-0 border-l-8 border-r-8 border-t-8 border-transparent border-t-gray-300 dark:border-t-gray-600"></div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            
            {{-- Summary --}}
            @php
                $data = $this->getFunnelData();
                $totalConversion = $data[0]['value'] > 0 ? round(($data[count($data)-1]['value'] / $data[0]['value']) * 100, 1) : 0;
            @endphp
            
            <div class="pt-3 border-t border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">
                        Gesamt-Conversion
                    </span>
                    <span class="text-sm font-bold text-{{ $totalConversion > 20 ? 'success' : ($totalConversion > 10 ? 'warning' : 'danger') }}-600">
                        {{ $totalConversion }}%
                    </span>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>