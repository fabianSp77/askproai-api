<div class="branch-performance-metrics">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Anruf-Statistiken -->
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Anruf-Performance</h4>
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
            </div>
            
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Anrufe heute</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $callsToday ?? 0 }}</span>
                    </div>
                    <div class="mt-1 flex items-center">
                        @if(($callsTrend ?? 0) > 0)
                            <svg class="w-4 h-4 text-green-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 9.707a1 1 0 010-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 01-1.414 1.414L10 6.414l-3.293 3.293a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-xs text-green-600 dark:text-green-400">+{{ $callsTrend }}% vs. gestern</span>
                        @else
                            <svg class="w-4 h-4 text-red-500 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M14.707 10.293a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L10 13.586l3.293-3.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-xs text-red-600 dark:text-red-400">{{ $callsTrend }}% vs. gestern</span>
                        @endif
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Erfolgsrate</span>
                        <span class="text-xl font-semibold text-gray-900 dark:text-white">{{ $successRate ?? 0 }}%</span>
                    </div>
                    <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-blue-600 dark:bg-blue-400 h-2 rounded-full" style="width: {{ $successRate ?? 0 }}%"></div>
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Ø Gesprächsdauer</span>
                        <span class="text-lg font-medium text-gray-900 dark:text-white">{{ $avgCallDuration ?? '0' }}s</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Termin-Statistiken -->
        <div class="bg-gradient-to-br from-green-50 to-emerald-50 dark:from-green-900/20 dark:to-emerald-900/20 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Termin-Performance</h4>
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            
            <div class="space-y-3">
                <div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Termine heute</span>
                        <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $appointmentsToday ?? 0 }}</span>
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Auslastung</span>
                        <span class="text-xl font-semibold text-gray-900 dark:text-white">{{ $utilizationRate ?? 0 }}%</span>
                    </div>
                    <div class="mt-2 w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="h-2 rounded-full {{ ($utilizationRate ?? 0) > 80 ? 'bg-yellow-500' : 'bg-green-600 dark:bg-green-400' }}" 
                             style="width: {{ $utilizationRate ?? 0 }}%"></div>
                    </div>
                </div>
                
                <div>
                    <div class="flex justify-between items-baseline">
                        <span class="text-sm text-gray-600 dark:text-gray-400">No-Show Rate</span>
                        <span class="text-lg font-medium {{ ($noShowRate ?? 0) > 10 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $noShowRate ?? 0 }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Service-Statistiken -->
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Top Services</h4>
                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            
            <div class="space-y-3">
                @foreach(($topServices ?? []) as $service)
                    <div>
                        <div class="flex justify-between items-baseline mb-1">
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $service['name'] }}</span>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $service['count'] }}</span>
                        </div>
                        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                            <div class="bg-purple-600 dark:bg-purple-400 h-1.5 rounded-full" 
                                 style="width: {{ $service['percentage'] }}%"></div>
                        </div>
                    </div>
                @endforeach
                
                @if(empty($topServices))
                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center py-4">
                        Noch keine Service-Daten verfügbar
                    </p>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Zusätzliche Metriken -->
    <div class="mt-6 grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">Conversion Rate</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $conversionRate ?? 0 }}%</p>
            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Anruf → Termin</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">Ø Wartezeit</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $avgWaitTime ?? 0 }}s</p>
            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Bis Annahme</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">Wiederkehrende</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $returningCustomers ?? 0 }}%</p>
            <p class="text-xs text-gray-500 dark:text-gray-500 mt-1">Diese Woche</p>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 text-center">
            <p class="text-sm text-gray-600 dark:text-gray-400">Bewertung</p>
            <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $satisfaction ?? 0 }}/5</p>
            <div class="flex justify-center mt-1">
                @for($i = 1; $i <= 5; $i++)
                    <svg class="w-4 h-4 {{ $i <= ($satisfaction ?? 0) ? 'text-yellow-400' : 'text-gray-300 dark:text-gray-600' }}" 
                         fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                    </svg>
                @endfor
            </div>
        </div>
    </div>
</div>
