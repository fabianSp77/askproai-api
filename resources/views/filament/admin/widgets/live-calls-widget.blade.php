<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-4">
            {{-- Header --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="relative">
                        <div class="h-3 w-3 bg-green-500 rounded-full animate-pulse"></div>
                        <div class="absolute inset-0 h-3 w-3 bg-green-500 rounded-full animate-ping"></div>
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Live Anrufe
                        @if(count($activeCalls) > 0)
                            <span class="ml-2 text-sm text-gray-500">({{ count($activeCalls) }} aktiv)</span>
                        @endif
                    </h2>
                </div>
                
                <div class="flex items-center space-x-2">
                    <span class="text-xs text-gray-500">
                        Letztes Update: {{ $lastUpdate }}
                    </span>
                    
                    <x-filament::button
                        wire:click="toggleRealtime"
                        size="sm"
                        :color="$realtimeEnabled ? 'success' : 'gray'"
                    >
                        @if($realtimeEnabled)
                            <x-heroicon-m-signal class="h-4 w-4 mr-1" />
                            Live
                        @else
                            <x-heroicon-m-signal-slash class="h-4 w-4 mr-1" />
                            Pausiert
                        @endif
                    </x-filament::button>
                    
                    <x-filament::button
                        wire:click="refreshCalls"
                        size="sm"
                        color="gray"
                    >
                        <x-heroicon-m-arrow-path class="h-4 w-4" />
                    </x-filament::button>
                    
                    <x-filament::button
                        wire:click="syncNow"
                        size="sm"
                        color="primary"
                    >
                        <x-heroicon-m-cloud-arrow-down class="h-4 w-4 mr-1" />
                        Sync
                    </x-filament::button>
                </div>
            </div>
            
            {{-- Active Calls --}}
            @if(count($activeCalls) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($this->getDisplayedCalls() as $call)
                        <div 
                            class="relative overflow-hidden rounded-lg border p-4 transition-all duration-300 
                                   {{ $call['is_new'] ? 'border-green-500 bg-green-50 dark:bg-green-900/20' : 'border-gray-200 dark:border-gray-700' }}"
                            wire:key="call-{{ $call['id'] }}"
                        >
                            {{-- Status indicator --}}
                            <div class="absolute top-2 right-2">
                                <div class="flex items-center space-x-2">
                                    <div class="h-2 w-2 bg-red-500 rounded-full animate-pulse"></div>
                                    <span class="text-xs font-medium text-red-600">LIVE</span>
                                </div>
                            </div>
                            
                            {{-- Call info --}}
                            <div class="space-y-2">
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-m-phone class="h-5 w-5 text-gray-400" />
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        {{ $call['from_number'] ?? 'Unbekannt' }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center space-x-2">
                                    <x-heroicon-m-user class="h-5 w-5 text-gray-400" />
                                    <span class="text-sm text-gray-600 dark:text-gray-300">
                                        {{ $call['customer_name'] }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-2">
                                        <x-heroicon-m-clock class="h-5 w-5 text-gray-400" />
                                        <span class="text-2xl font-bold text-gray-900 dark:text-white font-mono">
                                            {{ $call['duration'] }}
                                        </span>
                                    </div>
                                    
                                    <span class="text-xs text-gray-500">
                                        Start: {{ $call['start_time'] }}
                                    </span>
                                </div>
                                
                                {{-- Progress bar --}}
                                <div class="w-full bg-gray-200 rounded-full h-1.5 dark:bg-gray-700 overflow-hidden">
                                    <div 
                                        class="bg-blue-600 h-1.5 rounded-full transition-all duration-1000"
                                        style="width: {{ min($call['duration_seconds'] / 300 * 100, 100) }}%"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                {{-- Show More/Less Button --}}
                @if(count($activeCalls) > $displayLimit)
                    <div class="mt-4 text-center">
                        <x-filament::button
                            wire:click="toggleShowAll"
                            color="gray"
                            size="sm"
                        >
                            @if($showAll)
                                <x-heroicon-m-chevron-up class="h-4 w-4 mr-1" />
                                Weniger anzeigen
                            @else
                                <x-heroicon-m-chevron-down class="h-4 w-4 mr-1" />
                                {{ $this->getRemainingCallsCount() }} weitere Anrufe anzeigen
                            @endif
                        </x-filament::button>
                    </div>
                @endif
            @else
                {{-- No active calls --}}
                <div class="text-center py-12">
                    <x-heroicon-o-phone-x-mark class="mx-auto h-12 w-12 text-gray-400" />
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                        Keine aktiven Anrufe
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Neue Anrufe werden hier in Echtzeit angezeigt.
                    </p>
                </div>
            @endif
        </div>
        
        {{-- JavaScript for real-time updates --}}
        @if($realtimeEnabled)
            @push('scripts')
                <script src="https://js.pusher.com/8.2/pusher.min.js"></script>
                <script src="{{ asset('js/pusher-integration.js') }}"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        // Set Pusher credentials from config
                        window.pusherKey = @json(config('broadcasting.connections.pusher.key'));
                        window.pusherCluster = @json(config('broadcasting.connections.pusher.options.cluster'));
                        
                        // Initialize Pusher with company ID
                        const companyId = {{ auth()->user()->company_id ?? 'null' }};
                        if (companyId && window.initializePusher) {
                            window.initializePusher(companyId);
                        }
                        
                        // Fallback to Server-Sent Events if Pusher not available
                        if (!window.pusherKey) {
                            const eventSource = new EventSource('/api/retell/realtime/stream');
                            
                            eventSource.onmessage = function(event) {
                                const data = JSON.parse(event.data);
                                console.log('Real-time update (SSE):', data);
                                
                                // Refresh the widget when updates arrive
                                @this.loadActiveCalls();
                            };
                            
                            eventSource.onerror = function(error) {
                                console.error('SSE Error:', error);
                                // Reconnect after 5 seconds
                                setTimeout(() => {
                                    eventSource.close();
                                    // Component will reconnect on next poll
                                }, 5000);
                            };
                            
                            // Clean up on component destroy
                            Livewire.on('disable-realtime', () => {
                                eventSource.close();
                            });
                        }
                    });
                    
                    // Cleanup on page leave
                    window.addEventListener('beforeunload', function() {
                        if (window.cleanupPusher) {
                            window.cleanupPusher();
                        }
                    });
                </script>
            @endpush
        @endif
    </x-filament::section>
</x-filament-widgets::widget>