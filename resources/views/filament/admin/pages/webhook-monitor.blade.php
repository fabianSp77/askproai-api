<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Overview Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500">24h Total</div>
                <div class="mt-1 text-2xl font-semibold">{{ number_format($stats['total_24h']) }}</div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500">1h Total</div>
                <div class="mt-1 text-2xl font-semibold">{{ number_format($stats['total_1h']) }}</div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500">Success Rate</div>
                <div class="mt-1 text-2xl font-semibold 
                    {{ $stats['success_rate'] >= 95 ? 'text-success-600' : ($stats['success_rate'] >= 90 ? 'text-warning-600' : 'text-danger-600') }}">
                    {{ $stats['success_rate'] }}%
                </div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500">Avg Time</div>
                <div class="mt-1 text-2xl font-semibold">{{ round($stats['avg_processing_time']) }}ms</div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500">Active Providers</div>
                <div class="mt-1 text-2xl font-semibold">{{ $stats['active_providers'] }}</div>
            </x-filament::card>
            
            <x-filament::card>
                <div class="text-sm font-medium text-gray-500">Duplicate Rate</div>
                <div class="mt-1 text-2xl font-semibold 
                    {{ $stats['duplicate_rate'] < 1 ? 'text-success-600' : 'text-warning-600' }}">
                    {{ $stats['duplicate_rate'] }}%
                </div>
            </x-filament::card>
        </div>

        {{-- Provider Health Status --}}
        <x-filament::section heading="Provider Health">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @foreach(['retell' => 'Retell.ai', 'calcom' => 'Cal.com', 'stripe' => 'Stripe'] as $key => $name)
                    @php
                        $providerData = $providerStats[$key] ?? null;
                        $health = $providerData['health'] ?? ['status' => 'unknown', 'message' => 'No data'];
                    @endphp
                    
                    <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border
                        {{ $health['status'] === 'healthy' ? 'border-success-300' : ($health['status'] === 'warning' ? 'border-warning-300' : ($health['status'] === 'error' ? 'border-danger-300' : 'border-gray-300')) }}">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-semibold">{{ $name }}</h3>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                {{ $health['status'] === 'healthy' ? 'bg-success-100 text-success-800' : ($health['status'] === 'warning' ? 'bg-warning-100 text-warning-800' : ($health['status'] === 'error' ? 'bg-danger-100 text-danger-800' : 'bg-gray-100 text-gray-800')) }}">
                                @if($health['status'] === 'healthy')
                                    <x-heroicon-o-check-circle class="w-3 h-3 mr-1"/>
                                @elseif($health['status'] === 'warning')
                                    <x-heroicon-o-exclamation-triangle class="w-3 h-3 mr-1"/>
                                @elseif($health['status'] === 'error')
                                    <x-heroicon-o-x-circle class="w-3 h-3 mr-1"/>
                                @else
                                    <x-heroicon-o-question-mark-circle class="w-3 h-3 mr-1"/>
                                @endif
                                {{ ucfirst($health['status']) }}
                            </span>
                        </div>
                        
                        @if($providerData)
                            <div class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                                <div>Total: {{ number_format($providerData['total']) }} webhooks</div>
                                <div>Avg Time: {{ $providerData['avg_time'] }}ms</div>
                                <div>{{ $health['message'] }}</div>
                            </div>
                        @else
                            <div class="text-sm text-gray-500">No webhooks in last 24h</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Hourly Activity Chart --}}
        <x-filament::section heading="24-Hour Activity">
            <div class="h-48">
                <canvas id="hourlyChart"></canvas>
            </div>
        </x-filament::section>

        {{-- Recent Webhooks --}}
        <x-filament::section heading="Recent Webhooks" collapsible>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Provider</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Time (ms)</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($recentWebhooks as $webhook)
                            <tr>
                                <td class="px-4 py-2 text-sm">{{ $webhook->time_ago }}</td>
                                <td class="px-4 py-2 text-sm">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $webhook->provider }}
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm">{{ $webhook->event_type }}</td>
                                <td class="px-4 py-2 text-sm">
                                    @if($webhook->status === 'success')
                                        <span class="text-success-600">✓ Success</span>
                                    @elseif($webhook->status === 'error')
                                        <span class="text-danger-600">✗ Error</span>
                                    @else
                                        <span class="text-warning-600">○ {{ $webhook->status }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm">{{ $webhook->processing_time_ms ?? '-' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $webhook->payload_preview }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">No recent webhooks</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-filament::section>

        {{-- Error Log --}}
        @if(count($errorWebhooks) > 0)
            <x-filament::section heading="Recent Errors" collapsible collapsed>
                <div class="space-y-2">
                    @foreach($errorWebhooks as $error)
                        <div class="bg-danger-50 dark:bg-danger-900/20 rounded-lg p-3">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="font-medium text-danger-800 dark:text-danger-400">
                                        {{ $error->provider }} - {{ $error->event_type }}
                                    </div>
                                    <div class="text-sm text-danger-600 dark:text-danger-500 mt-1">
                                        {{ $error->error_preview }}
                                    </div>
                                </div>
                                <div class="text-xs text-gray-500 ml-4">
                                    {{ $error->time_ago }}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        @endif
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const ctx = document.getElementById('hourlyChart').getContext('2d');
                const hourlyData = @json($hourlyStats);
                
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: hourlyData.map(d => d.hour),
                        datasets: [{
                            label: 'Webhooks',
                            data: hourlyData.map(d => d.count),
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            });
        </script>
    @endpush
</x-filament-panels::page>