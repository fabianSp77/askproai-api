<x-filament-widgets::widget>
    <div class="relative overflow-hidden rounded-xl bg-gradient-to-r from-slate-50 to-slate-100 dark:from-gray-800 dark:to-gray-900 p-6">
        {{-- Background Pattern --}}
        <div class="absolute inset-0 opacity-5">
            <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width="60" height="60" viewBox="0 0 60 60" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="none" fill-rule="evenodd"%3E%3Cg fill="%239C92AC" fill-opacity="0.4"%3E%3Cpath d="M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
        </div>
        
        {{-- Content Grid --}}
        <div class="relative grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            
            {{-- System Health --}}
            <div class="space-y-2">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 flex items-center gap-2">
                    <div class="w-2 h-2 rounded-full animate-pulse {{ $systemHealth['overall'] === 'operational' ? 'bg-green-500' : ($systemHealth['overall'] === 'degraded' ? 'bg-yellow-500' : 'bg-red-500') }}"></div>
                    System Status
                </h3>
                <div class="space-y-2">
                    {{-- Cal.com Status --}}
                    <div class="flex items-center justify-between p-2 rounded-lg {{ $systemHealth['calcom']['status'] ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-calendar class="w-4 h-4 {{ $systemHealth['calcom']['status'] ? 'text-green-600' : 'text-red-600' }}" />
                            <span class="text-sm font-medium">Cal.com</span>
                        </div>
                        <span class="text-xs {{ $systemHealth['calcom']['status'] ? 'text-green-600' : 'text-red-600' }}">
                            @if($systemHealth['calcom']['status'])
                                ✓ {{ $systemHealth['calcom']['responseTime'] }}ms
                            @else
                                ✗ Offline
                            @endif
                        </span>
                    </div>
                    
                    {{-- Retell Status --}}
                    <div class="flex items-center justify-between p-2 rounded-lg {{ $systemHealth['retell']['status'] ? 'bg-green-50 dark:bg-green-900/20' : 'bg-red-50 dark:bg-red-900/20' }}">
                        <div class="flex items-center gap-2">
                            <x-heroicon-o-phone class="w-4 h-4 {{ $systemHealth['retell']['status'] ? 'text-green-600' : 'text-red-600' }}" />
                            <span class="text-sm font-medium">Retell</span>
                        </div>
                        <span class="text-xs {{ $systemHealth['retell']['status'] ? 'text-green-600' : 'text-red-600' }}">
                            @if($systemHealth['retell']['status'])
                                ✓ {{ $systemHealth['retell']['responseTime'] }}ms
                            @else
                                ✗ Offline
                            @endif
                        </span>
                    </div>
                </div>
            </div>
            
            {{-- Live Calls --}}
            <div class="space-y-2">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 flex items-center gap-2">
                    <x-heroicon-o-phone class="w-4 h-4" />
                    Aktive Anrufe
                </h3>
                <div class="space-y-2">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold text-gray-900 dark:text-white">{{ $liveCalls['active'] }}</span>
                        <span class="text-sm text-gray-500">aktiv</span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">{{ $liveCalls['avgDuration'] }}</span> Ø
                    </div>
                    
                    {{-- Anomaly Alert --}}
                    @if(count($liveCalls['anomalies']) > 0)
                        <div class="mt-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                            <div class="flex items-start gap-2">
                                <x-heroicon-o-exclamation-triangle class="w-4 h-4 text-yellow-600 flex-shrink-0 mt-0.5" />
                                <div class="text-xs">
                                    @foreach($liveCalls['anomalies']->take(2) as $anomaly)
                                        <div class="text-yellow-700 dark:text-yellow-300">
                                            {{ $anomaly['branch'] }}: {{ $anomaly['duration'] }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
            {{-- Conversion Rate --}}
            <div class="space-y-2">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 flex items-center gap-2">
                    <x-heroicon-o-arrow-trending-up class="w-4 h-4" />
                    Konversionsrate
                </h3>
                <div class="space-y-2">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold {{ $conversion['rate'] >= 40 ? 'text-green-600' : ($conversion['rate'] >= 25 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $conversion['rate'] }}%
                        </span>
                        @if($conversion['trend'] != 0)
                            <span class="text-sm {{ $conversion['trend'] > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $conversion['trend'] > 0 ? '↑' : '↓' }}{{ abs($conversion['trend']) }}%
                            </span>
                        @endif
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $conversion['convertedCalls'] }}/{{ $conversion['totalCalls'] }} Termine
                    </div>
                    
                    {{-- Visual Progress Bar --}}
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full transition-all duration-500 {{ $conversion['rate'] >= 40 ? 'bg-green-600' : ($conversion['rate'] >= 25 ? 'bg-yellow-600' : 'bg-red-600') }}" 
                             style="width: {{ min($conversion['rate'], 100) }}%"></div>
                    </div>
                </div>
            </div>
            
            {{-- Cost per Booking --}}
            <div class="space-y-2">
                <h3 class="text-sm font-medium text-gray-500 dark:text-gray-400 flex items-center gap-2">
                    <x-heroicon-o-currency-euro class="w-4 h-4" />
                    Kosten/Termin
                </h3>
                <div class="space-y-2">
                    <div class="flex items-baseline gap-2">
                        <span class="text-3xl font-bold {{ $costPerBooking['cost'] <= 2 ? 'text-green-600' : ($costPerBooking['cost'] <= 4 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ number_format($costPerBooking['cost'], 2, ',', '.') }}€
                        </span>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ number_format($costPerBooking['totalCosts'], 2, ',', '.') }}€ / {{ $costPerBooking['totalBookings'] }} Termine
                    </div>
                    
                    {{-- Cost Indicator --}}
                    <div class="flex items-center gap-1">
                        @php
                            $costLevel = $costPerBooking['cost'] <= 2 ? 1 : ($costPerBooking['cost'] <= 3 ? 2 : ($costPerBooking['cost'] <= 4 ? 3 : 4));
                        @endphp
                        @for($i = 1; $i <= 4; $i++)
                            <div class="w-6 h-2 rounded {{ $i <= $costLevel ? ($costLevel <= 2 ? 'bg-green-500' : ($costLevel == 3 ? 'bg-yellow-500' : 'bg-red-500')) : 'bg-gray-300 dark:bg-gray-600' }}"></div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Anomaly Strip --}}
        @if(count($anomalies) > 0)
            <div class="mt-6 -mx-6 -mb-6 px-6 py-3 bg-red-50 dark:bg-red-900/20 border-t border-red-100 dark:border-red-800">
                <div class="flex items-center gap-4 overflow-x-auto">
                    <div class="flex items-center gap-2 text-red-700 dark:text-red-300 font-medium text-sm">
                        <x-heroicon-o-exclamation-circle class="w-5 h-5" />
                        Auffälligkeiten:
                    </div>
                    @foreach($anomalies as $anomaly)
                        <div class="flex-shrink-0 px-3 py-1 rounded-full text-xs font-medium {{ $anomaly['severity'] === 'critical' ? 'bg-red-100 text-red-700 dark:bg-red-800 dark:text-red-200' : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-800 dark:text-yellow-200' }}">
                            {{ $anomaly['branch'] }}: {{ $anomaly['message'] }} ({{ $anomaly['value'] }})
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-widgets::widget>