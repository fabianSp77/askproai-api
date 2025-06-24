<div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
    {{-- Header --}}
    <div class="p-6 border-b border-gray-200 dark:border-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Live Call Monitor</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Echtzeit-Anrufverfolgung und Warteschlangenstatus</p>
            </div>
            <div class="flex items-center space-x-4">
                {{-- Queue Status Indicators --}}
                <div class="flex items-center space-x-6">
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $queueMetrics['total_waiting'] }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">In Warteschlange</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold {{ $queueMetrics['service_level'] >= 90 ? 'text-green-600 dark:text-green-400' : ($queueMetrics['service_level'] >= 80 ? 'text-amber-600 dark:text-amber-400' : 'text-red-600 dark:text-red-400') }}">
                            {{ $queueMetrics['service_level'] }}%
                        </p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Service Level</p>
                    </div>
                    <div class="text-center">
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $queueMetrics['agents_available'] }}</p>
                        <p class="text-xs text-gray-600 dark:text-gray-400">Verfügbar</p>
                    </div>
                </div>
                
                {{-- Actions --}}
                <button wire:click="toggleExpanded" 
                        class="p-2 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors"
                        title="{{ $isExpanded ? 'Minimieren' : 'Erweitern' }}">
                    @if($isExpanded)
                        <x-heroicon-m-chevron-up class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                    @else
                        <x-heroicon-m-chevron-down class="w-5 h-5 text-gray-600 dark:text-gray-400" />
                    @endif
                </button>
            </div>
        </div>
    </div>

    {{-- Active Calls Grid --}}
    <div class="p-6">
        @if(count($activeCalls) > 0)
            <div class="grid grid-cols-1 {{ $isExpanded ? 'lg:grid-cols-2' : '' }} gap-4">
                @foreach($activeCalls as $call)
                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                {{-- Call Header --}}
                                <div class="flex items-center space-x-3 mb-3">
                                    <div class="relative">
                                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                        <div class="absolute inset-0 w-3 h-3 bg-green-500 rounded-full animate-ping"></div>
                                    </div>
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $call['phone'] }}</span>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">• {{ $call['branch'] }}</span>
                                    <span class="text-sm font-mono text-gray-600 dark:text-gray-400">{{ $call['duration'] }}</span>
                                </div>
                                
                                {{-- Agent Status --}}
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Agent Status:</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                        {{ $call['agent_status'] === 'greeting' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                        {{ $call['agent_status'] === 'qualifying' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400' : '' }}
                                        {{ $call['agent_status'] === 'booking' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : '' }}
                                        {{ $call['agent_status'] === 'closing' ? 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-400' : '' }}
                                    ">
                                        {{ ucfirst($call['agent_status']) }}
                                    </span>
                                </div>
                                
                                {{-- Call Topic/Summary --}}
                                @if($isExpanded)
                                    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                        {{ $call['topic'] }}
                                    </p>
                                @endif
                            </div>
                            
                            {{-- Sentiment Indicator --}}
                            <div class="ml-4">
                                @if($call['sentiment'] === 'positive')
                                    <x-heroicon-m-face-smile class="w-6 h-6 text-green-500" />
                                @elseif($call['sentiment'] === 'negative')
                                    <x-heroicon-m-face-frown class="w-6 h-6 text-red-500" />
                                @else
                                    <x-heroicon-m-minus-circle class="w-6 h-6 text-gray-400" />
                                @endif
                            </div>
                        </div>
                        
                        {{-- Progress Bar --}}
                        <div class="mt-3">
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5">
                                <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-1000" 
                                     style="width: {{ min(($call['duration_sec'] / 300) * 100, 100) }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-12">
                <x-heroicon-o-phone class="mx-auto h-12 w-12 text-gray-400" />
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">Keine aktiven Anrufe im Moment</p>
            </div>
        @endif
    </div>
    
    {{-- Queue Metrics Bar --}}
    @if($queueMetrics['total_waiting'] > 0)
        <div class="px-6 pb-6">
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <x-heroicon-o-clock class="w-5 h-5 text-amber-600 dark:text-amber-400" />
                        <span class="text-sm font-medium text-amber-800 dark:text-amber-200">
                            Warteschlangen-Status
                        </span>
                    </div>
                    <div class="flex items-center space-x-6 text-sm">
                        <div>
                            <span class="text-amber-600 dark:text-amber-400">Durchschn. Wartezeit:</span>
                            <span class="font-medium text-amber-800 dark:text-amber-200">
                                {{ gmdate('i:s', $queueMetrics['avg_wait_time']) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-amber-600 dark:text-amber-400">Längste:</span>
                            <span class="font-medium text-amber-800 dark:text-amber-200">
                                {{ gmdate('i:s', $queueMetrics['longest_wait']) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>