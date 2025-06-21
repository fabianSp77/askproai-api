<x-filament-widgets::widget>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
        {{-- System Status Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">System Status</h3>
                    <div class="w-2 h-2 rounded-full animate-pulse {{ $systemHealth['overall'] === 'operational' ? 'bg-green-500' : ($systemHealth['overall'] === 'degraded' ? 'bg-yellow-500' : 'bg-red-500') }}"></div>
                </div>
                
                <div class="space-y-3">
                    {{-- Cal.com Status --}}
                    <div class="flex items-center justify-between p-3 rounded-lg {{ $systemHealth['calcom']['status'] ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 rounded-lg {{ $systemHealth['calcom']['status'] ? 'bg-green-100 dark:bg-green-800' : 'bg-red-100 dark:bg-red-800' }}">
                                <x-heroicon-o-calendar class="w-4 h-4 {{ $systemHealth['calcom']['status'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" />
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Cal.com</span>
                        </div>
                        <span class="text-xs font-medium {{ $systemHealth['calcom']['status'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            @if($systemHealth['calcom']['status'])
                                {{ $systemHealth['calcom']['responseTime'] }}ms
                            @else
                                Offline
                            @endif
                        </span>
                    </div>
                    
                    {{-- Retell Status --}}
                    <div class="flex items-center justify-between p-3 rounded-lg {{ $systemHealth['retell']['status'] ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <div class="flex items-center gap-2">
                            <div class="p-1.5 rounded-lg {{ $systemHealth['retell']['status'] ? 'bg-green-100 dark:bg-green-800' : 'bg-red-100 dark:bg-red-800' }}">
                                <x-heroicon-o-phone class="w-4 h-4 {{ $systemHealth['retell']['status'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}" />
                            </div>
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Retell AI</span>
                        </div>
                        <span class="text-xs font-medium {{ $systemHealth['retell']['status'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            @if($systemHealth['retell']['status'])
                                {{ $systemHealth['retell']['responseTime'] }}ms
                            @else
                                Offline
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Live Calls Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktive Anrufe</h3>
                    <x-heroicon-o-phone class="w-4 h-4 text-gray-400" />
                </div>
                
                <div class="flex items-baseline gap-2 mb-4">
                    <span class="text-4xl font-bold text-gray-900 dark:text-white">{{ $liveCalls['active'] }}</span>
                    <span class="text-sm text-gray-500">aktiv</span>
                </div>
                
                <div class="flex items-center justify-between text-sm">
                    <span class="text-gray-600 dark:text-gray-400">Ø Dauer</span>
                    <span class="font-medium text-gray-900 dark:text-white">{{ $liveCalls['avgDuration'] }}</span>
                </div>
                
                {{-- Anomaly Alert --}}
                @if(count($liveCalls['anomalies']) > 0)
                    <div class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                        <div class="flex items-start gap-2">
                            <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-yellow-600 flex-shrink-0 mt-0.5" />
                            <div class="text-xs">
                                <p class="font-medium text-yellow-800 dark:text-yellow-200">Auffällige Anrufe</p>
                                @foreach($liveCalls['anomalies']->take(1) as $anomaly)
                                    <p class="text-yellow-700 dark:text-yellow-300 mt-1">
                                        {{ $anomaly['branch'] }}: {{ $anomaly['duration'] }}
                                    </p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        
        {{-- Conversion Rate Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Konversionsrate</h3>
                    <x-heroicon-o-arrow-trending-up class="w-4 h-4 text-gray-400" />
                </div>
                
                <div class="flex items-baseline gap-2 mb-4">
                    <span class="text-4xl font-bold {{ $conversion['rate'] >= 40 ? 'text-green-600' : ($conversion['rate'] >= 25 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ $conversion['rate'] }}%
                    </span>
                    @if($conversion['trend'] != 0)
                        <span class="text-sm font-medium {{ $conversion['trend'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $conversion['trend'] > 0 ? '+' : '' }}{{ $conversion['trend'] }}%
                        </span>
                    @endif
                </div>
                
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Termine heute</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ $conversion['convertedCalls'] }}/{{ $conversion['totalCalls'] }}</span>
                    </div>
                    
                    {{-- Visual Progress Bar --}}
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500 {{ $conversion['rate'] >= 40 ? 'bg-green-500' : ($conversion['rate'] >= 25 ? 'bg-yellow-500' : 'bg-red-500') }}" 
                             style="width: {{ min($conversion['rate'], 100) }}%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Cost per Booking Card --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 overflow-hidden">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400">Kosten/Termin</h3>
                    <x-heroicon-o-currency-euro class="w-4 h-4 text-gray-400" />
                </div>
                
                <div class="flex items-baseline gap-2 mb-4">
                    <span class="text-4xl font-bold {{ $costPerBooking['cost'] <= 2 ? 'text-green-600' : ($costPerBooking['cost'] <= 4 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ number_format($costPerBooking['cost'], 2, ',', '.') }}€
                    </span>
                </div>
                
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">Gesamt heute</span>
                        <span class="font-medium text-gray-900 dark:text-white">{{ number_format($costPerBooking['totalCosts'], 2, ',', '.') }}€</span>
                    </div>
                    
                    {{-- Cost Level Indicator --}}
                    <div class="flex items-center gap-1 mt-2">
                        @php
                            $costLevel = $costPerBooking['cost'] <= 2 ? 1 : ($costPerBooking['cost'] <= 3 ? 2 : ($costPerBooking['cost'] <= 4 ? 3 : 4));
                        @endphp
                        @for($i = 1; $i <= 4; $i++)
                            <div class="flex-1 h-2 rounded-full {{ $i <= $costLevel ? ($costLevel <= 2 ? 'bg-green-500' : ($costLevel == 3 ? 'bg-yellow-500' : 'bg-red-500')) : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    {{-- Anomaly Alert Strip --}}
    @if(count($anomalies) > 0)
        <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-2xl border border-red-100 dark:border-red-800">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
                    <x-heroicon-o-exclamation-circle class="w-5 h-5" />
                    <span class="font-medium text-sm">Wichtige Hinweise:</span>
                </div>
                <div class="flex gap-2 overflow-x-auto">
                    @foreach($anomalies->take(3) as $anomaly)
                        <div class="flex-shrink-0 px-3 py-1.5 rounded-lg text-xs font-medium {{ $anomaly['severity'] === 'critical' ? 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-200' }}">
                            {{ $anomaly['branch'] }}: {{ $anomaly['message'] }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</x-filament-widgets::widget>