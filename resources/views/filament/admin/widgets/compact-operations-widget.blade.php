<x-filament-widgets::widget>
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        {{-- System Status - Compact Card --}}
        <div class="relative group">
            <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200 h-full">
                {{-- Status Indicator --}}
                <div class="absolute top-3 right-3">
                    <div class="relative">
                        <div class="w-2.5 h-2.5 rounded-full {{ $systemHealth['overall'] === 'operational' ? 'bg-green-500' : ($systemHealth['overall'] === 'degraded' ? 'bg-yellow-500' : 'bg-red-500') }}"></div>
                        <div class="absolute inset-0 w-2.5 h-2.5 rounded-full {{ $systemHealth['overall'] === 'operational' ? 'bg-green-500' : ($systemHealth['overall'] === 'degraded' ? 'bg-yellow-500' : 'bg-red-500') }} animate-ping opacity-25"></div>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">System Status</p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white mt-1">
                            {{ $systemHealth['overall'] === 'operational' ? 'Operational' : ($systemHealth['overall'] === 'degraded' ? 'Degraded' : 'Offline') }}
                        </p>
                    </div>
                    
                    {{-- Mini Status Grid --}}
                    <div class="grid grid-cols-2 gap-2">
                        <div class="flex items-center gap-1.5">
                            <div class="w-1.5 h-1.5 rounded-full {{ $systemHealth['calcom']['status'] ? 'bg-green-500' : 'bg-red-500' }}"></div>
                            <span class="text-xs text-gray-600 dark:text-gray-400">Cal.com</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <div class="w-1.5 h-1.5 rounded-full {{ $systemHealth['retell']['status'] ? 'bg-green-500' : 'bg-red-500' }}"></div>
                            <span class="text-xs text-gray-600 dark:text-gray-400">Retell</span>
                        </div>
                    </div>
                </div>
                
                {{-- Hover Detail --}}
                <div class="absolute bottom-full left-0 right-0 mb-2 p-3 bg-gray-900 dark:bg-gray-700 text-white rounded-lg shadow-xl opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none z-10">
                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span>Cal.com:</span>
                            <span class="{{ $systemHealth['calcom']['status'] ? 'text-green-400' : 'text-red-400' }}">
                                {{ $systemHealth['calcom']['status'] ? $systemHealth['calcom']['responseTime'] . 'ms' : 'Offline' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span>Retell AI:</span>
                            <span class="{{ $systemHealth['retell']['status'] ? 'text-green-400' : 'text-red-400' }}">
                                {{ $systemHealth['retell']['status'] ? $systemHealth['retell']['responseTime'] . 'ms' : 'Offline' }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Active Calls - With Sparkline --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Aktive Anrufe</p>
                    <p class="text-3xl font-bold text-gray-900 dark:text-white mt-1">{{ $liveCalls['active'] }}</p>
                </div>
                <div class="flex flex-col items-end">
                    <x-heroicon-o-phone class="w-4 h-4 text-gray-400 mb-1" />
                    <span class="text-xs text-gray-500">{{ $liveCalls['avgDuration'] }} Ø</span>
                </div>
            </div>
            
            {{-- Mini Sparkline --}}
            <div class="h-8 flex items-end gap-0.5">
                @foreach($liveCalls['sparkline'] as $value)
                    <div class="flex-1 bg-blue-200 dark:bg-blue-700 rounded-t opacity-60 hover:opacity-100 transition-opacity"
                         style="height: {{ $value }}%"></div>
                @endforeach
            </div>
            
            {{-- Anomaly Indicator --}}
            @if(count($liveCalls['anomalies']) > 0)
                <div class="mt-2 flex items-center gap-1">
                    <div class="w-1.5 h-1.5 bg-yellow-500 rounded-full"></div>
                    <span class="text-xs text-yellow-600 dark:text-yellow-400">{{ count($liveCalls['anomalies']) }} Anomalie(n)</span>
                </div>
            @endif
        </div>
        
        {{-- Conversion Rate - With Progress Ring --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Konversion</p>
                    <div class="flex items-baseline gap-2 mt-1">
                        <p class="text-3xl font-bold {{ $conversion['rate'] >= 40 ? 'text-green-600' : ($conversion['rate'] >= 25 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $conversion['rate'] }}%
                        </p>
                        @if($conversion['trend'] != 0)
                            <span class="text-xs font-medium {{ $conversion['trend'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $conversion['trend'] > 0 ? '↑' : '↓' }}{{ abs($conversion['trend']) }}%
                            </span>
                        @endif
                    </div>
                </div>
                
                {{-- Circular Progress --}}
                <div class="relative w-12 h-12">
                    <svg class="w-12 h-12 transform -rotate-90">
                        <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="4" fill="none" class="text-gray-200 dark:text-gray-700"></circle>
                        <circle cx="24" cy="24" r="20" stroke="currentColor" stroke-width="4" fill="none" 
                                class="{{ $conversion['rate'] >= 40 ? 'text-green-500' : ($conversion['rate'] >= 25 ? 'text-yellow-500' : 'text-red-500') }}"
                                stroke-dasharray="{{ $conversion['rate'] * 1.256 }} 125.6"
                                stroke-linecap="round"></circle>
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-xs font-bold text-gray-700 dark:text-gray-300">{{ $conversion['convertedCalls'] }}</span>
                    </div>
                </div>
            </div>
            
            <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                {{ $conversion['convertedCalls'] }} von {{ $conversion['totalCalls'] }} Anrufen
            </div>
        </div>
        
        {{-- Cost per Booking - With Bar Chart --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl p-4 border border-gray-200 dark:border-gray-700 hover:shadow-lg transition-all duration-200">
            <div class="flex items-start justify-between mb-2">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">€ pro Termin</p>
                    <p class="text-3xl font-bold {{ $costPerBooking['cost'] <= 2 ? 'text-green-600' : ($costPerBooking['cost'] <= 4 ? 'text-yellow-600' : 'text-red-600') }} mt-1">
                        {{ number_format($costPerBooking['cost'], 2, ',', '.') }}
                    </p>
                </div>
                <x-heroicon-o-currency-euro class="w-4 h-4 text-gray-400" />
            </div>
            
            {{-- Mini Bar Chart --}}
            <div class="flex items-end gap-0.5 h-8">
                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-t" style="height: 20%"></div>
                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-t" style="height: 40%"></div>
                <div class="flex-1 {{ $costPerBooking['cost'] <= 2 ? 'bg-green-500' : ($costPerBooking['cost'] <= 4 ? 'bg-yellow-500' : 'bg-red-500') }} rounded-t" style="height: {{ min($costPerBooking['cost'] * 20, 100) }}%"></div>
                <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-t" style="height: 30%"></div>
            </div>
            
            <div class="mt-2 text-xs text-gray-600 dark:text-gray-400">
                Gesamt: {{ number_format($costPerBooking['totalCosts'], 2, ',', '.') }}€
            </div>
        </div>
    </div>
</x-filament-widgets::widget>