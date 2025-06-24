@php
    $customer = $getRecord();
    
    // Calculate analytics
    $appointmentsByMonth = $customer->appointments()
        ->selectRaw('MONTH(starts_at) as month, COUNT(*) as count')
        ->whereYear('starts_at', now()->year)
        ->groupBy('month')
        ->pluck('count', 'month')
        ->toArray();
    
    $revenueByMonth = $customer->appointments()
        ->selectRaw('MONTH(starts_at) as month, SUM(price) as revenue')
        ->whereYear('starts_at', now()->year)
        ->groupBy('month')
        ->pluck('revenue', 'month')
        ->toArray();
    
    $serviceBreakdown = $customer->appointments()
        ->join('services', 'appointments.service_id', '=', 'services.id')
        ->selectRaw('services.name, COUNT(*) as count')
        ->groupBy('services.id', 'services.name')
        ->orderByDesc('count')
        ->limit(5)
        ->pluck('count', 'name')
        ->toArray();
@endphp

<div class="customer-analytics-dashboard">
    <!-- Charts Container -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <!-- Appointments Chart -->
        <div class="chart-container bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Appointments This Year</h4>
            <canvas id="appointmentsChart" width="400" height="200"></canvas>
        </div>
        
        <!-- Revenue Chart -->
        <div class="chart-container bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Revenue This Year</h4>
            <canvas id="revenueChart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Service Breakdown -->
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-6">
        <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Most Booked Services</h4>
        <div class="space-y-3">
            @forelse($serviceBreakdown as $service => $count)
                @php
                    $percentage = $customer->appointments()->count() > 0 
                        ? round(($count / $customer->appointments()->count()) * 100) 
                        : 0;
                @endphp
                <div>
                    <div class="flex justify-between items-center mb-1">
                        <span class="text-sm font-medium">{{ $service }}</span>
                        <span class="text-sm text-gray-500">{{ $count }} times ({{ $percentage }}%)</span>
                    </div>
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div class="bg-primary-600 h-2 rounded-full" style="width: {{ $percentage }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 text-center">No service data available</p>
            @endforelse
        </div>
    </div>

    <!-- Key Insights -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <!-- Best Month -->
        <div class="insight-card bg-primary-50 dark:bg-primary-900/20 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <div class="p-2 bg-primary-100 dark:bg-primary-900/30 rounded-lg">
                    <x-heroicon-o-trophy class="w-5 h-5 text-primary-600 dark:text-primary-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Best Month</p>
                    @php
                        $bestMonth = array_search(max($appointmentsByMonth ?: [0]), $appointmentsByMonth ?: []);
                    @endphp
                    <p class="text-lg font-semibold text-primary-600 dark:text-primary-400">
                        {{ $bestMonth ? date('F', mktime(0, 0, 0, $bestMonth, 1)) : 'N/A' }}
                    </p>
                    <p class="text-xs text-gray-500">{{ max($appointmentsByMonth ?: [0]) }} appointments</p>
                </div>
            </div>
        </div>

        <!-- Average Interval -->
        <div class="insight-card bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-lg">
                    <x-heroicon-o-calendar class="w-5 h-5 text-green-600 dark:text-green-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Avg. Visit Interval</p>
                    @php
                        $appointments = $customer->appointments()->orderBy('starts_at')->get();
                        $intervals = [];
                        for ($i = 1; $i < $appointments->count(); $i++) {
                            $intervals[] = $appointments[$i]->starts_at->diffInDays($appointments[$i-1]->starts_at);
                        }
                        $avgInterval = count($intervals) > 0 ? round(array_sum($intervals) / count($intervals)) : 0;
                    @endphp
                    <p class="text-lg font-semibold text-green-600 dark:text-green-400">
                        {{ $avgInterval }} days
                    </p>
                    <p class="text-xs text-gray-500">Between visits</p>
                </div>
            </div>
        </div>

        <!-- Lifetime Rank -->
        <div class="insight-card bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4">
            <div class="flex items-start gap-3">
                <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-lg">
                    <x-heroicon-o-chart-bar class="w-5 h-5 text-purple-600 dark:text-purple-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Customer Rank</p>
                    @php
                        $rank = \App\Models\Customer::where('company_id', $customer->company_id)
                            ->where('total_spent', '>', $customer->total_spent)
                            ->count() + 1;
                    @endphp
                    <p class="text-lg font-semibold text-purple-600 dark:text-purple-400">
                        #{{ $rank }}
                    </p>
                    <p class="text-xs text-gray-500">By lifetime value</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Appointments Chart
    const appointmentsCtx = document.getElementById('appointmentsChart').getContext('2d');
    const appointmentsData = @json($appointmentsByMonth);
    
    new Chart(appointmentsCtx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Appointments',
                data: Array.from({length: 12}, (_, i) => appointmentsData[i + 1] || 0),
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueData = @json($revenueByMonth);
    
    new Chart(revenueCtx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Revenue',
                data: Array.from({length: 12}, (_, i) => revenueData[i + 1] || 0),
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderColor: 'rgb(16, 185, 129)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '€' + value;
                        }
                    }
                }
            }
        }
    });
</script>