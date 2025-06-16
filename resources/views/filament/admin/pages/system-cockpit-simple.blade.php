<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Calls Today -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($callsToday) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Calls Today</div>
        </div>
        
        <!-- Appointments Today -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($appointmentsToday) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Appointments Today</div>
        </div>
        
        <!-- Active Companies -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($activeCompanies) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Active Companies</div>
        </div>
        
        <!-- Total Customers -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($totalCustomers) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Total Customers</div>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Calls -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Calls</h3>
                <div class="space-y-3">
                    @forelse($recentCalls as $call)
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $call['customer'] }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $call['duration'] }}s • {{ $call['time'] }}</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400">No recent calls</p>
                    @endforelse
                </div>
            </div>
        </div>
        
        <!-- Recent Appointments -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            <div class="p-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Appointments</h3>
                <div class="space-y-3">
                    @forelse($recentAppointments as $appointment)
                        <div class="flex justify-between items-center p-3 bg-gray-50 dark:bg-gray-700 rounded">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $appointment['customer'] }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $appointment['staff'] }} • {{ $appointment['time'] }}
                                </div>
                            </div>
                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                @if($appointment['status'] == 'completed') bg-green-100 text-green-800
                                @elseif($appointment['status'] == 'scheduled') bg-blue-100 text-blue-800
                                @else bg-gray-100 text-gray-800
                                @endif">
                                {{ ucfirst($appointment['status']) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-gray-500 dark:text-gray-400">No recent appointments</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    
    <!-- System Status -->
    <div class="mt-6 bg-white dark:bg-gray-800 rounded-lg shadow p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">System Status</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">Queue Size</div>
                <div class="text-xl font-semibold text-gray-900 dark:text-white">{{ number_format($queueSize) }}</div>
            </div>
            <div class="text-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">Database</div>
                <div class="text-xl font-semibold text-green-600">Connected</div>
            </div>
            <div class="text-center">
                <div class="text-sm text-gray-500 dark:text-gray-400">Status</div>
                <div class="text-xl font-semibold text-green-600">Operational</div>
            </div>
        </div>
    </div>
</x-filament-panels::page>