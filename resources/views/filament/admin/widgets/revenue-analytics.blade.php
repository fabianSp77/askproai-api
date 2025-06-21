<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Revenue Analytics
        </x-slot>

        <x-slot name="headerActions">
            <x-filament::input.wrapper>
                <x-filament::input.select wire:model.live="period">
                    @foreach($this->getPeriodOptions() as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-slot>

        <!-- Revenue Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Revenue -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">
                            €{{ number_format($revenueData['summary']['total_revenue'] ?? 0, 2) }}
                        </p>
                    </div>
                    <div class="flex items-center">
                        @if(($revenueData['summary']['growth_direction'] ?? 'stable') === 'up')
                            <x-heroicon-m-arrow-trending-up class="w-6 h-6 text-green-500" />
                        @elseif(($revenueData['summary']['growth_direction'] ?? 'stable') === 'down')
                            <x-heroicon-m-arrow-trending-down class="w-6 h-6 text-red-500" />
                        @else
                            <x-heroicon-m-minus class="w-6 h-6 text-gray-500" />
                        @endif
                        <span class="ml-1 text-sm font-medium {{ ($revenueData['summary']['growth_direction'] ?? 'stable') === 'up' ? 'text-green-600' : (($revenueData['summary']['growth_direction'] ?? 'stable') === 'down' ? 'text-red-600' : 'text-gray-600') }}">
                            {{ abs($revenueData['summary']['growth'] ?? 0) }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Collection Rate -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Collection Rate</p>
                        <p class="text-2xl font-bold {{ ($revenueData['summary']['collection_rate'] ?? 0) >= 90 ? 'text-green-600' : (($revenueData['summary']['collection_rate'] ?? 0) >= 80 ? 'text-yellow-600' : 'text-red-600') }}">
                            {{ $revenueData['summary']['collection_rate'] ?? 0 }}%
                        </p>
                    </div>
                    <x-heroicon-o-currency-euro class="w-8 h-8 text-gray-400" />
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    €{{ number_format($revenueData['summary']['total_collected'] ?? 0, 2) }} collected
                </p>
            </div>

            <!-- Outstanding -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Outstanding</p>
                        <p class="text-2xl font-bold text-orange-600">
                            €{{ number_format($revenueData['summary']['outstanding'] ?? 0, 2) }}
                        </p>
                    </div>
                    <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-orange-400" />
                </div>
            </div>

            <!-- Lost Revenue -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Lost Revenue</p>
                        <p class="text-2xl font-bold text-red-600">
                            €{{ number_format($revenueData['summary']['lost_revenue'] ?? 0, 2) }}
                        </p>
                    </div>
                    <x-heroicon-o-x-circle class="w-8 h-8 text-red-400" />
                </div>
                <p class="text-xs text-gray-500 mt-2">From cancellations & no-shows</p>
            </div>
        </div>

        <!-- Revenue Trends Chart -->
        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 mb-6">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Revenue Trends</h3>
            <div class="h-64">
                <canvas id="revenueTrendsChart" wire:ignore></canvas>
            </div>
        </div>

        <!-- Revenue Breakdown -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- By Branch -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Revenue by Branch</h3>
                <div class="space-y-3">
                    @foreach(($revenueData['breakdown']['by_branch'] ?? []) as $branch)
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $branch->name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $branch->appointment_count }} appointments • €{{ number_format($branch->avg_per_appointment, 2) }} avg
                                </p>
                            </div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                €{{ number_format($branch->revenue, 2) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- By Category -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Revenue by Category</h3>
                <div class="space-y-3">
                    @foreach(($revenueData['breakdown']['by_category'] ?? []) as $category)
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $category->category }}</p>
                                <p class="text-xs text-gray-500">{{ $category->count }} services</p>
                            </div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                €{{ number_format($category->revenue, 2) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Top Performers -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Top Services -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Services</h3>
                <div class="space-y-2">
                    @foreach(array_slice($revenueData['top_services'] ?? [], 0, 5) as $service)
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $service->name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $service->booking_count }} bookings • €{{ number_format($service->price, 2) }} each
                                </p>
                            </div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                €{{ number_format($service->total_revenue, 2) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Top Staff -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Top Staff by Revenue</h3>
                <div class="space-y-2">
                    @foreach(array_slice($revenueData['top_staff'] ?? [], 0, 5) as $staff)
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $staff->name }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $staff->appointment_count }} appointments • €{{ number_format($staff->avg_appointment_value, 2) }} avg
                                </p>
                            </div>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">
                                €{{ number_format($staff->revenue, 2) }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Payment Status & Projections -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Payment Status -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Payment Status</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Paid</span>
                        <span class="text-sm font-medium text-green-600">{{ $revenueData['payment_status']['paid'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Pending</span>
                        <span class="text-sm font-medium text-yellow-600">{{ $revenueData['payment_status']['pending'] ?? 0 }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-400">Overdue</span>
                        <span class="text-sm font-medium text-red-600">{{ $revenueData['payment_status']['overdue'] ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <!-- Revenue Projections -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Revenue Projections</h3>
                <div class="space-y-3">
                    @foreach(($revenueData['projections'] ?? []) as $projection)
                        <div class="flex items-center justify-between">
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">{{ $projection['month'] }}</span>
                                <div class="flex items-center mt-1">
                                    <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $projection['confidence'] }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500">{{ $projection['confidence'] }}% confidence</span>
                                </div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                €{{ number_format($projection['projected_revenue'], 2) }}
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </x-filament::section>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            const ctx = document.getElementById('revenueTrendsChart').getContext('2d');
            const revenueData = @json($revenueData['trends'] ?? []);
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: revenueData.map(item => item.label),
                    datasets: [{
                        label: 'Revenue',
                        data: revenueData.map(item => item.revenue),
                        borderColor: 'rgb(59, 130, 246)',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true
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
                                    return '€' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</x-filament-widgets::widget>