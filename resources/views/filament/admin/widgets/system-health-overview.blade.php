<x-filament-widgets::widget>
    <x-filament::card>
        @php
            $status = $this->getHealthStatus();
            $statusColor = match($status) {
                'critical' => 'danger',
                'warning' => 'warning',
                'healthy' => 'success',
                default => 'gray'
            };
            $statusIcon = match($status) {
                'critical' => 'heroicon-o-x-circle',
                'warning' => 'heroicon-o-exclamation-triangle',
                'healthy' => 'heroicon-o-check-circle',
                default => 'heroicon-o-question-mark-circle'
            };
            $statusText = match($status) {
                'critical' => 'Kritisch',
                'warning' => 'Warnung',
                'healthy' => 'Gesund',
                default => 'Unbekannt'
            };
        @endphp
        
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-3">
                <x-filament::icon 
                    :icon="$statusIcon"
                    class="w-8 h-8 text-{{ $statusColor }}-500"
                />
                <div>
                    <h2 class="text-xl font-semibold">System Health</h2>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Status: <span class="font-medium text-{{ $statusColor }}-600">{{ $statusText }}</span>
                    </p>
                </div>
            </div>
            <div class="flex space-x-2">
                <x-filament::button
                    wire:click="refresh"
                    size="sm"
                    color="gray"
                >
                    Aktualisieren
                </x-filament::button>
                <x-filament::button
                    :href="route('filament.admin.pages.system-monitoring-dashboard')"
                    tag="a"
                    size="sm"
                >
                    Details anzeigen
                </x-filament::button>
            </div>
        </div>
        
        @if($healthMetrics)
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                {{-- Database Status --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Database</span>
                        @if(($healthMetrics['database']['connection_time'] ?? 0) > 500)
                            <x-filament::icon 
                                icon="heroicon-o-exclamation-triangle"
                                class="w-4 h-4 text-warning-500"
                            />
                        @else
                            <x-filament::icon 
                                icon="heroicon-o-check-circle"
                                class="w-4 h-4 text-success-500"
                            />
                        @endif
                    </div>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Response:</span>
                            <span class="font-medium">{{ $healthMetrics['database']['connection_time'] ?? 0 }}ms</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Connections:</span>
                            <span class="font-medium">{{ $healthMetrics['database']['connection_usage'] ?? 0 }}%</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Slow Queries:</span>
                            <span class="font-medium {{ ($healthMetrics['database']['slow_queries'] ?? 0) > 5 ? 'text-warning-600' : '' }}">
                                {{ $healthMetrics['database']['slow_queries'] ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>
                
                {{-- Queue Status --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Queue</span>
                        @if(!($healthMetrics['queue']['horizon_running'] ?? false))
                            <x-filament::icon 
                                icon="heroicon-o-x-circle"
                                class="w-4 h-4 text-danger-500"
                            />
                        @else
                            <x-filament::icon 
                                icon="heroicon-o-check-circle"
                                class="w-4 h-4 text-success-500"
                            />
                        @endif
                    </div>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Horizon:</span>
                            <span class="font-medium">
                                {{ ($healthMetrics['queue']['horizon_running'] ?? false) ? 'Running' : 'Stopped' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Failed Jobs:</span>
                            <span class="font-medium {{ ($healthMetrics['queue']['failed_jobs'] ?? 0) > 0 ? 'text-danger-600' : '' }}">
                                {{ $healthMetrics['queue']['failed_jobs'] ?? 0 }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Recent Failures:</span>
                            <span class="font-medium {{ ($healthMetrics['queue']['recent_failures'] ?? 0) > 10 ? 'text-warning-600' : '' }}">
                                {{ $healthMetrics['queue']['recent_failures'] ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>
                
                {{-- API Status --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">APIs</span>
                        @php
                            $hasApiIssues = false;
                            foreach($healthMetrics['api'] ?? [] as $api) {
                                if($api['status'] !== 'online') {
                                    $hasApiIssues = true;
                                    break;
                                }
                            }
                        @endphp
                        @if($hasApiIssues)
                            <x-filament::icon 
                                icon="heroicon-o-exclamation-triangle"
                                class="w-4 h-4 text-warning-500"
                            />
                        @else
                            <x-filament::icon 
                                icon="heroicon-o-check-circle"
                                class="w-4 h-4 text-success-500"
                            />
                        @endif
                    </div>
                    <div class="space-y-1 text-xs">
                        @foreach(['calcom' => 'Cal.com', 'retell' => 'Retell.ai', 'stripe' => 'Stripe'] as $key => $name)
                            <div class="flex justify-between">
                                <span class="text-gray-500">{{ $name }}:</span>
                                <span class="font-medium {{ ($healthMetrics['api'][$key]['status'] ?? '') !== 'online' ? 'text-danger-600' : 'text-success-600' }}">
                                    {{ ucfirst($healthMetrics['api'][$key]['status'] ?? 'unknown') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
                
                {{-- Business Metrics --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Business</span>
                        @if(($healthMetrics['business']['appointment_conflicts'] ?? 0) > 0)
                            <x-filament::icon 
                                icon="heroicon-o-exclamation-triangle"
                                class="w-4 h-4 text-warning-500"
                            />
                        @else
                            <x-filament::icon 
                                icon="heroicon-o-check-circle"
                                class="w-4 h-4 text-success-500"
                            />
                        @endif
                    </div>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Stale Calls:</span>
                            <span class="font-medium {{ ($healthMetrics['business']['stale_calls'] ?? 0) > 0 ? 'text-warning-600' : '' }}">
                                {{ $healthMetrics['business']['stale_calls'] ?? 0 }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Conflicts:</span>
                            <span class="font-medium {{ ($healthMetrics['business']['appointment_conflicts'] ?? 0) > 0 ? 'text-danger-600' : '' }}">
                                {{ $healthMetrics['business']['appointment_conflicts'] ?? 0 }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Inactive:</span>
                            <span class="font-medium">
                                {{ $healthMetrics['business']['inactive_companies'] ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>
                
                {{-- System Resources --}}
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-400">System</span>
                        @if(($healthMetrics['system']['disk_usage'] ?? 0) > 90 || ($healthMetrics['system']['memory_usage'] ?? 0) > 90)
                            <x-filament::icon 
                                icon="heroicon-o-x-circle"
                                class="w-4 h-4 text-danger-500"
                            />
                        @elseif(($healthMetrics['system']['disk_usage'] ?? 0) > 80 || ($healthMetrics['system']['memory_usage'] ?? 0) > 80)
                            <x-filament::icon 
                                icon="heroicon-o-exclamation-triangle"
                                class="w-4 h-4 text-warning-500"
                            />
                        @else
                            <x-filament::icon 
                                icon="heroicon-o-check-circle"
                                class="w-4 h-4 text-success-500"
                            />
                        @endif
                    </div>
                    <div class="space-y-1 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Disk:</span>
                            <span class="font-medium {{ ($healthMetrics['system']['disk_usage'] ?? 0) > 80 ? 'text-warning-600' : '' }}">
                                {{ $healthMetrics['system']['disk_usage'] ?? 0 }}%
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Memory:</span>
                            <span class="font-medium {{ ($healthMetrics['system']['memory_usage'] ?? 0) > 80 ? 'text-warning-600' : '' }}">
                                {{ $healthMetrics['system']['memory_usage'] ?? 0 }}%
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Load:</span>
                            <span class="font-medium">
                                {{ $healthMetrics['system']['load_average']['1m'] ?? 0 }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="text-center py-8 text-gray-500">
                <p>Keine Health-Metriken verfügbar</p>
                <p class="text-sm">Führen Sie <code>php artisan monitoring:health-check</code> aus</p>
            </div>
        @endif
    </x-filament::card>
</x-filament-widgets::widget>