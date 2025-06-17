<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Header Controls --}}
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                @php
                    $overall = $this->getOverallHealth();
                @endphp
                <div class="flex items-center space-x-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75
                            {{ $overall['status'] === 'healthy' ? 'bg-success-400' : ($overall['status'] === 'warning' ? 'bg-warning-400' : 'bg-danger-400') }}">
                        </span>
                        <span class="relative inline-flex rounded-full h-3 w-3
                            {{ $overall['status'] === 'healthy' ? 'bg-success-500' : ($overall['status'] === 'warning' ? 'bg-warning-500' : 'bg-danger-500') }}">
                        </span>
                    </span>
                    <span class="text-sm font-medium {{ $overall['status'] === 'healthy' ? 'text-success-600' : ($overall['status'] === 'warning' ? 'text-warning-600' : 'text-danger-600') }}">
                        {{ $overall['message'] }}
                    </span>
                </div>
            </div>
            
            <div class="flex items-center space-x-2">
                <x-filament::button
                    wire:click="toggleAutoRefresh"
                    color="gray"
                    size="sm"
                >
                    @if($autoRefresh)
                        <x-heroicon-o-pause class="w-4 h-4 mr-1"/>
                        Stop Auto-Refresh
                    @else
                        <x-heroicon-o-play class="w-4 h-4 mr-1"/>
                        Start Auto-Refresh
                    @endif
                </x-filament::button>
                
                <x-filament::button
                    wire:click="refresh"
                    color="gray"
                    size="sm"
                >
                    <x-heroicon-o-arrow-path class="w-4 h-4 mr-1"/>
                    Refresh Now
                </x-filament::button>
                
                <x-filament::button
                    wire:click="exportMetrics"
                    color="gray"
                    size="sm"
                >
                    <x-heroicon-o-arrow-down-tray class="w-4 h-4 mr-1"/>
                    Export
                </x-filament::button>
            </div>
        </div>
        
        {{-- Service Status Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach(['calcom' => 'Cal.com API', 'retell' => 'Retell.ai API'] as $service => $label)
                @php
                    $health = $this->getHealthStatus($service);
                    $metrics = $this->metrics[$service] ?? [];
                    $circuitBreaker = $this->circuitBreakerStatus[$service] ?? [];
                    $performance = $this->performanceStats[$service] ?? [];
                @endphp
                
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center justify-between">
                            <span>{{ $label }}</span>
                            <x-filament::badge :color="$health['color']">
                                {{ strtoupper($health['status']) }}
                            </x-filament::badge>
                        </div>
                    </x-slot>
                    
                    <div class="space-y-4">
                        {{-- Health Status --}}
                        <div class="p-3 rounded-lg {{ $health['status'] === 'healthy' ? 'bg-success-50' : ($health['status'] === 'warning' ? 'bg-warning-50' : 'bg-danger-50') }}">
                            <div class="flex items-center">
                                <x-dynamic-component
                                    :component="$health['icon']"
                                    class="w-5 h-5 mr-2 {{ $health['status'] === 'healthy' ? 'text-success-600' : ($health['status'] === 'warning' ? 'text-warning-600' : 'text-danger-600') }}"
                                />
                                <span class="text-sm {{ $health['status'] === 'healthy' ? 'text-success-700' : ($health['status'] === 'warning' ? 'text-warning-700' : 'text-danger-700') }}">
                                    {{ $health['message'] }}
                                </span>
                            </div>
                        </div>
                        
                        {{-- Metrics --}}
                        @if(!empty($metrics))
                            <div class="grid grid-cols-2 gap-3">
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold text-gray-900">{{ $metrics['total'] ?? 0 }}</div>
                                    <div class="text-xs text-gray-500">Total Calls</div>
                                </div>
                                <div class="text-center p-3 bg-gray-50 rounded-lg">
                                    <div class="text-2xl font-bold {{ ($metrics['success_rate'] ?? 0) >= 90 ? 'text-success-600' : 'text-warning-600' }}">
                                        {{ $metrics['success_rate'] ?? 0 }}%
                                    </div>
                                    <div class="text-xs text-gray-500">Success Rate</div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-4 text-gray-500">
                                <x-heroicon-o-chart-bar class="w-8 h-8 mx-auto mb-2 text-gray-400"/>
                                <p class="text-sm">No metrics available</p>
                            </div>
                        @endif
                        
                        {{-- Performance Stats --}}
                        @if(!empty($performance))
                            <div class="bg-gray-50 rounded-lg p-3">
                                <h4 class="text-xs font-medium text-gray-700 mb-2">Response Times (ms)</h4>
                                <div class="grid grid-cols-4 gap-2 text-xs">
                                    <div>
                                        <span class="text-gray-500">Min:</span>
                                        <span class="font-medium">{{ $performance['min'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Avg:</span>
                                        <span class="font-medium">{{ $performance['avg'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Max:</span>
                                        <span class="font-medium">{{ $performance['max'] }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">StdDev:</span>
                                        <span class="font-medium">{{ $performance['stddev'] }}</span>
                                    </div>
                                </div>
                            </div>
                        @endif
                        
                        {{-- Circuit Breaker Status --}}
                        @if(!empty($circuitBreaker))
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <h4 class="text-xs font-medium text-gray-700">Circuit Breaker</h4>
                                    <div class="flex items-center mt-1">
                                        <span class="text-sm font-medium mr-2">
                                            State: {{ strtoupper($circuitBreaker['state']) }}
                                        </span>
                                        @if($circuitBreaker['failures'] > 0)
                                            <x-filament::badge color="danger" size="xs">
                                                {{ $circuitBreaker['failures'] }} failures
                                            </x-filament::badge>
                                        @endif
                                    </div>
                                </div>
                                @if($circuitBreaker['state'] === 'open')
                                    <x-filament::button
                                        wire:click="resetCircuitBreaker('{{ $service }}')"
                                        color="warning"
                                        size="xs"
                                    >
                                        Reset
                                    </x-filament::button>
                                @endif
                            </div>
                        @endif
                    </div>
                </x-filament::section>
            @endforeach
        </div>
        
        {{-- Recent Errors --}}
        @if(!empty($recentErrors))
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center">
                        <x-heroicon-o-exclamation-circle class="w-5 h-5 mr-2 text-danger-600"/>
                        Recent Errors
                    </div>
                </x-slot>
                
                <div class="space-y-2">
                    @foreach($recentErrors as $error)
                        <div class="p-3 bg-danger-50 rounded-lg">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="text-sm font-medium text-danger-800">
                                        {{ $error->service }} - {{ $error->error_type }}
                                    </div>
                                    <div class="text-xs text-danger-600 mt-1">
                                        {{ Str::limit($error->message, 100) }}
                                    </div>
                                </div>
                                <div class="text-xs text-danger-500 ml-2">
                                    {{ $error->time_ago }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
        
        {{-- Real-time Chart Placeholder --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center">
                    <x-heroicon-o-chart-bar class="w-5 h-5 mr-2"/>
                    Real-time Performance
                </div>
            </x-slot>
            
            <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                <div class="text-center">
                    <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto mb-3 text-gray-400"/>
                    <p class="text-gray-500">Performance chart coming soon</p>
                    <p class="text-xs text-gray-400 mt-1">Will show real-time API response times</p>
                </div>
            </div>
        </x-filament::section>
    </div>
    
    {{-- Auto-refresh JavaScript --}}
    @if($autoRefresh)
        <script>
            let refreshInterval;
            
            document.addEventListener('livewire:initialized', () => {
                startAutoRefresh();
                
                Livewire.on('start-auto-refresh', () => {
                    startAutoRefresh();
                });
                
                Livewire.on('stop-auto-refresh', () => {
                    stopAutoRefresh();
                });
            });
            
            function startAutoRefresh() {
                refreshInterval = setInterval(() => {
                    @this.refresh();
                }, {{ $refreshInterval * 1000 }});
            }
            
            function stopAutoRefresh() {
                if (refreshInterval) {
                    clearInterval(refreshInterval);
                }
            }
        </script>
    @endif
</x-filament-panels::page>