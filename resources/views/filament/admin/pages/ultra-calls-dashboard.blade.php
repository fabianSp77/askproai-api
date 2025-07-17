<x-filament-panels::page>
    @vite(['resources/css/filament/admin/ultra-calls.css'])
    
    {{-- Live Status Bar --}}
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">📞 Call Center Command</h1>
        <div class="ultra-live-indicator">
            <span class="ultra-live-dot"></span>
            <span>Live Monitoring</span>
        </div>
    </div>
    
    {{-- Statistics Cards --}}
    <div class="ultra-call-stats">
        <div class="ultra-stat-card">
            <div class="ultra-stat-label">Active Calls</div>
            <div class="ultra-stat-value">{{ $activeCallsCount ?? 12 }}</div>
            <div class="ultra-stat-trend positive">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                <span>+20% from last hour</span>
            </div>
        </div>
        
        <div class="ultra-stat-card">
            <div class="ultra-stat-label">Average Duration</div>
            <div class="ultra-stat-value">3:45</div>
            <div class="ultra-stat-trend negative">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"></path>
                </svg>
                <span>-30s from average</span>
            </div>
        </div>
        
        <div class="ultra-stat-card">
            <div class="ultra-stat-label">Success Rate</div>
            <div class="ultra-stat-value">87%</div>
            <div class="ultra-stat-trend positive">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                </svg>
                <span>+5% improvement</span>
            </div>
        </div>
        
        <div class="ultra-stat-card">
            <div class="ultra-stat-label">Queue Size</div>
            <div class="ultra-stat-value">5</div>
            <div class="ultra-stat-trend positive">
                <span class="text-green-600">Normal</span>
            </div>
        </div>
    </div>
    
    {{-- Visualization Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <h3 class="text-lg font-semibold mb-4">🔴 Live Calls Timeline</h3>
            <div class="h-48 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                <canvas id="liveCallsChart" class="w-full h-full"></canvas>
            </div>
        </div>
        
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow-sm">
            <h3 class="text-lg font-semibold mb-4">📊 Call Distribution</h3>
            <div class="h-48 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                <canvas id="distributionChart" class="w-full h-full"></canvas>
            </div>
        </div>
    </div>
    
    {{-- Smart Filter Bar --}}
    <div class="mb-6">
        <div class="flex items-center gap-4">
            <div class="flex-1">
                <input 
                    type="text" 
                    placeholder="🔍 Try 'positive calls today' or 'calls longer than 5 minutes'..."
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                    x-data
                    x-on:keyup.enter="$wire.applySmartFilter($event.target.value)"
                >
            </div>
            <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Apply Filter
            </button>
        </div>
    </div>
    
    {{-- Enhanced Table/Cards View --}}
    <div class="space-y-4">
        @forelse($calls ?? [] as $call)
            <div class="ultra-call-card">
                <div class="ultra-call-header">
                    <div class="ultra-call-customer">
                        <div class="ultra-call-avatar">
                            {{ substr($call->customer_name ?? 'U', 0, 1) }}
                        </div>
                        <div>
                            <h4 class="font-semibold text-gray-900 dark:text-white">
                                {{ $call->customer_name ?? 'Unknown Caller' }}
                            </h4>
                            <p class="text-sm text-gray-500">{{ $call->phone_number ?? 'No number' }}</p>
                        </div>
                    </div>
                    
                    <div class="ultra-call-meta">
                        <span>⏱️ {{ $call->duration ?? '0:00' }}</span>
                        <span>📅 {{ $call->created_at?->format('d.m.Y H:i') ?? 'N/A' }}</span>
                        <span class="px-2 py-1 rounded-full text-xs font-medium
                            {{ $call->status === 'completed' ? 'bg-green-100 text-green-800' : '' }}
                            {{ $call->status === 'active' ? 'bg-blue-100 text-blue-800' : '' }}
                            {{ $call->status === 'missed' ? 'bg-red-100 text-red-800' : '' }}
                        ">
                            {{ ucfirst($call->status ?? 'unknown') }}
                        </span>
                    </div>
                </div>
                
                <div class="ultra-call-sentiment">
                    <span class="ultra-sentiment-emoji">😊</span>
                    <span>Sentiment:</span>
                    <span class="ultra-sentiment-score">Positive (8.5/10)</span>
                </div>
                
                <div class="ultra-call-tags">
                    <span class="ultra-tag">#Neukunde</span>
                    <span class="ultra-tag">#Beratung</span>
                    <span class="ultra-tag">#Premium</span>
                </div>
                
                @if($call->recording_url)
                    <div class="ultra-audio-player">
                        <div class="ultra-waveform"></div>
                        <div class="ultra-playback-controls">
                            <button class="ultra-play-button">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M6.3 2.841A1.5 1.5 0 004 4.11V15.89a1.5 1.5 0 002.3 1.269l9.344-5.89a1.5 1.5 0 000-2.538L6.3 2.84z"></path>
                                </svg>
                            </button>
                            <div class="ultra-timeline">
                                <div class="ultra-timeline-progress"></div>
                            </div>
                            <span class="text-sm text-gray-500">0:00 / {{ $call->duration ?? '0:00' }}</span>
                        </div>
                    </div>
                @endif
                
                <div class="flex justify-between items-center mt-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400 italic">
                        "{{ Str::limit($call->notes ?? 'Interessiert an Premium-Paket, möchte Rückruf planen für nächste Woche...', 80) }}"
                    </p>
                    
                    <div class="flex gap-2">
                        <button class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">
                            📄 Transcript
                        </button>
                        <button class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded-lg">
                            📊 Analytics
                        </button>
                        <button class="px-3 py-1 text-sm bg-blue-600 text-white hover:bg-blue-700 rounded-lg">
                            Details →
                        </button>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No calls yet</h3>
                <p class="mt-1 text-sm text-gray-500">Calls will appear here once they are received.</p>
            </div>
        @endforelse
    </div>
    
    {{-- Pagination --}}
    @if(method_exists($this, 'getTableRecords') && $this->getTableRecords()->hasPages())
        <div class="mt-6">
            {{ $this->getTableRecords()->links() }}
        </div>
    @endif
    
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Initialize charts when page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Live Calls Timeline Chart
            const ctx1 = document.getElementById('liveCallsChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: ['9:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00'],
                    datasets: [{
                        label: 'Active Calls',
                        data: [5, 8, 12, 15, 11, 13, 10],
                        borderColor: '#3B82F6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
            
            // Call Distribution Chart
            const ctx2 = document.getElementById('distributionChart').getContext('2d');
            new Chart(ctx2, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Active', 'Missed', 'Transferred'],
                    datasets: [{
                        data: [65, 12, 8, 15],
                        backgroundColor: ['#10B981', '#3B82F6', '#EF4444', '#F59E0B']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });
        });
    </script>
    @endpush
</x-filament-panels::page>